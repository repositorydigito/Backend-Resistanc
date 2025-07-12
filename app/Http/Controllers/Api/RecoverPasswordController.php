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
     *   "message": "Se ha enviado un código de verificación a tu correo electrónico.",
     *   "data": {
     *     "email": "migelo5511@gmail.com",
     *     "expires_in": 600
     *   }
     * }
     *
     * @response 404 {
     *   "message": "No se encontró un usuario con ese correo electrónico."
     * }
     *
     * @response 429 {
     *   "message": "Demasiadas solicitudes. Intenta de nuevo en 60 segundos."
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
                'message' => "Demasiadas solicitudes. Intenta de nuevo en {$seconds} segundos.",
            ], 429);
        }

        // Buscar usuario
        $user = User::where('email', $email)->first();
        if (!$user) {
            return response()->json([
                'message' => 'No se encontró un usuario con ese correo electrónico.',
            ], 404);
        }

        // Crear código de recuperación
        $resetCode = PasswordResetCode::createForEmail($email, 10);

        // Enviar notificación
        $user->notify(new PasswordResetCodeNotification($resetCode->code));

        // Incrementar rate limiter
        RateLimiter::hit($key, 60);

        return response()->json([
            'message' => 'Se ha enviado un código de verificación a tu correo electrónico.',
            'data' => [
                'email' => $email,
                'expires_in' => 600, // 10 minutos en segundos
            ],
        ]);
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
     *   "message": "Código verificado correctamente.",
     *   "data": {
     *     "email": "migelo5511@gmail.com",
     *     "verified": true
     *   }
     * }
     *
     * @response 400 {
     *   "message": "Código inválido o expirado."
     * }
     *
     * @response 404 {
     *   "message": "No se encontró un usuario con ese correo electrónico."
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
                'message' => 'No se encontró un usuario con ese correo electrónico.',
            ], 404);
        }

        // Buscar código válido
        $resetCode = PasswordResetCode::valid()
            ->forEmailAndCode($email, $code)
            ->first();

        if (!$resetCode) {
            return response()->json([
                'message' => 'Código inválido o expirado.',
            ], 400);
        }

        return response()->json([
            'message' => 'Código verificado correctamente.',
            'data' => [
                'email' => $email,
                'verified' => true,
            ],
        ]);
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
     *   "message": "Contraseña restablecida correctamente.",
     *   "data": {
     *     "email": "migelo5511@gmail.com",
     *     "updated_at": "2024-01-15T10:30:00.000Z"
     *   }
     * }
     *
     * @response 400 {
     *   "message": "Código inválido o expirado."
     * }
     *
     * @response 404 {
     *   "message": "No se encontró un usuario con ese correo electrónico."
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
                'message' => 'No se encontró un usuario con ese correo electrónico.',
            ], 404);
        }

        // Buscar código válido
        $resetCode = PasswordResetCode::valid()
            ->forEmailAndCode($email, $code)
            ->first();

        if (!$resetCode) {
            return response()->json([
                'message' => 'Código inválido o expirado.',
            ], 400);
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
            'message' => 'Contraseña restablecida correctamente.',
            'data' => [
                'email' => $email,
                'updated_at' => $user->updated_at,
            ],
        ]);
    }
}
