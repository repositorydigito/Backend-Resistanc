<?php

namespace App\Filament\Resources\ClassScheduleResource\RelationManagers;

use App\Models\ClassScheduleSeat;
use App\Models\Seat;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;


class SeatAssignmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'seatAssignments';

    protected static ?string $title = 'Gestión de Asientos';

    protected static ?string $label = 'Asiento';
    protected static ?string $pluralLabel = 'Asientos';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('seats_id')
                    ->label('Asiento')
                    ->options(function () {
                        // Solo mostrar asientos del estudio de esta clase
                        $studioId = $this->getOwnerRecord()->studio_id;
                        return Seat::where('studio_id', $studioId)
                            ->where('is_active', true)
                            ->orderBy('row')
                            ->orderBy('column')
                            ->get()
                            ->mapWithKeys(function ($seat) {
                                return [$seat->id => "Fila {$seat->row} - Col {$seat->column} (ID: {$seat->id})"];
                            });
                    })
                    ->required()
                    ->searchable(),

                Forms\Components\Select::make('user_id')
                    ->label('Usuario Asignado')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->nullable(),

                Forms\Components\Select::make('status')
                    ->label('Estado')
                    ->options([
                        'available' => '🟢 Disponible',
                        'reserved' => '🟡 Reservado',
                        'occupied' => '🔴 Ocupado',
                        'Completed' => '✅ Completado',
                        'blocked' => '⛔ Bloqueado',
                    ])
                    ->required()
                    ->default('available'),

                Forms\Components\DateTimePicker::make('reserved_at')
                    ->label('Reservado en')
                    ->visible(fn(Forms\Get $get) => in_array($get('status'), ['reserved', 'occupied', 'Completed'])),

                Forms\Components\DateTimePicker::make('expires_at')
                    ->label('Expira en')
                    ->visible(fn(Forms\Get $get) => $get('status') === 'reserved')
                    ->helperText('Solo para reservas temporales'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('seat.seat_number')
            ->columns([
                Tables\Columns\TextColumn::make('seat.id')
                    ->label('Asiento ID')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('seat.row')
                    ->label('Fila')
                    ->sortable(),

                Tables\Columns\TextColumn::make('seat.column')
                    ->label('Columna')
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Usuario')
                    ->searchable()
                    ->placeholder('Sin asignar'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'available' => 'Disponible',
                        'reserved' => 'Reservado',
                        'occupied' => 'Ocupado',
                        'Completed' => 'Completado',
                        'blocked' => 'Bloqueado',
                        default => ucfirst($state),
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'available' => 'success',
                        'reserved' => 'warning',
                        'occupied' => 'danger',
                        'Completed' => 'info',
                        'blocked' => 'gray',
                        default => 'secondary',
                    }),

                Tables\Columns\TextColumn::make('reserved_at')
                    ->label('Reservado')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('expires_at')
                    ->label('Expira')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('-')
                    ->color(fn($record) => $record?->isExpired() ? 'danger' : 'secondary'),
            ])
            ->defaultSort('seat.row')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'available' => 'Disponible',
                        'reserved' => 'Reservado',
                        'occupied' => 'Ocupado',
                        'Completed' => 'Completado',
                        'blocked' => 'Bloqueado',
                    ]),
            ])
            ->headerActions([
                // Tables\Actions\CreateAction::make()
                //     ->label('Asignar Asiento'),

                // Tables\Actions\Action::make('generateSeats')
                //     ->label('Generar Asientos Automáticamente')
                //     ->icon('heroicon-o-squares-plus')
                //     ->color('info')
                //     ->action(function () {
                //         $this->generateSeatsForSchedule();
                //     })
                //     ->requiresConfirmation()
                //     ->modalHeading('Generar Asientos')
                //     ->modalDescription('Esto creará automáticamente asientos para todos los asientos activos del estudio. ¿Continuar?'),

                // Tables\Actions\Action::make('releaseExpired')
                //     ->label('Liberar Expirados')
                //     ->icon('heroicon-o-clock')
                //     ->color('warning')
                //     ->action(function () {
                //         $count = $this->getOwnerRecord()->releaseExpiredReservations();
                //         Notification::make()
                //             ->title('Reservas Liberadas')
                //             ->body("Se liberaron {$count} reservas expiradas.")
                //             ->success()
                //             ->send();
                //     }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),

                Tables\Actions\Action::make('reserve')
                    ->label('Reservar')
                    ->icon('heroicon-o-clock')
                    ->color('warning')
                    ->visible(fn($record) => $record->status === 'available')
                    ->form([
                        Forms\Components\Select::make('user_id')
                            ->label('Usuario')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->required(),
                        Forms\Components\TextInput::make('minutes')
                            ->label('Minutos de reserva')
                            ->numeric()
                            ->default(15)
                            ->required(),
                    ])
                    ->action(function ($record, array $data) {
                        $record->reserve($data['user_id'], $data['minutes']);
                        Notification::make()
                            ->title('Asiento Reservado')
                            ->body('Asiento reservado exitosamente.')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('confirm')
                    ->label('Confirmar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn($record) => $record->status === 'reserved')
                    ->action(function ($record) {
                        $record->confirm();
                        Notification::make()
                            ->title('Reserva Confirmada')
                            ->body('Reserva confirmada exitosamente.')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation(),

                // Tables\Actions\Action::make('release')
                //     ->label('Liberar')
                //     ->icon('heroicon-o-x-circle')
                //     ->color('danger')
                //     ->visible(fn($record) => in_array($record->status, ['reserved', 'occupied']))
                //     ->action(function ($record) {
                //         $record->release();
                //         Notification::make()
                //             ->title('Asiento Liberado')
                //             ->body('Asiento liberado exitosamente.')
                //             ->success()
                //             ->send();
                //     })
                //     ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),

                    Tables\Actions\BulkAction::make('releaseSelected')
                        ->label('Liberar Seleccionados')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->release();
                            }
                            Notification::make()
                                ->title('Asientos Liberados')
                                ->body('Asientos liberados exitosamente.')
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation(),
                ]),
            ]);
    }

    protected function generateSeatsForSchedule(): void
    {
        $schedule = $this->getOwnerRecord();
        $studio = $schedule->studio;

        // Obtener todos los asientos activos del estudio
        $seats = Seat::where('studio_id', $studio->id)
            ->where('is_active', true)
            ->get();

        $created = 0;
        foreach ($seats as $seat) {
            // Solo crear si no existe ya
            $exists = ClassScheduleSeat::where('class_schedules_id', $schedule->id)
                ->where('seats_id', $seat->id)
                ->exists();

            if (!$exists) {
                ClassScheduleSeat::create([
                    'class_schedules_id' => $schedule->id,
                    'seats_id' => $seat->id,
                    'status' => 'available',
                ]);
                $created++;
            }
        }

        Notification::make()
            ->title('Asientos Generados')
            ->body("Se generaron {$created} asientos automáticamente.")
            ->success()
            ->send();
    }
}
