<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;

class AuthRedController extends Controller
{
    /**
     * Redirige a Facebook (útil para navegador web)
     */
    public function facebookRedirect()
    {
        try {
            return Socialite::driver('facebook')
                ->stateless()
                ->redirect();
        } catch (\Exception $e) {
            Log::error('Error en Facebook redirect', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al redirigir a Facebook'
            ], 500);
        }
    }

    /**
     * Callback que Facebook llamará (devuelve token para la PWA)
     */
    public function facebookCallback(Request $request)
    {
        try {
            $fbUser = Socialite::driver('facebook')->stateless()->user();

            Log::info('Facebook callback recibido', [
                'fb_id' => $fbUser->getId(),
                'email' => $fbUser->getEmail(),
                'name' => $fbUser->getName()
            ]);

            // Buscar usuario existente por facebook_id o email
            $user = User::where('facebook_id', $fbUser->getId())
                ->orWhere('email', $fbUser->getEmail())
                ->first();

            if (!$user) {
                // Crear nuevo usuario
                $user = User::create([
                    'name' => $fbUser->getName() ?? $fbUser->getNickname() ?? 'Usuario Facebook',
                    'email' => $fbUser->getEmail() ?? "fb_{$fbUser->getId()}@noemail.local",
                    'password' => Hash::make(Str::random(16)),
                    'facebook_id' => $fbUser->getId(),
                    'avatar' => $fbUser->getAvatar(),
                    'email_verified_at' => now(), // Facebook ya verificó el email
                    'status' => 'active',
                ]);

                Log::info('Usuario creado via Facebook', ['user_id' => $user->id]);
            } else {
                // Actualizar datos del usuario existente
                $updateData = [];

                if (!$user->facebook_id) {
                    $updateData['facebook_id'] = $fbUser->getId();
                }

                if (!$user->avatar && $fbUser->getAvatar()) {
                    $updateData['avatar'] = $fbUser->getAvatar();
                }

                if (!$user->email_verified_at) {
                    $updateData['email_verified_at'] = now();
                }

                if (!empty($updateData)) {
                    $user->update($updateData);
                    Log::info('Usuario actualizado via Facebook', ['user_id' => $user->id, 'updates' => $updateData]);
                }
            }

            // Crear token API
            $token = $user->createToken('facebook-login', ['*'])->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Login exitoso',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'avatar' => $user->avatar,
                        'facebook_id' => $user->facebook_id,
                    ],
                    'token' => $token,
                    'token_type' => 'Bearer',
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error en Facebook callback', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error en autenticación con Facebook',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Login directo con token de Facebook (para PWA)
     */
    public function facebookTokenLogin(Request $request)
    {
        $request->validate([
            'access_token' => 'required|string',
        ]);

        try {
            // Obtener datos del usuario desde Facebook usando el token
            $fbUser = Socialite::driver('facebook')->stateless()->userFromToken($request->access_token);

            Log::info('Facebook token login', [
                'fb_id' => $fbUser->getId(),
                'email' => $fbUser->getEmail()
            ]);

            // Buscar o crear usuario (misma lógica que en callback)
            $user = User::where('facebook_id', $fbUser->getId())
                ->orWhere('email', $fbUser->getEmail())
                ->first();

            if (!$user) {
                $user = User::create([
                    'name' => $fbUser->getName() ?? $fbUser->getNickname() ?? 'Usuario Facebook',
                    'email' => $fbUser->getEmail() ?? "fb_{$fbUser->getId()}@noemail.local",
                    'password' => Hash::make(Str::random(16)),
                    'facebook_id' => $fbUser->getId(),
                    'avatar' => $fbUser->getAvatar(),
                    'email_verified_at' => now(),
                    'status' => 'active',
                ]);

                Log::info('Usuario creado via Facebook token', ['user_id' => $user->id]);
            } else {
                // Actualizar datos si es necesario
                $updateData = [];

                if (!$user->facebook_id) {
                    $updateData['facebook_id'] = $fbUser->getId();
                }

                if (!$user->avatar && $fbUser->getAvatar()) {
                    $updateData['avatar'] = $fbUser->getAvatar();
                }

                if (!$user->email_verified_at) {
                    $updateData['email_verified_at'] = now();
                }

                if (!empty($updateData)) {
                    $user->update($updateData);
                }
            }

            // Crear token API
            $token = $user->createToken('facebook-token-login', ['*'])->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Login exitoso con token de Facebook',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'avatar' => $user->avatar,
                        'facebook_id' => $user->facebook_id,
                    ],
                    'token' => $token,
                    'token_type' => 'Bearer',
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error en Facebook token login', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Token de Facebook inválido o expirado',
                'error' => $e->getMessage()
            ], 401);
        }
    }

    /**
     * Obtener URL de redirección de Facebook (para PWA)
     */
    public function getFacebookAuthUrl()
    {
        try {
            $url = Socialite::driver('facebook')
                ->stateless()
                ->redirect()
                ->getTargetUrl();

            return response()->json([
                'success' => true,
                'data' => [
                    'auth_url' => $url,
                    'client_id' => config('services.facebook.client_id'),
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error generando URL de Facebook', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Error generando URL de autenticación'
            ], 500);
        }
    }
}
