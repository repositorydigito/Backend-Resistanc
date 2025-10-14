<?php

namespace App\Filament\Resources\JuiceOrderResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DetailsRelationManager extends RelationManager
{
    protected static string $relationship = 'details';

    protected static ?string $title = 'Detalles del Pedido';

    protected static ?string $recordTitleAttribute = 'drink_name';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('drink_name')
                    ->label('Nombre de la Bebida')
                    ->disabled()
                    ->required(),

                Forms\Components\TextInput::make('quantity')
                    ->label('Cantidad')
                    ->numeric()
                    ->disabled()
                    ->required(),

                Forms\Components\TextInput::make('unit_price_soles')
                    ->label('Precio Unitario (S/)')
                    ->numeric()
                    ->disabled()
                    ->prefix('S/'),

                Forms\Components\TextInput::make('total_price_soles')
                    ->label('Precio Total (S/)')
                    ->numeric()
                    ->disabled()
                    ->prefix('S/'),

                Forms\Components\KeyValue::make('ingredients_info')
                    ->label('Ingredientes de la Bebida')
                    ->disabled()
                    ->addActionLabel('Agregar Ingrediente')
                    ->keyLabel('Tipo')
                    ->valueLabel('Ingredientes'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('drink_name')
            ->columns([
                Tables\Columns\TextColumn::make('drink_name')
                    ->label('Bebida')
                    ->searchable()
                    ->weight('bold')
                    ->wrap(),

                Tables\Columns\TextColumn::make('quantity')
                    ->label('Cantidad')
                    ->alignCenter()
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('unit_price_soles')
                    ->label('Precio Unit.')
                    ->money('PEN')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_price_soles')
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
                // No permitir crear detalles manualmente
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                // No permitir eliminar detalles en bulk
            ])
            ->emptyStateHeading('Sin detalles de pedido')
            ->emptyStateDescription('Este pedido no tiene bebidas registradas.')
            ->defaultSort('created_at', 'asc');
    }
}
