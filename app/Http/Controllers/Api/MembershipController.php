<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Log;
use App\Models\UserMembership;
use App\Models\UserPackage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MembershipController extends Controller
{
    /**
     * Obtener la membresía activa del usuario (solo la de mayor nivel vigente)
     */
    public function getMyMemberships(Request $request): JsonResponse
    {
        try {
            $userId = Auth::id();

            // Obtener la membresía activa vigente con mayor nivel (level)
            $membership = UserMembership::with(['membership', 'discipline'])
                ->where('user_id', $userId)
                ->where('status', 'active')
                ->where(function ($query) {
                    $query->whereNull('expiry_date')
                        ->orWhere('expiry_date', '>', now());
                })
                ->join('memberships', 'user_memberships.membership_id', '=', 'memberships.id')
                ->orderBy('memberships.level', 'desc') // Mayor nivel primero
                ->orderBy('user_memberships.expiry_date', 'asc') // Si mismo nivel, la que expira antes
                ->select('user_memberships.*')
                ->first();

            if (!$membership) {
                return response()->json([
                    'exito' => true,
                    'codMensaje' => 0,
                    'mensajeUsuario' => 'No tienes una membresía vigente',
                    'datoAdicional' => [
                        'has_membership' => false,
                        'membership' => null
                    ]
                ], 200);
            }

            // Cargar los datos de la membresía
            $membership->load('membership', 'discipline', 'sourcePackage');

            // Obtener información del usuario y su perfil
            $user = Auth::user();
            $userProfile = $user->profile;

            $formattedMembership = [
                'id' => $membership->id,
                'code' => $membership->code, // Código de membresía (formato tarjeta: XXXX-XXXX-XXXX-XXXX)
                'code_digits' => $membership->code_digits, // Código sin guiones
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'code' => $user->code,
                    'birth_date' => $userProfile?->birth_date ? $userProfile->birth_date->format('Y-m-d') : null,
                    'birth_date_formatted' => $userProfile?->birth_date ? $userProfile->birth_date->format('d/m/Y') : null,
                ],
                'membership' => [
                    'id' => $membership->membership->id ?? null,
                    'name' => $membership->membership->name ?? 'N/A',
                    'level' => $membership->membership->level ?? null,
                    'slug' => $membership->membership->slug ?? null,
                    'description' => $membership->membership->description ?? null,
                    'icon' => $membership->membership->icon ?? null,
                    'color_hex' => $membership->membership->color_hex ?? null,
                    'colors' => $membership->membership->colors ?? null, // Array de colores
                    'is_active' => $membership->membership->is_active ?? null,
                ],
                'discipline' => [
                    'id' => $membership->discipline->id ?? null,
                    'name' => $membership->discipline->name ?? 'N/A',
                ],
                'free_classes' => [
                    'total' => $membership->total_free_classes,
                    'used' => $membership->used_free_classes,
                    'remaining' => $membership->remaining_free_classes,
                    'percentage_used' => $membership->total_free_classes > 0
                        ? round(($membership->used_free_classes / $membership->total_free_classes) * 100, 2)
                        : 0,
                ],
                'dates' => [
                    'activation_date' => $membership->activation_date ? $membership->activation_date->format('Y-m-d') : null,
                    'expiry_date' => $membership->expiry_date ? $membership->expiry_date->format('Y-m-d') : null,
                    'days_remaining' => $membership->days_remaining,
                ],
                'status' => [
                    'code' => $membership->status,
                    'display' => $membership->status_display_name,
                    'is_valid' => $membership->is_valid,
                    'is_expired' => $membership->is_expired,
                    'has_free_classes' => $membership->has_free_classes,
                ],
            ];

            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Membresía obtenida exitosamente',
                'datoAdicional' => [
                    'has_membership' => true,
                    'membership' => $formattedMembership,
                ]
            ], 200);
        } catch (\Throwable $e) {

            Log::create([
                'user_id' => Auth::id(),
                'action' => 'Obtener la membresía activa del usuario (solo la de mayor nivel vigente)',
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
     * Verificar si la membresía está vigente para una disciplina específica
     */
    public function checkMembershipStatus(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'discipline_id' => 'required|integer|exists:disciplines,id'
            ]);

            $userId = Auth::id();
            $disciplineId = $request->integer('discipline_id');

            // Verificar si tiene membresía vigente para esta disciplina
            $membership = UserMembership::with(['membership', 'discipline'])
                ->where('user_id', $userId)
                ->where('discipline_id', $disciplineId)
                ->where('status', 'active')
                ->where(function ($query) {
                    $query->whereNull('expiry_date')
                        ->orWhere('expiry_date', '>', now());
                })
                ->where('remaining_free_classes', '>', 0)
                ->orderBy('remaining_free_classes', 'desc')
                ->orderBy('expiry_date', 'asc')
                ->first();

            if (!$membership) {
                return response()->json([
                    'exito' => true,
                    'codMensaje' => 0,
                    'mensajeUsuario' => 'No tienes membresía vigente para esta disciplina',
                    'datoAdicional' => [
                        'has_membership' => false,
                        'discipline_id' => $disciplineId,
                    ]
                ], 200);
            }

            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Tienes membresía vigente',
                'datoAdicional' => [
                    'has_membership' => true,
                    'discipline' => [
                        'id' => $membership->discipline->id ?? null,
                        'name' => $membership->discipline->name ?? 'N/A',
                    ],
                    'membership' => [
                        'id' => $membership->membership->id ?? null,
                        'name' => $membership->membership->name ?? 'N/A',
                        'level' => $membership->membership->level ?? null,
                    ],
                    'free_classes_remaining' => $membership->remaining_free_classes,
                    'free_classes_total' => $membership->total_free_classes,
                    'free_classes_used' => $membership->used_free_classes,
                    'expiry_date' => $membership->expiry_date->format('Y-m-d'),
                    'days_remaining' => $membership->days_remaining,
                    'is_valid' => $membership->is_valid,
                ]
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {

            Log::create([
                'user_id' => Auth::id(),
                'action' => 'Verificar si la membresía está vigente para una disciplina específica',
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
                'action' => 'Verificar si la membresía está vigente para una disciplina específica',
                'description' => 'Error al verificar el estado de la membresía',
                'data' => $e->getMessage(),
            ]);

            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error al verificar el estado de la membresía',
                'datoAdicional' => $e->getMessage()
            ], 200);
        }
    }

    /**
     * Obtener un resumen completo de membresías y paquetes del usuario
     */
    public function getMembershipSummary(Request $request): JsonResponse
    {
        try {
            $userId = Auth::id();

            // Obtener todas las membresías (activas e inactivas)
            $allMemberships = UserMembership::with(['membership', 'discipline'])
                ->where('user_id', $userId)
                ->get();

            $activeMemberships = $allMemberships->where('status', 'active')
                ->where(function ($m) {
                    return $m->expiry_date === null || $m->expiry_date->isFuture();
                })
                ->values();

            // Obtener paquetes activos
            $activePackages = UserPackage::with(['package.disciplines'])
                ->where('user_id', $userId)
                ->where('status', 'active')
                ->where('expiry_date', '>', now())
                ->where('remaining_classes', '>', 0)
                ->get();

            // Resumen de membresías
            $membershipsSummary = [
                'total' => $allMemberships->count(),
                'active' => $activeMemberships->count(),
                'expired' => $allMemberships->where('status', 'expired')->count(),
                'by_discipline' => $activeMemberships->groupBy('discipline_id')->map(function ($ms) {
                    return [
                        'discipline_name' => $ms->first()->discipline->name ?? 'N/A',
                        'count' => $ms->count(),
                        'total_free_classes' => $ms->sum('remaining_free_classes'),
                    ];
                })->values(),
            ];

            // Resumen de paquetes
            $packagesSummary = [
                'total' => $activePackages->count(),
                'total_classes_remaining' => $activePackages->sum('remaining_classes'),
                'total_classes_used' => $activePackages->sum('used_classes'),
                'by_package' => $activePackages->map(function ($userPackage) {
                    return [
                        'package_name' => $userPackage->package->name ?? 'N/A',
                        'remaining_classes' => $userPackage->remaining_classes,
                        'used_classes' => $userPackage->used_classes,
                    ];
                }),
            ];

            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Resumen de membresías obtenido exitosamente',
                'datoAdicional' => [
                    'memberships' => $membershipsSummary,
                    'packages' => $packagesSummary,
                    'total_free_classes_available' => $activeMemberships->sum('remaining_free_classes'),
                    'total_paid_classes_available' => $activePackages->sum('remaining_classes'),
                ]
            ], 200);
        } catch (\Throwable $e) {

            Log::create([
                'user_id' => Auth::id(),
                'action' => 'Verificar si la membresía está vigente para una disciplina específica',
                'description' => 'Error al obtener el resumen de membresías',
                'data' => $e->getMessage(),
            ]);

            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error al obtener el resumen de membresías',
                'datoAdicional' => $e->getMessage()
            ], 200);
        }
    }
}
