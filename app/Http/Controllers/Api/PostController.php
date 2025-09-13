<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\PostCategoryResource;
use App\Http\Resources\PostResource;
use App\Models\Category;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;


/**
 * @tags Artículos
 */
class PostController extends Controller
{
    /**
     * Lista todos los artículos del sistema
     *
     * Obtiene una lista paginada de artículos con opciones de filtrado y ordenación.
     *
     * @summary Listar artículos
     * @operationId getPostsList
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @queryParam per_page integer Número de artículos por página (máximo 100). Example: 15
     * @queryParam page integer Número de página para la paginación. Example: 1
     * @queryParam category_id integer Filtrar artículos por categoría. Example: 1
     * @queryParam tag_id integer Filtrar artículos por etiqueta. Example: 1
     * @queryParam order_by string Ordenar artículos por título o fecha de creación (title, created_at). Example: title
     * @queryParam order_direction string Dirección de la ordenación (asc, desc). Example: asc
     * @queryParam is_destacado boolean Filtrar artículos destacados. Example: true
     */
    public function index(Request $request): JsonResponse
    {
        // Validación de parámetros
        $request->validate([
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
            'category_id' => 'nullable|integer|exists:categories,id',
            'tag_id' => 'nullable|integer|exists:tags,id',
            'order_by' => 'nullable|string|in:title,created_at',
            'order_direction' => 'nullable|string|in:asc,desc',
            'is_featured' => 'nullable|boolean',
        ]);

        try {
            $query = Post::where('status', 'published')->with(['category', 'tags']);

            // Filtrar por categoría si se proporciona
            if ($request->has('category_id')) {
                $query->where('category_id', $request->category_id);
            }

            // Filtrar por etiqueta si se proporciona
            if ($request->has('tag_id')) {
                $query->whereHas('tags', function ($q) use ($request) {
                    $q->where('tags.id', $request->tag_id);
                });
            }

            // Filtrar por destacado si se proporciona
            if ($request->has('is_destacado')) {
                $query->where('is_destacado', $request->is_destacado);
            }

            // Ordenar resultados
            if ($request->has('order_by')) {
                $orderBy = $request->order_by;
                $orderDirection = $request->order_direction ?? 'asc';
                $query->orderBy($orderBy, $orderDirection);
            } else {
                $query->orderBy('title', 'asc');
            }

            // Paginación o lista completa
            if ($request->has('per_page')) {
                $posts = $query->paginate(
                    perPage: $request->integer('per_page', 15),
                    page: $request->integer('page', 1)
                );
            } else {
                $posts = $query->get();
            }

            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Lista de artículos obtenida correctamente',
                'datoAdicional' => PostResource::collection($posts)
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error al obtener los artículos',
                'datoAdicional' => $e->getMessage()
            ], 200);
        }
    }
    /**
     * Lista todas las categorías de artículos
     *
     * @param int $id
     * @return JsonResponse
     */

    /**
     * Muestra los detalles de un artículo específico
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'id' => 'required|integer|exists:posts,id',
            ]);

            $post = Post::with(['category', 'tags'])->find($request->id);

            if (!$post) {
                return response()->json([
                    'exito' => false,
                    'codMensaje' => 0,
                    'mensajeUsuario' => 'Artículo no encontrado',
                    'datoAdicional' => null
                ], 200);
            }

            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Artículo obtenido correctamente',
                'datoAdicional' => new PostResource($post)
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error al obtener el artículo',
                'datoAdicional' => $th->getMessage()
            ], 200);
        }
    }
}
