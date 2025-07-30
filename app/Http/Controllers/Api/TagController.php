<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TagResource;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;


/**
 * @tags Articulos Etiquetas
 */

class TagController extends Controller
{

    /**
     * Lista todas las etiquetas del sistema
     *
     * Obtiene una lista paginada de etiquetas ordenadas por nombre.
     *
     * @summary Listar etiquetas
     * @operationId getTagsList
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @queryParam per_page integer Número de etiquetas por página (máximo 100). Example: 15
     * @queryParam page integer Número de página para la paginación. Example: 1
     */
    public function index(Request $request): JsonResponse
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
            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error al obtener las etiquetas',
                'datoAdicional' => $e->getMessage()
            ], 200);
        }
    }
}
