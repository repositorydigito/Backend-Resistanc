<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Package extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'short_description',
        'classes_quantity',
        'price_soles',
        'original_price_soles',
        'validity_days',
        'package_type',
        'billing_type',
        'is_virtual_access',
        'priority_booking_days',
        'auto_renewal',
        'is_featured',
        'is_popular',
        'status',
        'display_order',
        'features',
        'restrictions',
        'target_audience',
    ];

    protected $casts = [
        'price_soles' => 'decimal:2',
        'original_price_soles' => 'decimal:2',
        'classes_quantity' => 'integer',
        'validity_days' => 'integer',
        'priority_booking_days' => 'integer',
        'features' => 'array',
        'restrictions' => 'array',
        'is_virtual_access' => 'boolean',
        'auto_renewal' => 'boolean',
        'is_featured' => 'boolean',
        'is_popular' => 'boolean',
        'display_order' => 'integer',
    ];

    /**
     * Get the user packages for this package.
     */
    public function userPackages(): HasMany
    {
        return $this->hasMany(UserPackage::class);
    }

    /**
     * Scope to get only active packages.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to order by display order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order');
    }

    /**
     * Scope to filter by package type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('package_type', $type);
    }

    /**
     * Check if the package is unlimited.
     */
    public function getIsUnlimitedAttribute(): bool
    {
        return $this->billing_type === 'monthly' || $this->classes_quantity >= 999;
    }

    /**
     * Check if the package is on sale.
     */
    public function getIsOnSaleAttribute(): bool
    {
        return $this->original_price_soles && $this->price_soles < $this->original_price_soles;
    }

    /**
     * Get the discount percentage.
     */
    public function getDiscountPercentageAttribute(): int
    {
        if (!$this->is_on_sale) {
            return 0;
        }

        return (int) round((($this->original_price_soles - $this->price_soles) / $this->original_price_soles) * 100);
    }

    /**
     * Get the features as a formatted string.
     */
    public function getFeaturesStringAttribute(): string
    {
        if (!$this->features || !is_array($this->features)) {
            return '';
        }

        return implode(', ', $this->features);
    }

    /**
     * Get the restrictions as a formatted string.
     */
    public function getRestrictionsStringAttribute(): string
    {
        if (!$this->restrictions || !is_array($this->restrictions)) {
            return '';
        }

        return implode(', ', $this->restrictions);
    }

    /**
     * Get the price per credit (for credit-based packages).
     */
    public function getPricePerCreditAttribute(): ?float
    {
        if (!$this->classes_quantity || $this->classes_quantity <= 0 || $this->classes_quantity >= 999) {
            return null;
        }

        return $this->price_soles / $this->classes_quantity;
    }

    /**
     * Get the package type display name.
     */
    public function getTypeDisplayNameAttribute(): string
    {
        return match ($this->package_type) {
            'presencial' => 'Presencial',
            'virtual' => 'Virtual',
            'mixto' => 'Mixto',
            default => ucfirst($this->package_type),
        };
    }

    /**
     * Get the billing type display name.
     */
    public function getBillingTypeDisplayNameAttribute(): string
    {
        return match ($this->billing_type) {
            'one_time' => 'Pago Único',
            'monthly' => 'Mensual',
            'quarterly' => 'Trimestral',
            'yearly' => 'Anual',
            default => ucfirst($this->billing_type),
        };
    }

    /**
     * Get the validity period in a human-readable format.
     */
    public function getValidityPeriodAttribute(): string
    {
        if ($this->validity_days <= 7) {
            return $this->validity_days . ' días';
        } elseif ($this->validity_days <= 31) {
            $weeks = round($this->validity_days / 7);
            return $weeks . ' semanas';
        } elseif ($this->validity_days <= 365) {
            $months = round($this->validity_days / 30);
            return $months . ' meses';
        } else {
            $years = round($this->validity_days / 365);
            return $years . ' años';
        }
    }
}
