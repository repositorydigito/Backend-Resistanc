<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Auditoría de intentos de inicio de sesión
 *
 * @property int $id ID único de la auditoría
 * @property int $user_id ID del usuario
 * @property string $ip Dirección IP del intento
 * @property string $user_agent User agent del navegador
 * @property bool $success Si el intento fue exitoso
 * @property \Illuminate\Support\Carbon $created_at Fecha del intento
 *
 * @property-read \App\Models\User $user Usuario propietario
 * @property-read string $browser Navegador detectado
 */
final class LoginAudit extends Model
{
    use HasFactory;

    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'ip',
        'user_agent',
        'success',
        'created_at',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'success' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Get the user that owns the login audit.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to get successful logins.
     */
    public function scopeSuccessful($query)
    {
        return $query->where('success', true);
    }

    /**
     * Scope to get failed logins.
     */
    public function scopeFailed($query)
    {
        return $query->where('success', false);
    }

    /**
     * Scope to get logins by IP.
     */
    public function scopeByIp($query, string $ip)
    {
        return $query->where('ip', $ip);
    }

    /**
     * Scope to get recent logins.
     */
    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }

    /**
     * Get the browser name from user agent.
     */
    public function getBrowserAttribute(): string
    {
        $userAgent = $this->user_agent;

        if (str_contains($userAgent, 'Chrome')) {
            return 'Chrome';
        } elseif (str_contains($userAgent, 'Firefox')) {
            return 'Firefox';
        } elseif (str_contains($userAgent, 'Safari')) {
            return 'Safari';
        } elseif (str_contains($userAgent, 'Edge')) {
            return 'Edge';
        }

        return 'Unknown';
    }
}
