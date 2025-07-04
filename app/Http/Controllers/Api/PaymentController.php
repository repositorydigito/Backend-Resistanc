<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentResource;
use App\Models\UserPaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * @tags Métodos de Pago
 */
class PaymentController extends Controller
{
    /**
     * Lista los métodos de pago del usuario autenticado
     *
     * @summary Listar métodos de pago del usuario
     * @operationId getPaymentMethods
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @response 200 {
     *   "exito": true,
     *   "codMensaje": 1,
     *   "mensajeUsuario": "Métodos de pago obtenidos exitosamente",
     *   "datoAdicional": [
     *     {
     *       "id": 1,
     *       "payment_type": "credit_card",
     *       "provider": "visa",
     *       "card_last_four": "****1234",
     *       "card_brand": "Visa",
     *       "card_holder_name": "Juan Pérez",
     *       "is_default": true,
     *       "status": "active",
     *       "display_name": "Visa ****1234",
     *       "can_use": true
     *     }
     *   ]
     * }
     */
    public function index(Request $request)
    {
        try {
            $userId = auth()->id();

            // Validar parámetros de paginación
            $request->validate([
                'per_page' => 'nullable|integer|min:1|max:50',
                'page' => 'nullable|integer|min:1'
            ]);

            $query = UserPaymentMethod::where('user_id', $userId)
                ->where('status', 'active')
                // ->orderBy('is_default', 'desc')
                ->orderBy('created_at', 'desc');

            // Aplicar paginación si se especifican parámetros
            if ($request->has('per_page')) {
                $paymentMethods = $query->paginate(
                    perPage: $request->integer('per_page', 15),
                    page: $request->integer('page', 1)
                );

                return response()->json([
                    'exito' => true,
                    'codMensaje' => 1,
                    'mensajeUsuario' => 'Métodos de pago obtenidos exitosamente',
                    'datoAdicional' => PaymentResource::collection($paymentMethods),
                    'meta' => [
                        'current_page' => $paymentMethods->currentPage(),
                        'per_page' => $paymentMethods->perPage(),
                        'total' => $paymentMethods->total(),
                        'last_page' => $paymentMethods->lastPage(),
                        'from' => $paymentMethods->firstItem(),
                        'to' => $paymentMethods->lastItem()
                    ],
                    'links' => [
                        'first' => $paymentMethods->url(1),
                        'last' => $paymentMethods->url($paymentMethods->lastPage()),
                        'prev' => $paymentMethods->previousPageUrl(),
                        'next' => $paymentMethods->nextPageUrl()
                    ]
                ], 200);
            } else {
                // Sin paginación - devolver todos los resultados
                $paymentMethods = $query->get();

                return response()->json([
                    'exito' => true,
                    'codMensaje' => 1,
                    'mensajeUsuario' => 'Métodos de pago obtenidos exitosamente',
                    'datoAdicional' => PaymentResource::collection($paymentMethods)
                ], 200);
            }
        } catch (\Exception $e) {
            Log::error('Error al obtener métodos de pago', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error al obtener métodos de pago',
                'datoAdicional' => []
            ], 200);
        }
    }

    /**
     * Crea un nuevo método de pago para el usuario autenticado
     *
     * @summary Crear método de pago
     * @operationId createPaymentMethod
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @bodyParam payment_type string required Tipo de método de pago (credit_card, debit_card, bank_transfer, digital_wallet, crypto). Example: credit_card
     * @bodyParam provider string Proveedor del método de pago. Example: visa
     * @bodyParam card_last_four string Últimos 4 dígitos de la tarjeta. Example: 1234
     * @bodyParam card_brand string Marca de la tarjeta. Example: Visa
     * @bodyParam card_holder_name string Nombre del titular. Example: Juan Pérez
     * @bodyParam card_expiry_month integer Mes de expiración (1-12). Example: 12
     * @bodyParam card_expiry_year integer Año de expiración. Example: 2025
     * @bodyParam bank_name string Nombre del banco. Example: BCP
     * @bodyParam account_number_masked string Número de cuenta enmascarado. Example: ****1234
     * @bodyParam is_default boolean Marcar como método predeterminado. Example: false
     * @bodyParam billing_address object Dirección de facturación. Example: {"street": "Av. Principal 123"}
     *
     * @response 200 {
     *   "exito": true,
     *   "codMensaje": 1,
     *   "mensajeUsuario": "Método de pago creado exitosamente",
     *   "datoAdicional": {
     *     "id": 2,
     *     "payment_type": "credit_card",
     *     "provider": "visa",
     *     "card_last_four": "****5678",
     *     "card_brand": "Visa",
     *     "display_name": "Visa ****5678",
     *     "is_default": false,
     *     "status": "active"
     *   }
     * }
     */
    public function store(Request $request)
    {
        try {
            // Debug: Log de los datos recibidos
            Log::info('Datos recibidos en store payment method', [
                'all_data' => $request->all(),
                'content_type' => $request->header('Content-Type'),
                'payment_type' => $request->input('payment_type'),
                'provider' => $request->input('provider')
            ]);

            $validator = Validator::make($request->all(), [
                'payment_type' => 'required|string|in:credit_card,debit_card,bank_transfer,digital_wallet,crypto',
                'provider' => 'nullable|string|in:visa,mastercard,amex,bcp,interbank,scotiabank,bbva,yape,plin,paypal',
                'card_last_four' => 'nullable|string|size:4',
                'card_brand' => 'nullable|string|max:20',
                'card_holder_name' => 'nullable|string|max:255',
                'card_expiry_month' => 'nullable|integer|between:1,12',
                'card_expiry_year' => 'nullable|integer|min:2024',
                'bank_name' => 'nullable|string|max:100',
                'account_number_masked' => 'nullable|string|max:50',
                'is_default' => 'boolean',
                'billing_address' => 'nullable|array',
                'metadata' => 'nullable|array'
            ]);
            //  return response()->json([
            //         'mensaje' => 'Petición recibida',
            //         'headers' => $request->headers->all(),
            //         'content_type' => $request->header('Content-Type'),
            //         'body_raw' => file_get_contents('php://input'),
            //         'body_parsed' => $request->all(),
            //     ], 200);
            if ($validator->fails()) {
                return response()->json([
                    'exito' => false,
                    'codMensaje' => 0,
                    'mensajeUsuario' => 'Datos de entrada inválidos',
                    'datoAdicional' => $validator->errors()
                ], 200);
            }

            $userId = auth()->id();
            $data = $request->all();
            $data['user_id'] = $userId;
            $data['status'] = 'active';
            $data['verification_status'] = 'pending';

            // Si se marca como predeterminado, desmarcar los demás
            if ($data['is_default'] ?? false) {
                UserPaymentMethod::where('user_id', $userId)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }

            $paymentMethod = UserPaymentMethod::create($data);

            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Método de pago creado exitosamente',
                'datoAdicional' => new PaymentResource($paymentMethod)
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error al crear método de pago', [
                'user_id' => auth()->id(),
                'request_data' => $request->all(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error al crear método de pago',
                'datoAdicional' => $e->getMessage()
            ], 200);
        }
    }

    /**
     * Obtiene un método de pago específico del usuario autenticado
     *
     * @summary Obtener método de pago específico
     * @operationId getPaymentMethod
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     *
     * @response 200 {
     *   "exito": true,
     *   "codMensaje": 1,
     *   "mensajeUsuario": "Método de pago obtenido exitosamente",
     *   "datoAdicional": {
     *     "id": 1,
     *     "payment_type": "credit_card",
     *     "provider": "visa",
     *     "card_last_four": "****1234",
     *     "card_brand": "Visa",
     *     "display_name": "Visa ****1234",
     *     "is_default": true,
     *     "status": "active"
     *   }
     * }
     */
    public function show($id)
    {
        try {
            $userId = auth()->id();

            $paymentMethod = UserPaymentMethod::where('id', $id)
                ->where('user_id', $userId)
                ->where('status', 'active')
                ->first();

            if (!$paymentMethod) {
                return response()->json([
                    'exito' => false,
                    'codMensaje' => 0,
                    'mensajeUsuario' => 'Método de pago no encontrado',
                    'datoAdicional' => []
                ], 200);
            }

            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Método de pago obtenido exitosamente',
                'datoAdicional' => new PaymentResource($paymentMethod)
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error al obtener método de pago', [
                'user_id' => auth()->id(),
                'payment_method_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error al obtener método de pago',
                'datoAdicional' => []
            ], 200);
        }
    }

    /**
     * Actualiza un método de pago específico del usuario autenticado
     *
     * @summary Actualizar método de pago
     * @operationId updatePaymentMethod
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     *
     * @bodyParam payment_type string Tipo de método de pago. Example: credit_card
     * @bodyParam provider string Proveedor del método de pago. Example: visa
     * @bodyParam card_holder_name string Nombre del titular. Example: Juan Pérez
     * @bodyParam is_default boolean Marcar como método predeterminado. Example: true
     * @bodyParam billing_address object Dirección de facturación. Example: {"street": "Av. Principal 123"}
     *
     * @response 200 {
     *   "exito": true,
     *   "codMensaje": 1,
     *   "mensajeUsuario": "Método de pago actualizado exitosamente",
     *   "datoAdicional": {
     *     "id": 1,
     *     "payment_type": "credit_card",
     *     "provider": "visa",
     *     "card_last_four": "****1234",
     *     "display_name": "Visa ****1234",
     *     "is_default": true,
     *     "status": "active"
     *   }
     * }
     */
    public function update(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'payment_type' => 'sometimes|string|in:credit_card,debit_card,bank_transfer,digital_wallet,crypto',
                'provider' => 'sometimes|string|in:visa,mastercard,amex,bcp,interbank,scotiabank,bbva,yape,plin,paypal',
                'card_holder_name' => 'sometimes|string|max:255',
                'is_default' => 'sometimes|boolean',
                'billing_address' => 'sometimes|array',
                'metadata' => 'sometimes|array'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'exito' => false,
                    'codMensaje' => 0,
                    'mensajeUsuario' => 'Datos de entrada inválidos',
                    'datoAdicional' => $validator->errors()
                ], 200);
            }

            $userId = auth()->id();

            $paymentMethod = UserPaymentMethod::where('id', $id)
                ->where('user_id', $userId)
                ->where('status', 'active')
                ->first();

            if (!$paymentMethod) {
                return response()->json([
                    'exito' => false,
                    'codMensaje' => 0,
                    'mensajeUsuario' => 'Método de pago no encontrado',
                    'datoAdicional' => []
                ], 200);
            }

            $data = $request->validated();

            // Si se marca como predeterminado, desmarcar los demás
            if (isset($data['is_default']) && $data['is_default']) {
                UserPaymentMethod::where('user_id', $userId)
                    ->where('id', '!=', $id)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }

            $paymentMethod->update($data);

            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Método de pago actualizado exitosamente',
                'datoAdicional' => new PaymentResource($paymentMethod->fresh())
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error al actualizar método de pago', [
                'user_id' => auth()->id(),
                'payment_method_id' => $id,
                'request_data' => $request->all(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error al actualizar método de pago',
                'datoAdicional' => []
            ], 200);
        }
    }

    /**
     * Desactiva (elimina lógicamente) un método de pago específico del usuario autenticado.
     *
     * @summary Eliminar (desactivar) método de pago
     * @description Cambia el estatus del método de pago a 'inactive' en vez de eliminarlo físicamente.
     * @operationId deletePaymentMethod
     * @param int $id ID del método de pago a eliminar
     * @return \Illuminate\Http\JsonResponse
     *
     * @response 200 {
     *   "exito": true,
     *   "codMensaje": 1,
     *   "mensajeUsuario": "Método de pago eliminado exitosamente",
     *   "datoAdicional": {
     *     "id": 1,
     *     "status": "inactive"
     *   }
     * }
     * @response 200 {
     *   "exito": false,
     *   "codMensaje": 0,
     *   "mensajeUsuario": "Método de pago no encontrado",
     *   "datoAdicional": []
     * }
     */
    public function destroy($id)
    {

        try {
            $userId = auth()->id();

            $paymentMethod = UserPaymentMethod::where('id', $id)
                ->where('user_id', $userId)
                ->where('status', 'active')
                ->first();

            if (!$paymentMethod) {
                return response()->json([
                    'exito' => false,
                    'codMensaje' => 0,
                    'mensajeUsuario' => 'Método de pago no encontrado',
                    'datoAdicional' => []
                ], 200);
            }

            // Desactivar el método de pago (soft delete)
            $paymentMethod->update(['status' => 'inactive']);

            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Método de pago eliminado exitosamente',
                'datoAdicional' => [
                    'id' => $paymentMethod->id,
                    'status' => 'inactive'
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error al eliminar método de pago', [
                'user_id' => auth()->id(),
                'payment_method_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => $e->getMessage(),
                'datoAdicional' => []
            ], 200);
        }
    }


    /**
     * Selecciona un método de pago como predeterminado para el usuario autenticado
     *
     * @summary Seleccionar método de pago predeterminado
     * @operationId selectDefaultPaymentMethod
     *
     * @param int $id ID del método de pago a seleccionar
     * @return \Illuminate\Http\JsonResponse
     *
     * @response 200 {
     *   "exito": true,
     *   "codMensaje": 1,
     *   "mensajeUsuario": "Método de pago seleccionado exitosamente",
     *   "datoAdicional": {
     *     "id": 1,
     *     "payment_type": "credit_card",
     *     "provider": "visa",
     *     "card_last_four": "****1234",
     *     "display_name": "Visa ****1234",
     *     "is_default": true,
     *     "status": "active"
     *   }
     * }
     * @response 200 {
     *   "exito": false,
     *   "codMensaje": 0,
     *   "mensajeUsuario": "Método de pago no encontrado",
     *   "datoAdicional": []
     * }
     */
    public function selectPayment($id)
    {

        try {
            $userId = auth()->id();

            if ($id == 0) {
                UserPaymentMethod::where('user_id', $userId)
                    ->update(['is_default' => false]);

                return response()->json([
                    'exito' => true,
                    'codMensaje' => 1,
                    'mensajeUsuario' => 'Método de pago deseleccionado exitosamente',
                    'datoAdicional' => null
                ], 200);
            }

            $paymentMethod = UserPaymentMethod::where('id', $id)
                ->where('user_id', $userId)
                ->where('status', 'active')
                ->first();

            if (!$paymentMethod) {
                return response()->json([
                    'exito' => false,
                    'codMensaje' => 0,
                    'mensajeUsuario' => 'Método de pago no encontrado',
                    'datoAdicional' => []
                ], 200);
            }

            // Marcar este método como predeterminado



            UserPaymentMethod::where('user_id', $userId)
                ->update(['is_default' => false]);

            $paymentMethod->update(['is_default' => true]);

            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Método de pago seleccionado exitosamente',
                'datoAdicional' => new PaymentResource($paymentMethod)
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error al seleccionar método de pago', [
                'user_id' => auth()->id(),
                'payment_method_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => $e->getMessage(),
                'datoAdicional' => []
            ], 200);
        }
    }
}
