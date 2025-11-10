<?php

namespace App\Filament\Resources\UserProfileResource\RelationManagers;

use App\Models\ClassSchedule;
use App\Models\Drink;
use App\Models\DrinkUser;
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
    protected static string $relationship = 'drinkUsers';

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
                    ->options(fn () => Drink::orderBy('drink_name')
                        ->pluck('drink_name', 'id')
                        ->toArray())
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
                            })
                            ->toArray();
                    })
                    ->searchable()
                    ->preload()
                    ->nullable()
                    ->helperText('Solo se muestran las clases donde tienes reservas activas'),

                Forms\Components\Select::make('status')
                    ->label('Estado')
                    ->options([
                        'pending' => 'Pendiente',
                        'completed' => 'Completado',
                        'cancelled' => 'Cancelado',
                    ])
                    ->default('pending')
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['drink', 'classSchedule']))
            ->columns([
                Tables\Columns\TextColumn::make('drink.drink_name')
                    ->label('Bebida')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('quantity')
                    ->label('Cantidad')
                    ->sortable()
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('drink.total_price_soles')
                    ->label('Precio unitario')
                    ->money('PEN')
                    ->sortable(),

                Tables\Columns\TextColumn::make('classschedule_id')
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
                    ->formatStateUsing(function (DrinkUser $record) {
                        $unit = $record->drink?->total_price_soles ?? 0;
                        return 'S/. ' . number_format($unit * $record->quantity, 2);
                    })
                    ->sortable(false),

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

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha de pedido')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('with_class')
                    ->label('Con clase asignada')
                    ->query(fn (Builder $query) => $query->whereNotNull('classschedule_id')),

                Tables\Filters\Filter::make('without_class')
                    ->label('Sin clase asignada')
                    ->query(fn (Builder $query) => $query->whereNull('classschedule_id')),

                Tables\Filters\Filter::make('recent')
                    ->label('Últimos 7 días')
                    ->query(fn (Builder $query) => $query->where('created_at', '>=', now()->subDays(7))),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'pending' => 'Pendiente',
                        'completed' => 'Completado',
                        'cancelled' => 'Cancelado',
                    ]),
            ])
            ->headerActions([
                // Tables\Actions\AttachAction::make()
                //     ->label('Añadir Bebida')
                //     ->preloadRecordSelect()
                //     ->form(fn(Tables\Actions\AttachAction $action): array => [
                //         $action->getRecordSelect()
                //             ->label('Bebida')
                //             ->searchable()
                //             ->preload()
                //             ->required()
                //             ->optionsLimit(50),

                //         Forms\Components\TextInput::make('quantity')
                //             ->label('Cantidad')
                //             ->required()
                //             ->numeric()
                //             ->default(1)
                //             ->minValue(1)
                //             ->maxValue(10)
                //             ->step(1),

                //         Forms\Components\Select::make('classschedule_id')
                //             ->label('Clase (Opcional)')
                //             ->options(function () {
                //                 $userId = $this->getOwnerRecord()->id;

                //                 return ClassSchedule::whereHas('classScheduleSeats', function ($query) use ($userId) {
                //                     $query->where('user_id', $userId)
                //                         ->whereIn('status', ['reserved', 'occupied']);
                //                 })
                //                     ->with(['class', 'studio'])
                //                     ->orderBy('scheduled_date', 'desc')
                //                     ->get()
                //                     ->mapWithKeys(function ($schedule) {
                //                         $className = $schedule->class->name ?? 'Sin nombre';
                //                         $studioName = $schedule->studio->name ?? '';
                //                         $date = $schedule->scheduled_date;
                //                         $time = $schedule->start_time;

                //                         return [$schedule->id => "{$className} - {$date} {$time} ({$studioName})"];
                //                     });
                //             })
                //             ->searchable()
                //             ->nullable()
                //             ->helperText('Solo se muestran las clases donde tienes reservas activas'),
                //     ])
                //     ->color('success'),
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
                                    })
                                    ->toArray();
                            })
                            ->searchable()
                            ->nullable(),

                        Forms\Components\Select::make('status')
                            ->label('Estado')
                            ->options([
                                'pending' => 'Pendiente',
                                'completed' => 'Completado',
                                'cancelled' => 'Cancelado',
                            ])
                            ->required(),
                    ])
                    ->fillForm(function (DrinkUser $record): array {
                        return [
                            'quantity' => $record->quantity,
                            'classschedule_id' => $record->classschedule_id,
                            'status' => $record->status,
                        ];
                    })
                    ->action(function ($record, array $data): void {
                        /** @var DrinkUser $record */
                        $record->update([
                            'quantity' => $data['quantity'],
                            'classschedule_id' => $data['classschedule_id'],
                            'status' => $data['status'],
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

                // Tables\Actions\DetachAction::make()
                //     ->label('Quitar')
                //     ->color('danger')
                //     ->requiresConfirmation()
                //     ->modalHeading('Quitar bebida')
                //     ->modalDescription('¿Estás seguro de que quieres quitar esta bebida del usuario?')
                //     ->modalSubmitActionLabel('Sí, quitar'),
            ])
            ->bulkActions([

            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No hay bebidas asignadas')
            ->emptyStateDescription('Haz clic en "Añadir Bebida" para comenzar.');
    }
}
