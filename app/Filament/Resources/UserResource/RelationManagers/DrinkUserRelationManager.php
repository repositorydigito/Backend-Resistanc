<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Models\ClassSchedule;
use App\Models\Drink;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\FormsComponent;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DrinkUserRelationManager extends RelationManager
{
    protected static string $relationship = 'drinks'; // ✅ Coincide con la relación en User

    protected static ?string $title = 'Bebidas del Usuario';
    protected static ?string $modelLabel = 'Bebida';
    protected static ?string $pluralModelLabel = 'Bebidas';


    public function form(Form $form): Form
    {
        return $form
            ->schema([

                Forms\Components\Select::make('drink_id')
                    ->label('Bebida')
                    ->required()
                    ->options(Drink::all()->pluck('name', 'id'))
                    ->preload()
                    ->searchable(),

                Forms\Components\TextInput::make('quantity')
                    ->label('Cantidad')
                    ->required()
                    ->numeric()
                    ->default(1)
                    ->minValue(1),

                Forms\Components\Select::make('classschedule_id')
                    ->label('Clase (Opcional)')
                    ->options(function () {
                        $userId = $this->getOwnerRecord()->id;

                        return ClassSchedule::whereHas('classScheduleSeats', function ($query) use ($userId) {
                            $query->where('user_id', $userId)
                                ->whereIn('status', ['reserved', 'occupied']);
                        })
                            ->with(['class', 'studio'])
                            ->get()
                            ->mapWithKeys(function ($schedule) {
                                $className = $schedule->class->name ?? 'Sin nombre';
                                $studioName = $schedule->studio->name ?? '';
                                $date = $schedule->scheduled_date;
                                $time = $schedule->start_time;

                                return [$schedule->id => "{$className} - {$date} {$time} ({$studioName})"];
                            });
                    })
                    ->searchable()
                    ->preload()
                    ->nullable()
                    ->helperText('Solo se muestran las clases donde tienes reservas activas'),
            ]);
    }

      public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Bebida')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('pivot.quantity')
                    ->label('Cantidad')
                    ->sortable()
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('price')
                    ->label('Precio unitario')
                    ->money('PEN')
                    ->sortable(),

                Tables\Columns\TextColumn::make('pivot.classschedule_id')
                    ->label('Clase')
                    ->formatStateUsing(function ($state) {
                        if (!$state) return 'Sin clase asignada';

                        $schedule = ClassSchedule::with(['class', 'studio'])->find($state);
                        if (!$schedule) return 'Clase no encontrada';

                        $className = $schedule->class->name ?? 'Sin nombre';
                        $date = $schedule->scheduled_date;
                        $time = $schedule->start_time;

                        return "{$className} - {$date} {$time}";
                    })
                    ->wrap(),

                Tables\Columns\TextColumn::make('total_price')
                    ->label('Total')
                    ->formatStateUsing(function ($record) {
                        return 'S/. ' . number_format($record->price * $record->pivot->quantity, 2);
                    })
                    ->sortable(false), // Deshabilitamos el sort para columnas calculadas

                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'pending' => 'Pendiente',
                        'completed' => 'Completado',
                        'cancelled' => 'Cancelado',
                        default => ucfirst($state),
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'pending' => 'warning',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'secondary',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('pivot.created_at')
                    ->label('Fecha de pedido')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('with_class')
                    ->label('Con clase asignada')
                    ->query(fn($query) => $query->wherePivotNotNull('classschedule_id')),

                Tables\Filters\Filter::make('without_class')
                    ->label('Sin clase asignada')
                    ->query(fn($query) => $query->wherePivotNull('classschedule_id')),

                Tables\Filters\Filter::make('recent')
                    ->label('Últimos 7 días')
                    ->query(function ($query) {
                        return $query->wherePivot('created_at', '>=', now()->subDays(7));
                    }),
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->label('Añadir Bebida')
                    ->preloadRecordSelect()
                    ->form(fn(Tables\Actions\AttachAction $action): array => [
                        $action->getRecordSelect()
                            ->label('Bebida')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->optionsLimit(50),

                        Forms\Components\TextInput::make('quantity')
                            ->label('Cantidad')
                            ->required()
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->maxValue(10)
                            ->step(1),

                        Forms\Components\Select::make('classschedule_id')
                            ->label('Clase (Opcional)')
                            ->options(function () {
                                $userId = $this->getOwnerRecord()->id;

                                return ClassSchedule::whereHas('classScheduleSeats', function ($query) use ($userId) {
                                    $query->where('user_id', $userId)
                                        ->whereIn('status', ['reserved', 'occupied']);
                                })
                                    ->with(['class', 'studio'])
                                    ->orderBy('scheduled_date', 'desc')
                                    ->get()
                                    ->mapWithKeys(function ($schedule) {
                                        $className = $schedule->class->name ?? 'Sin nombre';
                                        $studioName = $schedule->studio->name ?? '';
                                        $date = $schedule->scheduled_date;
                                        $time = $schedule->start_time;

                                        return [$schedule->id => "{$className} - {$date} {$time} ({$studioName})"];
                                    });
                            })
                            ->searchable()
                            ->nullable()
                            ->helperText('Solo se muestran las clases donde tienes reservas activas'),
                    ])
                    ->color('success'),
            ])
            ->actions([
                Tables\Actions\Action::make('edit_pivot')
                    ->label('Editar')
                    ->icon('heroicon-o-pencil')
                    ->color('warning')
                    ->form([
                        Forms\Components\TextInput::make('quantity')
                            ->label('Cantidad')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(10)
                            ->step(1),

                        Forms\Components\Select::make('classschedule_id')
                            ->label('Clase (Opcional)')
                            ->options(function () {
                                $userId = $this->getOwnerRecord()->id;

                                return ClassSchedule::whereHas('classScheduleSeats', function ($query) use ($userId) {
                                    $query->where('user_id', $userId)
                                        ->whereIn('status', ['reserved', 'occupied']);
                                })
                                    ->with(['class', 'studio'])
                                    ->orderBy('scheduled_date', 'desc')
                                    ->get()
                                    ->mapWithKeys(function ($schedule) {
                                        $className = $schedule->class->name ?? 'Sin nombre';
                                        $studioName = $schedule->studio->name ?? '';
                                        $date = $schedule->scheduled_date;
                                        $time = $schedule->start_time;

                                        return [$schedule->id => "{$className} - {$date} {$time} ({$studioName})"];
                                    });
                            })
                            ->searchable()
                            ->nullable(),
                    ])
                    ->fillForm(function ($record): array {
                        return [
                            'quantity' => $record->pivot->quantity,
                            'classschedule_id' => $record->pivot->classschedule_id,
                        ];
                    })
                    ->action(function ($record, array $data): void {
                        $user = $this->getOwnerRecord();

                        $user->drinks()->updateExistingPivot($record->id, [
                            'quantity' => $data['quantity'],
                            'classschedule_id' => $data['classschedule_id'],
                            'updated_at' => now(),
                        ]);
                    }),

                // Tables\Actions\Action::make('view_details')
                //     ->label('Ver detalles')
                //     ->icon('heroicon-o-eye')
                //     ->color('info')
                //     ->infolist([
                //         \Filament\Infolists\Components\TextEntry::make('name')
                //             ->label('Bebida'),
                //         \Filament\Infolists\Components\TextEntry::make('description')
                //             ->label('Descripción'),
                //         \Filament\Infolists\Components\TextEntry::make('price')
                //             ->label('Precio unitario')
                //             ->money('PEN'),
                //         \Filament\Infolists\Components\TextEntry::make('pivot.quantity')
                //             ->label('Cantidad pedida'),
                //         \Filament\Infolists\Components\TextEntry::make('total')
                //             ->label('Total')
                //             ->formatStateUsing(fn($record) => 'S/. ' . number_format($record->price * $record->pivot->quantity, 2)),
                //     ])
                //     ->modalHeading(fn($record) => "Detalles de {$record->name}"),

                Tables\Actions\DetachAction::make()
                    ->label('Quitar')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Quitar bebida')
                    ->modalDescription('¿Estás seguro de que quieres quitar esta bebida del usuario?')
                    ->modalSubmitActionLabel('Sí, quitar'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make()
                        ->label('Quitar seleccionados')
                        ->color('danger')
                        ->requiresConfirmation(),

                    Tables\Actions\BulkAction::make('update_quantity')
                        ->label('Actualizar cantidad')
                        ->icon('heroicon-o-pencil')
                        ->color('warning')
                        ->form([
                            Forms\Components\TextInput::make('new_quantity')
                                ->label('Nueva cantidad')
                                ->required()
                                ->numeric()
                                ->minValue(1)
                                ->maxValue(10),
                        ])
                        ->action(function ($records, array $data) {
                            $user = $this->getOwnerRecord();

                            foreach ($records as $record) {
                                $user->drinks()->updateExistingPivot($record->id, [
                                    'quantity' => $data['new_quantity'],
                                    'updated_at' => now(),
                                ]);
                            }
                        }),
                ]),
            ])
            ->defaultSort('drink_user.created_at', 'desc') // ✅ Usar el nombre completo de la tabla pivot
            ->emptyStateHeading('No hay bebidas asignadas')
            ->emptyStateDescription('Haz clic en "Añadir Bebida" para comenzar.');
    }
}
