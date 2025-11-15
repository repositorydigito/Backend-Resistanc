<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PaymentMercadoPagoService;
use Faker\Provider\Payment;
use Illuminate\Http\Request;

class MercardoPagoController extends Controller
{


    public function __construct(private PaymentMercadoPagoService $service) {}

    public function create()
    {
        $response = $this->service->createCheckoutPreferenceç();
        return response()->json($response, 200);
    }
}
