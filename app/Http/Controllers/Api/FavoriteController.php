<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\ClassSchedule;
use App\Models\Drink;
use App\Models\Instructor;
use App\Models\Product;
use Illuminate\Http\Request;

/**
 * @tags Favoritos
 */
final class FavoriteController extends Controller
{
    /**
     * Lista los elementos favoritos del usuario
     *
     * Devuelve una lista agrupada de favoritos del usuario autenticado, clasificados por tipo:
     * bebidas, productos, clases e instructores.
     * **Requiere autenticación:** Incluye el token Bearer en el header Authorization.
     *
     * @summary Listar favoritos del usuario
     * @operationId getFavoritesList
     *
     * @response 200 {
     *   "favorites": {
     *     "drinks": [
     *       {
     *         "id": 1,
     *         "name": "Cappuccino Vainilla",
     *         "slug": "cappuccino-vainilla",
     *         "image_url": "https://example.com/images/cappuccino.jpg"
     *       }
     *     ],
     *     "products": [
     *       {
     *         "id": 2,
     *         "name": "Proteína Whey",
     *         "slug": "proteina-whey",
     *         "price_soles": 150.00
     *       }
     *     ],
     *     "classes": [
     *       {
     *         "id": 3,
     *         "name": "Yoga Avanzado",
     *         "slug": "yoga-avanzado"
     *       }
     *     ],
     *     "instructors": [
     *       {
     *         "id": 4,
     *         "name": "Laura Mendoza",
     *         "specialty": "Funcional"
     *       }
     *     ]
     *   }
     * }
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'favorites' => [
                'drinks' => $user->favoriteDrinks,
                'products' => $user->favoriteProducts,
                'classes' => $user->favoriteClasses,
                'instructors' => $user->favoriteInstructors,
            ]
        ]);
    }

    /**
     * Agrega un producto a los favoritos del usuario
     *
     * Marca un producto como favorito para el usuario autenticado.
     * **Requiere autenticación:** Incluye el token Bearer en el header Authorization.
     *
     * @summary Agregar producto a favoritos
     * @operationId addFavoriteProduct
     *
     * @param \Illuminate\Http\Request $request
     * @bodyParam favoritable_id integer required ID del producto a agregar. Example: 12
     * @bodyParam notes string Notas adicionales sobre el favorito. Example: "Me gusta mucho"
     * @bodyParam priority integer Nivel de prioridad del favorito. Example: 1
     *
     * @response 200 {
     *   "message": "Producto favorito agregado correctamente."
     * }
     */

    public function storeProduct(Request $request)
    {
        $request->validate([
            'product' => 'required|integer|exists:products,id',
            'notes' => 'nullable|string|max:255',
            'priority' => 'nullable|integer|min:0',
        ]);

        try {
            $user = $request->user();
            $productId = $request->input('product');
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
     *
     * Marca una bebida como favorita para el usuario autenticado.
     * **Requiere autenticación:** Incluye el token Bearer en el header Authorization.
     *
     * @summary Agregar bebida a favoritos
     * @operationId addFavoriteDrink
     *
     * @param \Illuminate\Http\Request $request
     * @bodyParam favoritable_id integer required ID de la bebida a agregar. Example: 5
     * @bodyParam notes string Notas adicionales sobre el favorito. Example: "Siempre la pido"
     * @bodyParam priority integer Nivel de prioridad del favorito. Example: 2
     *
     * @response 200 {
     *   "message": "Bebida favorita agregada correctamente."
     * }
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
            return response()->json([
                'exito' => false,
                'codMensaje' => 2,
                'mensajeUsuario' => 'Error al procesar la bebida favorita',
                'datoAdicional' => $e->errors(),
            ], 200);
        } catch (\Throwable $e) {
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
     *
     * Marca una clase como favorita para el usuario autenticado.
     * **Requiere autenticación:** Incluye el token Bearer en el header Authorization.
     *
     * @summary Agregar clase a favoritos
     * @operationId addFavoriteClass
     *
     * @param \Illuminate\Http\Request $request
     * @bodyParam favoritable_id integer required ID de la clase a agregar. Example: 8
     * @bodyParam notes string Notas adicionales sobre el favorito. Example: "Quiero repetirla"
     * @bodyParam priority integer Nivel de prioridad del favorito. Example: 3
     *
     * @response 200 {
     *   "message": "Clase favorita agregada correctamente."
     * }
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
            return response()->json([
                'exito' => false,
                'codMensaje' => 2,
                'mensajeUsuario' => 'Error al procesar la clase favorita',
                'datoAdicional' => $e->errors(),
            ], 200);
        } catch (\Throwable $e) {
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
     *
     * Marca un instructor como favorito para el usuario autenticado.
     * **Requiere autenticación:** Incluye el token Bearer en el header Authorization.
     *
     * @summary Agregar instructor a favoritos
     * @operationId addFavoriteInstructor
     *
     * @param \Illuminate\Http\Request $request
     * @bodyParam favoritable_id integer required ID del instructor a agregar. Example: 3
     * @bodyParam notes string Notas adicionales sobre el favorito. Example: "Me motiva bastante"
     * @bodyParam priority integer Nivel de prioridad del favorito. Example: 4
     *
     * @response 200 {
     *   "message": "Instructor favorito agregado correctamente."
     * }
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
            return response()->json([
                'exito' => false,
                'codMensaje' => 2,
                'mensajeUsuario' => 'Error al procesar el instructor favorito',
                'datoAdicional' => $e->errors(),
            ], 200);
        } catch (\Throwable $e) {
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
     *
     * Alterna el estado de favorito para un horario de clase específico. Si ya está marcado como favorito, lo remueve.
     * **Requiere autenticación:** Incluye el token Bearer en el header Authorization.
     *
     * @summary Alternar horario de clase en favoritos
     * @operationId toggleFavoriteClassSchedule
     *
     * @param \Illuminate\Http\Request $request
     * @bodyParam favoritable_id integer required ID del horario de clase. Example: 5
     * @bodyParam notes string Notas adicionales sobre el favorito. Example: "Me gusta el enfoque práctico"
     * @bodyParam priority integer Nivel de prioridad del favorito (0-10). Example: 3
     *
     * @response 200 {
     *   "exito": true,
     *   "codMensaje": 1,
     *   "mensajeUsuario": "Horario de clase agregado a favoritos correctamente.",
     *   "datoAdicional": {
     *     "action": "added",
     *     "class_schedule_id": 5,
     *     "notes": "Me gusta el enfoque práctico",
     *     "priority": 3
     *   }
     * }
     * @response 200 {
     *   "exito": true,
     *   "codMensaje": 1,
     *   "mensajeUsuario": "Horario de clase removido de favoritos correctamente.",
     *   "datoAdicional": {
     *     "action": "removed",
     *     "class_schedule_id": 5,
     *     "notes": "Me gusta el enfoque práctico",
     *     "priority": 3
     *   }
     * }
     * @response 422 {
     *   "exito": false,
     *   "codMensaje": 2,
     *   "mensajeUsuario": "Error al procesar el horario favorito",
     *   "datoAdicional": {
     *     "favoritable_id": ["El campo favoritable_id es obligatorio."]
     *   }
     * }
     * @response 500 {
     *   "exito": false,
     *   "codMensaje": 0,
     *   "mensajeUsuario": "Error inesperado al procesar el horario favorito",
     *   "datoAdicional": null
     * }
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
            return response()->json([
                'exito' => false,
                'codMensaje' => 2,
                'mensajeUsuario' => 'Error al procesar el horario favorito',
                'datoAdicional' => $e->errors(),
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error inesperado al procesar el horario favorito: ' . $e->getMessage(),
                'datoAdicional' => null,
            ], 200);
        }
    }
}
