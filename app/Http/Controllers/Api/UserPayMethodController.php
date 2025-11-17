<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserPaymentMethodResource;
use App\Models\Log;
use App\Models\UserPaymentMethod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Dedoc\Scramble\Attributes\BodyParameter;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

/**
 * @tags Métodos de Pago
 */
final class UserPayMethodController extends Controller
{
    /**
     * Lista todos los métodos de pago del usuario autenticado
     *
     * @param string $source - 'database' (default) o 'stripe' para obtener directamente desde Stripe
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Validar parámetros
            $request->validate([
                'per_page' => 'nullable|integer|min:1|max:50',
                'page' => 'nullable|integer|min:1',
                'source' => 'nullable|string|in:database,stripe',
                'status' => 'nullable|string',
                'payment_type' => 'nullable|string'
            ]);

            $source = $request->input('source', 'database'); // Por defecto desde BD
            $user = Auth::user();

            // Si se solicita obtener directamente desde Stripe
            if ($source === 'stripe') {
                return $this->getPaymentMethodsFromStripe($user, $request);
            }

            // Método tradicional: desde base de datos
            $query = UserPaymentMethod::query()
                ->where('user_id', Auth::id())
                ->orderBy('is_default', 'desc')
                ->orderBy('last_used_at', 'desc')
                ->orderBy('created_at', 'desc');

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('payment_type')) {
                $query->where('payment_type', $request->payment_type);
            }

            if (!$request->filled('status')) {
                $query->where('status', 'active');
            }

            // Paginación opcional
            if ($request->has('per_page')) {
                $paymentMethods = $query->paginate(
                    perPage: min($request->integer('per_page', 10), 50),
                    page: $request->integer('page', 1)
                );

                return response()->json([
                    'exito' => true,
                    'codMensaje' => 1,
                    'mensajeUsuario' => 'Métodos de pago obtenidos exitosamente',
                    'datoAdicional' => [
                        'payment_methods' => UserPaymentMethodResource::collection($paymentMethods->items())->resolve(),
                        'pagination' => [
                            'current_page' => $paymentMethods->currentPage(),
                            'last_page' => $paymentMethods->lastPage(),
                            'per_page' => $paymentMethods->perPage(),
                            'total' => $paymentMethods->total(),
                            'from' => $paymentMethods->firstItem(),
                            'to' => $paymentMethods->lastItem(),
                            'has_more_pages' => $paymentMethods->hasMorePages(),
                        ]
                    ]
                ], 200);
            } else {
                // Sin paginación - retornar todos los resultados
                $paymentMethods = $query->get();

                return response()->json([
                    'exito' => true,
                    'codMensaje' => 1,
                    'mensajeUsuario' => 'Métodos de pago obtenidos exitosamente',
                    'datoAdicional' => [
                        'payment_methods' => UserPaymentMethodResource::collection($paymentMethods)->resolve(),
                        'total_count' => $paymentMethods->count()
                    ]
                ], 200);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::create([
                'user_id' => Auth::id(),
                'action' => 'Lista todos los métodos de pago del usuario autenticado',
                'description' => 'Datos de entrada inválidos',
                'data' => $e->getMessage(),
            ]);

            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Datos de entrada inválidos',
                'datoAdicional' => $e->errors()
            ], 200);
        } catch (\Throwable $e) {
            Log::create([
                'user_id' => Auth::id(),
                'action' => 'Lista todos los métodos de pago del usuario autenticado',
                'description' => 'Error al obtener los métodos de pago',
                'data' => $e->getMessage(),
            ]);

            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error al obtener los métodos de pago',
                'datoAdicional' => $e->getMessage()
            ], 200);
        }
    }

    /**
     * Crear un nuevo método de pago
     */
    #[BodyParameter('payment_type', description: 'Tipo de pago', type: 'string', example: 'credit_card')]
    #[BodyParameter('card_brand', description: 'Marca de la tarjeta', type: 'string', example: 'visa')]
    #[BodyParameter('card_last_four', description: 'Últimos 4 dígitos de la tarjeta', type: 'string', example: '4532')]
    #[BodyParameter('card_holder_name', description: 'Nombre del titular de la tarjeta', type: 'string', example: 'Juan Carlos Pérez')]
    #[BodyParameter('card_expiry_month', description: 'Mes de expiración (1-12)', type: 'integer', example: 12)]
    #[BodyParameter('card_expiry_year', description: 'Año de expiración', type: 'integer', example: 2026)]
    #[BodyParameter('bank_name', description: 'Nombre del banco emisor', type: 'string', example: 'Banco de Crédito del Perú')]
    #[BodyParameter('is_default', description: 'Marcar como método de pago predeterminado', type: 'boolean', example: false)]
    #[BodyParameter('billing_address', description: 'Dirección de facturación', type: 'object', example: ['street' => 'Av. Javier Prado Este 4200', 'city' => 'Lima', 'state' => 'Lima', 'postal_code' => '15023', 'country' => 'Perú'])]
    #[BodyParameter('gateway_token', description: 'Token del gateway de pago', type: 'string', example: 'tok_1234567890abcdef')]
    #[BodyParameter('gateway_customer_id', description: 'ID del cliente en el gateway', type: 'string', example: 'cus_customer123456')]
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'payment_type' => [
                    'required',
                    'string',
                    Rule::in(['credit_card', 'debit_card', 'bank_transfer', 'digital_wallet', 'cash'])
                ],
                'card_brand' => [
                    'required',
                    'string',
                    // Aceptar marcas en cualquier formato (mayúsculas, minúsculas o mixtas)
                    // Se normalizará a minúsculas después de la validación
                ],
                'card_last_four' => ['required', 'string', 'size:4'],
                'card_holder_name' => ['required', 'string', 'max:255'],
                'card_expiry_month' => ['required', 'integer', 'between:1,12'],
                'card_expiry_year' => ['required', 'integer', 'min:' . date('Y'), 'max:' . (date('Y') + 20)],
                'bank_name' => ['nullable', 'string', 'max:100'],
                'is_default' => ['boolean'],
                'billing_address' => ['nullable', 'array'],
                'billing_address.street' => ['nullable', 'string', 'max:255'],
                'billing_address.city' => ['nullable', 'string', 'max:100'],
                'billing_address.state' => ['nullable', 'string', 'max:100'],
                'billing_address.postal_code' => ['nullable', 'string', 'max:20'],
                'billing_address.country' => ['nullable', 'string', 'max:100'],
                'gateway_token' => ['nullable', 'string', 'max:500'],
                'gateway_customer_id' => ['nullable', 'string', 'max:255'],
            ]);

            // Normalizar card_brand a minúsculas para consistencia
            if (isset($validated['card_brand'])) {
                $validated['card_brand'] = strtolower($validated['card_brand']);
            }

            // Agregar campos automáticos
            $validated['user_id'] = Auth::id();
            $validated['status'] = 'active';
            $validated['verification_status'] = 'pending';
            $validated['is_saved_for_future'] = true;

            // Si se marca como predeterminado, quitar de otros métodos
            if ($validated['is_default'] ?? false) {
                UserPaymentMethod::where('user_id', Auth::id())
                    ->update(['is_default' => false]);
            }

            $paymentMethod = UserPaymentMethod::create($validated);

            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Método de pago creado exitosamente',
                'datoAdicional' => new UserPaymentMethodResource($paymentMethod)
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::create([
                'user_id' => Auth::id(),
                'action' => 'Crear un nuevo método de pago',
                'description' => 'Datos de entrada inválidos',
                'data' => $e->getMessage(),
            ]);

            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Datos de entrada inválidos',
                'datoAdicional' => $e->errors()
            ], 200);
        } catch (\Throwable $e) {
            Log::create([
                'user_id' => Auth::id(),
                'action' => 'Crear un nuevo método de pago',
                'description' => 'Error al crear el método de pago',
                'data' => $e->getMessage(),
            ]);

            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error al crear el método de pago',
                'datoAdicional' => $e->getMessage()
            ], 200);
        }
    }

    /**
     * Mostrar un método de pago específico
     */
    public function show(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'id' => 'required|integer|exists:user_payment_methods,id'
            ]);

            $id = $request->integer('id');
            $paymentMethod = UserPaymentMethod::where('user_id', Auth::id())
                ->where('id', $id)
                ->first();

            if (!$paymentMethod) {
                return response()->json([
                    'exito' => false,
                    'codMensaje' => 0,
                    'mensajeUsuario' => 'Método de pago no encontrado o no tienes permisos para verlo',
                    'datoAdicional' => null
                ], 200);
            }

            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Método de pago obtenido exitosamente',
                'datoAdicional' => new UserPaymentMethodResource($paymentMethod)
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::create([
                'user_id' => Auth::id(),
                'action' => 'Mostrar un método de pago específico',
                'description' => 'Datos de entrada inválidos',
                'data' => $e->getMessage(),
            ]);

            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Datos de entrada inválidos',
                'datoAdicional' => $e->errors()
            ], 200);
        } catch (\Throwable $e) {
            Log::create([
                'user_id' => Auth::id(),
                'action' => 'Mostrar un método de pago específico',
                'description' => 'Error al obtener el método de pago',
                'data' => $e->getMessage(),
            ]);

            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error al obtener el método de pago',
                'datoAdicional' => $e->getMessage()
            ], 200);
        }
    }

    /**
     * Actualizar un método de pago
     */
    public function update(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'id' => ['required', 'integer', 'exists:user_payment_methods,id'],
                'card_holder_name' => ['sometimes', 'string', 'max:255'],
                'card_expiry_month' => ['sometimes', 'integer', 'between:1,12'],
                'card_expiry_year' => ['sometimes', 'integer', 'min:' . date('Y'), 'max:' . (date('Y') + 20)],
                'billing_address' => ['nullable', 'array'],
                'billing_address.street' => ['nullable', 'string', 'max:255'],
                'billing_address.city' => ['nullable', 'string', 'max:100'],
                'billing_address.state' => ['nullable', 'string', 'max:100'],
                'billing_address.postal_code' => ['nullable', 'string', 'max:20'],
                'billing_address.country' => ['nullable', 'string', 'max:100'],
                'bank_name' => ['nullable', 'string', 'max:100'],
                'is_default' => ['boolean'],
            ]);

            $id = $validated['id'];
            unset($validated['id']);

            // Verificar que el método de pago pertenece al usuario autenticado
            $paymentMethod = UserPaymentMethod::where('user_id', Auth::id())
                ->where('id', $id)
                ->first();

            if (!$paymentMethod) {
                return response()->json([
                    'exito' => false,
                    'codMensaje' => 0,
                    'mensajeUsuario' => 'Método de pago no encontrado o no tienes permisos para editarlo',
                    'datoAdicional' => null
                ], 200);
            }

            // Si se marca como predeterminado, quitar de otros métodos del mismo usuario
            if (($validated['is_default'] ?? false) && !$paymentMethod->is_default) {
                UserPaymentMethod::where('user_id', Auth::id())
                    ->where('id', '!=', $id)
                    ->update(['is_default' => false]);
            }

            $paymentMethod->update($validated);

            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Método de pago actualizado exitosamente',
                'datoAdicional' => new UserPaymentMethodResource($paymentMethod->fresh())
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::create([
                'user_id' => Auth::id(),
                'action' => 'Actualizar un método de pago',
                'description' => 'Datos de entrada inválidos',
                'data' => $e->getMessage(),
            ]);

            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Datos de entrada inválidos',
                'datoAdicional' => $e->errors()
            ], 200);
        } catch (\Throwable $e) {
            Log::create([
                'user_id' => Auth::id(),
                'action' => 'Actualizar un método de pago',
                'description' => 'Error al actualizar el método de pago',
                'data' => $e->getMessage(),
            ]);

            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error al actualizar el método de pago',
                'datoAdicional' => $e->getMessage()
            ], 200);
        }
    }

    /**
     * Eliminar un método de pago
     * Acepta 'id' (ID de la base de datos) o 'gateway_token' (ID de Stripe)
     */
    // public function destroy(Request $request): JsonResponse
    // {
    //     try {
    //         // Log inicial para confirmar que el método se está ejecutando
    //         Log::create([
    //             'user_id' => Auth::id(),
    //             'action' => 'Eliminar método de pago - ENTRADA',
    //             'description' => 'Método destroy llamado - Iniciando proceso de eliminación',
    //             'data' => json_encode([
    //                 'request_data' => $request->all(),
    //                 'has_id' => $request->has('id'),
    //                 'has_gateway_token' => $request->has('gateway_token'),
    //                 'id_value' => $request->input('id'),
    //                 'gateway_token_value' => $request->input('gateway_token'),
    //                 'user_id' => Auth::id()
    //             ])
    //         ]);

    //         // Validar que al menos uno de los dos esté presente
    //         $request->validate([
    //             'id' => 'required_without:gateway_token|nullable',
    //             'gateway_token' => 'required_without:id|nullable|string'
    //         ]);

    //         $userId = Auth::id();
    //         $paymentMethod = null;
    //         $gatewayToken = $request->has('gateway_token') && $request->filled('gateway_token')
    //             ? $request->string('gateway_token')
    //             : null;

    //         // Log después de obtener el gateway_token
    //         Log::create([
    //             'user_id' => $userId,
    //             'action' => 'Eliminar método de pago - Datos procesados',
    //             'description' => 'Datos de entrada procesados',
    //             'data' => json_encode([
    //                 'gateway_token' => $gatewayToken,
    //                 'has_id' => $request->has('id') && $request->filled('id'),
    //                 'id_value' => $request->input('id')
    //             ])
    //         ]);

    //         // Buscar por ID de base de datos o por gateway_token
    //         if ($request->has('id') && $request->filled('id')) {
    //             // Intentar convertir a entero si es numérico
    //             $idValue = $request->input('id');
    //             if (is_numeric($idValue)) {
    //                 $id = (int) $idValue;
    //                 $paymentMethod = UserPaymentMethod::where('user_id', $userId)
    //                     ->where('id', $id)
    //                     ->first();
    //             }
    //         }

    //         // Si no se encontró por ID y tenemos gateway_token, intentar buscar por gateway_token
    //         if (!$paymentMethod && $gatewayToken) {
    //             $paymentMethod = UserPaymentMethod::where('user_id', $userId)
    //                 ->where('gateway_token', $gatewayToken)
    //                 ->first();
    //         }

    //         if (!$paymentMethod) {
    //             // Log cuando no se encuentra el método de pago
    //             Log::create([
    //                 'user_id' => $userId,
    //                 'action' => 'Eliminar método de pago - No encontrado en BD',
    //                 'description' => 'Método de pago no encontrado en la base de datos',
    //                 'data' => json_encode([
    //                     'gateway_token' => $gatewayToken,
    //                     'has_id' => $request->has('id') && $request->filled('id'),
    //                     'id_value' => $request->input('id'),
    //                     'intentando_eliminar_de_stripe' => !empty($gatewayToken)
    //                 ])
    //             ]);

    //             // Si no existe en la BD pero se envió gateway_token, intentar eliminar solo de Stripe
    //             if ($gatewayToken) {
    //                 $user = Auth::user();

    //                 if ($user && $user->stripe_id) {
    //                     try {
    //                         $stripeClient = $this->makeStripeClient();
    //                         $stripeId = $user->stripe_id;

    //                         // Verificar que el payment method pertenece al customer antes de detach
    //                         try {
    //                             $stripePaymentMethod = $stripeClient->paymentMethods->retrieve($gatewayToken);

    //                             // Verificar que pertenece al customer del usuario
    //                             if ($stripePaymentMethod->customer && $stripePaymentMethod->customer !== $stripeId) {
    //                                 throw new \RuntimeException("El método de pago no pertenece al customer del usuario.");
    //                             }
    //                         } catch (ApiErrorException $e) {
    //                             // Si no existe, continuar de todas formas
    //                             Log::create([
    //                                 'user_id' => $userId,
    //                                 'action' => 'Eliminar método de pago de Stripe (sin BD)',
    //                                 'description' => 'Payment method no encontrado en Stripe antes de detach: ' . $e->getMessage(),
    //                                 'data' => json_encode([
    //                                     'gateway_token' => $gatewayToken,
    //                                     'stripe_error' => $e->getMessage()
    //                                 ])
    //                             ]);
    //                         }

    //                         // Hacer detach según la documentación de Stripe
    //                         // POST /v1/payment_methods/:id/detach
    //                         $detachedPaymentMethod = $stripeClient->paymentMethods->detach($gatewayToken);

    //                         // Log inmediato de la respuesta
    //                         Log::create([
    //                             'user_id' => $userId,
    //                             'action' => 'Eliminar método de pago de Stripe (sin BD) - Respuesta',
    //                             'description' => 'Respuesta inmediata de Stripe después de detach',
    //                             'data' => json_encode([
    //                                 'gateway_token' => $gatewayToken,
    //                                 'stripe_response_raw' => [
    //                                     'id' => $detachedPaymentMethod->id ?? 'NO_ID',
    //                                     'customer' => $detachedPaymentMethod->customer ?? 'NULL',
    //                                     'type' => $detachedPaymentMethod->type ?? null,
    //                                     'object' => $detachedPaymentMethod->object ?? null
    //                                 ]
    //                             ])
    //                         ]);

    //                         // Verificar que se eliminó correctamente
    //                         $deleted = $detachedPaymentMethod->customer === null;

    //                         if (!$deleted) {
    //                             Log::create([
    //                                 'user_id' => $userId,
    //                                 'action' => 'Eliminar método de pago de Stripe (sin BD) - ADVERTENCIA',
    //                                 'description' => 'El payment method fue detachado pero customer NO es null',
    //                                 'data' => json_encode([
    //                                     'gateway_token' => $gatewayToken,
    //                                     'customer_value' => $detachedPaymentMethod->customer,
    //                                     'customer_is_null' => is_null($detachedPaymentMethod->customer)
    //                                 ])
    //                             ]);
    //                         }

    //                         Log::create([
    //                             'user_id' => $userId,
    //                             'action' => 'Eliminar método de pago de Stripe (sin BD)',
    //                             'description' => $deleted ? 'Método de pago eliminado de Stripe (no existía en BD, customer es null)' : 'Método de pago detachado pero customer NO es null - puede que no se haya eliminado correctamente',
    //                             'data' => json_encode([
    //                                 'gateway_token' => $gatewayToken,
    //                                 'deleted' => $deleted,
    //                                 'customer_is_null' => is_null($detachedPaymentMethod->customer),
    //                                 'stripe_response' => [
    //                                     'id' => $detachedPaymentMethod->id ?? null,
    //                                     'customer' => $detachedPaymentMethod->customer ?? null,
    //                                     'type' => $detachedPaymentMethod->type ?? null,
    //                                     'object' => $detachedPaymentMethod->object ?? null
    //                                 ]
    //                             ])
    //                         ]);

    //                         return response()->json([
    //                             'exito' => true,
    //                             'codMensaje' => 1,
    //                             'mensajeUsuario' => 'Método de pago eliminado exitosamente de Stripe',
    //                             'datoAdicional' => null
    //                         ], 200);
    //                     } catch (\Throwable $e) {
    //                         Log::create([
    //                             'user_id' => $userId,
    //                             'action' => 'Eliminar método de pago de Stripe (sin BD)',
    //                             'description' => 'Error al eliminar método de pago de Stripe: ' . $e->getMessage(),
    //                             'data' => json_encode([
    //                                 'gateway_token' => $gatewayToken,
    //                                 'error' => $e->getMessage(),
    //                                 'error_type' => get_class($e),
    //                                 'trace' => $e->getTraceAsString()
    //                             ])
    //                         ]);

    //                         return response()->json([
    //                             'exito' => false,
    //                             'codMensaje' => 0,
    //                             'mensajeUsuario' => 'Error al eliminar método de pago de Stripe: ' . $e->getMessage(),
    //                             'datoAdicional' => null
    //                         ], 200);
    //                     }
    //                 }
    //             }

    //             return response()->json([
    //                 'exito' => false,
    //                 'codMensaje' => 0,
    //                 'mensajeUsuario' => 'Método de pago no encontrado o no tienes permisos para eliminarlo',
    //                 'datoAdicional' => null
    //             ], 200);
    //         }

    //         // Log cuando se encuentra el método de pago
    //         Log::create([
    //             'user_id' => $userId,
    //             'action' => 'Eliminar método de pago - Encontrado en BD',
    //             'description' => 'Método de pago encontrado en la base de datos',
    //             'data' => json_encode([
    //                 'payment_method_id' => $paymentMethod->id,
    //                 'gateway_token' => $paymentMethod->gateway_token,
    //                 'card_brand' => $paymentMethod->card_brand,
    //                 'card_last_four' => $paymentMethod->card_last_four,
    //                 'is_default' => $paymentMethod->is_default,
    //                 'status' => $paymentMethod->status
    //             ])
    //         ]);

    //         // Si era el método predeterminado, marcar otro como predeterminado
    //         if ($paymentMethod->is_default) {
    //             $nextDefault = UserPaymentMethod::where('user_id', Auth::id())
    //                 ->where('id', '!=', $paymentMethod->id)
    //                 ->where('status', 'active')
    //                 ->orderBy('last_used_at', 'desc')
    //                 ->orderBy('created_at', 'desc')
    //                 ->first();

    //             if ($nextDefault) {
    //                 $nextDefault->update(['is_default' => true]);
    //             }
    //         }

    //         // Si tiene gateway_token (ID de Stripe), eliminar también desde Stripe usando Laravel Cashier
    //         $gatewayToken = $paymentMethod->gateway_token;
    //         $user = Auth::user();

    //         // Log inicial para depuración
    //         Log::create([
    //             'user_id' => Auth::id(),
    //             'action' => 'Eliminar método de pago - Inicio',
    //             'description' => 'Iniciando eliminación de método de pago',
    //             'data' => json_encode([
    //                 'payment_method_id' => $paymentMethod->id,
    //                 'gateway_token' => $gatewayToken,
    //                 'has_gateway_token' => !empty($gatewayToken),
    //                 'card_brand' => $paymentMethod->card_brand,
    //                 'card_last_four' => $paymentMethod->card_last_four
    //             ])
    //         ]);

    //         if (!empty($gatewayToken) && $user) {
    //             try {
    //                 // Verificar que el usuario tenga stripe_id configurado
    //                 $stripeId = $user->stripe_id;

    //                 Log::create([
    //                     'user_id' => Auth::id(),
    //                     'action' => 'Eliminar método de pago de Stripe - Verificación',
    //                     'description' => 'Verificando configuración de Stripe antes de eliminar',
    //                     'data' => json_encode([
    //                         'payment_method_id' => $paymentMethod->id,
    //                         'gateway_token' => $gatewayToken,
    //                         'user_stripe_id' => $stripeId,
    //                         'has_stripe_id' => !empty($stripeId),
    //                         'stripe_secret_configured' => !empty(config('services.stripe.secret'))
    //                     ])
    //                 ]);

    //                 if (empty($stripeId)) {
    //                     throw new \RuntimeException('El usuario no tiene stripe_id configurado. No se puede eliminar el método de pago de Stripe.');
    //                 }

    //                 // Usar StripeClient directamente para mayor control
    //                 $stripeClient = $this->makeStripeClient();

    //                 // Verificar que el payment method existe y pertenece al customer antes de detach
    //                 try {
    //                     $stripePaymentMethod = $stripeClient->paymentMethods->retrieve($gatewayToken);

    //                     Log::create([
    //                         'user_id' => Auth::id(),
    //                         'action' => 'Eliminar método de pago de Stripe - Verificación',
    //                         'description' => 'Payment method encontrado en Stripe antes de detach',
    //                         'data' => json_encode([
    //                             'payment_method_id' => $paymentMethod->id,
    //                             'gateway_token' => $gatewayToken,
    //                             'stripe_customer_id' => $stripeId,
    //                             'payment_method_customer' => $stripePaymentMethod->customer ?? null,
    //                             'belongs_to_customer' => ($stripePaymentMethod->customer ?? null) === $stripeId
    //                         ])
    //                     ]);

    //                     // Verificar que el payment method pertenece al customer
    //                     if ($stripePaymentMethod->customer && $stripePaymentMethod->customer !== $stripeId) {
    //                         throw new \RuntimeException("El método de pago no pertenece al customer del usuario. Customer del PM: {$stripePaymentMethod->customer}, Customer del usuario: {$stripeId}");
    //                     }
    //                 } catch (ApiErrorException $e) {
    //                     // Si el payment method no existe, loguear y continuar
    //                     Log::create([
    //                         'user_id' => Auth::id(),
    //                         'action' => 'Eliminar método de pago de Stripe - Verificación',
    //                         'description' => 'Payment method no encontrado en Stripe: ' . $e->getMessage(),
    //                         'data' => json_encode([
    //                             'payment_method_id' => $paymentMethod->id,
    //                             'gateway_token' => $gatewayToken,
    //                             'stripe_error' => $e->getMessage(),
    //                             'stripe_error_code' => $e->getStripeCode() ?? null
    //                         ])
    //                     ]);
    //                     // Continuar con el detach de todas formas, puede que ya esté detachado
    //                 }

    //                 // Intentar eliminar el método de pago de Stripe usando el endpoint detach
    //                 // Según la documentación: POST /v1/payment_methods/:id/detach
    //                 Log::create([
    //                     'user_id' => Auth::id(),
    //                     'action' => 'Eliminar método de pago de Stripe - Intentando',
    //                     'description' => 'Intentando detach payment method de Stripe',
    //                     'data' => json_encode([
    //                         'payment_method_id' => $paymentMethod->id,
    //                         'gateway_token' => $gatewayToken,
    //                         'stripe_customer_id' => $stripeId,
    //                         'endpoint' => '/v1/payment_methods/' . $gatewayToken . '/detach'
    //                     ])
    //                 ]);

    //                 // Llamar al método detach según la documentación de Stripe
    //                 // POST /v1/payment_methods/:id/detach
    //                 $detachedPaymentMethod = $stripeClient->paymentMethods->detach($gatewayToken);

    //                 // Log inmediato de la respuesta de Stripe
    //                 Log::create([
    //                     'user_id' => Auth::id(),
    //                     'action' => 'Eliminar método de pago de Stripe - Respuesta',
    //                     'description' => 'Respuesta inmediata de Stripe después de detach',
    //                     'data' => json_encode([
    //                         'payment_method_id' => $paymentMethod->id,
    //                         'gateway_token' => $gatewayToken,
    //                         'stripe_response_raw' => [
    //                             'id' => $detachedPaymentMethod->id ?? 'NO_ID',
    //                             'customer' => $detachedPaymentMethod->customer ?? 'NULL',
    //                             'type' => $detachedPaymentMethod->type ?? null,
    //                             'object' => $detachedPaymentMethod->object ?? null,
    //                             'livemode' => $detachedPaymentMethod->livemode ?? null,
    //                             'card_brand' => $detachedPaymentMethod->card->brand ?? null,
    //                             'card_last4' => $detachedPaymentMethod->card->last4 ?? null
    //                         ]
    //                     ])
    //                 ]);

    //                 // Verificar que se eliminó correctamente (customer debe ser null después de detach)
    //                 // Según la documentación: https://docs.stripe.com/api/payment_methods/detach
    //                 // Después del detach, el campo customer será null
    //                 $deleted = $detachedPaymentMethod->customer === null;

    //                 if (!$deleted) {
    //                     // Si customer no es null, algo salió mal
    //                     Log::create([
    //                         'user_id' => Auth::id(),
    //                         'action' => 'Eliminar método de pago de Stripe - ADVERTENCIA',
    //                         'description' => 'El payment method fue detachado pero customer NO es null. Puede que no se haya eliminado correctamente.',
    //                         'data' => json_encode([
    //                             'payment_method_id' => $paymentMethod->id,
    //                             'gateway_token' => $gatewayToken,
    //                             'customer_value' => $detachedPaymentMethod->customer,
    //                             'customer_is_null' => is_null($detachedPaymentMethod->customer)
    //                         ])
    //                     ]);
    //                 }

    //                 // Registrar respuesta completa de Stripe
    //                 Log::create([
    //                     'user_id' => Auth::id(),
    //                     'action' => 'Eliminar método de pago de Stripe',
    //                     'description' => $deleted ? 'Método de pago eliminado exitosamente de Stripe (customer es null)' : 'Método de pago detachado pero customer NO es null - puede que no se haya eliminado correctamente',
    //                     'data' => json_encode([
    //                         'payment_method_id' => $paymentMethod->id,
    //                         'gateway_token' => $gatewayToken,
    //                         'card_brand' => $paymentMethod->card_brand,
    //                         'card_last_four' => $paymentMethod->card_last_four,
    //                         'deleted' => $deleted,
    //                         'customer_is_null' => is_null($detachedPaymentMethod->customer),
    //                         'stripe_response' => [
    //                             'id' => $detachedPaymentMethod->id ?? null,
    //                             'customer' => $detachedPaymentMethod->customer ?? null,
    //                             'type' => $detachedPaymentMethod->type ?? null,
    //                             'object' => $detachedPaymentMethod->object ?? null,
    //                             'livemode' => $detachedPaymentMethod->livemode ?? null
    //                         ]
    //                     ])
    //                 ]);
    //             } catch (ApiErrorException $e) {
    //                 // Si el método ya no existe en Stripe o hay otro error, solo loguear
    //                 // pero continuar con la eliminación en BD
    //                 Log::create([
    //                     'user_id' => Auth::id(),
    //                     'action' => 'Eliminar método de pago de Stripe',
    //                     'description' => 'Error de Stripe API al eliminar método de pago: ' . $e->getMessage(),
    //                     'data' => json_encode([
    //                         'payment_method_id' => $paymentMethod->id,
    //                         'gateway_token' => $gatewayToken,
    //                         'stripe_error' => $e->getMessage(),
    //                         'stripe_error_code' => $e->getStripeCode() ?? null,
    //                         'stripe_error_type' => $e->getStripeCode() ?? null,
    //                         'error_type' => 'ApiErrorException',
    //                         'http_status' => $e->getHttpStatus() ?? null
    //                     ])
    //                 ]);
    //             } catch (\Throwable $e) {
    //                 Log::create([
    //                     'user_id' => Auth::id(),
    //                     'action' => 'Eliminar método de pago de Stripe',
    //                     'description' => 'Error inesperado al eliminar método de pago de Stripe: ' . $e->getMessage(),
    //                     'data' => json_encode([
    //                         'payment_method_id' => $paymentMethod->id,
    //                         'gateway_token' => $gatewayToken,
    //                         'error' => $e->getMessage(),
    //                         'error_type' => get_class($e),
    //                         'file' => $e->getFile(),
    //                         'line' => $e->getLine(),
    //                         'trace' => $e->getTraceAsString()
    //                     ])
    //                 ]);
    //             }
    //         } else {
    //             // Log cuando no hay gateway_token o usuario
    //             Log::create([
    //                 'user_id' => Auth::id(),
    //                 'action' => 'Eliminar método de pago de Stripe',
    //                 'description' => !empty($gatewayToken) ? 'No se puede eliminar de Stripe: usuario no encontrado' : 'No se puede eliminar de Stripe: método de pago no tiene gateway_token',
    //                 'data' => json_encode([
    //                     'payment_method_id' => $paymentMethod->id,
    //                     'gateway_token' => $gatewayToken,
    //                     'has_user' => !empty($user),
    //                     'card_brand' => $paymentMethod->card_brand,
    //                     'card_last_four' => $paymentMethod->card_last_four
    //                 ])
    //             ]);
    //         }

    //         // Guardar datos antes de eliminar para el log
    //         $paymentMethodId = $paymentMethod->id;
    //         $cardBrand = $paymentMethod->card_brand;
    //         $cardLastFour = $paymentMethod->card_last_four;

    //         // Eliminación física del método de pago
    //         Log::create([
    //             'user_id' => $userId,
    //             'action' => 'Eliminar método de pago - Eliminando de BD',
    //             'description' => 'Eliminando método de pago físicamente de la base de datos',
    //             'data' => json_encode([
    //                 'payment_method_id' => $paymentMethodId,
    //                 'gateway_token' => $gatewayToken,
    //                 'card_brand' => $cardBrand,
    //                 'card_last_four' => $cardLastFour
    //             ])
    //         ]);

    //         $paymentMethod->delete();

    //         Log::create([
    //             'user_id' => $userId,
    //             'action' => 'Eliminar método de pago - ÉXITO',
    //             'description' => 'Método de pago eliminado exitosamente de la base de datos',
    //             'data' => json_encode([
    //                 'payment_method_id' => $paymentMethodId,
    //                 'gateway_token' => $gatewayToken,
    //                 'card_brand' => $cardBrand,
    //                 'card_last_four' => $cardLastFour
    //             ])
    //         ]);

    //         return response()->json([
    //             'exito' => true,
    //             'codMensaje' => 1,
    //             'mensajeUsuario' => 'Método de pago eliminado exitosamente',
    //             'datoAdicional' => null
    //         ], 200);
    //     } catch (\Illuminate\Validation\ValidationException $e) {
    //         Log::create([
    //             'user_id' => Auth::id(),
    //             'action' => 'Eliminar un método de pago',
    //             'description' => 'Datos de entrada inválidos',
    //             'data' => $e->getMessage(),
    //         ]);

    //         return response()->json([
    //             'exito' => false,
    //             'codMensaje' => 0,
    //             'mensajeUsuario' => 'Datos de entrada inválidos',
    //             'datoAdicional' => $e->errors()
    //         ], 200);
    //     } catch (\Throwable $e) {
    //         Log::create([
    //             'user_id' => Auth::id(),
    //             'action' => 'Eliminar un método de pago',
    //             'description' => 'Error al eliminar el método de pago',
    //             'data' => $e->getMessage(),
    //         ]);

    //         return response()->json([
    //             'exito' => false,
    //             'codMensaje' => 0,
    //             'mensajeUsuario' => 'Error al eliminar el método de pago',
    //             'datoAdicional' => $e->getMessage()
    //         ], 200);
    //     }
    // }

    /**
     * Obtiene los métodos de pago directamente desde Stripe usando Laravel Cashier
     *
     * @param \App\Models\User $user
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    private function getPaymentMethodsFromStripe($user, Request $request)
    {
        try {
            // Verificar que el usuario tenga un customer_id en Stripe
            if (!$user->stripe_id) {
                return response()->json([
                    'exito' => true,
                    'codMensaje' => 1,
                    'mensajeUsuario' => 'Métodos de pago obtenidos exitosamente',
                    'datoAdicional' => [
                        'payment_methods' => [],
                        'total_count' => 0
                    ]
                ], 200);
            }

            // Obtener métodos de pago directamente desde Stripe usando Cashier
            $stripeCustomer = $user->asStripeCustomer();
            $stripePaymentMethods = $stripeCustomer->paymentMethods->all([
                'type' => 'card',
            ]);

            // Transformar los métodos de Stripe al formato esperado
            $paymentMethods = [];
            foreach ($stripePaymentMethods->data as $stripeMethod) {
                if ($stripeMethod->type === 'card' && isset($stripeMethod->card)) {
                    $card = $stripeMethod->card;
                    $billingDetails = $stripeMethod->billing_details ?? null;

                    $paymentMethods[] = [
                        'id' => $stripeMethod->id,
                        'payment_type' => 'credit_card',
                        'card_brand' => $card->brand ?? null,
                        'card_last_four' => $card->last4 ?? null,
                        'card_holder_name' => $billingDetails->name ?? null,
                        'expiry_date' => ($card->exp_month && $card->exp_year)
                            ? sprintf('%02d/%d', $card->exp_month, $card->exp_year)
                            : null,
                        'card_expiry_month' => $card->exp_month ?? null,
                        'card_expiry_year' => $card->exp_year ?? null,
                        'is_expired' => false, // Stripe no devuelve esto directamente, se calcula
                        'is_default' => false, // Se determina comparando con el default_payment_method del customer
                        'is_saved_for_future' => true,
                        'status' => 'active',
                        'verification_status' => 'verified',
                        'billing_address' => $this->formatStripeAddress($billingDetails->address ?? null),
                        'gateway_token' => $stripeMethod->id,
                        'gateway_customer_id' => $stripeCustomer->id,
                        'created_at' => date('Y-m-d\TH:i:s.000000\Z', $stripeMethod->created),
                        'updated_at' => date('Y-m-d\TH:i:s.000000\Z', $stripeMethod->created),
                    ];
                }
            }

            // Marcar el método predeterminado si existe
            if ($stripeCustomer->invoice_settings->default_payment_method) {
                $defaultId = $stripeCustomer->invoice_settings->default_payment_method;
                foreach ($paymentMethods as &$method) {
                    if ($method['gateway_token'] === $defaultId) {
                        $method['is_default'] = true;
                        break;
                    }
                }
            }

            // Ordenar: primero el predeterminado
            usort($paymentMethods, function ($a, $b) {
                if ($a['is_default'] && !$b['is_default']) return -1;
                if (!$a['is_default'] && $b['is_default']) return 1;
                return 0;
            });

            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Métodos de pago obtenidos exitosamente desde Stripe',
                'datoAdicional' => [
                    'payment_methods' => $paymentMethods,
                    'total_count' => count($paymentMethods)
                ]
            ], 200);

        } catch (\Throwable $e) {
            Log::create([
                'user_id' => $user->id,
                'action' => 'Obtener métodos de pago desde Stripe',
                'description' => 'Error al obtener métodos de pago desde Stripe',
                'data' => $e->getMessage(),
            ]);

            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error al obtener métodos de pago desde Stripe',
                'datoAdicional' => $e->getMessage()
            ], 200);
        }
    }

    /**
     * Formatea la dirección de facturación de Stripe al formato esperado
     *
     * @param mixed $address
     * @return array|null
     */
    private function formatStripeAddress($address): ?array
    {
        if (!$address) {
            return null;
        }

        $formatted = array_filter([
            'line1' => $address->line1 ?? null,
            'line2' => $address->line2 ?? null,
            'city' => $address->city ?? null,
            'state' => $address->state ?? null,
            'postal_code' => $address->postal_code ?? null,
            'country' => $address->country ?? null,
        ], function ($value) {
            return !is_null($value);
        });

        return $formatted ?: null;
    }

    /**
     * Crea una instancia del cliente de Stripe
     * @return StripeClient
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
