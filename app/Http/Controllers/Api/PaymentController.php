<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentResource;
use App\Models\Log;
use App\Models\UserPaymentMethod;
use DragonCode\Contracts\Cashier\Auth\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth as FacadesAuth;
use Illuminate\Support\Facades\Validator;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

use Laravel\Cashier\PaymentMethod;

/**
 * @tags Métodos de Pago
 */
class PaymentController extends Controller
{
    /**
     * Lista los métodos de pago del usuario autenticado
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();

            // Obtener métodos de pago directamente desde Stripe
            return $this->getPaymentMethodsFromStripe($user, $request);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::create([
                'user_id' => FacadesAuth::id(),
                'action' => 'Lista los métodos de pago del usuario autenticado',
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
                'user_id' => FacadesAuth::id(),
                'action' => 'Lista los métodos de pago del usuario autenticado',
                'description' => 'Error al obtener métodos de pago',
                'data' => $e->getMessage(),
            ]);

            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error al obtener métodos de pago',
                'datoAdicional' => $e->getMessage()
            ], 200);
        }
    }

    /**
     * Agrega un nuevo método de pago directamente a Stripe (sin guardar en BD)
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'payment_method_id' => 'required|string|max:255',
                'is_default' => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                Log::create([
                    'user_id' => FacadesAuth::id(),
                    'action' => 'Agrega un nuevo método de pago a Stripe',
                    'description' => 'Datos de entrada inválidos',
                    'data' => $validator->errors()->toJson(),
                ]);

                return response()->json([
                    'exito' => false,
                    'codMensaje' => 0,
                    'mensajeUsuario' => 'Datos de entrada inválidos',
                    'datoAdicional' => $validator->errors()
                ], 200);
            }

            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'exito' => false,
                    'codMensaje' => 0,
                    'mensajeUsuario' => 'Usuario no autenticado',
                    'datoAdicional' => []
                ], 401);
            }

            $paymentMethodId = $request->input('payment_method_id');
            $isDefault = $request->boolean('is_default', false);

            // Verificar que el payment method existe y es válido
            $stripePaymentMethod = $this->retrieveStripePaymentMethod($paymentMethodId);

            if (!$stripePaymentMethod || $stripePaymentMethod->type !== 'card' || empty($stripePaymentMethod->card)) {
                return response()->json([
                    'exito' => false,
                    'codMensaje' => 0,
                    'mensajeUsuario' => 'El método de pago proporcionado no es una tarjeta válida de Stripe',
                    'datoAdicional' => []
                ], 200);
            }

            // Asegurar que el usuario tenga un customer en Stripe
            if (!$user->stripe_id) {
                // Crear el customer en Stripe si no existe
                $user->createAsStripeCustomer();
            }

            // Agregar el método de pago al customer en Stripe usando Laravel Cashier
            $paymentMethod = $user->addPaymentMethod($paymentMethodId);

            // Si se marca como predeterminado, actualizar el método predeterminado en Stripe
            if ($isDefault) {
                $user->updateDefaultPaymentMethod($paymentMethodId);
            }

            // Obtener el método de pago completo desde Stripe
            $stripeMethod = $paymentMethod->asStripePaymentMethod();
            $card = $stripeMethod->card;
            $billingDetails = $stripeMethod->billing_details ?? null;

            // Obtener el método predeterminado actual
            $defaultPaymentMethod = $user->defaultPaymentMethod();
            $isDefaultMethod = $defaultPaymentMethod && $defaultPaymentMethod->id === $paymentMethodId;

            // Formatear la respuesta
            $formattedPaymentMethod = [
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
                'is_expired' => $this->isCardExpired($card->exp_month, $card->exp_year),
                'is_default' => $isDefaultMethod,
                'is_saved_for_future' => true,
                'status' => 'active',
                'verification_status' => 'verified',
                'billing_address' => $this->formatStripeAddress($billingDetails->address ?? null),
                'gateway_token' => $stripeMethod->id,
                'gateway_customer_id' => $user->stripe_id,
                'created_at' => date('Y-m-d\TH:i:s.000000\Z', $stripeMethod->created),
                'updated_at' => date('Y-m-d\TH:i:s.000000\Z', $stripeMethod->created),
            ];

            Log::create([
                'user_id' => $user->id,
                'action' => 'Agrega un nuevo método de pago a Stripe',
                'description' => 'Método de pago agregado exitosamente a Stripe',
                'data' => json_encode([
                    'payment_method_id' => $paymentMethodId,
                    'stripe_customer_id' => $user->stripe_id,
                    'is_default' => $isDefaultMethod,
                ])
            ]);

            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Método de pago agregado exitosamente a Stripe',
                'datoAdicional' => $formattedPaymentMethod
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::create([
                'user_id' => FacadesAuth::id(),
                'action' => 'Agrega un nuevo método de pago a Stripe',
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
                'user_id' => FacadesAuth::id(),
                'action' => 'Agrega un nuevo método de pago a Stripe',
                'description' => 'Error al agregar método de pago a Stripe',
                'data' => $e->getMessage(),
            ]);

            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error al agregar método de pago a Stripe',
                'datoAdicional' => $e->getMessage()
            ], 200);
        }
    }

    /**
     * Obtiene un método de pago específico del usuario autenticado
     */
    public function show(Request $request)
    {
        try {
            $request->validate([
                'id' => 'required|integer|exists:user_payment_methods,id'
            ]);

            $userId = FacadesAuth::id();
            $id = $request->integer('id');

            $paymentMethod = UserPaymentMethod::where('id', $id)
                ->where('user_id', $userId)
                ->where('status', 'active')
                ->first();

            if (!$paymentMethod) {
                return response()->json([
                    'exito' => false,
                    'codMensaje' => 0,
                    'mensajeUsuario' => 'Método de pago no encontrado',
                    'datoAdicional' => null
                ], 200);
            }

            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Método de pago obtenido exitosamente',
                'datoAdicional' => new PaymentResource($paymentMethod)
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::create([
                'user_id' => FacadesAuth::id(),
                'action' => 'Obtiene un método de pago específico del usuario autenticado',
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
                'user_id' => FacadesAuth::id(),
                'action' => 'Obtiene un método de pago específico del usuario autenticado',
                'description' => 'Error al obtener método de pago',
                'data' => $e->getMessage(),
            ]);

            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error al obtener método de pago',
                'datoAdicional' => $e->getMessage()
            ], 200);
        }
    }

    /**
     * Actualiza un método de pago específico del usuario autenticado
     */
    public function update(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required|integer|exists:user_payment_methods,id',
                'payment_type' => 'sometimes|string|in:credit_card,debit_card,bank_transfer,digital_wallet,crypto',
                'provider' => 'sometimes|string|in:visa,mastercard,amex,bcp,interbank,scotiabank,bbva,yape,plin,paypal',
                'card_holder_name' => 'sometimes|string|max:255',
                'is_default' => 'sometimes|boolean',
                'billing_address' => 'sometimes|array',
                'metadata' => 'sometimes|array'
            ]);

            if ($validator->fails()) {
                Log::create([
                    'user_id' => FacadesAuth::id(),
                    'action' => 'Actualiza un método de pago específico del usuario autenticado',
                    'description' => 'Datos de entrada inválidos',
                    'data' => $validator->errors()->toJson(),
                ]);

                return response()->json([
                    'exito' => false,
                    'codMensaje' => 0,
                    'mensajeUsuario' => 'Datos de entrada inválidos',
                    'datoAdicional' => $validator->errors()
                ], 200);
            }

            $userId = FacadesAuth::id();
            $id = $request->integer('id');

            $paymentMethod = UserPaymentMethod::where('id', $id)
                ->where('user_id', $userId)
                ->where('status', 'active')
                ->first();

            if (!$paymentMethod) {
                return response()->json([
                    'exito' => false,
                    'codMensaje' => 0,
                    'mensajeUsuario' => 'Método de pago no encontrado',
                    'datoAdicional' => null
                ], 200);
            }

            $data = $validator->validated();
            unset($data['id']); // Remover id de los datos a actualizar

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
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::create([
                'user_id' => FacadesAuth::id(),
                'action' => 'Actualiza un método de pago específico del usuario autenticado',
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
                'user_id' => FacadesAuth::id(),
                'action' => 'Actualiza un método de pago específico del usuario autenticado',
                'description' => 'Error al actualizar método de pago',
                'data' => $e->getMessage(),
            ]);

            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error al actualizar método de pago',
                'datoAdicional' => $e->getMessage()
            ], 200);
        }
    }

    /**
     * Elimina un método de pago directamente desde Stripe (sin tocar BD)
     */
    public function destroy(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required_without:gateway_token|nullable|string',
                'gateway_token' => 'required_without:id|nullable|string',
            ]);

            if ($validator->fails()) {
                Log::create([
                    'user_id' => FacadesAuth::id(),
                    'action' => 'Eliminar método de pago de Stripe',
                    'description' => 'Datos de entrada inválidos',
                    'data' => $validator->errors()->toJson(),
                ]);

                return response()->json([
                    'exito' => false,
                    'codMensaje' => 0,
                    'mensajeUsuario' => 'Datos de entrada inválidos',
                    'datoAdicional' => $validator->errors()
                ], 200);
            }

            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'exito' => false,
                    'codMensaje' => 0,
                    'mensajeUsuario' => 'Usuario no autenticado',
                    'datoAdicional' => []
                ], 401);
            }

            // Obtener el payment_method_id (puede venir como 'id' o 'gateway_token')
            $paymentMethodId = $request->input('gateway_token') ?? $request->input('id');

            if (!$paymentMethodId) {
                return response()->json([
                    'exito' => false,
                    'codMensaje' => 0,
                    'mensajeUsuario' => 'Se requiere el ID del método de pago (gateway_token)',
                    'datoAdicional' => []
                ], 200);
            }

            // Verificar que el usuario tenga un customer en Stripe
            if (!$user->stripe_id) {
                return response()->json([
                    'exito' => false,
                    'codMensaje' => 0,
                    'mensajeUsuario' => 'El usuario no tiene un customer en Stripe',
                    'datoAdicional' => []
                ], 200);
            }

            // Verificar que el payment method existe y pertenece al customer
            try {
                $stripePaymentMethod = $this->retrieveStripePaymentMethod($paymentMethodId);

                // Verificar que el payment method pertenece al customer del usuario
                if ($stripePaymentMethod->customer && $stripePaymentMethod->customer !== $user->stripe_id) {
                    return response()->json([
                        'exito' => false,
                        'codMensaje' => 0,
                        'mensajeUsuario' => 'El método de pago no pertenece a este usuario',
                        'datoAdicional' => []
                    ], 200);
                }
            } catch (\Throwable $e) {
                // Si el payment method no existe en Stripe
                return response()->json([
                    'exito' => false,
                    'codMensaje' => 0,
                    'mensajeUsuario' => 'El método de pago no existe en Stripe',
                    'datoAdicional' => []
                ], 200);
            }

            // Eliminar el método de pago usando Laravel Cashier
            // El método deletePaymentMethod de Cashier hace el detach del payment method
            $user->deletePaymentMethod($paymentMethodId);

            Log::create([
                'user_id' => $user->id,
                'action' => 'Eliminar método de pago de Stripe',
                'description' => 'Método de pago eliminado exitosamente de Stripe',
                'data' => json_encode([
                    'payment_method_id' => $paymentMethodId,
                    'stripe_customer_id' => $user->stripe_id,
                ])
            ]);

            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Método de pago eliminado exitosamente de Stripe',
                'datoAdicional' => null
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::create([
                'user_id' => FacadesAuth::id(),
                'action' => 'Eliminar método de pago de Stripe',
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
                'user_id' => FacadesAuth::id(),
                'action' => 'Eliminar método de pago de Stripe',
                'description' => 'Error al eliminar método de pago de Stripe',
                'data' => $e->getMessage(),
            ]);

            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error al eliminar método de pago de Stripe',
                'datoAdicional' => $e->getMessage()
            ], 200);
        }
    }


    /**
     * Selecciona un método de pago como predeterminado en Stripe (sin tocar BD)
     */
    public function selectPayment(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required|string',
            ]);

            if ($validator->fails()) {
                Log::create([
                    'user_id' => FacadesAuth::id(),
                    'action' => 'Selecciona un método de pago como predeterminado en Stripe',
                    'description' => 'Datos de entrada inválidos',
                    'data' => $validator->errors()->toJson(),
                ]);

                return response()->json([
                    'exito' => false,
                    'codMensaje' => 0,
                    'mensajeUsuario' => 'Datos de entrada inválidos',
                    'datoAdicional' => $validator->errors()
                ], 200);
            }

            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'exito' => false,
                    'codMensaje' => 0,
                    'mensajeUsuario' => 'Usuario no autenticado',
                    'datoAdicional' => []
                ], 401);
            }

            $paymentMethodId = $request->input('id');

            // Si el ID es 0 o '0', significa que se quiere quitar el método predeterminado
            if ($paymentMethodId === 0 || $paymentMethodId === '0') {
                // Actualizar el customer en Stripe para quitar el método predeterminado
                if ($user->stripe_id) {
                    $stripeClient = $this->makeStripeClient();
                    $stripeClient->customers->update($user->stripe_id, [
                        'invoice_settings' => [
                            'default_payment_method' => null
                        ]
                    ]);
                }

                return response()->json([
                    'exito' => true,
                    'codMensaje' => 1,
                    'mensajeUsuario' => 'Método de pago predeterminado removido exitosamente',
                    'datoAdicional' => null
                ], 200);
            }

            // Verificar que el usuario tenga un customer en Stripe
            if (!$user->stripe_id) {
                return response()->json([
                    'exito' => false,
                    'codMensaje' => 0,
                    'mensajeUsuario' => 'El usuario no tiene un customer en Stripe',
                    'datoAdicional' => []
                ], 200);
            }

            // Verificar que el payment method existe y pertenece al customer
            try {
                $stripePaymentMethod = $this->retrieveStripePaymentMethod($paymentMethodId);

                // Verificar que el payment method pertenece al customer del usuario
                if ($stripePaymentMethod->customer && $stripePaymentMethod->customer !== $user->stripe_id) {
                    return response()->json([
                        'exito' => false,
                        'codMensaje' => 0,
                        'mensajeUsuario' => 'El método de pago no pertenece a este usuario',
                        'datoAdicional' => []
                    ], 200);
                }
            } catch (\Throwable $e) {
                return response()->json([
                    'exito' => false,
                    'codMensaje' => 0,
                    'mensajeUsuario' => 'El método de pago no existe en Stripe',
                    'datoAdicional' => []
                ], 200);
            }

            // Actualizar el método predeterminado en Stripe usando Laravel Cashier
            $user->updateDefaultPaymentMethod($paymentMethodId);

            // Obtener el método de pago actualizado desde Stripe
            $paymentMethod = $user->findPaymentMethod($paymentMethodId);

            if (!$paymentMethod) {
                return response()->json([
                    'exito' => false,
                    'codMensaje' => 0,
                    'mensajeUsuario' => 'No se pudo obtener el método de pago actualizado',
                    'datoAdicional' => []
                ], 200);
            }

            // Obtener el método de pago completo desde Stripe
            $stripeMethod = $paymentMethod->asStripePaymentMethod();
            $card = $stripeMethod->card;
            $billingDetails = $stripeMethod->billing_details ?? null;

            // Formatear la respuesta
            $formattedPaymentMethod = [
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
                'is_expired' => $this->isCardExpired($card->exp_month, $card->exp_year),
                'is_default' => true,
                'is_saved_for_future' => true,
                'status' => 'active',
                'verification_status' => 'verified',
                'billing_address' => $this->formatStripeAddress($billingDetails->address ?? null),
                'gateway_token' => $stripeMethod->id,
                'gateway_customer_id' => $user->stripe_id,
                'created_at' => date('Y-m-d\TH:i:s.000000\Z', $stripeMethod->created),
                'updated_at' => date('Y-m-d\TH:i:s.000000\Z', $stripeMethod->created),
            ];

            Log::create([
                'user_id' => $user->id,
                'action' => 'Selecciona un método de pago como predeterminado en Stripe',
                'description' => 'Método de pago establecido como predeterminado en Stripe',
                'data' => json_encode([
                    'payment_method_id' => $paymentMethodId,
                    'stripe_customer_id' => $user->stripe_id,
                ])
            ]);

            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Método de pago seleccionado exitosamente como predeterminado',
                'datoAdicional' => $formattedPaymentMethod
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::create([
                'user_id' => FacadesAuth::id(),
                'action' => 'Selecciona un método de pago como predeterminado en Stripe',
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
                'user_id' => FacadesAuth::id(),
                'action' => 'Selecciona un método de pago como predeterminado en Stripe',
                'description' => 'Error al seleccionar método de pago predeterminado',
                'data' => $e->getMessage(),
            ]);

            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error al seleccionar método de pago predeterminado',
                'datoAdicional' => $e->getMessage()
            ], 200);
        }
    }



    /**
     * Obtener el método de pago predeterminado desde Stripe
     */
    public function defaultPayment(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'exito' => false,
                    'codMensaje' => 0,
                    'mensajeUsuario' => 'Usuario no autenticado',
                    'datoAdicional' => []
                ], 401);
            }

            // Verificar que el usuario tenga un customer en Stripe
            if (!$user->stripe_id) {
                return response()->json([
                    'exito' => false,
                    'codMensaje' => 2,
                    'mensajeUsuario' => 'No tienes un método de pago por defecto configurado',
                    'datoAdicional' => null,
                ], 200);
            }

            // Obtener el método de pago predeterminado desde Stripe usando Laravel Cashier
            $defaultPaymentMethod = $user->defaultPaymentMethod();

            if (!$defaultPaymentMethod) {
                return response()->json([
                    'exito' => false,
                    'codMensaje' => 2,
                    'mensajeUsuario' => 'No tienes un método de pago por defecto configurado',
                    'datoAdicional' => null,
                ], 200);
            }

            // Obtener el método de pago completo desde Stripe
            $stripeMethod = $defaultPaymentMethod->asStripePaymentMethod();
            $card = $stripeMethod->card;
            $billingDetails = $stripeMethod->billing_details ?? null;

            // Formatear la respuesta
            $formattedPaymentMethod = [
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
                'is_expired' => $this->isCardExpired($card->exp_month, $card->exp_year),
                'is_default' => true,
                'is_saved_for_future' => true,
                'status' => 'active',
                'verification_status' => 'verified',
                'billing_address' => $this->formatStripeAddress($billingDetails->address ?? null),
                'gateway_token' => $stripeMethod->id,
                'gateway_customer_id' => $user->stripe_id,
                'created_at' => date('Y-m-d\TH:i:s.000000\Z', $stripeMethod->created),
                'updated_at' => date('Y-m-d\TH:i:s.000000\Z', $stripeMethod->created),
            ];

            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Método de pago por defecto obtenido exitosamente',
                'datoAdicional' => $formattedPaymentMethod,
            ], 200);
        } catch (\Throwable $e) {
            Log::create([
                'user_id' => FacadesAuth::id(),
                'action' => 'Obtener el metodo de pago de la compra',
                'description' => 'Error al obtener el método de pago',
                'data' => $e->getMessage(),
            ]);

            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error al obtener el método de pago',
                'datoAdicional' => $e->getMessage(),
            ], 200);
        }
    }

    /**
     * Genera un SetupIntent de Stripe para el usuario autenticado.
     */
    public function createStripeIntent(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'exito' => false,
                    'codMensaje' => 0,
                    'mensajeUsuario' => 'Usuario no autenticado',
                    'datoAdicional' => []
                ], 401);
            }

            $intent = $user->createSetupIntent();

            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => 'SetupIntent generado correctamente',
                'datoAdicional' => [
                    'id' => $intent->id,
                    'client_secret' => $intent->client_secret,
                    'status' => $intent->status,
                ]
            ], 200);
        } catch (\Throwable $e) {
            Log::create([
                'user_id' => FacadesAuth::id(),
                'action' => 'Genera un SetupIntent de Stripe para el usuario autenticado',
                'description' => 'Error al generar el SetupIntent',
                'data' => $e->getMessage(),
            ]);

            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error al generar el SetupIntent',
                'datoAdicional' => $e->getMessage()
            ], 200);
        }
    }

    private function retrieveStripePaymentMethod(string $paymentMethodId)
    {
        $stripeClient = $this->makeStripeClient();

        try {
            return $stripeClient->paymentMethods->retrieve($paymentMethodId, []);
        } catch (\Throwable $e) {


            Log::create([
                'user_id' => FacadesAuth::id(),
                'action' => 'Stripe API error al recuperar payment_method',
                'description' => 'Stripe API error al recuperar payment_method',
                'data' => $e->getMessage(),
            ]);


            throw $e;
        }
    }

    private function makeStripeClient(): StripeClient
    {
        $secret = config('services.stripe.secret');

        if (!$secret) {
            throw new \RuntimeException('Stripe no está configurado correctamente. Falta services.stripe.secret.');
        }

        return new StripeClient($secret);
    }

    /**
     * Mapea la marca de tarjeta de Stripe a nuestro formato de provider
     * @param string|null $stripeBrand - Marca de Stripe (visa, mastercard, amex, etc.)
     * @return string|null - Provider mapeado o null si no se puede mapear
     */
    private function mapStripeBrandToProvider(?string $stripeBrand): ?string
    {
        if (!$stripeBrand) {
            return null;
        }

        // Mapeo de marcas de Stripe a nuestros providers
        $brandMap = [
            'visa' => 'visa',
            'mastercard' => 'mastercard',
            'amex' => 'amex',
            'american_express' => 'amex', // Stripe también puede devolver esto
            'discover' => 'discover',
            'diners' => 'diners',
            'diners_club' => 'diners', // Stripe también puede devolver esto
            'jcb' => 'jcb',
            'unionpay' => 'unionpay',
        ];

        return $brandMap[strtolower($stripeBrand)] ?? null;
    }

    /**
     * Obtiene los métodos de pago directamente desde Stripe usando Laravel Cashier
     */
    private function getPaymentMethodsFromStripe($user, Request $request)
    {
        try {
            // Log para depuración
            Log::create([
                'user_id' => $user->id ?? null,
                'action' => 'Obtener métodos de pago desde Stripe - Inicio',
                'description' => 'Iniciando obtención de métodos de pago',
                'data' => json_encode([
                    'user_id' => $user->id,
                    'stripe_id' => $user->stripe_id,
                    'has_stripe_id' => !empty($user->stripe_id),
                ])
            ]);

            // Verificar si el usuario tiene un customer_id en Stripe
            if (!$user->stripe_id) {
                Log::create([
                    'user_id' => $user->id ?? null,
                    'action' => 'Obtener métodos de pago desde Stripe',
                    'description' => 'Usuario no tiene stripe_id',
                    'data' => json_encode(['user_id' => $user->id])
                ]);

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

            // Intentar obtener métodos de pago usando Laravel Cashier primero
            $cashierPaymentMethods = $user->paymentMethods('card');
            $defaultPaymentMethodId = null;

            Log::create([
                'user_id' => $user->id ?? null,
                'action' => 'Obtener métodos de pago desde Stripe',
                'description' => 'Métodos obtenidos de Cashier',
                'data' => json_encode([
                    'count' => $cashierPaymentMethods->count(),
                    'stripe_id' => $user->stripe_id,
                ])
            ]);

            // Si Cashier no devuelve métodos, intentar con StripeClient directamente
            if ($cashierPaymentMethods->isEmpty()) {
                Log::create([
                    'user_id' => $user->id ?? null,
                    'action' => 'Obtener métodos de pago desde Stripe',
                    'description' => 'Cashier devolvió vacío, intentando con StripeClient directamente',
                    'data' => json_encode(['stripe_id' => $user->stripe_id])
                ]);

                $stripeClient = $this->makeStripeClient();
                // Usar el endpoint específico para listar métodos de pago de un customer
                // Según la documentación: GET /v1/customers/:id/payment_methods
                $stripePaymentMethodsResponse = $stripeClient->customers->allPaymentMethods(
                    $user->stripe_id,
                    ['type' => 'card']
                );

                Log::create([
                    'user_id' => $user->id ?? null,
                    'action' => 'Obtener métodos de pago desde Stripe',
                    'description' => 'Métodos obtenidos directamente de Stripe API',
                    'data' => json_encode([
                        'count' => count($stripePaymentMethodsResponse->data),
                        'stripe_id' => $user->stripe_id,
                    ])
                ]);

                // Obtener el customer para el método predeterminado
                $stripeCustomer = $user->asStripeCustomer();
                $defaultPaymentMethodId = $stripeCustomer->invoice_settings->default_payment_method ?? null;

                // Transformar los métodos de Stripe API al formato esperado
                $paymentMethods = [];
                foreach ($stripePaymentMethodsResponse->data as $stripeMethod) {
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
                            'is_expired' => $this->isCardExpired($card->exp_month, $card->exp_year),
                            'is_default' => ($stripeMethod->id === $defaultPaymentMethodId),
                            'is_saved_for_future' => true,
                            'status' => 'active',
                            'verification_status' => 'verified',
                            'billing_address' => $this->formatStripeAddress($billingDetails->address ?? null),
                            'gateway_token' => $stripeMethod->id,
                            'gateway_customer_id' => $user->stripe_id,
                            'created_at' => date('Y-m-d\TH:i:s.000000\Z', $stripeMethod->created),
                            'updated_at' => date('Y-m-d\TH:i:s.000000\Z', $stripeMethod->created),
                        ];
                    }
                }
            } else {
                // Obtener el método de pago predeterminado
                $defaultPaymentMethod = $user->defaultPaymentMethod();
                $defaultPaymentMethodId = $defaultPaymentMethod ? $defaultPaymentMethod->id : null;

                // Transformar los métodos de Cashier al formato esperado
                $paymentMethods = [];
                foreach ($cashierPaymentMethods as $cashierMethod) {
                    // Obtener el objeto Stripe completo
                    $stripeMethod = $cashierMethod->asStripePaymentMethod();

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
                            'is_expired' => $this->isCardExpired($card->exp_month, $card->exp_year),
                            'is_default' => ($stripeMethod->id === $defaultPaymentMethodId),
                            'is_saved_for_future' => true,
                            'status' => 'active',
                            'verification_status' => 'verified',
                            'billing_address' => $this->formatStripeAddress($billingDetails->address ?? null),
                            'gateway_token' => $stripeMethod->id,
                            'gateway_customer_id' => $user->stripe_id,
                            'created_at' => date('Y-m-d\TH:i:s.000000\Z', $stripeMethod->created),
                            'updated_at' => date('Y-m-d\TH:i:s.000000\Z', $stripeMethod->created),
                        ];
                    }
                }
            }

            // Ordenar: primero el predeterminado
            usort($paymentMethods, function ($a, $b) {
                if ($a['is_default'] && !$b['is_default']) return -1;
                if (!$a['is_default'] && $b['is_default']) return 1;
                return 0;
            });

            Log::create([
                'user_id' => $user->id ?? null,
                'action' => 'Obtener métodos de pago desde Stripe - Final',
                'description' => 'Métodos transformados',
                'data' => json_encode([
                    'payment_methods_count' => count($paymentMethods),
                    'cashier_count' => $cashierPaymentMethods->count(),
                    'default_payment_method_id' => $defaultPaymentMethodId,
                ])
            ]);

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
                'user_id' => $user->id ?? null,
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
     * Verifica si una tarjeta está expirada
     */
    private function isCardExpired(?int $expMonth, ?int $expYear): bool
    {
        if (!$expMonth || !$expYear) {
            return false;
        }

        $expiryDate = \Carbon\Carbon::createFromDate($expYear, $expMonth, 1)->endOfMonth();
        return $expiryDate->isPast();
    }

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
}
