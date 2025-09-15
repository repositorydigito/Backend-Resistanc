<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DisciplineResource;
use App\Models\Discipline;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @tags Disciplinas
 */
final class DisciplineController extends Controller
{
    /**
     * Lista todas las disciplinas activas del sistema
     *
     */
    public function index(Request $request)
    {

        try {
            $query = Discipline::query()
                ->active()
                ->whereHas('packages')
                ->orderBy('sort_order', 'asc')
                ->orderBy('display_name', 'asc');

            // Incluir contadores si se solicita
            if ($request->boolean('include_counts', false)) {
                $query->withCount(['classes', 'instructors']);
            }

            // Si no se especifica per_page, devolver todo sin paginar
            if ($request->has('per_page')) {
                $disciplines = $query->paginate(
                    perPage: $request->integer('per_page', 15),
                    page: $request->integer('page', 1)
                );
            } else {
                $disciplines = $query->get();
            }
            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Lista de disciplinas obtenida correctamente',
                'datoAdicional' => DisciplineResource::collection($disciplines)
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error al obtener las disciplinas',
                'datoAdicional' => $th->getMessage()
            ], 200);
        }
    }
}
