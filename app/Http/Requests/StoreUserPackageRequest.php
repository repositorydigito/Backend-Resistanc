<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreUserPackageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization is handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'package_id' => ['required', 'integer', 'exists:packages,id'],
            'total_classes' => ['required', 'integer', 'min:1'],
            'amount_paid_soles' => ['required', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'max:3', 'in:PEN,USD,EUR'],
            'purchase_date' => ['required', 'date'],
            'activation_date' => ['nullable', 'date', 'after_or_equal:purchase_date'],
            'status' => ['nullable', 'string', Rule::in(['pending', 'active', 'expired', 'cancelled', 'suspended'])],
            'auto_renew' => ['nullable', 'boolean'],
            'renewal_price' => ['nullable', 'numeric', 'min:0'],
            'benefits_included' => ['nullable', 'array'],
            'benefits_included.*' => ['string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
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
            'package_id.required' => 'El paquete es obligatorio.',
            'package_id.exists' => 'El paquete seleccionado no existe.',
            'total_classes.required' => 'El número total de clases es obligatorio.',
            'total_classes.min' => 'El número de clases debe ser mayor a 0.',
            'amount_paid_soles.required' => 'El monto pagado es obligatorio.',
            'amount_paid_soles.min' => 'El monto debe ser mayor o igual a 0.',
            'purchase_date.required' => 'La fecha de compra es obligatoria.',

            'activation_date.after_or_equal' => 'La fecha de activación debe ser posterior o igual a la fecha de compra.',
            'currency.in' => 'La moneda debe ser PEN, USD o EUR.',
            'status.in' => 'El estado debe ser: pending, active, expired, cancelled o suspended.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default values
        $this->merge([
            'currency' => $this->currency ?? 'PEN',
            'status' => $this->status ?? 'pending',
            'auto_renew' => $this->boolean('auto_renew', false),
        ]);
    }
}
