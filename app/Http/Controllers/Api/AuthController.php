<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\AuthResource;
use App\Http\Resources\UserDetailResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Dedoc\Scramble\Attributes\BodyParameter;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;

/**
 * @tags Autenticación
 */
final class AuthController extends Controller
{
    /**
     * Registrar un nuevo usuario
     *
     * Crea una nueva cuenta de usuario en el sistema RSISTANC.
     * Genera automáticamente un token de acceso para la API.
     *
     * @summary Registrar nuevo usuario
     * @operationId registerUser
     *
     * @param  \App\Http\Requests\RegisterRequest  $request
     * @return \App\Http\Resources\AuthResource
     *
     * @bodyParam name string required Nombre completo del usuario. Example: Juan Carlos Pérez
     * @bodyParam email string required Correo electrónico del usuario. Example: juan.perez@resistanc.com
     * @bodyParam password string required Contraseña del usuario (mín. 8 caracteres). Example: MiPassword123!
     * @bodyParam password_confirmation string required Confirmación de la contraseña. Example: MiPassword123!
     * @bodyParam device_name string Nombre del dispositivo para el token. Example: Mi Aplicación Móvil
     *
     * @response 201 {
     *   "user": {
     *     "id": 1,
     *     "name": "Ana Lucía Torres",
     *     "email": "ana.torres@ejemplo.com",
     *     "email_verified_at": null,
     *     "created_at": "2024-01-15T10:30:00.000Z",
     *     "updated_at": "2024-01-15T10:30:00.000Z",
     *     "full_name": null,
     *     "has_complete_profile": false,
     *     "profile": null,
     *     "primary_contact": null
     *   },
     *   "token": {
     *     "access_token": "1|abc123def456...",
     *     "token_type": "Bearer"
     *   },
     *   "meta": {
     *     "login_count": 0,
     *     "last_login": null
     *   }
     * }
     *
     * @response 422 {
     *   "message": "Los datos proporcionados no son válidos.",
     *   "errors": {
     *     "email": ["Este correo electrónico ya está registrado."],
     *     "password": ["La contraseña debe tener al menos 8 caracteres."]
     *   }
     * }
     */
    public function register(RegisterRequest $request): AuthResource
    {
        $validated = $request->validated();

        $user = DB::transaction(function () use ($validated) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'], // Auto-hashed by model
            ]);

            // Create login audit for registration
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
            $ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? request()->ip() ?? '127.0.0.1';
            $user->loginAudits()->create([
                'ip' => $ipAddress,
                'user_agent' => $userAgent,
                'success' => true,
                'created_at' => now(),
            ]);

            return $user;
        });

        // Generate API token
        $deviceName = $validated['device_name'] ?? 'API Token';
        $token = $user->createToken($deviceName)->plainTextToken;

        // Add token to user for resource
        $user->token = $token;

        // Load relationships for response
        $user->load(['profile', 'primaryContact', 'loginAudits']);

        return new AuthResource($user);
    }

    /**
     * Iniciar sesión
     *
     * Autentica un usuario existente y genera un token de acceso para la API.
     * Registra el intento de login en la auditoría del sistema.
     *
     * @summary Iniciar sesión
     * @operationId loginUser
     *
     * @param  \App\Http\Requests\LoginRequest  $request
     * @return \App\Http\Resources\AuthResource
     *
     * @response 200 {
     *   "user": {
     *     "id": 1,
     *     "name": "Ana Lucía Torres",
     *     "email": "ana.torres@ejemplo.com",
     *     "email_verified_at": "2024-01-15T10:30:00.000Z",
     *     "created_at": "2024-01-15T10:30:00.000Z",
     *     "updated_at": "2024-01-15T10:30:00.000Z",
     *     "full_name": "Ana Lucía Torres Mendoza",
     *     "has_complete_profile": true,
     *     "profile": {
     *       "id": 1,
     *       "first_name": "Ana Lucía",
     *       "last_name": "Torres Mendoza",
     *       "gender": "female",
     *       "birth_date": "1990-05-15",
     *       "bio": "Desarrolladora Full Stack especializada en Laravel y Vue.js"
     *     },
     *     "primary_contact": {
     *       "id": 1,
     *       "phone": "+51 987 654 321",
     *       "address_line": "Av. Javier Prado Este 4200, Surco",
     *       "city": "Lima",
     *       "country": "PE",
     *       "is_primary": true
     *     }
     *   },
     *   "token": {
     *     "access_token": "1|abc123def456...",
     *     "token_type": "Bearer"
     *   },
     *   "meta": {
     *     "login_count": 15,
     *     "last_login": "2024-01-14T08:20:00.000Z"
     *   }
     * }
     *
     * @response 422 {
     *   "message": "Las credenciales proporcionadas son incorrectas.",
     *   "errors": {
     *     "email": ["Las credenciales proporcionadas son incorrectas."]
     *   }
     * }
     */
    #[BodyParameter('email', description: 'Correo electrónico del usuario', type: 'string', example: 'migelo5511@gmail.com')]
    #[BodyParameter('password', description: 'Contraseña del usuario', type: 'string', example: '123456789')]
    #[BodyParameter('remember', description: 'Recordar sesión por más tiempo', type: 'boolean', example: true)]
    #[BodyParameter('device_name', description: 'Nombre del dispositivo para el token', type: 'string', example: 'Mi Dispositivo')]
    public function login(LoginRequest $request): AuthResource
    {
        $validated = $request->validated();

        // Find user by email
        $user = User::where('email', $validated['email'])->first();

        // Check credentials
        if (!$user || !Hash::check($validated['password'], $user->password)) {
            // Log failed attempt if user exists
            if ($user) {
                $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
                $ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? request()->ip() ?? '127.0.0.1';
                $user->loginAudits()->create([
                    'ip' => $ipAddress,
                    'user_agent' => $userAgent,
                    'success' => false,
                    'created_at' => now(),
                ]);
            }

            throw ValidationException::withMessages([
                'email' => ['Las credenciales proporcionadas son incorrectas.'],
            ]);
        }

        // Log successful login
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        $ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? request()->ip() ?? '127.0.0.1';
        $user->loginAudits()->create([
            'ip' => $ipAddress,
            'user_agent' => $userAgent,
            'success' => true,
            'created_at' => now(),
        ]);

        // Generate API token
        $deviceName = $validated['device_name'] ?? 'API Token';
        $token = $user->createToken($deviceName)->plainTextToken;

        // Add token to user for resource
        $user->token = $token;

        // Load relationships for response
        $user->load(['profile', 'primaryContact', 'loginAudits']);

        return new AuthResource($user);
    }

    /**
     * Obtener usuario autenticado
     *
     * Retorna la información completa del usuario actualmente autenticado,
     * incluyendo su perfil, contacto principal y estadísticas de login.
     *
     * @summary Obtener usuario autenticado
     * @operationId getAuthenticatedUser
     *
     * @return \App\Http\Resources\UserDetailResource
     *
     * @response 200 {
     *   "id": 1,
     *   "name": "Ana Lucía Torres",
     *   "email": "ana.torres@ejemplo.com",
     *   "email_verified_at": "2024-01-15T10:30:00.000Z",
     *   "created_at": "2024-01-15T10:30:00.000Z",
     *   "updated_at": "2024-01-15T10:30:00.000Z",
     *   "full_name": "Ana Lucía Torres Mendoza",
     *   "has_complete_profile": true,
     *   "profile": {
     *     "id": 1,
     *     "first_name": "Ana Lucía",
     *     "last_name": "Torres Mendoza",
     *     "gender": "female",
     *     "birth_date": "1990-05-15",
     *     "bio": "Desarrolladora Full Stack especializada en Laravel y Vue.js"
     *   },
     *   "contacts": [...],
     *   "primary_contact": {...},
     *   "social_accounts": [...],
     *   "login_audits": [...],
     *   "contacts_count": 2,
     *   "social_accounts_count": 1,
     *   "login_audits_count": 15
     * }
     *
     * @response 401 {
     *   "message": "Unauthenticated."
     * }
     */
    public function me(Request $request): UserDetailResource
    {
        $user = $request->user();

        $user->load([
            'profile',
            'contacts',
            'primaryContact',
            'socialAccounts',
            'loginAudits' => function ($query) {
                $query->orderBy('created_at', 'desc')->limit(10);
            }
        ]);

        $user->loadCount(['contacts', 'socialAccounts', 'loginAudits']);

        return new UserDetailResource($user);
    }

    /**
     * Cerrar sesión
     *
     * Revoca el token de acceso actual del usuario autenticado.
     * El token dejará de ser válido inmediatamente.
     *
     * @summary Cerrar sesión
     * @operationId logoutUser
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @response 200 {
     *   "message": "Sesión cerrada exitosamente.",
     *   "logged_out_at": "2024-01-15T10:30:00.000Z"
     * }
     *
     * @response 401 {
     *   "message": "Unauthenticated."
     * }
     */
    public function logout(Request $request): JsonResponse
    {
        // Revoke current token
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Sesión cerrada exitosamente.',
            'logged_out_at' => now()->toISOString(),
        ]);
    }

    /**
     * Cerrar todas las sesiones
     *
     * Revoca todos los tokens de acceso del usuario autenticado.
     * Útil para cerrar sesión en todos los dispositivos.
     *
     * @summary Cerrar todas las sesiones
     * @operationId logoutAllDevices
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @response 200 {
     *   "message": "Todas las sesiones han sido cerradas exitosamente.",
     *   "tokens_revoked": 3,
     *   "logged_out_at": "2024-01-15T10:30:00.000Z"
     * }
     *
     * @response 401 {
     *   "message": "Unauthenticated."
     * }
     */
    public function logoutAll(Request $request): JsonResponse
    {
        $user = $request->user();
        $tokensCount = $user->tokens()->count();

        // Revoke all tokens
        $user->tokens()->delete();

        return response()->json([
            'message' => 'Todas las sesiones han sido cerradas exitosamente.',
            'tokens_revoked' => $tokensCount,
            'logged_out_at' => now()->toISOString(),
        ]);
    }

    /**
     * Renovar token de acceso
     *
     * Genera un nuevo token de acceso y revoca el actual.
     * Útil para renovar tokens antes de que expiren.
     *
     * @summary Renovar token de acceso
     * @operationId refreshToken
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @bodyParam device_name string Nombre del dispositivo para el nuevo token. Example: iPhone de Ana
     *
     * @response 200 {
     *   "message": "Token renovado exitosamente.",
     *   "token": {
     *     "access_token": "2|xyz789abc123...",
     *     "token_type": "Bearer"
     *   },
     *   "refreshed_at": "2024-01-15T10:30:00.000Z"
     * }
     *
     * @response 401 {
     *   "message": "Unauthenticated."
     * }
     */
    public function refresh(Request $request): JsonResponse
    {
        $user = $request->user();
        $currentToken = $request->user()->currentAccessToken();

        // Get device name from request or use current token name
        $deviceName = $request->input('device_name', $currentToken->name);

        // Create new token
        $newToken = $user->createToken($deviceName)->plainTextToken;

        // Revoke current token
        $currentToken?->delete();

        return response()->json([
            'message' => 'Token renovado exitosamente.',
            'token' => [
                'access_token' => $newToken,
                'token_type' => 'Bearer',
            ],
            'refreshed_at' => now()->toISOString(),
        ]);
    }

    /**
     * Extract browser name from user agent string
     */
    private function getBrowserFromUserAgent(?string $userAgent): ?string
    {
        if (!$userAgent) {
            return null;
        }

        $browsers = [
            'Chrome' => '/Chrome\/[\d.]+/',
            'Firefox' => '/Firefox\/[\d.]+/',
            'Safari' => '/Safari\/[\d.]+/',
            'Edge' => '/Edg\/[\d.]+/',
            'Opera' => '/Opera\/[\d.]+/',
            'Internet Explorer' => '/MSIE [\d.]+/',
        ];

        foreach ($browsers as $browser => $pattern) {
            if (preg_match($pattern, $userAgent)) {
                return $browser;
            }
        }

        return 'Unknown';
    }

    /**
     * Extract platform name from user agent string
     */
    private function getPlatformFromUserAgent(?string $userAgent): ?string
    {
        if (!$userAgent) {
            return null;
        }

        $platforms = [
            'Windows' => '/Windows NT/',
            'macOS' => '/Mac OS X/',
            'Linux' => '/Linux/',
            'Android' => '/Android/',
            'iOS' => '/iPhone|iPad/',
        ];

        foreach ($platforms as $platform => $pattern) {
            if (preg_match($pattern, $userAgent)) {
                return $platform;
            }
        }

        return 'Unknown';
    }



    // Logueo por Facebook

    public function redirectToFacebook()
    {
        return Socialite::driver('facebook')->redirect();
    }
    public function loginWithFacebookToken(Request $request)
    {

        $accessToken = $request->input('access_token');

        try {
            $facebookUser = Socialite::driver('facebook')->stateless()->userFromToken($accessToken);

            $user = User::firstOrCreate(
                ['email' => $facebookUser->getEmail()],
                [
                    'name' => $facebookUser->getName(),
                    'password' => bcrypt(Str::random(16)),
                ]
            );

            $token = $user->createToken('Facebook Login')->plainTextToken;

            return response()->json([
                'user' => $user,
                'token' => [
                    'access_token' => $token,
                    'token_type' => 'Bearer',
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Error al autenticar con Facebook.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    // Logueo por Google
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }
    public function loginWithGoogleToken(Request $request)
    {
        $accessToken = $request->input('access_token');

        try {
            $googleUser = Socialite::driver('google')->stateless()->userFromToken($accessToken);

            $user = User::firstOrCreate(
                ['email' => $googleUser->getEmail()],
                [
                    'name' => $googleUser->getName(),
                    'password' => bcrypt(Str::random(16)),
                ]
            );

            $token = $user->createToken('Google Login')->plainTextToken;

            return response()->json([
                'user' => $user,
                'token' => [
                    'access_token' => $token,
                    'token_type' => 'Bearer',
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Error al autenticar con Google.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
