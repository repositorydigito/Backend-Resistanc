<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Http;
use phpseclib3\Crypt\AES;
use phpseclib3\Crypt\RSA;

class PasarelaController extends Controller
{

    public function conectar(Request $request)
    {
        // 1. Datos de tarjeta (normalmente deben venir desde el frontend o formulario HTTPS)
        $card = [
            'card_number' => '4111111111111111',
            'cvv' => '123',
            'expiration_month' => '09',
            'expiration_year' => '2025',
            'email' => 'cliente@correo.com'
        ];

        // 2. Generar clave AES de 16 bytes (128 bits)
        $aesKey = random_bytes(16);

        // 3. Encriptar el payload con AES-128-ECB
        $aes = new AES('ecb');
        $aes->setKey($aesKey);
        $encryptedData = base64_encode($aes->encrypt(json_encode($card)));

        // 4. Encriptar la clave AES con la clave pública de Culqi (RSA 2048)
        $publicKey = <<<EOD
-----BEGIN PUBLIC KEY-----
AQUI_VA_TU_LLAVE_PUBLICA_RSA_CULQI
-----END PUBLIC KEY-----
EOD;

        $rsa = RSA::loadPublicKey($publicKey);
        $rsa->setEncryptionMode(RSA::ENCRYPTION_PKCS1);
        $encryptedKey = base64_encode($rsa->encrypt($aesKey));

        // 5. Enviar a Culqi
        $response = Http::withHeaders([
            'Authorization' => 'Bearer TU_LLAVE_PUBLICA_CULQI',
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ])->post('https://api.culqi.com/v2/tokens', [
            'encrypted_data' => $encryptedData,
            'encrypted_key' => $encryptedKey
        ]);

        return response()->json([
            'culqi_response' => $response->json()
        ]);
    }
}
