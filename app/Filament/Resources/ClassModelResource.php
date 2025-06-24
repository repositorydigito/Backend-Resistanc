<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClassModelResource\Pages;
use App\Filament\Resources\ClassModelResource\RelationManagers;
use App\Models\ClassModel;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Log;

class ClassModelResource extends Resource
{
    protected static ?string $model = ClassModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationGroup = 'Entrenamiento';

    protected static ?string $navigationLabel = 'Clases';

    protected static ?string $label = 'Clase'; // Nombre en singular
    protected static ?string $pluralLabel = 'Clases'; // Nombre en plural

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([

                Forms\Components\Toggle::make('is_featured')
                    ->label('Destacada')
                    ->required(),
                Section::make('Información de la clase')
                    ->columns(2)
                    ->schema([

                       Forms\Components\FileUpload::make('img_url')
                            ->label('Imagen')
                            ->disk('public')
                            ->directory('clases')
                            ->visibility('public')
                            ->acceptedFileTypes(['image/*'])
                            ->maxSize(1024 * 5) // 5 MB
                            ->imageResizeMode('crop')
                            ->imageResizeTargetWidth(800)
                            ->imageResizeTargetHeight(600)
                            ->image()
                            ->columnSpanFull()
                            ->required(),

                        Forms\Components\TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('discipline_id')
                            ->searchable()
                            ->preload()
                            ->label('Disciplina')
                            ->relationship('discipline', 'name')
                            ->required(),

                        // Forms\Components\Select::make('instructor_id')
                        //     ->searchable()
                        //     ->label('Instructor')
                        //     ->options(function () {
                        //         // Debug: Ver qué instructores tienes
                        //         $instructors = \App\Models\Instructor::with('user')->get();

                        //         foreach ($instructors as $instructor) {
                        //             Log::info('Instructor: ' . $instructor->name);
                        //             if ($instructor->user) {
                        //                 Log::info('User roles: ' . $instructor->user->roles->pluck('name'));
                        //             }
                        //         }

                        //         return \App\Models\Instructor::where('status', 'active')
                        //             ->pluck('name', 'id');
                        //     })
                        //     ->preload()
                        //     ->required(),




                        // Forms\Components\Select::make('studio_id')
                        //     ->label('Sala')
                        //     ->searchable()
                        //     ->preload()
                        //     ->relationship('studio', 'name')
                        //     ->required(),

                        Forms\Components\TextInput::make('max_capacity')
                            ->label('Capacidad Máxima')
                            ->required()
                            ->numeric(),

                        Forms\Components\Select::make('type')
                            ->label('Modalidad')
                            ->options([
                                'presencial' => 'Presencial',
                                'en_vivo' => 'En Vivo',
                                'grabada' => 'Grabada',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('duration_minutes')
                            ->label('Duración (minutos)')
                            ->required()
                            ->numeric(),

                        Forms\Components\Select::make('difficulty_level')
                            ->label('Nivel de Dificultad')
                            ->options([
                                'beginner' => 'Principiante',
                                'intermediate' => 'Intermedio',
                                'advanced' => 'Avanzado',
                                'all_levels' => 'Todos los Niveles',
                            ])
                            ->required(),

                        Forms\Components\Select::make('status')
                            ->label('Estado')
                            ->options([
                                'active' => 'Activo',
                                'inactive' => 'Inactivo',
                                'draft' => 'Borrador',
                            ]),
                        Forms\Components\TextInput::make('music_genre')
                            ->label('Género Musical')
                            ->maxLength(100),
                        Forms\Components\Textarea::make('special_requirements')
                            ->label('Requerimientos Especiales')
                            ->columnSpanFull(),


                        Forms\Components\Textarea::make('description')
                            ->label('Descripción')
                            ->columnSpanFull(),




                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable(),
                Tables\Columns\TextColumn::make('discipline.name')
                    ->label('Disciplina')
                    ->numeric()
                    ->sortable(),
                // Tables\Columns\TextColumn::make('instructor.name')
                //     ->numeric()
                //     ->sortable(),

                // Tables\Columns\TextColumn::make('studio.name')
                //     ->numeric()
                //     ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'presencial' => 'Presencial',
                        'en_vivo' => 'En Vivo',
                        'grabada' => 'Grabada',
                        default => ucfirst($state),
                    })
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'presencial' => 'primary',
                        'en_vivo' => 'info',
                        'grabada' => 'warning',
                        default => 'gray',
                    })
                    ->label('Modalidad'),
                Tables\Columns\TextColumn::make('duration_minutes')
                    ->label('Duración (minutos)')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('max_capacity')
                    ->label('Capacidad Máxima')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('difficulty_level')
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'beginner' => 'Principiante',
                        'intermediate' => 'Intermedio',
                        'advanced' => 'Avanzado',
                        'all_levels' => 'Todos los Niveles',
                        default => ucfirst($state),
                    })
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'beginner' => 'success',
                        'intermediate' => 'warning',
                        'advanced' => 'danger',
                        'all_levels' => 'primary',
                        default => 'gray',
                    })
                    ->label('Nivel de Dificultad'),
                Tables\Columns\TextColumn::make('music_genre')
                    ->label('Género Musical')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_featured')
                    ->label('Destacada')
                    ->boolean(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'active' => 'Activo',
                        'inactive' => 'Inactivo',
                        'draft' => 'Borrador',
                        default => ucfirst($state),
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'danger',
                        'draft' => 'warning',
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
            RelationManagers\ClassSchedulesRelationManager::class,

        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClassModels::route('/'),
            'create' => Pages\CreateClassModel::route('/create'),
            'edit' => Pages\EditClassModel::route('/{record}/edit'),
        ];
    }
}
