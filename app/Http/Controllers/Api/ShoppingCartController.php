<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductOptionResource;
use App\Models\ShoppingCart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Error;
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
            throw new Error('Usuario no autenticado');
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
     *
     */
    public function show(Request $request)
    {
        try {
            $cart = $this->getOrCreateActiveCart();

            // Cargar los items con sus relaciones
            $cart->load(['items.product', 'items.productVariant.variantOptions.productOptionType']);

            // Recalcular totales por si acaso
            $cart->recalculateTotals();

            return response()->json([
                'exito' => true,
                'codMensaje' => 0,
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
                                'img_url' =>$item->product->img_url ? asset('storage/' . $item->product->img_url) : null,
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

        } catch (Error $e) {
            return response()->json([
                'exito' => false,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Error al mostrar los productos del carrito',
                'datoAdicional' => $e->getMessage(),
            ], 200);
        }
    }

    /**
     * Agregar producto al carrito del usuario
     */
    public function add(Request $request)
    {
        try {
            $request->validate([
                'product_id' => 'required|exists:products,id',
                'quantity' => 'required|integer|min:1',
                'product_variant_id' => 'nullable|exists:product_variants,id',
            ]);

            $cart = $this->getOrCreateActiveCart();

            $product = Product::findOrFail($request->product_id);
            $variant = null;

            if ($request->product_variant_id) {
                $variant = ProductVariant::findOrFail($request->product_variant_id);

                // Verificar que la variante pertenece al producto
                if ($variant->product_id !== $product->id) {
                    throw new Error('La variante no pertenece al producto especificado');
                }
            }


            // Agregar item al carrito
            $cartItem = $cart->addItem($product, $request->quantity, $variant);

            return response()->json([
                'exito' => true,
                'codMensaje' => 0,
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

        } catch (Error $e) {
            return response()->json([
                'exito' => false,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Error al agregar el producto al carrito',
                'datoAdicional' => $e->getMessage(),
            ], 200);
        }
    }

    /**
     * Eliminar producto específico del carrito
     */
    public function remove(Request $request)
    {
        try {
            $request->validate([
                'cart_item_id' => 'required|exists:cart_items,id',
            ]);

            $cart = $this->getOrCreateActiveCart();

            // Verificar que el item pertenece al carrito del usuario
            $cartItem = $cart->items()->where('id', $request->cart_item_id)->first();

            if (!$cartItem) {
                throw new Error('El item no pertenece a tu carrito');
            }

            // Eliminar el item
            $cart->removeItem($request->cart_item_id);

            return response()->json([
                'exito' => true,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Producto eliminado del carrito exitosamente',
                'datoAdicional' => [
                    'cart_total' => $cart->total_amount,
                    'cart_items_count' => $cart->total_items,
                    'is_empty' => $cart->is_empty,
                ],
            ], 200);

        } catch (Error $e) {
            return response()->json([
                'exito' => false,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Error al eliminar el producto del carrito',
                'datoAdicional' => $e->getMessage(),
            ], 200);
        }
    }

    /**
     * Actualizar cantidad de un producto en el carrito
     */
    public function updateQuantity(Request $request)
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
                throw new Error('El item no pertenece a tu carrito');
            }

            // Verificar stock
            $product = $cartItem->product;
            $variant = $cartItem->productVariant;

            if (!$product->requires_variants) {
                if ($product->stock_quantity < $request->quantity) {
                    throw new Error('Stock insuficiente para este producto');
                }
            } else if ($variant) {
                if ($variant->stock_quantity < $request->quantity) {
                    throw new Error('Stock insuficiente para esta variante');
                }
            }

            // Actualizar cantidad
            $cartItem->update(['quantity' => $request->quantity]);
            $cartItem->updateTotal();

            // Recalcular totales del carrito
            $cart->recalculateTotals();

            return response()->json([
                'exito' => true,
                'codMensaje' => 0,
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

        } catch (Error $e) {
            return response()->json([
                'exito' => false,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Error al actualizar la cantidad',
                'datoAdicional' => $e->getMessage(),
            ], 200);
        }
    }

    /**
     * Limpiar todo el carrito del usuario
     */
    public function clear(Request $request)
    {
        try {
            $cart = $this->getOrCreateActiveCart();

            // Limpiar todos los items
            $cart->clear();

            return response()->json([
                'exito' => true,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Carrito limpiado exitosamente',
                'datoAdicional' => [
                    'cart_total' => $cart->total_amount,
                    'cart_items_count' => $cart->total_items,
                    'is_empty' => $cart->is_empty,
                ],
            ], 200);

        } catch (Error $e) {
            return response()->json([
                'exito' => false,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Error al limpiar el carrito',
                'datoAdicional' => $e->getMessage(),
            ], 200);
        }
    }

    /**
     * Confirmar carrito y crear orden
     */
    public function confirm(Request $request)
    {
        try {
            $cart = $this->getOrCreateActiveCart();

            if ($cart->is_empty) {
                throw new Error('No puedes confirmar un carrito vacío');
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
                'codMensaje' => 0,
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

        } catch (Error $e) {
            return response()->json([
                'exito' => false,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Error al confirmar el carrito',
                'datoAdicional' => $e->getMessage(),
            ], 200);
        }
    }
}
