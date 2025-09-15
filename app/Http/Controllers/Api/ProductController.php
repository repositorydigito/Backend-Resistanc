<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductCategoryResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\ProductTagResource;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductTag;
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
     */
    public function index(Request $request)
    {
        try {
            $query = Product::query()
                ->with(['category', 'variants', 'productBrand'])
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



            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Lista de productos obtenida correctamente',
                'datoAdicional' => ProductResource::collection($products)
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error al obtener lista de productos',
                'datoAdicional' => $th->getMessage()
            ], 200);
        }
    }

    /**
     * Mostrar un producto
     */
    public function show(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id'
        ]);

        try {
            $product = Product::with(['category', 'variants'])->findOrFail($request->product_id);

            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Producto obtenido correctamente',
                'datoAdicional' => new ProductResource($product)
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error al obtener el producto',
                'datoAdicional' => $th->getMessage()
            ], 200);
        }
    }


    /**
     * Lista todas las categorías activas de productos
     *
     */
    public function categories(): JsonResponse
    {
        try {
            $categories = ProductCategory::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get();

            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Lista de categorias de productos obtenida correctamente',
                'datoAdicional' => ProductCategoryResource::collection($categories)
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error al obtener la lista de categorias de los productos',
                'datoAdicional' => $th->getMessage()
            ], 200);
        }
    }


    /**
     * Lista todas las tags activas de los productos
     *
     */
    public function tags()
    {
        try {
            $tags = ProductTag::query()
                ->select('id', 'name')
                ->orderBy('id', 'asc')
                ->get();
            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Lista de etiquetas de productos obtenida correctamente',
                'datoAdicional' => ProductTagResource::collection($tags)
            ], 200);

            return response()->json([
                'data' => $tags,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error al obtener la lista de etiquetas de los productos',
                'datoAdicional' => $th->getMessage()
            ], 200);
        }
    }
}
