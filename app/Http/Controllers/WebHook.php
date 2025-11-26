<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class WebHook extends Controller
{

    public function github(Request $request)
    {
        // Aquí puedes manejar el webhook de GitHub
        // Por ejemplo, verificar la firma, procesar el payload, etc.

        // Retornar una respuesta adecuada
        return response()->json(['status' => 'success']);
    }
}
