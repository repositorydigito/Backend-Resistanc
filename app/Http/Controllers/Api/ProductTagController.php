<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductTag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @tags Etiquetas
 */
final class ProductTagController extends Controller
{
    /**
     * Lista todas las tags activas
     *
     * Devuelve una lista simple de tags activas ordenadas por el campo `sort_order`.
     * Solo se devuelven los campos `id` y `name`.
     *
     * @summary Listar tags
     * @operationId getTagsList
     *
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "Bebidas"
     *     },
     *     {
     *       "id": 2,
     *       "name": "Postres"
     *     }
     *   ]
     * }
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(): JsonResponse
    {
        $tags = ProductTag::query()
            ->select('id', 'name')
            ->orderBy('id', 'asc')
            ->get();

        return response()->json([
            'data' => $tags,
        ]);
    }
}
