<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductvariantOption extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug'
    ];

    public function options(): HasMany
    {
        return $this->hasMany(ProductVariantOption::class, 'product_option_type_id');
    }
}
