<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserPaymentMethodResource;
use App\Models\UserPaymentMethod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
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
     *
     * Obtiene una lista de métodos de pago activos del usuario autenticado ordenados por método por defecto primero.
     * **Requiere autenticación:** Incluye el token Bearer en el header Authorization.
     *
     * **Ruta:** GET /api/me/payment-methods
     *
     * @summary Listar métodos de pago del usuario autenticado
     * @operationId getMyPaymentMethodsList
     */
    public function index(Request $request): AnonymousResourceCollection
    {
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

        $paymentMethods = $query->paginate(
            perPage: min($request->integer('per_page', 10), 50),
            page: $request->integer('page', 1)
        );

        return UserPaymentMethodResource::collection($paymentMethods);
    }

    /**
     * Crear un nuevo método de pago
     *
     * Crea un nuevo método de pago para el usuario autenticado. Si se marca como predeterminado,
     * automáticamente se quitará la marca de predeterminado de otros métodos del usuario.
     * **Requiere autenticación:** Incluye el token Bearer en el header Authorization.
     *
     * **Ruta:** POST /api/me/payment-methods
     *
     * @summary Crear método de pago
     * @operationId createMyPaymentMethod
     *
     * @response 201 {
     *   "success": true,
     *   "message": "Método de pago creado exitosamente",
     *   "data": {
     *     "id": 1,
     *     "payment_type": "credit_card",
     *     "card_brand": "visa",
     *     "card_last_four": "1234",
     *     "card_holder_name": "Juan Pérez",
     *     "expiry_date": "12/2026",
     *     "is_default": false,
     *     "status": "active",
     *     "created_at": "2024-01-15T10:30:00.000Z"
     *   }
     * }
     *
     * @response 422 {
     *   "success": false,
     *   "message": "Error de validación",
     *   "errors": {
     *     "card_last_four": ["El campo últimos 4 dígitos es obligatorio."]
     *   }
     * }
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
            'success' => true,
            'message' => 'Método de pago creado exitosamente',
            'data' => new UserPaymentMethodResource($paymentMethod)
        ], 201);
    }

    /**
     * Mostrar un método de pago específico
     *
     * Obtiene los detalles de un método de pago específico que pertenece al usuario autenticado.
     * Solo se pueden ver métodos de pago propios del usuario.
     * **Requiere autenticación:** Incluye el token Bearer en el header Authorization.
     *
     * **Ruta:** GET /api/me/payment-methods/{id}
     *
     * @summary Mostrar método de pago propio
     * @operationId getMyPaymentMethod
     *
     * @urlParam id integer required ID del método de pago. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "id": 1,
     *     "payment_type": "credit_card",
     *     "card_brand": "visa",
     *     "card_last_four": "1234",
     *     "card_holder_name": "Juan Pérez",
     *     "expiry_date": "12/2026",
     *     "is_expired": false,
     *     "is_default": true,
     *     "status": "active",
     *     "created_at": "2024-01-15T10:30:00.000Z"
     *   }
     * }
     *
     * @response 404 {
     *   "success": false,
     *   "message": "Método de pago no encontrado o no tienes permisos para verlo"
     * }
     */
    public function show(int $id): JsonResponse
    {
        $paymentMethod = UserPaymentMethod::where('user_id', Auth::id())
            ->where('id', $id)
            ->first();

        if (!$paymentMethod) {
            return response()->json([
                'success' => false,
                'message' => 'Método de pago no encontrado o no tienes permisos para verlo'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new UserPaymentMethodResource($paymentMethod)
        ]);
    }

    /**
     * Actualizar un método de pago
     *
     * Actualiza los detalles de un método de pago específico que pertenece al usuario autenticado.
     * Solo se pueden editar métodos de pago propios del usuario.
     * **Requiere autenticación:** Incluye el token Bearer en el header Authorization.
     *
     * **Ruta:** PUT/PATCH /api/me/payment-methods/{id}
     *
     * @summary Actualizar método de pago propio
     * @operationId updateMyPaymentMethod
     *
     * @urlParam id integer required ID del método de pago. Example: 1
     *
     * @bodyParam card_holder_name string Nombre del titular. Example: Juan Pérez Actualizado
     * @bodyParam card_expiry_month integer Mes de expiración (1-12). Example: 6
     * @bodyParam card_expiry_year integer Año de expiración. Example: 2027
     * @bodyParam billing_address string Dirección de facturación. Example: Nueva dirección 456
     * @bodyParam is_default boolean Marcar como método predeterminado. Example: true
     * @bodyParam bank_name string Nombre del banco. Example: BBVA
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Método de pago actualizado exitosamente",
     *   "data": {
     *     "id": 1,
     *     "payment_type": "credit_card",
     *     "card_brand": "visa",
     *     "card_last_four": "1234",
     *     "card_holder_name": "Juan Pérez Actualizado",
     *     "expiry_date": "06/2027",
     *     "is_default": true,
     *     "status": "active",
     *     "updated_at": "2024-01-15T11:30:00.000Z"
     *   }
     * }
     *
     * @response 404 {
     *   "success": false,
     *   "message": "Método de pago no encontrado o no tienes permisos para editarlo"
     * }
     *
     * @response 422 {
     *   "success": false,
     *   "message": "Error de validación",
     *   "errors": {
     *     "card_expiry_year": ["El año de expiración debe ser mayor al año actual."]
     *   }
     * }
     */
    public function update(Request $request, int $id): JsonResponse
    {
        // Verificar que el método de pago pertenece al usuario autenticado
        $paymentMethod = UserPaymentMethod::where('user_id', Auth::id())
            ->where('id', $id)
            ->first();

        if (!$paymentMethod) {
            return response()->json([
                'success' => false,
                'message' => 'Método de pago no encontrado o no tienes permisos para editarlo'
            ], 404);
        }

        $validated = $request->validate([
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

        // Si se marca como predeterminado, quitar de otros métodos del mismo usuario
        if (($validated['is_default'] ?? false) && !$paymentMethod->is_default) {
            UserPaymentMethod::where('user_id', Auth::id())
                ->where('id', '!=', $id)
                ->update(['is_default' => false]);
        }

        $paymentMethod->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Método de pago actualizado exitosamente',
            'data' => new UserPaymentMethodResource($paymentMethod->fresh())
        ]);
    }

    /**
     * Eliminar un método de pago
     *
     * Elimina un método de pago específico que pertenece al usuario autenticado.
     * Si era el método predeterminado, automáticamente se marcará otro como predeterminado.
     * **Requiere autenticación:** Incluye el token Bearer en el header Authorization.
     *
     * **Ruta:** DELETE /api/me/payment-methods/{id}
     *
     * @summary Eliminar método de pago propio
     * @operationId deleteMyPaymentMethod
     *
     * @urlParam id integer required ID del método de pago. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Método de pago eliminado exitosamente"
     * }
     *
     * @response 404 {
     *   "success": false,
     *   "message": "Método de pago no encontrado o no tienes permisos para eliminarlo"
     * }
     */
    public function destroy(int $id): JsonResponse
    {
        // Verificar que el método de pago pertenece al usuario autenticado
        $paymentMethod = UserPaymentMethod::where('user_id', Auth::id())
            ->where('id', $id)
            ->first();

        if (!$paymentMethod) {
            return response()->json([
                'success' => false,
                'message' => 'Método de pago no encontrado o no tienes permisos para eliminarlo'
            ], 404);
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
            'success' => true,
            'message' => 'Método de pago eliminado exitosamente'
        ]);
    }
}
