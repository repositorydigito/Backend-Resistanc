<?php

namespace App\Filament\Resources\StudioResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;

class SeatsRelationManager extends RelationManager
{
    protected static string $relationship = 'seats';

    protected static ?string $title = 'Espacios';

    protected static ?string $modelLabel = 'Espacio';

    protected static ?string $pluralModelLabel = 'Espacios';

    /**
     * Verificar si un asiento está siendo utilizado en algún horario de clase
     */
    protected function isSeatInUse(Model $seat): bool
    {
        return $seat->seatAssignments()->exists();
    }

    /**
     * Obtener información sobre el uso del asiento
     */
    protected function getSeatUsageInfo(Model $seat): array
    {
        $assignments = $seat->seatAssignments()
            ->with(['classSchedule.class', 'classSchedule.studio'])
            ->get();

        $totalAssignments = $assignments->count();
        $activeAssignments = $assignments->whereIn('status', ['reserved', 'occupied'])->count();
        $completedAssignments = $assignments->where('status', 'Completed')->count();
        $availableAssignments = $assignments->where('status', 'available')->count();

        // Contar clases futuras usando una consulta separada
        $upcomingClasses = $seat->seatAssignments()
            ->whereHas('classSchedule', function ($query) {
                $query->where('scheduled_date', '>=', now()->toDateString());
            })
            ->count();

        $usageInfo = [
            'total_assignments' => $totalAssignments,
            'active_assignments' => $activeAssignments,
            'completed_assignments' => $completedAssignments,
            'available_assignments' => $availableAssignments,
            'upcoming_classes' => $upcomingClasses,
        ];

        return $usageInfo;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([

                // Forms\Components\TextInput::make('row')
                //     ->label('Fila')
                //     ->required()
                //     ->numeric()
                //     ->minValue(1)
                //     ->maxValue(function () {
                //         return $this->getOwnerRecord()->row ?? 20;
                //     })
                //     ->helperText(function () {
                //         $maxRows = $this->getOwnerRecord()->row ?? 0;
                //         return "Máximo: {$maxRows} filas (configurado en la sala)";
                //     })
                //     ->rules([
                //         function () {
                //             return function (string $attribute, $value, \Closure $fail) {
                //                 $studio = $this->getOwnerRecord();
                //                 $column = request()->input('data.column');

                //                 if ($studio && $column) {
                //                     // Check if position already exists
                //                     $exists = $studio->seats()
                //                         ->where('row', $value)
                //                         ->where('column', $column)
                //                         ->when($this->record, function ($query) {
                //                             return $query->where('id', '!=', $this->record->id);
                //                         })
                //                         ->exists();

                //                     if ($exists) {
                //                         $fail("Ya existe un asiento en la fila {$value}, columna {$column}.");
                //                     }
                //                 }
                //             };
                //         },
                //     ]),

                // Forms\Components\TextInput::make('column')
                //     ->label('Columna')
                //     ->required()
                //     ->numeric()
                //     ->minValue(1)
                //     ->maxValue(function () {
                //         return $this->getOwnerRecord()->column ?? 20;
                //     })
                //     ->helperText(function () {
                //         $maxColumns = $this->getOwnerRecord()->column ?? 0;
                //         return "Máximo: {$maxColumns} columnas (configurado en la sala)";
                //     })
                //     ->live()
                //     ->afterStateUpdated(function ($state, $get, $set) {
                //         // Trigger validation of row field when column changes
                //         $get('row');
                //     }),

                // Forms\Components\Toggle::make('is_active')
                //     ->label('¿Está activo?')
                //     ->default(true)
                //     ->helperText('Desactivar temporalmente este asiento'),



                //     Forms\Components\Placeholder::make('addressing_info')
                //     ->label('Direccionamiento')
                //     ->content(function () {
                //         $addressing = $this->getOwnerRecord()->addressing ?? 'left_to_right';
                //         return match ($addressing) {
                //             'left_to_right' => 'Izquierda a Derecha',
                //             'right_to_left' => 'Derecha a Izquierda',
                //             'center' => 'Centro',
                //             default => $addressing,
                //         } . ' (configurado en la sala)';
                //     }),

                Forms\Components\TextInput::make('seat_number')
                    ->label('Espacio')
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    // ->unique(ignoreRecord: true)
                    ->maxValue(function () {
                        return $this->getOwnerRecord()->seats()->count() + 1;
                    })
                    ->helperText('Número de espacio único dentro de la sala.'),

            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('seat_number')
                    ->label('Espacio')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('row')
                    ->label('Fila')
                    ->sortable(),

                Tables\Columns\TextColumn::make('column')
                    ->label('Columna')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean()
                    ->sortable(),

                // Nueva columna para mostrar si está en uso
                // Tables\Columns\TextColumn::make('usage_status')
                //     ->label('Estado de Uso')
                //     ->getStateUsing(function (Model $record) {
                //         if ($this->isSeatInUse($record)) {
                //             $usageInfo = $this->getSeatUsageInfo($record);
                //             $totalCount = $usageInfo['total_assignments'];
                //             $upcomingCount = $usageInfo['upcoming_classes'];

                //             if ($upcomingCount > 0) {
                //                 return "🔵 Asignado ({$totalCount} total, {$upcomingCount} futuras)";
                //             } else {
                //                 return "✅ Solo histórico ({$totalCount} asignaciones)";
                //             }
                //         }
                //         return "🟢 Sin asignar";
                //     })
                //     ->badge()
                //     ->color(function (Model $record) {
                //         if ($this->isSeatInUse($record)) {
                //             $usageInfo = $this->getSeatUsageInfo($record);
                //             if ($usageInfo['upcoming_classes'] > 0) {
                //                 return 'info';
                //             } else {
                //                 return 'success';
                //             }
                //         }
                //         return 'success';
                //     }),

            ])
            ->defaultSort('column', 'asc')
            // ->defaultSort('column', 'asc')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Estado')
                    ->placeholder('Todos los espacios')
                    ->trueLabel('Solo activos')
                    ->falseLabel('Solo inactivos'),

                Tables\Filters\SelectFilter::make('studio.addressing')
                    ->label('Direccionamiento')
                    ->options([
                        'left_to_right' => 'Izquierda a Derecha',
                        'right_to_left' => 'Derecha a Izquierda',
                        'center' => 'Centro',
                    ]),
            ])
            ->modifyQueryUsing(function ($query) {
                return $query
                    ->orderBy('row')
                    ->orderBy('column');
            })
            ->headerActions([
                // Tables\Actions\Action::make('regenerate_seats')
                //     ->label('Regenerar espacios')
                //     ->icon('heroicon-o-arrow-path')
                //     ->color('warning')
                //     ->requiresConfirmation()
                //     ->modalHeading('Regenerar espacios')
                //     ->modalDescription('¿Estás seguro de que quieres regenerar todos los espacios? Esto eliminará los espacios existentes y creará nuevos basados en la configuración actual de la sala.')
                //     ->action(function () {
                //         $this->getOwnerRecord()->generateSeats();
                //         $this->resetTable();
                //     }),

                // Tables\Actions\Action::make('add_seat')
                //     ->label('Agregar Espacio')
                //     ->icon('heroicon-o-plus')
                //     ->color('success')
                //     ->form([
                //         Forms\Components\TextInput::make('row')
                //             ->label('Fila')
                //             ->required()
                //             ->numeric()
                //             ->minValue(1)
                //             ->maxValue(function () {
                //                 return $this->getOwnerRecord()->row ?? 20;
                //             })
                //             ->helperText(function () {
                //                 $maxRows = $this->getOwnerRecord()->row ?? 0;
                //                 return "Máximo: {$maxRows} filas (configurado en la sala)";
                //             }),
                //         Forms\Components\TextInput::make('column')
                //             ->label('Columna')
                //             ->required()
                //             ->numeric()
                //             ->minValue(1)
                //             ->maxValue(function () {
                //                 return $this->getOwnerRecord()->column ?? 20;
                //             })
                //             ->helperText(function () {
                //                 $maxColumns = $this->getOwnerRecord()->column ?? 0;
                //                 return "Máximo: {$maxColumns} columnas (configurado en la sala)";
                //             }),
                //     ])
                //     ->action(function (array $data) {
                //         $studio = $this->getOwnerRecord();

                //         // Verificar si la posición ya existe
                //         $existingSeat = $studio->seats()
                //             ->where('row', $data['row'])
                //             ->where('column', $data['column'])
                //             ->first();

                //         if ($existingSeat) {
                //             Notification::make()
                //                 ->title('Posición ocupada')
                //                 ->body("Ya existe un espacio en la fila {$data['row']}, columna {$data['column']}")
                //                 ->danger()
                //                 ->send();
                //             return;
                //         }

                //         // Agregar el nuevo asiento y reordenar
                //         $newSeat = $studio->addSeat($data['row'], $data['column']);

                //         Notification::make()
                //             ->title('Espacio agregado')
                //             ->body("Nuevo espacio creado en fila {$data['row']}, columna {$data['column']} con número {$newSeat->seat_number}")
                //             ->success()
                //             ->send();

                //         $this->resetTable();
                //     }),

                Tables\Actions\Action::make('reorder_seats')
                    ->label('Reordenar Números')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Reordenar números de espacios')
                    ->modalDescription('¿Estás seguro de que quieres reordenar los números de espacios? Esto asegurará que los números sean secuenciales empezando desde 1.')
                    ->action(function () {
                        $studio = $this->getOwnerRecord();
                        $studio->reorderSeatNumbers();

                        Notification::make()
                            ->title('Números reordenados')
                            ->body('Los números de espacios han sido reordenados secuencialmente')
                            ->success()
                            ->send();

                        $this->resetTable();
                    }),

                // Tables\Actions\CreateAction::make()
                //     ->label('Nuevo Asiento'),
            ])
            ->actions([
                Tables\Actions\Action::make('toggle_active')
                    ->label(fn($record) => $record->is_active ? 'Desactivar' : 'Activar')
                    ->icon(fn($record) => $record->is_active ? 'heroicon-o-eye-slash' : 'heroicon-o-eye')
                    ->color(fn($record) => $record->is_active ? 'warning' : 'success')
                    ->action(function ($record) {
                        $record->update(['is_active' => !$record->is_active]);
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function (Model $record) {
                        // Verificar si el asiento está siendo utilizado
                        if ($this->isSeatInUse($record)) {
                            $usageInfo = $this->getSeatUsageInfo($record);

                            $message = "No se puede eliminar este espacio porque está asignado a horarios de clase:\n";
                            $message .= "• Total de asignaciones: {$usageInfo['total_assignments']}\n";
                            $message .= "• Asignaciones activas: {$usageInfo['active_assignments']}\n";
                            $message .= "• Asignaciones disponibles: {$usageInfo['available_assignments']}\n";
                            $message .= "• Clases futuras: {$usageInfo['upcoming_classes']}";

                            Notification::make()
                                ->title('No se puede eliminar')
                                ->body($message)
                                ->danger()
                                ->send();

                            return false; // Cancela la eliminación
                        }

                        return true; // Permite la eliminación
                    })
                    ->action(function (Model $record) {
                        $studio = $this->getOwnerRecord();
                        $seatNumber = $record->seat_number;
                        $row = $record->row;
                        $column = $record->column;

                        // Usar el método del modelo Studio que incluye reordenamiento
                        $deleted = $studio->deleteSeat($record->id);

                        if ($deleted) {
                            Notification::make()
                                ->title('Espacio eliminado')
                                ->body("Espacio {$seatNumber} (fila {$row}, columna {$column}) eliminado y números reordenados")
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Error al eliminar')
                                ->body('No se pudo eliminar el espacio')
                                ->danger()
                                ->send();
                        }
                    })
                    ->visible(fn (Model $record) => !$this->isSeatInUse($record)),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activar seleccionados')
                        ->icon('heroicon-o-eye')
                        ->color('success')
                        ->action(function ($records) {
                            $records->each(fn($record) => $record->update(['is_active' => true]));
                        }),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Desactivar seleccionados')
                        ->icon('heroicon-o-eye-slash')
                        ->color('warning')
                        ->action(function ($records) {
                            $records->each(fn($record) => $record->update(['is_active' => false]));
                        }),
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function ($records) {
                            $recordsInUse = $records->filter(fn($record) => $this->isSeatInUse($record));

                            if ($recordsInUse->count() > 0) {
                                $message = "No se pueden eliminar {$recordsInUse->count()} espacios porque están asignados a horarios de clase.";

                                Notification::make()
                                    ->title('No se pueden eliminar algunos espacios')
                                    ->body($message)
                                    ->danger()
                                    ->send();

                                return false; // Cancela la eliminación masiva
                            }

                            return true; // Permite la eliminación
                        }),
                ]),
            ])
            ->defaultSort('row')
            ->defaultSort('column', 'asc');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['studio_id'] = $this->getOwnerRecord()->id;

        // Ensure is_active is set
        if (!isset($data['is_active'])) {
            $data['is_active'] = true;
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['studio_id'] = $this->getOwnerRecord()->id;

        return $data;
    }
}
