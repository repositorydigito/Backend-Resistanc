<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StudioResource\Pages;
use App\Filament\Resources\StudioResource\RelationManagers;
use App\Models\Studio;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class StudioResource extends Resource
{
    protected static ?string $model = Studio::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    protected static ?string $navigationGroup = 'Configuración General';

    protected static ?string $navigationLabel = 'Salas';

    protected static ?string $label = 'Sala'; // Nombre en singular
    protected static ?string $pluralLabel = 'Salas'; // Nombre en plural

    protected static ?int $navigationSort = 21;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Toggle::make('is_active')
                    ->label('¿Está activa?')
                    ->default(true)
                    ->required(),

                Section::make('Información de la sala')
                    ->columns(2)
                    ->schema([
                        // Sección 1: Información básica
                        Section::make('Datos principales')
                            ->columns(2)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nombre')
                                    ->required()
                                    ->maxLength(100),

                                // Agregar el campo para la relación muchos a muchos con disciplines
                                Forms\Components\Select::make('disciplines')
                                    ->label('Disciplinas')
                                    ->relationship('disciplines', 'name') // 'name' es el campo que se mostrará
                                    // ->multiple()
                                    ->preload()
                                    ->searchable()
                                    ->required(),

                                Forms\Components\TextInput::make('location')
                                    ->label('Ubicación')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpanFull(),

                                Forms\Components\Toggle::make('zigzag')
                                    ->label('¿Mapa en zigzag?')
                                    ->default(false)
                                    ->required(),
                            ]),

                        // Sección 2: Capacidad y distribución
                        // Section::make('Capacidad')
                        //     ->columns(2)
                        //     ->schema([
                        //         Forms\Components\TextInput::make('max_capacity')
                        //             ->label('Aforo total')
                        //             // ->required()
                        //             ->numeric(),

                        //         Forms\Components\TextInput::make('capacity_per_seat')
                        //             ->label('Espacios Disponibles')
                        //             // ->required()
                        //             ->numeric(),


                        //     ]),

                        // Sección 3: Configuración de asientos
                        Section::make('Configuración de asientos')
                            ->columns(2)
                            ->schema([
                                Forms\Components\TextInput::make('row')
                                    ->label('Filas')
                                    ->required()
                                    ->numeric()
                                    ->minValue(1)
                                    // ->maxValue(20)
                                    ->helperText('Número de filas para distribuir los asientos'),

                                Forms\Components\TextInput::make('column')
                                    ->label('Columnas')
                                    ->required()
                                    ->numeric()
                                    ->minValue(1)
                                    // ->maxValue(20)
                                    ->helperText('Número de columnas para distribuir los asientos')
                                    ->live()
                                    ->afterStateUpdated(function ($state, $get, $set) {
                                        $rows = (int) $get('row');
                                        $columns = (int) $state;
                                        $maxCapacity = (int) $get('max_capacity');

                                        if ($rows > 0 && $columns > 0 && $maxCapacity > 0) {
                                            $maxPossible = $rows * $columns;
                                            if ($maxPossible < $maxCapacity) {
                                                // Opcional: ajustar max_capacity o mostrar advertencia
                                                // $set('max_capacity', $maxPossible);
                                            }
                                        }
                                    }),

                                Forms\Components\Placeholder::make('seats_info')
                                    ->label('Información de Asientos')
                                    ->content(function ($record) {
                                        if (!$record) {
                                            return 'Los asientos se generarán automáticamente al crear la sala. Se crearán según la "Capacidad por Asiento" especificada, distribuidos fila por fila según las filas y columnas configuradas.';
                                        }

                                        $seatsCount = $record->seats()->count();
                                        $seatCapacity = $record->capacity_per_seat ?? 0;
                                        $maxPossible = ($record->row ?? 0) * ($record->column ?? 0);

                                        $info = "Asientos generados: {$seatsCount} de {$seatCapacity} (capacidad por asiento)";

                                        if ($maxPossible < $seatCapacity) {
                                            $info .= " | ⚠️ Configuración: {$record->row}×{$record->column} = {$maxPossible} posiciones (menor que capacidad por asiento)";
                                        } else {
                                            $info .= " | Configuración: {$record->row}×{$record->column} posiciones disponibles";
                                        }

                                        $info .= " | Direccionamiento: " . match ($record->addressing) {
                                            'left_to_right' => 'Izquierda a Derecha',
                                            'right_to_left' => 'Derecha a Izquierda',
                                            'center' => 'Centro',
                                            default => 'No definido'
                                        };

                                        return $info;
                                    })
                                    ->columnSpanFull(),
                            ]),

                        // Sección 4: Equipamiento y servicios
                        Section::make('Informacion adicional')
                            ->schema([
                                // Forms\Components\TagsInput::make('equipment_available')
                                //     ->dehydrated(true)
                                //     ->label('Equipamiento Disponible')
                                //     ->placeholder('Presiona Enter después de cada equipo')
                                //     ->columnSpanFull(),

                                Forms\Components\TagsInput::make('amenities')
                                    ->dehydrated(true)
                                    ->label('Servicios')
                                    ->placeholder('Presiona Enter después de cada servicio. Ejemplo: vestuarios, duchas, casilleros, agua_fría, etc.')
                                    ->columnSpanFull(),
                            ]),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable(),
                Tables\Columns\TextColumn::make('location')
                    ->label('Ubicación')
                    ->searchable(),
                // Tables\Columns\TextColumn::make('max_capacity')
                //     ->label('Aforo')
                //     ->numeric()
                //     ->sortable(),

                Tables\Columns\TextColumn::make('seats_count')
                    ->label('Espacios')
                    ->getStateUsing(function ($record) {
                        $seatsCount = $record->seats()->count();
                        $seatCapacity = $record->capacity_per_seat ?? 0;
                        return "{$seatsCount}";
                    })
                    ->badge()
                    ->color(function ($record) {
                        $seatsCount = $record->seats()->count();
                        $seatCapacity = $record->capacity_per_seat ?? 0;
                        return $seatsCount === $seatCapacity ? 'success' : 'warning';
                    }),

                // Tables\Columns\TextColumn::make('class_schedules_count')
                //     ->label('Horarios')
                //     ->getStateUsing(function ($record) {
                //         return $record->classSchedules()->count();
                //     })
                //     ->badge()
                //     ->color(function ($record) {
                //         $count = $record->classSchedules()->count();
                //         return $count > 0 ? 'danger' : 'success';
                //     })
                //     ->tooltip(function ($record) {
                //         $count = $record->classSchedules()->count();
                //         return $count > 0
                //             ? "Esta sala tiene {$count} horario(s) asociado(s) y no puede ser eliminada"
                //             : "Esta sala no tiene horarios asociados";
                //     }),


                Tables\Columns\TextColumn::make('disciplines')
                    ->label('Disciplinas')
                    ->getStateUsing(function ($record) {
                        return $record->disciplines->pluck('name')->join(', ');
                    })
                    ->wrap(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('¿Está activa?')
                    ->boolean(),
                // Tables\Columns\TextColumn::make('created_at')
                //     ->dateTime()
                //     ->sortable()
                //     ->toggleable(isToggledHiddenByDefault: true),
                // Tables\Columns\TextColumn::make('updated_at')
                //     ->dateTime()
                //     ->sortable()
                //     ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function (Studio $record) {
                        if ($record->hasClassSchedules()) {
                            $count = $record->classSchedules()->count();
                            throw new \Exception("No se puede eliminar esta sala porque tiene {$count} horario(s) asociado(s). Primero debe eliminar o reasignar todos los horarios.");
                        }
                    })
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->tooltip(function (Studio $record) {
                        return $record->hasClassSchedules()
                            ? 'No se puede eliminar - tiene horarios asociados'
                            : 'Eliminar sala';
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function ($records) {
                            $studiosWithSchedules = $records->filter(function ($studio) {
                                return $studio->hasClassSchedules();
                            });

                            if ($studiosWithSchedules->isNotEmpty()) {
                                $studioNames = $studiosWithSchedules->pluck('name')->implode(', ');
                                throw new \Exception("No se pueden eliminar las siguientes salas porque tienen horarios asociados: {$studioNames}");
                            }
                        }),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\SeatMapDirectRelationManager::class,
            RelationManagers\SeatsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudios::route('/'),
            'create' => Pages\CreateStudio::route('/create'),
            'edit' => Pages\EditStudio::route('/{record}/edit'),
            'manage-seats' => Pages\ManageStudioSeats::route('/{record}/seats'),
        ];
    }
}
