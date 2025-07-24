<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InstructorResource\Pages;
use App\Filament\Resources\InstructorResource\RelationManagers;
use App\Models\Instructor;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class InstructorResource extends Resource
{
    protected static ?string $model = Instructor::class;

    protected static ?string $navigationIcon = 'heroicon-o-hand-raised'; // Fuerza

    protected static ?string $navigationGroup = 'Entrenamiento';

    protected static ?string $navigationLabel = 'Instructores';

    protected static ?string $label = 'Instructor'; // Nombre en singular
    protected static ?string $pluralLabel = 'Instructores'; // Nombre en plural

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Toggle::make('is_head_coach')
                    ->label('¿Es Head Coach?')
                    ->required(),

                Section::make('Información del instructor')
                    ->columns(2)
                    ->schema([
                        // Sección 1: Información personal
                        Section::make('Datos personales')
                            ->columns(2)
                            ->schema([
                                Forms\Components\FileUpload::make('profile_image')
                                    ->label('Imagen de Perfil')
                                    ->directory('instructors/profiles')
                                    ->disk('public')
                                    ->visibility('public')
                                    ->extraAttributes(['class' => 'h-64 w-64'])
                                    ->preserveFilenames()
                                    ->maxSize(2048)
                                    ->columnSpanFull()
                                    ->image(),

                                Forms\Components\TextInput::make('name')
                                    ->label('Nombre')
                                    ->required()
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('email')
                                    ->label('Correo Electrónico')
                                    ->email()
                                    ->required()
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('phone')
                                    ->label('Teléfono')
                                    ->tel()
                                    ->maxLength(15),

                                Forms\Components\Select::make('type_document')
                                    ->label('Tipo de Documento')
                                    ->options([
                                        'dni' => 'DNI',
                                        'passport' => 'Pasaporte',
                                        'other' => 'Otro',
                                    ])
                                    ->required(),

                                Forms\Components\TextInput::make('document_number')
                                    ->label('Número de Documento')
                                    ->required()
                                    ->maxLength(15),
                            ]),

                        // Sección 2: Información profesional
                        Section::make('Información profesional')
                            ->columns(2)
                            ->schema([
                                Forms\Components\Select::make('disciplines')
                                    ->label('Disciplinas')
                                    ->multiple()
                                    ->relationship('disciplines', 'name')
                                    ->preload(),

                                Forms\Components\TextInput::make('experience_years')
                                    ->label('Años de Experiencia')
                                    ->numeric(),

                                Forms\Components\TextInput::make('hourly_rate_soles')
                                    ->label('Tarifa por Hora (S/.)')
                                    ->numeric(),

                                Forms\Components\DatePicker::make('hire_date')
                                    ->label('Fecha de Contratación'),

                                Forms\Components\TextInput::make('rating_average')
                                    ->label('Calificación Promedio')
                                    ->required()
                                    ->numeric()
                                    ->disabled(true)
                                    ->default(0.00),

                                Forms\Components\TextInput::make('total_classes_taught')
                                    ->label('Total de Clases Dictadas')
                                    ->required()
                                    ->numeric()
                                    ->disabled(true)
                                    ->default(0),

                                Forms\Components\Select::make('status')
                                    ->label('Estado')
                                    ->options([
                                        'active' => 'Activo',
                                        'inactive' => 'Inactivo',
                                        'on_leave' => 'En Licencia',
                                        'terminated' => 'Terminado',
                                    ])
                                    ->required(),
                            ]),

                        // Sección 3: Especialidades y certificaciones
                        Section::make('Habilidades y certificaciones')
                            ->schema([
                                Forms\Components\TagsInput::make('specialties')
                                    ->dehydrated(true)
                                    ->label('Especialidades')
                                    ->placeholder('Presiona Enter después de cada especialidad')
                                    ->columnSpanFull()
                                    ->default([])
                                    ->afterStateHydrated(function (Forms\Components\TagsInput $component, $state) {
                                        if (is_string($state)) {
                                            $component->state(json_decode($state, true) ?? []);
                                        }
                                    }),

                                Forms\Components\TagsInput::make('certifications')
                                    ->dehydrated(true)
                                    ->label('Certificaciones')
                                    ->placeholder('Presiona Enter después de cada certificación')
                                    ->columnSpanFull()
                                    ->default([])
                                    ->afterStateHydrated(function (Forms\Components\TagsInput $component, $state) {
                                        if (is_string($state)) {
                                            $component->state(json_decode($state, true) ?? []);
                                        }
                                    }),
                            ]),

                        // Sección 4: Biografía y redes sociales
                        Section::make('Biografía y redes')
                            ->schema([
                                Forms\Components\Textarea::make('bio')
                                    ->label('Biografía')
                                    ->columnSpanFull(),

                                Forms\Components\TextInput::make('instagram_handle')
                                    ->label('Instagram')
                                    ->maxLength(100),
                            ]),

                        // Sección 5: Horario de disponibilidad
                        Section::make('Horario de disponibilidad')
                            ->schema([
                                Forms\Components\Repeater::make('availability_schedule')
                                    ->label('')
                                    ->defaultItems(0)
                                    ->schema([
                                        Forms\Components\Select::make('day')
                                            ->label('Día')
                                            ->options([
                                                'monday' => 'Lunes',
                                                'tuesday' => 'Martes',
                                                'wednesday' => 'Miércoles',
                                                'thursday' => 'Jueves',
                                                'friday' => 'Viernes',
                                                'saturday' => 'Sábado',
                                                'sunday' => 'Domingo',
                                            ])
                                            ->required(),
                                        Forms\Components\TimePicker::make('start_time')
                                            ->label('Hora de Inicio')
                                            ->required(),
                                        Forms\Components\TimePicker::make('end_time')
                                            ->label('Hora de Fin')
                                            ->required(),
                                    ])
                                    ->columns(3)
                                    ->addActionLabel('Agregar Horario')
                                    ->reorderable()
                                    ->collapsible()
                                    ->columnSpanFull()
                            ]),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Correo Electrónico')
                    ->searchable(),

                Tables\Columns\TextColumn::make('document_number')
                    ->label('Número de Documento')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_head_coach')
                    ->label('¿Es Head Coach?')
                    ->boolean(),

                Tables\Columns\TextColumn::make('total_classes_taught')
                    ->label('Total de Clases Dictadas')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('hourly_rate_soles')
                    ->label('Tarifa por Hora (S/.)')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'active' => 'Activo',
                        'inactive' => 'Inactivo',
                        'on_leave' => 'En Licencia',
                        'terminated' => 'Terminado',
                        default => 'Desconocido',
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'danger',
                        'on_leave' => 'warning',
                        'terminated' => 'gray',
                        default => 'secondary',
                    })
                    ->label('Estado'),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            RelationManagers\CoachRatingsRelationManager::class,
            RelationManagers\ClassScheduleRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInstructors::route('/'),
            'create' => Pages\CreateInstructor::route('/create'),
            'edit' => Pages\EditInstructor::route('/{record}/edit'),
        ];
    }
}
