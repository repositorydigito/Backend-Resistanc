<?php

namespace App\Filament\Web\Resources;

use App\Filament\Web\Resources\DisciplineResource\Pages;
use App\Models\Discipline;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DisciplineResource extends Resource
{
    protected static ?string $model = Discipline::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationLabel = 'Disciplinas';

    protected static ?string $modelLabel = 'Disciplina';

    protected static ?string $pluralModelLabel = 'Disciplinas';

    protected static ?string $navigationGroup = 'Gestión de Contenido';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información Básica')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Nombre interno de la disciplina'),

                        Forms\Components\TextInput::make('display_name')
                            ->label('Nombre para Mostrar')
                            ->maxLength(255)
                            ->helperText('Nombre que se muestra al público (opcional)'),

                        Forms\Components\Textarea::make('description')
                            ->label('Descripción')
                            ->rows(4)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('color_hex')
                            ->label('Color (Hex)')
                            ->maxLength(7)
                            ->placeholder('#FF5733')
                            ->helperText('Color en formato hexadecimal (ej: #FF5733)'),

                        Forms\Components\Select::make('difficulty_level')
                            ->label('Nivel de Dificultad')
                            ->options([
                                'beginner' => 'Principiante',
                                'intermediate' => 'Intermedio',
                                'advanced' => 'Avanzado',
                                'expert' => 'Experto',
                            ])
                            ->placeholder('Selecciona un nivel'),

                        Forms\Components\TextInput::make('calories_per_hour_avg')
                            ->label('Calorías por Hora (Promedio)')
                            ->numeric()
                            ->placeholder('300')
                            ->helperText('Promedio de calorías quemadas por hora'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Imágenes y Recursos')
                    ->schema([
                        Forms\Components\FileUpload::make('icon_url')
                            ->label('Icono')
                            ->image()
                            ->directory('disciplines/icons')
                            ->visibility('public')
                            ->imageEditor()
                            ->imageEditorAspectRatios([
                                '1:1',
                            ])
                            ->helperText('Icono pequeño de la disciplina (recomendado: cuadrado)'),

                        Forms\Components\FileUpload::make('image_url')
                            ->label('Imagen Principal')
                            ->image()
                            ->directory('disciplines/images')
                            ->visibility('public')
                            ->imageEditor()
                            ->imageEditorAspectRatios([
                                null,
                                '16:9',
                                '4:3',
                            ])
                            ->helperText('Imagen principal de la disciplina'),

                        Forms\Components\FileUpload::make('image_seat')
                            ->label('Imagen de Asiento/Equipo')
                            ->image()
                            ->directory('disciplines/equipment')
                            ->visibility('public')
                            ->imageEditor()
                            ->helperText('Imagen del equipo o asiento utilizado'),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('Equipamiento')
                    ->schema([
                        Forms\Components\TagsInput::make('equipment_required')
                            ->label('Equipamiento Requerido')
                            ->placeholder('Agregar equipo...')
                            ->helperText('Lista de equipos necesarios para esta disciplina')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Configuración')
                    ->schema([
                        Forms\Components\TextInput::make('sort_order')
                            ->label('Orden')
                            ->numeric()
                            ->default(0)
                            ->helperText('Orden de aparición en la página'),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Activo')
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('icon_url')
                    ->label('Icono')
                    ->circular()
                    ->size(40),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('display_name')
                    ->label('Nombre para Mostrar')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('description')
                    ->label('Descripción')
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 50) {
                            return null;
                        }
                        return $state;
                    })
                    ->toggleable(),

                Tables\Columns\ColorColumn::make('color_hex')
                    ->label('Color')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('difficulty_level')
                    ->label('Dificultad')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'beginner' => 'success',
                        'intermediate' => 'warning',
                        'advanced' => 'danger',
                        'expert' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'beginner' => 'Principiante',
                        'intermediate' => 'Intermedio',
                        'advanced' => 'Avanzado',
                        'expert' => 'Experto',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('calories_per_hour_avg')
                    ->label('Cal/Hora')
                    ->suffix(' cal')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Orden')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('difficulty_level')
                    ->label('Dificultad')
                    ->options([
                        'beginner' => 'Principiante',
                        'intermediate' => 'Intermedio',
                        'advanced' => 'Avanzado',
                        'expert' => 'Experto',
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Estado')
                    ->placeholder('Todos')
                    ->trueLabel('Activos')
                    ->falseLabel('Inactivos'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDisciplines::route('/'),
            'create' => Pages\CreateDiscipline::route('/create'),
            'edit' => Pages\EditDiscipline::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->orderBy('sort_order');
    }
}