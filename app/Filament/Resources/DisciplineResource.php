<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DisciplineResource\Pages;
use App\Filament\Resources\DisciplineResource\RelationManagers;
use App\Models\Discipline;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DisciplineResource extends Resource
{
    protected static ?string $model = Discipline::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Gestión de Clases';

    protected static ?string $navigationLabel = 'Disciplinas';

    protected static ?string $label = 'Disciplina'; // Nombre en singular
    protected static ?string $pluralLabel = 'Disciplinas'; // Nombre en plural

    protected static ?int $navigationSort = 4;


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Toggle::make('is_active')
                    ->label('¿Está activa?')
                    ->default(true)
                    ->required(),

                Section::make('Información de la disciplina')
                    ->columns(2)
                    ->schema([
                        // Sección 1: Imagen y colores
                        Section::make('Identificación visual')
                            ->schema([
                                Forms\Components\FileUpload::make('icon_url')
                                    ->label('Icono')
                                    ->columnSpanFull()
                                    ->image()
                                    ->directory('disciplines/icons')
                                    ->disk('public')
                                    ->visibility('public')
                                    ->extraAttributes(['class' => 'h-64 w-64'])
                                    ->preserveFilenames()
                                    ->maxSize(2048),
                                Forms\Components\FileUpload::make('image_url')
                                    ->label('Imagen de fondo')
                                    ->columnSpanFull()
                                    ->image()
                                    ->directory('disciplines/images')
                                    ->disk('public')
                                    ->visibility('public')
                                    ->extraAttributes(['class' => 'h-64 w-full object-cover'])
                                    ->preserveFilenames()
                                    ->maxSize(2048),

                                Forms\Components\ColorPicker::make('color_hex')
                                    ->label('Color principal')
                                    ->required()
                                    ->default('#000000'),
                            ]),

                        // Sección 2: Información básica
                        Section::make('Datos principales')
                            ->columns(2)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nombre técnico')
                                    ->required()
                                    ->maxLength(100),

                                Forms\Components\TextInput::make('display_name')
                                    ->label('Nombre para mostrar')
                                    ->required()
                                    ->maxLength(100),

                                Forms\Components\Select::make('difficulty_level')
                                    ->label('Nivel de dificultad')
                                    ->options([
                                        'beginner' => 'Principiante',
                                        'intermediate' => 'Intermedio',
                                        'advanced' => 'Avanzado',
                                        'all_levels' => 'Todos los niveles',
                                    ])
                                    ->required(),

                                Forms\Components\TextInput::make('calories_per_hour_avg')
                                    ->label('Calorías por hora (promedio)')
                                    ->numeric()
                                    ->suffix('cal/h'),
                            ]),

                        // Sección 3: Descripción y equipamiento
                        Section::make('Detalles adicionales')
                            ->schema([
                                Forms\Components\Textarea::make('description')
                                    ->label('Descripción')
                                    ->columnSpanFull()
                                    ->maxLength(500),

                                Forms\Components\TagsInput::make('equipment_required')
                                    ->dehydrated(true)
                                    ->label('Equipamiento necesario')
                                    ->placeholder('Presiona Enter después de cada equipo')
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

                Tables\Columns\TextColumn::make('display_name')
                    ->label('Nombre para mostrar')
                    ->searchable(),

                Tables\Columns\ImageColumn::make('icon_url')
                    ->label('Icono')
                    ->size(40),

                Tables\Columns\ColorColumn::make('color_hex')
                    ->label('Color'),

                Tables\Columns\TextColumn::make('difficulty_level')
                    ->label('Dificultad')
                    ->formatStateUsing(fn($state) => match ($state) {
                        'beginner' => 'Principiante',
                        'intermediate' => 'Intermedio',
                        'advanced' => 'Avanzado',
                        'all_levels' => 'Todos',
                        default => $state
                    })
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'beginner' => 'success',
                        'intermediate' => 'primary',
                        'advanced' => 'danger',
                        'all_levels' => 'gray',
                        default => 'secondary'
                    }),

                Tables\Columns\TextColumn::make('calories_per_hour_avg')
                    ->label('Calorías/h')
                    ->numeric()
                    ->sortable()
                    ->suffix(' cal'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activa')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('difficulty_level')
                    ->label('Nivel de dificultad')
                    ->options([
                        'beginner' => 'Principiante',
                        'intermediate' => 'Intermedio',
                        'advanced' => 'Avanzado',
                        'all_levels' => 'Todos los niveles',
                    ]),

                // Tables\Filters\SelectFilter::make('is_active')
                //     ->label('Estado activo')
                //     ->trueLabel('Solo activas')
                //     ->falseLabel('Solo inactivas')
                //     ->nullableLabel('Todas'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->icon('heroicon-o-trash'),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDisciplines::route('/'),
            'create' => Pages\CreateDiscipline::route('/create'),
            'edit' => Pages\EditDiscipline::route('/{record}/edit'),
        ];
    }
}
