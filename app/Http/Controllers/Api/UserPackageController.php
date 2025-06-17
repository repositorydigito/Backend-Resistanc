<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserPackageRequest;
use App\Http\Resources\UserPackageResource;
use App\Models\UserPackage;
use App\Services\PackageValidationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * @tags Mis Paquetes
 */
class UserPackageController extends Controller
{
    /**
     * Lista todos los paquetes del usuario autenticado
     *
     * Obtiene una lista paginada de paquetes del usuario autenticado con filtros opcionales por estado.
     * **Requiere autenticación:** Incluye el token Bearer en el header Authorization.
     *
     * **Ruta:** GET /api/me/packages
     *
     * @summary Listar mis paquetes
     * @operationId getMyPackagesList
     * @authenticated
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     *
     * @queryParam per_page integer Número de paquetes por página (máximo 50). Example: 15
     * @queryParam page integer Número de página para la paginación. Example: 1
     * @queryParam status string Filtrar por estado (pending, active, expired, cancelled, suspended). Example: active
     * @queryParam search string Buscar por código de paquete o notas. Example: PKG001
     * @queryParam expiring_soon boolean Mostrar solo paquetes que expiran pronto (próximos 7 días). Example: true
     * @queryParam expired boolean Mostrar solo paquetes expirados. Example: false
     *
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "user_id": 1,
     *       "package_id": 2,
     *       "package_code": "PKG001-2024",
     *       "total_classes": 10,
     *       "used_classes": 3,
     *       "remaining_classes": 7,
     *       "amount_paid_soles": 299.00,
     *       "currency": "PEN",
     *       "purchase_date": "2024-01-15",
     *       "activation_date": "2024-01-16",
     *       "expiry_date": "2024-03-16",
     *       "status": "active",
     *       "auto_renew": false,
     *       "renewal_price": null,
     *       "benefits_included": ["Acceso a todas las máquinas", "Asesoría personalizada"],
     *       "notes": "Paquete inicial del usuario",
     *       "status_display_name": "Activo",
     *       "is_expired": false,
     *       "is_valid": true,
     *       "package": {
     *         "id": 2,
     *         "name": "PAQUETE10R",
     *         "slug": "paquete10r",
     *         "description": "Paquete de 10 clases de resistencia",
     *         "classes_quantity": 10,
     *         "price_soles": 299.00,
     *         "validity_days": 60
     *       },
     *       "created_at": "2024-01-15T10:30:00.000Z",
     *       "updated_at": "2024-01-15T10:30:00.000Z"
     *     }
     *   ],
     *   "links": {
     *     "first": "http://backend-resistanc.test/api/me/packages?page=1",
     *     "last": "http://backend-resistanc.test/api/me/packages?page=3",
     *     "prev": null,
     *     "next": "http://backend-resistanc.test/api/me/packages?page=2"
     *   },
     *   "meta": {
     *     "current_page": 1,
     *     "from": 1,
     *     "last_page": 3,
     *     "per_page": 15,
     *     "to": 15,
     *     "total": 42
     *   }
     * }
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = UserPackage::query()
            ->where('user_id', Auth::id())
            ->with(['package:id,name,slug,description,classes_quantity,price_soles,validity_days']);

        // Filtros opcionales
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($q) use ($search) {
                $q->where('package_code', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        if ($request->boolean('expiring_soon')) {
            $query->expiringSoon();
        }

        if ($request->boolean('expired')) {
            $query->expired();
        }

        // Si no se especifica un estado, mostrar solo activos por defecto
        if (!$request->filled('status') && !$request->boolean('expired')) {
            $query->where('status', 'active');
        }

        $userPackages = $query
            ->latest()
            ->paginate(
                perPage: min($request->integer('per_page', 15), 50),
                page: $request->integer('page', 1)
            );

        return UserPackageResource::collection($userPackages);
    }

    /**
     * Crea un nuevo paquete para el usuario autenticado
     *
     * Registra un nuevo paquete de usuario con información completa incluyendo fechas, precios y configuraciones.
     * Genera automáticamente un código único para el paquete y calcula las clases restantes.
     * **Requiere autenticación:** Incluye el token Bearer en el header Authorization.
     *
     * **Ruta:** POST /api/me/packages
     *
     * @summary Crear nuevo paquete para mi usuario
     * @operationId createMyPackage
     * @authenticated
     *
     * @param  \App\Http\Requests\StoreUserPackageRequest  $request
     * @return \App\Http\Resources\UserPackageResource
     *
     * @response 201 {
     *   "id": 1,
     *   "user_id": 1,
     *   "package_id": 2,
     *   "package_code": "PKG001-2024",
     *   "total_classes": 10,
     *   "used_classes": 0,
     *   "remaining_classes": 10,
     *   "amount_paid_soles": "299.00",
     *   "currency": "PEN",
     *   "purchase_date": "2024-01-15",
     *   "activation_date": "2024-01-16",
     *   "expiry_date": "2024-03-16",
     *   "status": "active",
     *   "auto_renew": false,
     *   "renewal_price": null,
     *   "benefits_included": ["Acceso a todas las máquinas", "Asesoría personalizada"],
     *   "notes": "Paquete promocional de inicio",
     *   "status_display_name": "Activo",
     *   "is_expired": false,
     *   "is_valid": true,
     *   "package": {
     *     "id": 2,
     *     "name": "PAQUETE10R",
     *     "slug": "paquete10r",
     *     "description": "Paquete de 10 clases de resistencia para nivel intermedio",
     *     "classes_quantity": 10,
     *     "price_soles": "299.00",
     *     "validity_days": 60
     *   },
     *   "created_at": "2024-01-15T10:30:00.000Z",
     *   "updated_at": "2024-01-15T10:30:00.000Z"
     * }
     *
     * @response 422 {
     *   "message": "Los datos proporcionados no son válidos.",
     *   "errors": {
     *     "package_id": ["El paquete seleccionado no existe."],
     *     "total_classes": ["El número de clases debe ser mayor a 0."],
     *     "amount_paid_soles": ["El monto debe ser mayor o igual a 0."],
     *     "purchase_date": ["La fecha de compra no puede ser futura."],
     *     "expiry_date": ["La fecha de expiración debe ser posterior a la fecha de compra."]
     *   }
     * }
     */
    public function store(StoreUserPackageRequest $request): UserPackageResource
    {
        $validated = $request->validated();

        return DB::transaction(function () use ($validated) {
            // Generate unique package code
            $packageCode = $this->generateUniquePackageCode();

            // Calculate remaining classes (initially all classes are available)
            $remainingClasses = $validated['total_classes'];

            // Convert dates to proper format if they come with timezone
            $purchaseDate = Carbon::parse($validated['purchase_date'])->toDateString();
            $activationDate = $validated['activation_date'] ? Carbon::parse($validated['activation_date'])->toDateString() : null;
            $expiryDate = Carbon::parse($validated['expiry_date'])->toDateString();

            // Create user package for the authenticated user
            $userPackage = UserPackage::create([
                'user_id' => Auth::id(), // Always use the authenticated user
                'package_id' => $validated['package_id'],
                'package_code' => $packageCode,
                'total_classes' => $validated['total_classes'],
                'used_classes' => 0, // Always start with 0 used classes
                'remaining_classes' => $remainingClasses,
                'amount_paid_soles' => $validated['amount_paid_soles'],
                'currency' => $validated['currency'],
                'purchase_date' => $purchaseDate,
                'activation_date' => $activationDate,
                'expiry_date' => $expiryDate,
                'status' => $validated['status'],
                'auto_renew' => $validated['auto_renew'],
                'renewal_price' => $validated['renewal_price'] ?? null,
                'benefits_included' => $validated['benefits_included'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            // Load relationships for response
            $userPackage->load(['package:id,name,slug,description,classes_quantity,price_soles,validity_days']);

            return new UserPackageResource($userPackage);
        });
    }

    /**
     * Obtener resumen de paquetes del usuario por disciplina
     *
     * Muestra un resumen organizado de todos los paquetes activos del usuario,
     * agrupados por disciplina con información de clases disponibles.
     *
     * @summary Resumen de paquetes por disciplina
     * @operationId getPackagesSummaryByDiscipline
     * @tags Mis Paquetes
     *
     * @return \Illuminate\Http\JsonResponse
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Resumen de paquetes obtenido exitosamente",
     *   "data": {
     *     "disciplines": [
     *       {
     *         "discipline_id": 1,
     *         "discipline_name": "Yoga",
     *         "total_packages": 2,
     *         "total_classes_remaining": 15,
     *         "packages": [
     *           {
     *             "id": 42,
     *             "package_code": "PKG001-2024",
     *             "package_name": "Paquete Yoga 10 Clases",
     *             "remaining_classes": 9,
     *             "expiry_date": "2025-02-15"
     *           }
     *         ]
     *       }
     *     ],
     *     "summary": {
     *       "total_disciplines": 3,
     *       "total_packages": 5,
     *       "total_classes_remaining": 42
     *     }
     *   }
     * }
     */
    public function getPackagesSummaryByDiscipline()
    {
        try {
            $userId = Auth::id();
            $packageValidationService = new PackageValidationService();

            $disciplinesSummary = $packageValidationService->getUserPackagesSummaryByDiscipline($userId);

            // Calcular totales generales
            $totalDisciplines = count($disciplinesSummary);
            $totalPackages = array_sum(array_column($disciplinesSummary, 'total_packages'));
            $totalClassesRemaining = array_sum(array_column($disciplinesSummary, 'total_classes_remaining'));

            return response()->json([
                'success' => true,
                'message' => 'Resumen de paquetes obtenido exitosamente',
                'data' => [
                    'disciplines' => $disciplinesSummary,
                    'summary' => [
                        'total_disciplines' => $totalDisciplines,
                        'total_packages' => $totalPackages,
                        'total_classes_remaining' => $totalClassesRemaining
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error interno al obtener resumen de paquetes',
                'data' => null
            ], 500);
        }
    }

    /**
     * Generate a unique package code
     */
    private function generateUniquePackageCode(): string
    {
        do {
            $code = 'PKG' . str_pad((string) mt_rand(1, 999), 3, '0', STR_PAD_LEFT) . '-' . date('Y');
        } while (UserPackage::where('package_code', $code)->exists());

        return $code;
    }
}
