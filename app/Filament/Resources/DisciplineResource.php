<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DisciplineResource\Pages;
use App\Filament\Resources\DisciplineResource\RelationManagers;
use App\Models\Discipline;
use Filament\Forms;
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

    protected static ?string $navigationGroup = 'Entrenamiento';

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
                Forms\Components\Section::make('Información de la disciplina')
                    ->columns(2)
                    ->schema([

                        Forms\Components\FileUpload::make('icon_url')
                            ->label('Icono')
                            ->columnSpan(2)
                            ->image()
                            ->directory('disciplines/icons') // Carpeta dentro de storage/app/public
                            ->disk('public')    // Usa el filesystem configurado como 'public'
                            ->visibility('public') // Permisos (opcional)
                            ->extraAttributes(['class' => 'h-64 w-64'])
                            ->preserveFilenames() // Opcional: mantiene el nombre original
                            ->maxSize(2048), // Tamaño máximo en KB (opcional)

                        Forms\Components\TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(100),
                        Forms\Components\TextInput::make('display_name')
                            ->label('Nombre para Mostrar')
                            ->required()
                            ->maxLength(100),

                        Forms\Components\Select::make('difficulty_level')
                            ->label('Nivel de Dificultad')
                            ->options([
                                'beginner' => 'Principiante',
                                'intermediate' => 'Intermedio',
                                'advanced' => 'Avanzado',
                                'all_levels' => 'Todos los Niveles',
                            ])
                            ->required(),


                        Forms\Components\TextInput::make('calories_per_hour_avg')
                            ->label('Calorías por Hora Promedio')
                            ->numeric(),

                        // Forms\Components\TextInput::make('sort_order')
                        //     ->label('Orden de Visualización')
                        //     ->required()
                        //     ->numeric()
                        //     ->default(0),
                        Forms\Components\ColorPicker::make('color_hex')
                            ->label('Color')
                            ->required()
                            ->default('#000000'),

                        Forms\Components\Textarea::make('description')
                            ->label('Descripción')
                            ->columnSpanFull(),



                        // En lugar de TextInput, usar:
                        Forms\Components\TagsInput::make('equipment_required')
                            ->dehydrated(true)
                            ->label('Equipos Requeridos')

                            ->placeholder('Presiona Enter después de cada equipo')
                            ->columnSpanFull(),


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
                    ->label('Nombre para Mostrar')
                    ->searchable(),
                Tables\Columns\ImageColumn::make('icon_url')
                    ->label('Icono'),
                Tables\Columns\ColorColumn::make('color_hex')
                    ->label('Color'),
                Tables\Columns\TextColumn::make('difficulty_level')
                    ->label('Nivel de Dificultad')
                    ->formatStateUsing(function ($state) {
                        $levels = [
                            'beginner' => 'Principiante',
                            'intermediate' => 'Intermedio',
                            'advanced' => 'Avanzado',
                            'all_levels' => 'Todos los Niveles'
                        ];
                        return $levels[$state] ?? $state; // Si no encuentra coincidencia, muestra el valor original
                    }),

                Tables\Columns\TextColumn::make('calories_per_hour_avg')
                    ->label('Calorías por Hora Promedio')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('¿Está activa?')
                    ->boolean(),
                // Tables\Columns\TextColumn::make('sort_order')
                //     ->numeric()
                //     ->sortable(),
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
