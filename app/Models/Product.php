<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

use Illuminate\Support\Str;

final class Product extends Model
{
    use HasFactory;

    // protected $fillable = [
    //     'category_id',
    //     'name',
    //     'slug',
    //     'description',
    //     'short_description',
    //     'sku',
    //     'price_soles',
    //     'cost_price_soles',
    //     'compare_price_soles',
    //     'stock_quantity',
    //     'min_stock_alert',
    //     'weight_grams',
    //     'dimensions',
    //     'images',
    //     'nutritional_info',
    //     'ingredients',
    //     'allergens',
    //     'product_type',
    //     'requires_variants',
    //     'is_virtual',
    //     'is_featured',
    //     'is_available_for_booking',
    //     'status',
    //     'meta_title',
    //     'meta_description',
    // ];

    // protected $casts = [
    //     'price_soles' => 'decimal:2',
    //     'cost_price_soles' => 'decimal:2',
    //     'compare_price_soles' => 'decimal:2',
    //     'stock_quantity' => 'integer',
    //     'min_stock_alert' => 'integer',
    //     'weight_grams' => 'integer',
    //     'dimensions' => 'array',
    //     'images' => 'array',
    //     'nutritional_info' => 'array',
    //     'ingredients' => 'array',
    //     'allergens' => 'array',
    //     'requires_variants' => 'boolean',
    //     'is_virtual' => 'boolean',
    //     'is_featured' => 'boolean',
    //     'is_available_for_booking' => 'boolean',
    // ];


    protected $fillable = [
        'name',
        'slug',
        'category_id',
        'sku',
        'stock_quantity',
        'description',
        'short_description',
        'price_soles',
        'cost_price_soles',
        'compare_price_soles',
        'min_stock_alert',
        'weight_grams',
        'dimensions',
        'images',
        'img_url',
        'nutritional_info',
        'ingredients',
        'allergens',
        // 'product_type',
        'requires_variants',
        'is_virtual',
        'is_featured',
        'is_available_for_booking',
        'status',
        'meta_title',
        'meta_description',
        'is_cupon',
        'url_cupon_code',
    ];

    protected $casts = [
        'price_soles' => 'float',
        'cost_price_soles' => 'float',
        'compare_price_soles' => 'float',
        'stock_quantity' => 'integer',
        'min_stock_alert' => 'integer',
        'weight_grams' => 'integer',
        'dimensions' => 'array',
        'images' => 'array',
        'nutritional_info' => 'array',
        'ingredients' => 'array',
        'allergens' => 'array',
        'requires_variants' => 'boolean',
        'is_virtual' => 'boolean',
        'is_featured' => 'boolean',
        'is_available_for_booking' => 'boolean',
    ];


    /**
     * Get the category that owns the product.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class);
    }

    /**
     * Get the variants for this product.
     */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function optionValues(): HasMany
    {
        return $this->hasMany(ProductVariantOption::class);
    }


    /**
     * Get the tags for this product.
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(ProductTag::class, 'product_product_tag');
    }

    /**
     * Get the order items for this product.
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get the cart items for this product.
     */
    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    /**
     * Scope to get only active products.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to get only featured products.
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope to get products with discounts.
     */
    public function scopeOnSale($query)
    {
        return $query->whereNotNull('discount_price');
    }

    /**
     * Scope to filter by category.
     */
    public function scopeInCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Get the final price (with discount if applicable).
     */
    public function getFinalPriceAttribute(): float
    {
        return (float) ($this->compare_price_soles ?? $this->price_soles ?? 0);
    }

    /**
     * Check if the product is on sale.
     */
    public function getIsOnSaleAttribute(): bool
    {
        return $this->compare_price_soles !== null && $this->price_soles < $this->compare_price_soles;
    }

    /**
     * Get the discount percentage.
     */
    public function getDiscountPercentageAttribute(): int
    {
        if (!$this->is_on_sale) {
            return 0;
        }

        return (int) round((($this->compare_price_soles - $this->price_soles) / $this->compare_price_soles) * 100);
    }

    /**
     * Get the main image URL.
     */
    public function getMainImageAttribute(): ?string
    {
        return $this->images[0] ?? null;
    }

    /**
     * Check if the product is in stock.
     */
    public function getIsInStockAttribute(): bool
    {
        return $this->stock_quantity > 0;
    }

    /**
     * Check if the product is low in stock.
     */
    public function getIsLowStockAttribute(): bool
    {
        return $this->stock_quantity <= $this->min_stock_alert;
    }

    /**
     * Get the features as a formatted string.
     */
    public function getFeaturesStringAttribute(): string
    {
        if (!$this->features) {
            return '';
        }

        return implode(', ', $this->features);
    }

    /**
     * Get the URL for this product.
     */
    public function getUrlAttribute(): string
    {
        return route('products.show', $this->slug);
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

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($membership) {
            if (empty($membership->slug)) {
                $membership->slug = Str::slug($membership->name);

                // Asegurar que el slug sea único
                $originalSlug = $membership->slug;
                $counter = 1;
                while (static::where('slug', $membership->slug)->exists()) {
                    $membership->slug = $originalSlug . '-' . $counter;
                    $counter++;
                }
            }
        });

        static::updating(function ($membership) {
            if ($membership->isDirty('name') && empty($membership->slug)) {
                $membership->slug = Str::slug($membership->name);

                // Asegurar que el slug sea único
                $originalSlug = $membership->slug;
                $counter = 1;
                while (static::where('slug', $membership->slug)->where('id', '!=', $membership->id)->exists()) {
                    $membership->slug = $originalSlug . '-' . $counter;
                    $counter++;
                }
            }
        });
    }

    // relacion muchos a muchos polimorfica

    public function userFavorites(): BelongsToMany
    {
        return $this->morphToMany(UserFavorite::class, 'favoritable', 'user_favorites', 'favoritable_id', 'user_id')
            ->withPivot('notes', 'priority')
            ->withTimestamps();
    }

    public function productBrand(): BelongsTo
    {
        return $this->belongsTo(ProductBrand::class, 'product_brand_id');
    }
}
