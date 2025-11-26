<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Enum para proveedores de autenticación OAuth
 *
 * Valores disponibles:
 * - google: Google OAuth
 * - facebook: Facebook OAuth
 */
enum AuthProvider: string
{
    case GOOGLE = 'google';
    case FACEBOOK = 'facebook';

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
            self::GOOGLE => 'Google',
            self::FACEBOOK => 'Facebook',
        };
    }

    /**
     * Get provider icon or class
     */
    public function icon(): string
    {
        return match ($this) {
            self::GOOGLE => 'fab fa-google',
            self::FACEBOOK => 'fab fa-facebook',
        };
    }
}
