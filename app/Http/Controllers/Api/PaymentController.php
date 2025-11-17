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
            $userId = FacadesAuth::id();

            // Validar parámetros de paginación
            $request->validate([
                'per_page' => 'nullable|integer|min:1|max:50',
                'page' => 'nullable|integer|min:1'
            ]);

            $query = UserPaymentMethod::where('user_id', $userId)
                ->where('status', 'active')
                ->orderBy('is_default', 'desc')
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
     * Crea un nuevo método de pago para el usuario autenticado
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'payment_method_id' => 'required_without:payment_type|nullable|string|max:255',
                'payment_type' => 'required_without:payment_method_id|string|in:credit_card,debit_card,bank_transfer,digital_wallet,crypto',
                'provider' => 'nullable|string|in:visa,mastercard,amex,bcp,interbank,scotiabank,bbva,yape,plin,paypal,mercadopago',
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

            if ($validator->fails()) {
                Log::create([
                    'user_id' => FacadesAuth::id(),
                    'action' => 'Crea un nuevo método de pago para el usuario autenticado',
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
            $userId = $user?->id;

            if (!$userId) {
                return response()->json([
                    'exito' => false,
                    'codMensaje' => 0,
                    'mensajeUsuario' => 'Usuario no autenticado',
                    'datoAdicional' => []
                ], 401);
            }

            $data = $validator->validated();
            $data['user_id'] = $userId;
            $data['status'] = 'active';
            $data['verification_status'] = $data['payment_method_id'] ? 'verified' : 'pending';

            if (!empty($data['payment_method_id'])) {
                $stripePaymentMethod = $this->retrieveStripePaymentMethod($data['payment_method_id']);

                if (!$stripePaymentMethod || $stripePaymentMethod->type !== 'card' || empty($stripePaymentMethod->card)) {
                    return response()->json([
                        'exito' => false,
                        'codMensaje' => 0,
                        'mensajeUsuario' => 'El método de pago proporcionado no es una tarjeta válida de Stripe',
                        'datoAdicional' => []
                    ], 200);
                }

                $card = $stripePaymentMethod->card;
                $billingDetails = $stripePaymentMethod->billing_details;

                $formattedBillingAddress = $this->formatStripeAddress($billingDetails->address ?? null);
                $metadata = $stripePaymentMethod->metadata?->toArray() ?? null;

                $data = array_merge($data, [
                    'payment_type' => $data['payment_type'] ?? 'credit_card',
                    'provider' => $data['provider'] ?? ($card->brand ? strtolower($card->brand) : null),
                    'card_brand' => $card->brand,
                    'card_last_four' => $card->last4,
                    'card_holder_name' => $data['card_holder_name'] ?? ($billingDetails->name ?? null),
                    'card_expiry_month' => $card->exp_month,
                    'card_expiry_year' => $card->exp_year,
                    'gateway_token' => $stripePaymentMethod->id,
                    'gateway_customer_id' => $stripePaymentMethod->customer ?? $user->stripe_id,
                    'billing_address' => $formattedBillingAddress,
                    'metadata' => $metadata,
                ]);

                unset($data['payment_method_id']);
            }

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
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::create([
                'user_id' => FacadesAuth::id(),
                'action' => 'Crea un nuevo método de pago para el usuario autenticado',
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
                'action' => 'Crea un nuevo método de pago para el usuario autenticado',
                'description' => 'Error al crear método de pago',
                'data' => $e->getMessage(),
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
     * Desactiva (elimina lógicamente) un método de pago específico del usuario autenticado.
     */
    public function destroy(Request $request)
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

            // Desactivar el método de pago (soft delete)
            $paymentMethod->update(['status' => 'inactive']);

            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Método de pago eliminado exitosamente',
                'datoAdicional' => null
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::create([
                'user_id' => FacadesAuth::id(),
                'action' => 'Desactiva (elimina lógicamente) un método de pago específico del usuario autenticado',
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
                'action' => 'Desactiva (elimina lógicamente) un método de pago específico del usuario autenticado',
                'description' => 'Error al eliminar método de pago',
                'data' => $e->getMessage(),
            ]);

            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error al eliminar método de pago',
                'datoAdicional' => $e->getMessage()
            ], 200);
        }
    }


    /**
     * Selecciona un método de pago como predeterminado para el usuario autenticado
     */
    public function selectPayment(Request $request)
    {
        try {
            $request->validate([
                'id' => 'required|integer|min:0'
            ]);

            $userId = FacadesAuth::id();
            $id = $request->integer('id');

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
                    'datoAdicional' => null
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
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::create([
                'user_id' => FacadesAuth::id(),
                'action' => 'Selecciona un método de pago como predeterminado para el usuario autenticado',
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
                'action' => 'Selecciona un método de pago como predeterminado para el usuario autenticado',
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
     * Obtener el metodo de pago de la compra
     */
    public function defaultPayment(Request $request)
    {
        try {
            // Obtener el ID del usuario autenticado
            $user_id = FacadesAuth::id();

            // Buscar el método de pago por defecto del usuario
            $userPaymentMethod = UserPaymentMethod::where('user_id', $user_id)
                ->where('is_default', true)
                ->where('status', 'active')
                ->first();

            if (!$userPaymentMethod) {
                // Si no tiene un método de pago por defecto, sugerir agregar uno
                return response()->json([
                    'exito' => false,
                    'codMensaje' => 2,
                    'mensajeUsuario' => 'No tienes un método de pago por defecto configurado',
                    'datoAdicional' => null,
                ], 200);
            }

            // Si tiene un método de pago por defecto, devolverlo
            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Método de pago por defecto obtenido exitosamente',
                'datoAdicional' => $userPaymentMethod,
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
        } catch (ApiErrorException $e) {


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
