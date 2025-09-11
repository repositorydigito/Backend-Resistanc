<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
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

    protected static ?string $navigationGroup = 'Gestión de Tienda';

    protected static ?string $navigationLabel = 'Pedidos'; // Nombre en el menú de navegación
    // protected static ?string $slug = 'product-orders'; // Ruta del recurso

    protected static ?string $label = 'Pedido'; // Nombre en singular
    protected static ?string $pluralLabel = 'Pedidos'; // Nombre en plural

    protected static ?int $navigationSort = 14;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Sección de Información del Cliente
                Forms\Components\Section::make('Información del Cliente')
                    ->description('Datos del cliente que realiza el pedido')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Cliente')
                            ->options(function () {
                                return User::whereHas('roles', function ($query) {
                                    $query->where('name', 'Cliente');
                                })->pluck('name', 'id');
                            })
                            ->searchable()
                            ->required()
                            ->placeholder('Selecciona un cliente'),
                    ])
                    ->columns(1),

                // Sección de Información del Pedido
                Forms\Components\Section::make('Información del Pedido')
                    ->description('Detalles básicos del pedido')
                    ->schema([
                        // Forms\Components\TextInput::make('order_number')
                        //     ->label('Número de Pedido')
                        //     ->required()
                        //     ->maxLength(20)
                        //     ->placeholder('ORD-001'),

                        Forms\Components\Select::make('order_type')
                            ->label('Tipo de Pedido')
                            ->options([
                                'purchase' => 'Compra',
                                'booking_extras' => 'Extras de Reserva',
                                'subscription' => 'Suscripción',
                                'gift' => 'Regalo',
                            ])
                            ->required()
                            ->default('purchase'),


                        Forms\Components\Select::make('status')
                            ->label('Estado del Pedido')
                            ->options([
                                'pending' => 'Pendiente',
                                'confirmed' => 'Confirmado',
                                'processing' => 'Procesando',
                                'preparing' => 'Preparando',
                                'ready' => 'Listo',
                                'delivered' => 'Entregado',
                                'cancelled' => 'Cancelado',
                                'refunded' => 'Reembolsado',
                            ])
                            ->required()
                            ->default('pending'),

                        // Forms\Components\Select::make('payment_status')
                        //     ->label('Estado del Pago')
                        //     ->options([
                        //         'pending' => 'Pendiente',
                        //         'authorized' => 'Autorizado',
                        //         'paid' => 'Pagado',
                        //         'partially_paid' => 'Parcialmente Pagado',
                        //         'failed' => 'Fallido',
                        //         'refunded' => 'Reembolsado',
                        //     ])
                        //     ->required()
                        //     ->default('pending'),
                    ])
                    ->columns(2),

                // Sección de Productos
                Forms\Components\Section::make('Productos del Pedido')
                    ->description('Agregar productos al pedido')
                    ->schema([
                        Forms\Components\Repeater::make('order_items')
                            ->label('Productos')
                            ->relationship('orderItems')
                            ->schema([
                                Forms\Components\Select::make('product_id')
                                    ->label('Producto')
                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                    ->options(Product::where('status', 'active')->pluck('name', 'id'))
                                    ->searchable()
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set, $get) {
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
                                    ->disabled()
                                    ->dehydrated()
                                    ->prefix('S/'),

                                Forms\Components\TextInput::make('total_price_soles')
                                    ->label('Total (S/)')
                                    ->numeric()
                                    ->required()
                                    ->disabled()
                                    ->dehydrated()
                                    ->prefix('S/'),

                                Forms\Components\Textarea::make('notes')
                                    ->label('Notas del Producto')
                                    ->rows(2)
                                    ->placeholder('Notas especiales para este producto...'),
                            ])
                            ->columns(5)
                            ->defaultItems(1)
                            ->addActionLabel('Agregar Producto')
                            ->reorderable(false)
                            ->collapsible()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set, $get) {
                                // Recalcular subtotal cuando cambien los productos
                                $subtotal = 0;
                                if (is_array($state)) {
                                    foreach ($state as $item) {
                                        if (isset($item['total_price_soles']) && is_numeric($item['total_price_soles'])) {
                                            $subtotal += (float) $item['total_price_soles'];
                                        }
                                    }
                                }
                                $set('subtotal_soles', $subtotal);

                                // Recalcular total final
                                $tax = $get('tax_amount_soles') ?? 0;
                                $shipping = $get('shipping_amount_soles') ?? 0;
                                $discount = $get('discount_amount_soles') ?? 0;
                                $set('total_amount_soles', $subtotal + $tax + $shipping - $discount);
                            }),
                    ]),

                // Sección de Cálculos
                Forms\Components\Section::make('Cálculos del Pedido')
                    ->description('Totales y descuentos')
                    ->schema([
                        Forms\Components\TextInput::make('subtotal_soles')
                            ->label('Subtotal (S/)')
                            ->numeric()
                            ->required()
                            ->prefix('S/')
                            ->default(0.00)
                            ->disabled()
                            ->dehydrated(),

                        Forms\Components\TextInput::make('tax_amount_soles')
                            ->label('IGV (S/)')
                            ->numeric()
                            ->default(0.00)
                            ->debounce(500)
                            ->prefix('S/')
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, $get) {
                                $subtotal = $get('subtotal_soles') ?? 0;
                                $tax = $state ?? 0;
                                $shipping = $get('shipping_amount_soles') ?? 0;
                                $discount = $get('discount_amount_soles') ?? 0;
                                $set('total_amount_soles', $subtotal + $tax + $shipping - $discount);
                            }),

                        Forms\Components\TextInput::make('shipping_amount_soles')
                            ->label('Envío (S/)')
                            ->numeric()
                            ->default(0.00)
                            ->prefix('S/')
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, $get) {
                                $subtotal = $get('subtotal_soles') ?? 0;
                                $tax = $get('tax_amount_soles') ?? 0;
                                $shipping = $state ?? 0;
                                $discount = $get('discount_amount_soles') ?? 0;
                                $set('total_amount_soles', $subtotal + $tax + $shipping - $discount);
                            }),

                        Forms\Components\TextInput::make('discount_amount_soles')
                            ->label('Descuento (S/)')
                            ->numeric()
                            ->default(0.00)
                            ->prefix('S/')
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, $get) {
                                $subtotal = $get('subtotal_soles') ?? 0;
                                $tax = $get('tax_amount_soles') ?? 0;
                                $shipping = $get('shipping_amount_soles') ?? 0;
                                $discount = $state ?? 0;
                                $set('total_amount_soles', $subtotal + $tax + $shipping - $discount);
                            }),

                        Forms\Components\TextInput::make('total_amount_soles')
                            ->label('Total Final (S/)')
                            ->numeric()
                            ->required()
                            ->prefix('S/')
                            ->default(0.00)
                            ->disabled()
                            ->dehydrated(),
                    ])
                    ->columns(2),

                // Sección de Entrega
                Forms\Components\Section::make('Información de Entrega')
                    ->description('Detalles de entrega del pedido')
                    ->schema([
                        Forms\Components\Select::make('delivery_method')
                            ->label('Método de Entrega')
                            ->options([
                                'pickup' => 'Recojo en Tienda',
                                'delivery' => 'Delivery',
                                'express' => 'Express',
                            ])
                            ->required()
                            ->default('pickup'),

                        Forms\Components\DatePicker::make('delivery_date')
                            ->label('Fecha de Entrega')
                            ->minDate(now()),

                        Forms\Components\TextInput::make('delivery_time_slot')
                            ->label('Horario de Entrega')
                            ->maxLength(50)
                            ->placeholder('Ej: 14:00 - 16:00'),

                        Forms\Components\Textarea::make('delivery_address')
                            ->label('Dirección de Entrega')
                            ->rows(3)
                            ->placeholder('Dirección completa para delivery...'),

                        Forms\Components\Textarea::make('special_instructions')
                            ->label('Instrucciones Especiales')
                            ->rows(3)
                            ->placeholder('Instrucciones especiales para la entrega...'),
                    ])
                    ->columns(2),

                // Sección de Información Adicional
                Forms\Components\Section::make('Información Adicional')
                    ->description('Códigos promocionales y notas')
                    ->schema([
                        Forms\Components\TextInput::make('promocode_used')
                            ->label('Código Promocional')
                            ->maxLength(50)
                            ->placeholder('Código de descuento...'),

                        Forms\Components\TextInput::make('currency')
                            ->label('Moneda')
                            ->maxLength(3)
                            ->default('PEN')
                            ->disabled(),

                        Forms\Components\Textarea::make('notes')
                            ->label('Notas Generales')
                            ->rows(3)
                            ->placeholder('Notas adicionales sobre el pedido...'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order_number')
                    ->label('Número de Pedido')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('order_type')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'online' => 'success',
                        'presencial' => 'info',
                        'telefono' => 'warning',
                        'whatsapp' => 'success',
                        default => 'gray',
                    }),


                Tables\Columns\TextColumn::make('total_amount_soles')
                    ->label('Total')
                    ->money('PEN')
                    ->sortable()
                    ->color('success'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'pending' => 'warning',
                        'confirmed' => 'info',
                        'processing' => 'primary',
                        'preparing' => 'info',
                        'ready' => 'success',
                        'delivered' => 'success',
                        'cancelled' => 'danger',
                        'refunded' => 'gray',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('payment_status')
                    ->label('Pago')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'pending' => 'warning',
                        'authorized' => 'info',
                        'paid' => 'success',
                        'partially_paid' => 'warning',
                        'failed' => 'danger',
                        'refunded' => 'gray',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('delivery_method')
                    ->label('Entrega')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'pickup' => 'info',
                        'delivery' => 'primary',
                        'express' => 'success',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('delivery_date')
                    ->label('Fecha Entrega')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado del Pedido')
                    ->options([
                        'pending' => 'Pendiente',
                        'confirmed' => 'Confirmado',
                        'processing' => 'Procesando',
                        'preparing' => 'Preparando',
                        'ready' => 'Listo',
                        'delivered' => 'Entregado',
                        'cancelled' => 'Cancelado',
                        'refunded' => 'Reembolsado',
                    ]),

                Tables\Filters\SelectFilter::make('payment_status')
                    ->label('Estado del Pago')
                    ->options([
                        'pending' => 'Pendiente',
                        'authorized' => 'Autorizado',
                        'paid' => 'Pagado',
                        'partially_paid' => 'Parcialmente Pagado',
                        'failed' => 'Fallido',
                        'refunded' => 'Reembolsado',
                    ]),

                Tables\Filters\SelectFilter::make('delivery_method')
                    ->label('Método de Entrega')
                    ->options([
                        'pickup' => 'Recojo en Tienda',
                        'delivery' => 'Delivery',
                        'express' => 'Express',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            // RelationManagers\OrderItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'view' => Pages\ViewOrder::route('/{record}'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}
