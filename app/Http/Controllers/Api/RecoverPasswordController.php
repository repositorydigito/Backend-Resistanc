<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SendPasswordResetCodeRequest;
use App\Http\Requests\VerifyPasswordResetCodeRequest;
use App\Http\Requests\ResetPasswordWithCodeRequest;
use App\Mail\RecoverPasswordCode;
use App\Models\Log;
use App\Models\User;
use App\Models\PasswordResetCode;
use App\Notifications\PasswordResetCodeNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Dedoc\Scramble\Attributes\BodyParameter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

/**
 * @tags Recuperación de Contraseña
 */
final class RecoverPasswordController extends Controller
{
    /**
     * Enviar código de recuperación de contraseña
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


        // Enviar correo
        Mail::to($user->email)->send(new RecoverPasswordCode($resetCode->code, $user));

        // $user->notify(new PasswordResetCodeNotification($resetCode->code));

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
     */
    #[BodyParameter('email', description: 'Correo electrónico del usuario', type: 'string', example: 'migelo5511@gmail.com')]
    #[BodyParameter('code', description: 'Código de verificación de 4 dígitos', type: 'string', example: '1234')]
    public function verifyResetCode(VerifyPasswordResetCodeRequest $request): JsonResponse
    {

        try {
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
        } catch (\Throwable $e) {
            Log::create([
                'user_id' => Auth::id(),
                'action' => 'Verificar código de recuperación de contraseña',
                'description' => 'Error al verificar el código',
                'data' => $e->getMessage(),
            ]);

            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error al verificar el código',
                'datoAdicional' => $e->getMessage()
            ], 200);
        }
    }

    /**
     * Restablecer contraseña con código verificado
     */
    #[BodyParameter('email', description: 'Correo electrónico del usuario', type: 'string', example: 'migelo5511@gmail.com')]
    #[BodyParameter('code', description: 'Código de verificación de 4 dígitos', type: 'string', example: '1234')]
    #[BodyParameter('password', description: 'Nueva contraseña del usuario', type: 'string', example: 'NuevaPassword123!')]
    #[BodyParameter('password_confirmation', description: 'Confirmación de la nueva contraseña', type: 'string', example: 'NuevaPassword123!')]
    public function resetPassword(ResetPasswordWithCodeRequest $request): JsonResponse
    {

        try {
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
        } catch (\Throwable $e) {
            Log::create([
                'user_id' => Auth::id(),
                'action' => 'Restablecer contraseña con código verificado',
                'description' => 'Error al restablecer la contraseña',
                'data' => $e->getMessage(),
            ]);

            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error al restablecer la contraseña',
                'datoAdicional' => $e->getMessage()
            ], 200);
        }
    }
}
