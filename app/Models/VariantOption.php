<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VariantOption extends Model
{
    use HasFactory;
    protected $table = 'variant_option';
    protected $fillable = [
        'name',
        'product_option_type_id',
        'value',
    ];

    public function productOptionType()
    {
        return $this->belongsTo(ProductOptionType::class);
    }

    public function productVariants()
    {
        return $this->belongsToMany(\App\Models\ProductVariant::class, 'product_variant_option_value');
    }

}
