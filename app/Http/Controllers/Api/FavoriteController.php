<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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

        $user = $request->user();
        $productId = $request->input('product');

        $user->favoriteProducts()->attach($productId, [
            'notes' => $request->input('notes', ''),
            'priority' => $request->input('priority', 0),
        ]);

        return response()->json(['message' => 'Producto favorito agregado correctamente.']);
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

        // return $request;

        $request->validate([
            'drink' => 'required|integer|exists:drinks,id',
            'notes' => 'nullable|string|max:255',
            'priority' => 'nullable|integer|min:0',
        ]);



        $user = $request->user();
        $drinkId = $request->input('drink');

        $user->favoriteDrinks()->syncWithoutDetaching([
            $drinkId => [
                'notes' => $request->input('notes', ''),
                'priority' => $request->input('priority', 0),
            ]
        ]);

        return response()->json(['message' => 'Bebida favorita agregada correctamente.']);
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

        $user = $request->user();
        $classId = $request->input('class');

        $user->favoriteClasses()->attach($classId, [
            'notes' => $request->input('notes', ''),
            'priority' => $request->input('priority', 0),
        ]);


        return response()->json(['message' => 'Clase favorita agregada correctamente.']);
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

        $user = $request->user();
        $instructorId = $request->input('instructor');

        $user->favoriteInstructors()->attach($instructorId, [
            'notes' => $request->input('notes', ''),
            'priority' => $request->input('priority', 0),
        ]);

        return response()->json(['message' => 'Instructor favorito agregado correctamente.']);
    }

    public function storeClassSchedule(Request $request)
    {

        $request->validate([
            'favoritable_id' => 'required|integer|exists:class_schedules,id',
            'notes' => 'nullable|string|max:255',
            'priority' => 'nullable|integer|min:0',
        ]);

        $user = $request->user();
        $classScheduleId = $request->input('favoritable_id');

        $user->favoriteClassSchedules()->attach($classScheduleId, [
            'notes' => $request->input('notes', ''),
            'priority' => $request->input('priority', 0),
        ]);

        return response()->json(['message' => 'La clase con el horario favorito se agregó correctamente.']);
    }
}
