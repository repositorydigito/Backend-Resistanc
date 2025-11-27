<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

final class UpdateUserRequest extends FormRequest
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
        $userId = $this->route('user')?->id ?? $this->route('user');
        
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => [
                'sometimes', 
                'required', 
                'string', 
                'email', 
                'max:255', 
                Rule::unique('users')->ignore($userId)
            ],
            'password' => ['sometimes', 'required', 'confirmed', Password::defaults()],
            
            // Profile data
            'profile.first_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'profile.last_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'profile.birth_date' => ['sometimes', 'nullable', 'date', 'before:today'],
            'profile.gender' => ['sometimes', 'nullable', 'in:female,male,other,na'],
            'profile.shoe_size_eu' => ['sometimes', 'nullable', 'integer', 'min:20', 'max:60'],
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
        ];
    }
}
