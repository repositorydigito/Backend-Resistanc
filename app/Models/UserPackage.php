<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class UserPackage extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'package_id',
        'package_code',
        'total_classes',
        'used_classes',
        'remaining_classes',
        'amount_paid_soles',
        'currency',
        'purchase_date',
        'activation_date',
        'expiry_date',
        'status',
        'auto_renew',
        'renewal_price',
        'benefits_included',
        'notes',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'activation_date' => 'date',
        'expiry_date' => 'date',
        'total_classes' => 'integer',
        'used_classes' => 'integer',
        'remaining_classes' => 'integer',
        'amount_paid_soles' => 'decimal:2',
        'renewal_price' => 'decimal:2',
        'benefits_included' => 'array',
        'auto_renew' => 'boolean',
    ];

    /**
     * Get the user that owns this package.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the package details.
     */
    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    /**
     * Get the user who gifted this package.
     */
    public function giftFrom(): BelongsTo
    {
        return $this->belongsTo(User::class, 'gift_from_user_id');
    }

    /**
     * Get the bookings made with this package.
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Scope to get only active packages.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to get expired packages.
     */
    public function scopeExpired($query)
    {
        return $query->where('expiry_date', '<', now());
    }

    /**
     * Scope to get packages expiring soon.
     */
    public function scopeExpiringSoon($query, int $days = 7)
    {
        return $query->whereBetween('expiry_date', [now(), now()->addDays($days)]);
    }

    /**
     * Check if the package is expired.
     */
    public function getIsExpiredAttribute(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    /**
     * Check if the package is valid (active and not expired).
     */
    public function getIsValidAttribute(): bool
    {
        return $this->status === 'active' &&
               $this->activation_date && $this->activation_date->isPast() &&
               $this->expiry_date && $this->expiry_date->isFuture();
    }

    /**
     * Check if the package has credits available.
     */
    public function getHasCreditsAttribute(): bool
    {
        return $this->remaining_credits > 0;
    }

    /**
     * Check if the package is a gift.
     */
    public function getIsGiftAttribute(): bool
    {
        return $this->gift_from_user_id !== null;
    }

    /**
     * Get the days remaining until expiry.
     */
    public function getDaysRemainingAttribute(): int
    {
        if (!$this->expiry_date || $this->is_expired) {
            return 0;
        }

        return $this->expiry_date->diffInDays(now());
    }

    /**
     * Get the usage percentage.
     */
    public function getUsagePercentageAttribute(): int
    {
        if (!$this->original_credits || $this->original_credits <= 0) {
            return 0;
        }

        return (int) round(($this->used_credits / $this->original_credits) * 100);
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
     * Use credits from this package.
     */
    public function useCredits(int $credits): bool
    {
        if (!$this->is_valid || $this->remaining_credits < $credits) {
            return false;
        }

        $this->increment('used_credits', $credits);
        $this->decrement('remaining_credits', $credits);

        return true;
    }

    /**
     * Refund credits to this package.
     */
    public function refundCredits(int $credits): void
    {
        $this->decrement('used_credits', $credits);
        $this->increment('remaining_credits', $credits);
    }

    /**
     * Use classes from this package.
     */
    public function useClasses(int $classes = 1): bool
    {
        if (!$this->is_valid || $this->remaining_classes < $classes) {
            return false;
        }

        $this->increment('used_classes', $classes);
        $this->decrement('remaining_classes', $classes);

        return true;
    }

    /**
     * Refund classes to this package.
     */
    public function refundClasses(int $classes = 1): bool
    {
        if ($this->used_classes < $classes) {
            return false;
        }

        $this->decrement('used_classes', $classes);
        $this->increment('remaining_classes', $classes);

        return true;
    }

    /**
     * Check if this package can be used for a specific discipline.
     */
    public function canUseForDiscipline(int $disciplineId): bool
    {
        if (!$this->is_valid || !$this->has_classes) {
            return false;
        }

        // Cargar la relación del paquete si no está cargada
        if (!$this->relationLoaded('package')) {
            $this->load('package');
        }

        return $this->package && $this->package->discipline_id === $disciplineId;
    }

    /**
     * Check if the package has classes available.
     */
    public function getHasClassesAttribute(): bool
    {
        return $this->remaining_classes > 0;
    }

    /**
     * Get the discipline ID of this package.
     */
    public function getDisciplineIdAttribute(): ?int
    {
        if (!$this->relationLoaded('package')) {
            $this->load('package');
        }

        return $this->package?->discipline_id;
    }

    /**
     * Get the discipline name of this package.
     */
    public function getDisciplineNameAttribute(): ?string
    {
        if (!$this->relationLoaded('package.discipline')) {
            $this->load('package.discipline');
        }

        return $this->package?->discipline?->name;
    }

    /**
     * Activate the package.
     */
    public function activate(): void
    {
        $this->update([
            'status' => 'active',
            'activation_date' => $this->activation_date ?? now(),
        ]);
    }

    /**
     * Suspend the package.
     */
    public function suspend(): void
    {
        $this->update([
            'status' => 'suspended',
        ]);
    }

    /**
     * Expire the package.
     */
    public function expire(): void
    {
        $this->update([
            'status' => 'expired',
        ]);
    }
}
