<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Resources\OrderDetailResource;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\Product;
use Error;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * @tags Pedidos
 */
class OrderController extends Controller
{
    /**
     * Lista los pedidos del usuario autenticado
     *
     * Obtiene una lista paginada de los pedidos realizados por el usuario.
     *
     * @summary Listar mis pedidos
     * @operationId getUserOrders
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $orders = $request->user()->orders()
            ->with('orderItems.product:id,name')
            ->withCount('orderItems')
            ->latest()
            ->paginate($request->integer('per_page', 10));

        return OrderResource::collection($orders);
    }

    /**
     * Crea un nuevo pedido
     *
     * Registra un nuevo pedido para el usuario autenticado con los productos seleccionados.
     *
     * @summary Crear un pedido
     * @operationId createOrder
     *
     * @param  \App\Http\Requests\StoreOrderRequest  $request
     * @return \App\Http\Resources\OrderDetailResource
     */
    public function store(StoreOrderRequest $request): OrderDetailResource
    {
        $validated = $request->validated();

        $order = DB::transaction(function () use ($request, $validated) {
            $subtotal = 0;
            $orderItemsData = [];

            foreach ($validated['order_items'] as $item) {
                $product = Product::findOrFail($item['product_id']);
                $total_price_soles = $product->price_soles * $item['quantity'];
                $subtotal += $total_price_soles;

                $orderItemsData[] = [
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'unit_price_soles' => $product->price_soles,
                    'total_price_soles' => $total_price_soles,
                    'notes' => $item['notes'] ?? null,
                ];
            }

            $order = $request->user()->orders()->create([
                'order_number' => 'ORD-' . strtoupper(Str::random(10)),
                'subtotal_soles' => $subtotal,
                'total_amount_soles' => $subtotal, // Simplified for now
                'order_type' => $validated['order_type'] ?? 'purchase',
                'status' => 'pending',
                'payment_status' => 'pending',
                'delivery_method' => 'delivery', // Default value, can be changed later
                'delivery_date' => $validated['delivery_date'] ?? null,
                'delivery_time_slot' => $validated['delivery_time_slot'] ?? null,
                'delivery_address' => $validated['delivery_address'] ?? null,
                'special_instructions' => $validated['special_instructions'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            $order->orderItems()->createMany($orderItemsData);

            return $order;
        });

        $order->load('orderItems.product', 'user');

        return new OrderDetailResource($order);
    }

    /**
     * Muestra los detalles de un pedido específico
     *
     * Obtiene la información completa de un pedido, si le pertenece al usuario.
     *
     * @summary Ver detalle de mi pedido
     * @operationId getOrderDetails
     *
     * @param  \App\Models\Order  $order
     * @return \App\Http\Resources\OrderDetailResource
     */
    public function show(Order $order): OrderDetailResource
    {

        try {
            if (auth()->id() !== $order->user_id) {
               return response()->json(['error' => 'Error al obtener el pedido'], 200);
            }

            $order->load('orderItems.product', 'user');

            return new OrderDetailResource($order);
        } catch (Error $e) {
            // Manejo de errores
            return response()->json(['error' => 'Error al obtener el pedido'], 200);
        }
    }
}
