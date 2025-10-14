<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderDetailResource;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error al obtener pedidos',
                'datoAdicional' => null,
            ], 200);
        }
    }

    /**
     * Crea un nuevo pedido
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'order_items' => 'required|array|min:1',
                'order_items.*.product_id' => 'required|integer|exists:products,id',
                'order_items.*.quantity' => 'required|integer|min:1',
                'order_items.*.notes' => 'nullable|string|max:500',
                'notes' => 'nullable|string|max:500',
            ]);

            $userId = $request->user()->id;

            return DB::transaction(function () use ($request, $userId) {
                $subtotal = 0;
                $orderItemsData = [];

                foreach ($request->order_items as $item) {
                    $product = Product::findOrFail($item['product_id']);
                    $totalPrice = $product->price_soles * $item['quantity'];
                    $subtotal += $totalPrice;

                    $orderItemsData[] = [
                        'product_id' => $product->id,
                        'quantity' => $item['quantity'],
                        'unit_price_soles' => $product->price_soles,
                        'total_price_soles' => $totalPrice,
                        'product_name' => $product->name, // Para historial
                        'notes' => $item['notes'] ?? null,
                    ];
                }

                $order = Order::create([
                    'order_number' => 'ORD-' . strtoupper(Str::random(10)),
                    'user_id' => $userId,
                    'subtotal_soles' => $subtotal,
                    'total_amount_soles' => $subtotal,
                    'order_type' => 'purchase',
                    'status' => 'pending',
                    'payment_status' => 'paid', // Ya viene pagado desde la app
                    'delivery_method' => 'pickup', // Todos contra entrega
                    'notes' => $request->notes,
                    'delivered_at' => null,
                ]);

                $order->orderItems()->createMany($orderItemsData);

                $order->load(['orderItems.product', 'user']);

                return response()->json([
                    'exito' => true,
                    'codMensaje' => 1,
                    'mensajeUsuario' => 'Pedido creado exitosamente',
                    'datoAdicional' => [
                        'order' => [
                            'id' => $order->id,
                            'order_number' => $order->order_number,
                            'status' => $order->status,
                            'payment_status' => $order->payment_status,
                            'total_amount_soles' => $order->total_amount_soles,
                            'delivery_method' => $order->delivery_method,
                            'created_at' => $order->created_at->format('Y-m-d H:i:s'),
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
            });
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'exito' => false,
                'codMensaje' => 2,
                'mensajeUsuario' => 'Datos de entrada inválidos',
                'datoAdicional' => $e->errors(),
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error al crear el pedido',
                'datoAdicional' => null,
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
            return response()->json([
                'exito' => false,
                'codMensaje' => 2,
                'mensajeUsuario' => 'Datos de entrada inválidos',
                'datoAdicional' => $e->errors(),
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error al obtener el pedido',
                'datoAdicional' => null,
            ], 200);
        }
    }
}
