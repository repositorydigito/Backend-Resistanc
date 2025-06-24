<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ProductVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'sku',
        'price_soles',
        'cost_price_soles',
        'compare_price_soles',
        'stock_quantity',
        'min_stock_alert',
        'images',
        'main_image',
        'is_active'

    ];

    protected $casts = [
        'images' => 'array',
    ];


    /**
     * Get the product that owns this variant.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Scope to get only active variants.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get only default variants.
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope to order by sort order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    /**
     * Get the final price including adjustment.
     */
    public function getFinalPriceAttribute(): float
    {
        return $this->product->price_soles + $this->price_modifier;
    }

    /**
     * Get the full SKU including suffix.
     */
    public function getFullSkuAttribute(): string
    {
        return $this->sku;
    }

    /**
     * Get the main image URL for this variant.
     */
    public function getMainImageAttribute(): ?string
    {
        return $this->images[0] ?? $this->product->main_image;
    }

    /**
     * Check if the variant is in stock.
     */
    public function getIsInStockAttribute(): bool
    {
        return $this->stock_quantity > 0;
    }

    /**
     * Get the variant display name with type.
     */
    public function getFullDisplayNameAttribute(): string
    {
        return ucfirst($this->variant_type) . ': ' . $this->variant_display_name;
    }

    /**
     * Decrease stock quantity.
     */
    public function decreaseStock(int $quantity): bool
    {
        if ($this->stock_quantity < $quantity) {
            return false;
        }

        $this->decrement('stock_quantity', $quantity);
        return true;
    }

    /**
     * Increase stock quantity.
     */
    public function increaseStock(int $quantity): void
    {
        $this->increment('stock_quantity', $quantity);
    }

    public function variantOptions()
    {
        return $this->belongsToMany(\App\Models\VariantOption::class, 'product_variant_option_value');
    }
}
