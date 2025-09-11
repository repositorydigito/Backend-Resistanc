<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DrinkResource\Pages;
use App\Filament\Resources\DrinkResource\RelationManagers;
use App\Models\Basedrink;
use App\Models\Drink;
use App\Models\Flavordrink;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DrinkResource extends Resource
{
    protected static ?string $model = Drink::class;

    // protected static ?string $navigationIcon = 'heroicon-o-beaker';

    protected static ?string $navigationGroup = 'Bebidas';

    protected static ?string $navigationLabel = 'Combinaciones'; // Nombre del grupo de navegación

    protected static ?string $label = 'Combinación'; // Nombre en singular
    protected static ?string $pluralLabel = 'Combinaciones'; // Nombre en plural

    // protected static ?int $navigationSort = 5;

    protected static bool $shouldRegisterNavigation = false; // Oculta del menú

    // Opcional: bloquea el acceso por URL
    public static function canAccess(): bool
    {
        return false;
    }


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información de la bebida')
                    ->columns(2)
                    ->schema([


                        // Forms\Components\TextInput::make('slug')
                        //     ->required()
                        //     ->maxLength(255),

                        Forms\Components\Select::make('basesdrinks')
                            ->label('Base de la bebida')
                            ->relationship('basesdrinks', 'name')
                            ->options(
                                Basedrink::all()->pluck('name', 'id')
                            )
                            ->searchable()
                            ->required()
                            // ->multiple()
                            ->preload(),
                        Forms\Components\Select::make('flavordrinks')
                            ->label('Sabor de la bebida')
                            ->relationship('flavordrinks', 'name')
                            ->options(
                                Flavordrink::all()->pluck('name', 'id')
                            )
                            ->searchable()
                            ->required()
                            // ->multiple()
                            ->preload(),

                        Forms\Components\Select::make('typesdrinks')
                            ->label('Tipo de bebida')
                            ->relationship('typesdrinks', 'name')
                            ->options(
                                \App\Models\Typedrink::all()->pluck('name', 'id')
                            )

                            ->searchable()
                            ->required()
                            // ->multiple()
                            ->preload(),



                        // Forms\Components\TextInput::make('price')
                        //     ->label('Precio')
                        //     ->required()
                        //     ->minValue(0)
                        //     ->maxValue(1000000)
                        //     ->step(0.01)
                        //     ->numeric()
                        //     ->default(0)
                        //     ->prefix('S/'),

                        Forms\Components\Textarea::make('description')
                            ->columnSpanFull()
                            ->label('Descripción')
                            ->maxLength(255),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('basesdrinks.name')
                    ->label('Base')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('flavordrinks.name')
                    ->label('Sabor')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('typesdrinks.name')
                    ->label('Tipo')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('typesdrinks.price')
                    ->label('Precio')
                    ->money('PEN', true)
                    ->sortable(),
                // Tables\Columns\TextColumn::make('description')
                //     ->label('Descripción')
                //     ->searchable()
                //     ->limit(50),
                // Tables\Columns\TextColumn::make('created_at')
                //     ->label('Creado')
                //     ->dateTime('d/m/Y H:i')
                //     ->sortable()
                //     ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('basesdrinks')
                    ->label('Filtrar por Base')
                    ->relationship('basesdrinks', 'name'),
                Tables\Filters\SelectFilter::make('flavordrinks')
                    ->label('Filtrar por Sabor')
                    ->relationship('flavordrinks', 'name'),
                Tables\Filters\SelectFilter::make('typesdrinks')
                    ->label('Filtrar por Tipo')
                    ->relationship('typesdrinks', 'name'),
            ])
            ->actions([
                // Tables\Actions\EditAction::make(),
                // Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListDrinks::route('/'),
            // 'create' => Pages\CreateDrink::route('/create'),
            // 'edit' => Pages\EditDrink::route('/{record}/edit'),
        ];
    }
}
