<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserMembership extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'membership_id',
        'discipline_id',
        'total_free_classes',
        'used_free_classes',
        'remaining_free_classes',
        'activation_date',
        'expiry_date',
        'status',
        'source_package_id', // ID del paquete que otorgó esta membresía
        'notes',
    ];

    protected $casts = [
        'activation_date' => 'date',
        'expiry_date' => 'date',
        'total_free_classes' => 'integer',
        'used_free_classes' => 'integer',
        'remaining_free_classes' => 'integer',
    ];

    /**
     * Get the user that owns this membership.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the membership details.
     */
    public function membership(): BelongsTo
    {
        return $this->belongsTo(Membership::class);
    }

    /**
     * Get the discipline details.
     */
    public function discipline(): BelongsTo
    {
        return $this->belongsTo(Discipline::class);
    }

    /**
     * Get the source package that granted this membership.
     */
    public function sourcePackage(): BelongsTo
    {
        return $this->belongsTo(Package::class, 'source_package_id');
    }

    /**
     * Get the bookings made with this membership.
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'user_membership_id');
    }

    /**
     * Scope to get only active memberships.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to get expired memberships.
     */
    public function scopeExpired($query)
    {
        return $query->where('expiry_date', '<', now());
    }

    /**
     * Scope to get memberships expiring soon.
     */
    public function scopeExpiringSoon($query, int $days = 7)
    {
        return $query->whereBetween('expiry_date', [now(), now()->addDays($days)]);
    }

    /**
     * Check if the membership is expired.
     */
    public function getIsExpiredAttribute(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    /**
     * Check if the membership is valid (active and not expired).
     */
    public function getIsValidAttribute(): bool
    {
        return $this->status === 'active' &&
            $this->activation_date && $this->activation_date->isPast() &&
            $this->expiry_date && $this->expiry_date->isFuture();
    }

    /**
     * Check if the membership has free classes available.
     */
    public function getHasFreeClassesAttribute(): bool
    {
        return $this->remaining_free_classes > 0;
    }

    /**
     * Check if this membership can be used for a specific discipline.
     */
    public function canUseForDiscipline(int $disciplineId): bool
    {
        return $this->is_valid &&
               $this->has_free_classes &&
               $this->discipline_id === $disciplineId;
    }

    /**
     * Use free classes from this membership.
     */
    public function useFreeClasses(int $classes = 1): bool
    {
        // Validar que la membresía esté activa y no expirada
        if ($this->status !== 'active') {
            return false;
        }

        if ($this->expiry_date && $this->expiry_date->isPast()) {
            return false;
        }

        if ($this->remaining_free_classes < $classes) {
            return false;
        }

        $this->increment('used_free_classes', $classes);
        $this->decrement('remaining_free_classes', $classes);

        return true;
    }

    /**
     * Refund free classes to this membership.
     */
    public function refundFreeClasses(int $classes = 1): bool
    {
        $this->increment('remaining_free_classes', $classes);

        // Solo decrementar used_free_classes si hay suficientes
        if ($this->used_free_classes >= $classes) {
            $this->decrement('used_free_classes', $classes);
        }

        return true;
    }

    /**
     * Activate the membership.
     */
    public function activate(): void
    {
        $this->update([
            'status' => 'active',
            'activation_date' => $this->activation_date ?? now(),
        ]);
    }

    /**
     * Suspend the membership.
     */
    public function suspend(): void
    {
        $this->update([
            'status' => 'suspended',
        ]);
    }

    /**
     * Expire the membership.
     */
    public function expire(): void
    {
        $this->update([
            'status' => 'expired',
        ]);
    }

    /**
     * Get the days remaining until expiry.
     */
    public function getDaysRemainingAttribute(): int
    {
        if (!$this->expiry_date) {
            return 0;
        }

        if ($this->expiry_date->isPast()) {
            return 0;
        }

        return (int) now()->diffInDays($this->expiry_date, false);
    }

    /**
     * Get the usage percentage.
     */
    public function getUsagePercentageAttribute(): int
    {
        if (!$this->total_free_classes || $this->total_free_classes <= 0) {
            return 0;
        }

        return (int) round(($this->used_free_classes / $this->total_free_classes) * 100);
    }

    /**
     * Get the status display name.
     */
    public function getStatusDisplayNameAttribute(): string
    {
        return match ($this->status) {
            'active' => 'Activo',
            'expired' => 'Expirado',
            'pending' => 'Pendiente',
            'suspended' => 'Suspendido',
            'cancelled' => 'Cancelado',
            default => ucfirst($this->status),
        };
    }

    /**
     * Check if user has available free classes for a specific discipline.
     */
    public static function hasAvailableFreeClasses(int $userId, int $disciplineId): bool
    {
        return static::where('user_id', $userId)
            ->where('discipline_id', $disciplineId)
            ->where('status', 'active')
            ->where('expiry_date', '>', now())
            ->where('remaining_free_classes', '>', 0)
            ->exists();
    }

    /**
     * Get the best available membership for a discipline (most classes remaining).
     */
    public static function getBestAvailableMembership(int $userId, int $disciplineId): ?self
    {
        return static::where('user_id', $userId)
            ->where('discipline_id', $disciplineId)
            ->where('status', 'active')
            ->where('expiry_date', '>', now())
            ->where('remaining_free_classes', '>', 0)
            ->orderBy('remaining_free_classes', 'desc')
            ->orderBy('expiry_date', 'asc')
            ->first();
    }
}
