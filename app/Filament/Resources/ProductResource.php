<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationGroup = 'Tienda';

    protected static ?string $navigationLabel = 'Productos'; // Nombre en el menú de navegación
    protected static ?string $slug = 'products'; // Ruta del recurso

    protected static ?string $label = 'Producto'; // Nombre en singular
    protected static ?string $pluralLabel = 'Productos'; // Nombre en plural

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([

                Forms\Components\Section::make('Información del Producto')
                    ->columns(2)
                    ->schema([


                        Section::make('Datos Básicos')
                            ->columns(2)
                            ->schema([
                                TextInput::make('sku')->label('SKU')->required()->maxLength(50)->unique(ignoreRecord: true),
                                TextInput::make('name')->label('Nombre del Producto')->required()->maxLength(255),
                                Select::make('category_id')->label('Categoría')->required()->relationship('category', 'name'),
                                TextInput::make('price_soles')->label('Precio (S/)')->numeric()->required(),
                                TextInput::make('compare_price_soles')->label('Precio Comparación (S/)')->numeric(),
                                TextInput::make('cost_price_soles')->label('Costo (S/)')->numeric(),
                                Select::make('tags')
                                    ->label('Etiquetas')
                                    ->multiple()
                                    ->relationship('tags', 'name')
                                    ->preload()
                                    ->searchable(),
                            ]),

                        Section::make('Inventario')
                            ->columns(2)
                            ->schema([
                                TextInput::make('stock_quantity')->label('Stock')->numeric()->default(0)->required(),
                                TextInput::make('min_stock_alert')->label('Stock Mínimo')->numeric()->default(5)->required(),
                                TextInput::make('weight_grams')->label('Peso (g)')->numeric(),
                            ]),

                        Section::make('Descripciones')
                            ->schema([
                                TextInput::make('short_description')->label('Descripción Corta')->maxLength(500),
                                Textarea::make('description')->label('Descripción')->rows(3)->columnSpanFull(),
                            ]),

                        Section::make('Información Técnica')
                            ->columns(2)
                            ->schema([
                                TextInput::make('dimensions')->label('Dimensiones')->maxLength(50)->helperText('Largo x Ancho x Alto (cm)'),
                                TextInput::make('images')->label('Imágenes (URLs)')->maxLength(500)->helperText('Separadas por comas'),
                                TextInput::make('nutritional_info')->label('Info Nutricional'),
                                TextInput::make('ingredients')->label('Ingredientes'),
                                TextInput::make('allergens')->label('Alérgenos'),
                            ]),

                        Section::make('Configuraciones')
                            ->columns(3)
                            ->schema([
                                TextInput::make('product_type')->label('Tipo de Producto')->required(),
                                Toggle::make('requires_variants')->label('¿Requiere Variantes?')->required(),
                                Toggle::make('is_virtual')->label('¿Es Virtual?')->required(),
                                Toggle::make('is_featured')->label('¿Es Destacado?')->required(),
                                Toggle::make('is_available_for_booking')->label('¿Se Puede Reservar?')->required(),
                                TextInput::make('status')->label('Estado')->default('active')->required(),
                            ]),

                        Section::make('SEO')
                            ->columns(2)
                            ->schema([
                                TextInput::make('meta_title')->label('Meta Título')->maxLength(255),
                                TextInput::make('meta_description')->label('Meta Descripción')->maxLength(500),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('slug')
                    ->searchable(),
                Tables\Columns\TextColumn::make('category.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable(),
                Tables\Columns\TextColumn::make('short_description')
                    ->searchable(),
                Tables\Columns\TextColumn::make('price_soles')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('cost_price_soles')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('compare_price_soles')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('stock_quantity')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('min_stock_alert')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('weight_grams')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('product_type'),
                Tables\Columns\IconColumn::make('requires_variants')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_virtual')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_featured')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_available_for_booking')
                    ->boolean(),
                Tables\Columns\TextColumn::make('status'),
                Tables\Columns\TextColumn::make('meta_title')
                    ->searchable(),
                Tables\Columns\TextColumn::make('meta_description')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
