<?php

namespace App\Filament\Resources\ClassScheduleResource\RelationManagers;

use App\Models\ClassScheduleSeat;
use App\Models\Seat;
use App\Models\WaitingClass;
use App\Services\PackageValidationService;
use DragonCode\Contracts\Http\Builder;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;


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
                // Tables\Columns\TextColumn::make('seat.id')
                //     ->label('Asiento ID')
                //     ->sortable()

                //     ->searchable(),

                Tables\Columns\TextColumn::make('seat.seat_number')
                    ->label('Asiento')
                    ->sortable()
                    ->searchable(),





                Tables\Columns\TextColumn::make('seat.row')
                    ->label('Fila')
                    ->sortable(false),

                Tables\Columns\TextColumn::make('seat.column')
                    ->label('Columna')
                    ->sortable(false),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Usuario')
                    ->searchable()
                    ->placeholder('Sin asignar'),

                Tables\Columns\TextColumn::make('userWaiting.name')
                    ->label('Usuario en Espera')
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
                    ->sortable(false)
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('expires_at')
                    ->label('Expira')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('-')
                    ->color(fn($record) => $record?->isExpired() ? 'danger' : 'secondary'),
            ])
            ->modifyQueryUsing(function ($query) {
                return $query
                    ->join('seats', 'class_schedule_seat.seats_id', '=', 'seats.id')
                    ->orderBy('seats.row')
                    ->orderBy('seats.column')
                    ->select('class_schedule_seat.*'); // importante para evitar conflictos en el resultado
            })
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

                Tables\Actions\Action::make('generateSeats')
                    ->label('Generar Asientos Automáticamente')
                    ->icon('heroicon-o-squares-plus')
                    ->color('info')
                    ->action(function () {
                        $this->generateSeatsForSchedule();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Generar Asientos')
                    ->modalDescription('Esto creará automáticamente asientos para todos los asientos activos del estudio. ¿Continuar?'),

                // 🆕 Botón para asignar usuarios de lista de espera
                Tables\Actions\Action::make('assignWaitingList')
                    ->label('📋 Asignar Lista de Espera')
                    ->icon('heroicon-o-user-group')
                    ->color('warning')
                    ->action(function () {
                        $this->assignWaitingListUsers();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Asignar Usuarios de Lista de Espera')
                    ->modalDescription('¿Deseas asignar usuarios de la lista de espera a asientos disponibles?')
                    ->visible(fn() => $this->hasWaitingListUsers()),

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
                // Tables\Actions\DeleteAction::make(),

                // 🆕 Acción para asignar usuario específico de lista de espera
                Tables\Actions\Action::make('assignWaitingUser')
                    ->label('📋 Asignar de Lista')
                    ->icon('heroicon-o-user-plus')
                    ->color('warning')
                    ->visible(fn($record) => $record->status === 'available')
                    ->form([
                        Forms\Components\Select::make('waiting_user_id')
                            ->label('Usuario de Lista de Espera')
                            ->options(function () {
                                return $this->getWaitingListUsers();
                            })
                            ->required()
                            ->searchable()
                            ->preload(),
                    ])
                    ->action(function ($record, array $data) {
                        $this->assignSpecificWaitingUser($record, $data['waiting_user_id']);
                    }),

                // Tables\Actions\Action::make('reserve')
                //     ->label('Reservar')
                //     ->icon('heroicon-o-clock')
                //     ->color('warning')
                //     ->visible(fn($record) => $record->status === 'available')
                //     ->form([
                //         Forms\Components\Select::make('user_id')
                //             ->label('Usuario')
                //             ->relationship('user', 'name')
                //             ->searchable()
                //             ->required(),
                //         Forms\Components\TextInput::make('minutes')
                //             ->label('Minutos de reserva')
                //             ->numeric()
                //             ->default(15)
                //             ->required(),
                //     ])
                //     ->action(function ($record, array $data) {
                //         $record->reserve($data['user_id'], $data['minutes']);
                //         Notification::make()
                //             ->title('Asiento Reservado')
                //             ->body('Asiento reservado exitosamente.')
                //             ->success()
                //             ->send();
                //     }),

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

                // 🆕 Acción para reasignar asiento reservado a usuario de lista de espera
                Tables\Actions\Action::make('reassignToWaitingUser')
                    ->label('🔄 Reasignar a Lista de Espera')
                    ->icon('heroicon-o-arrow-path')
                    ->color('info')
                    ->visible(fn($record) => $record->status === 'reserved' && $this->hasWaitingListUsers())
                    ->form([
                        Forms\Components\Select::make('waiting_user_id')
                            ->label('Usuario de Lista de Espera')
                            ->options(function () {
                                return $this->getWaitingListUsers();
                            })
                            ->required()
                            ->searchable()
                            ->preload()
                            ->helperText('Selecciona el usuario de la lista de espera al que quieres asignar este asiento reservado.'),
                    ])
                    ->action(function ($record, array $data) {
                        $this->reassignReservedSeatToWaitingUser($record, $data['waiting_user_id']);
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Reasignar Asiento Reservado')
                    ->modalDescription('¿Estás seguro de que quieres reasignar este asiento reservado a un usuario de la lista de espera? Esto liberará la reserva actual.'),

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
                    'code' => $schedule->code . '-' . $seat->id,
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

    // 🆕 Métodos para manejar lista de espera

    /**
     * Verificar si hay usuarios en lista de espera
     */
    protected function hasWaitingListUsers(): bool
    {
        $schedule = $this->getOwnerRecord();
        return WaitingClass::where('class_schedules_id', $schedule->id)
            ->whereIn('status', ['waiting', 'notified'])
            ->exists();
    }

    /**
     * Obtener usuarios de lista de espera
     */
    protected function getWaitingListUsers(): array
    {
        $schedule = $this->getOwnerRecord();
        $packageValidationService = new PackageValidationService();

        return WaitingClass::with(['user', 'userPackage.package'])
            ->where('class_schedules_id', $schedule->id)
            ->whereIn('status', ['waiting', 'notified'])
            ->orderBy('created_at', 'asc')
            ->get()
            ->mapWithKeys(function ($waiting) use ($schedule, $packageValidationService) {
                $userName = $waiting->user->name ?? 'Usuario Desconocido';
                
                // Obtener información de paquetes disponibles para la disciplina
                $schedule->load(['class.discipline']);
                $disciplineId = $schedule->class->discipline_id;
                $availablePackages = $packageValidationService->getUserAvailablePackagesForDiscipline(
                    $waiting->user_id,
                    $disciplineId
                );

                if ($availablePackages->isEmpty()) {
                    $packageInfo = "❌ Sin paquetes disponibles para {$schedule->class->discipline->name}";
                } else {
                    $bestPackage = $availablePackages->sortBy('expiry_date')->first();
                    $packageInfo = "✅ {$bestPackage->package->name} ({$bestPackage->remaining_classes} clases restantes)";
                }

                return [
                    $waiting->id => "{$userName} - {$packageInfo}"
                ];
            })
            ->toArray();
    }

    /**
     * Asignar usuarios de lista de espera automáticamente
     */
    protected function assignWaitingListUsers(): void
    {
        $schedule = $this->getOwnerRecord();
        $packageValidationService = new PackageValidationService();

        // Obtener asientos disponibles
        $availableSeats = ClassScheduleSeat::where('class_schedules_id', $schedule->id)
            ->where('status', 'available')
            ->with('seat')
            ->get();

        // Obtener usuarios en lista de espera
        $waitingUsers = WaitingClass::with(['user', 'userPackage.package'])
            ->where('class_schedules_id', $schedule->id)
            ->whereIn('status', ['waiting', 'notified'])
            ->orderBy('created_at', 'asc')
            ->get();

        $assignedCount = 0;
        $errors = [];

        foreach ($waitingUsers as $waitingUser) {
            if ($availableSeats->isEmpty()) {
                break; // No hay más asientos disponibles
            }

            // Validaciones...
            $existingAssignment = ClassScheduleSeat::where('class_schedules_id', $schedule->id)
                ->where(function($q) use ($waitingUser) {
                    $q->where('user_id', $waitingUser->user_id)
                      ->orWhere('user_waiting_id', $waitingUser->user_id);
                })
                ->whereIn('status', ['reserved', 'occupied'])
                ->where('id', '!=', $availableSeats->first()->id)
                ->first();

            if ($existingAssignment) {
                $errors[] = "Usuario {$waitingUser->user->name}: Ya tiene un asiento asignado en esta clase";
                continue;
            }

            if ($schedule->status === 'cancelled') {
                $errors[] = "Usuario {$waitingUser->user->name}: No se puede asignar en un horario cancelado";
                continue;
            }

            $scheduledDate = $schedule->scheduled_date instanceof \Carbon\Carbon
                ? $schedule->scheduled_date->format('Y-m-d')
                : $schedule->scheduled_date;
            $startDateTime = \Carbon\Carbon::parse($scheduledDate . ' ' . $schedule->start_time);
            $limitToReserve = $startDateTime->copy()->addMinutes(10);
            if (now()->greaterThan($limitToReserve)) {
                $errors[] = "Usuario {$waitingUser->user->name}: No se puede asignar después de los 10 minutos del inicio de la clase";
                continue;
            }

            $validation = $packageValidationService->validateUserPackagesForSchedule($schedule, $waitingUser->user_id);
            if (!$validation['valid']) {
                $errors[] = "Usuario {$waitingUser->user->name}: {$validation['message']}";
                continue;
            }

            $seat = $availableSeats->shift();
            $schedule->load(['class.discipline']);
            $disciplineId = $schedule->class->discipline_id;
            $availablePackages = $packageValidationService->getUserAvailablePackagesForDiscipline(
                $waitingUser->user_id,
                $disciplineId
            );
            if ($availablePackages->isEmpty()) {
                $errors[] = "Usuario {$waitingUser->user->name}: No tiene paquetes disponibles para la disciplina '{$schedule->class->discipline->name}'";
                continue;
            }
            $bestPackage = $availablePackages->sortBy('expiry_date')->first();

            try {
                DB::transaction(function () use ($seat, $waitingUser, $bestPackage, &$assignedCount) {
                    $bestPackage->useClasses(1);
                    $seat->update([
                        // 'user_id' => $waitingUser->user_id, // NO modificar user_id
                        'user_waiting_id' => $waitingUser->user_id,
                        'status' => 'reserved',
                        'reserved_at' => now(),
                        'expires_at' => now()->addMinutes(15),
                        'user_package_id' => $bestPackage->id
                    ]);
                    $waitingUser->update([
                        'status' => 'confirmed',
                        'user_package_id' => $bestPackage->id
                    ]);
                    $assignedCount++;
                });
            } catch (\Exception $e) {
                $errors[] = "Error asignando usuario {$waitingUser->user->name}: " . $e->getMessage();
            }
        }

        if ($assignedCount > 0) {
            Notification::make()
                ->title('Usuarios Asignados')
                ->body("Se asignaron {$assignedCount} usuarios de la lista de espera.")
                ->success()
                ->send();
        }
        if (!empty($errors)) {
            Notification::make()
                ->title('Errores en Asignación')
                ->body('Algunos usuarios no pudieron ser asignados: ' . implode(', ', $errors))
                ->danger()
                ->send();
        }
    }

    /**
     * Asignar un usuario específico de la lista de espera a un asiento
     */
    protected function assignSpecificWaitingUser(ClassScheduleSeat $seat, int $waitingUserId): void
    {
        $schedule = $this->getOwnerRecord();
        $packageValidationService = new PackageValidationService();

        if ($seat->status !== 'available') {
            Notification::make()
                ->title('Error de Validación')
                ->body('Solo se pueden asignar usuarios a asientos disponibles.')
                ->danger()
                ->send();
            return;
        }
        if ($seat->class_schedules_id !== $schedule->id) {
            Notification::make()
                ->title('Error de Validación')
                ->body('El asiento no pertenece a este horario de clase.')
                ->danger()
                ->send();
            return;
        }
        $waitingUser = WaitingClass::with(['user', 'userPackage.package'])
            ->where('id', $waitingUserId)
            ->where('class_schedules_id', $schedule->id)
            ->whereIn('status', ['waiting', 'notified'])
            ->first();
        if (!$waitingUser) {
            Notification::make()
                ->title('Error')
                ->body('Usuario de lista de espera no encontrado o no válido.')
                ->danger()
                ->send();
            return;
        }
        if ($schedule->status === 'cancelled') {
            Notification::make()
                ->title('Error')
                ->body('No se puede asignar en un horario cancelado.')
                ->danger()
                ->send();
            return;
        }
        $scheduledDate = $schedule->scheduled_date instanceof \Carbon\Carbon
            ? $schedule->scheduled_date->format('Y-m-d')
            : $schedule->scheduled_date;
        $startDateTime = \Carbon\Carbon::parse($scheduledDate . ' ' . $schedule->start_time);
        $limitToReserve = $startDateTime->copy()->addMinutes(10);
        if (now()->greaterThan($limitToReserve)) {
            Notification::make()
                ->title('Error')
                ->body('No se puede asignar después de los 10 minutos del inicio de la clase.')
                ->danger()
                ->send();
            return;
        }
        if (($seat->user_id === $waitingUser->user_id) || ($seat->user_waiting_id === $waitingUser->user_id)) {
            Notification::make()
                ->title('Error de Asignación')
                ->body("El usuario {$waitingUser->user->name} ya está asignado a esta butaca.")
                ->danger()
                ->send();
            return;
        }
        $validation = $packageValidationService->validateUserPackagesForSchedule($schedule, $waitingUser->user_id);
        if (!$validation['valid']) {
            Notification::make()
                ->title('Error de Validación de Paquetes')
                ->body("Usuario {$waitingUser->user->name}: {$validation['message']}")
                ->danger()
                ->send();
            return;
        }
        $schedule->load(['class.discipline']);
        $disciplineId = $schedule->class->discipline_id;
        $availablePackages = $packageValidationService->getUserAvailablePackagesForDiscipline(
            $waitingUser->user_id,
            $disciplineId
        );
        if ($availablePackages->isEmpty()) {
            Notification::make()
                ->title('Error de Paquetes')
                ->body("Usuario {$waitingUser->user->name}: No tiene paquetes disponibles para la disciplina '{$schedule->class->discipline->name}'")
                ->danger()
                ->send();
            return;
        }
        $bestPackage = $availablePackages->sortBy('expiry_date')->first();
        try {
            DB::transaction(function () use ($seat, $waitingUser, $bestPackage) {
                $bestPackage->useClasses(1);
                $seat->update([
                    // 'user_id' => $waitingUser->user_id, // NO modificar user_id
                    'user_waiting_id' => $waitingUser->user_id,
                    'status' => 'reserved',
                    'reserved_at' => now(),
                    'expires_at' => now()->addMinutes(15),
                    'user_package_id' => $bestPackage->id
                ]);
                $waitingUser->update([
                    'status' => 'confirmed',
                    'user_package_id' => $bestPackage->id
                ]);
            });
            Notification::make()
                ->title('Usuario Asignado Exitosamente')
                ->body("Usuario {$waitingUser->user->name} asignado al asiento. Paquete usado: {$bestPackage->package->name} (Clases restantes: {$bestPackage->remaining_classes})")
                ->success()
                ->send();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error al asignar usuario específico', [
                'seat_id' => $seat->id,
                'waiting_user_id' => $waitingUserId,
                'schedule_id' => $schedule->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            Notification::make()
                ->title('Error en Asignación')
                ->body("Error asignando usuario: " . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    // 🆕 Nuevo método para reasignar un asiento reservado a un usuario de la lista de espera
    protected function reassignReservedSeatToWaitingUser(ClassScheduleSeat $seat, int $waitingUserId): void
    {
        $schedule = $this->getOwnerRecord();
        $packageValidationService = new PackageValidationService();

        if ($seat->status !== 'reserved') {
            Notification::make()
                ->title('Error de Validación')
                ->body('Solo se pueden reasignar asientos que estén en estado reservado.')
                ->danger()
                ->send();
            return;
        }
        if ($seat->class_schedules_id !== $schedule->id) {
            Notification::make()
                ->title('Error de Validación')
                ->body('El asiento no pertenece a este horario de clase.')
                ->danger()
                ->send();
            return;
        }
        $waitingUser = WaitingClass::with(['user', 'userPackage.package'])
            ->where('id', $waitingUserId)
            ->where('class_schedules_id', $schedule->id)
            ->whereIn('status', ['waiting', 'notified'])
            ->first();
        if (!$waitingUser) {
            Notification::make()
                ->title('Error')
                ->body('Usuario de lista de espera no encontrado o no válido.')
                ->danger()
                ->send();
            return;
        }
        if ($schedule->status === 'cancelled') {
            Notification::make()
                ->title('Error')
                ->body('No se puede reasignar en un horario cancelado.')
                ->danger()
                ->send();
            return;
        }
        $scheduledDate = $schedule->scheduled_date instanceof \Carbon\Carbon
            ? $schedule->scheduled_date->format('Y-m-d')
            : $schedule->scheduled_date;
        $startDateTime = \Carbon\Carbon::parse($scheduledDate . ' ' . $schedule->start_time);
        $limitToReserve = $startDateTime->copy()->addMinutes(10);
        if (now()->greaterThan($limitToReserve)) {
            Notification::make()
                ->title('Error')
                ->body('No se puede reasignar después de los 10 minutos del inicio de la clase.')
                ->danger()
                ->send();
            return;
        }
        if (($seat->user_id === $waitingUser->user_id) || ($seat->user_waiting_id === $waitingUser->user_id)) {
            Notification::make()
                ->title('Error de Asignación')
                ->body("El usuario {$waitingUser->user->name} ya está asignado a esta butaca.")
                ->danger()
                ->send();
            return;
        }
        $validation = $packageValidationService->validateUserPackagesForSchedule($schedule, $waitingUser->user_id);
        if (!$validation['valid']) {
            Notification::make()
                ->title('Error de Validación de Paquetes')
                ->body("Usuario {$waitingUser->user->name}: {$validation['message']}")
                ->danger()
                ->send();
            return;
        }
        $schedule->load(['class.discipline']);
        $disciplineId = $schedule->class->discipline_id;
        $availablePackages = $packageValidationService->getUserAvailablePackagesForDiscipline(
            $waitingUser->user_id,
            $disciplineId
        );
        if ($availablePackages->isEmpty()) {
            Notification::make()
                ->title('Error de Paquetes')
                ->body("Usuario {$waitingUser->user->name}: No tiene paquetes disponibles para la disciplina '{$schedule->class->discipline->name}'")
                ->danger()
                ->send();
            return;
        }
        $bestPackage = $availablePackages->sortBy('expiry_date')->first();
        try {
            DB::transaction(function () use ($seat, $waitingUser, $bestPackage, $schedule) {
                // Si el asiento tenía un user_package_id anterior, devolver clase
                if ($seat->user_id && $seat->user_package_id) {
                    $previousUserPackage = \App\Models\UserPackage::find($seat->user_package_id);
                    if ($previousUserPackage && $previousUserPackage->user_id === $seat->user_id) {
                        $previousUserPackage->refundClasses(1);
                    }
                }
                $bestPackage->useClasses(1);
                $seat->update([
                    // 'user_id' => $waitingUser->user_id, // NO modificar user_id
                    'user_waiting_id' => $waitingUser->user_id,
                    'status' => 'reserved',
                    'reserved_at' => now(),
                    'expires_at' => now()->addMinutes(15),
                    'user_package_id' => $bestPackage->id
                ]);
                $waitingUser->update([
                    'status' => 'confirmed',
                    'user_package_id' => $bestPackage->id
                ]);
            });
            Notification::make()
                ->title('Asiento Reasignado Exitosamente')
                ->body("Asiento reasignado al usuario {$waitingUser->user->name}. Paquete usado: {$bestPackage->package->name} (Clases restantes: {$bestPackage->remaining_classes})")
                ->success()
                ->send();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error al reasignar asiento', [
                'seat_id' => $seat->id,
                'waiting_user_id' => $waitingUserId,
                'schedule_id' => $schedule->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            Notification::make()
                ->title('Error en Reasignación')
                ->body("Error reasignando asiento: " . $e->getMessage())
                ->danger()
                ->send();
        }
    }
}
