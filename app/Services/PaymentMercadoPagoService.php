<?php


namespace App\Services;

use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\Exceptions\MPApiException;
use MercadoPago\MercadoPagoConfig;
use PhpParser\Node\Stmt\TryCatch;

class PaymentMercadoPagoService
{

    private const ACCESS_TOKEN = "APP_USR-6929379013094961-111515-335d6fa78c5bc337af707490fb8a4731-2991765228";
    private const NGROK_URL = "";


    public function createCheckoutPreferenceç()
    {

        try {
            MercadoPagoConfig::setAccessToken(self::ACCESS_TOKEN);
            MercadoPagoConfig::setRuntimeEnviroment(MercadoPagoConfig::LOCAL);
            $item = [
                [
                    'title' => 'Mi producto',
                    'currency_id' => 'PEN',
                    'quantity' => 1,
                    'unit_price' => 75.56,
                ]
            ];

            $back_urls = [
                'success' => self::NGROK_URL . '/payment/success',
                'failure' => self::NGROK_URL . '/payment/failure',
                'pending' => self::NGROK_URL . '/payment/pending',
            ];
            $request = [
                'items' => $item,
                'back_urls' => $back_urls,
                'auto_return' => 'approved',
                'notification_url' => self::NGROK_URL . '/api/webhooks/mercadopago',
            ];

            $client = new PreferenceClient();

            $preference = $client->create($request);

            return [
                'init_point' => $preference->init_point,
                'id' => $preference->id,
            ];
        } catch (MPApiException $e) {
            $body = $e->getApiResponse()->getContent();
            throw new \RuntimeException("No se pudo generar la preferncia de pago: " . json_encode($body));
        } catch (\Throwable $th) {
            throw new \RuntimeException("No se pudo generar la preferncia de pago: " . $th->getMessage());
        }
    }
}
