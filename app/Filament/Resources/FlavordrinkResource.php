<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FlavordrinkResource\Pages;
use App\Filament\Resources\FlavordrinkResource\RelationManagers;
use App\Models\Flavordrink;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class FlavordrinkResource extends Resource
{
    protected static ?string $model = Flavordrink::class;

    protected static ?string $navigationGroup = 'Bebidas';

    protected static ?string $navigationLabel = 'Sabor de bebidas'; // Nombre del grupo de navegación

    protected static ?string $label = 'Sabor de Bebida'; // Nombre en singular
    protected static ?string $pluralLabel = 'Sabores de Bebidas'; // Nombre en plural

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Toggle::make('is_active')
                    ->label('¿Está activa?')
                    ->default(true),

                Section::make('Información del sabor de bebida')
                    ->columns(2)
                    ->schema([

                        Forms\Components\FileUpload::make('image_url')
                            ->label('Imagen')
                            ->disk('public')
                            ->directory('flavordrinks')
                            ->visibility('public')
                            ->acceptedFileTypes(['image/*'])
                            ->maxSize(1024 * 5) // 5 MB
                            ->imageResizeMode('crop')
                            ->imageResizeTargetWidth(800)
                            ->imageResizeTargetHeight(600)
                            ->image(),
                        Forms\Components\FileUpload::make('ico_url')
                            ->label('Ícono')
                            ->disk('public')
                            ->directory('flavordrinks/icons')
                            ->visibility('public')
                            ->acceptedFileTypes(['image/*'])
                            ->maxSize(1024 * 2) // 2 MB
                            ->imageResizeMode('crop')
                            ->imageResizeTargetWidth(100)
                            ->imageResizeTargetHeight(100)
                            ->image(),
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(255),
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
                // Tables\Columns\ImageColumn::make('image_url'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('¿Está activa?')
                    ->boolean(),
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
            'index' => Pages\ListFlavordrinks::route('/'),
            'create' => Pages\CreateFlavordrink::route('/create'),
            'edit' => Pages\EditFlavordrink::route('/{record}/edit'),
        ];
    }
}
