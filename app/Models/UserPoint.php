<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPoint extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'point_user';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'quantity_point',
        'date_expire',
        'user_id',
        'membresia_id',
        'active_membership_id',
        'package_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date_expire' => 'date',
            'quantity_point' => 'integer',
        ];
    }

    /**
     * Get the user that owns these points.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the membership associated with these points (when earned).
     */
    public function membership(): BelongsTo
    {
        return $this->belongsTo(Membership::class, 'membresia_id');
    }

    /**
     * Get the active membership with which this point is currently being used.
     */
    public function activeMembership(): BelongsTo
    {
        return $this->belongsTo(Membership::class, 'active_membership_id');
    }

    /**
     * Get the package associated with these points.
     */
    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    /**
     * Scope para obtener puntos por membresía activa.
     */
    public function scopeWithActiveMembership($query, ?int $membershipId)
    {
        if ($membershipId === null) {
            return $query->whereNull('active_membership_id');
        }
        return $query->where('active_membership_id', $membershipId);
    }

    /**
     * Scope para obtener puntos no expirados.
     */
    public function scopeNotExpired($query)
    {
        return $query->where('date_expire', '>=', now()->toDateString());
    }

    /**
     * Scope para obtener puntos expirados.
     */
    public function scopeExpired($query)
    {
        return $query->where('date_expire', '<', now()->toDateString());
    }

    /**
     * Actualizar la membresía activa de este punto.
     */
    public function updateActiveMembership(?int $membershipId): bool
    {
        return $this->update(['active_membership_id' => $membershipId]);
    }

    /**
     * Actualizar la membresía activa de todos los puntos de un usuario.
     * Se usa cuando el usuario cambia de membresía.
     */
    public static function updateActiveMembershipForUser(int $userId, ?int $newMembershipId): int
    {
        return static::where('user_id', $userId)
            ->where('date_expire', '>=', now()->toDateString()) // Solo puntos no expirados
            ->update(['active_membership_id' => $newMembershipId]);
    }

    /**
     * Verificar si el punto está expirado.
     */
    public function isExpired(): bool
    {
        return $this->date_expire < now()->toDateString();
    }

    /**
     * Verificar si el punto está activo (no expirado).
     */
    public function isActive(): bool
    {
        return !$this->isExpired();
    }
}
