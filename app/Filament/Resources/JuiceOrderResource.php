<?php

namespace App\Filament\Resources;

use App\Filament\Resources\JuiceOrderResource\Pages;
use App\Filament\Resources\JuiceOrderResource\RelationManagers;
use App\Models\JuiceOrder;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class JuiceOrderResource extends Resource
{
    protected static ?string $model = JuiceOrder::class;

    // protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Gestión de Shakes'; // Nombre del grupo de navegación

    protected static ?string $navigationLabel = 'Historial de pedidos'; // Nombre del grupo de navegación

    protected static ?string $label = 'Historial de pedido'; // Nombre en singular
    protected static ?string $pluralLabel = 'Historial de pedidos'; // Nombre en plural

    protected static ?int $navigationSort = 13;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('order_number')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('user_id')
                    ->required()
                    ->numeric(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order_number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('user_id')
                    ->numeric()
                    ->sortable(),
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
            'index' => Pages\ListJuiceOrders::route('/'),
            'create' => Pages\CreateJuiceOrder::route('/create'),
            'edit' => Pages\EditJuiceOrder::route('/{record}/edit'),
        ];
    }
}
