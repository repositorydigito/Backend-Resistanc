<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderDetailResource;
use App\Http\Resources\OrderResource;
use App\Models\Log;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Stripe\StripeClient;

/**
 * @tags Pedidos de Productos
 */
class OrderController extends Controller
{
    /**
     * Lista los pedidos del usuario autenticado
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $userId = $request->user()->id;

            $orders = Order::where('user_id', $userId)
                ->with(['orderItems.product:id,name'])
                ->orderBy('created_at', 'desc')
                ->paginate($request->integer('per_page', 15));

            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Pedidos obtenidos exitosamente',
                'datoAdicional' => [
                    'orders' => $orders->map(function ($order) {
                        return [
                            'id' => $order->id,
                            'order_number' => $order->order_number,
                            'status' => $order->status,
                            'payment_status' => $order->payment_status,
                            'total_amount_soles' => $order->total_amount_soles,
                            'delivery_method' => $order->delivery_method,
                            'created_at' => $order->created_at->format('Y-m-d H:i:s'),
                            'delivered_at' => $order->delivered_at?->format('Y-m-d H:i:s'),
                            'items_count' => $order->orderItems->sum('quantity'),
                        ];
                    }),
                    'pagination' => [
                        'current_page' => $orders->currentPage(),
                        'total_pages' => $orders->lastPage(),
                        'per_page' => $orders->perPage(),
                        'total' => $orders->total(),
                    ]
                ],
            ], 200);
        } catch (\Throwable $e) {

            Log::create([
                'user_id' => Auth::id(),
                'action' => 'Lista los pedidos del usuario autenticado',
                'description' => 'Error al obtener pedidos',
                'data' => $e->getMessage(),
            ]);

            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error al obtener pedidos',
                'datoAdicional' => null,
            ], 200);
        }
    }

    /**
     * Confirma el carrito de compras y crea la orden
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Validar datos de entrada
            $request->validate([
                'payment_method_id' => 'required|string', // ID de Stripe directamente
                'notes' => 'nullable|string|max:500',
                'delivery_method' => 'sometimes|string|in:pickup,delivery',
            ]);

            $userId = $request->user()->id;
            $user = $request->user();

            // Asegurar que el usuario tenga un customer en Stripe
            if (!$user->stripe_id) {
                $user->createAsStripeCustomer();
            }

            // Obtener el ID del método de pago directamente desde Stripe
            $stripePaymentMethodId = $request->input('payment_method_id');

            // Validar que el método de pago existe en Stripe y pertenece al usuario
            try {
                $paymentMethod = $user->findPaymentMethod($stripePaymentMethodId);
                if (!$paymentMethod) {
                    // Si no está asociado, asociarlo
                    $user->addPaymentMethod($stripePaymentMethodId);
                }
            } catch (\Exception $e) {
                Log::create([
                    'user_id' => $user->id,
                    'action' => 'Error al validar método de pago de Stripe',
                    'description' => 'Error al validar método de pago de Stripe',
                    'data' => json_encode([
                        'error' => $e->getMessage(),
                        'payment_method_id' => $stripePaymentMethodId,
                    ]),
                ]);

                return response()->json([
                    'exito' => false,
                    'codMensaje' => 0,
                    'mensajeUsuario' => 'Error al validar el método de pago en Stripe',
                    'datoAdicional' => null,
                ], 200);
            }

            // Establecer como método de pago por defecto
            $user->updateDefaultPaymentMethod($stripePaymentMethodId);

            // Buscar el carrito activo del usuario
            $cart = \App\Models\ShoppingCart::where('user_id', $userId)
                ->where('status', 'active')
                ->with(['items.product', 'items.productVariant'])
                ->first();

            if (!$cart || $cart->items->isEmpty()) {
                return response()->json([
                    'exito' => false,
                    'codMensaje' => 0,
                    'mensajeUsuario' => 'No hay carrito activo o está vacío',
                    'datoAdicional' => null,
                ], 200);
            }

            return DB::transaction(function () use ($request, $cart, $user, $userId, $stripePaymentMethodId) {
                // Calcular totales
                $subtotal = 0;
                $orderItemsData = [];
                $itemsForJson = [];
                $itemsDescription = [];

                foreach ($cart->items as $cartItem) {
                    $product = $cartItem->product;
                    $variant = $cartItem->productVariant;

                    $unitPrice = $cartItem->unit_price;
                    $totalPrice = $cartItem->total_price;
                    $subtotal += $totalPrice;

                    // Para la tabla order_items (relacional)
                    $orderItemsData[] = [
                        'product_id' => $product->id,
                        'product_variant_id' => $variant?->id,
                        'quantity' => $cartItem->quantity,
                        'unit_price' => $unitPrice,
                        'total_price' => $totalPrice,
                    ];

                    // Para el campo JSON items (requerido por la migración)
                    $itemsForJson[] = [
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'product_sku' => $variant ? $variant->full_sku : $product->sku,
                        'quantity' => $cartItem->quantity,
                        'unit_price' => $unitPrice,
                        'total_price' => $totalPrice,
                    ];

                    // Para la descripción de Stripe
                    $itemName = $product->name;
                    if ($variant) {
                        $itemName .= ' - ' . $variant->name;
                    }
                    $itemsDescription[] = "{$cartItem->quantity}x {$itemName}";
                }

                // Convertir el precio a centavos (PEN no tiene centavos, pero Stripe espera el monto en la unidad menor)
                $amountInCents = (int) round($subtotal * 100);

                // Crear descripción para Stripe
                $description = 'Compra de productos: ' . implode(', ', $itemsDescription);

                // Procesar pago con Stripe
                $stripeClient = $this->makeStripeClient();
                $stripePaymentIntentId = null;
                $stripeInvoiceId = null;

                try {
                    // Crear el PaymentIntent con configuración para método de pago guardado (off_session)
                    $paymentIntentParams = [
                        'amount' => $amountInCents,
                        'currency' => 'pen',
                        'customer' => $user->stripe_id,
                        'payment_method' => $stripePaymentMethodId,
                        'payment_method_types' => ['card'],
                        'confirmation_method' => 'automatic',
                        'confirm' => true,
                        'description' => $description,
                        'metadata' => [
                            'order_type' => 'product_purchase',
                            'user_id' => (string) $userId,
                        ],
                        'off_session' => true,
                        'return_url' => config('app.url') . '/payment/success',
                    ];

                    // Crear el PaymentIntent
                    $paymentIntent = $stripeClient->paymentIntents->create($paymentIntentParams);
                    $stripePaymentIntentId = $paymentIntent->id;

                    // Verificar el estado del PaymentIntent
                    if ($paymentIntent->status === 'requires_action') {
                        throw new \Exception('El pago requiere autenticación adicional. Estado: ' . $paymentIntent->status);
                    }

                    if ($paymentIntent->status === 'requires_payment_method') {
                        throw new \Exception('El método de pago no es válido o fue rechazado. Estado: ' . $paymentIntent->status);
                    }

                    if ($paymentIntent->status !== 'succeeded') {
                        throw new \Exception('El pago no se completó. Estado: ' . $paymentIntent->status);
                    }

                    // Obtener invoice si existe
                    if (isset($paymentIntent->invoice)) {
                        if (is_string($paymentIntent->invoice)) {
                            $stripeInvoiceId = $paymentIntent->invoice;
                        } elseif (is_object($paymentIntent->invoice)) {
                            $stripeInvoiceId = $paymentIntent->invoice->id ?? null;
                        }
                    }

                    Log::create([
                        'user_id' => $user->id,
                        'action' => 'Pago de orden procesado exitosamente en Stripe',
                        'description' => 'Pago de orden procesado exitosamente en Stripe',
                        'data' => json_encode([
                            'payment_intent_id' => $stripePaymentIntentId,
                            'amount' => $amountInCents,
                            'currency' => 'pen',
                        ]),
                    ]);
                } catch (\Exception $e) {
                    Log::create([
                        'user_id' => $user->id,
                        'action' => 'Error al procesar pago de orden en Stripe',
                        'description' => 'Error al procesar pago de orden en Stripe',
                        'data' => json_encode([
                            'error' => $e->getMessage(),
                            'amount' => $amountInCents,
                        ]),
                    ]);

                    throw new \Exception('Error al procesar el pago: ' . $e->getMessage());
                }

                // Crear la orden
                $order = Order::create([
                    'order_number' => 'ORD-' . strtoupper(Str::random(10)),
                    'user_id' => $userId,
                    'subtotal_soles' => $subtotal,
                    'tax_amount_soles' => 0,
                    'shipping_amount_soles' => 0,
                    'discount_amount_soles' => 0,
                    'total_amount_soles' => $subtotal,
                    'currency' => 'PEN',
                    'order_type' => 'purchase',
                    'status' => 'pending',
                    'payment_status' => 'paid',
                    'delivery_method' => $request->input('delivery_method', 'pickup'),
                    'notes' => $request->notes,
                    'items' => $itemsForJson,
                    'stripe_payment_intent_id' => $stripePaymentIntentId,
                    'stripe_invoice_id' => $stripeInvoiceId,
                    'stripe_customer_id' => $user->stripe_id,
                ]);

                // Crear items de la orden
                $order->orderItems()->createMany($orderItemsData);

                // Marcar carrito como convertido y limpiarlo
                $cart->update(['status' => 'converted']);
                $cart->items()->delete();

                // Crear nuevo carrito activo para el usuario
                \App\Models\ShoppingCart::create([
                    'user_id' => $userId,
                    'session_id' => session()->getId(),
                    'status' => 'active',
                    'total_amount' => 0,
                    'item_count' => 0,
                ]);

                // Cargar relaciones para la respuesta
                $order->load(['orderItems.product', 'orderItems.productVariant', 'user']);

                return response()->json([
                    'exito' => true,
                    'codMensaje' => 1,
                    'mensajeUsuario' => 'Pedido confirmado exitosamente',
                    'datoAdicional' => [
                        'order' => [
                            'id' => $order->id,
                            'order_number' => $order->order_number,
                            'status' => $order->status,
                            'payment_status' => $order->payment_status,
                            'subtotal_soles' => number_format($order->subtotal_soles, 2, '.', ''),
                            'total_amount_soles' => number_format($order->total_amount_soles, 2, '.', ''),
                            'delivery_method' => $order->delivery_method,
                            'created_at' => $order->created_at->format('Y-m-d H:i:s'),
                        ],
                        'items' => $order->orderItems->map(function ($item) {
                            return [
                                'id' => $item->id,
                                'product_name' => $item->product_name,
                                'product_sku' => $item->product_sku,
                                'quantity' => $item->quantity,
                                'unit_price_soles' => number_format($item->unit_price_soles, 2, '.', ''),
                                'total_price_soles' => number_format($item->total_price_soles, 2, '.', ''),
                            ];
                        }),
                        'cart_cleared' => true,
                    ],
                ], 200);
            });
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'exito' => false,
                'codMensaje' => 2,
                'mensajeUsuario' => 'Datos de entrada inválidos',
                'datoAdicional' => $e->errors(),
            ], 200);
        } catch (\Throwable $e) {

            Log::create([
                'user_id' => Auth::id(),
                'action' => 'Confirma el carrito de compras y crea la orden',
                'description' => 'Error al confirmar el pedido',
                'data' => $e->getMessage(),
            ]);


            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error al confirmar el pedido',
                'datoAdicional' => $e->getMessage(),
            ], 200);
        }
    }

    /**
     * Mostrar una orden específica
     */
    public function show(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'order_id' => 'required|integer|exists:orders,id',
            ]);

            $userId = $request->user()->id;
            $orderId = $request->integer('order_id');

            $order = Order::where('id', $orderId)
                ->where('user_id', $userId)
                ->with(['orderItems.product:id,name'])
                ->first();

            if (!$order) {
                return response()->json([
                    'exito' => false,
                    'codMensaje' => 0,
                    'mensajeUsuario' => 'Pedido no encontrado',
                    'datoAdicional' => null,
                ], 200);
            }

            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Pedido obtenido exitosamente',
                'datoAdicional' => [
                    'order' => [
                        'id' => $order->id,
                        'order_number' => $order->order_number,
                        'status' => $order->status,
                        'payment_status' => $order->payment_status,
                        'subtotal_soles' => $order->subtotal_soles,
                        'total_amount_soles' => $order->total_amount_soles,
                        'delivery_method' => $order->delivery_method,
                        'created_at' => $order->created_at->format('Y-m-d H:i:s'),
                        'delivered_at' => $order->delivered_at?->format('Y-m-d H:i:s'),
                        'notes' => $order->notes,
                    ],
                    'items' => $order->orderItems->map(function ($item) {
                        return [
                            'product_name' => $item->product_name,
                            'quantity' => $item->quantity,
                            'unit_price_soles' => $item->unit_price_soles,
                            'total_price_soles' => $item->total_price_soles,
                            'notes' => $item->notes,
                        ];
                    }),
                ],
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {

            Log::create([
                'user_id' => Auth::id(),
                'action' => 'Mostrar una orden específica',
                'description' => 'Datos de entrada inválidos',
                'data' => $e->getMessage(),
            ]);

            return response()->json([
                'exito' => false,
                'codMensaje' => 2,
                'mensajeUsuario' => 'Datos de entrada inválidos',
                'datoAdicional' => $e->errors(),
            ], 200);
        } catch (\Throwable $e) {

            Log::create([
                'user_id' => Auth::id(),
                'action' => 'Mostrar una orden específica',
                'description' => 'Error al obtener el pedido',
                'data' => $e->getMessage(),
            ]);

            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error al obtener el pedido',
                'datoAdicional' => null,
            ], 200);
        }
    }

    /**
     * Crea un cliente de Stripe
     */
    private function makeStripeClient(): StripeClient
    {
        $secret = config('services.stripe.secret');

        if (!$secret) {
            throw new \RuntimeException('Stripe no está configurado correctamente. Falta services.stripe.secret.');
        }

        return new StripeClient($secret);
    }
}
