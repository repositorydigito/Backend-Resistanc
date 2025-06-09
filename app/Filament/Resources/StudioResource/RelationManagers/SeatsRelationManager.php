<?php

namespace App\Filament\Resources\StudioResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class SeatsRelationManager extends RelationManager
{
    protected static string $relationship = 'seats';

    protected static ?string $title = 'Asientos';

    protected static ?string $modelLabel = 'Asiento';

    protected static ?string $pluralModelLabel = 'Asientos';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('row')
                    ->label('Fila')
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(function () {
                        return $this->getOwnerRecord()->row ?? 20;
                    })
                    ->helperText(function () {
                        $maxRows = $this->getOwnerRecord()->row ?? 0;
                        return "Máximo: {$maxRows} filas (configurado en la sala)";
                    })
                    ->rules([
                        function () {
                            return function (string $attribute, $value, \Closure $fail) {
                                $studio = $this->getOwnerRecord();
                                $column = request()->input('data.column');

                                if ($studio && $column) {
                                    // Check if position already exists
                                    $exists = $studio->seats()
                                        ->where('row', $value)
                                        ->where('column', $column)
                                        ->when($this->record, function ($query) {
                                            return $query->where('id', '!=', $this->record->id);
                                        })
                                        ->exists();

                                    if ($exists) {
                                        $fail("Ya existe un asiento en la fila {$value}, columna {$column}.");
                                    }
                                }
                            };
                        },
                    ]),

                Forms\Components\TextInput::make('column')
                    ->label('Columna')
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(function () {
                        return $this->getOwnerRecord()->column ?? 20;
                    })
                    ->helperText(function () {
                        $maxColumns = $this->getOwnerRecord()->column ?? 0;
                        return "Máximo: {$maxColumns} columnas (configurado en la sala)";
                    })
                    ->live()
                    ->afterStateUpdated(function ($state, $get, $set) {
                        // Trigger validation of row field when column changes
                        $get('row');
                    }),

                Forms\Components\Toggle::make('is_active')
                    ->label('¿Está activo?')
                    ->default(true)
                    ->helperText('Desactivar temporalmente este asiento'),

                Forms\Components\Placeholder::make('addressing_info')
                    ->label('Direccionamiento')
                    ->content(function () {
                        $addressing = $this->getOwnerRecord()->addressing ?? 'left_to_right';
                        return match ($addressing) {
                            'left_to_right' => 'Izquierda a Derecha',
                            'right_to_left' => 'Derecha a Izquierda',
                            'center' => 'Centro',
                            default => $addressing,
                        } . ' (configurado en la sala)';
                    }),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
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

                // Tables\Columns\TextColumn::make('studio.addressing')
                //     ->label('Direccionamiento')
                //     ->badge()
                //     ->color(fn (string $state): string => match ($state) {
                //         'left_to_right' => 'success',
                //         'right_to_left' => 'warning',
                //         'center' => 'info',
                //         default => 'gray',
                //     })
                //     ->formatStateUsing(fn (string $state): string => match ($state) {
                //         'left_to_right' => 'Izq → Der',
                //         'right_to_left' => 'Der → Izq',
                //         'center' => 'Centro',
                //         default => $state,
                //     }),

                // Tables\Columns\TextColumn::make('created_at')
                //     ->label('Creado')
                //     ->dateTime()
                //     ->sortable()
                //     ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('column', 'asc')
            // ->defaultSort('column', 'asc')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Estado')
                    ->placeholder('Todos los asientos')
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
            ->headerActions([
                Tables\Actions\Action::make('regenerate_seats')
                    ->label('Regenerar Asientos')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Regenerar Asientos')
                    ->modalDescription('¿Estás seguro de que quieres regenerar todos los asientos? Esto eliminará los asientos existentes y creará nuevos basados en la configuración actual de la sala.')
                    ->action(function () {
                        $this->getOwnerRecord()->generateSeats();
                        $this->resetTable();
                    }),

                Tables\Actions\CreateAction::make()
                    ->label('Nuevo Asiento'),
            ])
            ->actions([
                Tables\Actions\Action::make('toggle_active')
                    ->label(fn ($record) => $record->is_active ? 'Desactivar' : 'Activar')
                    ->icon(fn ($record) => $record->is_active ? 'heroicon-o-eye-slash' : 'heroicon-o-eye')
                    ->color(fn ($record) => $record->is_active ? 'warning' : 'success')
                    ->action(function ($record) {
                        $record->update(['is_active' => !$record->is_active]);
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activar seleccionados')
                        ->icon('heroicon-o-eye')
                        ->color('success')
                        ->action(function ($records) {
                            $records->each(fn ($record) => $record->update(['is_active' => true]));
                        }),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Desactivar seleccionados')
                        ->icon('heroicon-o-eye-slash')
                        ->color('warning')
                        ->action(function ($records) {
                            $records->each(fn ($record) => $record->update(['is_active' => false]));
                        }),
                    Tables\Actions\DeleteBulkAction::make(),
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
