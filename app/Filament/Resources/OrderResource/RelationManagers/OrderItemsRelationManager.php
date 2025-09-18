<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class OrderItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'orderItems';

    protected static ?string $recordTitleAttribute = 'product_name';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('product_id')
                    ->label('Producto')
                    ->options(Product::where('status', 'active')->pluck('name', 'id'))
                    ->searchable()
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                        $product = Product::find($state);
                        $unitPrice = $product?->price_soles ?? 0;
                        $quantity = $get('quantity') ?? 1;

                        $set('unit_price_soles', $unitPrice);
                        $set('total_price_soles', $unitPrice * $quantity);
                    }),

                Forms\Components\TextInput::make('quantity')
                    ->label('Cantidad')
                    ->numeric()
                    ->required()
                    ->default(1)
                    ->minValue(1)
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set, $get) {
                        $unitPrice = $get('unit_price_soles') ?? 0;
                        $quantity = $state ?? 1;
                        $set('total_price_soles', $unitPrice * $quantity);
                    }),

                Forms\Components\TextInput::make('unit_price_soles')
                    ->label('Precio Unitario (S/)')
                    ->numeric()
                    ->required()
                    ->prefix('S/')
                    ->disabled()
                    ->dehydrated(),

                Forms\Components\TextInput::make('total_price_soles')
                    ->label('Total (S/)')
                    ->numeric()
                    ->required()
                    ->prefix('S/')
                    ->disabled()
                    ->dehydrated(),

                Forms\Components\Textarea::make('notes')
                    ->label('Notas')
                    ->rows(2)
                    ->placeholder('Notas especiales para este producto...'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('product_name')
            ->columns([
                Tables\Columns\TextColumn::make('product_name')
                    ->label('Producto')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('product_sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('quantity')
                    ->label('Cantidad')
                    ->sortable(),

                Tables\Columns\TextColumn::make('unit_price_soles')
                    ->label('Precio Unitario')
                    ->money('PEN')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_price_soles')
                    ->label('Total')
                    ->money('PEN')
                    ->sortable(),

                Tables\Columns\TextColumn::make('notes')
                    ->label('Notas')
                    ->limit(50)
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Agregar Producto'),
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