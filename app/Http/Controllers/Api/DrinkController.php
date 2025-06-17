<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DrinkResource;
use App\Models\Drink;
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
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Drink::query();

        // Incluir relaciones si se solicita
        if ($request->boolean('include_relations', false)) {
            $query->with(['basesdrinks', 'flavordrinks', 'typesdrinks']);
        }

        // Búsqueda por texto
        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filtrar por base de bebida
        if ($request->filled('base_id')) {
            $query->whereHas('basesdrinks', function ($q) use ($request) {
                $q->where('basedrink_id', $request->integer('base_id'));
            });
        }

        // Filtrar por sabor
        if ($request->filled('flavor_id')) {
            $query->whereHas('flavordrinks', function ($q) use ($request) {
                $q->where('flavordrink_id', $request->integer('flavor_id'));
            });
        }

        // Filtrar por tipo
        if ($request->filled('type_id')) {
            $query->whereHas('typesdrinks', function ($q) use ($request) {
                $q->where('typedrink_id', $request->integer('type_id'));
            });
        }

        // Filtrar por rango de precio
        if ($request->filled('min_price')) {
            $query->where('price', '>=', $request->float('min_price'));
        }

        if ($request->filled('max_price')) {
            $query->where('price', '<=', $request->float('max_price'));
        }

        // Ordenar por nombre
        $query->orderBy('name', 'asc');

        // Paginación o lista completa
        if ($request->has('per_page')) {
            $drinks = $query->paginate(
                perPage: $request->integer('per_page', 15),
                page: $request->integer('page', 1)
            );
        } else {
            $drinks = $query->get();
        }

        return DrinkResource::collection($drinks);
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
    public function show(int $id): DrinkResource
    {
        $drink = Drink::with(['basesdrinks', 'flavordrinks', 'typesdrinks'])
            ->findOrFail($id);

        return new DrinkResource($drink);
    }




}
