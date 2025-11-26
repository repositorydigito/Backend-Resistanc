<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\PostCategoryResource;
use App\Http\Resources\PostResource;
use App\Http\Resources\TagResource;
use App\Models\Category;
use App\Models\Log;
use App\Models\Post;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Dedoc\Scramble\Attributes\BodyParameter;
use Illuminate\Support\Facades\Auth;

/**
 * @tags Artículos
 */
class PostController extends Controller
{
    /**
     * Lista todos los artículos del sistema
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

            Log::create([
                'user_id' => Auth::id(),
                'action' => 'Lista todos los artículos del sistema',
                'description' => 'Error al obtener los artículos',
                'data' => $e->getMessage(),
            ]);


            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error al obtener los artículos',
                'datoAdicional' => $e->getMessage()
            ], 200);
        }
    }
    /**
     * Vista independiente del articulo
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

            // Verificar si el artículo está en estado "draft"
            if ($post->status === 'draft') {
                return response()->json([
                    'exito' => false,
                    'codMensaje' => 1,
                    'mensajeUsuario' => 'El articulo no se puede mostrar esta en borrador',
                    'datoAdicional' => null,

                ], 200);
            }


            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Artículo obtenido correctamente',
                'datoAdicional' => new PostResource($post)
            ], 200);
        } catch (\Throwable $e) {


            Log::create([
                'user_id' => Auth::id(),
                'action' => 'Vista independiente del articulo',
                'description' => 'Error al obtener el artículo',
                'data' => $e->getMessage(),
            ]);


            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error al obtener el artículo',
                'datoAdicional' => $e->getMessage()
            ], 200);
        }
    }

    /**
     * Lista todas las categorías de articulos
     */

    #[BodyParameter('per_page', description: 'Número de categorías por página', type: 'integer', example: 15)]
    #[BodyParameter('page', description: 'Número de página para la paginación', type: 'integer', example: 1)]

    public function categories(Request $request): JsonResponse
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

            Log::create([
                'user_id' => Auth::id(),
                'action' => 'Lista todas las categorías de articulos',
                'description' => 'Error al obtener las categorías',
                'data' => $e->getMessage(),
            ]);


            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error al obtener las categorías',
                'datoAdicional' => null
            ], 200);
        }
    }


    /**
     * Lista todas las etiquetas de articulos
     */
    public function tags(Request $request): JsonResponse
    {
        // Validación de parámetros de paginación
        $request->validate([
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
        ]);

        try {
            $query = Tag::query()->orderBy('name', 'asc');

            // Paginación o lista completa
            if ($request->has('per_page')) {
                $tags = $query->paginate(
                    perPage: $request->integer('per_page', 15),
                    page: $request->integer('page', 1)
                );
            } else {
                $tags = $query->get();
            }

            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Lista de etiquetas obtenida correctamente',
                'datoAdicional' => TagResource::collection($tags)
            ], 200);
        } catch (\Exception $e) {


            Log::create([
                'user_id' => Auth::id(),
                'action' => 'Lista todas las etiquetas de articulos',
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
}
