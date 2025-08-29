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
            $bases = Basedrink::where('is_active', true)->get();

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
            $flavors = Flavordrink::where('is_active', true)->get();

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
            $types = Typedrink::where('is_active', true)->get();

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

            // Validar que todos los ingredientes especificados estén activos
            $validationErrors = [];

            if ($request->filled('base_id')) {
                $base = Basedrink::find($request->base_id);
                if (!$base || !$base->is_active) {
                    $validationErrors[] = 'La base de bebida seleccionada no está disponible';
                }
            }

            if ($request->filled('flavor_id')) {
                $flavor = Flavordrink::find($request->flavor_id);
                if (!$flavor || !$flavor->is_active) {
                    $validationErrors[] = 'El sabor seleccionado no está disponible';
                }
            }

            if ($request->filled('type_id')) {
                $type = Typedrink::find($request->type_id);
                if (!$type || !$type->is_active) {
                    $validationErrors[] = 'El tipo de bebida seleccionado no está disponible';
                }
            }

            // Si hay ingredientes inactivos, retornar error
            if (!empty($validationErrors)) {
                return response()->json([
                    'exito' => false,
                    'codMensaje' => 2,
                    'mensajeUsuario' => 'No se puede agregar la bebida al carrito',
                    'datoAdicional' => [
                        'errors' => $validationErrors,
                        'message' => 'Uno o más ingredientes no están disponibles actualmente'
                    ]
                ], 200);
            }

            // Buscar bebida existente con EXACTAMENTE las combinaciones especificadas
            $drink = null;

            if ($request->filled('base_id') || $request->filled('flavor_id') || $request->filled('type_id')) {
                $query = Drink::query();

                // Aplicar filtros para los parámetros enviados
                if ($request->filled('base_id')) {
                    $query->whereHas('basesdrinks', function ($q) use ($request) {
                        $q->where('basedrink_id', $request->base_id);
                    });
                }

                if ($request->filled('flavor_id')) {
                    $query->whereHas('flavordrinks', function ($q) use ($request) {
                        $q->where('flavordrink_id', $request->flavor_id);
                    });
                }

                if ($request->filled('type_id')) {
                    $query->whereHas('typesdrinks', function ($q) use ($request) {
                        $q->where('typedrink_id', $request->type_id);
                    });
                }

                // Filtrar para que NO tenga relaciones adicionales no especificadas
                if ($request->filled('base_id')) {
                    // Si se especifica base_id, la bebida NO debe tener otras bases
                    $query->whereDoesntHave('basesdrinks', function ($q) use ($request) {
                        $q->where('basedrink_id', '!=', $request->base_id);
                    });
                } else {
                    // Si NO se especifica base_id, la bebida NO debe tener ninguna base
                    $query->whereDoesntHave('basesdrinks');
                }

                if ($request->filled('flavor_id')) {
                    // Si se especifica flavor_id, la bebida NO debe tener otros flavors
                    $query->whereDoesntHave('flavordrinks', function ($q) use ($request) {
                        $q->where('flavordrink_id', '!=', $request->flavor_id);
                    });
                } else {
                    // Si NO se especifica flavor_id, la bebida NO debe tener ningún flavor
                    $query->whereDoesntHave('flavordrinks');
                }

                if ($request->filled('type_id')) {
                    // Si se especifica type_id, la bebida NO debe tener otros types
                    $query->whereDoesntHave('typesdrinks', function ($q) use ($request) {
                        $q->where('typedrink_id', '!=', $request->type_id);
                    });
                } else {
                    // Si NO se especifica type_id, la bebida NO debe tener ningún type
                    $query->whereDoesntHave('typesdrinks');
                }

                $drink = $query->first();
            }

            // Si no existe, crear la bebida y sus relaciones
            if (!$drink) {
                $drink = Drink::create();

                // Solo crear relaciones si se proporcionan los parámetros
                if ($request->filled('base_id')) {
                    $drink->basesdrinks()->attach($request->base_id);
                }
                if ($request->filled('flavor_id')) {
                    $drink->flavordrinks()->attach($request->flavor_id);
                }
                if ($request->filled('type_id')) {
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
     * Actualiza la cantidad de una o varias bebidas en el carrito del usuario autenticado
     *
     * **Requiere autenticación:** Incluye el token Bearer en el header Authorization.
     *
     * @summary Actualizar cantidad de bebida(s) en el carrito
     * @operationId updateCartQuantity
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @bodyParam drink_id integer sometimes ID de la bebida en el carrito (para actualización individual). Example: 3
     * @bodyParam quantity integer sometimes Nueva cantidad (mínimo 1, para actualización individual). Example: 2
     * @bodyParam drinks array sometimes Array de bebidas para actualización masiva. Example: [{"drink_id": 1, "quantity": 2}, {"drink_id": 3, "quantity": 5}]
     * @bodyParam drinks.*.drink_id integer required ID de la bebida en el carrito. Example: 1
     * @bodyParam drinks.*.quantity integer required Nueva cantidad (mínimo 1). Example: 2
     *
     * @response 200 {
     *   "exito": true,
     *   "codMensaje": 1,
     *   "mensajeUsuario": "Cantidades actualizadas exitosamente",
     *   "datoAdicional": {
     *     "id": 1,
     *     "code": "NWHLD7DJ",
     *     "drinks": [...],
     *     "total_items": 3,
     *     "total_price": 0,
     *     "updated_drinks": [1, 3],
     *     "not_found_drinks": []
     *   }
     * }
     *
     * @response 200 {
     *   "exito": false,
     *   "codMensaje": 2,
     *   "mensajeUsuario": "La bebida no está en el carrito",
     *   "datoAdicional": null
     * }
     */
    public function updateCartQuantity(Request $request)
    {
        try {
            // Validar si es actualización individual o masiva
            if ($request->has('drinks')) {
                // Validación para actualización masiva
                $request->validate([
                    'drinks' => 'required|array|min:1|max:50',
                    'drinks.*.drink_id' => 'required|integer|exists:drinks,id',
                    'drinks.*.quantity' => 'required|integer|min:1',
                ]);
            } else {
                // Validación para actualización individual
                $request->validate([
                    'drink_id' => 'required|integer|exists:drinks,id',
                    'quantity' => 'required|integer|min:1',
                ]);
            }

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

            $updatedDrinks = [];
            $notFoundDrinks = [];
            $errors = [];

            if ($request->has('drinks')) {
                // Actualización masiva
                foreach ($request->input('drinks') as $drinkData) {
                    $drinkId = $drinkData['drink_id'];
                    $newQuantity = $drinkData['quantity'];

                    // Verificar si la bebida existe en el carrito
                    $existingDrink = $cart->drinks()->where('drink_id', $drinkId)->first();

                    if (!$existingDrink) {
                        $notFoundDrinks[] = $drinkId;
                        continue;
                    }

                    try {
                        // Actualizar la cantidad
                        $cart->drinks()->updateExistingPivot($drinkId, [
                            'quantity' => $newQuantity
                        ]);
                        $updatedDrinks[] = $drinkId;
                    } catch (\Exception $e) {
                        $errors[] = "Error actualizando bebida ID {$drinkId}: " . $e->getMessage();
                    }
                }

                $message = 'Cantidades actualizadas exitosamente';
                if (!empty($notFoundDrinks)) {
                    $message .= '. Algunas bebidas no fueron encontradas en el carrito';
                }
                if (!empty($errors)) {
                    $message .= '. Algunos elementos tuvieron errores';
                }
            } else {
                // Actualización individual
                $drinkId = $request->integer('drink_id');
                $newQuantity = $request->integer('quantity');

                // Verificar si la bebida existe en el carrito
                $existingDrink = $cart->drinks()->where('drink_id', $drinkId)->first();

                if (!$existingDrink) {
                    return response()->json([
                        'exito' => false,
                        'codMensaje' => 2,
                        'mensajeUsuario' => 'La bebida no está en el carrito',
                        'datoAdicional' => null,
                    ], 200);
                }

                // Actualizar la cantidad
                $cart->drinks()->updateExistingPivot($drinkId, [
                    'quantity' => $newQuantity
                ]);

                $updatedDrinks[] = $drinkId;
                $message = 'Cantidad actualizada exitosamente';
            }

            // Recargar el carrito con las relaciones para usar el resource
            $cart->load('drinks.basesdrinks', 'drinks.flavordrinks', 'drinks.typesdrinks');

            $responseData = new JuiceCartCodesResource($cart);

            // Agregar información adicional para actualizaciones masivas
            if ($request->has('drinks')) {
                $responseData = $responseData->toArray($request);
                $responseData['updated_drinks'] = $updatedDrinks;
                $responseData['not_found_drinks'] = $notFoundDrinks;
                if (!empty($errors)) {
                    $responseData['errors'] = $errors;
                }
            }

            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => $message,
                'datoAdicional' => $responseData,
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
                'mensajeUsuario' => 'Error al actualizar cantidad en el carrito',
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
