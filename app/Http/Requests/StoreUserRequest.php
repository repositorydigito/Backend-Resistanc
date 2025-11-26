<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

final class StoreUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // TODO: Implement authorization logic
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::defaults()],
            
            // Optional profile data
            'profile.first_name' => ['nullable', 'string', 'max:255'],
            'profile.last_name' => ['nullable', 'string', 'max:255'],
            'profile.birth_date' => ['nullable', 'date', 'before:today'],
            'profile.gender' => ['nullable', 'in:female,male,other,na'],
            'profile.shoe_size_eu' => ['nullable', 'integer', 'min:20', 'max:60'],
            
            // Optional contact data
            'contact.phone' => ['nullable', 'string', 'max:20'],
            'contact.address_line' => ['nullable', 'string', 'max:255'],
            'contact.city' => ['nullable', 'string', 'max:100'],
            'contact.country' => ['nullable', 'string', 'size:2'], // ISO country code
            'contact.is_primary' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'Este email ya está registrado en el sistema.',
            'password.confirmed' => 'La confirmación de contraseña no coincide.',
            'profile.birth_date.before' => 'La fecha de nacimiento debe ser anterior a hoy.',
            'profile.gender.in' => 'El género debe ser: female, male, other o na.',
            'contact.country.size' => 'El código de país debe tener exactamente 2 caracteres.',
        ];
    }
}
