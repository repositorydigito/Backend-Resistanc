<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
/**
 * @tags Categorías
 */
final class ProductCategoryController extends Controller
{
    /**
     * Lista todas las categorías activas
     *
     * Devuelve una lista simple de categorías activas ordenadas por el campo `sort_order`.
     * Solo se devuelven los campos `id` y `name`.
     *
     * @summary Listar categorías
     * @operationId getCategoriesList
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
        $categories = ProductCategory::query()
            ->select('id', 'name','sort_order')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return response()->json([
            'data' => $categories,
        ]);
    }
}
