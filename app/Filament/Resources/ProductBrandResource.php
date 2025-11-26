<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductBrandResource\Pages;
use App\Filament\Resources\ProductBrandResource\RelationManagers;
use App\Models\ProductBrand;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProductBrandResource extends Resource
{
    protected static ?string $model = ProductBrand::class;

    protected static ?string $navigationIcon = 'heroicon-o-square-3-stack-3d';


    protected static ?string $navigationGroup = 'Gestión de Tienda';
    protected static ?string $navigationLabel = 'Marcas';
    protected static ?string $slug = 'marcas';
    protected static ?string $label = 'Marca';
    protected static ?string $pluralLabel = 'Marcas';
    protected static ?int $navigationSort = 16;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Información básica')
                ->description('Ingresa los datos principales de la marca')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Nombre')
                        ->required()
                        ->maxLength(255),
                    // Agrega más campos aquí si es necesario
                ])
                ->columns(2), // Opcional: divide el formulario en 2 columnas
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable(),
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
            'index' => Pages\ListProductBrands::route('/'),
            'create' => Pages\CreateProductBrand::route('/create'),
            'edit' => Pages\EditProductBrand::route('/{record}/edit'),
        ];
    }
}
