<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
     * Obtiene todos los productos en el carrito actual del usuario, incluyendo información
     * detallada de productos y variantes, así como los totales calculados.
     * **Requiere autenticación:** Incluye el token Bearer en el header Authorization.
     *
     * @summary Mostrar carrito de compras
     * @operationId getShoppingCart
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @response 200 {
     *   "exito": true,
     *   "codMensaje": 0,
     *   "mensajeUsuario": "Carrito obtenido exitosamente",
     *   "datoAdicional": {
     *     "cart": {
     *       "id": 1,
     *       "total_amount": 150.00,
     *       "item_count": 3,
     *       "total_items": 3,
     *       "is_empty": false,
     *       "status": "active"
     *     },
     *     "items": [
     *       {
     *         "id": 1,
     *         "product_id": 1,
     *         "product_variant_id": null,
     *         "quantity": 2,
     *         "unit_price": 50.00,
     *         "total_price": 100.00,
     *         "product": {
     *           "id": 1,
     *           "name": "Camiseta Resistance",
     *           "sku": "CAM-001",
     *           "img_url": "products/main/camisa.jpg"
     *         },
     *         "variant": null
     *       },
     *       {
     *         "id": 2,
     *         "product_id": 2,
     *         "product_variant_id": 5,
     *         "quantity": 1,
     *         "unit_price": 50.00,
     *         "total_price": 50.00,
     *         "product": {
     *           "id": 2,
     *           "name": "Pantalón Deportivo",
     *           "sku": "PAN-001",
     *           "img_url": "products/main/pantalon.jpg"
     *         },
     *         "variant": {
     *           "id": 5,
     *           "name": "Talla M - Azul",
     *           "sku": "PAN-001-M-AZUL"
     *         }
     *       }
     *     ]
     *   }
     * }
     *
     * @response 200 {
     *   "exito": false,
     *   "codMensaje": 1,
     *   "mensajeUsuario": "Error al mostrar los productos del carrito",
     *   "datoAdicional": "Usuario no autenticado"
     * }
     */
    public function show(Request $request)
    {
        try {
            $cart = $this->getOrCreateActiveCart();

            // Cargar los items con sus relaciones
            $cart->load(['items.product', 'items.productVariant']);

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
                                'img_url' => $item->product->img_url,
                            ],
                            'variant' => $item->productVariant ? [
                                'id' => $item->productVariant->id,
                                'name' => $item->productVariant->name,
                                'sku' => $item->productVariant->full_sku,
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
     *
     * Agrega un producto específico al carrito del usuario autenticado. Si el producto
     * ya existe en el carrito, se incrementa la cantidad. Se valida el stock disponible
     * antes de agregar el producto.
     * **Requiere autenticación:** Incluye el token Bearer en el header Authorization.
     *
     * @summary Agregar producto al carrito
     * @operationId addToCart
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @bodyParam product_id integer required ID del producto a agregar. Example: 1
     * @bodyParam quantity integer required Cantidad del producto (mínimo 1). Example: 2
     * @bodyParam product_variant_id integer nullable ID de la variante del producto (opcional). Example: 5
     *
     * @response 200 {
     *   "exito": true,
     *   "codMensaje": 0,
     *   "mensajeUsuario": "Producto agregado al carrito exitosamente",
     *   "datoAdicional": {
     *     "cart_item": {
     *       "id": 1,
     *       "quantity": 2,
     *       "unit_price": 50.00,
     *       "total_price": 100.00
     *     },
     *     "cart_total": 177.00,
     *     "cart_items_count": 3
     *   }
     * }
     *
     * @response 200 {
     *   "exito": false,
     *   "codMensaje": 1,
     *   "mensajeUsuario": "Error al agregar el producto al carrito",
     *   "datoAdicional": "Stock insuficiente para este producto"
     * }
     *
     * @response 200 {
     *   "exito": false,
     *   "codMensaje": 1,
     *   "mensajeUsuario": "Error al agregar el producto al carrito",
     *   "datoAdicional": "La variante no pertenece al producto especificado"
     * }
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

            // Verificar stock si el producto no requiere variantes
            if (!$product->requires_variants) {
                if ($product->stock_quantity < $request->quantity) {
                    throw new Error('Stock insuficiente para este producto');
                }
            } else if ($variant) {
                if ($variant->stock_quantity < $request->quantity) {
                    throw new Error('Stock insuficiente para esta variante');
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
     *
     * Elimina un producto específico del carrito del usuario autenticado.
     * Solo se puede eliminar productos que pertenezcan al carrito del usuario.
     * **Requiere autenticación:** Incluye el token Bearer en el header Authorization.
     *
     * @summary Eliminar producto del carrito
     * @operationId removeFromCart
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @bodyParam cart_item_id integer required ID del item del carrito a eliminar. Example: 1
     *
     * @response 200 {
     *   "exito": true,
     *   "codMensaje": 0,
     *   "mensajeUsuario": "Producto eliminado del carrito exitosamente",
     *   "datoAdicional": {
     *     "cart_total": 77.00,
     *     "cart_items_count": 2,
     *     "is_empty": false
     *   }
     * }
     *
     * @response 200 {
     *   "exito": false,
     *   "codMensaje": 1,
     *   "mensajeUsuario": "Error al eliminar el producto del carrito",
     *   "datoAdicional": "El item no pertenece a tu carrito"
     * }
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
     *
     * Actualiza la cantidad de un producto específico en el carrito del usuario.
     * Se valida el stock disponible antes de actualizar la cantidad.
     * **Requiere autenticación:** Incluye el token Bearer en el header Authorization.
     *
     * @summary Actualizar cantidad de producto
     * @operationId updateCartQuantity
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @bodyParam cart_item_id integer required ID del item del carrito a actualizar. Example: 1
     * @bodyParam quantity integer required Nueva cantidad del producto (mínimo 1). Example: 3
     *
     * @response 200 {
     *   "exito": true,
     *   "codMensaje": 0,
     *   "mensajeUsuario": "Cantidad actualizada exitosamente",
     *   "datoAdicional": {
     *     "cart_item": {
     *       "id": 1,
     *       "quantity": 3,
     *       "total_price": 150.00
     *     },
     *     "cart_total": 227.00
     *   }
     * }
     *
     * @response 200 {
     *   "exito": false,
     *   "codMensaje": 1,
     *   "mensajeUsuario": "Error al actualizar la cantidad",
     *   "datoAdicional": "Stock insuficiente para este producto"
     * }
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
     *
     * Elimina todos los productos del carrito del usuario autenticado,
     * dejando el carrito completamente vacío.
     * **Requiere autenticación:** Incluye el token Bearer en el header Authorization.
     *
     * @summary Limpiar carrito completo
     * @operationId clearCart
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @response 200 {
     *   "exito": true,
     *   "codMensaje": 0,
     *   "mensajeUsuario": "Carrito limpiado exitosamente",
     *   "datoAdicional": {
     *     "cart_total": 0.00,
     *     "cart_items_count": 0,
     *     "is_empty": true
     *   }
     * }
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
     *
     * Confirma el carrito actual del usuario, crea una nueva orden con todos los productos
     * y genera automáticamente un nuevo carrito vacío para futuras compras.
     * **Requiere autenticación:** Incluye el token Bearer en el header Authorization.
     *
     * @summary Confirmar compra y crear orden
     * @operationId confirmCart
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @response 200 {
     *   "exito": true,
     *   "codMensaje": 0,
     *   "mensajeUsuario": "Orden creada exitosamente",
     *   "datoAdicional": {
     *     "order": {
     *       "id": 1,
     *       "order_number": "RST-2025-000001",
     *       "total_amount": 177.00,
     *       "status": "pending"
     *     },
     *     "new_cart": {
     *       "id": 2,
     *       "is_empty": true,
     *       "total_items": 0
     *     }
     *   }
     * }
     *
     * @response 200 {
     *   "exito": false,
     *   "codMensaje": 1,
     *   "mensajeUsuario": "Error al confirmar el carrito",
     *   "datoAdicional": "No puedes confirmar un carrito vacío"
     * }
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
