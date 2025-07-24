<?php

namespace App\Models;

use App\Enums\AuthProvider;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

/**
 * Cuenta social vinculada (Google, Facebook, etc.)
 *
 * @property int $id ID único de la cuenta social
 * @property int $user_id ID del usuario propietario
 * @property \App\Enums\AuthProvider $provider Proveedor de autenticación (google, facebook)
 * @property string $provider_uid ID único en el proveedor
 * @property string|null $provider_email Email en el proveedor
 * @property string $token Token de acceso (encriptado)
 * @property \Illuminate\Support\Carbon|null $token_expires_at Fecha de expiración del token
 * @property \Illuminate\Support\Carbon|null $created_at Fecha de creación
 * @property \Illuminate\Support\Carbon|null $updated_at Fecha de actualización
 *
 * @property-read \App\Models\User $user Usuario propietario
 * @property-read bool $is_token_expired Si el token está expirado
 */
final class SocialAccount extends Model
{

    // No utilizado
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'provider',
        'provider_uid',
        'provider_email',
        'token',
        'token_expires_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'token',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'provider' => AuthProvider::class,
            'token_expires_at' => 'datetime',
        ];
    }

    /**
     * Get the user that owns the social account.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Interact with the token attribute (encrypt/decrypt).
     */
    protected function token(): Attribute
    {
        return Attribute::make(
            get: fn (string $value) => Crypt::decryptString($value),
            set: fn (string $value) => Crypt::encryptString($value),
        );
    }

    /**
     * Check if the token is expired.
     */
    public function isTokenExpired(): bool
    {
        if (!$this->token_expires_at) {
            return false;
        }

        return $this->token_expires_at->isPast();
    }

    /**
     * Scope to get accounts by provider.
     */
    public function scopeByProvider($query, AuthProvider $provider)
    {
        return $query->where('provider', $provider);
    }

    /**
     * Scope to get active (non-expired) tokens.
     */
    public function scopeActiveTokens($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('token_expires_at')
              ->orWhere('token_expires_at', '>', now());
        });
    }
}
