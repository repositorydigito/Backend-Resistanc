<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Request para reservar múltiples asientos en un horario de clase
 *
 * @property array $class_schedule_seat_ids Array de IDs de class_schedule_seat a reservar
 * @property int|null $minutes_to_expire Minutos antes de que expire la reserva (opcional, default: 15)
 */
class ReserveSeatsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // La autorización se maneja en el middleware de autenticación
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'class_schedule_id' => [
                'required',
                'integer',
                'exists:class_schedules,id'
            ],
            'class_schedule_seat_ids' => [
                'required',
                'array',
                'min:1',
                'max:50' // Máximo 50 asientos por reserva (permite reservar toda la capacidad del estudio)
            ],
            'class_schedule_seat_ids.*' => [
                'required',
                'integer',
                'exists:class_schedule_seat,id'
            ],
            'minutes_to_expire' => [
                'nullable',
                'integer',
                'min:5',
                'max:60' // Entre 5 y 60 minutos
            ]
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
            'class_schedule_seat_ids.required' => 'Debe especificar al menos un asiento para reservar',
            'class_schedule_seat_ids.array' => 'Los IDs de asientos deben ser un array',
            'class_schedule_seat_ids.min' => 'Debe reservar al menos 1 asiento',
            'class_schedule_seat_ids.max' => 'No puede reservar más de 50 asientos a la vez',
            'class_schedule_seat_ids.*.required' => 'Cada ID de asiento es requerido',
            'class_schedule_seat_ids.*.integer' => 'Los IDs de asientos deben ser números enteros',
            'class_schedule_seat_ids.*.exists' => 'Uno o más asientos especificados no existen',
            'minutes_to_expire.integer' => 'Los minutos de expiración deben ser un número entero',
            'minutes_to_expire.min' => 'La reserva debe durar al menos 5 minutos',
            'minutes_to_expire.max' => 'La reserva no puede durar más de 60 minutos',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'class_schedule_seat_ids' => 'asientos',
            'class_schedule_seat_ids.*' => 'ID de asiento',
            'minutes_to_expire' => 'minutos de expiración',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Asegurar que class_schedule_seat_ids sea un array
        if ($this->has('class_schedule_seat_ids') && !is_array($this->class_schedule_seat_ids)) {
            $this->merge([
                'class_schedule_seat_ids' => [$this->class_schedule_seat_ids]
            ]);
        }

        // Establecer valor por defecto para minutes_to_expire
        if (!$this->has('minutes_to_expire') || $this->minutes_to_expire === null) {
            $this->merge([
                'minutes_to_expire' => 15
            ]);
        }
    }
}
