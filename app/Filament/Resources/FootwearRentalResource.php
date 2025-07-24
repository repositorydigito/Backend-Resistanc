<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FootwearRentalResource\Pages;
use App\Filament\Resources\FootwearRentalResource\RelationManagers;
use App\Models\Footwear;
use App\Models\User;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Collection;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class FootwearRentalResource extends Resource
{
    protected static ?string $model = Footwear::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-on-square';


    protected static ?string $navigationGroup = 'Calzados'; // Nombre del grupo de navegación

    protected static ?string $navigationLabel = 'Préstamos de calzados'; // Nombre del grupo de navegación

    protected static ?string $label = 'Préstamo de calzado'; // Nombre en singular
    protected static ?string $pluralLabel = 'Préstamos de calzado'; // Nombre en plural

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Código')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\ImageColumn::make('image')
                    ->label('Imagen')
                    ->circular()
                    ->size(50),

                Tables\Columns\TextColumn::make('model')
                    ->label('Modelo')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('brand')
                    ->label('Marca')
                    ->searchable()
                    ->sortable(),

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
                    ->sortable(),

                // Columna de usuario actual
                Tables\Columns\TextColumn::make('activeLoan.userClient.name')
                    ->label('Usuario actual')
                    ->default('-')
                    ->formatStateUsing(fn($state, $record) => $record->activeLoan?->userClient?->name ?? '-')
                    ->visible(fn($record) => $record && $record->status === 'in_use'),
            ])
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

                // Tables\Filters\Filter::make('only_available')
                //     ->label('Mostrar solo disponibles')
                //     ->query(fn(Builder $query): Builder => $query->where('status', 'available'))
                //     ->default(), // Activo por defecto
            ])
            ->actions([
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

                        // Forms\Components\Select::make('user_id')
                        //     ->label('Usuario gestor')
                        //     ->options(fn() => User::pluck('name', 'id'))
                        //     ->searchable()
                        //     ->required(),
                        Forms\Components\DateTimePicker::make('estimated_return_date')
                            ->label('Fecha estimada de devolución')
                            ->default(now()),
                        Forms\Components\Textarea::make('notes')
                            ->label('Notas')
                            ->rows(2),
                    ])
                    ->action(function (array $data, Footwear $record) {
                        // Crear historial FootwearLoan
                        $record->loans()->create([
                            'user_client_id' => $data['user_client_id'],
                            // 'user_id' => $data['user_id'], // Eliminado, lo asigna el observer
                            'loan_date' => now(),
                            'estimated_return_date' => $data['estimated_return_date'],
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

                Tables\Actions\EditAction::make()
                    ->visible(fn($record) => $record && $record->status === 'in_use'),
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
                    Tables\Actions\BulkAction::make('markAsAvailable')
                        ->label('Marcar como disponible')
                        ->icon('heroicon-o-check-circle')
                        ->action(function (Collection $records) {
                            $records->each->update(['status' => 'available']);
                        }),

                    Tables\Actions\BulkAction::make('markAsInUse')
                        ->label('Marcar como en uso')
                        ->icon('heroicon-o-arrow-up-on-square')
                        ->action(function (Collection $records) {
                            $records->each->update(['status' => 'in_use']);
                        }),

                    Tables\Actions\DeleteBulkAction::make(),
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
