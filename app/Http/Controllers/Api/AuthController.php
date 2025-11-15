<?php


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
use App\Mail\EmailVerificationMailable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
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

            // $stripeCustomer = $user->createAsStripeCustomer();

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

            // Enviar verificación usando EmailVerificationMailable
            Mail::to($user->email)->send(new EmailVerificationMailable($user));

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

            // Preparar información completa del usuario
            $userData = [
                'id' => $user->id,
                'code' => $user->code,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at?->toISOString(),
                'created_at' => $user->created_at?->toISOString(),
                'updated_at' => $user->updated_at?->toISOString(),

                // Roles
                'roles' => $user->roles->map(function ($role) {
                    return [
                        'id' => $role->id,
                        'name' => $role->name,
                        'guard_name' => $role->guard_name,
                    ];
                }),

                // Información del perfil (si existe)
                'profile' => $user->profile ? [
                    'id' => $user->profile->id,
                    'first_name' => $user->profile->first_name,
                    'last_name' => $user->profile->last_name,
                    'full_name' => $user->profile->first_name . ' ' . $user->profile->last_name,
                    'birth_date' => $user->profile->birth_date?->toDateString(),
                    'age' => $user->profile->birth_date ? $user->profile->age : null,
                    'gender' => $user->profile->gender,
                    'phone' => $user->profile->phone,
                    'adress' => $user->profile->adress,
                    'shoe_size_eu' => $user->profile->shoe_size_eu,
                    'profile_image' => $user->profile->profile_image ? asset('storage/' . $user->profile->profile_image) : null,
                    'emergency_contact_name' => $user->profile->emergency_contact_name,
                    'emergency_contact_phone' => $user->profile->emergency_contact_phone,
                    'medical_conditions' => $user->profile->medical_conditions,
                    'is_active' => $user->profile->is_active,
                    'created_at' => $user->profile->created_at?->toISOString(),
                    'updated_at' => $user->profile->updated_at?->toISOString(),
                ] : null,
            ];

            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Usuario autenticado',
                'datoAdicional' => $userData,
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

        Mail::to($user->email)->send(new EmailVerificationMailable($user));

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

    /**
     * Actualizar información del usuario y perfil
     */
    public function updateMe(Request $request)
    {
        try {
            $user = $request->user();

            // Validar datos de entrada
            $validated = $request->validate([
                // Datos básicos del usuario
                'name' => 'sometimes|string|max:255',
                'email' => 'sometimes|string|email|max:255|unique:users,email,' . $user->id,

                // Datos del perfil
                'first_name' => 'sometimes|string|max:255',
                'last_name' => 'sometimes|string|max:255',
                'birth_date' => 'sometimes|date|before:today',
                'gender' => 'sometimes|in:male,female,other,na',
                'phone' => 'sometimes|string|max:20',
                'adress' => 'sometimes|string|max:255',
                'shoe_size_eu' => 'sometimes|string|max:5',
                'profile_image' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            // Actualizar datos básicos del usuario
            if (isset($validated['name'])) {
                $user->name = $validated['name'];
            }

            if (isset($validated['email']) && $validated['email'] !== $user->email) {
                $user->email = $validated['email'];
                $user->email_verified_at = null; // Reset email verification if email changed
            }

            $user->save();

            if ($user->hasStripeId()) {
                $user->syncStripeCustomerDetails();
            }

            // Actualizar o crear perfil
            $profileData = collect($validated)->except(['name', 'email'])->toArray();

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

            // Cargar relaciones para la respuesta
            $user->load(['profile']);

            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Perfil actualizado exitosamente',
                'datoAdicional' => new UserResource($user)
            ], 200);
        } catch (ValidationException $e) {
            // Verificar si es error de email duplicado
            if (isset($e->errors()['email']) && str_contains($e->errors()['email'][0], 'unique')) {
                return response()->json([
                    'exito' => false,
                    'codMensaje' => 2,
                    'mensajeUsuario' => 'El correo electrónico ya está registrado por otro usuario',
                    'datoAdicional' => null,
                ], 200);
            }

            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error de validación',
                'datoAdicional' => $e->errors(),
            ], 200);
        } catch (\Illuminate\Database\QueryException $e) {
            // Manejar errores de base de datos específicos
            if ($e->getCode() == 23000 && str_contains($e->getMessage(), 'Duplicate entry')) {
                return response()->json([
                    'exito' => false,
                    'codMensaje' => 2,
                    'mensajeUsuario' => 'El correo electrónico ya está registrado por otro usuario',
                    'datoAdicional' => null,
                ], 200);
            }

            Log::error('Error de base de datos en Atención [updateMe]', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'url' => request()->fullUrl(),
                'input' => request()->all()
            ]);

            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error de base de datos al actualizar perfil',
                'datoAdicional' => null,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('Error en Atención [updateMe]', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'url' => request()->fullUrl(),
                'input' => request()->all()
            ]);

            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error al actualizar perfil',
                'datoAdicional' => null,
            ], 200);
        }
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
