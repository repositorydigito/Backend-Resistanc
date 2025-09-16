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
                'discipline_id' => 'sometimes|integer|exists:disciplines,id',
                'mode_type' => 'sometimes|string|in:online,presencial,híbrido', // Ajusta según tus valores
                'commercial_type' => 'sometimes|string|in:promotion,regular', // Ajusta según tus valores
                'per_page' => 'sometimes|integer|min:1|max:100',
                'page' => 'sometimes|integer|min:1',
            ]);

            $packages = Package::query()
                ->with(['discipline', 'membership'])
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
                ->when($request->filled('discipline_id'), function ($query) use ($request) {
                    $query->where('discipline_id', $request->integer('discipline_id'));
                })
                ->when($request->filled('mode_type'), function ($query) use ($request) {
                    $query->where('mode_type', $request->string('mode_type'));
                })
                ->when($request->filled('commercial_type'), function ($query) use ($request) {
                    $query->where('commercial_type', $request->string('commercial_type'));
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
        } catch (\Throwable $th) {
            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Fallo al listar paquetes',
                'datoAdicional' => $th->getMessage(),
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
                ->with(['discipline', 'membership'])
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
        } catch (\Throwable $th) {
            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Fallo al obtener paquete',
                'datoAdicional' => $th->getMessage(),
            ], 200);
        }
    }
    /**
     * Mis paquetes
     */
    public function packageMe(Request $request)
    {

        $request->validate([
            'discipline_id' => 'sometimes|exists:disciplines,id'
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
                ->with(['package:id,name,slug,description,classes_quantity,price_soles', 'package.discipline:id,name,slug']);

            // Filtro por disciplina (nuevo)
            if ($request->filled('discipline_id')) {
                $query->whereHas('package', function ($q) use ($request) {
                    $q->where('discipline_id', $request->integer('discipline_id'));
                });
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
        } catch (\Throwable $th) {
            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error al obtener los paquetes del usuario',
                'datoAdicional' => $th->getMessage()
            ], 200);
        }
    }

    /**
     * Comprar/Agregar un paquete al usuario autenticado
     *
     */
    public function packageMeCreate(Request $request)
    {
        try {
            // Validar datos de entrada
            $request->validate([
                'package_id' => 'required|integer|exists:packages,id',
                'payment_method_id' => 'required|integer|exists:user_payment_methods,id',
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



            // Calcular fecha de expiración
            $expiryDate = $package->duration_in_months
                ? now()->addMonths($package->duration_in_months)
                : now()->addDays($package->validity_days ?? 30);

            // Crear el UserPackage usando transacción
            DB::beginTransaction();
            try {
                $userPackage = UserPackage::create([
                    'user_id' => $userId,
                    'package_id' => $package->id,
                    'remaining_classes' => $package->classes_quantity,
                    'used_classes' => 0,
                    'amount_paid_soles' => $package->price_soles,
                    'currency' => 'PEN',
                    'purchase_date' => now(),
                    'activation_date' => now(),
                    'expiry_date' => $expiryDate,
                    'status' => 'active',
                    'notes' => $request->notes ?? 'Compra realizada desde la aplicación',
                ]);

                // Verificar si el paquete tiene una membresía asociada con clases gratis
                $membershipData = null;
                if ($package->membership_id && $package->membership) {
                    $membership = $package->membership;

                    // Si la membresía tiene beneficios de disciplina (clases gratis)
                    if (
                        $membership->is_benefit_discipline &&
                        $membership->discipline_id &&
                        $membership->discipline_quantity > 0
                    ) {

                        // Calcular fecha de expiración de la membresía basada en su duración
                        $membershipExpiryDate = now()->addMonths($membership->duration);

                        // Crear UserMembership para las clases gratis
                        $membershipData = UserMembership::create([
                            'user_id' => $userId,
                            'membership_id' => $membership->id,
                            'discipline_id' => $membership->discipline_id,
                            'total_free_classes' => $membership->discipline_quantity,
                            'used_free_classes' => 0,
                            'remaining_free_classes' => $membership->discipline_quantity,
                            'activation_date' => now(),
                            'expiry_date' => $membershipExpiryDate, // Fecha de expiración basada en la duración de la membresía
                            'status' => 'active',
                            'source_package_id' => $package->id,
                            'notes' => "Clases gratis otorgadas por la compra del paquete: {$package->name} (Duración membresía: {$membership->duration} meses)",
                        ]);
                    }
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
                    'amount_paid' => $userPackage->amount_paid_soles,
                ];

                // Agregar información de membresía si se creó
                if ($membershipData) {
                    $responseData['membership'] = [
                        'id' => $membershipData->id,
                        'membership_name' => $membershipData->membership->name,
                        'discipline_name' => $membershipData->discipline->name,
                        'total_free_classes' => $membershipData->total_free_classes,
                        'remaining_free_classes' => $membershipData->remaining_free_classes,
                        'expiry_date' => $membershipData->expiry_date->format('Y-m-d'),
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
            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Datos de entrada inválidos',
                'datoAdicional' => $e->errors()
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error al comprar el paquete',
                'datoAdicional' => $th->getMessage()
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
                        'activation_date' => $membership->activation_date->format('Y-m-d'),
                        'expiry_date' => $membership->expiry_date->format('Y-m-d'),
                        'days_remaining' => $membership->days_remaining,
                        'status' => $membership->status,
                        'source_package_name' => $membership->sourcePackage->name ?? 'N/A',
                    ];
                })
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error al obtener las membresías',
                'datoAdicional' => $th->getMessage()
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
                'discipline_id' => 'sometimes|integer|exists:disciplines,id'
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
            $disciplineId = $request->integer('discipline_id');

            // Obtener paquetes activos y vigentes del usuario
            $userPackagesQuery = $user->userPackages()
                ->with(['package.discipline'])
                ->where('status', 'active')
                ->where('remaining_classes', '>', 0)
                ->where('expiry_date', '>', now()) // Solo paquetes vigentes
                ->where('activation_date', '<=', now()); // Solo paquetes activados

            // Aplicar filtro por disciplina si se proporciona
            if ($disciplineId) {
                $userPackagesQuery->whereHas('package', function ($query) use ($disciplineId) {
                    $query->where('discipline_id', $disciplineId);
                });
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
            if ($disciplineId) {
                $userMembershipsQuery->where('discipline_id', $disciplineId);
            }

            $userMemberships = $userMembershipsQuery->get();

            // Combinar y formatear los datos
            $classesData = [];

            // Agregar paquetes
            foreach ($userPackages as $userPackage) {
                $classesData[] = [
                    'id' => $userPackage->id,
                    'type' => 'package',
                    'name' => $userPackage->package->name,
                    'discipline_id' => $userPackage->package->discipline_id,
                    'discipline_name' => $userPackage->package->discipline->name ?? 'Sin disciplina',
                    'remaining_classes' => $userPackage->remaining_classes,
                    'total_classes' => $userPackage->package->classes_quantity,
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
                        'total_packages' => $totalPackages,
                        'total_memberships' => $totalMemberships,
                        'filtered_by_discipline' => $disciplineId ? true : false,
                        'discipline_id' => $disciplineId,
                    ]
                ]
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error al obtener clases restantes',
                'datoAdicional' => $th->getMessage()
            ], 200);
        }
    }
}
