<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @property int|null $per_page Número de horarios por página (máximo 50)
 * @property int|null $page Número de página para la paginación
 * @property int|null $class_id Filtrar por ID de clase específica
 * @property int|null $instructor_id Filtrar por ID de instructor específico
 * @property int|null $studio_id Filtrar por ID de estudio específico
 * @property int|null $discipline_id Filtrar por ID de disciplina específica
 * @property string|null $scheduled_date Filtrar por fecha específica (formato: YYYY-MM-DD)
 * @property string|null $date_from Filtrar desde una fecha específica (formato: YYYY-MM-DD)
 * @property string|null $date_to Filtrar hasta una fecha específica (formato: YYYY-MM-DD)
 * @property string|null $search Buscar por nombre de clase
 * @property bool|null $include_counts Incluir contadores de reservas y asientos
 * @property bool|null $include_relations Incluir información completa de relaciones
 */
class ClassScheduleIndexRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'per_page' => 'sometimes|integer|min:1|max:50',
            'page' => 'sometimes|integer|min:1',
            'class_id' => 'sometimes|integer|exists:classes,id',
            'instructor_id' => 'sometimes|integer|exists:users,id',
            'studio_id' => 'sometimes|integer|exists:studios,id',
            'discipline_id' => 'sometimes|integer|exists:disciplines,id',
            'scheduled_date' => 'sometimes|date_format:Y-m-d',
            'date_from' => 'sometimes|date_format:Y-m-d',
            'date_to' => 'sometimes|date_format:Y-m-d|after_or_equal:date_from',
            'search' => 'sometimes|string|max:255',
            'include_counts' => 'sometimes|boolean',
            'include_relations' => 'sometimes|boolean',
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
            'scheduled_date.date_format' => 'La fecha debe tener el formato YYYY-MM-DD (ejemplo: 2024-06-15)',
            'date_from.date_format' => 'La fecha desde debe tener el formato YYYY-MM-DD (ejemplo: 2024-06-10)',
            'date_to.date_format' => 'La fecha hasta debe tener el formato YYYY-MM-DD (ejemplo: 2024-06-20)',
            'date_to.after_or_equal' => 'La fecha hasta debe ser igual o posterior a la fecha desde',
            'per_page.max' => 'El número máximo de elementos por página es 50',
            'class_id.exists' => 'La clase especificada no existe',
            'instructor_id.exists' => 'El instructor especificado no existe',
            'studio_id.exists' => 'El estudio especificado no existe',
            'discipline_id.exists' => 'La disciplina especificada no existe',
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
            'per_page' => 'elementos por página',
            'page' => 'página',
            'class_id' => 'ID de clase',
            'instructor_id' => 'ID de instructor',
            'studio_id' => 'ID de estudio',
            'discipline_id' => 'ID de disciplina',
            'scheduled_date' => 'fecha programada',
            'date_from' => 'fecha desde',
            'date_to' => 'fecha hasta',
            'search' => 'búsqueda',
            'include_counts' => 'incluir contadores',
            'include_relations' => 'incluir relaciones',
        ];
    }
}
