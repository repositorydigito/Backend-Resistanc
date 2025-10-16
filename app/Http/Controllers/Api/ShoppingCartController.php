<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductOptionResource;
use App\Models\ShoppingCart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * @tags Carrito de Compras
 */
class ShoppingCartController extends Controller
{
    /**
     * Obtener o crear el carrito activo del usuario
     */
    private function getOrCreateActiveCart()
    {
        $user = Auth::user();

        if (!$user) {
            throw new \Exception('Usuario no autenticado');
        }

        // Buscar carrito activo del usuario
        $cart = ShoppingCart::where('user_id', $user->id)
            ->where('status', 'active')
            ->first();

        // Si no existe, crear uno nuevo
        if (!$cart) {
            $cart = ShoppingCart::create([
                'user_id' => $user->id,
                'session_id' => session()->getId(),
                'status' => 'active',
                'total_amount' => 0,
                'item_count' => 0,
            ]);
        }

        return $cart;
    }

    /**
     * Mostrar los productos del carrito del usuario autenticado
     */
    public function show(Request $request): JsonResponse
    {
        try {
            $cart = $this->getOrCreateActiveCart();

            // Cargar los items con sus relaciones
            $cart->load(['items.product', 'items.productVariant.variantOptions.productOptionType']);

            // Recalcular totales por si acaso
            $cart->recalculateTotals();

            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Carrito obtenido exitosamente',
                'datoAdicional' => [
                    'cart' => [
                        'id' => $cart->id,
                        'total_amount' => $cart->total_amount,
                        'item_count' => $cart->item_count,
                        'total_items' => $cart->total_items,
                        'is_empty' => $cart->is_empty,
                        'status' => $cart->status,
                    ],
                    'items' => $cart->items->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'product_id' => $item->product_id,
                            'product_brand' => $item->product->productBrand ?? null,
                            'product_variant_id' => $item->product_variant_id,
                            'quantity' => $item->quantity,
                            'unit_price' => $item->unit_price,
                            'total_price' => $item->total_price,
                            'product' => [
                                'id' => $item->product->id,
                                'name' => $item->product->name,
                                'sku' => $item->product->sku,
                                'img_url' => $item->product->img_url ? asset('storage/' . $item->product->img_url) : asset('default/product.jpg'),
                            ],
                            'variant' => $item->productVariant ? [
                                'id' => $item->productVariant->id,
                                'name' => $item->productVariant->name,
                                'sku' => $item->productVariant->full_sku,
                                'options' => ProductOptionResource::collection($item->productVariant->variantOptions)
                            ] : null,
                        ];
                    }),
                ],
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error al obtener el carrito',
                'datoAdicional' => null,
            ], 200);
        }
    }

    /**
     * Agregar producto al carrito del usuario
     */
    public function add(Request $request)
    {
        try {
            // Log para debug
            \Log::info('ShoppingCart Add Request', [
                'all_data' => $request->all(),
                'json_data' => $request->json()->all(),
                'input_product_id' => $request->input('product_id'),
                'input_quantity' => $request->input('quantity'),
            ]);

            $request->validate([
                'product_id' => 'required|exists:products,id',
                'quantity' => 'required|integer|min:1',
                'product_variant_id' => 'sometimes|exists:product_variants,id',
            ]);

            $cart = $this->getOrCreateActiveCart();

            $product = Product::findOrFail($request->product_id);
            $variant = null;

            if ($request->product_variant_id) {
                $variant = ProductVariant::findOrFail($request->product_variant_id);

                // Verificar que la variante pertenece al producto
                if ($variant->product_id !== $product->id) {
                    return response()->json([
                        'exito' => false,
                        'codMensaje' => 0,
                        'mensajeUsuario' => 'La variante no pertenece al producto especificado',
                        'datoAdicional' => null,
                    ], 200);
                }
            }

            // Agregar item al carrito
            $cartItem = $cart->addItem($product, $request->quantity, $variant);

            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Producto agregado al carrito exitosamente',
                'datoAdicional' => [
                    'cart_item' => [
                        'id' => $cartItem->id,
                        'quantity' => $cartItem->quantity,
                        'unit_price' => $cartItem->unit_price,
                        'total_price' => $cartItem->total_price,
                    ],
                    'cart_total' => $cart->total_amount,
                    'cart_items_count' => $cart->total_items,
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
                'mensajeUsuario' => 'Error al agregar producto al carrito',
                'datoAdicional' => null,
            ], 200);
        }
    }

    /**
     * Eliminar producto específico del carrito
     */
    public function remove(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'cart_item_id' => 'required|exists:cart_items,id',
            ]);

            $cart = $this->getOrCreateActiveCart();

            // Verificar que el item pertenece al carrito del usuario
            $cartItem = $cart->items()->where('id', $request->cart_item_id)->first();

            if (!$cartItem) {
                return response()->json([
                    'exito' => false,
                    'codMensaje' => 0,
                    'mensajeUsuario' => 'El item no pertenece a tu carrito',
                    'datoAdicional' => null,
                ], 200);
            }

            // Eliminar el item
            $cart->removeItem($request->cart_item_id);

            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Producto eliminado del carrito exitosamente',
                'datoAdicional' => [
                    'cart_total' => $cart->total_amount,
                    'cart_items_count' => $cart->total_items,
                    'is_empty' => $cart->is_empty,
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
                'mensajeUsuario' => 'Error al eliminar producto del carrito',
                'datoAdicional' => null,
            ], 200);
        }
    }

    /**
     * Actualizar cantidad de un producto en el carrito
     */
    public function updateQuantity(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'cart_item_id' => 'required|exists:cart_items,id',
                'quantity' => 'required|integer|min:1',
            ]);

            $cart = $this->getOrCreateActiveCart();

            // Verificar que el item pertenece al carrito del usuario
            $cartItem = $cart->items()->where('id', $request->cart_item_id)->first();

            if (!$cartItem) {
                return response()->json([
                    'exito' => false,
                    'codMensaje' => 0,
                    'mensajeUsuario' => 'El item no pertenece a tu carrito',
                    'datoAdicional' => null,
                ], 200);
            }

            // Verificar stock
            $product = $cartItem->product;
            $variant = $cartItem->productVariant;

            if (!$product->requires_variants) {
                if ($product->stock_quantity < $request->quantity) {
                    return response()->json([
                        'exito' => false,
                        'codMensaje' => 0,
                        'mensajeUsuario' => 'Stock insuficiente para este producto',
                        'datoAdicional' => null,
                    ], 200);
                }
            } else if ($variant) {
                if ($variant->stock_quantity < $request->quantity) {
                    return response()->json([
                        'exito' => false,
                        'codMensaje' => 0,
                        'mensajeUsuario' => 'Stock insuficiente para esta variante',
                        'datoAdicional' => null,
                    ], 200);
                }
            }

            // Actualizar cantidad
            $cartItem->update(['quantity' => $request->quantity]);
            $cartItem->updateTotal();

            // Recalcular totales del carrito
            $cart->recalculateTotals();

            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Cantidad actualizada exitosamente',
                'datoAdicional' => [
                    'cart_item' => [
                        'id' => $cartItem->id,
                        'quantity' => $cartItem->quantity,
                        'total_price' => $cartItem->total_price,
                    ],
                    'cart_total' => $cart->total_amount,
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
                'mensajeUsuario' => 'Error al actualizar cantidad',
                'datoAdicional' => null,
            ], 200);
        }
    }

    /**
     * Limpiar todo el carrito del usuario
     */
    public function clear(Request $request): JsonResponse
    {
        try {
            $cart = $this->getOrCreateActiveCart();

            // Limpiar todos los items
            $cart->clear();

            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Carrito limpiado exitosamente',
                'datoAdicional' => [
                    'cart_total' => $cart->total_amount,
                    'cart_items_count' => $cart->total_items,
                    'is_empty' => $cart->is_empty,
                ],
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error al limpiar el carrito',
                'datoAdicional' => null,
            ], 200);
        }
    }

    /**
     * Confirmar carrito y crear orden
     */
    public function confirm(Request $request): JsonResponse
    {
        try {
            $cart = $this->getOrCreateActiveCart();

            if ($cart->is_empty) {
                return response()->json([
                    'exito' => false,
                    'codMensaje' => 0,
                    'mensajeUsuario' => 'No puedes confirmar un carrito vacío',
                    'datoAdicional' => null,
                ], 200);
            }

            // Convertir carrito a orden
            $order = $cart->convertToOrder();

            // El método convertToOrder ya marca el carrito como 'converted'
            // y limpia los items, pero necesitamos crear un nuevo carrito activo

            // Crear nuevo carrito activo para el usuario
            $newCart = ShoppingCart::create([
                'user_id' => Auth::user()->id,
                'session_id' => session()->getId(),
                'status' => 'active',
                'total_amount' => 0,
                'item_count' => 0,
            ]);

            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Orden creada exitosamente',
                'datoAdicional' => [
                    'order' => [
                        'id' => $order->id,
                        'order_number' => $order->order_number,
                        'total_amount' => $order->total_amount,
                        'status' => $order->status,
                    ],
                    'new_cart' => [
                        'id' => $newCart->id,
                        'is_empty' => true,
                        'total_items' => 0,
                    ],
                ],
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error al confirmar el carrito',
                'datoAdicional' => null,
            ], 200);
        }
    }
}
