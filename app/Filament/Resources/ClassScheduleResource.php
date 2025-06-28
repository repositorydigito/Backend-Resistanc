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

    protected static ?string $navigationGroup = 'Entrenamiento';

    protected static ?string $navigationLabel = 'Horarios';

    protected static ?string $label = 'Horario'; // Nombre en singular
    protected static ?string $pluralLabel = 'Horarios'; // Nombre en plural

    protected static ?int $navigationSort = 4;


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
                            ->afterStateUpdated(fn(Set $set) => $set('instructor_id', null)), // Limpiar instructor

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
                                $class = \App\Models\ClassModel::find($classId);
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
                            ->helperText('Primero selecciona una clase para ver instructores disponibles'),


                        Toggle::make('is_replaced')
                            ->label('¿Sera remplazado?')
                            ->live() // Hacer reactivo
                            ->default(false)
                            ->helperText('Marca si el instructor sera reemplazado. Si es así, selecciona un suplente.'), // Solo en crear/editar
                        // Nuevo campo para suplente

                        Select::make('substitute_instructor_id')
                            ->label('Instructor Suplente')
                            ->visible(fn(Get $get): bool => $get('is_replaced')) // Solo visible si es reemplazo
                            ->options(function ($get) {
                                $primary = $get('instructor_id');

                                return \App\Models\Instructor::query()
                                    ->where('status', 'active')
                                    ->when($primary, fn($query) => $query->where('id', '!=', $primary))
                                    ->pluck('name', 'id');
                            })
                            ->searchable()
                            ->nullable()
                            ->helperText('Seleccione un instructor suplente si es necesario'),


                        Forms\Components\Select::make('studio_id')
                            ->label('Sala/Estudio')
                            ->relationship('studio', 'name')
                            ->required()
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
                            ->helperText(function ($record) {
                                if ($record && $record->seats()->wherePivotNotNull('user_id')->exists()) {
                                    $seatsCount = $record->seats()->wherePivotNotNull('user_id')->count();
                                    return "⚠️ No se puede cambiar la sala porque hay {$seatsCount} asiento(s) reservado(s) por usuarios";
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
                            ->required(),

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
                            ->required(),

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

                        Forms\Components\TextInput::make('max_capacity')
                            ->label('Capacidad Máxima')
                            ->required()
                            ->numeric(),

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


                        Forms\Components\TextInput::make('theme')
                            ->label('Tema'), // Ocupa todo el ancho

                        Forms\Components\TextInput::make('waitlist_spots')
                            ->label('Lista de Espera')
                            ->numeric()
                            ->default(0)
                            ->disabled() // Solo lectura
                            ->dehydrated(false) // No se envía en el formulario
                            ->visible(fn(string $operation): bool => $operation === 'edit'), // Solo en editar
                        Forms\Components\DateTimePicker::make('booking_opens_at')
                            ->label('Reservas Abren'),
                        Forms\Components\DateTimePicker::make('booking_closes_at')
                            ->label('Reservas Cierran'),
                        Forms\Components\DateTimePicker::make('cancellation_deadline')
                            ->label('Límite de Cancelación'),
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
                        //     ->visible(fn(Get $get): bool => filled($get('studio_id'))),http://backend-resistanc.test/admin/class-schedules/5/edit
                        Forms\Components\Toggle::make('is_holiday_schedule')
                            ->label('Horario de Feriado')
                            ->required(),

                        Forms\Components\Select::make('status')
                            ->label('Estado')
                            ->options([
                                'scheduled' => 'Programado',
                                'in_progress' => 'En Progreso',
                                'completed' => 'Completado',
                                'cancelled' => 'Cancelado',
                                'postponed' => 'Pospuesto',
                            ])
                            ->required(),
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
                Tables\Columns\TextColumn::make('max_capacity')
                    ->label('Capacidad Máxima')
                    ->numeric()
                    ->sortable(),

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
                        default => ucfirst($state),
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

                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'scheduled' => 'Programado',
                        'in_progress' => 'En Progreso',
                        'completed' => 'Completado',
                        'cancelled' => 'Cancelado',
                        'postponed' => 'Pospuesto',
                    ])->default('scheduled'),
            ])
            ->actions([

                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
