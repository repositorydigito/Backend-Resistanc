<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OrderItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'orderItems';

    protected static ?string $title = 'Detalles del Pedido';

    protected static ?string $recordTitleAttribute = 'product_name';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('product.name')
                    ->label('Nombre del Producto')
                    ->disabled()
                    ->required(),

                Forms\Components\TextInput::make('quantity')
                    ->label('Cantidad')
                    ->numeric()
                    ->disabled()
                    ->required(),

                Forms\Components\TextInput::make('unit_price')
                    ->label('Precio Unitario (S/)')
                    ->numeric()
                    ->disabled()
                    ->prefix('S/'),

                Forms\Components\TextInput::make('total_price')
                    ->label('Precio Total (S/)')
                    ->numeric()
                    ->disabled()
                    ->prefix('S/'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('product_name')
            ->columns([
                Tables\Columns\TextColumn::make('product.name')
                    ->label('Producto')
                    ->searchable()
                    ->weight('bold')
                    ->wrap()
                    ->placeholder('Producto no encontrado'),

                Tables\Columns\TextColumn::make('quantity')
                    ->label('Cantidad')
                    ->alignCenter()
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('unit_price')
                    ->label('Precio Unit.')
                    ->money('PEN')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_price')
                    ->label('Total')
                    ->money('PEN')
                    ->sortable()
                    ->weight('bold')
                    ->color('success'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Agregado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // No permitir crear items manualmente
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                // No permitir eliminar items en bulk
            ])
            ->emptyStateHeading('Sin productos en el pedido')
            ->emptyStateDescription('Este pedido no tiene productos registrados.')
            ->defaultSort('unit_price', 'asc');
    }

    public function canCreate(): bool
    {
        return false;
    }
}
