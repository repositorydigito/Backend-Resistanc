<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\ClassSchedule;
use App\Models\Drink;
use App\Models\Instructor;
use App\Models\Log;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * @tags Favoritos
 */
final class FavoriteController extends Controller
{
    /**
     * Lista los elementos favoritos del usuario
     *
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();

            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Lista de etiquetas obtenida correctamente',
                'datoAdicional' => [
                    'drinks' => $user->favoriteDrinks,
                    'products' => $user->favoriteProducts,
                    'classes' => $user->favoriteClasses,
                    'instructors' => $user->favoriteInstructors,
                ]
            ], 200);
        } catch (\Throwable $e) {

            Log::create([
                'user_id' => Auth::id(),
                'action' => 'Lista los elementos favoritos del usuario',
                'description' => 'Error al obtener las etiquetas',
                'data' => $e->getMessage(),
            ]);

            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error al obtener las etiquetas',
                'datoAdicional' => $e->getMessage()
            ], 200);
        }
    }


    /**
     * Obtiene los productos favoritos del usuario logueado
     */
    public function products(Request $request)
    {
        try {
            $user = $request->user();

            // Validar parámetros de paginación
            $perPage = $request->input('per_page', 15);
            $perPage = min(max($perPage, 1), 100); // Limitar entre 1 y 100

            // Obtener productos favoritos con información adicional
            $favoriteProductsQuery = $user->favoriteProducts()
                ->with(['category', 'productBrand', 'variants'])
                ->orderBy('user_favorites.priority', 'desc')
                ->orderBy('user_favorites.created_at', 'desc');

            // Verificar si se solicita paginación
            if ($request->has('paginate') && $request->boolean('paginate')) {
                $favoriteProducts = $favoriteProductsQuery->paginate($perPage);

                return response()->json([
                    'exito' => true,
                    'codMensaje' => 1,
                    'mensajeUsuario' => 'Productos favoritos obtenidos exitosamente',
                    'datoAdicional' => [
                        'products' => \App\Http\Resources\ProductResource::collection($favoriteProducts),
                        'pagination' => [
                            'current_page' => $favoriteProducts->currentPage(),
                            'last_page' => $favoriteProducts->lastPage(),
                            'per_page' => $favoriteProducts->perPage(),
                            'total' => $favoriteProducts->total(),
                            'from' => $favoriteProducts->firstItem(),
                            'to' => $favoriteProducts->lastItem(),
                            'has_more_pages' => $favoriteProducts->hasMorePages(),
                        ]
                    ]
                ], 200);
            } else {
                // Sin paginación - retornar todos los resultados
                $favoriteProducts = $favoriteProductsQuery->get();

                return response()->json([
                    'exito' => true,
                    'codMensaje' => 1,
                    'mensajeUsuario' => 'Productos favoritos obtenidos exitosamente',
                    'datoAdicional' => [
                        'products' => \App\Http\Resources\ProductResource::collection($favoriteProducts),
                        'total_count' => $favoriteProducts->count()
                    ]
                ], 200);
            }
        } catch (\Throwable $e) {

            Log::create([
                'user_id' => Auth::id(),
                'action' => 'Obtiene los productos favoritos del usuario logueado',
                'description' => 'Error al obtener los productos favoritos',
                'data' => $e->getMessage(),
            ]);

            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error al obtener los productos favoritos',
                'datoAdicional' => $e->getMessage()
            ], 200);
        }
    }

    /**
     * Agrega un producto a los favoritos del usuario
     */

    public function storeProduct(Request $request)
    {
        $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'notes' => 'nullable|string|max:255',
            'priority' => 'nullable|integer|min:0',
        ]);

        try {
            $user = $request->user();
            $productId = $request->input('product_id');
            $notes = $request->input('notes', '');
            $priority = $request->input('priority', 0);

            // Verificar si ya existe usando la tabla directa
            $existingFavorite = $user->userFavorites()
                ->where('favoritable_id', (string)$productId) // Cast a string para coincidir con la migración
                ->where('favoritable_type', Product::class)
                ->first();

            if ($existingFavorite) {
                // Forma correcta para eliminar con tipo polimórfico
                $user->userFavorites()
                    ->where('favoritable_id', (string)$productId)
                    ->where('favoritable_type', Product::class)
                    ->delete();

                $message = 'Producto removido de favoritos correctamente.';
                $action = 'removed';
            } else {
                // Crear usando la relación directa para evitar problemas
                $user->userFavorites()->create([
                    'favoritable_id' => (string)$productId,
                    'favoritable_type' => Product::class,
                    'notes' => $notes,
                    'priority' => $priority
                ]);

                $message = 'Producto agregado a favoritos correctamente.';
                $action = 'added';
            }

            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => $message,
                'datoAdicional' => [
                    'action' => $action,
                    'product_id' => $productId,
                    'notes' => $notes,
                    'priority' => $priority
                ],
            ], 200);
        } catch (\Exception $e) {

            Log::create([
                'user_id' => Auth::id(),
                'action' => 'Agrega un producto a los favoritos del usuario',
                'description' => 'Error al procesar el favorito',
                'data' => $e->getMessage(),
            ]);

            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error al procesar el favorito: ' . $e->getMessage(),
                'datoAdicional' => null,
            ], 200);
        }
    }

    /**
     * Agrega una bebida a los favoritos del usuario
     */
    public function storeDrink(Request $request)
    {
        $request->validate([
            'drink' => 'required|integer|exists:drinks,id',
            'notes' => 'nullable|string|max:255',
            'priority' => 'nullable|integer|min:0',
        ]);

        try {
            $user = $request->user();
            $drinkId = $request->input('drink');
            $notes = $request->input('notes', '');
            $priority = $request->input('priority', 0);

            // Verificar si ya existe usando la tabla directa
            $existingFavorite = $user->userFavorites()
                ->where('favoritable_id', (string)$drinkId)
                ->where('favoritable_type', Drink::class)
                ->first();

            if ($existingFavorite) {
                // Eliminar usando la relación directa
                $user->userFavorites()
                    ->where('favoritable_id', (string)$drinkId)
                    ->where('favoritable_type', Drink::class)
                    ->delete();

                $message = 'Bebida removida de favoritos correctamente.';
                $action = 'removed';
            } else {
                // Crear nuevo favorito
                $user->userFavorites()->create([
                    'favoritable_id' => (string)$drinkId,
                    'favoritable_type' => Drink::class,
                    'notes' => $notes,
                    'priority' => $priority
                ]);

                $message = 'Bebida agregada a favoritos correctamente.';
                $action = 'added';
            }

            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => $message,
                'datoAdicional' => [
                    'action' => $action,
                    'drink_id' => $drinkId,
                    'notes' => $notes,
                    'priority' => $priority
                ],
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {

            Log::create([
                'user_id' => Auth::id(),
                'action' => 'Agrega una bebida a los favoritos del usuario',
                'description' => 'Error al procesar la bebida favorita',
                'data' => $e->getMessage(),
            ]);

            return response()->json([
                'exito' => false,
                'codMensaje' => 2,
                'mensajeUsuario' => 'Error al procesar la bebida favorita',
                'datoAdicional' => $e->errors(),
            ], 200);
        } catch (\Throwable $e) {

            Log::create([
                'user_id' => Auth::id(),
                'action' => 'Agrega una bebida a los favoritos del usuario',
                'description' => 'Error inesperado al procesar la bebida favorita',
                'data' => $e->getMessage(),
            ]);

            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error inesperado al procesar la bebida favorita: ' . $e->getMessage(),
                'datoAdicional' => null,
            ], 200);
        }
    }

    /**
     * Agrega una clase a los favoritos del usuario
     */

    public function storeClass(Request $request)
    {
        $request->validate([
            'class' => 'required|integer|exists:classes,id',
            'notes' => 'nullable|string|max:255',
            'priority' => 'nullable|integer|min:0',
        ]);

        try {
            $user = $request->user();
            $classId = $request->input('class');
            $notes = $request->input('notes', '');
            $priority = $request->input('priority', 0);

            // Verificar si ya existe el favorito
            $existingFavorite = $user->userFavorites()
                ->where('favoritable_id', (string)$classId)
                ->where('favoritable_type', ClassModel::class)
                ->first();

            if ($existingFavorite) {
                // Eliminar el favorito existente
                $user->userFavorites()
                    ->where('favoritable_id', (string)$classId)
                    ->where('favoritable_type', ClassModel::class)
                    ->delete();

                $message = 'Clase removida de favoritos correctamente.';
                $action = 'removed';
            } else {
                // Agregar nueva clase favorita
                $user->userFavorites()->create([
                    'favoritable_id' => (string)$classId,
                    'favoritable_type' => ClassModel::class,
                    'notes' => $notes,
                    'priority' => $priority
                ]);

                $message = 'Clase agregada a favoritos correctamente.';
                $action = 'added';
            }

            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => $message,
                'datoAdicional' => [
                    'action' => $action,
                    'class_id' => $classId,
                    'notes' => $notes,
                    'priority' => $priority
                ],
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {


            Log::create([
                'user_id' => Auth::id(),
                'action' => 'Agrega una clase a los favoritos del usuario',
                'description' => 'Error al procesar la clase favorita',
                'data' => $e->getMessage(),
            ]);


            return response()->json([
                'exito' => false,
                'codMensaje' => 2,
                'mensajeUsuario' => 'Error al procesar la clase favorita',
                'datoAdicional' => $e->errors(),
            ], 200);
        } catch (\Throwable $e) {

            Log::create([
                'user_id' => Auth::id(),
                'action' => 'Agrega una clase a los favoritos del usuario',
                'description' => 'Error inesperado al procesar la clase favorita',
                'data' => $e->getMessage(),
            ]);


            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error inesperado al procesar la clase favorita: ' . $e->getMessage(),
                'datoAdicional' => null,
            ], 200);
        }
    }

    /**
     * Agrega un instructor a los favoritos del usuario
     */

    public function storeInstructor(Request $request)
    {
        $request->validate([
            'instructor' => 'required|integer|exists:instructors,id',
            'notes' => 'nullable|string|max:255',
            'priority' => 'nullable|integer|min:0',
        ]);

        try {
            $user = $request->user();
            $instructorId = $request->input('instructor');
            $notes = $request->input('notes', '');
            $priority = $request->input('priority', 0);

            // Verificar si ya existe el favorito
            $existingFavorite = $user->userFavorites()
                ->where('favoritable_id', (string)$instructorId)
                ->where('favoritable_type', Instructor::class)
                ->first();

            if ($existingFavorite) {
                // Eliminar el favorito existente
                $user->userFavorites()
                    ->where('favoritable_id', (string)$instructorId)
                    ->where('favoritable_type', Instructor::class)
                    ->delete();

                $message = 'Instructor removido de favoritos correctamente.';
                $action = 'removed';
            } else {
                // Agregar nuevo instructor favorito
                $user->userFavorites()->create([
                    'favoritable_id' => (string)$instructorId,
                    'favoritable_type' => Instructor::class,
                    'notes' => $notes,
                    'priority' => $priority
                ]);

                $message = 'Instructor agregado a favoritos correctamente.';
                $action = 'added';
            }

            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => $message,
                'datoAdicional' => [
                    'action' => $action,
                    'instructor_id' => $instructorId,
                    'notes' => $notes,
                    'priority' => $priority
                ],
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {

            Log::create([
                'user_id' => Auth::id(),
                'action' => 'Agrega un instructor a los favoritos del usuario',
                'description' => 'Error al procesar el instructor favorito',
                'data' => $e->getMessage(),
            ]);


            return response()->json([
                'exito' => false,
                'codMensaje' => 2,
                'mensajeUsuario' => 'Error al procesar el instructor favorito',
                'datoAdicional' => $e->errors(),
            ], 200);
        } catch (\Throwable $e) {

            Log::create([
                'user_id' => Auth::id(),
                'action' => 'Agrega un instructor a los favoritos del usuario',
                'description' => 'Error inesperado al procesar el instructor favorito',
                'data' => $e->getMessage(),
            ]);

            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error inesperado al procesar el instructor favorito: ' . $e->getMessage(),
                'datoAdicional' => null,
            ], 200);
        }
    }

    /**
     * Agrega o remueve un horario de clase de los favoritos del usuario
     */

    public function storeClassSchedule(Request $request)
    {
        $request->validate([
            'favoritable_id' => 'required|integer|exists:class_schedules,id',
            'notes' => 'nullable|string|max:255',
            'priority' => 'nullable|integer|min:0',
        ]);

        try {
            $user = $request->user();
            $classScheduleId = $request->input('favoritable_id');
            $notes = $request->input('notes', '');
            $priority = $request->input('priority', 0);

            // Verificar si ya existe el favorito
            $existingFavorite = $user->userFavorites()
                ->where('favoritable_id', (string)$classScheduleId)
                ->where('favoritable_type', ClassSchedule::class)
                ->first();

            if ($existingFavorite) {
                // Eliminar el favorito existente
                $user->userFavorites()
                    ->where('favoritable_id', (string)$classScheduleId)
                    ->where('favoritable_type', ClassSchedule::class)
                    ->delete();

                $message = 'Horario de clase removido de favoritos correctamente.';
                $action = 'removed';
            } else {
                // Agregar nuevo horario favorito
                $user->userFavorites()->create([
                    'favoritable_id' => (string)$classScheduleId,
                    'favoritable_type' => ClassSchedule::class,
                    'notes' => $notes,
                    'priority' => $priority
                ]);

                $message = 'Horario de clase agregado a favoritos correctamente.';
                $action = 'added';
            }

            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => $message,
                'datoAdicional' => [
                    'action' => $action,
                    'class_schedule_id' => $classScheduleId,
                    'notes' => $notes,
                    'priority' => $priority
                ],
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {

            Log::create([
                'user_id' => Auth::id(),
                'action' => 'Agrega o remueve un horario de clase de los favoritos del usuario',
                'description' => 'Error al procesar el horario favorito',
                'data' => $e->getMessage(),
            ]);

            return response()->json([
                'exito' => false,
                'codMensaje' => 2,
                'mensajeUsuario' => 'Error al procesar el horario favorito',
                'datoAdicional' => $e->errors(),
            ], 200);
        } catch (\Throwable $e) {

            Log::create([
                'user_id' => Auth::id(),
                'action' => 'Agrega o remueve un horario de clase de los favoritos del usuario',
                'description' => 'Error inesperado al procesar el horario favorito',
                'data' => $e->getMessage(),
            ]);

            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error inesperado al procesar el horario favorito: ' . $e->getMessage(),
                'datoAdicional' => null,
            ], 200);
        }
    }
}
