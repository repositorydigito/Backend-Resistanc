<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use App\Models\ProductOptionType;
use App\Models\VariantOption;
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

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if (isset($this->record)) {
            // Agrupa las opciones por tipo y toma solo la primera por tipo
            $variantOptions = $this->record->variantOptions()->get();
            $byType = [];
            foreach ($variantOptions as $option) {
                $key = 'variant_option_' . $option->product_option_type_id;
                if (!isset($byType[$key])) {
                    $byType[$key] = $option->id;
                }
            }
            $data = array_merge($data, $byType);
        }
        return $data;
    }

    public function form(Form $form): Form
    {
        $optionTypes = ProductOptionType::where('is_active', true)->get();
        $fields = [
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
        ];

        // Obtener los IDs seleccionados por tipo si estamos editando
        $selectedOptionsByType = [];
        $editingRecord = $this->getMountedTableActionRecord();
        if ($editingRecord) {
            foreach ($editingRecord->variantOptions as $option) {
                $selectedOptionsByType[$option->product_option_type_id] = $option->id;
            }
        }

        $variantOptionSelects = [];
        foreach ($optionTypes as $type) {
            $options = VariantOption::where('product_option_type_id', $type->id)->pluck('name', 'id');
            if ($options->isNotEmpty()) {
                $currentValue = null;
                if ($editingRecord) {
                    $currentOption = $editingRecord->variantOptions
                        ->where('product_option_type_id', $type->id)
                        ->first();
                    $currentValue = $currentOption ? $currentOption->name : null;
                }
                $variantOptionSelects[] = Forms\Components\Select::make('variant_option_' . $type->id)
                    ->label($type->name)
                    ->options($options)
                    ->required($type->is_required)
                    ->searchable()
                    ->preload()
                    ->dehydrated(true)
                    ->hint($currentValue ? 'Actual: ' . $currentValue : null);
            }
        }
        if (!empty($variantOptionSelects)) {
            $fields[] = Forms\Components\Section::make('Opciones de Variante')
                ->description('Selecciona las opciones para esta variante específica. Si no seleccionas una opción, se mantendrá la opción actual de la variante.')
                ->schema($variantOptionSelects)
                ->columnSpanFull();
        }

        $fields[] = Forms\Components\FileUpload::make('images')
            ->label('Imágenes del producto')
            ->multiple()
            ->reorderable()
            ->image()
            ->directory('products/variants')
            ->preserveFilenames();
        return $form->schema($fields);
    }





    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('sku')
            ->columns([

                // Tables\Columns\TextColumn::make('id')
                //     ->label('ID')
                //     ->sortable()
                //     ->searchable(),

                Tables\Columns\TextColumn::make('sku')
                    ->label('SKU'),

                Tables\Columns\TextColumn::make('price_soles')
                    ->label('Precio')
                    ->sortable(),

                // Tables\Columns\TextColumn::make('stock_quantity')
                //     ->label('Stock')
                //     ->sortable(),

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
                Tables\Actions\CreateAction::make()
                    ->label('Agregar Variante')
                    ->using(function (array $data, RelationManager $livewire) {
                        // Recoger los IDs de las opciones seleccionadas
                        $variantOptionIds = [];
                        foreach ($data as $key => $value) {
                            if (str_starts_with($key, 'variant_option_') && $value) {
                                $variantOptionIds[] = $value;
                                unset($data[$key]);
                            }
                        }
                        // Crear la variante
                        $variant = $livewire->getOwnerRecord()->variants()->create($data);
                        // Sincronizar la relación en la tabla pivote
                        if (!empty($variantOptionIds)) {
                            $variant->variantOptions()->sync($variantOptionIds);
                        }
                        return $variant;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->beforeFormFilled(function (RelationManager $livewire, array $data) {
                        $record = $livewire->getMountedTableActionRecord();
                        if ($record) {
                            $variantOptions = $record->variantOptions()->get();
                            foreach ($variantOptions as $option) {
                                $data['variant_option_' . $option->product_option_type_id] = $option->id;
                            }
                        }
                        return $data;
                    })
                    ->using(function (\Illuminate\Database\Eloquent\Model $record, array $data, RelationManager $livewire) {
                        // Recoger los IDs de las opciones seleccionadas
                        $variantOptionIds = [];
                        foreach ($data as $key => $value) {
                            if (str_starts_with($key, 'variant_option_') && $value) {
                                $variantOptionIds[] = $value;
                                unset($data[$key]);
                            }
                        }
                        // Actualizar la variante
                        $record->update($data);
                        // Sincronizar la relación en la tabla pivote
                        if (!empty($variantOptionIds)) {
                            $record->variantOptions()->sync($variantOptionIds);
                        }
                        return $record;
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
