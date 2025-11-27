<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClassScheduleResource\Pages;
use App\Filament\Resources\ClassScheduleResource\RelationManagers;
use App\Models\ClassSchedule;
use App\Models\Instructor;
use Closure;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\FormsComponent;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ClassScheduleResource extends Resource
{
    protected static ?string $model = ClassSchedule::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    protected static ?string $navigationGroup = 'Gestión de Clases';

    protected static ?string $navigationLabel = 'Horarios';

    protected static ?string $label = 'Horario'; // Nombre en singular
    protected static ?string $pluralLabel = 'Horarios'; // Nombre en plural

    protected static ?int $navigationSort = 3;


    public static function form(Form $form): Form
    {
        return $form
            ->schema([

                Forms\Components\Section::make('Información del Horario')
                    ->columns(2)
                    ->schema([

                        Forms\Components\FileUpload::make('img_url')
                            ->label('Imagen')
                            ->disk('public')
                            ->directory('horarios')
                            ->visibility('public')
                            ->acceptedFileTypes(['image/*'])
                            ->maxSize(1024 * 5) // 5 MB
                            ->imageResizeMode('crop')
                            ->imageResizeTargetWidth(800)
                            ->imageResizeTargetHeight(600)
                            ->image()
                            ->columnSpanFull(),

                        Forms\Components\Select::make('class_id')
                            ->label('Clase')
                            ->required()
                            ->relationship('class', 'name')
                            ->searchable()
                            ->preload()
                            ->live() // Reacciona a cambios
                            ->afterStateUpdated(function (Set $set) {
                                $set('instructor_id', null); // Limpiar instructor
                                $set('studio_id', null); // Limpiar sala
                            })
                            ->rules([
                                fn(Get $get): \Closure => function (string $attribute, $value, \Closure $fail) use ($get) {
                                    // Validar que no exista otro horario con la misma clase, fecha y hora de inicio
                                    // Nota: Esta validación es solo para feedback inmediato.
                                    // La validación final se hace en mutateFormDataBeforeCreate/Save
                                    $scheduledDate = $get('scheduled_date');
                                    $startTime = $get('start_time');

                                    if ($value && $scheduledDate && $startTime) {
                                        $existing = \App\Models\ClassSchedule::where('class_id', $value)
                                            ->whereDate('scheduled_date', $scheduledDate)
                                            ->where('start_time', $startTime)
                                            ->where('status', '!=', 'cancelled') // Excluir cancelados
                                            ->first();

                                        if ($existing) {
                                            $class = $existing->class->name ?? 'Clase';
                                            $fail("Ya existe un horario para esta clase el {$scheduledDate} a las {$startTime}. Horario ID: {$existing->id}");
                                        }
                                    }
                                },
                            ]),

                        Forms\Components\Select::make('instructor_id')
                            ->label('Instructor')
                            ->preload()
                            ->required()
                            ->options(function (Get $get) {
                                $classId = $get('class_id');

                                if (!$classId) {
                                    return [];
                                }

                                // Obtener la clase con su disciplina
                                $class = \App\Models\ClassModel::with('discipline')->find($classId);
                                if (!$class || !$class->discipline_id) {
                                    return [];
                                }

                                // Filtrar instructores que enseñan esa disciplina específica
                                return \App\Models\Instructor::whereHas('disciplines', function ($query) use ($class) {
                                    $query->where('discipline_id', $class->discipline_id);
                                })
                                    ->where('status', 'active') // Solo instructores activos
                                    ->pluck('name', 'id');
                            })
                            ->searchable()
                            ->dehydrated() // ✅ AGREGAR ESTO - asegura que se envíe aunque esté disabled
                            ->disabled(fn(Get $get): bool => !filled($get('class_id')))
                            ->live() // Reacciona a cambios
                            ->afterStateUpdated(fn(Set $set) => $set('studio_id', null)) // Limpiar sala al cambiar instructor
                            ->helperText('Primero selecciona una clase para ver instructores disponibles'),


                        Forms\Components\Select::make('studio_id')
                            ->label('Sala/Estudio')
                            ->required()
                            ->options(function (Get $get) {
                                $classId = $get('class_id');

                                if (!$classId) {
                                    return [];
                                }

                                // Obtener la clase con su disciplina
                                $class = \App\Models\ClassModel::with('discipline')->find($classId);
                                if (!$class || !$class->discipline_id) {
                                    return [];
                                }

                                // Filtrar salas/estudios que tienen esa disciplina asociada
                                return \App\Models\Studio::whereHas('disciplines', function ($query) use ($class) {
                                    $query->where('discipline_id', $class->discipline_id);
                                })
                                    ->where('is_active', true) // Solo salas activas
                                    ->pluck('name', 'id');
                            })
                            ->searchable()
                            ->disabled(function (Get $get, $record): bool {
                                // Deshabilitar si no hay class_id seleccionado
                                if (!filled($get('class_id'))) {
                                    return true;
                                }

                                // Deshabilitar si ya tiene asientos reservados POR USUARIOS
                                if ($record && $record->seats()->wherePivotNotNull('user_id')->exists()) {
                                    return true;
                                }

                                return false;
                            })
                            ->helperText(function (Get $get, $record) {
                                if ($record && $record->seats()->wherePivotNotNull('user_id')->exists()) {
                                    $seatsCount = $record->seats()->wherePivotNotNull('user_id')->count();
                                    return "⚠️ No se puede cambiar la sala porque hay {$seatsCount} asiento(s) reservado(s) por usuarios";
                                }
                                if (!filled($get('class_id'))) {
                                    return 'Primero selecciona una clase para ver salas disponibles';
                                }
                                return null;
                            })
                            ->live() // Hacer reactivo para detectar cambios
                            ->afterStateUpdated(function (Get $get, Set $set, $state, $old) {
                                // Solo procesar si realmente cambió la sala y estamos en modo edición
                                if ($state && $old && $state !== $old) {
                                    // Validar que la nueva sala no esté ocupada en este horario
                                    $scheduledDate = $get('scheduled_date');
                                    $startTime = $get('start_time');
                                    $endTime = $get('end_time');

                                    if ($scheduledDate && $startTime && $endTime) {
                                        $conflict = \App\Models\ClassSchedule::where('studio_id', $state)
                                            ->where('scheduled_date', $scheduledDate)
                                            ->where('status', '!=', 'cancelled')
                                            ->where('id', '!=', $get('id')) // Excluir el horario actual
                                            ->where(function ($query) use ($startTime, $endTime) {
                                                $query->where(function ($q) use ($startTime, $endTime) {
                                                    $q->where('start_time', '<', $endTime)
                                                        ->where('end_time', '>', $startTime);
                                                });
                                            })->first();

                                        if ($conflict) {
                                            // Revertir el cambio
                                            $set('studio_id', $old);

                                            // Mostrar notificación de error
                                            \Filament\Notifications\Notification::make()
                                                ->title('Conflicto de Sala')
                                                ->body("La sala ya está ocupada el {$scheduledDate} de {$conflict->start_time} a {$conflict->end_time} por la clase '{$conflict->class->name}'")
                                                ->danger()
                                                ->duration(8000)
                                                ->send();
                                            return;
                                        }
                                    }
                                }
                            })
                            ->rules([
                                fn(Get $get): \Closure => function (string $attribute, $value, \Closure $fail) use ($get) {
                                    // Validación adicional en el servidor
                                    $scheduledDate = $get('scheduled_date');
                                    $startTime = $get('start_time');
                                    $endTime = $get('end_time');
                                    $currentId = $get('id');

                                    if ($value && $scheduledDate && $startTime && $endTime) {
                                        $conflict = \App\Models\ClassSchedule::where('studio_id', $value)
                                            ->where('scheduled_date', $scheduledDate)
                                            ->where('status', '!=', 'cancelled')
                                            ->when($currentId, fn($q) => $q->where('id', '!=', $currentId))
                                            ->where(function ($query) use ($startTime, $endTime) {
                                                $query->where(function ($q) use ($startTime, $endTime) {
                                                    $q->where('start_time', '<', $endTime)
                                                        ->where('end_time', '>', $startTime);
                                                });
                                            })->first();

                                        if ($conflict) {
                                            $fail("La sala ya está ocupada el {$scheduledDate} de {$conflict->start_time} a {$conflict->end_time} por la clase '{$conflict->class->name}'");
                                        }
                                    }
                                },
                            ]),
                        Forms\Components\DatePicker::make('scheduled_date')
                            ->minDate(now()->subDays(1))
                            ->maxDate(now()->addDays(30))
                            ->default(now())
                            ->label('Fecha Programada')
                            ->required()
                            ->live() // Hacer reactivo para validar duplicados
                            ->rules([
                                fn(Get $get): \Closure => function (string $attribute, $value, \Closure $fail) use ($get) {
                                    // Validar que no exista otro horario con la misma clase, fecha y hora de inicio
                                    // Nota: Esta validación es solo para feedback inmediato.
                                    // La validación final se hace en mutateFormDataBeforeCreate/Save
                                    $classId = $get('class_id');
                                    $startTime = $get('start_time');

                                    if ($value && $classId && $startTime) {
                                        $existing = \App\Models\ClassSchedule::where('class_id', $classId)
                                            ->whereDate('scheduled_date', $value)
                                            ->where('start_time', $startTime)
                                            ->where('status', '!=', 'cancelled') // Excluir cancelados
                                            ->first();

                                        if ($existing) {
                                            $class = $existing->class->name ?? 'Clase';
                                            $fail("Ya existe un horario para esta clase el {$value} a las {$startTime}. Horario ID: {$existing->id}");
                                        }
                                    }
                                },
                            ]),

                        Forms\Components\TimePicker::make('start_time')
                            ->label('Hora de Inicio')
                            ->seconds(false)
                            ->live() // Importante para que reaccione a cambios
                            ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                $endTime = $get('end_time');
                                // Si ya hay una hora de fin y es menor que la nueva hora de inicio, limpiarla
                                if ($endTime && $state && $endTime <= $state) {
                                    $set('end_time', null);
                                }
                            })
                            ->required()
                            ->rules([
                                fn(Get $get): \Closure => function (string $attribute, $value, \Closure $fail) use ($get) {
                                    // Validar que no exista otro horario con la misma clase, fecha y hora de inicio
                                    // Nota: Esta validación es solo para feedback inmediato.
                                    // La validación final se hace en mutateFormDataBeforeCreate/Save
                                    $classId = $get('class_id');
                                    $scheduledDate = $get('scheduled_date');

                                    if ($value && $classId && $scheduledDate) {
                                        $existing = \App\Models\ClassSchedule::where('class_id', $classId)
                                            ->whereDate('scheduled_date', $scheduledDate)
                                            ->where('start_time', $value)
                                            ->where('status', '!=', 'cancelled') // Excluir cancelados
                                            ->first();

                                        if ($existing) {
                                            $class = $existing->class->name ?? 'Clase';
                                            $fail("Ya existe un horario para esta clase el {$scheduledDate} a las {$value}. Horario ID: {$existing->id}");
                                        }
                                    }
                                },
                            ]),

                        Forms\Components\TimePicker::make('end_time')
                            ->label('Hora de Fin')
                            ->seconds(false)
                            ->live()
                            ->rules([
                                fn(Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                                    $startTime = $get('start_time');
                                    if ($startTime && $value && $value <= $startTime) {
                                        $fail('La hora de fin debe ser posterior a la hora de inicio.');
                                    }
                                },
                            ])
                            ->afterStateUpdated(fn(Get $get, Set $set) => $set('max_capacity', $get('max_capacity')))
                            ->required(),

                        // Forms\Components\TextInput::make('max_capacity')
                        //     ->label('Capacidad Máxima')
                        //     ->required()
                        //     ->numeric(),

                        // ✅ Campos que solo aparecen en EDITAR y son de SOLO LECTURA
                        Forms\Components\TextInput::make('available_spots')
                            ->label('Lugares Disponibles')
                            ->numeric()
                            ->disabled() // Solo lectura
                            ->dehydrated(false) // No se envía en el formulario
                            ->visible(fn(string $operation): bool => $operation === 'edit'), // Solo en editar

                        // Forms\Components\TextInput::make('booked_spots')
                        //     ->label('Lugares Reservados')
                        //     ->numeric()
                        //     ->default(0)
                        //     ->disabled() // Solo lectura
                        //     ->dehydrated(false) // No se envía en el formulario
                        //     ->visible(fn(string $operation): bool => $operation === 'edit'), // Solo en editar


                        // Forms\Components\TextInput::make('theme')
                        //     ->label('Tema'), // Ocupa todo el ancho

                        Forms\Components\TextInput::make('waitlist_spots')
                            ->label('Lista de Espera')
                            ->numeric()
                            ->default(0)
                            ->disabled() // Solo lectura
                            ->dehydrated(false) // No se envía en el formulario
                            ->visible(fn(string $operation): bool => $operation === 'edit'), // Solo en editar
                        // Forms\Components\DateTimePicker::make('booking_opens_at')
                        //     ->label('Reservas Abren'),
                        // Forms\Components\DateTimePicker::make('booking_closes_at')
                        //     ->label('Reservas Cierran'),
                        // Forms\Components\DateTimePicker::make('cancellation_deadline')
                        //     ->label('Límite de Cancelación'),
                        Forms\Components\Textarea::make('special_notes')
                            ->label('Notas Especiales')
                            ->columnSpanFull(),

                        // 🆕 Vista previa del mapa de asientos
                        // Forms\Components\Placeholder::make('seat_preview')
                        //     ->label('Vista Previa de Asientos')
                        //     ->content(function (Get $get) {
                        //         $studioId = $get('studio_id');
                        //         if (!$studioId) {
                        //             return view('filament.forms.components.studio-seat-empty')->render();
                        //         }

                        //         $studio = \App\Models\Studio::find($studioId);
                        //         if (!$studio) {
                        //             return view('filament.forms.components.studio-seat-not-found')->render();
                        //         }

                        //         return view('filament.forms.components.studio-seat-map', compact('studio'))->render();
                        //     })
                        //     ->columnSpanFull()
                        //     ->visible(fn(Get $get): bool => filled($get('studio_id'))),
                        // Forms\Components\Toggle::make('is_holiday_schedule')
                        //     ->label('Horario de Feriado')
                        //     ->required(),

                        Forms\Components\Select::make('status')
                            ->label('Estado')
                            ->visible(false) // Oculto en el formulario
                            ->options([
                                'scheduled' => 'Programado',
                                'in_progress' => 'En Progreso',
                                'completed' => 'Completado',
                                'cancelled' => 'Cancelado',
                                'postponed' => 'Pospuesto',
                            ])
                            ->default('scheduled')
                            ->required(),
                    ]),

                // Sección de Reemplazo de Instructor (solo en editar)
                Forms\Components\Section::make('Reemplazo de Instructor')
                    ->description('Usar solo cuando el instructor original no pueda asistir y necesite ser reemplazado por un suplente.')
                    ->icon('heroicon-o-arrow-path')
                    ->collapsible()
                    ->collapsed()
                    ->visible(fn(string $operation): bool => $operation === 'edit')
                    ->schema([
                        Forms\Components\Toggle::make('is_replaced')
                            ->label('¿El instructor será reemplazado?')
                            ->live() // Hacer reactivo
                            ->default(false)
                            ->helperText('Marca esta opción si el instructor original no puede asistir y será reemplazado por un suplente.'),

                        Forms\Components\Select::make('substitute_instructor_id')
                            ->label('Instructor Suplente')
                            ->visible(fn(Get $get): bool => $get('is_replaced')) // Solo visible si es reemplazo
                            ->options(function (Get $get) {
                                $classId = $get('class_id');
                                $primary = $get('instructor_id');

                                if (!$classId) {
                                    return [];
                                }

                                // Obtener la clase con su disciplina
                                $class = \App\Models\ClassModel::with('discipline')->find($classId);
                                if (!$class || !$class->discipline_id) {
                                    return [];
                                }

                                // Filtrar instructores que enseñan esa disciplina específica, excluyendo el instructor principal
                                return \App\Models\Instructor::whereHas('disciplines', function ($query) use ($class) {
                                    $query->where('discipline_id', $class->discipline_id);
                                })
                                    ->where('status', 'active') // Solo instructores activos
                                    ->when($primary, fn($query) => $query->where('id', '!=', $primary)) // Excluir instructor principal
                                    ->pluck('name', 'id');
                            })
                            ->searchable()
                            ->nullable()
                            ->helperText('Selecciona el instructor suplente que reemplazará al instructor original. Solo se mostrarán instructores que enseñan la misma disciplina.'),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('class.name')
                    ->label('Clase')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('instructor.name')
                    ->label('Instructor')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('studio.name')
                    ->label('Sala/Estudio')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('scheduled_date')
                    ->label('Fecha')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('start_time')
                    ->label('Hora Inicio')
                    ->time()
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_time')
                    ->label('Hora Fin')
                    ->time()
                    ->sortable(),
                // Tables\Columns\TextColumn::make('max_capacity')
                //     ->label('Capacidad Máxima')
                //     ->numeric()
                //     ->sortable(),

                Tables\Columns\TextColumn::make('seat_assignments_count')
                    ->label('Asientos')
                    ->counts('seatAssignments')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('available_seats_count')
                    ->label('Disponibles')
                    ->getStateUsing(fn($record) => $record->seatAssignments()->where('status', 'available')->count())
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('occupied_seats_count')
                    ->label('Ocupados')
                    ->getStateUsing(fn($record) => $record->seatAssignments()->whereIn('status', ['reserved', 'occupied'])->count())
                    ->badge()
                    ->color('danger'),

                Tables\Columns\TextColumn::make('completed_seats_count')
                    ->label('Completados')
                    ->getStateUsing(fn($record) => $record ? $record->seatAssignments()->where('status', 'completed')->count() : 0)
                    ->badge()
                    ->color('success')
                    ->visible(fn($record) => $record && in_array($record->status, ['completed'])),

                Tables\Columns\TextColumn::make('lost_seats_count')
                    ->label('Perdidos')
                    ->getStateUsing(fn($record) => $record ? $record->seatAssignments()->where('status', 'lost')->count() : 0)
                    ->badge()
                    ->color('gray')
                    ->visible(fn($record) => $record && in_array($record->status, ['completed'])),
                // Tables\Columns\TextColumn::make('available_spots')
                //     ->label('Lugares Disponibles')
                //     ->numeric()
                //     ->sortable(),
                // Tables\Columns\TextColumn::make('booked_spots')
                //     ->numeric()
                //     ->sortable(),
                // Tables\Columns\TextColumn::make('waitlist_spots')
                //     ->numeric()
                //     ->sortable(),
                // Tables\Columns\TextColumn::make('booking_opens_at')
                //     ->dateTime()
                //     ->sortable(),
                // Tables\Columns\TextColumn::make('booking_closes_at')
                //     ->dateTime()
                //     ->sortable(),
                // Tables\Columns\TextColumn::make('cancellation_deadline')
                //     ->dateTime()
                //     ->sortable(),
                // Tables\Columns\IconColumn::make('is_holiday_schedule')
                //     ->boolean(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'scheduled' => 'Programado',
                        'in_progress' => 'En Progreso',
                        'completed' => 'Completado',
                        'cancelled' => 'Cancelado',
                        'postponed' => 'Pospuesto',
                        // default => ucfirst($state),
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'scheduled' => 'gray',
                        'in_progress' => 'warning',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        'postponed' => 'info',
                        default => 'gray',
                    })
                    ->label('Estado'),
                // Tables\Columns\TextColumn::make('created_at')
                //     ->dateTime()
                //     ->sortable()
                //     ->toggleable(isToggledHiddenByDefault: true),
                // Tables\Columns\TextColumn::make('updated_at')
                //     ->dateTime()
                //     ->sortable()
                //     ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('scheduled_date', 'desc')
            ->filters([
                // Filtro de rango de fechas
                Tables\Filters\Filter::make('scheduled_date')
                    ->form([
                        Forms\Components\DatePicker::make('scheduled_from')
                            ->label('Desde')
                            ->displayFormat('d/m/Y')
                            ->placeholder('Fecha inicial')
                            ->columnSpan(1),
                        Forms\Components\DatePicker::make('scheduled_until')
                            ->label('Hasta')
                            ->displayFormat('d/m/Y')
                            ->placeholder('Fecha final')
                            ->columnSpan(1),
                    ])
                    ->columns(2)
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['scheduled_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('scheduled_date', '>=', $date),
                            )
                            ->when(
                                $data['scheduled_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('scheduled_date', '<=', $date),
                            );
                    }),

                // Filtro de estado
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'scheduled' => 'Programado',
                        'in_progress' => 'En Progreso',
                        'completed' => 'Completado',
                        'cancelled' => 'Cancelado',
                        'postponed' => 'Pospuesto',
                    ]),
            ])
            ->filtersLayout(\Filament\Tables\Enums\FiltersLayout::AboveContent)
            ->filtersFormColumns(3)
            ->actions([
                Tables\Actions\Action::make('start_class')
                    ->label('Iniciar Clase')
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->visible(fn($record) => $record->status === 'scheduled')
                    ->requiresConfirmation()
                    ->modalHeading('Iniciar Clase')
                    ->modalDescription('¿Estás seguro de que quieres iniciar esta clase? Los asientos reservados se marcarán como ocupados.')
                    ->action(function ($record) {
                        // Cambiar estado del horario a 'in_progress'
                        $record->update(['status' => 'in_progress']);

                        // Cambiar todos los asientos reservados a ocupados
                        $record->seatAssignments()
                            ->where('status', 'reserved')
                            ->update(['status' => 'occupied']);

                        \Filament\Notifications\Notification::make()
                            ->title('Clase iniciada')
                            ->body('La clase ha sido iniciada. Los asientos reservados ahora están ocupados.')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('finish_class')
                    ->label('Finalizar Clase')
                    ->icon('heroicon-o-stop')
                    ->color('warning')
                    ->visible(fn($record) => $record->status === 'in_progress')
                    ->requiresConfirmation()
                    ->modalHeading('Finalizar Clase')
                    ->modalDescription('¿Estás seguro de que quieres finalizar esta clase? Los asientos ocupados se marcarán como completados y los no ocupados como perdidos.')
                    ->action(function ($record) {
                        // Cargar la relación con la clase para obtener la disciplina
                        $record->load('class.discipline');

                        // Obtener usuarios únicos que completaron la clase (asientos ocupados)
                        $completedSeats = $record->seatAssignments()
                            ->where('status', 'occupied')
                            ->whereNotNull('user_id')
                            ->get();

                        // Extraer IDs únicos de usuarios
                        $userIds = $completedSeats->pluck('user_id')->unique()->toArray();

                        // Obtener el discipline_id de la clase
                        $disciplineId = $record->class->discipline_id ?? null;

                        // Cambiar estado del horario a 'completed'
                        $record->update(['status' => 'completed']);

                        // Obtener asientos ocupados para actualizarlos individualmente
                        // IMPORTANTE: Debemos actualizar cada asiento individualmente para que se disparen los eventos
                        $occupiedSeats = $record->seatAssignments()
                            ->where('status', 'occupied')
                            ->whereNotNull('user_id')
                            ->get();
                        
                        // Actualizar cada asiento individualmente para que se dispare el evento 'updated'
                        // Esto creará los puntos automáticamente para cada usuario
                        foreach ($occupiedSeats as $seat) {
                            try {
                                $seat->status = 'Completed';
                                $seat->save(); // save() dispara el evento updated que crea los puntos
                                
                                \Illuminate\Support\Facades\Log::info('Asiento actualizado a Completed - puntos se crearán automáticamente', [
                                    'seat_id' => $seat->id,
                                    'user_id' => $seat->user_id,
                                    'user_package_id' => $seat->user_package_id,
                                    'user_membership_id' => $seat->user_membership_id,
                                ]);
                            } catch (\Exception $e) {
                                // Log del error pero continuar con los demás asientos
                                \Illuminate\Support\Facades\Log::error('Error al actualizar asiento y crear puntos', [
                                    'seat_id' => $seat->id,
                                    'user_id' => $seat->user_id,
                                    'error' => $e->getMessage(),
                                ]);
                            }
                        }
                        
                        // Log de resumen
                        \App\Models\Log::create([
                            'action' => 'Asientos actualizados a Completed',
                            'description' => "Se procesaron " . count($occupiedSeats) . " asientos ocupados. Los puntos se crearán automáticamente mediante eventos.",
                            'data' => [
                                'class_schedule_id' => $record->id,
                                'seats_processed' => count($occupiedSeats),
                            ],
                        ]);

                        // Cambiar asientos reservados (que no se ocuparon) a perdidos
                        $record->seatAssignments()
                            ->where('status', 'reserved')
                            ->update(['status' => 'lost']);

                        // Pequeña pausa para asegurar que todos los eventos de actualización se hayan procesado
                        // Los eventos crean los puntos y actualizan las clases efectivas automáticamente
                        usleep(200000); // 200ms

                        // Recalcular clases efectivas completadas para cada usuario desde cero
                        // Esto asegura que el contador esté correcto después de todos los eventos
                        foreach ($userIds as $userId) {
                            $user = \App\Models\User::find($userId);
                            if ($user) {
                                // Recalcular clases efectivas desde cero (incluye clases físicas + base de membresía)
                                $effectiveClasses = $user->calculateAndUpdateEffectiveCompletedClasses();
                                
                                // Log para debug
                                \App\Models\Log::create([
                                    'user_id' => $userId,
                                    'action' => 'Clases efectivas recalculadas al finalizar clase',
                                    'description' => "Usuario {$userId}: Clases efectivas completadas = {$effectiveClasses}",
                                    'data' => [
                                        'effective_completed_classes' => $effectiveClasses,
                                        'class_schedule_id' => $record->id,
                                    ],
                                ]);
                                
                                // Recargar el usuario para obtener el valor actualizado
                                $user->refresh();
                            }
                        }

                        // Pequeña pausa adicional para asegurar que los puntos se hayan creado correctamente
                        usleep(100000); // 100ms

                        // Validar puntos acumulados (no expirados) para verificar si alcanzan para la siguiente membresía
                        // Esta validación considera: clases efectivas + puntos no expirados >= clases requeridas
                        foreach ($userIds as $userId) {
                            $user = \App\Models\User::find($userId);
                            if (!$user) {
                                continue;
                            }

                            // Obtener todos los puntos no expirados del usuario
                            $nonExpiredPoints = \App\Models\UserPoint::where('user_id', $userId)
                                ->notExpired()
                                ->get();

                            $totalPoints = $nonExpiredPoints->sum('quantity_point');

                            if ($totalPoints <= 0) {
                                continue;
                            }

                            // Obtener las clases efectivas completadas del usuario (actualizadas después de la clase)
                            $user->refresh();
                            $effectiveClasses = $user->effective_completed_classes ?? 0;

                            // Obtener todas las membresías activas ordenadas por nivel
                            $allMemberships = \App\Models\Membership::where('is_active', true)
                                ->orderBy('level', 'asc')
                                ->get();

                            if ($allMemberships->isEmpty()) {
                                continue;
                            }

                            // Determinar la membresía actual basada en clases completadas
                            $currentMembershipByProgress = null;
                            foreach ($allMemberships as $m) {
                                if ($effectiveClasses >= $m->class_completed) {
                                    $currentMembershipByProgress = $m;
                                } else {
                                    break;
                                }
                            }

                            // Determinar la siguiente membresía
                            $nextMembership = null;
                            if ($currentMembershipByProgress) {
                                // Buscar la membresía con mayor nivel que la actual
                                $nextMembership = $allMemberships
                                    ->filter(function ($m) use ($currentMembershipByProgress) {
                                        return $m->level > $currentMembershipByProgress->level;
                                    })
                                    ->sortBy('level')
                                    ->first();
                            } else {
                                // No tiene membresía actual, buscar la primera que requiere más clases
                                $nextMembership = $allMemberships
                                    ->filter(function ($m) use ($effectiveClasses) {
                                        return $effectiveClasses < $m->class_completed;
                                    })
                                    ->sortBy('level')
                                    ->first();
                            }

                            // Si no hay siguiente membresía, el usuario ya tiene la más alta
                            if (!$nextMembership) {
                                continue;
                            }

                            // Calcular el total considerando clases efectivas + puntos no expirados
                            $totalWithPoints = $effectiveClasses + $totalPoints;

                            // Verificar si alcanza para la siguiente membresía
                            if ($totalWithPoints >= $nextMembership->class_completed) {
                                // Verificar si ya tiene esta membresía activa
                                $hasNextMembership = \App\Models\UserMembership::where('user_id', $userId)
                                    ->where('membership_id', $nextMembership->id)
                                    ->where('status', 'active')
                                    ->where(function ($query) {
                                        $query->whereNull('expiry_date')
                                            ->orWhere('expiry_date', '>', now());
                                    })
                                    ->exists();

                                if (!$hasNextMembership) {
                                    // Crear la membresía automáticamente basada en puntos acumulados
                                    $membershipService = new \App\Services\MembershipService();
                                    $result = $membershipService->evaluateAndCreateMembershipForUser($userId, $disciplineId);

                                    \App\Models\Log::create([
                                        'user_id' => $userId,
                                        'action' => 'Membresía creada por puntos acumulados',
                                        'description' => "Usuario {$userId}: Se validaron {$totalPoints} puntos no expirados + {$effectiveClasses} clases efectivas = {$totalWithPoints} total. Se alcanzó la membresía {$nextMembership->name} (requería {$nextMembership->class_completed}).",
                                        'data' => [
                                            'effective_classes' => $effectiveClasses,
                                            'total_points' => $totalPoints,
                                            'total_with_points' => $totalWithPoints,
                                            'next_membership_id' => $nextMembership->id,
                                            'next_membership_name' => $nextMembership->name,
                                            'next_membership_required' => $nextMembership->class_completed,
                                            'membership_created' => $result['created'] ?? false,
                                        ],
                                    ]);
                                }
                            }
                        }

                        // Evaluar y crear membresías automáticamente para usuarios que completaron la clase
                        // Esto debe hacerse DESPUÉS de cambiar el status a 'Completed' y actualizar clases efectivas
                        // Esta evaluación considera solo las clases completadas (sin puntos)
                        $membershipService = new \App\Services\MembershipService();
                        $results = $membershipService->evaluateAndCreateMembershipsForUsers($userIds, $disciplineId);

                        $notificationBody = 'La clase ha sido finalizada. Los asientos ocupados están completados y los no ocupados están marcados como perdidos.';
                        if ($results['memberships_created'] > 0) {
                            $notificationBody .= " Se crearon {$results['memberships_created']} nueva(s) membresía(s) automáticamente para " . $results['memberships_created'] . " usuario(s).";
                        }

                        // Agregar detalles de debug si hay algún problema
                        if ($results['memberships_created'] === 0 && !empty($results['details'])) {
                            $reasons = array_column($results['details'], 'result');
                            $uniqueReasons = array_unique(array_column($reasons, 'reason'));
                            if (!empty($uniqueReasons)) {
                                $notificationBody .= " Razones: " . implode(", ", array_filter($uniqueReasons));
                            }
                        }

                        \Filament\Notifications\Notification::make()
                            ->title('Clase finalizada')
                            ->body($notificationBody)
                            ->success()
                            ->send();
                    }),

                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\SeatMapVisualRelationManager::class,
            RelationManagers\SeatAssignmentsRelationManager::class,
            RelationManagers\WaitingUserClassRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClassSchedules::route('/'),
            'create' => Pages\CreateClassSchedule::route('/create'),
            'edit' => Pages\EditClassSchedule::route('/{record}/edit'),
            'manage-seats' => Pages\ManageSeats::route('/{record}/seats'),
        ];
    }

}
