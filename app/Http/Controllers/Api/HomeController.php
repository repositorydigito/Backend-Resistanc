<?php

namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;
use App\Http\Resources\ClassScheduleResource;
use App\Http\Resources\InstructorResource;
use App\Http\Resources\ProductResource;
use App\Models\ClassSchedule;
use App\Models\Instructor;
use App\Models\Product;
use DragonCode\Contracts\Cashier\Auth\Auth;
use Illuminate\Http\Request;

/**
 * @tags Inicio
 */
final class HomeController extends Controller
{
    /**
     * Datos de la pantalla de inicio
     *
     * Retorna información útil para la app del usuario autenticado: instructores activos con disciplina,
     * clases próximas reservadas, y productos activos.
     * **Requiere autenticación:** Incluye el token Bearer en el header Authorization.
     *
     * @summary Obtener datos del home
     * @operationId getHomeData
     *
     * @response 200 {
     *   "data": {
     *     "instructors": [
     *       {
     *         "id": 1,
     *         "name": "Laura Mendoza",
     *         "discipline": {
     *           "id": 1,
     *           "name": "Yoga"
     *         }
     *       }
     *     ],
     *     "classSchedules": [
     *       {
     *         "id": 10,
     *         "status": "reserved",
     *         "class_schedule": {
     *           "id": 2,
     *           "class": { "name": "Pilates" },
     *           "studio": { "name": "Sala A" }
     *         },
     *         "seat": {
     *           "id": 5,
     *           "label": "A3"
     *         }
     *       }
     *     ],
     *     "products": [
     *       {
     *         "id": 5,
     *         "name": "Proteína Whey",
     *         "category": {
     *           "id": 2,
     *           "name": "Suplementos"
     *         }
     *       }
     *     ]
     *   }
     * }
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $user = request()->user(); // más seguro que Auth::user()

        // Cantidad de reservas de asientos completadas
        $classSchedulesCompletedCount = $user->completedSeatReservations()->count();

        $classSchedulesPendingCount = $user->pendingSeatReservations()->count();

        // Instructores activos con disciplina
        $instructors = Instructor::with('disciplines')
            ->whereHas('disciplines', fn($q) => $q->where('status', 'active'))
            ->orderBy('name')
            ->limit(10)
            ->get();

        $classSchedulesMe = $user->upcomingSeatReservations()
            ->whereHas('classSchedule.class', fn($q) => $q->where('status', 'active'))
            ->whereHas('classSchedule.studio', fn($q) => $q->where('status', 'active'))
            ->with(['classSchedule.class', 'classSchedule.studio', 'seat'])
            ->orderBy('reserved_at')
            ->limit(10)
            ->get();

        $classSchedules = ClassSchedule::with(['class', 'studio'])
            ->where('scheduled_date', '>=', now()->toDateString())
            ->orderBy('scheduled_date', 'asc')
            ->orderBy('start_time', 'asc')
            ->limit(10)
            ->get();

        $products = Product::with('category')
            ->where('status', 'active')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'data' => [
                'user' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                    ],
                    'info' => [
                        'completedClassSchedulesCount' => $classSchedulesCompletedCount,
                        'pendingClassSchedulesCount' => $classSchedulesPendingCount,
                    ],
                    'contact' => $user->contact ? [
                        'phone' => $user->contact->phone,
                        'address' => $user->contact->address,
                    ] : [],
                    'paymentMethods' => $user->paymentMethods ? $user->paymentMethods->map(function ($method) {
                        return [
                            'id' => $method->id,
                            'type' => $method->type,
                            'details' => $method->details,
                        ];
                    }) : [],
                    'packages' => $user->packages ? $user->packages->map(function ($package) {
                        return [
                            'id' => $package->id,
                            'name' => $package->name,
                            'description' => $package->description,
                            'price' => $package->price,
                            'status' => $package->status,
                        ];
                    }) : [],

                ],
                'instructors' => InstructorResource::collection($instructors),
                'classSchedules' => ClassScheduleResource::collection($classSchedules),
                'classSchedulesMe' => ClassScheduleResource::collection($classSchedulesMe),
                'products' => ProductResource::collection($products),
            ],
        ]);
    }
}
