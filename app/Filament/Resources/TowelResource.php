<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TowelResource\Pages;
use App\Filament\Resources\TowelResource\RelationManagers;
use App\Models\Towel;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TowelResource extends Resource
{
    protected static ?string $model = Towel::class;

    protected static ?string $navigationGroup = 'Configuración General';

    protected static ?string $navigationIcon = 'heroicon-s-swatch'; // Icono de muestra de color
    protected static ?string $modelLabel = 'Toalla';
    protected static ?string $pluralModelLabel = 'Toallas';

     protected static ?int $navigationSort = 25;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información básica')
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->label('Código')
                            ->required()
                            ->maxLength(50)
                            ->unique(ignoreRecord: true),

                        Forms\Components\TextInput::make('size')
                            ->label('Tamaño (cm)')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(300),

                        Forms\Components\Select::make('color')
                            ->label('Color')
                            ->options([
                                'red' => 'Rojo',
                                'green' => 'Verde',
                                'blue' => 'Azul',
                                'yellow' => 'Amarillo',
                                'black' => 'Negro',
                                'white' => 'Blanco',
                            ])
                            ->nullable(),

                        Forms\Components\Select::make('gender')
                            ->label('Género')
                            ->options([
                                'male' => 'Masculino',
                                'female' => 'Femenino',
                                'unisex' => 'Unisex',
                            ])
                            ->default('unisex'),
                    ])->columns(2),

                Forms\Components\Section::make('Detalles adicionales')
                    ->schema([
                        Forms\Components\Textarea::make('description')
                            ->label('Descripción')
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('observations')
                            ->label('Observaciones')
                            ->columnSpanFull(),

                        // Forms\Components\Select::make('status')
                        //     ->label('Estado')
                        //     ->required()
                        //     ->options([
                        //         'available' => 'Disponible',
                        //         'maintenance' => 'En mantenimiento',
                        //         'in_use' => 'En uso',
                        //         'lost' => 'Perdida',
                        //     ])
                        //     ->default('available'),

                        Forms\Components\FileUpload::make('image')
                            ->label('Imagen principal')
                            ->image()
                            ->directory('towels')
                            ->columnSpanFull(),
                        Forms\Components\FileUpload::make('images_gallery')
                            ->label('Galería de imágenes')
                            ->image()
                            ->directory('towels/gallery')
                            ->multiple()
                            ->json() // ⚠️ Añade esto para serializar automáticamente el array a JSON
                            ->columnSpanFull(),
                    ]),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([


                Tables\Columns\TextColumn::make('code')
                    ->label('Código')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('size')
                    ->label('Tamaño')
                    ->suffix(' cm')
                    ->sortable(),

                Tables\Columns\TextColumn::make('color')
                    ->label('Color')
                    ->searchable(),

                Tables\Columns\TextColumn::make('gender')
                    ->label('Género')
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'male' => 'Masculino',
                        'female' => 'Femenino',
                        'unisex' => 'Unisex',
                    })
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'male' => 'info',
                        'female' => 'pink',
                        'unisex' => 'gray',
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'available' => 'Disponible',
                        'maintenance' => 'Mantenimiento',
                        'in_use' => 'En uso',
                        'lost' => 'Perdida',
                    })
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'available' => 'success',
                        'maintenance' => 'warning',
                        'in_use' => 'info',
                        'lost' => 'danger',
                    }),


            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'available' => 'Disponible',
                        'maintenance' => 'En mantenimiento',
                        'in_use' => 'En uso',
                        'lost' => 'Perdida',
                    ]),

                Tables\Filters\SelectFilter::make('gender')
                    ->label('Género')
                    ->options([
                        'male' => 'Masculino',
                        'female' => 'Femenino',
                        'unisex' => 'Unisex',
                    ]),

                Tables\Filters\Filter::make('has_color')
                    ->label('Con color específico')
                    ->query(fn(Builder $query): Builder => $query->whereNotNull('color')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
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
            'index' => Pages\ListTowels::route('/'),
            'create' => Pages\CreateTowel::route('/create'),
            'edit' => Pages\EditTowel::route('/{record}/edit'),
        ];
    }
}
