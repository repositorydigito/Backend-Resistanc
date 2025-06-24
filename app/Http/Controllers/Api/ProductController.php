<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @tags Productos
 */
class ProductController extends Controller
{
    /**
     * Lista de productos con filtros, ordenamientos y paginación.
     *
     * @summary Listar productos
     * @operationId getProductsList
     *
     * @queryParam search string Buscar por nombre o descripción. Example: pollo
     * @queryParam category_id integer Filtrar por ID de categoría. Example: 2
     * @queryParam sort string Ordenamiento: price_asc, price_desc, name_asc, name_desc. Example: price_asc
     * @queryParam page integer Número de página. Example: 1
     * @queryParam per_page integer Cantidad por página. Example: 15
     *
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "Camiseta Deportiva",
     *       "price_soles": "49.99",
     *       ...
     *     }
     *   ],
     *   "links": {
     *     "first": "...",
     *     "last": "...",
     *     "prev": null,
     *     "next": "..."
     *   },
     *   "meta": {
     *     "current_page": 1,
     *     "from": 1,
     *     "last_page": 3,
     *     "path": "...",
     *     "per_page": 15,
     *     "to": 15,
     *     "total": 45
     *   }
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $query = Product::query()
            ->with(['category', 'variants'])
            ->where('status', 'active');

        // Búsqueda por nombre o descripción
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filtrado por categoría
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        // Ordenamiento dinámico
        switch ($request->input('sort')) {
            case 'price_asc':
                $query->orderBy('price_soles', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('price_soles', 'desc');
                break;
            case 'name_desc':
                $query->orderBy('name', 'desc');
                break;
            case 'name_asc':
            default:
                $query->orderBy('name', 'asc');
                break;
        }

        // Paginación con `per_page` opcional
        $perPage = $request->input('per_page', 12);
        $products = $query->paginate($perPage);

        // Retorno con metadatos
        return ProductResource::collection($products)->response();
    }

    /**
     * Mostrar un producto
     *
     * Devuelve la información detallada de un producto específico.
     *
     * @summary Mostrar producto
     * @operationId getProductDetail
     *
     * @urlParam id integer ID del producto. Example: 1
     *
     * @response 200 {
     *   "data": {
     *     "id": 1,
     *     "name": "1/4 de pollo",
     *     "price_soles": "10.90",
     *     ...
     *   }
     * }
     */
    public function show($id): JsonResponse
    {
        $product = Product::with(['category', 'variants'])->findOrFail($id);

        return response()->json(new ProductResource($product));
    }
}
