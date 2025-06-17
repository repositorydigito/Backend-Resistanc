<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers;
use App\Models\Order;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?string $navigationGroup = 'Tienda';

    protected static ?string $navigationLabel = 'Pedidos'; // Nombre en el menú de navegación
    protected static ?string $slug = 'product-orders'; // Ruta del recurso

    protected static ?string $label = 'Pedido'; // Nombre en singular
    protected static ?string $pluralLabel = 'Pedidos'; // Nombre en plural

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                Forms\Components\TextInput::make('order_number')
                    ->required()
                    ->maxLength(20),
                Forms\Components\TextInput::make('order_type')
                    ->required(),
                Forms\Components\TextInput::make('subtotal_soles')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('tax_amount_soles')
                    ->required()
                    ->numeric()
                    ->default(0.00),
                Forms\Components\TextInput::make('shipping_amount_soles')
                    ->required()
                    ->numeric()
                    ->default(0.00),
                Forms\Components\TextInput::make('discount_amount_soles')
                    ->required()
                    ->numeric()
                    ->default(0.00),
                Forms\Components\TextInput::make('total_amount_soles')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('currency')
                    ->required()
                    ->maxLength(3)
                    ->default('PEN'),
                Forms\Components\TextInput::make('status')
                    ->required(),
                Forms\Components\TextInput::make('payment_status')
                    ->required(),
                Forms\Components\TextInput::make('delivery_method')
                    ->required(),
                Forms\Components\DatePicker::make('delivery_date'),
                Forms\Components\TextInput::make('delivery_time_slot')
                    ->maxLength(50),
                Forms\Components\TextInput::make('delivery_address'),
                Forms\Components\Textarea::make('special_instructions')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('promocode_used')
                    ->maxLength(50),
                Forms\Components\Textarea::make('notes')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('discount_code_id')
                    ->numeric(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('order_number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('order_type'),
                Tables\Columns\TextColumn::make('subtotal_soles')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tax_amount_soles')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('shipping_amount_soles')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('discount_amount_soles')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_amount_soles')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('currency')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status'),
                Tables\Columns\TextColumn::make('payment_status'),
                Tables\Columns\TextColumn::make('delivery_method'),
                Tables\Columns\TextColumn::make('delivery_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('delivery_time_slot')
                    ->searchable(),
                Tables\Columns\TextColumn::make('promocode_used')
                    ->searchable(),
                Tables\Columns\TextColumn::make('discount_code_id')
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
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}
