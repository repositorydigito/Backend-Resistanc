<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BasedrinkResource;
use App\Http\Resources\DrinkResource;
use App\Http\Resources\FlavordrinkResource;
use App\Http\Resources\TypedrinkResource;
use App\Http\Resources\JuiceCartCodesResource;
use App\Models\Basedrink;
use App\Models\Drink;
use App\Models\Flavordrink;
use App\Models\JuiceCartCodes;
use App\Models\Typedrink;
use Dedoc\Scramble\Support\Generator\Types\Type;
use DragonCode\PrettyArray\Services\Formatters\Json;
use Error;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @tags Bebidas
 */
final class DrinkController extends Controller
{
    /**
     * Lista todas las bebidas activas del sistema
     *
     * Obtiene una lista paginada de bebidas ordenadas por nombre.
     * **Requiere autenticación:** Incluye el token Bearer en el header Authorization.
     *
     * @summary Listar bebidas
     * @operationId getDrinksList
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     *
     * @queryParam per_page integer Número de bebidas por página (máximo 100). Example: 15
     * @queryParam page integer Número de página para la paginación. Example: 1
     * @queryParam include_relations boolean Incluir relaciones (bases, sabores, tipos). Example: true
     * @queryParam search string Buscar bebidas por nombre o descripción. Example: "café"
     * @queryParam base_id integer Filtrar por base de bebida específica. Example: 1
     * @queryParam flavor_id integer Filtrar por sabor específico. Example: 2
     * @queryParam type_id integer Filtrar por tipo de bebida específico. Example: 3
     * @queryParam min_price decimal Precio mínimo para filtrar. Example: 10.50
     * @queryParam max_price decimal Precio máximo para filtrar. Example: 25.00
     *
     */
    public function index(Request $request): JsonResponse
    {

        try {
            $query = Drink::query();

            // Paginación o lista completa
            if ($request->has('per_page')) {
                $drinks = $query->paginate(
                    perPage: $request->integer('per_page', 15),
                    page: $request->integer('page', 1)
                );
            } else {
                $drinks = $query->get();
            }

            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Bebidas listadas exitosamente',
                'datoAdicional' => DrinkResource::collection($drinks),
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error al listar bebidas',
                'datoAdicional' => null,
            ], 200);
        }
    }

    /**
     * Muestra una bebida específica
     *
     * Obtiene los detalles completos de una bebida por su ID.
     * **Requiere autenticación:** Incluye el token Bearer en el header Authorization.
     *
     * @summary Mostrar bebida específica
     * @operationId getDrinkById
     *
     * @param  int  $id
     * @return \App\Http\Resources\DrinkResource
     *
     * @response 200 {
     *   "data": {
     *     "id": 1,
     *     "name": "Cappuccino Vainilla",
     *     "slug": "cappuccino-vainilla",
     *     "description": "Delicioso cappuccino con esencia de vainilla y espuma cremosa",
     *     "image_url": "https://example.com/images/cappuccino-vainilla.jpg",
     *     "price": 18.50,
     *     "bases": [
     *       {
     *         "id": 1,
     *         "name": "Café Espresso",
     *         "description": "Base de café espresso intenso"
     *       }
     *     ],
     *     "flavors": [
     *       {
     *         "id": 3,
     *         "name": "Vainilla",
     *         "description": "Esencia natural de vainilla"
     *       }
     *     ],
     *     "types": [
     *       {
     *         "id": 2,
     *         "name": "Caliente",
     *         "description": "Bebida servida caliente"
     *       }
     *     ],
     *     "created_at": "2024-01-15T10:30:00.000Z",
     *     "updated_at": "2024-01-15T10:30:00.000Z"
     *   }
     * }
     *
     * @response 404 {
     *   "message": "Bebida no encontrada"
     * }
     */
    public function show(Request $request): JsonResponse
    {
        try {

            $request->validate([
                'drink_id' => 'required|integer|exists:drinks,id',
            ]);

            $drink = Drink::with(['basesdrinks', 'flavordrinks', 'typesdrinks'])
                ->findOrFail($request->input('drink_id'));

            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Bebida obtenida exitosamente',
                'datoAdicional' => new DrinkResource($drink),
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error al obtener bebida',
                'datoAdicional' => null,
            ], 200);
        }
    }

    /**
     * Lista todas las bases de bebidas disponibles
     *
     * Obtiene un listado completo de las bases de bebidas registradas en el sistema.
     * **Requiere autenticación:** Incluye el token Bearer en el header Authorization.
     *
     * @summary Listar bases de bebidas
     * @operationId getDrinkBases
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     *
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "Café Espresso",
     *       "description": "Base de café espresso de alta calidad",
     *       "created_at": "2024-01-15T10:30:00.000Z",
     *       "updated_at": "2024-01-15T10:30:00.000Z"
     *     },
     *     {
     *       "id": 2,
     *       "name": "Té Negro",
     *       "description": "Base de té negro orgánico",
     *       "created_at": "2024-01-15T10:30:00.000Z",
     *       "updated_at": "2024-01-15T10:30:00.000Z"
     *     }
     *   ]
     * }
     */
    public function baseDrinks()
    {

        try {
            $bases = Basedrink::all();

            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Bases de bebidas listadas exitosamente',
                'datoAdicional' => BasedrinkResource::collection($bases),
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'exito' => false,
                'codMensaje' => 2,
                'mensajeUsuario' => 'Error al listar las bases de bebidas',
                'datoAdicional' => $e->errors(),
            ], 200);
        } catch (\Throwable $e) {

            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error inesperado al listar las bases de bebidas',
                'datoAdicional' => $e->getMessage(),
            ], 200);
        }
    }

    /**
     * Lista todos los sabores de bebidas disponibles
     *
     * Obtiene un listado completo de los sabores registrados para bebidas.
     * **Requiere autenticación:** Incluye el token Bearer en el header Authorization.
     *
     * @summary Listar sabores de bebidas
     * @operationId getDrinkFlavors
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     *
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "Vainilla",
     *       "description": "Esencia natural de vainilla",
     *       "created_at": "2024-01-15T10:30:00.000Z",
     *       "updated_at": "2024-01-15T10:30:00.000Z"
     *     },
     *     {
     *       "id": 2,
     *       "name": "Caramelo",
     *       "description": "Sabor a caramelo artesanal",
     *       "created_at": "2024-01-15T10:30:00.000Z",
     *       "updated_at": "2024-01-15T10:30:00.000Z"
     *     }
     *   ]
     * }
     */

    public function flavorDrinks()
    {

        try {
            $flavors = Flavordrink::all();

            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Sabores de bebidas listados exitosamente',
                'datoAdicional' => FlavordrinkResource::collection($flavors),
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'exito' => false,
                'codMensaje' => 2,
                'mensajeUsuario' => 'Error al listar los sabores de bebidas',
                'datoAdicional' => $e->errors(),
            ], 200);
        } catch (\Throwable $e) {

            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error inesperado al listar los sabores de bebidas',
                'datoAdicional' => $e->getMessage(),
            ], 200);
        }
    }

    /**
     * Lista todos los tipos de bebidas disponibles
     *
     * Obtiene un listado completo de los tipos de preparación de bebidas.
     * **Requiere autenticación:** Incluye el token Bearer en el header Authorization.
     *
     * @summary Listar tipos de bebidas
     * @operationId getDrinkTypes
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     *
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "Fría",
     *       "description": "Bebida servida con hielo",
     *       "created_at": "2024-01-15T10:30:00.000Z",
     *       "updated_at": "2024-01-15T10:30:00.000Z"
     *     },
     *     {
     *       "id": 2,
     *       "name": "Caliente",
     *       "description": "Bebida servida caliente",
     *       "created_at": "2024-01-15T10:30:00.000Z",
     *       "updated_at": "2024-01-15T10:30:00.000Z"
     *     }
     *   ]
     * }
     */

    public function typeDrinks()
    {

        try {
            $types = Typedrink::all();

            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Tipos de bebidas listados exitosamente',
                'datoAdicional' => TypedrinkResource::collection($types),
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'exito' => false,
                'codMensaje' => 2,
                'mensajeUsuario' => 'Error al listar los tipos de bebidas',
                'datoAdicional' => $e->errors(),
            ], 200);
        } catch (\Throwable $e) {

            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error inesperado al listar los tipos de bebidas',
                'datoAdicional' => $e->getMessage(),
            ], 200);
        }
    }


    /**
     * Añade una bebida al carrito del usuario autenticado
     *
     * **Requiere autenticación:** Incluye el token Bearer en el header Authorization.
     *
     * @summary Añadir bebida al carrito
     * @operationId addToCart

     */

    public function addToCart(Request $request)
    {
        try {
            $request->validate([
                'base_id' => 'sometimes|integer|exists:basedrinks,id',
                'flavor_id' => 'sometimes|integer|exists:flavordrinks,id',
                'type_id' => 'sometimes|integer|exists:typedrinks,id',
                'quantity' => 'required|integer|min:1',
            ]);

            $userId = $request->user()->id;

            // Buscar la bebida que tenga las relaciones correctas
            $drink = Drink::whereHas('basesdrinks', function ($query) use ($request) {
                if ($request->base_id) {
                    $query->where('basedrink_id', $request->base_id);
                }
            })
                ->whereHas('flavordrinks', function ($query) use ($request) {
                    if ($request->flavor_id) {
                        $query->where('flavordrink_id', $request->flavor_id);
                    }
                })
                ->whereHas('typesdrinks', function ($query) use ($request) {
                    if ($request->type_id) {
                        $query->where('typedrink_id', $request->type_id);
                    }
                })
                ->first();

            // Si no existe, crear la bebida y sus relaciones
            if (!$drink) {
                $drink = Drink::create();
                if ($request->base_id) {
                    $drink->basesdrinks()->attach($request->base_id);
                }
                if ($request->flavor_id) {
                    $drink->flavordrinks()->attach($request->flavor_id);
                }
                if ($request->type_id) {
                    $drink->typesdrinks()->attach($request->type_id);
                }
            }

            // Buscar el último carrito del usuario que NO tenga juice_order_id asociado
            $cart = JuiceCartCodes::where('user_id', $userId)
                ->whereNull('juice_order_id')
                ->latest('created_at')
                ->first();

            // Si no hay carrito sin juice_order_id, crear uno nuevo
            if (!$cart) {
                $cart = JuiceCartCodes::create([
                    'user_id' => $userId,
                    'is_used' => false,
                ]);
            }

            // Verificar si la bebida ya existe en el carrito
            $existingDrink = $cart->drinks()->where('drink_id', $drink->id)->first();
            
            if ($existingDrink) {
                // Si ya existe, actualizar la cantidad
                $cart->drinks()->updateExistingPivot($drink->id, [
                    'quantity' => $existingDrink->pivot->quantity + $request->quantity
                ]);
            } else {
                // Si no existe, agregar nueva bebida al carrito
                $cart->drinks()->attach($drink->id, ['quantity' => $request->quantity]);
            }

            // Recargar el carrito con las relaciones para usar el resource
            $cart->load('drinks.basesdrinks', 'drinks.flavordrinks', 'drinks.typesdrinks');

            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Bebida añadida al carrito exitosamente',
                'datoAdicional' => new JuiceCartCodesResource($cart),
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error al añadir bebida al carrito',
                'datoAdicional' => $e->getMessage(),
            ], 200);
        }
    }


    /**
     * Muestra el carrito del usuario autenticado
     *
     * **Requiere autenticación:** Incluye el token Bearer en el header Authorization.
     *
     * @summary Mostrar carrito
     * @operationId showToCart

     */

    public function showToCart(Request $request)
    {
        try {
            $userId = $request->user()->id;

            // Buscar el último carrito del usuario que NO tenga juice_order_id asociado
            $cart = JuiceCartCodes::where('user_id', $userId)
                ->whereNull('juice_order_id')
                ->where('is_used', false)
                ->latest('created_at')
                ->first();

            if (!$cart) {
                return response()->json([
                    'exito' => false,
                    'codMensaje' => 2,
                    'mensajeUsuario' => 'No hay carrito activo',
                    'datoAdicional' => null,
                ], 200);
            }

            // Recargar el carrito con las relaciones para usar el resource
            $cart->load('drinks.basesdrinks', 'drinks.flavordrinks', 'drinks.typesdrinks');

            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Carrito obtenido exitosamente',
                'datoAdicional' => new JuiceCartCodesResource($cart),
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error al obtener el carrito',
                'datoAdicional' => $e->getMessage(),
            ], 200);
        }
    }

    /**
     * Remueve una bebida del carrito del usuario autenticado
     *
     * **Requiere autenticación:** Incluye el token Bearer en el header Authorization.
     *
     * @summary Remover bebida del carrito
     * @operationId removeFromCart
     *
     */
    public function removeFromCart(Request $request)
    {
        try {
            $request->validate([
                'drink_id' => 'required|integer|exists:drinks,id',
                'remove_all' => 'sometimes|boolean', // Si es true, remueve toda la cantidad
            ]);

            $userId = $request->user()->id;

            // Buscar el carrito activo del usuario
            $cart = JuiceCartCodes::where('user_id', $userId)
                ->whereNull('juice_order_id')
                ->where('is_used', false)
                ->latest('created_at')
                ->first();

            if (!$cart) {
                return response()->json([
                    'exito' => false,
                    'codMensaje' => 2,
                    'mensajeUsuario' => 'No hay carrito activo',
                    'datoAdicional' => null,
                ], 200);
            }

            // Verificar si la bebida existe en el carrito
            $existingDrink = $cart->drinks()->where('drink_id', $request->drink_id)->first();

            if (!$existingDrink) {
                return response()->json([
                    'exito' => false,
                    'codMensaje' => 2,
                    'mensajeUsuario' => 'La bebida no está en el carrito',
                    'datoAdicional' => null,
                ], 200);
            }

            // Si remove_all es true o no se especifica, remover toda la bebida
            if ($request->input('remove_all', true)) {
                $cart->drinks()->detach($request->drink_id);
                $message = 'Bebida removida completamente del carrito';
            } else {
                // Si remove_all es false, solo reducir la cantidad en 1
                $currentQuantity = $existingDrink->pivot->quantity;
                if ($currentQuantity <= 1) {
                    // Si solo queda 1, remover completamente
                    $cart->drinks()->detach($request->drink_id);
                    $message = 'Bebida removida completamente del carrito';
                } else {
                    // Reducir cantidad en 1
                    $cart->drinks()->updateExistingPivot($request->drink_id, [
                        'quantity' => $currentQuantity - 1
                    ]);
                    $message = 'Cantidad de bebida reducida en 1';
                }
            }

            // Recargar el carrito con las relaciones para usar el resource
            $cart->load('drinks.basesdrinks', 'drinks.flavordrinks', 'drinks.typesdrinks');

            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => $message,
                'datoAdicional' => new JuiceCartCodesResource($cart),
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error al remover bebida del carrito',
                'datoAdicional' => $e->getMessage(),
            ], 200);
        }
    }
}
