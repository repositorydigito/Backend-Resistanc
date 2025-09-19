<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Filament\Pages\Auth\Login;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\AuthResource;
use App\Http\Resources\UserDetailResource;
use App\Http\Resources\LoginResource;
use App\Http\Resources\UserResource;
use App\Http\Resources\SimpleUserResource;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Dedoc\Scramble\Attributes\BodyParameter;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

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
     */
    public function register(Request $request)
    {

// Validación mejorada
    $validated = $request->validate([
        'first_name' => 'required|string|max:255|min:2',
        'last_name' => 'required|string|max:255|min:2',
        'email' => 'required|string|email|max:255|unique:users,email',
        'password' => 'required|string|confirmed|min:8',
        'birth_date' => 'nullable|date',
        'gender' => 'required|string',
        'phone' => 'required|string|min:9|max:20', // Cambiado a string para permitir formatos como +51 999999999
        'adress' => 'required|string|max:255',
        'shoe_size_eu' => 'nullable|string|max:5',
        // 'device_name' => 'nullable|string|max:255'
    ]);

    try {
        // Crear usuario con contraseña hasheada explícitamente
        $user = User::create([
            'name' => $validated['first_name'] . ' ' . $validated['last_name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']), // Hash explícito
        ]);

        // Crear perfil de usuario
        UserProfile::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'birth_date' => $validated['birth_date'],
            'gender' => $validated['gender'],
            'phone' => $validated['phone'],
            'adress' => $validated['adress'],
            'shoe_size_eu' => $validated['shoe_size_eu'] ?? null,
            'user_id' => $user->id
        ]);

        // Asignar rol
        $user->assignRole('Cliente');

        // Enviar verificación
        $user->sendEmailVerificationNotification();

        // Generar token
        $deviceName = $validated['device_name'] ?? 'API Token';
        $token = $user->createToken($deviceName)->plainTextToken;

        // Cargar relaciones y preparar respuesta
        $user->load(['profile', 'loginAudits']);
        $user->token = $token;

        return response()->json([
            'exito' => true,
            'codMensaje' => 1,
            'mensajeUsuario' => 'Registro de usuario exitoso',
            'datoAdicional' => new AuthResource($user)
        ], 200); // 201 Created es más semántico que 200

    } catch (ValidationException $e) {
        // Errores de validación (aunque no debería llegar aquí por el validate inicial)
        return response()->json([
            'exito' => false,
            'codMensaje' => 2,
            'mensajeUsuario' => 'Datos inválidos',
            'datoAdicional' => $e->errors()
        ], 200); // 422 Unprocessable Entity

    } catch (\Throwable $th) {
        // Otros errores
        return response()->json([
            'exito' => false,
            'codMensaje' => 0,
            'mensajeUsuario' => 'Error al registrar usuario',
            'datoAdicional' => $th->getMessage()
        ], 200); // 500 Internal Server Error
    }
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
     *   "exito": true,
     *   "codMensaje": 1,
     *   "mensajeUsuario": "Login Exitoso",
     *   "datoAdicional": {
     *     "id": 1,
     *     "nombre": "Ana Lucía Torres",
     *     "correo": "ana.torres@ejemplo.com",
     *     "roles": [
     *       {
     *         "id": 1,
     *         "nombre": "Cliente"
     *       }
     *     ],
     *     "token": "1|abc123def456..."
     *   }
     * }
     *
     * @responseHeaders {
     *   "Authorization": "Bearer 1|abc123def456..."
     * }
     *
     * @response 200 {
     *   "exito": false,
     *   "codMensaje": 0,
     *   "mensajeUsuario": "Las credenciales proporcionadas son incorrectas.",
     *   "datoAdicional": null
     * }
     *
     * @response 200 {
     *   "exito": false,
     *   "codMensaje": 0,
     *   "mensajeUsuario": "Tu dirección de correo electrónico no ha sido verificada. Por favor, verifica tu email antes de iniciar sesión.",
     *   "datoAdicional": null
     * }
     */
    #[BodyParameter('email', description: 'Correo electrónico del usuario', type: 'string', example: 'aizencode@gmail.com')]
    #[BodyParameter('password', description: 'Contraseña del usuario', type: 'string', example: '123456789')]
    #[BodyParameter('remember', description: 'Recordar sesión por más tiempo', type: 'boolean', example: true)]
    #[BodyParameter('device_name', description: 'Nombre del dispositivo para el token', type: 'string', example: 'Mi Dispositivo')]
    public function login(LoginRequest $request): JsonResponse
    {
        try {
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

                return response()->json([
                    'exito' => false,
                    'codMensaje' => 0,
                    'mensajeUsuario' => 'Las credenciales proporcionadas son incorrectas.',
                    'datoAdicional' => null,
                ], 200);
            }

            // Verificar si el usuario tiene el rol de "Cliente" (sin exponer esta información)
            if (!$user->hasRole('Cliente')) {
                // Log failed attempt for non-client users
                $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
                $ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? request()->ip() ?? '127.0.0.1';
                $user->loginAudits()->create([
                    'ip' => $ipAddress,
                    'user_agent' => $userAgent,
                    'success' => false,
                    'created_at' => now(),
                ]);

                return response()->json([
                    'exito' => false,
                    'codMensaje' => 0,
                    'mensajeUsuario' => 'Las credenciales proporcionadas son incorrectas.',
                    'datoAdicional' => null,
                ], 200);
            }


            // Verificar email para todos los usuarios
            if (!$user->hasVerifiedEmail()) {
                return response()->json([
                    'exito' => false,
                    'codMensaje' => 0,
                    'mensajeUsuario' => 'Tu dirección de correo electrónico no ha sido verificada. Por favor, verifica tu email antes de iniciar sesión.',
                    'datoAdicional' => null,
                ], 200);
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

            // Obtener todos los roles del usuario con sus detalles
            $roles = $user->roles->map(function ($role) {
                return [
                    'id' => $role->id,
                    'nombre' => $role->name,
                ];
            });

            // Load relationships for response
            $user->load(['roles', 'profile', 'loginAudits']);
            $user->token = $token;

            // Return JSON response
            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Login Exitoso',
                'datoAdicional' => LoginResource::make($user)->toArray(request()),
            ])->header('Authorization', 'Bearer ' . $token);
        } catch (ValidationException $e) {
            Log::error('Error en Atención [login]', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'url' => request()->fullUrl(),
                'input' => request()->all()
            ]);

            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error de validación',
                'datoAdicional' => $e->errors(),
            ], 200);
        } catch (\Throwable $e) {
            Log::error('Error en Atención [login]', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'url' => request()->fullUrl(),
                'input' => request()->all()
            ]);

            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error interno',
                'datoAdicional' => $e->getMessage(),
            ], 200);
        }
    }


    /**
     * Obtener usuario autenticado
     *
     * Retorna la información del usuario actualmente autenticado.
     */
    public function me(): JsonResponse
    {
        try {

            // Verificar si el usuario está autenticado
            if (!auth()->check()) {
                return response()->json([
                    'exito' => false,
                    'codMensaje' => 0,
                    'mensajeUsuario' => 'Usuario no autenticado',
                    'datoAdicional' => null,
                ], 200); // Código 401 para no autenticado
            }

            // Obtener el usuario autenticado
            $user = User::find(Auth::id());

            if (!$user) {
                return response()->json([
                    'exito' => false,
                    'codMensaje' => 0,
                    'mensajeUsuario' => 'Usuario no encontrado',
                    'datoAdicional' => null,
                ], 200); // Código 404 para no encontrado
            }


            // Load relationships for response
            $user->load(['roles', 'profile']);

            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Usuario autenticado',
                'datoAdicional' => new UserResource($user),
            ], 200);
        } catch (\Throwable $e) {
            Log::error('Error en Atención [me]', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'url' => request()->fullUrl(),
                'input' => request()->all()
            ]);

            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error interno',
                'datoAdicional' => $e->getMessage(),
            ], 200);
        }
    }

    /**
     * Verifica de usuario
     *
     */
    public function resendVerificationEmail(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Tu email ya ha sido verificado.',
                'datoAdicional' => null,
            ], 200);
        }

        $user->sendEmailVerificationNotification();

        return response()->json([
            'exito' => true,
            'codMensaje' => 1,
            'mensajeUsuario' => 'Email de verificación enviado correctamente.',
            'datoAdicional' => null,
        ]);
    }

    /**
     * Cerrar sesión
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



    // public function redirectToFacebook()
    // {
    //     return Socialite::driver('facebook')->redirect();
    // }


    // public function loginWithFacebookToken(Request $request)
    // {
    //     $accessToken = $request->input('access_token');

    //     try {
    //         $facebookUser = Socialite::driver('facebook')->stateless()->userFromToken($accessToken);

    //         $user = User::firstOrCreate(
    //             ['email' => $facebookUser->getEmail()],
    //             [
    //                 'name' => $facebookUser->getName(),
    //                 'password' => bcrypt(Str::random(16)),
    //             ]
    //         );

    //         $token = $user->createToken('Facebook Login')->plainTextToken;

    //         return response()->json([
    //             'user' => $user,
    //             'token' => [
    //                 'access_token' => $token,
    //                 'token_type' => 'Bearer',
    //             ],
    //         ]);
    //     } catch (\Throwable $e) {
    //         return response()->json([
    //             'message' => 'Error al autenticar con Facebook.',
    //             'error' => $e->getMessage(),
    //         ], 500);
    //     }
    // }



    // public function redirectToGoogle()
    // {
    //     return Socialite::driver('google')->redirect();
    // }

    // public function loginWithGoogleToken(Request $request)
    // {
    //     $accessToken = $request->input('access_token');

    //     try {
    //         $googleUser = Socialite::driver('google')->stateless()->userFromToken($accessToken);

    //         $user = User::firstOrCreate(
    //             ['email' => $googleUser->getEmail()],
    //             [
    //                 'name' => $googleUser->getName(),
    //                 'password' => bcrypt(Str::random(16)),
    //             ]
    //         );

    //         $token = $user->createToken('Google Login')->plainTextToken;

    //         return response()->json([
    //             'user' => $user,
    //             'token' => [
    //                 'access_token' => $token,
    //                 'token_type' => 'Bearer',
    //             ],
    //         ]);
    //     } catch (\Throwable $e) {
    //         return response()->json([
    //             'message' => 'Error al autenticar con Google.',
    //             'error' => $e->getMessage(),
    //         ], 500);
    //     }
    // }

    /**
     * Actualizar información completa del usuario
     */
    public function updateMe(Request $request): AuthResource
    {
        $user = $request->user();

        // Validar datos de entrada
        $validated = $request->validate([
            // Datos básicos del usuario
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255',

            // Datos del perfil
            'profile.first_name' => 'sometimes|string|max:255',
            'profile.last_name' => 'sometimes|string|max:255',
            'profile.birth_date' => 'sometimes|date|before:today',
            'profile.gender' => 'sometimes|in:male,female,other',
            'profile.shoe_size_eu' => 'sometimes|integer|min:20|max:50',
            'profile.profile_image' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',

            // Datos de contactos
            'contacts' => 'sometimes|array',
            'contacts.*.phone' => 'sometimes|string|max:20',
            'contacts.*.address_line' => 'sometimes|string|max:255',
            'contacts.*.city' => 'sometimes|string|max:100',
            'contacts.*.country' => 'sometimes|string|size:2',
            'contacts.*.is_primary' => 'sometimes|boolean',
        ]);

        $user = DB::transaction(function () use ($user, $validated) {
            // Actualizar datos básicos del usuario
            if (isset($validated['name'])) {
                $user->name = $validated['name'];
            }

            if (isset($validated['email']) && $validated['email'] !== $user->email) {
                $user->email = $validated['email'];
                // Reset email verification if email changed
                $user->email_verified_at = null;
            }

            $user->save();

            // Actualizar o crear perfil
            if (isset($validated['profile'])) {
                $profileData = $validated['profile'];

                // Manejar la subida de imagen de perfil
                if (isset($profileData['profile_image']) && $profileData['profile_image'] instanceof \Illuminate\Http\UploadedFile) {
                    // Eliminar imagen anterior si existe
                    if ($user->profile && $user->profile->profile_image) {
                        \Storage::disk('public')->delete($user->profile->profile_image);
                    }

                    // Guardar nueva imagen
                    $imagePath = $profileData['profile_image']->store('user/profile', 'public');
                    $profileData['profile_image'] = $imagePath;
                }

                if ($user->profile) {
                    $user->profile->update($profileData);
                } else {
                    $user->profile()->create($profileData);
                }
            }

            // Actualizar contactos
            if (isset($validated['contacts'])) {
                foreach ($validated['contacts'] as $contactData) {
                    // Si es contacto primario, desactivar otros
                    if (isset($contactData['is_primary']) && $contactData['is_primary']) {
                        $user->contacts()->update(['is_primary' => false]);
                    }

                    // Si el contacto tiene ID, actualizar
                    if (isset($contactData['id'])) {
                        $contact = $user->contacts()->find($contactData['id']);
                        if ($contact) {
                            $contact->update($contactData);
                        }
                    } else {
                        // Si no tiene ID, verificar si ya existe un contacto con ese teléfono
                        if (isset($contactData['phone'])) {
                            $existingContact = $user->contacts()->where('phone', $contactData['phone'])->first();
                            if ($existingContact) {
                                // Actualizar el contacto existente
                                $existingContact->update($contactData);
                            } else {
                                // Crear nuevo contacto
                                $user->contacts()->create($contactData);
                            }
                        } else {
                            // Si no hay teléfono, crear nuevo contacto
                            $user->contacts()->create($contactData);
                        }
                    }
                }
            }

            // Create update audit
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

        // Generate new API token
        $deviceName = $request->input('device_name', 'API Token Updated');
        $token = $user->createToken($deviceName)->plainTextToken;

        // Add token to user for resource
        $user->token = $token;

        // Load relationships for response
        $user->load(['profile', 'loginAudits']);

        return new AuthResource($user);
    }
    /**
     * Editar contraseña
     */
    public function updatePassword(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'current_password' => ['required', 'string'],
                'password' => [
                    'required',
                    'string',
                    'confirmed',
                    Password::min(8)
                        ->letters()
                        ->mixedCase()
                        ->numbers()
                        ->symbols()
                        ->uncompromised(),
                ],
            ]);

            // Verificar que la contraseña actual sea correcta
            if (!Hash::check($request->current_password, auth()->user()->password)) {
                return response()->json([
                    'exito' => false,
                    'codMensaje' => 2,
                    'mensajeUsuario' => 'La contraseña actual es incorrecta',
                    'datoAdicional' => null,
                ], 200);
            }

            $user = auth()->user();

            // Actualizar la contraseña
            $user->update([
                'password' => Hash::make($request->password),
            ]);

            // Eliminar todos los tokens existentes del usuario (cerrar sesiones en otros dispositivos)
            $user->tokens()->delete();

            // Generar un nuevo token para mantener la sesión activa en el dispositivo actual
            $newToken = $user->createToken('auth-token')->plainTextToken;

            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Contraseña actualizada exitosamente. Se han cerrado todas las demás sesiones.',
                'datoAdicional' => [
                    'token' => $newToken,
                    'token_type' => 'Bearer',
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                    ]
                ],
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error de validación',
                'datoAdicional' => $e->errors(),
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error al actualizar la contraseña',
                'datoAdicional' => $th->getMessage(),
            ], 200);
        }
    }
}
