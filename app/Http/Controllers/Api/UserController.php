<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\LoginAuditResource;
use App\Http\Resources\SocialAccountResource;
use App\Http\Resources\UserContactResource;
use App\Http\Resources\UserDetailResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Enums\Gender;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * @tags Usuarios
 */
final class UserController extends Controller
{
    /**
     * Lista todos los usuarios del sistema
     *
     * Obtiene una lista paginada de usuarios con sus perfiles y contactos principales.
     * Incluye funcionalidades de búsqueda y filtrado avanzado.
     *
     * @summary Listar usuarios
     * @operationId getUsersList
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     *
     * @queryParam search string Buscar por nombre o correo electrónico del usuario. Example: Juan
     * @queryParam has_profile boolean Filtrar usuarios con/sin perfil completo. Example: true
     * @queryParam per_page integer Número de usuarios por página (máximo 100). Example: 15
     * @queryParam page integer Número de página para la paginación. Example: 1
     *
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "María González",
     *       "email": "maria.gonzalez@ejemplo.com",
     *       "email_verified_at": "2024-01-15T10:30:00.000Z",
     *       "created_at": "2024-01-15T10:30:00.000Z",
     *       "updated_at": "2024-01-15T10:30:00.000Z",
     *       "full_name": "María Elena González Rodríguez",
     *       "has_complete_profile": true,
     *       "profile": {
     *         "id": 1,
     *         "first_name": "María Elena",
     *         "last_name": "González Rodríguez",
     *         "birth_date": "1992-03-20",
     *         "gender": "female",
     *         "shoe_size_eu": 38,
     *         "age": 32
     *       },
     *       "primary_contact": {
     *         "id": 1,
     *         "phone": "+51 987 654 321",
     *         "address_line": "Av. Javier Prado 1234",
     *         "city": "Lima",
     *         "country": "PE",
     *         "is_primary": true
     *       },
     *       "contacts_count": 2,
     *       "social_accounts_count": 1,
     *       "login_audits_count": 15
     *     }
     *   ],
     *   "links": {
     *     "first": "http://rsistanc.test/api/users?page=1",
     *     "last": "http://rsistanc.test/api/users?page=10",
     *     "prev": null,
     *     "next": "http://rsistanc.test/api/users?page=2"
     *   },
     *   "meta": {
     *     "current_page": 1,
     *     "from": 1,
     *     "last_page": 10,
     *     "per_page": 15,
     *     "to": 15,
     *     "total": 140
     *   }
     * }
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $users = User::query()
            ->with(['profile', 'primaryContact'])
            ->withCount(['contacts', 'socialAccounts', 'loginAudits'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search');
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
            })
            ->when($request->filled('has_profile'), function ($query) use ($request) {
                if ($request->boolean('has_profile')) {
                    $query->whereHas('profile');
                } else {
                    $query->whereDoesntHave('profile');
                }
            })
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return UserResource::collection($users);
    }

    /**
     * Obtiene los detalles completos de un usuario específico
     *
     * Retorna información detallada del usuario incluyendo perfil, contactos,
     * cuentas sociales y auditoría de logins (limitada para administradores).
     *
     * @summary Obtener usuario por ID
     * @operationId getUserById
     *
     * @param  \App\Models\User  $user
     * @return \App\Http\Resources\UserDetailResource
     *
     * @response 200 {
     *   "id": 1,
     *   "name": "Carlos Mendoza",
     *   "email": "carlos.mendoza@ejemplo.com",
     *   "email_verified_at": "2024-01-15T10:30:00.000Z",
     *   "created_at": "2024-01-15T10:30:00.000Z",
     *   "updated_at": "2024-01-15T10:30:00.000Z",
     *   "full_name": "Carlos Alberto Mendoza Silva",
     *   "has_complete_profile": true,
     *   "profile": {
     *     "id": 1,
     *     "first_name": "Carlos Alberto",
     *     "last_name": "Mendoza Silva",
     *     "birth_date": "1988-07-12",
     *     "gender": "male",
     *     "shoe_size_eu": 43,
     *     "age": 36,
     *     "full_name": "Carlos Alberto Mendoza Silva"
     *   },
     *   "contacts": [
     *     {
     *       "id": 1,
     *       "phone": "+51 987 654 321",
     *       "address_line": "Av. Larco 456, Miraflores",
     *       "city": "Lima",
     *       "country": "PE",
     *       "is_primary": true,
     *       "created_at": "2024-01-15T10:30:00.000Z",
     *       "updated_at": "2024-01-15T10:30:00.000Z"
     *     }
     *   ],
     *   "primary_contact": {
     *     "id": 1,
     *     "phone": "+51 987 654 321",
     *     "address_line": "Av. Larco 456, Miraflores",
     *     "city": "Lima",
     *     "country": "PE",
     *     "is_primary": true
     *   },
     *   "social_accounts": [
     *     {
     *       "id": 1,
     *       "provider": "google",
     *       "provider_id": "123456789",
     *       "created_at": "2024-01-15T10:30:00.000Z"
     *     }
     *   ],
     *   "contacts_count": 2,
     *   "social_accounts_count": 1,
     *   "login_audits_count": 15
     * }
     *
     * @response 404 {
     *   "message": "No query results for model [App\\Models\\User] 1"
     * }
     */
    public function show(User $user): UserDetailResource
    {
        $user->load([
            'profile',
            'contacts',
            'primaryContact',
            'socialAccounts',
            'loginAudits' => function ($query) {
                $query->latest()->limit(10);
            }
        ]);

        $user->loadCount(['contacts', 'socialAccounts', 'loginAudits']);

        return new UserDetailResource($user);
    }

    /**
     * Obtiene el perfil de un usuario específico
     *
     * Retorna la información del perfil del usuario incluyendo datos personales
     * como nombre completo, fecha de nacimiento, género y talla de zapato.
     *
     * @summary Obtener perfil de usuario
     * @operationId getUserProfile
     *
     * @param  \App\Models\User  $user
     * @return \App\Http\Resources\UserDetailResource
     *
     * @response 200 {
     *   "id": 1,
     *   "name": "Roberto Silva",
     *   "email": "roberto.silva@ejemplo.com",
     *   "profile": {
     *     "id": 1,
     *     "first_name": "Roberto Carlos",
     *     "last_name": "Silva Morales",
     *     "birth_date": "1987-11-30",
     *     "gender": "male",
     *     "shoe_size_eu": 44,
     *     "age": 37,
     *     "full_name": "Roberto Carlos Silva Morales"
     *   }
     * }
     *
     * @response 404 {
     *   "message": "No query results for model [App\\Models\\User] 1"
     * }
     */
    public function profile(User $user): UserDetailResource
    {
        $user->load('profile');

        return new UserDetailResource($user);
    }

    /**
     * Obtiene todos los contactos de un usuario
     *
     * Lista todos los contactos asociados al usuario con opciones de filtrado
     * por contacto principal y código de país.
     *
     * @summary Obtener contactos de usuario
     * @operationId getUserContacts
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     *
     * @queryParam primary_only boolean Filtrar solo contactos principales. Example: true
     * @queryParam country string Filtrar por código de país (2 caracteres). Example: PE
     *
     * @response 200 [
     *   {
     *     "id": 1,
     *     "phone": "+51 987 654 321",
     *     "address_line": "Av. El Sol 567, San Isidro",
     *     "city": "Lima",
     *     "country": "PE",
     *     "is_primary": true,
     *     "created_at": "2024-01-15T10:30:00.000Z",
     *     "updated_at": "2024-01-15T10:30:00.000Z"
     *   }
     * ]
     *
     * @response 404 {
     *   "message": "No query results for model [App\\Models\\User] 1"
     * }
     */
    public function contacts(User $user): AnonymousResourceCollection
    {
        $contacts = $user->contacts()
            ->when(request()->filled('primary_only'), function ($query) {
                $query->where('is_primary', true);
            })
            ->when(request()->filled('country'), function ($query) {
                $query->where('country', request()->string('country'));
            })
            ->get();

        return UserContactResource::collection($contacts);
    }

    /**
     * Obtiene las cuentas sociales de un usuario
     *
     * Lista todas las cuentas sociales vinculadas al usuario
     * (Google, Facebook, etc.) con opciones de filtrado.
     *
     * @summary Obtener cuentas sociales de usuario
     * @operationId getUserSocialAccounts
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     *
     * @queryParam provider string Filtrar por proveedor (google, facebook). Example: google
     * @queryParam active_only boolean Filtrar solo cuentas con tokens activos. Example: true
     *
     * @response 200 [
     *   {
     *     "id": 1,
     *     "provider": "google",
     *     "provider_id": "123456789012345",
     *     "provider_email": "usuario@gmail.com",
     *     "provider_name": "Usuario Ejemplo",
     *     "provider_avatar": "https://lh3.googleusercontent.com/a/avatar.jpg",
     *     "access_token_expires_at": "2024-02-15T10:30:00.000Z",
     *     "created_at": "2024-01-15T10:30:00.000Z",
     *     "updated_at": "2024-01-15T10:30:00.000Z"
     *   },
     *   {
     *     "id": 2,
     *     "provider": "facebook",
     *     "provider_id": "987654321098765",
     *     "provider_email": "usuario@facebook.com",
     *     "provider_name": "Usuario Facebook",
     *     "provider_avatar": "https://graph.facebook.com/avatar.jpg",
     *     "access_token_expires_at": null,
     *     "created_at": "2024-01-10T08:15:00.000Z",
     *     "updated_at": "2024-01-10T08:15:00.000Z"
     *   }
     * ]
     *
     * @response 404 {
     *   "message": "No query results for model [App\\Models\\User] 1"
     * }
     */
    public function socialAccounts(User $user): AnonymousResourceCollection
    {
        $socialAccounts = $user->socialAccounts()
            ->when(request()->filled('provider'), function ($query) {
                $query->where('provider', request()->string('provider'));
            })
            ->when(request()->filled('active_only'), function ($query) {
                $query->activeTokens();
            })
            ->get();

        return SocialAccountResource::collection($socialAccounts);
    }

    /**
     * Obtiene la auditoría de inicios de sesión de un usuario
     *
     * Lista todos los intentos de inicio de sesión del usuario con opciones
     * de filtrado por éxito/fallo, IP y período de tiempo.
     *
     * @summary Obtener auditoría de inicios de sesión
     * @operationId getUserLoginAudits
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     *
     * @queryParam success boolean Filtrar por intentos exitosos (true) o fallidos (false). Example: true
     * @queryParam ip string Filtrar por dirección IP específica. Example: 192.168.1.100
     * @queryParam recent_hours integer Filtrar por horas recientes (por defecto 24). Example: 48
     * @queryParam per_page integer Número de registros por página (por defecto 20). Example: 10
     *
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "ip_address": "192.168.1.100",
     *       "user_agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36",
     *       "successful": true,
     *       "failure_reason": null,
     *       "location": "Lima, PE",
     *       "device_type": "desktop",
     *       "browser": "Chrome",
     *       "platform": "Windows",
     *       "attempted_at": "2024-01-15T10:30:00.000Z",
     *       "created_at": "2024-01-15T10:30:00.000Z"
     *     },
     *     {
     *       "id": 2,
     *       "ip_address": "192.168.1.101",
     *       "user_agent": "Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X)",
     *       "successful": false,
     *       "failure_reason": "invalid_credentials",
     *       "location": "Lima, PE",
     *       "device_type": "mobile",
     *       "browser": "Safari",
     *       "platform": "iOS",
     *       "attempted_at": "2024-01-15T09:15:00.000Z",
     *       "created_at": "2024-01-15T09:15:00.000Z"
     *     }
     *   ],
     *   "links": {
     *     "first": "http://rsistanc.test/api/users/1/login-audits?page=1",
     *     "last": "http://rsistanc.test/api/users/1/login-audits?page=5",
     *     "prev": null,
     *     "next": "http://rsistanc.test/api/users/1/login-audits?page=2"
     *   },
     *   "meta": {
     *     "current_page": 1,
     *     "from": 1,
     *     "last_page": 5,
     *     "per_page": 20,
     *     "to": 20,
     *     "total": 95
     *   }
     * }
     *
     * @response 404 {
     *   "message": "No query results for model [App\\Models\\User] 1"
     * }
     */
    public function loginAudits(User $user): AnonymousResourceCollection
    {
        $audits = $user->loginAudits()
            ->when(request()->filled('success'), function ($query) {
                if (request()->boolean('success')) {
                    $query->successful();
                } else {
                    $query->failed();
                }
            })
            ->when(request()->filled('ip'), function ($query) {
                $query->byIp(request()->string('ip'));
            })
            ->when(request()->filled('recent_hours'), function ($query) {
                $hours = request()->integer('recent_hours', 24);
                $query->recent($hours);
            })
            ->latest()
            ->paginate(request()->integer('per_page', 20));

        return LoginAuditResource::collection($audits);
    }

    /**
     * Crea un nuevo usuario en el sistema
     *
     * Registra un nuevo usuario con información básica, perfil opcional y contacto inicial.
     * Todos los datos se validan según las reglas definidas en StoreUserRequest.
     *
     * @summary Crear nuevo usuario
     * @operationId createUser
     *
     * @param  \App\Http\Requests\StoreUserRequest  $request
     * @return \App\Http\Resources\UserDetailResource
     *
     * @bodyParam name string required Nombre completo del usuario. Example: Ana Lucía Torres
     * @bodyParam email string required Correo electrónico único del usuario. Example: ana.torres@ejemplo.com
     * @bodyParam password string required Contraseña del usuario (mínimo 8 caracteres). Example: MiContraseña123
     * @bodyParam password_confirmation string required Confirmación de la contraseña. Example: MiContraseña123
     * @bodyParam profile.first_name string optional Nombre(s) del usuario. Example: Ana Lucía
     * @bodyParam profile.last_name string optional Apellido(s) del usuario. Example: Torres Vásquez
     * @bodyParam profile.birth_date string optional Fecha de nacimiento (YYYY-MM-DD). Example: 1995-08-25
     * @bodyParam profile.gender string optional Género del usuario (female, male, other, na). Example: female
     * @bodyParam profile.shoe_size_eu integer optional Talla de zapato europea (20-60). Example: 37
     * @bodyParam contact.phone string optional Teléfono de contacto. Example: +51 987 654 321
     * @bodyParam contact.address_line string optional Dirección completa. Example: Jr. de la Unión 789, Centro de Lima
     * @bodyParam contact.city string optional Ciudad. Example: Lima
     * @bodyParam contact.country string optional Código de país (2 caracteres). Example: PE
     * @bodyParam contact.is_primary boolean optional Si es el contacto principal. Example: true
     *
     * @response 201 {
     *   "id": 1,
     *   "name": "Ana Lucía Torres",
     *   "email": "ana.torres@ejemplo.com",
     *   "email_verified_at": null,
     *   "created_at": "2024-01-15T10:30:00.000Z",
     *   "updated_at": "2024-01-15T10:30:00.000Z",
     *   "full_name": "Ana Lucía Torres Vásquez",
     *   "has_complete_profile": true,
     *   "profile": {
     *     "id": 1,
     *     "first_name": "Ana Lucía",
     *     "last_name": "Torres Vásquez",
     *     "birth_date": "1995-08-25",
     *     "gender": "female",
     *     "shoe_size_eu": 37,
     *     "age": 29,
     *     "full_name": "Ana Lucía Torres Vásquez"
     *   },
     *   "contacts": [
     *     {
     *       "id": 1,
     *       "phone": "+51 987 654 321",
     *       "address_line": "Jr. de la Unión 789, Centro de Lima",
     *       "city": "Lima",
     *       "country": "PE",
     *       "is_primary": true,
     *       "created_at": "2024-01-15T10:30:00.000Z",
     *       "updated_at": "2024-01-15T10:30:00.000Z"
     *     }
     *   ],
     *   "primary_contact": {
     *     "id": 1,
     *     "phone": "+51 987 654 321",
     *     "address_line": "Jr. de la Unión 789, Centro de Lima",
     *     "city": "Lima",
     *     "country": "PE",
     *     "is_primary": true
     *   },
     *   "contacts_count": 1,
     *   "social_accounts_count": 0,
     *   "login_audits_count": 0
     * }
     *
     * @response 422 {
     *   "message": "Los datos proporcionados no son válidos.",
     *   "errors": {
     *     "email": ["Este correo electrónico ya está registrado en el sistema."],
     *     "password": ["La confirmación de contraseña no coincide."],
     *     "profile.birth_date": ["La fecha de nacimiento debe ser anterior a hoy."],
     *     "profile.gender": ["El género debe ser: female, male, other o na."]
     *   }
     * }
     */
    public function store(StoreUserRequest $request): UserDetailResource
    {
        $validated = $request->validated();

        return DB::transaction(function () use ($validated) {
            // Create user
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);

            // Create profile if provided
            if (isset($validated['profile'])) {
                $profileData = $validated['profile'];
                if (isset($profileData['gender'])) {
                    $profileData['gender'] = Gender::from($profileData['gender']);
                }
                $user->profile()->create($profileData);
            }

            // Create contact if provided
            if (isset($validated['contact'])) {
                $contactData = $validated['contact'];
                $contactData['is_primary'] = $contactData['is_primary'] ?? true;
                $user->contacts()->create($contactData);
            }

            // Load relationships for response
            $user->load(['profile', 'contacts', 'primaryContact']);
            $user->loadCount(['contacts', 'socialAccounts', 'loginAudits']);

            return new UserDetailResource($user);
        });
    }

    /**
     * Actualiza un usuario existente en el sistema
     *
     * Permite actualizar la información básica del usuario y su perfil.
     * Solo se actualizan los campos proporcionados en la petición.
     *
     * @summary Actualizar usuario
     * @operationId updateUser
     *
     * @param  \App\Http\Requests\UpdateUserRequest  $request
     * @param  \App\Models\User  $user
     * @return \App\Http\Resources\UserDetailResource
     *
     * @bodyParam name string optional Nombre completo del usuario. Example: Patricia Morales
     * @bodyParam email string optional Correo electrónico único del usuario. Example: patricia.morales@ejemplo.com
     * @bodyParam password string optional Nueva contraseña del usuario (mínimo 8 caracteres). Example: NuevaContraseña123
     * @bodyParam password_confirmation string optional Confirmación de la nueva contraseña. Example: NuevaContraseña123
     * @bodyParam profile.first_name string optional Nombre(s) del usuario. Example: Patricia Elena
     * @bodyParam profile.last_name string optional Apellido(s) del usuario. Example: Morales Vega
     * @bodyParam profile.birth_date string optional Fecha de nacimiento (YYYY-MM-DD). Example: 1989-12-10
     * @bodyParam profile.gender string optional Género del usuario (female, male, other, na). Example: female
     * @bodyParam profile.shoe_size_eu integer optional Talla de zapato europea (20-60). Example: 39
     *
     * @response 200 {
     *   "id": 1,
     *   "name": "Patricia Morales",
     *   "email": "patricia.morales@ejemplo.com",
     *   "email_verified_at": "2024-01-15T10:30:00.000Z",
     *   "created_at": "2024-01-15T10:30:00.000Z",
     *   "updated_at": "2024-01-15T15:45:00.000Z",
     *   "full_name": "Patricia Elena Morales Vega",
     *   "has_complete_profile": true,
     *   "profile": {
     *     "id": 1,
     *     "first_name": "Patricia Elena",
     *     "last_name": "Morales Vega",
     *     "birth_date": "1989-12-10",
     *     "gender": "female",
     *     "shoe_size_eu": 39,
     *     "age": 35,
     *     "full_name": "Patricia Elena Morales Vega"
     *   },
     *   "contacts": [
     *     {
     *       "id": 1,
     *       "phone": "+51 987 654 321",
     *       "address_line": "Av. Salaverry 2255, Jesús María",
     *       "city": "Lima",
     *       "country": "PE",
     *       "is_primary": true,
     *       "created_at": "2024-01-15T10:30:00.000Z",
     *       "updated_at": "2024-01-15T10:30:00.000Z"
     *     }
     *   ],
     *   "primary_contact": {
     *     "id": 1,
     *     "phone": "+51 987 654 321",
     *     "address_line": "Av. Salaverry 2255, Jesús María",
     *     "city": "Lima",
     *     "country": "PE",
     *     "is_primary": true
     *   },
     *   "social_accounts": [],
     *   "contacts_count": 1,
     *   "social_accounts_count": 0,
     *   "login_audits_count": 8
     * }
     *
     * @response 422 {
     *   "message": "Los datos proporcionados no son válidos.",
     *   "errors": {
     *     "email": ["Este correo electrónico ya está registrado en el sistema."],
     *     "password": ["La confirmación de contraseña no coincide."],
     *     "profile.birth_date": ["La fecha de nacimiento debe ser anterior a hoy."],
     *     "profile.gender": ["El género debe ser: female, male, other o na."]
     *   }
     * }
     *
     * @response 404 {
     *   "message": "No query results for model [App\\Models\\User] 1"
     * }
     */
    public function update(UpdateUserRequest $request, User $user): UserDetailResource
    {
        $validated = $request->validated();

        return DB::transaction(function () use ($user, $validated) {
            // Update user basic data
            $userData = collect($validated)->except(['profile'])->toArray();
            if (isset($userData['password'])) {
                $userData['password'] = Hash::make($userData['password']);
            }

            if (!empty($userData)) {
                $user->update($userData);
            }

            // Update profile if provided
            if (isset($validated['profile'])) {
                $profileData = $validated['profile'];
                if (isset($profileData['gender'])) {
                    $profileData['gender'] = Gender::from($profileData['gender']);
                }

                if ($user->profile) {
                    $user->profile->update($profileData);
                } else {
                    $user->profile()->create($profileData);
                }
            }

            // Load relationships for response
            $user->load(['profile', 'contacts', 'primaryContact', 'socialAccounts']);
            $user->loadCount(['contacts', 'socialAccounts', 'loginAudits']);

            return new UserDetailResource($user);
        });
    }

    /**
     * Elimina un usuario del sistema
     *
     * Elimina permanentemente un usuario y todos sus datos relacionados
     * (perfil, contactos, cuentas sociales y auditoría de logins).
     * Esta acción no se puede deshacer.
     *
     * @summary Eliminar usuario
     * @operationId deleteUser
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\JsonResponse
     *
     * @response 200 {
     *   "message": "Usuario eliminado exitosamente.",
     *   "deleted_user_id": 1
     * }
     *
     * @response 404 {
     *   "message": "No query results for model [App\\Models\\User] 1"
     * }
     */
    public function destroy(User $user): JsonResponse
    {
        return DB::transaction(function () use ($user) {
            // Delete related records (cascade should handle this, but being explicit)
            $user->profile()?->delete();
            $user->contacts()->delete();
            $user->socialAccounts()->delete();
            $user->loginAudits()->delete();

            // Delete the user
            $user->delete();

            return response()->json([
                'message' => 'Usuario eliminado exitosamente.',
                'deleted_user_id' => $user->id,
            ], 200);
        });
    }
}
