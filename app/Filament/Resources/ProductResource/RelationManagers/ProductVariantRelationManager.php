<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProductVariantRelationManager extends RelationManager
{
    protected static string $relationship = 'variants';

    protected static ?string $title = 'Variantes del Producto';
    protected static ?string $modelLabel = 'Variante de Producto';
    protected static ?string $pluralModelLabel = 'Variantes de Producto';

    public function form(Form $form): Form
    {
        return $form
            ->schema([

                // Forms\Components\FileUpload::make('main_image')
                //     ->label('Imagen principal')
                //     ->image()
                //     ->directory('products/main')
                //     ->preserveFilenames()
                //     ->nullable(),

                Forms\Components\TextInput::make('sku')
                    ->label('SKU')
                    ->unique(ignoreRecord: true) // Ignora el registro actual para evitar conflictos al editar
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('price_soles')
                    ->label('Precio')
                    ->numeric()
                    ->required(),

                Forms\Components\TextInput::make('cost_price_soles')
                    ->label('Precio de Costo')
                    ->numeric(),

                Forms\Components\TextInput::make('compare_price_soles')
                    ->label('Precio Comparado')
                    ->numeric(),

                Forms\Components\TextInput::make('stock_quantity')
                    ->label('Cantidad en Stock')
                    ->numeric()
                    ->default(0),

                Forms\Components\TextInput::make('min_stock_alert')
                    ->label('Alerta Mínima de Stock')
                    ->numeric()
                    ->default(5),

                Forms\Components\Toggle::make('is_active')
                    ->label('Está Activa')
                    ->default(true),


                Forms\Components\Select::make('variant_option_ids')
                    ->label('Opciones de Variante')
                    ->multiple()
                    ->relationship('variantOptions', 'name') // usa la relación belongsToMany
                    ->preload()
                    ->searchable()
                    ->required()
                    ->columnSpanFull(),

                Forms\Components\FileUpload::make('images')
                    ->label('Imágenes del producto')
                    ->multiple()
                    ->reorderable()
                    ->image()
                    ->directory('products/variants') // se guardará en storage/app/public/products/variants
                    ->preserveFilenames(),


            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('sku')
            ->columns([
                Tables\Columns\TextColumn::make('sku')
                    ->label('SKU'),

                Tables\Columns\TextColumn::make('price_soles')
                    ->label('Precio')
                    ->sortable(),

                Tables\Columns\TextColumn::make('stock_quantity')
                    ->label('Stock')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activa')
                    ->boolean(),

                Tables\Columns\TextColumn::make('variantOptions.name')
                    ->label('Opciones de Variante')
                    ->sortable()
                    ->searchable()
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
