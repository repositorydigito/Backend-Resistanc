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
use Illuminate\Support\Facades\DB;

/**
 * @tags Bebidas
 */
final class DrinkController extends Controller
{
    /**
     * Lista todas las bebidas activas del sistema
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
     */
    public function baseDrinks(Request $request): JsonResponse
    {
        try {
            $bases = Basedrink::where('is_active', true)->get();

            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Bases de bebidas listadas exitosamente',
                'datoAdicional' => BasedrinkResource::collection($bases),
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error al listar las bases de bebidas',
                'datoAdicional' => null,
            ], 200);
        }
    }

    /**
     * Lista todos los sabores de bebidas disponibles
     */
    public function flavorDrinks(Request $request): JsonResponse
    {
        try {
            $flavors = Flavordrink::where('is_active', true)->get();

            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Sabores de bebidas listados exitosamente',
                'datoAdicional' => FlavordrinkResource::collection($flavors),
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error al listar los sabores de bebidas',
                'datoAdicional' => null,
            ], 200);
        }
    }

    /**
     * Lista todos los tipos de bebidas disponibles
     */
    public function typeDrinks(Request $request): JsonResponse
    {
        try {
            $types = Typedrink::where('is_active', true)->get();

            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Tipos de bebidas listados exitosamente',
                'datoAdicional' => TypedrinkResource::collection($types),
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error al listar los tipos de bebidas',
                'datoAdicional' => null,
            ], 200);
        }
    }


    /**
     * Añade una bebida al carrito del usuario autenticado
     */
    public function addToCart(Request $request): JsonResponse
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

                // Recargar relaciones y calcular precios
                $drink->load(['basesdrinks', 'flavordrinks', 'typesdrinks']);
                $drink->update([
                    'drink_name' => $drink->generateDrinkName(),
                    'drink_combination' => $drink->generateDrinkCombination(),
                    'total_price_soles' => $drink->calculateTotalPrice()
                ]);
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
                'mensajeUsuario' => 'Error al añadir bebida al carrito',
                'datoAdicional' => null,
            ], 200);
        }
    }


    /**
     * Muestra el carrito del usuario autenticado
     */
    public function showToCart(Request $request): JsonResponse
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
                'datoAdicional' => null,
            ], 200);
        }
    }

    /**
     * Actualiza la cantidad de una o varias bebidas en el carrito del usuario autenticado
     */
    public function updateCartQuantity(Request $request): JsonResponse
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
                'datoAdicional' => null,
            ], 200);
        }
    }

    /**
     * Remueve una bebida del carrito del usuario autenticado
     */
    public function removeFromCart(Request $request): JsonResponse
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
                'mensajeUsuario' => 'Error al remover bebida del carrito',
                'datoAdicional' => null,
            ], 200);
        }
    }

    /**
     * Confirmar carrito de bebidas y crear orden
     */
    public function confirmCart(Request $request): JsonResponse
    {
        try {
            // Validar datos de entrada
            $request->validate([
                'notes' => 'nullable|string|max:500',
            ]);

            $userId = $request->user()->id;

            // Buscar el carrito activo del usuario
            $cart = JuiceCartCodes::where('user_id', $userId)
                ->whereNull('juice_order_id')
                ->where('is_used', false)
                ->with(['drinks.basesdrinks', 'drinks.flavordrinks', 'drinks.typesdrinks'])
                ->latest('created_at')
                ->first();

            if (!$cart || $cart->drinks->isEmpty()) {
                return response()->json([
                    'exito' => false,
                    'codMensaje' => 0,
                    'mensajeUsuario' => 'No hay carrito activo o está vacío',
                    'datoAdicional' => null,
                ], 200);
            }

            // Obtener datos del usuario para historial
            $user = $request->user();

            return DB::transaction(function () use ($request, $cart, $user, $userId) {
                // Calcular totales
                $subtotal = 0;
                $orderDetails = [];

                foreach ($cart->drinks as $drink) {
                    // Asegurar que el precio se calcule correctamente
                    $drink->load(['basesdrinks', 'flavordrinks', 'typesdrinks']);

                    // Generar información histórica de la bebida
                    $drinkName = $drink->drink_name ?: $drink->generateDrinkName();
                    $drinkCombination = $drink->drink_combination ?: $drink->generateDrinkCombination();

                    // Calcular precio correctamente
                    $unitPrice = $drink->total_price_soles ?: $drink->calculateTotalPrice();

                    // Si el precio sigue siendo 0, calcular manualmente
                    if ($unitPrice == 0) {
                        $unitPrice = $drink->base_price_soles + $drink->typesdrinks->sum('price');
                    }

                    $quantity = $drink->pivot->quantity;
                    $totalPrice = $unitPrice * $quantity;

                    $subtotal += $totalPrice;

                    $orderDetails[] = [
                        'drink_id' => $drink->id,
                        'quantity' => $quantity,
                        'drink_name' => $drinkName,
                        'drink_combination' => $drinkCombination,
                        'unit_price_soles' => $unitPrice,
                        'total_price_soles' => $totalPrice,
                        'ingredients_info' => [
                            'bases' => $drink->basesdrinks->pluck('name')->toArray(),
                            'flavors' => $drink->flavordrinks->pluck('name')->toArray(),
                            'types' => $drink->typesdrinks->pluck('name')->toArray(),
                        ]
                    ];
                }

                // Calcular totales finales
                $totalAmount = $subtotal;

                // Crear la orden (contra entrega y ya pagado)
                $order = \App\Models\JuiceOrder::create([
                    'user_id' => $userId,
                    'user_name' => $user->name,
                    'user_email' => $user->email,
                    'subtotal_soles' => $subtotal,
                    'tax_amount_soles' => 0,
                    'discount_amount_soles' => 0,
                    'total_amount_soles' => $totalAmount,
                    'currency' => 'PEN',
                    'status' => 'pending',
                    'payment_status' => 'paid', // Ya viene pagado desde la app
                    'delivery_method' => 'pickup', // Todos contra entrega
                    'notes' => $request->notes,
                    'payment_method_name' => 'App',
                    'estimated_ready_at' => now()->addMinutes(count($orderDetails) * 5), // 5 min por bebida
                ]);

                // Crear detalles de la orden
                foreach ($orderDetails as $detail) {
                    $order->details()->create($detail);
                }

                // Marcar carrito como usado y asociar con la orden
                $cart->update([
                    'is_used' => true,
                    'juice_order_id' => $order->id,
                ]);

                // Cargar relaciones para la respuesta
                $order->load(['details', 'user']);

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
                            'subtotal_soles' => $order->subtotal_soles,
                            'total_amount_soles' => $order->total_amount_soles,
                            'estimated_ready_at' => $order->estimated_ready_at->format('Y-m-d H:i:s'),
                            'delivery_method' => $order->delivery_method,
                            'created_at' => $order->created_at->format('Y-m-d H:i:s'),
                        ],
                        'items' => $order->details->map(function ($detail) {
                            return [
                                'drink_name' => $detail->drink_name,
                                'quantity' => $detail->quantity,
                                'unit_price' => $detail->unit_price_soles,
                                'total_price' => $detail->total_price_soles,
                                'special_instructions' => $detail->special_instructions,
                            ];
                        }),
                        'cart' => [
                            'code' => $cart->code,
                            'is_used' => true,
                        ]
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
                'mensajeUsuario' => 'Error al confirmar el pedido',
                'datoAdicional' => null,
            ], 200);
        }
    }

    /**
     * Obtener órdenes del usuario
     */
    public function myOrders(Request $request): JsonResponse
    {
        try {
            $userId = $request->user()->id;

            $orders = \App\Models\JuiceOrder::where('user_id', $userId)
                ->with(['details'])
                ->orderBy('created_at', 'desc')
                ->paginate($request->integer('per_page', 15));

            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Órdenes obtenidas exitosamente',
                'datoAdicional' => [
                    'orders' => $orders->map(function ($order) {
                        return [
                            'id' => $order->id,
                            'order_number' => $order->order_number,
                            'status' => $order->status,
                            'payment_status' => $order->payment_status,
                            'total_amount_soles' => $order->total_amount_soles,
                            'estimated_ready_at' => $order->estimated_ready_at?->format('Y-m-d H:i:s'),
                            'delivery_method' => $order->delivery_method,
                            'created_at' => $order->created_at->format('Y-m-d H:i:s'),
                            'items_count' => $order->details->sum('quantity'),
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
                'mensajeUsuario' => 'Error al obtener órdenes',
                'datoAdicional' => null,
            ], 200);
        }
    }

    /**
     * Mostrar una orden específica
     */
    public function showOrder(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'order_id' => 'required|integer|exists:juice_orders,id',
            ]);

            $userId = $request->user()->id;
            $orderId = $request->integer('order_id');

            $order = \App\Models\JuiceOrder::where('id', $orderId)
                ->where('user_id', $userId)
                ->with(['details'])
                ->first();

            if (!$order) {
                return response()->json([
                    'exito' => false,
                    'codMensaje' => 0,
                    'mensajeUsuario' => 'Orden no encontrada',
                    'datoAdicional' => null,
                ], 200);
            }

            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Orden obtenida exitosamente',
                'datoAdicional' => [
                    'order' => [
                        'id' => $order->id,
                        'order_number' => $order->order_number,
                        'status' => $order->status,
                        'payment_status' => $order->payment_status,
                        'subtotal_soles' => $order->subtotal_soles,
                        'tax_amount_soles' => $order->tax_amount_soles,
                        'discount_amount_soles' => $order->discount_amount_soles,
                        'total_amount_soles' => $order->total_amount_soles,
                        'currency' => $order->currency,
                        'delivery_method' => $order->delivery_method,
                        'special_instructions' => $order->special_instructions,
                        'notes' => $order->notes,
                        'payment_method_name' => $order->payment_method_name,
                        'estimated_ready_at' => $order->estimated_ready_at?->format('Y-m-d H:i:s'),
                        'ready_at' => $order->ready_at?->format('Y-m-d H:i:s'),
                        'delivered_at' => $order->delivered_at?->format('Y-m-d H:i:s'),
                        'created_at' => $order->created_at->format('Y-m-d H:i:s'),
                        'confirmed_at' => $order->confirmed_at?->format('Y-m-d H:i:s'),
                        'preparing_at' => $order->preparing_at?->format('Y-m-d H:i:s'),
                    ],
                    'items' => $order->details->map(function ($detail) {
                        return [
                            'drink_name' => $detail->drink_name,
                            'quantity' => $detail->quantity,
                            'unit_price_soles' => $detail->unit_price_soles,
                            'total_price_soles' => $detail->total_price_soles,
                            'special_instructions' => $detail->special_instructions,
                            'ingredients_info' => $detail->ingredients_info,
                            'drink_combination' => $detail->drink_combination,
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
                'mensajeUsuario' => 'Error al obtener la orden',
                'datoAdicional' => null,
            ], 200);
        }
    }

    /**
     * Actualizar estado de una orden (solo para admin)
     */
    public function updateOrderStatus(Request $request): JsonResponse
    {
        try {
            // Validar que el usuario tenga permisos de admin
            if (!$request->user()->hasRole('admin')) {
                return response()->json([
                    'exito' => false,
                    'codMensaje' => 0,
                    'mensajeUsuario' => 'No tienes permisos para realizar esta acción',
                    'datoAdicional' => null,
                ], 403);
            }

            $request->validate([
                'order_id' => 'required|integer|exists:juice_orders,id',
                'status' => 'required|string|in:pending,confirmed,preparing,ready,delivered,cancelled',
                'notes' => 'nullable|string|max:500',
            ]);

            $orderId = $request->integer('order_id');
            $order = \App\Models\JuiceOrder::findOrFail($orderId);

            // Actualizar estado
            $order->updateStatus($request->status);

            // Agregar notas si se proporcionan
            if ($request->filled('notes')) {
                $currentNotes = $order->notes ? $order->notes . "\n" : '';
                $order->update([
                    'notes' => $currentNotes . "[" . now()->format('Y-m-d H:i:s') . "] " . $request->notes
                ]);
            }

            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Estado de orden actualizado exitosamente',
                'datoAdicional' => [
                    'order' => [
                        'id' => $order->id,
                        'order_number' => $order->order_number,
                        'status' => $order->status,
                        'updated_at' => $order->updated_at->format('Y-m-d H:i:s'),
                    ]
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
                'mensajeUsuario' => 'Error al actualizar el estado de la orden',
                'datoAdicional' => null,
            ], 200);
        }
    }
}
