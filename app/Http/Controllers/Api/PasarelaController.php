<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Http;

use Illuminate\Support\Str;

/**
 * @tags Pasarela de Pago
 */

final class PasarelaController extends Controller
{

    /**
     * @summary Obtener token de pago desde Izipay
     * @operationId getIzipayToken
     * @tags Pasarela de Pago
     *
     * Genera una orden y solicita un token desde la pasarela Izipay utilizando las credenciales configuradas.
     * El monto es fijo (100.00 USD) y el correo del usuario autenticado se envía como parte de los datos del cliente.
     *
     * @security BearerAuth
     *
     * @response 200 {
     *   "culqi_response": {
     *     "token": "abc123xyz",
     *     "status": "SUCCESS",
     *     "other_data": "..."
     *   }
     * }
     *
     * @response 401 {
     *   "message": "Unauthenticated."
     * }
     *
     * @return \Illuminate\Http\JsonResponse
     */


    public function izipayToken()
    {

        $auth = base64_encode(config('services.izipay.client_id') . ':' . config('services.izipay.client_secret'));

        $response = Http::withHeaders([
            'Authorization' => 'Basic ' . $auth,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ])->post(config('services.izipay.url'), [
            'amount' => 10000,
            'currency' => 'USD',
            'orderId' => Str::random(10),
            'customer' => [
                'email' => auth()->user()->email,
            ]
        ])
            ->json();

        return $response['answer']['formToken'];
    }
}
