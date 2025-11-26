<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductOptionType extends Model
{

    protected $fillable = [
        'name',
        'slug',
        'is_color',
        'is_required',
        'is_active',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function options()
    {
        return $this->hasMany(ProductvariantOption::class);
    }
    public function variants()
    {
        return $this->hasMany(VariantOption::class, 'product_option_type_id');
    }


    public function variantOptions()
    {
        return $this->hasMany(\App\Models\VariantOption::class, 'product_option_type_id');
    }
}
