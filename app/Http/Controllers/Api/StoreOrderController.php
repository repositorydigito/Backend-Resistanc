<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\JuiceOrderResource;
use App\Models\JuiceOrder;
use App\Models\Log;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class StoreOrderController extends Controller
{
    /**
     * Listar todos los pedidos del usuario (shakes y productos)
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $userId = Auth::id();
            $perPage = $request->input('per_page', 15);
            $status = $request->input('status'); // Opcional: filtrar por estado
            $type = $request->input('type'); // Opcional: 'shakes' o 'products'

            // Query para pedidos de shakes
            $shakeOrdersQuery = JuiceOrder::where('user_id', $userId)
                ->with(['details.drink', 'user'])
                ->orderBy('created_at', 'desc');

            // Query para pedidos de productos
            $productOrdersQuery = Order::where('user_id', $userId)
                ->with(['orderItems.product', 'orderItems.productVariant', 'user'])
                ->orderBy('created_at', 'desc');

            // Aplicar filtro de estado si se proporciona
            if ($status) {
                $shakeOrdersQuery->where('status', $status);
                $productOrdersQuery->where('status', $status);
            }

            // Obtener pedidos según el tipo solicitado
            if ($type === 'shakes') {
                $shakeOrders = $shakeOrdersQuery->paginate($perPage);
                $productOrders = collect(); // Colección vacía
            } elseif ($type === 'products') {
                $shakeOrders = collect(); // Colección vacía
                $productOrders = $productOrdersQuery->paginate($perPage);
            } else {
                // Obtener ambos tipos
                $shakeOrders = $shakeOrdersQuery->get();
                $productOrders = $productOrdersQuery->get();
            }

            // Combinar y ordenar todos los pedidos por fecha
            $allOrders = collect()
                ->merge(
                    $shakeOrders->map(function ($order) {
                        return [
                            'id' => $order->id,
                            'type' => 'shake',
                            'order_number' => $order->order_number ?? 'SHAKE-' . str_pad($order->id, 6, '0', STR_PAD_LEFT),
                            'status' => $order->status,
                            'payment_status' => $order->payment_status,
                            'total_amount' => $order->total_amount_soles,
                            'currency' => $order->currency,
                            'delivery_method' => $order->delivery_method,
                            'notes' => $order->notes,
                            'created_at' => $order->created_at,
                            'updated_at' => $order->updated_at,
                            'estimated_ready_at' => $order->estimated_ready_at,
                            'details' => $order->details->map(function ($detail) {
                                return [
                                    'id' => $detail->id,
                                    'drink_name' => $detail->drink_name,
                                    'drink_combination' => $detail->drink_combination,
                                    'drink_image' => $this->getDrinkImage($detail),
                                    'ingredients_info' => $detail->ingredients_info ?? [],
                                    'quantity' => $detail->quantity,
                                    'unit_price' => $detail->unit_price_soles,
                                    'total_price' => $detail->total_price_soles,
                                ];
                            }),
                        ];
                    })
                )
                ->merge(
                    $productOrders->map(function ($order) {
                        return [
                            'id' => $order->id,
                            'type' => 'product',
                            'order_number' => $order->order_number,
                            'status' => $order->status,
                            'payment_status' => $order->payment_status,
                            'total_amount' => $order->total_amount_soles,
                            'currency' => $order->currency,
                            'delivery_method' => $order->delivery_method,
                            'notes' => $order->notes,
                            'created_at' => $order->created_at,
                            'updated_at' => $order->updated_at,
                            'items' => $order->orderItems->map(function ($item) {
                                return [
                                    'id' => $item->id,
                                    'product_name' => $item->product ? $item->product->name : 'Producto no encontrado',
                                    'product_sku' => $item->productVariant ? $item->productVariant->full_sku : ($item->product ? $item->product->sku : 'N/A'),
                                    'product_image' => $item->product && $item->product->img_url
                                        ? asset('storage/' . $item->product->img_url)
                                        : asset('default/product.jpg'),
                                    'product_images' => $item->product && $item->product->images
                                        ? collect($item->product->images)->map(fn($path) => asset('storage/' . ltrim($path, '/')))->toArray()
                                        : [],
                                    'quantity' => $item->quantity,
                                    'unit_price' => $item->unit_price,
                                    'total_price' => $item->total_price,
                                ];
                            }),
                        ];
                    })
                )
                ->sortByDesc('created_at')
                ->values();

            // Si se especificó un tipo específico, devolver paginación
            if ($type === 'shakes') {
                $paginatedShakes = $shakeOrdersQuery->paginate($perPage);
                $paginatedShakes->getCollection()->transform(function ($order) {
                    return [
                        'id' => $order->id,
                        'type' => 'shake',
                        'order_number' => $order->order_number ?? 'SHAKE-' . str_pad($order->id, 6, '0', STR_PAD_LEFT),
                        'status' => $order->status,
                        'payment_status' => $order->payment_status,
                        'total_amount' => $order->total_amount_soles,
                        'currency' => $order->currency,
                        'delivery_method' => $order->delivery_method,
                        'notes' => $order->notes,
                        'created_at' => $order->created_at,
                        'updated_at' => $order->updated_at,
                        'estimated_ready_at' => $order->estimated_ready_at,
                        'details' => $order->details->map(function ($detail) {
                            return [
                                'id' => $detail->id,
                                'drink_name' => $detail->drink_name,
                                'drink_combination' => $detail->drink_combination,
                                'drink_image' => $this->getDrinkImage($detail),
                                'ingredients_info' => $detail->ingredients_info ?? [],
                                'quantity' => $detail->quantity,
                                'unit_price' => $detail->unit_price_soles,
                                'total_price' => $detail->total_price_soles,
                            ];
                        }),
                    ];
                });

                return response()->json([
                    'exito' => true,
                    'codMensaje' => 1,
                    'mensajeUsuario' => 'Lista de pedidos de shakes obtenida correctamente',
                    'datoAdicional' => [
                        'orders' => $paginatedShakes->items(),
                        'pagination' => [
                            'current_page' => $paginatedShakes->currentPage(),
                            'last_page' => $paginatedShakes->lastPage(),
                            'per_page' => $paginatedShakes->perPage(),
                            'total' => $paginatedShakes->total(),
                            'from' => $paginatedShakes->firstItem(),
                            'to' => $paginatedShakes->lastItem(),
                        ],
                    ],
                ], 200);
            } elseif ($type === 'products') {
                $paginatedProducts = $productOrdersQuery->paginate($perPage);
                $paginatedProducts->getCollection()->transform(function ($order) {
                    return [
                        'id' => $order->id,
                        'type' => 'product',
                        'order_number' => $order->order_number,
                        'status' => $order->status,
                        'payment_status' => $order->payment_status,
                        'total_amount' => $order->total_amount_soles,
                        'currency' => $order->currency,
                        'delivery_method' => $order->delivery_method,
                        'notes' => $order->notes,
                        'created_at' => $order->created_at,
                        'updated_at' => $order->updated_at,
                        'items' => $order->orderItems->map(function ($item) {
                            return [
                                'id' => $item->id,
                                'product_name' => $item->product ? $item->product->name : 'Producto no encontrado',
                                'product_sku' => $item->productVariant ? $item->productVariant->full_sku : ($item->product ? $item->product->sku : 'N/A'),
                                'product_image' => $item->product && $item->product->img_url
                                    ? asset('storage/' . $item->product->img_url)
                                    : asset('default/product.jpg'),
                                'product_images' => $item->product && $item->product->images
                                    ? collect($item->product->images)->map(fn($path) => asset('storage/' . ltrim($path, '/')))->toArray()
                                    : [],
                                'quantity' => $item->quantity,
                                'unit_price' => $item->unit_price,
                                'total_price' => $item->total_price,
                            ];
                        }),
                    ];
                });

                return response()->json([
                    'exito' => true,
                    'codMensaje' => 1,
                    'mensajeUsuario' => 'Lista de pedidos de productos obtenida correctamente',
                    'datoAdicional' => [
                        'orders' => $paginatedProducts->items(),
                        'pagination' => [
                            'current_page' => $paginatedProducts->currentPage(),
                            'last_page' => $paginatedProducts->lastPage(),
                            'per_page' => $paginatedProducts->perPage(),
                            'total' => $paginatedProducts->total(),
                            'from' => $paginatedProducts->firstItem(),
                            'to' => $paginatedProducts->lastItem(),
                        ],
                    ],
                ], 200);
            }

            // Para todos los pedidos, aplicar paginación manual
            $page = $request->input('page', 1);
            $offset = ($page - 1) * $perPage;
            $paginatedOrders = $allOrders->slice($offset, $perPage)->values();
            $total = $allOrders->count();
            $lastPage = ceil($total / $perPage);

            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Lista de pedidos obtenida correctamente',
                'datoAdicional' => [
                    'orders' => $paginatedOrders,
                    'summary' => [
                        'total_orders' => $total,
                        'shake_orders_count' => $shakeOrders->count(),
                        'product_orders_count' => $productOrders->count(),
                    ],
                    'pagination' => [
                        'current_page' => (int) $page,
                        'last_page' => $lastPage,
                        'per_page' => (int) $perPage,
                        'total' => $total,
                        'from' => $total > 0 ? $offset + 1 : null,
                        'to' => $total > 0 ? min($offset + $perPage, $total) : null,
                    ],
                ],
            ], 200);
        } catch (\Exception $e) {

            Log::create([
                'user_id' => Auth::id(),
                'action' => 'Listar todos los pedidos del usuario (shakes y productos)',
                'description' => 'Error al obtener la lista de pedidos',
                'data' => $e->getMessage(),
            ]);


            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error al obtener la lista de pedidos',
                'datoAdicional' => $e->getMessage(),
            ], 200);
        }
    }

    /**
     * Obtener la imagen de una bebida basada en sus ingredientes
     */
    private function getDrinkImage($detail): string
    {
        // Si el detalle tiene información de ingredientes, usar la primera imagen disponible
        if ($detail->ingredients_info) {
            $ingredients = $detail->ingredients_info;

            // Prioridad: bases > types > flavors
            if (!empty($ingredients['bases'])) {
                $base = \App\Models\Basedrink::where('name', $ingredients['bases'][0])->first();
                if ($base && $base->image_url) {
                    return asset('storage/' . $base->image_url);
                }
            }

            if (!empty($ingredients['types'])) {
                $type = \App\Models\Typedrink::where('name', $ingredients['types'][0])->first();
                if ($type && $type->image_url) {
                    return asset('storage/' . $type->image_url);
                }
            }

            if (!empty($ingredients['flavors'])) {
                $flavor = \App\Models\Flavordrink::where('name', $ingredients['flavors'][0])->first();
                if ($flavor && $flavor->image_url) {
                    return asset('storage/' . $flavor->image_url);
                }
            }
        }

        // Si no se encuentra imagen de ingredientes, usar imagen por defecto
        return asset('default/protico.png');
    }
}
