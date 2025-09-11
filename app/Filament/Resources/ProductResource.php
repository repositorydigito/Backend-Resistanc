<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
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

    protected static ?string $navigationGroup = 'Gestión de Tienda';
    protected static ?string $navigationLabel = 'Productos';
    protected static ?string $slug = 'productos';
    protected static ?string $label = 'Producto';
    protected static ?string $pluralLabel = 'Productos';
    protected static ?int $navigationSort = 16;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Información General')
                    ->columns(2)
                    ->schema([

                        Forms\Components\FileUpload::make('img_url')
                            ->label('Imagen principal')
                            ->image()
                            ->directory('products/main')
                            ->disk('public') // Usa el filesystem configurado como 'public'
                            ->preserveFilenames()
                            ->columnSpanFull()
                            ->nullable(),



                        Forms\Components\TextInput::make('name')
                            ->label('Nombre')
                            ->required(),

                        // Forms\Components\TextInput::make('slug')
                        //     ->label('Slug')
                        //     ->required(),

                        Forms\Components\Select::make('category_id')
                            ->label('Categoría')
                            ->relationship('category', 'name'),

                        Forms\Components\Select::make('product_brand_id')
                            ->label('Marca')
                            ->relationship('productBrand', 'name')
                            ->nullable()
                            ->searchable()
                            ->preload(),

                        Forms\Components\TextInput::make('sku')
                            ->label('SKU')
                            ->required(),

                        Forms\Components\Select::make('status')
                            ->label('Estado')
                            ->options([
                                'active' => 'Activo',
                                'inactive' => 'Inactivo',
                                'out_of_stock' => 'Sin stock',
                                'discontinued' => 'Descontinuado',
                            ])
                            ->required(),

                        Textarea::make('short_description')
                            ->label('Descripción corta')
                            ->columnSpanFull(),

                        Textarea::make('description')
                            ->label('Descripción larga')
                            ->columnSpanFull(),
                    ]),

                Section::make('Precios y Stock')
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('price_soles')
                            ->label('Precio de venta (S/.)')
                            ->numeric(),

                        Forms\Components\TextInput::make('cost_price_soles')
                            ->label('Precio de costo (S/.)')
                            ->numeric(),

                        Forms\Components\TextInput::make('compare_price_soles')
                            ->label('Precio original / comparación')
                            ->numeric(),

                        Forms\Components\TextInput::make('min_stock_alert')
                            ->label('Alerta de stock mínimo')
                            ->numeric()
                            ->default(5),

                        Forms\Components\TextInput::make('weight_grams')
                            ->label('Peso (gramos)')
                            ->numeric(),
                    ]),

                Section::make('Opciones del Producto')
                    ->columns(2)
                    ->schema([
                        // Forms\Components\Select::make('product_type')
                        //     ->label('Tipo de producto')
                        //     ->options([
                        //         'shake' => 'Batido',
                        //         'supplement' => 'Suplemento',
                        //         'merchandise' => 'Merchandising',
                        //         'service' => 'Servicio',
                        //         'gift_card' => 'Tarjeta de regalo',
                        //     ])
                        //     ->required(),



                        Forms\Components\Toggle::make('requires_variants')
                            ->label('¿Requiere variantes?'),

                        // Forms\Components\Toggle::make('is_virtual')
                        //     ->label('¿Es virtual?'),

                        Forms\Components\Toggle::make('is_featured')
                            ->label('¿Es destacado?'),

                        // Forms\Components\Toggle::make('is_available_for_booking')
                        //     ->label('¿Disponible para reservas?'),

                        Forms\Components\Toggle::make('is_cupon')
                            ->live()
                            ->label('¿Es cupón de descuento?')
                            ->helperText('Si es un producto de cupón, se debe ingresar la URL del cupón en el campo "URL del cupón"'),
                        Forms\Components\TextInput::make('url_cupon_code')
                            ->label('URL del cupón')
                            ->helperText('URL del cupón si es un producto de cupón. Ejemplo: https://resistanc.com/cupones/12345')
                            ->visible(fn(Forms\Get $get) => $get('is_cupon') === true)
                            ->nullable(),
                    ]),

                Section::make('Metadatos y SEO')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('meta_title')
                            ->label('Meta título'),

                        Forms\Components\TextInput::make('meta_description')
                            ->label('Meta descripción')
                            ->maxLength(500),
                    ]),

                Section::make('Información Adicional')
                    ->columns(1)
                    ->schema([
                        Forms\Components\Repeater::make('dimensions')
                            ->label('Dimensiones')
                            ->columns(2)
                            ->schema([
                                Forms\Components\TextInput::make('nombre')
                                    ->label('Nombre')
                                    ->required(),
                                Forms\Components\TextInput::make('valor')
                                    ->label('Valor')
                                    ->required(),
                            ])
                            ->createItemButtonLabel('Agregar dimensión'),

                        Forms\Components\FileUpload::make('images')
                            ->label('Imágenes del producto')
                            ->multiple()
                            ->reorderable()
                            ->image()
                            ->directory('products') // se guardará en storage/app/public/products
                            ->preserveFilenames(),

                        Forms\Components\KeyValue::make('nutritional_info')
                            ->label('Características')
                            ->keyLabel('Nombre')
                            ->valueLabel('Valor')
                            ->addButtonLabel('Agregar dato'),
                    ])


            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable(),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('Categoría'),

                Tables\Columns\TextColumn::make('sku')
                    ->label('SKU'),

                Tables\Columns\TextColumn::make('price_soles')
                    ->label('Precio (S/.)')
                    ->numeric(),

                Tables\Columns\IconColumn::make('requires_variants')
                    ->label('¿Variantes?')
                    ->boolean(),

                // Tables\Columns\TextColumn::make('product_type')
                //     ->label('Tipo'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Estado'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([])
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
            RelationManagers\ProductVariantRelationManager::class,
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
