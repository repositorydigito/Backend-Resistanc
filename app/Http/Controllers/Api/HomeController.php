<?php

namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;
use App\Http\Resources\ClassScheduleResource;
use App\Http\Resources\DisciplineResource;
use App\Http\Resources\InstructorResource;
use App\Http\Resources\PostResource;
use App\Http\Resources\ProductResource;
use App\Models\ClassSchedule;
use App\Models\Discipline;
use App\Models\Instructor;
use App\Models\Post;
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
     */
    public function index()
    {
        $user = request()->user(); // más seguro que Auth::user()

        // Cantidad de reservas de asientos completadas
        $classSchedulesCompletedCount = $user->completedSeatReservations()->count();

        $classSchedulesPendingCount = $user->pendingSeatReservations()->count();

        // Clases disponibles del usuario
        $availableClassesCount = $user->getAvailableClassesCount();
        $availableClassesByDiscipline = $user->getAvailableClassesByDiscipline();

        // Paquetes activos del usuario
        $activePackagesCount = $user->getActivePackagesCount();

        // Instructores activos con disciplina
        $instructors = Instructor::with('disciplines')
            ->whereHas('disciplines', fn($q) => $q->where('status', 'active'))
            ->orderBy('name')
            ->limit(10)
            ->get();

        $disciplines = Discipline::
        // whereHas('packages')
            orderBy('sort_order', 'asc')
            ->orderBy('display_name', 'asc')
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
            ->where('status', 'scheduled')
            ->orderBy('scheduled_date', 'asc')
            ->orderBy('start_time', 'asc')
            ->limit(10)
            ->get();

        $products = Product::with('category', 'productBrand')
            ->where('status', 'active')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $posts = Post::with('category', 'tags')
            ->where('status', 'published')
            ->orderBy('is_featured', 'desc')
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
                        'availableClassesCount' => $availableClassesCount,
                        'availableClassesByDiscipline' => $availableClassesByDiscipline,
                        'activePackagesCount' => $activePackagesCount,
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
                'disciplines' => DisciplineResource::collection($disciplines),
                'instructors' => InstructorResource::collection($instructors),
                'classSchedules' => ClassScheduleResource::collection($classSchedules),
                'classSchedulesMe' => ClassScheduleResource::collection($classSchedulesMe),
                'products' => ProductResource::collection($products),
                'posts' => PostResource::collection($posts),
            ],
        ]);
    }




}
