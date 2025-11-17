<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PackageResource;
use App\Http\Resources\UserPackageResource;
use App\Models\Package;
use App\Models\UserPackage;
use App\Models\UserMembership;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
            $request->validate([
                'package_id' => 'required|integer|exists:packages,id',
                'payment_method_id' => 'required|integer|exists:user_payment_methods,id',
                'promo_code' => 'nullable|string|max:50',
                'notes' => 'nullable|string|max:500',
            ]);

            $userId = Auth::id();

            // Verificar que el método de pago pertenece al usuario
            $paymentMethod = \App\Models\UserPaymentMethod::where('id', $request->payment_method_id)
                ->where('user_id', $userId)
                ->where('status', 'active')
                ->first();

            if (!$paymentMethod) {
                return response()->json([
                    'exito' => false,
                    'codMensaje' => 0,
                    'mensajeUsuario' => 'Método de pago no válido',
                    'datoAdicional' => 'El método de pago no existe o no está activo'
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

            // Validar y aplicar código promocional si se proporciona
            $promoCodeData = null;
            $finalPrice = $package->price_soles;
            $originalPrice = $package->price_soles;
            $discountPercentage = 0;
            $discountAmount = 0;
            $promoCodeUsed = null;

            if ($request->filled('promo_code')) {
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
                $finalPrice = $promoCodeData['final_price'];
                $originalPrice = $promoCodeData['original_price'];
                $discountPercentage = $promoCodeData['discount_percentage'];
                $discountAmount = $promoCodeData['discount_amount'];
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

                // Si la membresía tiene beneficios de shake, crear pedido de regalo
                // El canje de shakes se realiza bajo demanda; aquí solo se registran las cantidades disponibles.
            }

            // Crear el UserPackage usando transacción
            DB::beginTransaction();
            try {
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
                    'notes' => $request->notes ?? 'Compra realizada desde la aplicación',
                ]);

                // Registrar uso del código promocional si se usó
                if ($promoCodeUsed && $promoCodeData) {
                    $this->registerPromoCodeUsage($userId, $package->id, $promoCodeUsed, $promoCodeData);
                }

                // Aquí podrías agregar lógica de procesamiento de pago
                // Por ejemplo, integrar con un gateway de pago como Culqi, PayU, etc.

                DB::commit();

                $responseData = [
                    'id' => $userPackage->id,
                    'package_code' => $userPackage->package_code,
                    'remaining_classes' => $userPackage->remaining_classes,
                    'expiry_date' => $userPackage->expiry_date->format('Y-m-d'),
                    'status' => $userPackage->status,
                    'package_name' => $package->name,
                    'pricing' => [
                        'original_price' => $userPackage->amount_paid_soles,
                        'final_price' => $userPackage->real_amount_paid_soles,
                        'discount_percentage' => $userPackage->discount_percentage,
                        'savings' => $userPackage->amount_paid_soles - $userPackage->real_amount_paid_soles,
                    ],
                    'promo_code' => $promoCodeUsed ? [
                        'code' => $promoCodeUsed,
                        'applied' => true
                    ] : null,
                ];

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
                'data' => $e->getMessage(),
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
            $originalPrice = \App\Models\Package::find($packageId)->price_soles;
            $discount = (float) $packageDiscount->discount_percentage;
            $discountAmount = ($originalPrice * $discount) / 100;
            $finalPrice = $originalPrice - $discountAmount;

            return [
                'valid' => true,
                'code' => $promoCode->code,
                'data' => [
                    'original_price' => $originalPrice,
                    'discount_percentage' => $discount,
                    'discount_amount' => $discountAmount,
                    'final_price' => $finalPrice,
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

            Log::info('Código promocional aplicado en compra de paquete', [
                'user_id' => $userId,
                'promo_code' => $promoCode,
                'package_id' => $packageId,
                'discount' => $promoCodeData['discount_percentage'],
                'final_price' => $promoCodeData['final_price']
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

            Log::info('Pedido de regalo de shakes creado', [
                'user_id' => $userId,
                'order_id' => $giftOrder->id,
                'shake_quantity' => $shakeQuantity,
                'package_name' => $packageName
            ]);

            return $giftOrder->id;
        } catch (\Exception $e) {

            Log::create([
                'user_id' => Auth::id(),
                'action' => 'Crear pedido de regalo de shakes para membresía',
                'description' => 'Error crear pedido de regalo de shakes para membresía',
                'data' => $e->getMessage(),
            ]);
            return null;
        }
    }
}
