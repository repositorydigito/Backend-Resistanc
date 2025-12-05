<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PackageResource;
use App\Http\Resources\UserPackageResource;
use App\Mail\PackagePurchasedMailable;
use App\Models\Package;
use App\Models\UserPackage;
use App\Models\UserMembership;
use App\Models\Log;
use App\Services\SunatServices;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Stripe\StripeClient;

/**
 * @tags Paquetes
 */
final class PackageController extends Controller
{
    /**
     * Lista todos los paquetes disponibles del sistema
     */
    public function index(Request $request)
    {
        try {
            // Validar parámetros opcionales
            $request->validate([
                'discipline_id' => 'sometimes',
                'discipline_group' => 'sometimes|string',
                'mode_type' => 'sometimes|string|in:online,presencial,híbrido', // Ajusta según tus valores
                'commercial_type' => 'sometimes|string|in:promotion,regular', // Ajusta según tus valores
                'is_membresia' => 'sometimes|boolean',
                'per_page' => 'sometimes|integer|min:1|max:100',
                'page' => 'sometimes|integer|min:1',
            ]);

            $packages = Package::query()
                ->with(['disciplines', 'membership'])
                ->withCount(['userPackages'])
                ->where('buy_type', 'affordable')
                ->active()
                ->where(function ($query) {
                    // Paquetes fijos o temporales vigentes
                    $query->where('type', 'fixed')
                        ->orWhere(function ($subQuery) {
                            $subQuery->where('type', 'temporary')
                                ->where('start_date', '<=', now())
                                ->where('end_date', '>=', now());
                        });
                })
                ->when(
                    $request->filled('discipline_id') || $request->filled('discipline_group'),
                    function ($query) use ($request) {
                        $disciplineIds = collect();

                        if ($request->filled('discipline_group')) {
                            $disciplineIds = collect(explode('-', $request->string('discipline_group')))
                                ->filter()
                                ->filter(fn($value) => is_numeric($value))
                                ->map(fn($value) => (int) $value)
                                ->unique()
                                ->values();
                        } elseif ($request->filled('discipline_id')) {
                            $rawDiscipline = $request->input('discipline_id');

                            if (is_array($rawDiscipline)) {
                                $disciplineIds = collect($rawDiscipline);
                            } else {
                                $disciplineIds = collect(preg_split('/[,\-]/', (string) $rawDiscipline));
                            }

                            $disciplineIds = $disciplineIds
                                ->filter()
                                ->filter(fn($value) => is_numeric($value))
                                ->map(fn($value) => (int) $value)
                                ->unique()
                                ->values();
                        }

                        if ($disciplineIds->isEmpty()) {
                            return;
                        }

                        $disciplineIds->each(function (int $disciplineId) use ($query) {
                            $query->whereHas('disciplines', function ($subQuery) use ($disciplineId) {
                                $subQuery->where('disciplines.id', $disciplineId);
                            });
                        });

                        if ($request->filled('discipline_group')) {
                            $query->whereDoesntHave('disciplines', function ($subQuery) use ($disciplineIds) {
                                $subQuery->whereNotIn('disciplines.id', $disciplineIds->all());
                            });
                        }
                    }
                )
                ->when($request->filled('mode_type'), function ($query) use ($request) {
                    $query->where('mode_type', $request->string('mode_type'));
                })
                ->when($request->filled('commercial_type'), function ($query) use ($request) {
                    $query->where('commercial_type', $request->string('commercial_type'));
                })
                ->when($request->has('is_membresia'), function ($query) use ($request) {
                    $query->where('is_membresia', $request->boolean('is_membresia'));
                })
                ->orderByRaw("
                CASE
                    WHEN commercial_type = 'promotion' THEN 0
                    ELSE 1
                END ASC
            ")
                ->orderBy('price_soles', 'asc')
                ->paginate(
                    perPage: $request->integer('per_page', 15),
                    page: $request->integer('page', 1)
                );

            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Paquetes obtenidos exitosamente',
                'datoAdicional' => PackageResource::collection($packages),
            ], 200);
        } catch (\Throwable $e) {

            Log::create([
                'user_id' => Auth::id(),
                'action' => 'Lista todos los paquetes disponibles del sistema',
                'description' => 'Fallo al listar paquetes',
                'data' => $e->getMessage(),
            ]);
            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Fallo al listar paquetes',
                'datoAdicional' => $e->getMessage(),
            ], 200); // Código 500 para errores del servidor
        }
    }

    /**
     * Obtener paquete
     */
    public function show(Request $request)
    {
        $request->validate([
            'package_id' => 'required|exists:packages,id'
        ]);

        try {
            $package = Package::where('id', $request->package_id)
                ->where('buy_type', 'affordable')
                ->with(['disciplines', 'membership'])
                ->first(); // Usa first() en lugar de get()

            if (!$package) {
                return response()->json([
                    'exito' => false,
                    'codMensaje' => 0,
                    'mensajeUsuario' => 'Paquete no encontrado o no es asequible',
                    'datoAdicional' => null
                ], 200);
            }

            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Paquete obtenido exitosamente',
                'datoAdicional' => new PackageResource($package),
            ], 200);
        } catch (\Throwable $e) {

            Log::create([
                'user_id' => Auth::id(),
                'action' => 'Obtener paquete',
                'description' => 'Fallo al obtener paquete',
                'data' => $e->getMessage(),
            ]);

            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Fallo al obtener paquete',
                'datoAdicional' => $e->getMessage(),
            ], 200);
        }
    }
    /**
     * Mis paquetes
     */
    public function packageMe(Request $request)
    {

        $request->validate([
            'discipline_id' => 'sometimes',
            'discipline_group' => 'sometimes|string'
        ]);

        try {
            $query = UserPackage::query()
                ->where('user_id', Auth::id())
                ->where(function ($q) {
                    $q->where('status', 'active')
                        ->orWhere('remaining_classes', 0)
                        ->orWhere('expiry_date', '<', now());
                })
                ->orderBy('expiry_date', 'desc')
                ->with(['package:id,name,slug,description,classes_quantity,price_soles', 'package.disciplines:id,name,display_name,icon_url,color_hex,order']);

            if ($request->filled('discipline_id') || $request->filled('discipline_group')) {
                $disciplineIds = collect();

                if ($request->filled('discipline_group')) {
                    $disciplineIds = collect(explode('-', $request->string('discipline_group')))
                        ->filter()
                        ->filter(fn($value) => is_numeric($value))
                        ->map(fn($value) => (int) $value)
                        ->unique()
                        ->values();
                } elseif ($request->filled('discipline_id')) {
                    $rawDiscipline = $request->input('discipline_id');

                    if (is_array($rawDiscipline)) {
                        $disciplineIds = collect($rawDiscipline);
                    } else {
                        $disciplineIds = collect(preg_split('/[,\-]/', (string) $rawDiscipline));
                    }

                    $disciplineIds = $disciplineIds
                        ->filter()
                        ->filter(fn($value) => is_numeric($value))
                        ->map(fn($value) => (int) $value)
                        ->unique()
                        ->values();
                }

                if ($disciplineIds->isNotEmpty()) {
                    $disciplineIds->each(function (int $disciplineId) use ($query) {
                        $query->whereHas('package.disciplines', function ($q) use ($disciplineId) {
                            $q->where('disciplines.id', $disciplineId);
                        });
                    });

                    if ($request->filled('discipline_group')) {
                        $query->whereDoesntHave('package.disciplines', function ($q) use ($disciplineIds) {
                            $q->whereNotIn('disciplines.id', $disciplineIds->all());
                        });
                    }
                }
            }

            $userPackages = $query
                ->latest()
                ->paginate(
                    perPage: min($request->integer('per_page', 15), 50),
                    page: $request->integer('page', 1)
                );

            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Lista de paquetes del usuario obtenida correctamente',
                'datoAdicional' => UserPackageResource::collection($userPackages)
            ], 200);
        } catch (\Throwable $e) {

            Log::create([
                'user_id' => Auth::id(),
                'action' => 'Mis paquetes',
                'description' => 'Error al obtener los paquetes del usuario',
                'data' => $e->getMessage(),
            ]);

            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error al obtener los paquetes del usuario',
                'datoAdicional' => $e->getMessage()
            ], 200);
        }
    }

    /**
     * Comprar/Agregar un paquete al usuario autenticado
     */
    public function packageMeCreate(Request $request)
    {
        try {
            // Validar datos de entrada
            $validationRules = [
                'package_id' => 'required|integer|exists:packages,id',
                'payment_method_id' => 'required|string', // ID de Stripe directamente
                'promo_code' => 'sometimes|string|max:50',
                'notes' => 'sometimes|string|max:500',
                'invoice_type' => 'sometimes|string|in:boleta,factura', // Tipo de comprobante (por defecto: boleta)
                // Datos de facturación (requeridos solo si invoice_type es 'factura')
                'invoice_data' => 'required_if:invoice_type,factura|array',
                'invoice_data.ruc' => 'required_if:invoice_type,factura|string|size:11|regex:/^[0-9]{11}$/',
                'invoice_data.razon_social' => 'required_if:invoice_type,factura|string|min:3|max:255',
                'invoice_data.direccion_fiscal' => 'required_if:invoice_type,factura|string|min:5|max:500',
            ];
            
            // Validar email solo si está presente y no está vacío
            if ($request->has('invoice_data.email') && !empty($request->input('invoice_data.email'))) {
                $validationRules['invoice_data.email'] = 'email|max:255';
            }
            
            $request->validate($validationRules);

            $userId = Auth::id();

            // Obtener el usuario
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'exito' => false,
                    'codMensaje' => 0,
                    'mensajeUsuario' => 'Usuario no autenticado',
                    'datoAdicional' => []
                ], 200);
            }

            // Asegurar que el usuario tenga un customer en Stripe
            if (!$user->stripe_id) {
                $user->createAsStripeCustomer();
            }

            // Obtener el ID del método de pago directamente desde Stripe (no desde BD)
            $stripePaymentMethodId = $request->input('payment_method_id');

            // Validar que el método de pago existe en Stripe y pertenece al usuario
            try {
                // Verificar que el método de pago existe y está asociado al customer
                $paymentMethod = $user->findPaymentMethod($stripePaymentMethodId);

                if (!$paymentMethod) {
                    // Si no está asociado, intentar asociarlo
                    try {
                        $user->addPaymentMethod($stripePaymentMethodId);
                    } catch (\Exception $e) {
                        return response()->json([
                            'exito' => false,
                            'codMensaje' => 0,
                            'mensajeUsuario' => 'El método de pago no es válido o no pertenece a este usuario',
                            'datoAdicional' => $e->getMessage()
                        ], 200);
                    }
                }

                // Verificar que el método de pago pertenece al customer del usuario
                $stripePaymentMethod = $user->findPaymentMethod($stripePaymentMethodId)->asStripePaymentMethod();
                if ($stripePaymentMethod->customer && $stripePaymentMethod->customer !== $user->stripe_id) {
                    return response()->json([
                        'exito' => false,
                        'codMensaje' => 0,
                        'mensajeUsuario' => 'El método de pago no pertenece a este usuario',
                        'datoAdicional' => []
                    ], 200);
                }

            } catch (\Exception $e) {
                Log::create([
                    'user_id' => $userId,
                    'action' => 'Error al validar método de pago de Stripe',
                    'description' => 'Error al validar método de pago de Stripe',
                    'data' => json_encode([
                        'payment_method_id' => $stripePaymentMethodId,
                        'error' => $e->getMessage(),
                    ]),
                ]);

                return response()->json([
                    'exito' => false,
                    'codMensaje' => 0,
                    'mensajeUsuario' => 'Error al validar el método de pago en Stripe',
                    'datoAdicional' => $e->getMessage()
                ], 200);
            }

            // Obtener el paquete
            $package = Package::where('id', $request->package_id)
                ->where('buy_type', 'affordable')
                ->where('status', 'active')
                ->where(function ($query) {
                    // Paquetes fijos o temporales vigentes
                    $query->where('type', 'fixed')
                        ->orWhere(function ($subQuery) {
                            $subQuery->where('type', 'temporary')
                                ->where('start_date', '<=', now())
                                ->where('end_date', '>=', now());
                        });
                })
                ->first();

            if (!$package) {
                return response()->json([
                    'exito' => false,
                    'codMensaje' => 0,
                    'mensajeUsuario' => 'Paquete no disponible para compra',
                    'datoAdicional' => 'El paquete no está disponible o no es asequible'
                ], 200);
            }

            // Si el paquete es una membresía (recurrente), no se permiten códigos promocionales
            if ($package->is_membresia && $request->filled('promo_code')) {
                return response()->json([
                    'exito' => false,
                    'codMensaje' => 0,
                    'mensajeUsuario' => 'Los códigos promocionales no están disponibles para paquetes de membresía',
                    'datoAdicional' => [
                        'reason' => 'membership_no_promo',
                        'package_type' => 'membership'
                    ]
                ], 200);
            }

            // Validar y aplicar código promocional solo si NO es membresía
            $promoCodeData = null;
            $finalPrice = $package->price_soles; // Precio SIN IGV
            $originalPrice = $package->price_soles; // Precio SIN IGV
            $discountPercentage = 0;
            $discountAmount = 0;
            $promoCodeUsed = null;

            if (!$package->is_membresia && $request->filled('promo_code')) {
                $promoCodeValidation = $this->validateAndApplyPromoCode($request->string('promo_code'), $package->id, $userId);

                if (!$promoCodeValidation['valid']) {
                    return response()->json([
                        'exito' => false,
                        'codMensaje' => 0,
                        'mensajeUsuario' => $promoCodeValidation['message'],
                        'datoAdicional' => $promoCodeValidation['details'] ?? null
                    ], 200);
                }

                $promoCodeData = $promoCodeValidation['data'];

                // Los precios vienen con IGV desde validateAndApplyPromoCode, pero necesitamos sin IGV
                // para calcular correctamente. Convertimos de vuelta a precio sin IGV.
                $igvPercentage = (float) ($package->igv ?? 18);

                // Convertir precios CON IGV a precios SIN IGV
                $originalPriceWithIgv = $promoCodeData['original_price'];
                $finalPriceWithIgv = $promoCodeData['final_price'];

                $originalPrice = $originalPriceWithIgv / (1 + ($igvPercentage / 100));
                $finalPrice = $finalPriceWithIgv / (1 + ($igvPercentage / 100));

                $discountPercentage = $promoCodeData['discount_percentage'];
                $discountAmount = $promoCodeData['discount_amount'] / (1 + ($igvPercentage / 100)); // Convertir también el descuento
                $promoCodeUsed = $promoCodeValidation['code'];
            }

            // Calcular fecha de expiración
            $expiryDate = $package->duration_in_months
                ? now()->addMonths($package->duration_in_months)
                : now()->addDays($package->validity_days ?? 30);

            // Verificar si el paquete tiene una membresía asociada con beneficios
            $membershipData = null;
            $giftOrderId = null;
            $giftShakeQuantity = 0;

            if ($package->membership_id && $package->membership) {
                $membership = $package->membership;

                // Calcular fecha de expiración de la membresía basada en su duración (fallback seguro)
                $membershipExpiryDate = now()->addMonths((int) ($membership->duration ?? 0));

                // Determinar beneficios de disciplina
                $hasDisciplineBenefit = (bool) ($membership->is_benefit_discipline ?? false);
                $disciplineId = $hasDisciplineBenefit ? ($membership->discipline_id ?? null) : null;
                $disciplineQuantity = ($hasDisciplineBenefit && $disciplineId && ($membership->discipline_quantity ?? 0) > 0)
                    ? (int) $membership->discipline_quantity
                    : 0;

                $giftShakeQuantity = (int) ($membership->shake_quantity ?? 0);

                // Crear SIEMPRE la membresía del usuario (incluso si no otorga clases gratis)
                $membershipData = UserMembership::create([
                    'user_id' => $userId,
                    'membership_id' => $membership->id,
                    'discipline_id' => $disciplineId,
                    'total_free_classes' => $disciplineQuantity,
                    'used_free_classes' => 0,
                    'remaining_free_classes' => $disciplineQuantity,
                    'total_free_shakes' => $giftShakeQuantity,
                    'used_free_shakes' => 0,
                    'remaining_free_shakes' => $giftShakeQuantity,
                    'activation_date' => now(),
                    'expiry_date' => $membershipExpiryDate,
                    'status' => 'active',
                    'source_package_id' => $package->id,
                    'notes' => $disciplineQuantity > 0
                        ? "Clases gratis otorgadas por la compra del paquete: {$package->name} (Duración membresía: {$membership->duration} meses)"
                        : "Membresía otorgada por la compra del paquete: {$package->name} (sin clases gratis)",
                ]);

                // Crear puntos en point_user basados en la cantidad de clases completadas de la membresía
                // La cantidad de puntos es igual a class_completed de la membresía
                $pointsQuantity = (int) ($membership->class_completed ?? 0);
                
                if ($pointsQuantity > 0) {
                    // Obtener la configuración de la compañía para la duración de los puntos
                    $company = \App\Models\Company::first();
                    $monthsPoints = $company ? ($company->months_points ?? 8) : 8;
                    
                    // Calcular la fecha de expiración de los puntos
                    $pointsExpiryDate = now()->addMonths($monthsPoints);
                    
                    // Crear el registro de puntos
                    \App\Models\UserPoint::create([
                        'user_id' => $userId,
                        'quantity_point' => $pointsQuantity,
                        'date_expire' => $pointsExpiryDate,
                        'membresia_id' => $membership->id,
                        'active_membership_id' => $membership->id, // Inicialmente la misma membresía
                        'package_id' => $package->id,
                    ]);
                    
                    \Illuminate\Support\Facades\Log::info('Puntos creados al comprar paquete con membresía', [
                        'user_id' => $userId,
                        'package_id' => $package->id,
                        'membership_id' => $membership->id,
                        'quantity_point' => $pointsQuantity,
                        'date_expire' => $pointsExpiryDate->toDateString(),
                    ]);
                }

                // Actualizar los puntos del usuario con la nueva membresía activa
                \App\Models\UserPoint::updateActiveMembershipForUser($userId, $membership->id);

                // Actualizar las clases completadas efectivas del usuario
                // Cuando compra un paquete con membresía, se le otorgan las clases base de esa membresía
                $user->calculateAndUpdateEffectiveCompletedClasses();

                // Si la membresía tiene beneficios de shake, crear pedido de regalo
                // El canje de shakes se realiza bajo demanda; aquí solo se registran las cantidades disponibles.
            }

            // ============================================
            // VALIDAR FACTURACIÓN ANTES DE PROCESAR PAGO
            // ============================================
            // Validar los datos de facturación ANTES de procesar el pago en Stripe
            // Si la facturación no puede generarse, NO se debe cobrar
            $invoiceType = $request->string('invoice_type', 'boleta')->value(); // Por defecto: boleta
            $clientData = $this->prepareClientDataForInvoice($user, $invoiceType, $request);
            
            // Si es factura, validar que tenga todos los datos necesarios
            if ($invoiceType === 'factura') {
                $validationErrors = $this->validateClientDataForInvoice($clientData);
                
                if (!empty($validationErrors)) {
                    // Guardar log del error
                    Log::create([
                        'user_id' => $userId,
                        'action' => 'Validación de factura fallida - Datos incompletos (ANTES del pago)',
                        'description' => 'No se puede generar la factura. Datos del cliente incompletos o incorrectos. El pago NO se procesará.',
                        'data' => [
                            'errors' => $validationErrors,
                            'invoice_type' => 'factura',
                            'user_id' => $userId,
                            'package_id' => $package->id,
                            'client_data' => [
                                'tipoDoc' => $clientData['tipoDoc'] ?? null,
                                'numDoc' => $clientData['numDoc'] ?? null,
                                'rznSocial' => $clientData['rznSocial'] ?? null,
                                'direccion' => $clientData['direccion'] ?? null,
                            ],
                            'message' => 'Por favor complete o corrija los datos del cliente para generar una factura.',
                        ],
                    ]);
                    
                    // Retornar error ANTES de procesar el pago
                    return response()->json([
                        'exito' => false,
                        'codMensaje' => 0,
                        'mensajeUsuario' => 'No se puede procesar la compra. Datos del cliente incompletos o incorrectos para generar la factura.',
                        'datoAdicional' => [
                            'errors' => $validationErrors,
                            'invoice_type' => 'factura',
                            'message' => 'Por favor complete o corrija los datos del cliente para generar una factura. El pago no se ha procesado.',
                        ]
                    ], 200);
                }
            }

            // Crear el UserPackage usando transacción
            DB::beginTransaction();
            try {
                // Asegurar que el usuario tenga un customer en Stripe
                if (!$user->stripe_id) {
                    $user->createAsStripeCustomer();
                }

                // Verificar que el paquete tenga IDs de Stripe
                if (!$package->stripe_product_id || !$package->stripe_price_id) {
                    // Crear el producto y precio en Stripe si no existen
                    $package->createStripeProduct();
                    $package->refresh();
                }

                $stripeSubscriptionId = null;
                $stripePaymentIntentId = null;
                $stripeInvoiceId = null;

                // Procesar pago en Stripe según el tipo de paquete
                if ($package->is_membresia) {
                    // Si es membresía, crear suscripción recurrente
                    try {
                        // Asegurar que el método de pago esté asociado al customer
                        $paymentMethod = $user->findPaymentMethod($stripePaymentMethodId);
                        if (!$paymentMethod) {
                            $user->addPaymentMethod($stripePaymentMethodId);
                        }

                        // Establecer el método de pago como predeterminado
                        $user->updateDefaultPaymentMethod($stripePaymentMethodId);

                        // Calcular precios con IGV para Stripe
                        $igvPercentage = (float) ($package->igv ?? 18);
                        $finalPriceWithIgv = $finalPrice * (1 + ($igvPercentage / 100));
                        $amountInCents = (int) round($finalPriceWithIgv * 100);

                        // Para membresías no se usan códigos promocionales, usar el precio normal
                        $stripePriceIdToUse = $package->stripe_price_id;

                        // Crear la suscripción con un nombre único basado en el package_id
                        $subscriptionName = 'package_' . $package->id;
                        $subscription = $user->newSubscription($subscriptionName, $stripePriceIdToUse)
                            ->create($stripePaymentMethodId);

                        $stripeSubscriptionId = $subscription->stripe_id;

                        // Actualizar metadata de la suscripción después de crearla
                        try {
                            $subscription->updateStripeSubscription([
                                'metadata' => [
                                    'package_id' => (string) $package->id,
                                    'package_name' => $package->name,
                                    'user_package_id' => null, // Se actualizará después
                                    'transaction_type' => 'subscription',
                                ],
                            ]);
                        } catch (\Exception $e) {
                            // Si falla actualizar metadata, no es crítico, continuar
                            Log::create([
                                'user_id' => $userId,
                                'action' => 'No se pudo agregar metadata inicial a la suscripción',
                                'description' => 'No se pudo agregar metadata inicial a la suscripción',
                                'data' => json_encode([
                                    'subscription_id' => $stripeSubscriptionId,
                                    'error' => $e->getMessage(),
                                ]),
                            ]);
                        }

                        // Obtener la factura inicial si existe
                        $stripeInvoice = $subscription->asStripeSubscription()->latest_invoice;
                        if ($stripeInvoice) {
                            $stripeInvoiceId = is_string($stripeInvoice) ? $stripeInvoice : $stripeInvoice->id;
                        }

                        Log::create([
                            'user_id' => $userId,
                            'action' => 'Suscripción de Stripe creada exitosamente',
                            'description' => 'Suscripción de Stripe creada exitosamente',
                            'data' => json_encode([
                                'package_id' => $package->id,
                                'subscription_id' => $stripeSubscriptionId,
                            ]),
                        ]);
                    } catch (\Exception $e) {
                        Log::create([
                            'user_id' => $userId,
                            'action' => 'Error al crear suscripción en Stripe',
                            'description' => 'Error al crear suscripción en Stripe',
                            'data' => json_encode([
                                'package_id' => $package->id,
                                'error' => $e->getMessage(),
                            ]),
                        ]);
                        throw new \Exception('Error al procesar el pago recurrente: ' . $e->getMessage());
                    }
                } else {
                    // Si no es membresía, crear pago único
                    try {
                        // Asegurar que el método de pago esté asociado al customer
                        $paymentMethod = $user->findPaymentMethod($stripePaymentMethodId);
                        if (!$paymentMethod) {
                            $user->addPaymentMethod($stripePaymentMethodId);
                        }

                        // Establecer el método de pago como predeterminado
                        $user->updateDefaultPaymentMethod($stripePaymentMethodId);

                        // Calcular precios con IGV para Stripe
                        $igvPercentage = (float) ($package->igv ?? 18); // IGV por defecto 18% si no está definido
                        $originalPriceWithIgv = $originalPrice * (1 + ($igvPercentage / 100));
                        $finalPriceWithIgv = $finalPrice * (1 + ($igvPercentage / 100));
                        $discountAmountWithIgv = $discountAmount * (1 + ($igvPercentage / 100));

                        // IMPORTANTE: VENTA ÚNICA CON DESCUENTO PROMOCIONAL
                        // El precio del producto en Stripe (stripe_price_id) NO se modifica.
                        // El descuento se aplica solo en esta transacción específica como una "venta única".
                        // Usamos el precio final CON descuento aplicado para el PaymentIntent.
                        // Esto asegura que el precio base del producto permanezca intacto.
                        $amountInCents = (int) round($finalPriceWithIgv * 100);

                        // Crear descripción detallada que incluya información del descuento si existe
                        $description = "Compra de paquete: {$package->name}";
                        if ($promoCodeUsed) {
                            $description .= " | VENTA ÚNICA con código promocional: {$promoCodeUsed} ({$discountPercentage}% desc.)";
                            $description .= " | Precio original del producto: S/ " . number_format($originalPriceWithIgv, 2);
                            $description .= " | Descuento aplicado: S/ " . number_format($discountAmountWithIgv, 2);
                            $description .= " | Precio final de esta venta: S/ " . number_format($finalPriceWithIgv, 2);
                        }

                        // Crear y confirmar un PaymentIntent directamente con el método de pago guardado
                        $stripeClient = $this->makeStripeClient();

                        // Crear el PaymentIntent con configuración para método de pago guardado (off_session)
                        // Especificamos explícitamente que solo usamos 'card' para evitar métodos de pago automáticos
                        // NOTA: Este es un pago único (one-time payment) con descuento promocional.
                        // El precio del producto en Stripe NO se modifica, solo se aplica el descuento en esta transacción.
                        $paymentIntentParams = [
                            'amount' => $amountInCents, // Monto final con descuento aplicado (venta única)
                            'currency' => 'pen',
                            'customer' => $user->stripe_id,
                            'payment_method' => $stripePaymentMethodId,
                            'payment_method_types' => ['card'], // Especificar explícitamente solo tarjetas
                            'confirmation_method' => 'automatic',
                            'confirm' => true,
                            'description' => $description,
                            'metadata' => [
                                'package_id' => (string) $package->id,
                                'package_name' => $package->name,
                                'user_package_id' => null, // Se actualizará después
                                'is_promo_sale' => $promoCodeUsed ? 'true' : 'false', // Indicar que es venta promocional
                                'sale_type' => $promoCodeUsed ? 'one_time_promo' : 'one_time_regular', // Tipo de venta
                                'promo_code' => $promoCodeUsed ?? '',
                                'discount_percentage' => (string) $discountPercentage,
                                'discount_amount' => (string) round($discountAmountWithIgv, 2),
                                'original_price' => (string) round($originalPriceWithIgv, 2), // Precio original del producto (sin modificar)
                                'final_price' => (string) round($finalPriceWithIgv, 2), // Precio final de esta venta única
                                'price_without_igv' => (string) round($finalPrice, 2),
                                'igv_percentage' => (string) $igvPercentage,
                                'igv_amount' => (string) round($finalPriceWithIgv - $finalPrice, 2),
                                'transaction_type' => $promoCodeUsed ? 'one_time_promo_package_purchase' : 'one_time_package_purchase',
                                'note' => $promoCodeUsed
                                    ? 'Venta única con descuento promocional. El precio del producto en Stripe no se modifica.'
                                    : 'Venta única regular. Precio del producto sin modificaciones.',
                            ],
                            'off_session' => true, // Indicar que el cliente no está presente (método guardado)
                            'return_url' => config('app.url') . '/payment/success', // URL de retorno por si requiere autenticación
                        ];

                        // Crear el PaymentIntent con la configuración específica
                        $paymentIntent = $stripeClient->paymentIntents->create($paymentIntentParams);

                        // Obtener el ID del PaymentIntent
                        $stripePaymentIntentId = $paymentIntent->id;

                        // CRÍTICO: Verificar el estado del PaymentIntent - SOLO aceptar 'succeeded'
                        // Si el pago no es exitoso, NO se creará el UserPackage
                        if ($paymentIntent->status === 'requires_action') {
                            throw new \Exception('El pago requiere autenticación adicional. Estado: ' . $paymentIntent->status . '. El paquete NO será creado.');
                        }

                        if ($paymentIntent->status === 'requires_payment_method') {
                            throw new \Exception('El método de pago no es válido o fue rechazado. Estado: ' . $paymentIntent->status . '. El paquete NO será creado.');
                        }

                        // CRÍTICO: Solo crear el UserPackage si el pago fue exitoso
                        if ($paymentIntent->status !== 'succeeded') {
                            throw new \Exception('El pago no se completó exitosamente. Estado: ' . $paymentIntent->status . '. El paquete NO será creado.');
                        }

                        // Obtener la factura si existe
                        if (isset($paymentIntent->invoice)) {
                            if (is_string($paymentIntent->invoice)) {
                                $stripeInvoiceId = $paymentIntent->invoice;
                            } elseif (is_object($paymentIntent->invoice)) {
                                $stripeInvoiceId = $paymentIntent->invoice->id ?? null;
                            }
                        }

                        // Si hay un charge asociado, también obtenerlo
                        if (isset($paymentIntent->charges->data[0])) {
                            $charge = $paymentIntent->charges->data[0];
                            if (isset($charge->id)) {
                                // El charge ID está disponible si se necesita
                            }
                        }

                        Log::create([
                            'user_id' => $userId,
                            'action' => 'Pago único de Stripe procesado exitosamente',
                            'description' => 'Pago único de Stripe procesado exitosamente',
                            'data' => json_encode([
                                'package_id' => $package->id,
                                'payment_intent_id' => $stripePaymentIntentId,
                            ]),
                        ]);
                    } catch (\Exception $e) {
                        Log::create([
                            'user_id' => $userId,
                            'action' => 'Error al procesar pago único en Stripe',
                            'description' => 'Error al procesar pago único en Stripe - NO se creará el paquete',
                            'data' => json_encode([
                                'package_id' => $package->id,
                                'error' => $e->getMessage(),
                            ]),
                        ]);
                        // Lanzar excepción para hacer rollback de la transacción
                        throw new \Exception('Error al procesar el pago en Stripe. El paquete NO será creado: ' . $e->getMessage());
                    }
                }

                // CRÍTICO: Validar que el pago fue exitoso antes de crear el UserPackage
                // Para membresías: debe tener stripe_subscription_id
                // Para paquetes únicos: debe tener stripe_payment_intent_id
                if ($package->is_membresia && !$stripeSubscriptionId) {
                    throw new \Exception('No se puede crear el paquete: la suscripción no fue creada exitosamente en Stripe');
                }
                
                if (!$package->is_membresia && !$stripePaymentIntentId) {
                    throw new \Exception('No se puede crear el paquete: el pago no fue procesado exitosamente en Stripe');
                }

                // Crear el UserPackage con los IDs de Stripe
                $userPackage = UserPackage::create([
                    'user_id' => $userId,
                    'package_id' => $package->id,
                    'remaining_classes' => $package->classes_quantity,
                    'used_classes' => 0,
                    'amount_paid_soles' => $originalPrice, // Precio original del paquete
                    'real_amount_paid_soles' => $finalPrice, // Monto real pagado (después de descuentos)
                    'original_package_price_soles' => $originalPrice,
                    'promo_code_used' => $promoCodeUsed,
                    'discount_percentage' => $discountPercentage,
                    'currency' => 'PEN',
                    'purchase_date' => now(),
                    'activation_date' => now(),
                    'expiry_date' => $expiryDate,
                    'status' => 'active',
                    'gift_order_id' => $giftOrderId, // Pedido de regalo si aplica
                    'stripe_subscription_id' => $stripeSubscriptionId,
                    'stripe_payment_intent_id' => $stripePaymentIntentId,
                    'stripe_invoice_id' => $stripeInvoiceId,
                    'stripe_customer_id' => $user->stripe_id,
                    'notes' => $request->notes ?? 'Compra realizada desde la aplicación',
                ]);

                // Actualizar los puntos creados anteriormente con el user_package_id
                if ($package->membership_id && $package->membership) {
                    \App\Models\UserPoint::where('user_id', $userId)
                        ->where('membresia_id', $package->membership_id)
                        ->whereNull('user_package_id')
                        ->where('package_id', $package->id)
                        ->orderBy('created_at', 'desc')
                        ->limit(1)
                        ->update(['user_package_id' => $userPackage->id]);
                }

                // Actualizar metadata de Stripe con el user_package_id
                if ($stripeSubscriptionId) {
                    try {
                        $subscriptionName = 'package_' . $package->id;
                        $subscription = $user->subscription($subscriptionName);
                        if ($subscription) {
                            $subscription->updateStripeSubscription([
                                'metadata' => [
                                    'package_id' => (string) $package->id,
                                    'user_package_id' => (string) $userPackage->id,
                                ],
                            ]);
                        }
                    } catch (\Exception $e) {
                        Log::create([
                            'user_id' => $userId,
                            'action' => 'No se pudo actualizar metadata de suscripción en Stripe',
                            'description' => 'No se pudo actualizar metadata de suscripción en Stripe',
                            'data' => json_encode([
                                'user_package_id' => $userPackage->id,
                                'subscription_id' => $stripeSubscriptionId,
                                'error' => $e->getMessage(),
                            ]),
                        ]);
                    }
                }

                // Registrar uso del código promocional si se usó
                if ($promoCodeUsed && $promoCodeData) {
                    $this->registerPromoCodeUsage($userId, $package->id, $promoCodeUsed, $promoCodeData);
                }

                DB::commit();

                // Cargar relaciones necesarias para el correo
                $userPackage->load(['package.disciplines']);

                // Verificar que el paquete existe antes de enviar el correo
                if ($userPackage->package && $user->email) {
                    // Enviar correo de confirmación de compra
                    try {
                        Mail::to($user->email)->send(new PackagePurchasedMailable($user, $userPackage));
                    } catch (\Exception $emailException) {
                        // Log del error pero no fallar la transacción
                        Log::create([
                            'user_id' => $userId,
                            'action' => 'Error al enviar correo de paquete comprado',
                            'description' => 'Error al enviar correo de paquete comprado',
                            'data' => json_encode([
                                'user_package_id' => $userPackage->id,
                                'user_email' => $user->email,
                                'error' => $emailException->getMessage(),
                                'trace' => $emailException->getTraceAsString(),
                            ]),
                        ]);
                    }
                } else {
                    // Log si no se puede enviar el correo por datos faltantes
                    Log::create([
                        'user_id' => $userId,
                        'action' => 'No se pudo enviar correo de paquete comprado',
                        'description' => 'Datos faltantes para enviar correo',
                        'data' => json_encode([
                            'user_package_id' => $userPackage->id,
                            'has_package' => $userPackage->package ? true : false,
                            'has_email' => $user->email ? true : false,
                        ]),
                    ]);
                }

                // Calcular precios con IGV para la respuesta
                $igvPercentage = (float) ($package->igv ?? 18); // IGV por defecto 18% si no está definido
                $originalPriceWithIgv = $userPackage->amount_paid_soles * (1 + ($igvPercentage / 100));
                $finalPriceWithIgv = $userPackage->real_amount_paid_soles * (1 + ($igvPercentage / 100));

                $responseData = [
                    'id' => $userPackage->id,
                    'package_code' => $userPackage->package_code,
                    'remaining_classes' => $userPackage->remaining_classes,
                    'expiry_date' => $userPackage->expiry_date->format('Y-m-d'),
                    'status' => $userPackage->status,
                    'package_name' => $package->name,
                    'pricing' => [
                        'original_price' => round($originalPriceWithIgv, 2),
                        'final_price' => round($finalPriceWithIgv, 2),
                        'discount_percentage' => $userPackage->discount_percentage,
                        'savings' => round($originalPriceWithIgv - $finalPriceWithIgv, 2),
                    ],
                    'promo_code' => $promoCodeUsed ? [
                        'code' => $promoCodeUsed,
                        'applied' => true
                    ] : null,
                ];

                // Generar comprobante electrónico (boleta o factura)
                // NOTA: La validación de datos ya se hizo ANTES del pago, así que aquí solo generamos
                try {
                    // Preparar items del comprobante
                    $items = $this->prepareItemsForInvoice($package, $finalPrice, $originalPrice, $promoCodeUsed, $discountPercentage);
                    
                    // Generar comprobante según el tipo solicitado
                    $sunatService = app(SunatServices::class);
                    
                    if ($invoiceType === 'factura') {
                        $invoiceData = $sunatService->generarFactura($clientData, $items, $userId, null, $userPackage->id);
                    } else {
                        // Por defecto: boleta
                        $invoiceData = $sunatService->generarBoleta($clientData, $items, $userId, null, $userPackage->id);
                    }
                    
                    // Si la generación fue exitosa, agregar a la respuesta
                    if (isset($invoiceData['success']) && $invoiceData['success'] === true) {
                        $responseData['invoice'] = [
                            'id' => $invoiceData['data']['id'] ?? null,
                            'serie' => $invoiceData['data']['serie'] ?? null,
                            'numero' => $invoiceData['data']['numero'] ?? null,
                            'numero_completo' => $invoiceData['data']['numero_completo'] ?? null,
                            'tipo' => $invoiceType,
                            'total' => $invoiceData['data']['total'] ?? null,
                            'enlace_pdf' => $invoiceData['data']['enlace_pdf'] ?? null,
                            'enlace_xml' => $invoiceData['data']['enlace_xml'] ?? null,
                            'aceptada_por_sunat' => $invoiceData['data']['aceptada_por_sunat'] ?? false,
                            'envio_estado' => $invoiceData['data']['envio_estado'] ?? null,
                            'procesado_instantaneo' => $invoiceData['data']['procesado_instantaneo'] ?? false,
                        ];
                    } else {
                        // Si falló la generación (pero no lanzó excepción), agregar información del error
                        $errorMessage = $invoiceData['error'] ?? $invoiceData['message'] ?? 'Error desconocido al generar comprobante';
                        
                        $responseData['invoice_error'] = [
                            'message' => $errorMessage,
                            'tipo' => $invoiceType,
                            'note' => 'La compra se completó exitosamente, pero la factura no pudo generarse. Se puede reintentar después.',
                        ];
                    }
                } catch (\Exception $invoiceException) {
                    // IMPORTANTE: Si la generación de factura falla DESPUÉS del pago exitoso,
                    // no revertimos la compra porque el pago ya se procesó en Stripe.
                    // La factura quedará pendiente y se puede reintentar después mediante
                    // el comando programado o manualmente desde el admin.
                    // NOTA: La validación de datos de factura se hace ANTES del pago.
                    
                    $errorMessage = $invoiceException->getMessage();
                    
                    // Intentar extraer mensaje de error más claro si viene de Nubefact/Greenter
                    if (strpos($errorMessage, 'sunat_description') !== false || 
                        strpos($errorMessage, 'nubefact') !== false ||
                        strpos($errorMessage, 'SUNAT') !== false) {
                        // El error ya viene formateado de Nubefact
                        $cleanMessage = $errorMessage;
                    } else {
                        $cleanMessage = "Error al generar {$invoiceType}: {$errorMessage}";
                    }
                    
                    Log::create([
                        'user_id' => $userId,
                        'action' => 'Error al generar comprobante electrónico (DESPUÉS del pago)',
                        'description' => 'Error al generar comprobante electrónico después de procesar el pago exitosamente',
                        'data' => [
                            'error' => $cleanMessage,
                            'invoice_type' => $invoiceType,
                            'user_package_id' => $userPackage->id,
                            'package_id' => $package->id,
                            'trace' => $invoiceException->getTraceAsString(),
                            'note' => 'El pago ya se procesó exitosamente en Stripe. La factura quedará pendiente y se puede reintentar después.',
                        ],
                    ]);
                    
                    // Agregar el error a la respuesta pero no fallar la compra
                    $responseData['invoice_error'] = [
                        'message' => $cleanMessage,
                        'tipo' => $invoiceType,
                        'note' => 'La compra se completó exitosamente, pero la factura no pudo generarse. Se puede reintentar después.',
                    ];
                }

                // Agregar información de membresía si se creó
                if ($membershipData) {
                    $responseData['membership'] = [
                        'id' => $membershipData->id,
                        'code' => $membershipData->code,
                        'membership_name' => $membershipData->membership->name ?? null,
                        'membership_level' => $membershipData->membership->level ?? null,
                        'discipline_id' => $membershipData->discipline_id,
                        'discipline_name' => $membershipData->discipline->name ?? null,
                        'total_free_classes' => $membershipData->total_free_classes,
                        'remaining_free_classes' => $membershipData->remaining_free_classes,
                        'total_free_shakes' => $membershipData->total_free_shakes,
                        'remaining_free_shakes' => $membershipData->remaining_free_shakes,
                        'expiry_date' => $membershipData->expiry_date?->format('Y-m-d'),
                        'status' => $membershipData->status,
                    ];
                }

                return response()->json([
                    'exito' => true,
                    'codMensaje' => 1,
                    'mensajeUsuario' => 'Paquete comprado exitosamente',
                    'datoAdicional' => $responseData
                ], 200);
            } catch (\Exception $e) {
                DB::rollback();
                throw $e;
            }
        } catch (\Illuminate\Validation\ValidationException $e) {

            Log::create([
                'user_id' => Auth::id(),
                'action' => 'Comprar/Agregar un paquete al usuario autenticado',
                'description' => 'Datos de entrada inválidos',
                'data' => $e->getMessage(),
            ]);

            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Datos de entrada inválidos',
                'datoAdicional' => $e->errors()
            ], 200);
        } catch (\Throwable $th) {

            Log::create([
                'user_id' => Auth::id(),
                'action' => 'Comprar/Agregar un paquete al usuario autenticado',
                'description' => 'Error al comprar el paquete',
                'data' => json_encode([
                    'error' => $th->getMessage(),
                    'trace' => $th->getTraceAsString(),
                ]),
            ]);

            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error al comprar el paquete',
                'datoAdicional' => $th->getMessage()
            ], 200);
        }
    }

    /**
     * Canjear un shake gratuito asociado a una membresía del usuario autenticado.
     */
    public function redeemMembershipShake(Request $request)
    {
        try {
            $request->validate([
                'user_membership_id' => 'required|integer|exists:user_memberships,id',
                'quantity' => 'sometimes|integer|min:1|max:10',
                'drink_id' => 'nullable|integer|exists:drinks,id',
                'drink_name' => 'sometimes|string|max:255',
                'drink_combination' => 'required|array|min:1',
                'ingredients_info' => 'sometimes|array',
                'special_instructions' => 'sometimes|string|max:500',
                'delivery_method' => 'sometimes|string|in:pickup,delivery',
            ]);

            /** @var \App\Models\User|null $user */
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'exito' => false,
                    'codMensaje' => 0,
                    'mensajeUsuario' => 'Usuario no autenticado',
                    'datoAdicional' => [
                        'reason' => 'unauthenticated'
                    ]
                ], 200);
            }

            $membership = UserMembership::where('id', $request->integer('user_membership_id'))
                ->where('user_id', $user->id)
                ->first();

            if (!$membership) {
                return response()->json([
                    'exito' => false,
                    'codMensaje' => 0,
                    'mensajeUsuario' => 'No se encontró la membresía indicada para este usuario',
                    'datoAdicional' => [
                        'reason' => 'membership_not_found'
                    ]
                ], 200);
            }

            if ($membership->status !== 'active') {
                return response()->json([
                    'exito' => false,
                    'codMensaje' => 0,
                    'mensajeUsuario' => 'La membresía no está activa',
                    'datoAdicional' => [
                        'reason' => 'membership_inactive',
                        'status' => $membership->status
                    ]
                ], 200);
            }

            if ($membership->expiry_date && $membership->expiry_date->isPast()) {
                return response()->json([
                    'exito' => false,
                    'codMensaje' => 0,
                    'mensajeUsuario' => 'La membresía se encuentra expirada',
                    'datoAdicional' => [
                        'reason' => 'membership_expired',
                        'expiry_date' => $membership->expiry_date->toDateString()
                    ]
                ], 200);
            }

            if ($membership->remaining_free_shakes <= 0) {
                return response()->json([
                    'exito' => false,
                    'codMensaje' => 0,
                    'mensajeUsuario' => 'No tienes shakes gratuitos por canjear',
                    'datoAdicional' => [
                        'reason' => 'no_shakes_available'
                    ]
                ], 200);
            }

            $quantity = $request->integer('quantity', 1);

            if ($quantity > $membership->remaining_free_shakes) {
                return response()->json([
                    'exito' => false,
                    'codMensaje' => 0,
                    'mensajeUsuario' => 'No tienes suficientes shakes pendientes para canjear esta cantidad',
                    'datoAdicional' => [
                        'reason' => 'insufficient_shakes',
                        'requested' => $quantity,
                        'available' => $membership->remaining_free_shakes
                    ]
                ], 200);
            }

            $drinkName = $request->string('drink_name')->value() ?: 'Shake Personalizado';
            $drinkCombination = $request->input('drink_combination', []);
            $ingredientsInfo = $request->input('ingredients_info', $drinkCombination);
            $specialInstructions = $request->string('special_instructions')->value();
            $deliveryMethod = $request->string('delivery_method')->value() ?: 'pickup';

            $drinkId = $request->integer('drink_id');

            $membership->loadMissing('membership');

            $result = DB::transaction(function () use (
                $user,
                $membership,
                $quantity,
                $drinkName,
                $drinkCombination,
                $ingredientsInfo,
                $specialInstructions,
                $deliveryMethod,
                $drinkId
            ) {
                $lockedMembership = UserMembership::where('id', $membership->id)
                    ->where('user_id', $user->id)
                    ->lockForUpdate()
                    ->first();

                if (!$lockedMembership) {
                    throw new \RuntimeException('membership_not_found');
                }

                if ($lockedMembership->remaining_free_shakes < $quantity) {
                    throw new \RuntimeException('insufficient_shakes');
                }

                $order = \App\Models\JuiceOrder::create([
                    'user_id' => $user->id,
                    'user_name' => trim($user->name ?? '') ?: $user->email,
                    'user_email' => $user->email,
                    'subtotal_soles' => 0,
                    'tax_amount_soles' => 0,
                    'discount_amount_soles' => 0,
                    'total_amount_soles' => 0,
                    'currency' => 'PEN',
                    'status' => 'pending',
                    'payment_status' => 'paid',
                    'delivery_method' => $deliveryMethod,
                    'notes' => sprintf(
                        'Canje de shake gratuito por membresía %s (ID: %d)',
                        $lockedMembership->membership->name ?? 'N/A',
                        $lockedMembership->id
                    ),
                    'special_instructions' => $specialInstructions,
                    'payment_method_name' => 'Beneficio de membresía',
                    'is_membership_redeem' => true,
                    'user_membership_id' => $lockedMembership->id,
                    'redeemed_shakes_quantity' => $quantity,
                ]);

                $order->details()->create([
                    'drink_id' => $drinkId,
                    'quantity' => $quantity,
                    'drink_name' => $drinkName,
                    'drink_combination' => $drinkCombination,
                    'unit_price_soles' => 0,
                    'total_price_soles' => 0,
                    'special_instructions' => $specialInstructions,
                    'ingredients_info' => $ingredientsInfo,
                ]);

                if (!$lockedMembership->useFreeShakes($quantity)) {
                    throw new \RuntimeException('consume_failed');
                }

                $order->load('details');
                $lockedMembership->refresh();

                return [
                    'order' => $order,
                    'membership' => $lockedMembership,
                ];
            });

            /** @var \App\Models\JuiceOrder $order */
            $order = $result['order'];
            /** @var UserMembership $membership */
            $membership = $result['membership'];

            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Shake canjeado exitosamente',
                'datoAdicional' => [
                    'juice_order' => [
                        'id' => $order->id,
                        'order_number' => $order->order_number,
                        'status' => $order->status,
                        'delivery_method' => $order->delivery_method,
                        'quantity' => $quantity,
                        'drink_name' => $drinkName,
                        'drink_combination' => $drinkCombination,
                        'special_instructions' => $specialInstructions,
                        'is_membership_redeem' => (bool) $order->is_membership_redeem,
                    ],
                    'membership' => [
                        'id' => $membership->id,
                        'total_free_shakes' => $membership->total_free_shakes,
                        'used_free_shakes' => $membership->used_free_shakes,
                        'remaining_free_shakes' => $membership->remaining_free_shakes,
                        'redeemed_quantity' => $quantity,
                        'expiry_date' => $membership->expiry_date?->format('Y-m-d'),
                    ]
                ]
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {


            Log::create([
                'user_id' => Auth::id(),
                'action' => 'Canjear un shake gratuito asociado a una membresía del usuario autenticado.',
                'description' => 'Datos de entrada inválidos',
                'data' => $e->getMessage(),
            ]);


            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Datos de entrada inválidos',
                'datoAdicional' => $e->errors()
            ], 200);
        } catch (\RuntimeException $e) {
            $reason = $e->getMessage();

            if ($reason === 'insufficient_shakes') {

                Log::create([
                    'user_id' => Auth::id(),
                    'action' => 'Canjear un shake gratuito asociado a una membresía del usuario autenticado.',
                    'description' => 'La cantidad de shakes solicitada ya no está disponible',
                    'data' => $e->getMessage(),
                ]);

                return response()->json([
                    'exito' => false,
                    'codMensaje' => 0,
                    'mensajeUsuario' => 'La cantidad de shakes solicitada ya no está disponible',
                    'datoAdicional' => [
                        'reason' => $reason,
                    ]
                ], 200);
            }

            if ($reason === 'membership_not_found') {
                return response()->json([
                    'exito' => false,
                    'codMensaje' => 0,
                    'mensajeUsuario' => 'La membresía seleccionada ya no está disponible',
                    'datoAdicional' => [
                        'reason' => $reason,
                    ]
                ], 200);
            }

            if ($reason === 'consume_failed') {
                return response()->json([
                    'exito' => false,
                    'codMensaje' => 0,
                    'mensajeUsuario' => 'No se pudo aplicar el canje de la membresía',
                    'datoAdicional' => [
                        'reason' => $reason,
                    ]
                ], 200);
            }

            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'No se pudo completar el canje del shake',
                'datoAdicional' => [
                    'reason' => $reason,
                ]
            ], 200);
        } catch (\Throwable $e) {
            Log::create([
                'user_id' => Auth::id(),
                'action' => 'Canjear un shake gratuito asociado a una membresía del usuario autenticado.',
                'description' => 'Error interno al canjear shake',
                'data' => $e->getMessage(),
            ]);
            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error interno al canjear shake',
                'datoAdicional' => $e->getMessage()
            ], 200);
        }
    }

    /**
     * Obtener las membresías activas del usuario autenticado
     *
     */
    public function myMemberships()
    {
        try {
            $userId = Auth::id();

            $memberships = UserMembership::with(['membership', 'discipline'])
                ->where('user_id', $userId)
                ->where('status', 'active')
                ->where('expiry_date', '>', now())
                ->where('remaining_free_classes', '>', 0)
                ->orderBy('expiry_date', 'asc')
                ->get();

            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Membresías obtenidas exitosamente',
                'datoAdicional' => $memberships->map(function ($membership) {
                    return [
                        'id' => $membership->id,
                        'membership_name' => $membership->membership->name,
                        'discipline_name' => $membership->discipline->name,
                        'discipline_id' => $membership->discipline_id,
                        'total_free_classes' => $membership->total_free_classes,
                        'used_free_classes' => $membership->used_free_classes,
                        'remaining_free_classes' => $membership->remaining_free_classes,
                        'total_free_shakes' => $membership->total_free_shakes,
                        'used_free_shakes' => $membership->used_free_shakes,
                        'remaining_free_shakes' => $membership->remaining_free_shakes,
                        'activation_date' => $membership->activation_date->format('Y-m-d'),
                        'expiry_date' => $membership->expiry_date->format('Y-m-d'),
                        'days_remaining' => $membership->days_remaining,
                        'status' => $membership->status,
                        'source_package_name' => $membership->sourcePackage->name ?? 'N/A',
                    ];
                })
            ], 200);
        } catch (\Throwable $e) {

            Log::create([
                'user_id' => Auth::id(),
                'action' => 'Obtener las membresías activas del usuario autenticado',
                'description' => 'Error al obtener las membresías',
                'data' => $e->getMessage(),
            ]);

            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error al obtener las membresías',
                'datoAdicional' => $e->getMessage()
            ], 200);
        }
    }

    /**
     * Clases restantes del usuario (paquetes + membresías)
     */

    public function packageMeVigent(Request $request)
    {
        try {
            // Validar parámetros opcionales
            $request->validate([
                'discipline_id' => 'sometimes',
                'discipline_group' => 'sometimes|string'
            ]);

            $user = request()->user();

            if (!$user) {
                return response()->json([
                    'exito' => false,
                    'codMensaje' => 0,
                    'mensajeUsuario' => 'Usuario no autenticado',
                    'datoAdicional' => null
                ], 200);
            }

            $userId = $user->id;

            $disciplineIds = collect();

            if ($request->filled('discipline_group')) {
                $disciplineIds = collect(explode('-', $request->string('discipline_group')))
                    ->filter()
                    ->filter(fn($value) => is_numeric($value))
                    ->map(fn($value) => (int) $value)
                    ->unique()
                    ->values();
            } elseif ($request->filled('discipline_id')) {
                $rawDiscipline = $request->input('discipline_id');

                if (is_array($rawDiscipline)) {
                    $disciplineIds = collect($rawDiscipline);
                } else {
                    $disciplineIds = collect(preg_split('/[,\-]/', (string) $rawDiscipline));
                }

                $disciplineIds = $disciplineIds
                    ->filter()
                    ->filter(fn($value) => is_numeric($value))
                    ->map(fn($value) => (int) $value)
                    ->unique()
                    ->values();
            }

            $hasDisciplineFilter = $disciplineIds->isNotEmpty();

            // Obtener paquetes activos y vigentes del usuario
            $userPackagesQuery = $user->userPackages()
                ->with(['package.disciplines'])
                ->where('status', 'active')
                ->where('remaining_classes', '>', 0)
                ->where('expiry_date', '>', now()) // Solo paquetes vigentes
                ->where('activation_date', '<=', now()); // Solo paquetes activados

            // Aplicar filtro por disciplina si se proporciona
            if ($hasDisciplineFilter) {
                $disciplineIds->each(function (int $disciplineId) use ($userPackagesQuery) {
                    $userPackagesQuery->whereHas('package.disciplines', function ($query) use ($disciplineId) {
                        $query->where('disciplines.id', $disciplineId);
                    });
                });

                if ($request->filled('discipline_group')) {
                    $userPackagesQuery->whereDoesntHave('package.disciplines', function ($query) use ($disciplineIds) {
                        $query->whereNotIn('disciplines.id', $disciplineIds->all());
                    });
                }
            }

            $userPackages = $userPackagesQuery->get();

            // Obtener membresías activas y vigentes del usuario
            $userMembershipsQuery = $user->userMemberships()
                ->with(['membership', 'discipline'])
                ->where('status', 'active')
                ->where('remaining_free_classes', '>', 0)
                ->where('expiry_date', '>', now()) // Solo membresías vigentes
                ->where('activation_date', '<=', now()); // Solo membresías activadas

            // Aplicar filtro por disciplina si se proporciona
            if ($hasDisciplineFilter) {
                $userMembershipsQuery->whereIn('discipline_id', $disciplineIds->all());
            }

            $userMemberships = $userMembershipsQuery->get();

            // Combinar y formatear los datos
            $classesData = [];

            // Agregar paquetes
            foreach ($userPackages as $userPackage) {
                $disciplines = $userPackage->package->disciplines->map(function ($discipline) {
                    return [
                        'id' => $discipline->id,
                        'name' => $discipline->name,
                        'display_name' => $discipline->display_name,
                        'icon_url' => $discipline->icon_url ? asset('storage/' . $discipline->icon_url) : null,
                        'color_hex' => $discipline->color_hex,
                    ];
                });

                $classesData[] = [
                    'id' => $userPackage->id,
                    'type' => 'package',
                    'name' => $userPackage->package->name,
                    'disciplines' => $disciplines,
                    'primary_discipline' => $disciplines->first() ? [
                        'id' => $disciplines->first()['id'],
                        'name' => $disciplines->first()['name'],
                        'display_name' => $disciplines->first()['display_name'],
                    ] : null,
                    'remaining_classes' => $userPackage->remaining_classes,
                    'total_classes' => $userPackage->package->classes_quantity,
                    'remaining_shakes' => 0,
                    'total_shakes' => 0,
                    'expiry_date' => $userPackage->expiry_date->format('d \d\e F \d\e Y'),
                    'expiry_date_raw' => $userPackage->expiry_date->format('Y-m-d'),
                    'days_remaining' => $userPackage->days_remaining,
                    'is_expired' => $userPackage->is_expired,
                    'package_code' => $userPackage->package_code,
                ];
            }

            // Agregar membresías
            foreach ($userMemberships as $userMembership) {
                $classesData[] = [
                    'id' => $userMembership->id,
                    'type' => 'membership',
                    'name' => $userMembership->membership->name ?? 'Membresía',
                    'discipline_id' => $userMembership->discipline_id,
                    'discipline_name' => $userMembership->discipline->name ?? 'Sin disciplina',
                    'remaining_classes' => $userMembership->remaining_free_classes,
                    'total_classes' => $userMembership->total_free_classes,
                    'remaining_shakes' => $userMembership->remaining_free_shakes,
                    'total_shakes' => $userMembership->total_free_shakes,
                    'expiry_date' => $userMembership->expiry_date->format('d \d\e F \d\e Y'),
                    'expiry_date_raw' => $userMembership->expiry_date->format('Y-m-d'),
                    'days_remaining' => $userMembership->days_remaining,
                    'is_expired' => $userMembership->is_expired,
                    'package_code' => null, // Las membresías no tienen código de paquete
                ];
            }

            // Ordenar por fecha de expiración (más cercanos a vencer primero)
            usort($classesData, function ($a, $b) {
                return strtotime($a['expiry_date_raw']) - strtotime($b['expiry_date_raw']);
            });

            // Calcular estadísticas
            $totalClassesAvailable = array_sum(array_column($classesData, 'remaining_classes'));
            $totalShakesAvailable = array_sum(array_column($classesData, 'remaining_shakes'));
            $totalPackages = count(array_filter($classesData, fn($item) => $item['type'] === 'package'));
            $totalMemberships = count(array_filter($classesData, fn($item) => $item['type'] === 'membership'));

            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Clases restantes obtenidas exitosamente',
                'datoAdicional' => [
                    'classes' => $classesData,
                    'summary' => [
                        'total_classes_available' => $totalClassesAvailable,
                        'total_shakes_available' => $totalShakesAvailable,
                        'total_packages' => $totalPackages,
                        'total_memberships' => $totalMemberships,
                        'filtered_by_discipline' => $hasDisciplineFilter,
                        'discipline_ids' => $hasDisciplineFilter ? $disciplineIds->all() : null,
                    ]
                ]
            ], 200);
        } catch (\Throwable $e) {

            Log::create([
                'user_id' => Auth::id(),
                'action' => 'Clases restantes del usuario (paquetes + membresías)',
                'description' => 'Error al obtener clases restantes',
                'data' => $e->getMessage(),
            ]);


            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error al obtener clases restantes',
                'datoAdicional' => $e->getMessage()
            ], 200);
        }
    }

    /**
     * Validar y aplicar código promocional
     */
    private function validateAndApplyPromoCode(string $code, int $packageId, int $userId): array
    {
        try {
            // Buscar el código promocional
            $promoCode = \App\Models\PromoCodes::where('code', $code)
                ->where('status', 'active')
                ->first();

            if (!$promoCode) {
                return [
                    'valid' => false,
                    'message' => 'Código promocional no válido o inactivo'
                ];
            }

            // Validar fechas para códigos estacionales
            if ($promoCode->type === 'season') {
                $now = now();
                if ($promoCode->start_date && $now->lt($promoCode->start_date)) {
                    return [
                        'valid' => false,
                        'message' => 'Código promocional aún no está vigente'
                    ];
                }
                if ($promoCode->end_date && $now->gt($promoCode->end_date)) {
                    return [
                        'valid' => false,
                        'message' => 'Código promocional expirado'
                    ];
                }
            }

            // Verificar si el usuario ya usó este código
            $alreadyUsed = $promoCode->users()
                ->where('user_id', $userId)
                ->exists();

            if ($alreadyUsed) {
                return [
                    'valid' => false,
                    'message' => 'Ya has usado este código promocional anteriormente'
                ];
            }

            // Verificar si el código aplica al paquete
            $packageDiscount = $promoCode->packages()
                ->where('package_id', $packageId)
                ->where('quantity', '>', 0)
                ->first();

            if (!$packageDiscount) {
                return [
                    'valid' => false,
                    'message' => 'Este código promocional no es válido para el paquete seleccionado'
                ];
            }

            // Calcular descuento
            $packageModel = \App\Models\Package::find($packageId);
            $originalPrice = $packageModel->price_soles;
            $discount = (float) $packageDiscount->discount_percentage;
            $discountAmount = ($originalPrice * $discount) / 100;
            $finalPrice = $originalPrice - $discountAmount;

            // Calcular precios con IGV para la respuesta
            $igvPercentage = (float) ($packageModel->igv ?? 18); // IGV por defecto 18% si no está definido
            $originalPriceWithIgv = $originalPrice * (1 + ($igvPercentage / 100));
            $finalPriceWithIgv = $finalPrice * (1 + ($igvPercentage / 100));
            $discountAmountWithIgv = $discountAmount * (1 + ($igvPercentage / 100));

            return [
                'valid' => true,
                'code' => $promoCode->code,
                'data' => [
                    'original_price' => round($originalPriceWithIgv, 2),
                    'discount_percentage' => $discount,
                    'discount_amount' => round($discountAmountWithIgv, 2),
                    'final_price' => round($finalPriceWithIgv, 2),
                    'package_discount_id' => $packageDiscount->id,
                    'available_quantity' => $packageDiscount->quantity
                ]
            ];
        } catch (\Exception $e) {
            Log::create([
                'user_id' => Auth::id(),
                'action' => 'Validar y aplicar código promocional',
                'description' => 'Error al validar el código promocional',
                'data' => $e->getMessage(),
            ]);

            return [
                'valid' => false,
                'message' => 'Error al validar el código promocional'
            ];
        }
    }

    /**
     * Registrar uso del código promocional
     */
    private function registerPromoCodeUsage(int $userId, int $packageId, string $promoCode, array $promoCodeData): void
    {
        try {
            $promoCodeModel = \App\Models\PromoCodes::where('code', $promoCode)->first();

            if (!$promoCodeModel) {
                throw new \Exception('Código promocional no encontrado');
            }

            // Decrementar cantidad disponible
            $promoCodeModel->packages()->updateExistingPivot($packageId, [
                'quantity' => DB::raw('quantity - 1')
            ]);

            // Registrar uso del código por el usuario
            $promoCodeModel->users()->attach($userId, [
                'monto' => $promoCodeData['final_price'],
                'package_id' => $packageId,
                'discount_applied' => $promoCodeData['discount_percentage'],
                'original_price' => $promoCodeData['original_price'],
                'final_price' => $promoCodeData['final_price'],
                'created_at' => now(),
                'updated_at' => now()
            ]);

            Log::create([
                'user_id' => $userId,
                'action' => 'Código promocional aplicado en compra de paquete',
                'description' => 'Código promocional aplicado en compra de paquete',
                'data' => json_encode([
                    'promo_code' => $promoCode,
                    'package_id' => $packageId,
                    'discount' => $promoCodeData['discount_percentage'],
                    'final_price' => $promoCodeData['final_price'],
                ]),
            ]);
        } catch (\Exception $e) {
            Log::create([
                'user_id' => Auth::id(),
                'action' => 'Registrar uso del código promocional',
                'description' => 'Error registrar uso del código promocional',
                'data' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Crear pedido de regalo de shakes para membresía
     */
    private function createGiftShakeOrder(int $userId, $user, int $shakeQuantity, string $packageName): ?int
    {
        try {
            // Crear el pedido de regalo
            $giftOrder = \App\Models\JuiceOrder::create([
                'user_id' => $userId,
                'user_name' => $user->name,
                'user_email' => $user->email,
                'subtotal_soles' => 0,
                'tax_amount_soles' => 0,
                'discount_amount_soles' => 0,
                'total_amount_soles' => 0, // Gratis
                'currency' => 'PEN',
                'status' => 'pending',
                'payment_status' => 'paid', // Ya viene pagado como regalo
                'delivery_method' => 'pickup',
                'notes' => "Shakes de regalo por compra del paquete: {$packageName} (Cantidad: {$shakeQuantity})",
                'payment_method_name' => 'Regalo por Membresía',
                'estimated_ready_at' => now()->addMinutes(5),
            ]);

            // Crear detalles del pedido (shakes genéricos)
            for ($i = 0; $i < $shakeQuantity; $i++) {
                $giftOrder->details()->create([
                    'drink_id' => null, // No hay drink específico, es un regalo genérico
                    'quantity' => 1,
                    'drink_name' => 'Shake de Regalo',
                    'drink_combination' => 'Shake gratuito incluido con tu membresía',
                    'unit_price_soles' => 0,
                    'total_price_soles' => 0,
                    'ingredients_info' => [
                        'bases' => ['Shake de Regalo'],
                        'flavors' => ['Sabor a elección'],
                        'types' => ['Gratuito']
                    ]
                ]);
            }

            Log::create([
                'user_id' => $userId,
                'action' => 'Pedido de regalo de shakes creado',
                'description' => 'Pedido de regalo de shakes creado',
                'data' => json_encode([
                    'order_id' => $giftOrder->id,
                    'shake_quantity' => $shakeQuantity,
                    'package_name' => $packageName,
                ]),
            ]);

            return $giftOrder->id;
        } catch (\Exception $e) {

            Log::create([
                'user_id' => Auth::id(),
                'action' => 'Crear pedido de regalo de shakes para membresía',
                'description' => 'Error crear pedido de regalo de shakes para membresía',
                'data' => json_encode([
                    'error' => $e->getMessage(),
                ]),
            ]);
            return null;
        }
    }

    /**
     * Lista las suscripciones activas del usuario autenticado
     */
    public function mySubscriptions(Request $request)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'exito' => false,
                    'codMensaje' => 0,
                    'mensajeUsuario' => 'Usuario no autenticado',
                    'datoAdicional' => []
                ], 200);
            }

            // Verificar que el usuario tenga un customer en Stripe
            if (!$user->stripe_id) {
                return response()->json([
                    'exito' => true,
                    'codMensaje' => 1,
                    'mensajeUsuario' => 'Suscripciones obtenidas exitosamente',
                    'datoAdicional' => [
                        'subscriptions' => [],
                        'total_count' => 0
                    ]
                ], 200);
            }

            // Obtener todas las suscripciones del usuario desde Stripe
            $stripeClient = $this->makeStripeClient();
            $stripeSubscriptions = $stripeClient->subscriptions->all([
                'customer' => $user->stripe_id,
                'status' => 'all', // Obtener todas: active, past_due, canceled, etc.
            ]);

            // Transformar las suscripciones al formato esperado
            $subscriptions = [];
            foreach ($stripeSubscriptions->data as $stripeSubscription) {
                // Obtener el UserPackage relacionado si existe
                $userPackage = UserPackage::where('stripe_subscription_id', $stripeSubscription->id)
                    ->with(['package'])
                    ->first();

                // Obtener información del precio/producto
                $price = null;
                $product = null;
                if (isset($stripeSubscription->items->data[0]->price)) {
                    $price = $stripeSubscription->items->data[0]->price;
                    if (isset($price->product)) {
                        $productId = is_string($price->product) ? $price->product : $price->product->id;
                        try {
                            $product = $stripeClient->products->retrieve($productId);
                        } catch (\Exception $e) {
                            // Si no se puede obtener el producto, continuar
                        }
                    }
                }

                $subscriptions[] = [
                    'id' => $stripeSubscription->id,
                    'status' => $stripeSubscription->status,
                    'status_display' => $this->getSubscriptionStatusDisplay($stripeSubscription->status),
                    'current_period_start' => date('Y-m-d H:i:s', $stripeSubscription->current_period_start),
                    'current_period_end' => date('Y-m-d H:i:s', $stripeSubscription->current_period_end),
                    'cancel_at_period_end' => $stripeSubscription->cancel_at_period_end,
                    'canceled_at' => $stripeSubscription->canceled_at ? date('Y-m-d H:i:s', $stripeSubscription->canceled_at) : null,
                    'ended_at' => $stripeSubscription->ended_at ? date('Y-m-d H:i:s', $stripeSubscription->ended_at) : null,
                    'quantity' => $stripeSubscription->items->data[0]->quantity ?? 1,
                    'price' => [
                        'id' => $price->id ?? null,
                        'amount' => $price->unit_amount ? ($price->unit_amount / 100) : null,
                        'currency' => $price->currency ?? 'pen',
                        'recurring' => isset($price->recurring) ? [
                            'interval' => $price->recurring->interval ?? null,
                            'interval_count' => $price->recurring->interval_count ?? 1,
                        ] : null,
                    ],
                    'product' => $product ? [
                        'id' => $product->id,
                        'name' => $product->name,
                        'description' => $product->description,
                    ] : null,
                    'metadata' => $stripeSubscription->metadata->toArray(),
                    'package' => $userPackage ? [
                        'id' => $userPackage->id,
                        'package_id' => $userPackage->package_id,
                        'package_name' => $userPackage->package->name ?? null,
                        'remaining_classes' => $userPackage->remaining_classes,
                        'status' => $userPackage->status,
                    ] : null,
                ];
            }

            // Ordenar: activas primero, luego por fecha de creación descendente
            usort($subscriptions, function ($a, $b) {
                $statusOrder = ['active' => 1, 'trialing' => 2, 'past_due' => 3, 'canceled' => 4, 'unpaid' => 5];
                $aOrder = $statusOrder[$a['status']] ?? 99;
                $bOrder = $statusOrder[$b['status']] ?? 99;

                if ($aOrder !== $bOrder) {
                    return $aOrder - $bOrder;
                }

                return strtotime($b['current_period_start']) - strtotime($a['current_period_start']);
            });

            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Suscripciones obtenidas exitosamente',
                'datoAdicional' => [
                    'subscriptions' => $subscriptions,
                    'total_count' => count($subscriptions),
                    'active_count' => count(array_filter($subscriptions, fn($s) => $s['status'] === 'active')),
                ]
            ], 200);

        } catch (\Throwable $e) {
            Log::create([
                'user_id' => Auth::id(),
                'action' => 'Listar suscripciones del usuario',
                'description' => 'Error al obtener suscripciones',
                'data' => json_encode([
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]),
            ]);

            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error al obtener suscripciones',
                'datoAdicional' => $e->getMessage()
            ], 200);
        }
    }

    /**
     * Cancela una suscripción del usuario autenticado
     */
    public function cancelSubscription(Request $request)
    {
        try {
            $request->validate([
                'subscription_id' => 'required|string', // ID de Stripe de la suscripción
                'cancel_immediately' => 'nullable|boolean', // Si true, cancela inmediatamente. Si false, cancela al final del período
            ]);

            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'exito' => false,
                    'codMensaje' => 0,
                    'mensajeUsuario' => 'Usuario no autenticado',
                    'datoAdicional' => []
                ], 200);
            }

            $subscriptionId = $request->input('subscription_id');
            $cancelImmediately = $request->boolean('cancel_immediately', false);

            // Verificar que el usuario tenga un customer en Stripe
            if (!$user->stripe_id) {
                return response()->json([
                    'exito' => false,
                    'codMensaje' => 0,
                    'mensajeUsuario' => 'El usuario no tiene un customer en Stripe',
                    'datoAdicional' => []
                ], 200);
            }

            // Buscar la suscripción usando el tipo correcto (package_X)
            // Primero intentar encontrar por el nombre de suscripción
            $subscription = null;
            $subscriptionName = null;

            // Buscar en todas las suscripciones del usuario
            $allSubscriptions = $user->subscriptions;
            foreach ($allSubscriptions as $sub) {
                if ($sub->stripe_id === $subscriptionId) {
                    $subscription = $sub;
                    $subscriptionName = $sub->name;
                    break;
                }
            }

            // Si no se encuentra, buscar directamente en Stripe
            if (!$subscription) {
                $stripeClient = $this->makeStripeClient();
                try {
                    $stripeSubscription = $stripeClient->subscriptions->retrieve($subscriptionId);

                    // Verificar que la suscripción pertenece al usuario
                    if ($stripeSubscription->customer !== $user->stripe_id) {
                        return response()->json([
                            'exito' => false,
                            'codMensaje' => 0,
                            'mensajeUsuario' => 'La suscripción no pertenece a este usuario',
                            'datoAdicional' => []
                        ], 200);
                    }

                    // Intentar encontrar en Cashier por el nombre
                    // El nombre puede ser 'package_X' basado en cómo lo creamos
                    $metadata = $stripeSubscription->metadata->toArray();
                    if (isset($metadata['package_id'])) {
                        $subscriptionName = 'package_' . $metadata['package_id'];
                        $subscription = $user->subscription($subscriptionName);
                    }
                } catch (\Exception $e) {
                    return response()->json([
                        'exito' => false,
                        'codMensaje' => 0,
                        'mensajeUsuario' => 'La suscripción no existe en Stripe',
                        'datoAdicional' => $e->getMessage()
                    ], 200);
                }
            }

            // Si no se encontró la suscripción en Cashier, cancelar directamente en Stripe
            if (!$subscription) {
                $stripeClient = $this->makeStripeClient();

                if ($cancelImmediately) {
                    // Cancelar inmediatamente
                    $stripeSubscription = $stripeClient->subscriptions->cancel($subscriptionId);
                } else {
                    // Cancelar al final del período actual
                    $stripeSubscription = $stripeClient->subscriptions->update($subscriptionId, [
                        'cancel_at_period_end' => true,
                    ]);
                }

                Log::create([
                    'user_id' => $user->id,
                    'action' => 'Suscripción cancelada en Stripe',
                    'description' => 'Suscripción cancelada directamente en Stripe',
                    'data' => json_encode([
                        'subscription_id' => $subscriptionId,
                        'cancel_immediately' => $cancelImmediately,
                        'status' => $stripeSubscription->status,
                    ]),
                ]);

                return response()->json([
                    'exito' => true,
                    'codMensaje' => 1,
                    'mensajeUsuario' => $cancelImmediately
                        ? 'Suscripción cancelada exitosamente'
                        : 'Suscripción programada para cancelarse al final del período',
                    'datoAdicional' => [
                        'subscription_id' => $subscriptionId,
                        'status' => $stripeSubscription->status,
                        'cancel_at_period_end' => $stripeSubscription->cancel_at_period_end,
                        'canceled_at' => $stripeSubscription->canceled_at ? date('Y-m-d H:i:s', $stripeSubscription->canceled_at) : null,
                        'current_period_end' => date('Y-m-d H:i:s', $stripeSubscription->current_period_end),
                    ]
                ], 200);
            }

            // Cancelar usando Cashier
            if ($cancelImmediately) {
                // Cancelar inmediatamente
                $subscription->cancelNow();
                $message = 'Suscripción cancelada exitosamente';
            } else {
                // Cancelar al final del período actual
                $subscription->cancel();
                $message = 'Suscripción programada para cancelarse al final del período actual';
            }

            // Obtener información actualizada de la suscripción
            $stripeSubscription = $subscription->asStripeSubscription();

            // Actualizar el UserPackage relacionado si existe
            $userPackage = UserPackage::where('stripe_subscription_id', $subscriptionId)->first();
            if ($userPackage) {
                if ($cancelImmediately) {
                    $userPackage->update(['status' => 'cancelled']);
                } else {
                    // Marcar como pendiente de cancelación
                    $userPackage->update(['status' => 'active']); // Mantener activo hasta que termine el período
                }
            }

            Log::create([
                'user_id' => $user->id,
                'action' => 'Suscripción cancelada',
                'description' => $cancelImmediately ? 'Suscripción cancelada inmediatamente' : 'Suscripción programada para cancelarse',
                'data' => json_encode([
                    'subscription_id' => $subscriptionId,
                    'subscription_name' => $subscriptionName,
                    'cancel_immediately' => $cancelImmediately,
                    'status' => $stripeSubscription->status,
                    'user_package_id' => $userPackage->id ?? null,
                ]),
            ]);

            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => $message,
                'datoAdicional' => [
                    'subscription_id' => $subscriptionId,
                    'status' => $stripeSubscription->status,
                    'cancel_at_period_end' => $stripeSubscription->cancel_at_period_end,
                    'canceled_at' => $stripeSubscription->canceled_at ? date('Y-m-d H:i:s', $stripeSubscription->canceled_at) : null,
                    'current_period_end' => date('Y-m-d H:i:s', $stripeSubscription->current_period_end),
                    'ends_at' => $subscription->ends_at ? $subscription->ends_at->format('Y-m-d H:i:s') : null,
                ]
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::create([
                'user_id' => Auth::id(),
                'action' => 'Cancelar suscripción',
                'description' => 'Datos de entrada inválidos',
                'data' => json_encode([
                    'errors' => $e->errors(),
                ]),
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
                'action' => 'Cancelar suscripción',
                'description' => 'Error al cancelar suscripción',
                'data' => json_encode([
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]),
            ]);

            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error al cancelar suscripción',
                'datoAdicional' => $e->getMessage()
            ], 200);
        }
    }

    /**
     * Obtiene el texto de estado de la suscripción
     */
    private function getSubscriptionStatusDisplay(string $status): string
    {
        return match($status) {
            'active' => 'Activa',
            'trialing' => 'Período de Prueba',
            'past_due' => 'Pago Vencido',
            'canceled' => 'Cancelada',
            'unpaid' => 'No Pagada',
            'incomplete' => 'Incompleta',
            'incomplete_expired' => 'Incompleta Expirada',
            'paused' => 'Pausada',
            default => ucfirst($status),
        };
    }

    /**
     * Crea un cliente de Stripe
     */
    private function makeStripeClient(): StripeClient
    {
        $secret = config('services.stripe.secret');

        if (!$secret) {
            throw new \RuntimeException('Stripe no está configurado correctamente. Falta services.stripe.secret.');
        }

        return new StripeClient($secret);
    }

    /**
     * Preparar datos del cliente para el comprobante electrónico
     */
    private function prepareClientDataForInvoice($user, string $invoiceType = 'boleta', ?Request $request = null): array
    {
        $profile = $user->profile;
        
        // Para facturas, todos los campos son obligatorios
        if ($invoiceType === 'factura') {
            // Para factura, SIEMPRE usar RUC (tipo 6)
            $documentType = '6'; // RUC obligatorio para facturas
            
            // Si el request tiene datos de facturación, usarlos (vienen del frontend)
            if ($request && $request->has('invoice_data')) {
                $invoiceData = $request->input('invoice_data');
                
                return [
                    'tipoDoc' => $documentType, // Siempre '6' para facturas
                    'numDoc' => $invoiceData['ruc'] ?? '',
                    'rznSocial' => $invoiceData['razon_social'] ?? '',
                    'direccion' => $invoiceData['direccion_fiscal'] ?? '',
                    'email' => $invoiceData['email'] ?? $user->email ?? '',
                ];
            }
            
            // Fallback: intentar obtener de la BD (por si acaso)
            $documentNumber = $user->document_number ?? '';
            $razonSocial = $user->business_name ?? $user->name ?? '';
            
            if (empty($razonSocial) && $profile && $profile->full_name) {
                $razonSocial = $profile->full_name;
            }
            
            $direccion = $profile->fiscal_address ?? $profile->adress ?? '';
            
            return [
                'tipoDoc' => $documentType,
                'numDoc' => $documentNumber,
                'rznSocial' => $razonSocial,
                'direccion' => $direccion,
                'email' => $user->email ?? '',
            ];
        }
        
        // Para boletas, los campos pueden ser opcionales
        $documentType = '1'; // DNI por defecto
        $documentNumber = $user->document_number ?? '';
        
        // Si es empresa y tiene RUC
        if ($user->is_company && $user->document_number && strlen($user->document_number) === 11) {
            $documentType = '6'; // RUC
        } elseif ($user->document_type) {
            // Mapear tipos de documento comunes
            $documentTypeMap = [
                'dni' => '1',
                'ce' => '4', // Carné de extranjería
                'ruc' => '6',
            ];
            $documentType = $documentTypeMap[strtolower($user->document_type)] ?? '1';
        }
        
        // Obtener nombre o razón social
        $razonSocial = $user->business_name ?? $user->name ?? $user->email ?? 'Cliente';
        
        // Si tiene perfil, usar nombre completo
        if ($profile && $profile->full_name) {
            $razonSocial = $profile->full_name;
        }
        
        // Obtener dirección fiscal si existe
        $direccion = $profile->fiscal_address ?? $profile->adress ?? null;
        
        return [
            'tipoDoc' => $documentType,
            'numDoc' => $documentNumber,
            'rznSocial' => $razonSocial,
            'direccion' => $direccion,
            'email' => $user->email,
        ];
    }

    /**
     * Validar datos del cliente para factura
     */
    private function validateClientDataForInvoice(array $clientData): array
    {
        $errors = [];
        
        // Validar tipo de documento (debe ser RUC = 6)
        if (empty($clientData['tipoDoc']) || $clientData['tipoDoc'] !== '6') {
            $errors[] = 'El tipo de documento debe ser RUC (6) para facturas';
        }
        
        // Validar número de documento (RUC debe tener 11 dígitos)
        $ruc = trim($clientData['numDoc'] ?? '');
        if (empty($ruc)) {
            $errors[] = 'El número de documento (RUC) es obligatorio para facturas';
        } elseif (strlen($ruc) !== 11) {
            $errors[] = 'El RUC debe tener exactamente 11 dígitos';
        } elseif (!preg_match('/^[0-9]{11}$/', $ruc)) {
            $errors[] = 'El RUC debe contener solo números';
        }
        
        // Validar razón social
        $razonSocial = trim($clientData['rznSocial'] ?? '');
        if (empty($razonSocial)) {
            $errors[] = 'La razón social es obligatoria para facturas';
        } elseif (strlen($razonSocial) < 3) {
            $errors[] = 'La razón social debe tener al menos 3 caracteres';
        }
        
        // Validar dirección
        $direccion = trim($clientData['direccion'] ?? '');
        if (empty($direccion)) {
            $errors[] = 'La dirección fiscal es obligatoria para facturas';
        } elseif (strlen($direccion) < 5) {
            $errors[] = 'La dirección fiscal debe tener al menos 5 caracteres';
        }
        
        // Validar email (opcional pero recomendado)
        $email = trim($clientData['email'] ?? '');
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'El correo electrónico no es válido';
        }
        
        return $errors;
    }

    /**
     * Preparar items del comprobante desde el paquete
     */
    private function prepareItemsForInvoice(Package $package, float $finalPrice, float $originalPrice, ?string $promoCodeUsed, float $discountPercentage): array
    {
        $igvPercentage = (float) ($package->igv ?? 18);
        
        // Calcular precio sin IGV
        $finalPriceSinIgv = $finalPrice;
        $mtoValorUnitario = $finalPriceSinIgv;
        $mtoPrecioUnitario = $finalPriceSinIgv * (1 + ($igvPercentage / 100));
        
        // Descripción del producto
        $descripcion = $package->name;
        if ($package->description) {
            $descripcion .= ' - ' . $package->description;
        }
        if ($promoCodeUsed) {
            $descripcion .= " (Código promocional: {$promoCodeUsed} - {$discountPercentage}% desc.)";
        }
        
        return [
            [
                'codProducto' => 'PKG-' . str_pad($package->id, 6, '0', STR_PAD_LEFT),
                'unidad' => 'NIU',
                'cantidad' => 1,
                'mtoValorUnitario' => round($mtoValorUnitario, 2),
                'descripcion' => $descripcion,
                'mtoPrecioUnitario' => round($mtoPrecioUnitario, 2),
            ],
        ];
    }
}
