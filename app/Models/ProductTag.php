<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

final class ProductTag extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',

    ];

    /**
     * Get the products that have this tag.
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_product_tag');
    }

    /**
     * Get the count of products with this tag.
     */
    public function getProductsCountAttribute(): int
    {
        return $this->products()->where('status', 'active')->count();
    }
}
