<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\InstructorResource;
use App\Models\Instructor;
use App\Models\Log;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;

/**
 * @tags Instructores
 */
final class InstructorController extends Controller
{
    /**
     *
     * Lista todos los instructores activos del sistema
     *
     */
    public function index(Request $request)
    {
        try {
            // Validar parámetros de la solicitud
            $request->validate([
                'search' => 'sometimes|string|max:255',
                'discipline_id' => 'sometimes|integer|exists:disciplines,id',
                'per_page' => 'sometimes|integer|min:1|max:50',
                'page' => 'sometimes|integer|min:1',

            ]);

            $query = Instructor::query()
                ->where('status', 'active')
                ->when($request->filled('search'), function ($query) use ($request) {
                    $search = $request->string('search');
                    $query->where('name', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%');
                })
                ->when($request->filled('discipline_id'), function ($query) use ($request) {
                    $query->whereHas('disciplines', function ($q) use ($request) {
                        $q->where('disciplines.id', $request->integer('discipline_id'));
                    });
                });

            // Ordenamiento
            $query->orderBy('is_head_coach', 'desc')
                ->orderBy('rating_average', 'desc')
                ->orderBy('name', 'asc');

            // Paginación con límite de 50 registros por página
            $instructors = $query->paginate(
                perPage: $request->integer('per_page', 15),
                page: $request->integer('page', 1)
            );

            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Instructores obtenidos exitosamente',
                'datoAdicional' => [
                    'instructors' => InstructorResource::collection($instructors),
                    'pagination' => [
                        'current_page' => $instructors->currentPage(),
                        'last_page' => $instructors->lastPage(),
                        'per_page' => $instructors->perPage(),
                        'total' => $instructors->total(),
                        'from' => $instructors->firstItem(),
                        'to' => $instructors->lastItem(),
                        'has_more_pages' => $instructors->hasMorePages(),
                        'links' => [
                            'first' => $instructors->url(1),
                            'last' => $instructors->url($instructors->lastPage()),
                            'prev' => $instructors->previousPageUrl(),
                            'next' => $instructors->nextPageUrl(),
                        ]
                    ]
                ],
            ], 200);
        } catch (\Throwable $e) {

            Log::create([
                'user_id' => Auth::id(),
                'action' => 'Lista todos los instructores activos del sistema',
                'description' => 'Error al listar instructores',
                'data' => $e->getMessage(),
            ]);

            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error al listar instructores',
                'datoAdicional' => $e->getMessage(),
            ], 200); // Código 500 para errores del servidor
        }
    }

    /**
     * Lista los instructores activos del sistema que tienen clases programadas para la semana actual
     */
    public function instructorsWeek()
    {
        try {
            $instructors = Instructor::query()
                ->active()
                ->withCount(['classSchedules' => function ($query) {
                    $query->whereBetween('scheduled_date', [now()->startOfWeek(), now()->endOfWeek()]);
                }])
                ->having('class_schedules_count', '>', 0)
                ->orderBy('class_schedules_count', 'desc')
                ->get();

            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Instructores de la semana obtenidos exitosamente',
                'datoAdicional' => InstructorResource::collection($instructors),
            ], 200);
        } catch (\Throwable $e) {

            Log::create([
                'user_id' => Auth::id(),
                'action' => 'Lista todos los instructores activos del sistema',
                'description' => 'Error al obtener instructores de la semana',
                'data' => $e->getMessage(),
            ]);


            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error al obtener instructores de la semana',
                'datoAdicional' => $e->getMessage(),
            ], 200);
        }
    }

    /**
     * Lista de 10 instructores activos del sistema
     */
    public function indexTen(Request $request)
    {
        try {
            $instructors = Instructor::query()
                ->where('status', 'active')
                ->limit(10)
                ->get();

            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Top 10 instructores obtenidos exitosamente',
                'datoAdicional' => InstructorResource::collection($instructors),
            ], 200);
        } catch (\Throwable $e) {

            Log::create([
                'user_id' => Auth::id(),
                'action' => 'Lista de 10 instructores activos del sistema',
                'description' => 'Error al obtener top 10 instructores',
                'data' => $e->getMessage(),
            ]);

            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error al obtener top 10 instructores',
                'datoAdicional' => $e->getMessage(),
            ], 200);
        }
    }
    /**
     * Mostrar detalles de un instructor específico
     */

    public function show(Request $request)
    {

        $request->validate([
            'instructor_id' => 'required|exists:instructors,id'
        ]);


        try {

            $instructor = Instructor::find($request->instructor_id);
            // Cargar relaciones necesarias
            $instructor->load(['disciplines', 'classSchedules', 'ratings']);

            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Instructor obtenido exitosamente',
                'datoAdicional' => new InstructorResource($instructor),
            ], 200);
        } catch (\Throwable $e) {

            Log::create([
                'user_id' => Auth::id(),
                'action' => 'Mostrar detalles de un instructor específico',
                'description' => 'Error al obtener instructor',
                'data' => $e->getMessage(),
            ]);

            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error al obtener instructor',
                'datoAdicional' => $e->getMessage(),
            ], 200);
        }
    }

    /**
     * Calificar a un instructor
     */

    public function scoreInstructor(Request $request)
    {
        try {
            // Validar la puntuación
            $request->validate([
                'instructor_id' => 'required|exists:instructors,id',
                'score' => 'required|integer|min:1|max:5',
                'comment' => 'nullable|string|max:500',
            ]);

            $instructor = Instructor::findOrFail($request->instructor_id);
            $userId = Auth::id();

            // Verificar si ya existe una calificación previa
            $alreadyRated = $instructor->ratings()
                ->where('user_id', $userId)
                ->exists();

            if ($alreadyRated) {
                return response()->json([
                    'exito' => false,
                    'codMensaje' => 0,
                    'mensajeUsuario' => 'Ya has calificado a este instructor',
                    'datoAdicional' => 'Solo puedes calificar una vez por instructor',
                ], 200);
            }

            // Crear la calificación
            $rating = $instructor->ratings()->create([
                'user_id' => $userId,
                'score' => $request->input('score'),
                'comment' => $request->input('comment'),
            ]);

            // Actualizar el promedio de calificaciones del instructor
            $instructor->updateRatingAverage();

            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Calificación registrada exitosamente',
                'datoAdicional' => $rating,
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {

            Log::create([
                'user_id' => Auth::id(),
                'action' => 'Calificar a un instructor',
                'description' => 'Datos de entrada inválidos',
                'data' => $e->getMessage(),
            ]);

            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Datos de entrada inválidos',
                'datoAdicional' => $e->errors(),
            ], 200);
        } catch (\Throwable $e) {

            Log::create([
                'user_id' => Auth::id(),
                'action' => 'Calificar a un instructor',
                'description' => 'Error al registrar la calificación',
                'data' => $e->getMessage(),
            ]);

            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error al registrar la calificación',
                'datoAdicional' => $e->getMessage(),
            ], 200);
        }
    }
}
