<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Enum para géneros de usuario
 *
 * Valores disponibles:
 * - female: Femenino
 * - male: Masculino
 * - other: Otro
 * - na: No especifica
 */
enum Gender: string
{
    case FEMALE = 'female';
    case MALE = 'male';
    case OTHER = 'other';
    case NOT_APPLICABLE = 'na';

    /**
     * Get all enum values as array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get enum label for display
     */
    public function label(): string
    {
        return match ($this) {
            self::FEMALE => 'Femenino',
            self::MALE => 'Masculino',
            self::OTHER => 'Otro',
            self::NOT_APPLICABLE => 'No especifica',
        };
    }
}
