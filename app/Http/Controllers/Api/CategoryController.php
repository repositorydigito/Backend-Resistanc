<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Dedoc\Scramble\Attributes\BodyParameter;
use Error;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @tags Articulos Categorias
 */

class CategoryController extends Controller
{

    /**
     * Lista todas las categorías de productos del sistema
     *
     * Obtiene una lista paginada de categorías ordenadas por nombre.
     *
     * @summary Listar categorías
     * @operationId getCategoriesList
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     *
     * @queryParam per_page integer Número de categorías por página (máximo 100). Example: 15
     * @queryParam page integer Número de página para la paginación. Example: 1
     * @return JsonResponse
     *
     *
     *
     */

    #[BodyParameter('per_page', description: 'Número de categorías por página', type: 'integer', example: 15)]
    #[BodyParameter('page', description: 'Número de página para la paginación', type: 'integer', example: 1)]

    public function index(Request $request): JsonResponse
    {

        // Validación de parámetros de paginación
        $request->validate([
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
        ]);

        try {
            $query = Category::query()
                ->whereHas('posts') // Solo categorías con al menos un post
                ->orderBy('name', 'asc');

            // Paginación o lista completa
            if ($request->has('per_page')) {
                $categories = $query->paginate(
                    perPage: $request->integer('per_page', 15),
                    page: $request->integer('page', 1)
                );
            } else {
                $categories = $query->get();
            }

            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Lista de categorías obtenida correctamente',
                'datoAdicional' =>  CategoryResource::collection($categories)

            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error al obtener las categorías',
                'datoAdicional' => null
            ], 200);
        }
    }
}
