<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para códigos de recuperación de contraseña
 *
 * @property int $id ID único del código
 * @property string $email Correo electrónico del usuario
 * @property string $code Código de 4 dígitos
 * @property \Illuminate\Support\Carbon $expires_at Fecha de expiración
 * @property bool $used Si el código ya fue usado
 * @property \Illuminate\Support\Carbon|null $used_at Fecha de uso
 * @property \Illuminate\Support\Carbon $created_at Fecha de creación
 * @property \Illuminate\Support\Carbon $updated_at Fecha de actualización
 */
final class PasswordResetCode extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'email',
        'code',
        'expires_at',
        'used',
        'used_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used' => 'boolean',
            'used_at' => 'datetime',
        ];
    }

    /**
     * Scope para códigos válidos (no expirados y no usados)
     */
    public function scopeValid($query)
    {
        return $query->where('expires_at', '>', now())
                    ->where('used', false);
    }

    /**
     * Scope para códigos por email
     */
    public function scopeForEmail($query, string $email)
    {
        return $query->where('email', $email);
    }

    /**
     * Scope para códigos por email y código específico
     */
    public function scopeForEmailAndCode($query, string $email, string $code)
    {
        return $query->where('email', $email)
                    ->where('code', $code);
    }

    /**
     * Marcar el código como usado
     */
    public function markAsUsed(): void
    {
        $this->update([
            'used' => true,
            'used_at' => now(),
        ]);
    }

    /**
     * Verificar si el código está expirado
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Verificar si el código es válido (no expirado y no usado)
     */
    public function isValid(): bool
    {
        return !$this->used && !$this->isExpired();
    }

    /**
     * Generar un código único de 4 dígitos
     */
    public static function generateCode(): string
    {
        do {
            $code = str_pad((string) rand(0, 9999), 4, '0', STR_PAD_LEFT);
        } while (static::where('code', $code)->where('expires_at', '>', now())->exists());

        return $code;
    }

    /**
     * Crear un nuevo código de recuperación
     */
    public static function createForEmail(string $email, int $expireMinutes = 10): self
    {
        // Invalidar códigos anteriores para este email
        static::where('email', $email)
              ->where('used', false)
              ->update(['used' => true, 'used_at' => now()]);

        return static::create([
            'email' => $email,
            'code' => static::generateCode(),
            'expires_at' => now()->addMinutes($expireMinutes),
        ]);
    }
}
