<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserContactResource;
use App\Models\User;
use App\Models\UserContact;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

/**
 * @tags Contactos de Usuario
 */
final class UserContactController extends Controller
{
    /**
     * Lista todos los contactos de un usuario
     *
     * Obtiene todos los contactos asociados a un usuario específico.
     * Permite filtrar por contacto principal y país.
     *
     * @summary Listar contactos de usuario
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
     *     "address_line": "Av. Javier Prado Este 4200, Surco",
     *     "city": "Lima",
     *     "country": "PE",
     *     "is_primary": true,
     *     "created_at": "2024-01-15T10:30:00.000Z",
     *     "updated_at": "2024-01-15T10:30:00.000Z"
     *   },
     *   {
     *     "id": 2,
     *     "phone": "+51 987 654 322",
     *     "address_line": "Calle San Martín 789, Cercado",
     *     "city": "Arequipa",
     *     "country": "PE",
     *     "is_primary": false,
     *     "created_at": "2024-01-15T10:30:00.000Z",
     *     "updated_at": "2024-01-15T10:30:00.000Z"
     *   }
     * ]
     *
     * @response 404 {
     *   "message": "No query results for model [App\\Models\\User] 1"
     * }
     */
    public function index(User $user): AnonymousResourceCollection
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
     * Crea un nuevo contacto para un usuario
     *
     * Añade un nuevo contacto al usuario especificado. Si se marca como principal,
     * automáticamente desmarca otros contactos principales del mismo usuario.
     *
     * @summary Crear contacto de usuario
     * @operationId createUserContact
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User  $user
     * @return \App\Http\Resources\UserContactResource
     *
     * @bodyParam phone string required Número de teléfono del contacto. Example: +51 987 654 321
     * @bodyParam address_line string optional Dirección completa. Example: Av. Benavides 1234, Miraflores
     * @bodyParam city string optional Ciudad. Example: Lima
     * @bodyParam country string optional Código de país (2 caracteres). Example: PE
     * @bodyParam is_primary boolean optional Si es el contacto principal. Example: true
     *
     * @response 201 {
     *   "id": 1,
     *   "phone": "+51 987 654 321",
     *   "address_line": "Av. Benavides 1234, Miraflores",
     *   "city": "Lima",
     *   "country": "PE",
     *   "is_primary": true,
     *   "created_at": "2024-01-15T10:30:00.000Z",
     *   "updated_at": "2024-01-15T10:30:00.000Z"
     * }
     *
     * @response 422 {
     *   "message": "Los datos proporcionados no son válidos.",
     *   "errors": {
     *     "phone": ["El campo teléfono es obligatorio."],
     *     "country": ["El campo país debe tener exactamente 2 caracteres."],
     *     "address_line": ["La dirección no puede tener más de 255 caracteres."]
     *   }
     * }
     *
     * @response 404 {
     *   "message": "No query results for model [App\\Models\\User] 1"
     * }
     */
    public function store(Request $request, User $user): UserContactResource
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:20'],
            'address_line' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'size:2'],
            'is_primary' => ['nullable', 'boolean'],
        ]);

        // If this is set as primary, unset other primary contacts
        if ($validated['is_primary'] ?? false) {
            $user->contacts()->update(['is_primary' => false]);
        }

        $contact = $user->contacts()->create($validated);

        return new UserContactResource($contact);
    }

    /**
     * Obtiene un contacto específico de un usuario
     *
     * Retorna la información detallada de un contacto específico
     * que pertenece al usuario indicado.
     *
     * @summary Obtener contacto específico de usuario
     * @operationId getUserContact
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\UserContact  $contact
     * @return \App\Http\Resources\UserContactResource
     *
     * @response 200 {
     *   "id": 1,
     *   "phone": "+51 987 654 321",
     *   "address_line": "Av. Conquistadores 138, San Isidro",
     *   "city": "Lima",
     *   "country": "PE",
     *   "is_primary": true,
     *   "created_at": "2024-01-15T10:30:00.000Z",
     *   "updated_at": "2024-01-15T10:30:00.000Z",
     *   "full_address": "Av. Conquistadores 138, San Isidro, Lima, PE"
     * }
     *
     * @response 404 {
     *   "message": "No query results for model [App\\Models\\UserContact] 1"
     * }
     */
    public function show(User $user, UserContact $contact): UserContactResource
    {
        // Ensure the contact belongs to the user
        abort_unless($contact->user_id === $user->id, 404);

        return new UserContactResource($contact);
    }

    /**
     * Actualiza un contacto específico de un usuario
     *
     * Permite actualizar la información de un contacto existente.
     * Solo se actualizan los campos proporcionados en la petición.
     * Si se marca como principal, automáticamente desmarca otros contactos principales.
     *
     * @summary Actualizar contacto de usuario
     * @operationId updateUserContact
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User  $user
     * @param  \App\Models\UserContact  $contact
     * @return \App\Http\Resources\UserContactResource
     *
     * @bodyParam phone string optional Número de teléfono del contacto. Example: +51 987 654 999
     * @bodyParam address_line string optional Dirección completa. Example: Av. Universitaria 1801, San Miguel
     * @bodyParam city string optional Ciudad. Example: Lima
     * @bodyParam country string optional Código de país (2 caracteres). Example: PE
     * @bodyParam is_primary boolean optional Si es el contacto principal. Example: false
     *
     * @response 200 {
     *   "id": 1,
     *   "phone": "+51 987 654 999",
     *   "address_line": "Av. Universitaria 1801, San Miguel",
     *   "city": "Lima",
     *   "country": "PE",
     *   "is_primary": false,
     *   "created_at": "2024-01-15T10:30:00.000Z",
     *   "updated_at": "2024-01-15T16:20:00.000Z"
     * }
     *
     * @response 422 {
     *   "message": "Los datos proporcionados no son válidos.",
     *   "errors": {
     *     "phone": ["El campo teléfono es obligatorio."],
     *     "country": ["El campo país debe tener exactamente 2 caracteres."],
     *     "address_line": ["La dirección no puede tener más de 255 caracteres."]
     *   }
     * }
     *
     * @response 404 {
     *   "message": "No query results for model [App\\Models\\UserContact] 1"
     * }
     */
    public function update(Request $request, User $user, UserContact $contact): UserContactResource
    {
        // Ensure the contact belongs to the user
        abort_unless($contact->user_id === $user->id, 404);

        $validated = $request->validate([
            'phone' => ['sometimes', 'required', 'string', 'max:20'],
            'address_line' => ['sometimes', 'nullable', 'string', 'max:255'],
            'city' => ['sometimes', 'nullable', 'string', 'max:100'],
            'country' => ['sometimes', 'nullable', 'string', 'size:2'],
            'is_primary' => ['sometimes', 'nullable', 'boolean'],
        ]);

        // If this is set as primary, unset other primary contacts
        if (($validated['is_primary'] ?? false) && !$contact->is_primary) {
            $user->contacts()->where('id', '!=', $contact->id)->update(['is_primary' => false]);
        }

        $contact->update($validated);

        return new UserContactResource($contact);
    }

    /**
     * Elimina un contacto específico de un usuario
     *
     * Elimina permanentemente un contacto del usuario.
     * Esta acción no se puede deshacer.
     *
     * @summary Eliminar contacto de usuario
     * @operationId deleteUserContact
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\UserContact  $contact
     * @return \Illuminate\Http\JsonResponse
     *
     * @response 200 {
     *   "message": "Contacto eliminado exitosamente.",
     *   "deleted_contact_id": 1
     * }
     *
     * @response 404 {
     *   "message": "No query results for model [App\\Models\\UserContact] 1"
     * }
     */
    public function destroy(User $user, UserContact $contact): JsonResponse
    {
        // Ensure the contact belongs to the user
        abort_unless($contact->user_id === $user->id, 404);

        $contact->delete();

        return response()->json([
            'message' => 'Contacto eliminado exitosamente.',
            'deleted_contact_id' => $contact->id,
        ], 200);
    }
}
