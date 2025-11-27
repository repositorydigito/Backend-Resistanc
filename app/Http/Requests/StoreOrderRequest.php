<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // La autorización se manejará en el controlador/política.
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'order_type' => ['sometimes', 'string', Rule::in(['purchase', 'booking_extras', 'subscription', 'gift'])],
            // 'delivery_method' => ['required', 'string', Rule::in(['pickup', 'delivery', 'digital'])],
            // 'delivery_date' => ['nullable', 'date', 'after_or_equal:today'],
            // 'delivery_time_slot' => ['nullable', 'string', 'max:50'],
            // 'delivery_address' => ['nullable', 'array'],
            'special_instructions' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:1000'],

            'order_items' => ['required', 'array', 'min:1'],
            'order_items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'order_items.*.quantity' => ['required', 'integer', 'min:1'],
            'order_items.*.notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
