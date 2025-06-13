<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DrinkResource\Pages;
use App\Filament\Resources\DrinkResource\RelationManagers;
use App\Models\Drink;
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

    protected static ?string $navigationLabel = 'Jugos y Bebidas'; // Nombre del grupo de navegación

    protected static ?string $label = 'Bebida'; // Nombre en singular
    protected static ?string $pluralLabel = 'Jugos y Bebidas'; // Nombre en plural

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información de la bebida')
                    ->columns(2)
                    ->schema([

                        Forms\Components\FileUpload::make('image_url')
                            ->label('Imagen')
                            ->disk('public')
                            ->directory('drinks')
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
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        // Forms\Components\TextInput::make('slug')
                        //     ->required()
                        //     ->maxLength(255),


                        Forms\Components\TextInput::make('price')
                            ->label('Precio')
                            ->required()
                            ->minValue(0)
                            ->maxValue(1000000)
                            ->step(0.01)
                            ->numeric()
                            ->default(0)
                            ->prefix('S/'),

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
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                // Tables\Columns\TextColumn::make('slug')
                //     ->searchable(),
                Tables\Columns\TextColumn::make('description')
                    ->searchable(),
                Tables\Columns\ImageColumn::make('image_url'),
                Tables\Columns\TextColumn::make('price')
                    ->money('PEN', true)
                    ->sortable(),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDrinks::route('/'),
            'create' => Pages\CreateDrink::route('/create'),
            'edit' => Pages\EditDrink::route('/{record}/edit'),
        ];
    }
}
