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

/**
 * @tags Métodos de Pago
 */
final class UserPayMethodController extends Controller
{
    /**
     * Lista todos los métodos de pago del usuario autenticado
     */
    public function index(Request $request): JsonResponse
    {
        try {
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
                    Rule::in(['visa', 'mastercard', 'amex', 'dinners'])
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
     */
    public function destroy(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'id' => 'required|integer|exists:user_payment_methods,id'
            ]);

            $id = $request->integer('id');

            // Verificar que el método de pago pertenece al usuario autenticado
            $paymentMethod = UserPaymentMethod::where('user_id', Auth::id())
                ->where('id', $id)
                ->first();

            if (!$paymentMethod) {
                return response()->json([
                    'exito' => false,
                    'codMensaje' => 0,
                    'mensajeUsuario' => 'Método de pago no encontrado o no tienes permisos para eliminarlo',
                    'datoAdicional' => null
                ], 200);
            }

            // Si era el método predeterminado, marcar otro como predeterminado
            if ($paymentMethod->is_default) {
                $nextDefault = UserPaymentMethod::where('user_id', Auth::id())
                    ->where('id', '!=', $id)
                    ->where('status', 'active')
                    ->orderBy('last_used_at', 'desc')
                    ->orderBy('created_at', 'desc')
                    ->first();

                if ($nextDefault) {
                    $nextDefault->update(['is_default' => true]);
                }
            }

            $paymentMethod->delete();

            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Método de pago eliminado exitosamente',
                'datoAdicional' => null
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::create([
                'user_id' => Auth::id(),
                'action' => 'Eliminar un método de pago',
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
                'action' => 'Eliminar un método de pago',
                'description' => 'Error al eliminar el método de pago',
                'data' => $e->getMessage(),
            ]);

            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error al eliminar el método de pago',
                'datoAdicional' => $e->getMessage()
            ], 200);
        }
    }
}
