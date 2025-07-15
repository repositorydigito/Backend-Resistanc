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
        // IDs de los tipos de opción
        $tallaType = \App\Models\ProductOptionType::where('slug', 'talla')->first();
        $colorType = \App\Models\ProductOptionType::where('slug', 'color')->first();

        $tallas = $tallaType
            ? \App\Models\VariantOption::where('product_option_type_id', $tallaType->id)->pluck('name', 'id')
            : collect();
        $colores = $colorType
            ? \App\Models\VariantOption::where('product_option_type_id', $colorType->id)->pluck('name', 'id')
            : collect();

        return $form
            ->schema([
                Forms\Components\TextInput::make('sku')
                    ->label('SKU')
                    ->unique(ignoreRecord: true)
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

                Forms\Components\Section::make('Opciones de Variante')
                    ->description('Selecciona las opciones para esta variante específica')
                    ->schema([
                        Forms\Components\Select::make('variant_option_talla')
                            ->label('Talla')
                            ->options($tallas)
                            ->reactive()
                            ->required(),
                        Forms\Components\Select::make('variant_option_color')
                            ->label('Color')
                            ->options(function (callable $get) use ($colorType) {
                                // Aquí podrías filtrar colores válidos según la talla seleccionada
                                // Por simplicidad, mostramos todos los colores
                                return $colorType
                                    ? \App\Models\VariantOption::where('product_option_type_id', $colorType->id)->pluck('name', 'id')
                                    : collect();
                            })
                            ->required()
                            ->reactive(),
                    ])
                    ->columnSpanFull(),

                Forms\Components\FileUpload::make('images')
                    ->label('Imágenes del producto')
                    ->multiple()
                    ->reorderable()
                    ->image()
                    ->directory('products/variants')
                    ->preserveFilenames(),
            ]);
    }

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        $variantOptionIds = [];
        if (!empty($data['variant_option_talla'])) {
            $variantOptionIds[] = $data['variant_option_talla'];
        }
        if (!empty($data['variant_option_color'])) {
            $variantOptionIds[] = $data['variant_option_color'];
        }
        unset($data['variant_option_talla'], $data['variant_option_color']);
        $variant = static::getModel()::create($data);
        if (!empty($variantOptionIds)) {
            $variant->variantOptions()->sync($variantOptionIds);
        }
        return $variant;
    }

    protected function handleRecordUpdate(\Illuminate\Database\Eloquent\Model $record, array $data): \Illuminate\Database\Eloquent\Model
    {
        $variantOptionIds = [];
        if (!empty($data['variant_option_talla'])) {
            $variantOptionIds[] = $data['variant_option_talla'];
        }
        if (!empty($data['variant_option_color'])) {
            $variantOptionIds[] = $data['variant_option_color'];
        }
        unset($data['variant_option_talla'], $data['variant_option_color']);
        $record->update($data);
        if (!empty($variantOptionIds)) {
            $record->variantOptions()->sync($variantOptionIds);
        }
        return $record;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('sku')
            ->columns([

                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),

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

