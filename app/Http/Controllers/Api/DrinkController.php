<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BasedrinkResource;
use App\Http\Resources\DrinkResource;
use App\Http\Resources\FlavordrinkResource;
use App\Http\Resources\TypedrinkResource;
use App\Models\Basedrink;
use App\Models\Drink;
use App\Models\Flavordrink;
use App\Models\Typedrink;
use Dedoc\Scramble\Support\Generator\Types\Type;
use DragonCode\PrettyArray\Services\Formatters\Json;
use Error;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @tags Bebidas
 */
final class DrinkController extends Controller
{
    /**
     * Lista todas las bebidas activas del sistema
     *
     * Obtiene una lista paginada de bebidas ordenadas por nombre.
     * **Requiere autenticación:** Incluye el token Bearer en el header Authorization.
     *
     * @summary Listar bebidas
     * @operationId getDrinksList
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     *
     * @queryParam per_page integer Número de bebidas por página (máximo 100). Example: 15
     * @queryParam page integer Número de página para la paginación. Example: 1
     * @queryParam include_relations boolean Incluir relaciones (bases, sabores, tipos). Example: true
     * @queryParam search string Buscar bebidas por nombre o descripción. Example: "café"
     * @queryParam base_id integer Filtrar por base de bebida específica. Example: 1
     * @queryParam flavor_id integer Filtrar por sabor específico. Example: 2
     * @queryParam type_id integer Filtrar por tipo de bebida específico. Example: 3
     * @queryParam min_price decimal Precio mínimo para filtrar. Example: 10.50
     * @queryParam max_price decimal Precio máximo para filtrar. Example: 25.00
     *
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "Cappuccino Vainilla",
     *       "slug": "cappuccino-vainilla",
     *       "description": "Delicioso cappuccino con esencia de vainilla y espuma cremosa",
     *       "image_url": "https://example.com/images/cappuccino-vainilla.jpg",
     *       "price": 18.50,
     *       "bases": [
     *         {
     *           "id": 1,
     *           "name": "Café Espresso"
     *         }
     *       ],
     *       "flavors": [
     *         {
     *           "id": 3,
     *           "name": "Vainilla"
     *         }
     *       ],
     *       "types": [
     *         {
     *           "id": 2,
     *           "name": "Caliente"
     *         }
     *       ],
     *       "created_at": "2024-01-15T10:30:00.000Z",
     *       "updated_at": "2024-01-15T10:30:00.000Z"
     *     }
     *   ],
     *   "links": {
     *     "first": "http://localhost/api/drinks?page=1",
     *     "last": "http://localhost/api/drinks?page=1",
     *     "prev": null,
     *     "next": null
     *   },
     *   "meta": {
     *     "current_page": 1,
     *     "from": 1,
     *     "last_page": 1,
     *     "path": "http://localhost/api/drinks",
     *     "per_page": 15,
     *     "to": 1,
     *     "total": 1
     *   }
     * }
     */
    public function index(Request $request): JsonResponse
    {

        try {
            $query = Drink::query();

            // Paginación o lista completa
            if ($request->has('per_page')) {
                $drinks = $query->paginate(
                    perPage: $request->integer('per_page', 15),
                    page: $request->integer('page', 1)
                );
            } else {
                $drinks = $query->get();
            }

            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Bebidas listadas exitosamente',
                'datoAdicional' => DrinkResource::collection($drinks),
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error al listar bebidas',
                'datoAdicional' => null,
            ], 200);
        }
    }

    /**
     * Muestra una bebida específica
     *
     * Obtiene los detalles completos de una bebida por su ID.
     * **Requiere autenticación:** Incluye el token Bearer en el header Authorization.
     *
     * @summary Mostrar bebida específica
     * @operationId getDrinkById
     *
     * @param  int  $id
     * @return \App\Http\Resources\DrinkResource
     *
     * @response 200 {
     *   "data": {
     *     "id": 1,
     *     "name": "Cappuccino Vainilla",
     *     "slug": "cappuccino-vainilla",
     *     "description": "Delicioso cappuccino con esencia de vainilla y espuma cremosa",
     *     "image_url": "https://example.com/images/cappuccino-vainilla.jpg",
     *     "price": 18.50,
     *     "bases": [
     *       {
     *         "id": 1,
     *         "name": "Café Espresso",
     *         "description": "Base de café espresso intenso"
     *       }
     *     ],
     *     "flavors": [
     *       {
     *         "id": 3,
     *         "name": "Vainilla",
     *         "description": "Esencia natural de vainilla"
     *       }
     *     ],
     *     "types": [
     *       {
     *         "id": 2,
     *         "name": "Caliente",
     *         "description": "Bebida servida caliente"
     *       }
     *     ],
     *     "created_at": "2024-01-15T10:30:00.000Z",
     *     "updated_at": "2024-01-15T10:30:00.000Z"
     *   }
     * }
     *
     * @response 404 {
     *   "message": "Bebida no encontrada"
     * }
     */
    public function show(Request $request): JsonResponse
    {
        try {

            $request->validate([
                'drink_id' => 'required|integer|exists:drinks,id',
            ]);

            $drink = Drink::with(['basesdrinks', 'flavordrinks', 'typesdrinks'])
                ->findOrFail($request->input('drink_id'));

            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Bebida obtenida exitosamente',
                'datoAdicional' => new DrinkResource($drink),
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error al obtener bebida',
                'datoAdicional' => null,
            ], 200);
        }
    }

    /**
     * Lista todas las bases de bebidas disponibles
     *
     * Obtiene un listado completo de las bases de bebidas registradas en el sistema.
     * **Requiere autenticación:** Incluye el token Bearer en el header Authorization.
     *
     * @summary Listar bases de bebidas
     * @operationId getDrinkBases
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     *
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "Café Espresso",
     *       "description": "Base de café espresso de alta calidad",
     *       "created_at": "2024-01-15T10:30:00.000Z",
     *       "updated_at": "2024-01-15T10:30:00.000Z"
     *     },
     *     {
     *       "id": 2,
     *       "name": "Té Negro",
     *       "description": "Base de té negro orgánico",
     *       "created_at": "2024-01-15T10:30:00.000Z",
     *       "updated_at": "2024-01-15T10:30:00.000Z"
     *     }
     *   ]
     * }
     */
    public function baseDrinks()
    {

        try {
            $bases = Basedrink::all();

            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Bases de bebidas listadas exitosamente',
                'datoAdicional' => BasedrinkResource::collection($bases),
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'exito' => false,
                'codMensaje' => 2,
                'mensajeUsuario' => 'Error al listar las bases de bebidas',
                'datoAdicional' => $e->errors(),
            ], 200);
        } catch (\Throwable $e) {

            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error inesperado al listar las bases de bebidas',
                'datoAdicional' => $e->getMessage(),
            ], 200);
        }
    }

    /**
     * Lista todos los sabores de bebidas disponibles
     *
     * Obtiene un listado completo de los sabores registrados para bebidas.
     * **Requiere autenticación:** Incluye el token Bearer en el header Authorization.
     *
     * @summary Listar sabores de bebidas
     * @operationId getDrinkFlavors
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     *
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "Vainilla",
     *       "description": "Esencia natural de vainilla",
     *       "created_at": "2024-01-15T10:30:00.000Z",
     *       "updated_at": "2024-01-15T10:30:00.000Z"
     *     },
     *     {
     *       "id": 2,
     *       "name": "Caramelo",
     *       "description": "Sabor a caramelo artesanal",
     *       "created_at": "2024-01-15T10:30:00.000Z",
     *       "updated_at": "2024-01-15T10:30:00.000Z"
     *     }
     *   ]
     * }
     */

    public function flavorDrinks()
    {

        try {
            $flavors = Flavordrink::all();

            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Sabores de bebidas listados exitosamente',
                'datoAdicional' => FlavordrinkResource::collection($flavors),
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'exito' => false,
                'codMensaje' => 2,
                'mensajeUsuario' => 'Error al listar los sabores de bebidas',
                'datoAdicional' => $e->errors(),
            ], 200);
        } catch (\Throwable $e) {

            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error inesperado al listar los sabores de bebidas',
                'datoAdicional' => $e->getMessage(),
            ], 200);
        }
    }

    /**
     * Lista todos los tipos de bebidas disponibles
     *
     * Obtiene un listado completo de los tipos de preparación de bebidas.
     * **Requiere autenticación:** Incluye el token Bearer en el header Authorization.
     *
     * @summary Listar tipos de bebidas
     * @operationId getDrinkTypes
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     *
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "Fría",
     *       "description": "Bebida servida con hielo",
     *       "created_at": "2024-01-15T10:30:00.000Z",
     *       "updated_at": "2024-01-15T10:30:00.000Z"
     *     },
     *     {
     *       "id": 2,
     *       "name": "Caliente",
     *       "description": "Bebida servida caliente",
     *       "created_at": "2024-01-15T10:30:00.000Z",
     *       "updated_at": "2024-01-15T10:30:00.000Z"
     *     }
     *   ]
     * }
     */

    public function typeDrinks()
    {

        try {
            $types = Typedrink::all();

            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Tipos de bebidas listados exitosamente',
                'datoAdicional' => TypedrinkResource::collection($types),
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'exito' => false,
                'codMensaje' => 2,
                'mensajeUsuario' => 'Error al listar los tipos de bebidas',
                'datoAdicional' => $e->errors(),
            ], 200);
        } catch (\Throwable $e) {

            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error inesperado al listar los tipos de bebidas',
                'datoAdicional' => $e->getMessage(),
            ], 200);
        }
    }
}
