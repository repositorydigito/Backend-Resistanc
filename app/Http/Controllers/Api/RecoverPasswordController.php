<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SendPasswordResetCodeRequest;
use App\Http\Requests\VerifyPasswordResetCodeRequest;
use App\Http\Requests\ResetPasswordWithCodeRequest;
use App\Models\User;
use App\Models\PasswordResetCode;
use App\Notifications\PasswordResetCodeNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Dedoc\Scramble\Attributes\BodyParameter;

/**
 * @tags Recuperación de Contraseña
 */
final class RecoverPasswordController extends Controller
{
    /**
     * Enviar código de recuperación de contraseña
     *
     * Envía un código de 4 dígitos al correo electrónico del usuario
     * para permitir la recuperación de contraseña.
     *
     * @summary Enviar código de recuperación
     * @operationId sendPasswordResetCode
     *
     * @param  \App\Http\Requests\SendPasswordResetCodeRequest  $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @bodyParam email string required Correo electrónico del usuario. Example: migelo5511@gmail.com
     *
     * @response 200 {
     *   "exito": true,
     *   "codMensaje": 200,
     *   "mensajeUsuario": "Se ha enviado un código de verificación a tu correo electrónico.",
     *   "datoAdicional": {
     *     "email": "migelo5511@gmail.com",
     *     "expires_in": 600
     *   }
     * }
     *
     * @response 200 {
     *   "exito": false,
     *   "codMensaje": 404,
     *   "mensajeUsuario": "No se encontró un usuario con ese correo electrónico.",
     *   "datoAdicional": null
     * }
     *
     * @response 200 {
     *   "exito": false,
     *   "codMensaje": 429,
     *   "mensajeUsuario": "Demasiadas solicitudes. Intenta de nuevo en 60 segundos.",
     *   "datoAdicional": null
     * }
     */
    #[BodyParameter('email', description: 'Correo electrónico del usuario', type: 'string', example: 'migelo5511@gmail.com')]
    public function sendResetCode(SendPasswordResetCodeRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $email = $validated['email'];

        // Rate limiting por IP
        $key = 'password-reset:' . request()->ip();
        if (RateLimiter::tooManyAttempts($key, 3)) {
            $seconds = RateLimiter::availableIn($key);
            return response()->json([
                'exito' => false,
                'codMensaje' => 429,
                'mensajeUsuario' => "Demasiadas solicitudes. Intenta de nuevo en {$seconds} segundos.",
                'datoAdicional' => null
            ], 200);
        }

        // Buscar usuario
        $user = User::where('email', $email)->first();
        if (!$user) {
            return response()->json([
                'exito' => false,
                'codMensaje' => 404,
                'mensajeUsuario' => 'No se encontró un usuario con ese correo electrónico.',
                'datoAdicional' => null
            ], 200);
        }

        // Crear código de recuperación
        $resetCode = PasswordResetCode::createForEmail($email, 10);

        // Enviar notificación
        $user->notify(new PasswordResetCodeNotification($resetCode->code));

        // Incrementar rate limiter
        RateLimiter::hit($key, 60);

        return response()->json([
            'exito' => true,
            'codMensaje' => 200,
            'mensajeUsuario' => 'Se ha enviado un código de verificación a tu correo electrónico.',
            'datoAdicional' => [
                'email' => $email,
                'expires_in' => 600, // 10 minutos en segundos
            ]
        ], 200);
    }

    /**
     * Verificar código de recuperación de contraseña
     *
     * Verifica que el código de 4 dígitos enviado al correo sea válido.
     *
     * @summary Verificar código de recuperación
     * @operationId verifyPasswordResetCode
     *
     * @param  \App\Http\Requests\VerifyPasswordResetCodeRequest  $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @bodyParam email string required Correo electrónico del usuario. Example: migelo5511@gmail.com
     * @bodyParam code string required Código de verificación de 4 dígitos. Example: 1234
     *
     * @response 200 {
     *   "exito": true,
     *   "codMensaje": 200,
     *   "mensajeUsuario": "Código verificado correctamente.",
     *   "datoAdicional": {
     *     "email": "migelo5511@gmail.com",
     *     "verified": true
     *   }
     * }
     *
     * @response 200 {
     *   "exito": false,
     *   "codMensaje": 400,
     *   "mensajeUsuario": "Código inválido o expirado.",
     *   "datoAdicional": null
     * }
     *
     * @response 200 {
     *   "exito": false,
     *   "codMensaje": 404,
     *   "mensajeUsuario": "No se encontró un usuario con ese correo electrónico.",
     *   "datoAdicional": null
     * }
     */
    #[BodyParameter('email', description: 'Correo electrónico del usuario', type: 'string', example: 'migelo5511@gmail.com')]
    #[BodyParameter('code', description: 'Código de verificación de 4 dígitos', type: 'string', example: '1234')]
    public function verifyResetCode(VerifyPasswordResetCodeRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $email = $validated['email'];
        $code = $validated['code'];

        // Buscar usuario
        $user = User::where('email', $email)->first();
        if (!$user) {
            return response()->json([
                'exito' => false,
                'codMensaje' => 404,
                'mensajeUsuario' => 'No se encontró un usuario con ese correo electrónico.',
                'datoAdicional' => null
            ], 200);
        }

        // Buscar código válido
        $resetCode = PasswordResetCode::valid()
            ->forEmailAndCode($email, $code)
            ->first();

        if (!$resetCode) {
            return response()->json([
                'exito' => false,
                'codMensaje' => 400,
                'mensajeUsuario' => 'Código inválido o expirado.',
                'datoAdicional' => null
            ], 200);
        }

        return response()->json([
            'exito' => true,
            'codMensaje' => 200,
            'mensajeUsuario' => 'Código verificado correctamente.',
            'datoAdicional' => [
                'email' => $email,
                'verified' => true,
            ]
        ], 200);
    }

    /**
     * Restablecer contraseña con código verificado
     *
     * Cambia la contraseña del usuario usando el código de verificación.
     *
     * @summary Restablecer contraseña
     * @operationId resetPasswordWithCode
     *
     * @param  \App\Http\Requests\ResetPasswordWithCodeRequest  $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @bodyParam email string required Correo electrónico del usuario. Example: migelo5511@gmail.com
     * @bodyParam code string required Código de verificación de 4 dígitos. Example: 1234
     * @bodyParam password string required Nueva contraseña del usuario. Example: NuevaPassword123!
     * @bodyParam password_confirmation string required Confirmación de la nueva contraseña. Example: NuevaPassword123!
     *
     * @response 200 {
     *   "exito": true,
     *   "codMensaje": 200,
     *   "mensajeUsuario": "Contraseña restablecida correctamente.",
     *   "datoAdicional": {
     *     "email": "migelo5511@gmail.com",
     *     "updated_at": "2024-01-15T10:30:00.000Z"
     *   }
     * }
     *
     * @response 200 {
     *   "exito": false,
     *   "codMensaje": 400,
     *   "mensajeUsuario": "Código inválido o expirado.",
     *   "datoAdicional": null
     * }
     *
     * @response 200 {
     *   "exito": false,
     *   "codMensaje": 404,
     *   "mensajeUsuario": "No se encontró un usuario con ese correo electrónico.",
     *   "datoAdicional": null
     * }
     */
    #[BodyParameter('email', description: 'Correo electrónico del usuario', type: 'string', example: 'migelo5511@gmail.com')]
    #[BodyParameter('code', description: 'Código de verificación de 4 dígitos', type: 'string', example: '1234')]
    #[BodyParameter('password', description: 'Nueva contraseña del usuario', type: 'string', example: 'NuevaPassword123!')]
    #[BodyParameter('password_confirmation', description: 'Confirmación de la nueva contraseña', type: 'string', example: 'NuevaPassword123!')]
    public function resetPassword(ResetPasswordWithCodeRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $email = $validated['email'];
        $code = $validated['code'];
        $password = $validated['password'];

        // Buscar usuario
        $user = User::where('email', $email)->first();
        if (!$user) {
            return response()->json([
                'exito' => false,
                'codMensaje' => 404,
                'mensajeUsuario' => 'No se encontró un usuario con ese correo electrónico.',
                'datoAdicional' => null
            ], 200);
        }

        // Buscar código válido
        $resetCode = PasswordResetCode::valid()
            ->forEmailAndCode($email, $code)
            ->first();

        if (!$resetCode) {
            return response()->json([
                'exito' => false,
                'codMensaje' => 400,
                'mensajeUsuario' => 'Código inválido o expirado.',
                'datoAdicional' => null
            ], 200);
        }

        // Actualizar contraseña
        $user->update([
            'password' => $password,
        ]);

        // Marcar código como usado
        $resetCode->markAsUsed();

        // Revocar todos los tokens del usuario
        $user->tokens()->delete();

        return response()->json([
            'exito' => true,
            'codMensaje' => 200,
            'mensajeUsuario' => 'Contraseña restablecida correctamente.',
            'datoAdicional' => [
                'email' => $email,
                'updated_at' => $user->updated_at,
            ]
        ], 200);
    }
}
