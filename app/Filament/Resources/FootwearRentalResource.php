<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FootwearRentalResource\Pages;
use App\Filament\Resources\FootwearRentalResource\RelationManagers;
use App\Models\Footwear;
use App\Models\User;

use Filament\Forms;
use Filament\Forms\Form;

use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Collection; // Asegúrate de importar la clase correcta

class FootwearRentalResource extends Resource
{
    protected static ?string $model = Footwear::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-on-square';


    protected static ?string $navigationGroup = 'Gestión de Calzados'; // Nombre del grupo de navegación

    protected static ?string $navigationLabel = 'Préstamo de calzados'; // Nombre del grupo de navegación

    protected static ?string $label = 'Préstamo de calzado'; // Nombre en singular
    protected static ?string $pluralLabel = 'Préstamos de calzado'; // Nombre en plural

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->label('Socio')
                    ->options(function ($record) {
                        $users = \App\Models\User::pluck('name', 'id');
                        if ($record && $record->user_id && !$users->has($record->user_id)) {
                            $user = \App\Models\User::find($record->user_id);
                            if ($user) {
                                $users->put($user->id, $user->name);
                            }
                        }
                        return $users;
                    })
                    ->required()
                    ->searchable()
                    ->visibleOn('create'),

                Forms\Components\TextInput::make('notes')
                    ->label('Notas')
                    ->maxLength(500),

                // Solo en edición: permitir cambiar a dañado/perdido y dejar nota
                Forms\Components\Select::make('status')
                    ->label('Estado')
                    ->options([
                        'maintenance' => 'Dañado (En mantenimiento)',
                        'lost' => 'Perdido',
                    ])
                    ->visibleOn('edit')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Tables\Columns\TextColumn::make('id')
                //     ->label('ID')
                //     ->sortable()
                //     ->searchable(),

                Tables\Columns\TextColumn::make('code')
                    ->label('Código')
                    ->searchable()
                    ->sortable(),

                // Tables\Columns\ImageColumn::make('image')
                //     ->label('Imagen')
                //     ->circular()
                //     ->size(50),

                // Tables\Columns\TextColumn::make('model')
                //     ->label('Modelo')
                //     ->searchable()
                //     ->sortable(),

                // Tables\Columns\TextColumn::make('brand')
                //     ->label('Marca')
                //     ->searchable()
                //     ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'available' => 'success',
                        'out_of_stock' => 'gray',
                        'maintenance' => 'warning',
                        'in_use' => 'primary',
                        'lost' => 'danger'
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'available' => 'Disponible',
                        'out_of_stock' => 'Agotado',
                        'maintenance' => 'En mantenimiento',
                        'in_use' => 'En uso',
                        'lost' => 'Perdido'
                    }),

                Tables\Columns\TextColumn::make('size')
                    ->label('Talla')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('proxima_reserva')
                    ->label('Próxima reserva')
                    ->formatStateUsing(function ($state, $record) {
                        $reserva = $record->reservations()
                            ->where('status', 'pending')
                            ->orderBy('scheduled_date')
                            ->first();
                        Log::info('DEBUG PROXIMA RESERVA', [
                            'footwear_id' => $record->id,
                            'reserva_id' => $reserva?->id,
                            'reserva_status' => $reserva?->status,
                            'reserva_fecha' => $reserva?->scheduled_date,
                            'reserva_user' => $reserva?->userClient?->name,
                        ]);
                        if ($reserva) {
                            $fecha = $reserva->scheduled_date ? \Carbon\Carbon::parse($reserva->scheduled_date)->format('d/m/Y H:i') : '-';
                            $nombre = $reserva->userClient?->name ?? '-';
                            return "$fecha ($nombre)";
                        }
                        return '-';
                    }),

                Tables\Columns\TextColumn::make('activeLoan.userClient.name')
                    ->label('Usuario actual')
                    ->default('-')
                    ->formatStateUsing(fn($state, $record) => $record->activeLoan()->first()?->userClient?->name ?? '-'),
            ])
            ->defaultSort('status', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'available' => 'Disponible',
                        'in_use' => 'En uso',
                        'out_of_stock' => 'Agotado',
                        'maintenance' => 'En mantenimiento',
                        'lost' => 'Perdido'
                    ])
                    ->default(['available', 'in_use']) // Estados mostrados por defecto
                    ->multiple(), // Permite selección múltiple
            ])
            ->actions([
                Tables\Actions\Action::make('marcar_danado_perdido')
                    ->label('Marcar como dañado/perdido')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->color('warning')
                    ->visible(fn($record) => $record && $record->status === 'in_use')
                    ->form([
                        Forms\Components\Select::make('nuevo_estado')
                            ->label('Nuevo estado')
                            ->options([
                                'maintenance' => 'Dañado (En mantenimiento)',
                                'lost' => 'Perdido',
                            ])
                            ->required(),
                        Forms\Components\Textarea::make('notes')
                            ->label('Motivo o nota')
                            ->required(),
                    ])
                    ->action(function (array $data, Footwear $record) {
                        // Cambia el estado del calzado
                        $record->update(['status' => $data['nuevo_estado']]);
                        // Cambia el estado del préstamo activo y agrega la nota
                        $activeLoan = $record->activeLoan()->first();
                        if ($activeLoan) {
                            $activeLoan->update([
                                'status' => $data['nuevo_estado'] === 'lost' ? 'lost' : 'maintenance',
                                'notes' => $data['notes'],
                                'return_date' => now(),
                            ]);
                        }
                    }),
                Tables\Actions\Action::make('prestar')
                    ->label('Prestar')
                    ->icon('heroicon-o-arrow-up-on-square')
                    ->visible(fn($record) => $record && $record->status === 'available')
                    ->form([
                        Forms\Components\Select::make('user_client_id')
                            ->label('Usuario que recibe el préstamo')
                            ->options(fn() => User::pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                        Forms\Components\Textarea::make('notes')
                            ->label('Notas')
                            ->rows(2),
                    ])
                    ->action(function (array $data, Footwear $record) {
                        $record->loans()->create([
                            'user_client_id' => $data['user_client_id'],
                            'loan_date' => now(),
                            'status' => 'in_use',
                            'notes' => $data['notes'] ?? null,
                        ]);
                        $record->update(['status' => 'in_use']);
                    }),
                Tables\Actions\Action::make('devolver')
                    ->label('Devolver')
                    ->icon('heroicon-o-arrow-down-on-square')
                    ->color('success')
                    ->visible(fn($record) => $record && $record->status === 'in_use')
                    ->action(function (Footwear $record) {
                        $activeLoan = $record->activeLoan()->first();
                        if ($activeLoan) {
                            $activeLoan->update([
                                'status' => 'returned',
                                'return_date' => now(),
                            ]);
                        }
                        $record->update(['status' => 'available']);
                    }),
                Tables\Actions\Action::make('aceptar_reserva')
                    ->label('Aceptar próxima reserva')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(
                        fn($record) =>
                        $record->status === 'available' &&
                            $record->reservations()
                            ->where('status', 'pending')
                            ->orderBy('scheduled_date')
                            ->exists()
                    )
                    ->action(function (Footwear $record) {
                        $reserva = $record->reservations()
                            ->where('status', 'pending')
                            ->orderBy('scheduled_date')
                            ->first();
                        if ($reserva) {
                            $reserva->update(['status' => 'confirmed']);
                            $record->update(['status' => 'in_use']);
                        }
                    }),
                // Tables\Actions\EditAction::make()
                //     ->visible(fn($record) => $record && $record->status === 'in_use'),
                Tables\Actions\Action::make('eliminar_historial')
                    ->label('Eliminar historial actual')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->visible(fn($record) => $record && $record->status === 'in_use')
                    ->action(function (Footwear $record) {
                        $activeLoan = $record->activeLoan()->first();
                        if ($activeLoan) {
                            $activeLoan->delete();
                        }
                        $record->update(['status' => 'available']);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('returnAll')
                        ->label('Devolver todos')
                        ->icon('heroicon-o-arrow-down-on-square')
                        ->color('success')
                        ->action(function (Collection $records) {
                            $records->each(function ($record) {
                                $activeLoan = $record->activeLoan()->first();
                                if ($activeLoan) {
                                    $activeLoan->update([
                                        'status' => 'returned',
                                        'return_date' => now(),
                                    ]);
                                }
                                $record->update(['status' => 'available']);
                            });
                        }),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFootwearRentals::route('/'),
            // 'create' => Pages\CreateFootwearRental::route('/create'),
            // 'edit' => Pages\EditFootwearRental::route('/{record}/edit'),
        ];
    }
}
