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

    protected static ?string $navigationGroup = 'Administración';

    protected static ?string $navigationLabel = 'Salas';

    protected static ?string $label = 'Sala'; // Nombre en singular
    protected static ?string $pluralLabel = 'Salas'; // Nombre en plural

    protected static ?int $navigationSort = 2;

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

                        Forms\Components\TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(100),
                        Forms\Components\TextInput::make('location')
                            ->label('Ubicación')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('studio_type')
                            ->label('Tipo de Sala')
                            ->options([
                                'cycling' => 'Ciclo',
                                'reformer' => 'Reformer',
                                'mat' => 'Mat',
                                'multipurpose' => 'Multipropósito',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('max_capacity')
                            ->label('Capacidad Máxima')
                            ->required()
                            ->numeric(),

                        // En lugar de TextInput, usar:
                        Forms\Components\TagsInput::make('equipment_available')
                            ->dehydrated(true)
                            ->label('Equipamiento Disponible')
                            ->placeholder('Presiona Enter después de cada equipo')
                            ->columnSpanFull(),



                        // En lugar de TextInput, usar:
                        Forms\Components\TagsInput::make('amenities')
                            ->dehydrated(true)
                            ->label('Servicios')
                            ->placeholder('Presiona Enter después de cada servicio. Ejemplo: vestuarios, duchas, casilleros, agua_fría, etc.')
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
                Tables\Columns\TextColumn::make('location')
                    ->label('Ubicación')
                    ->searchable(),
                Tables\Columns\TextColumn::make('max_capacity')
                    ->label('Capacidad Máxima')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('studio_type')
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'cycling' => 'Ciclo',
                        'reformer' => 'Reformer',
                        'mat' => 'Mat',
                        'multipurpose' => 'Multipropósito',
                        default => 'Desconocido',
                    })
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'cycling' => 'primary',
                        'reformer' => 'info',
                        'mat' => 'success',
                        'multipurpose' => 'warning',
                        default => 'gray',
                    })
                    ->label('Tipo de Sala'),
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
            'index' => Pages\ListStudios::route('/'),
            'create' => Pages\CreateStudio::route('/create'),
            'edit' => Pages\EditStudio::route('/{record}/edit'),
        ];
    }
}
