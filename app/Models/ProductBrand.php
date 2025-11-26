<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductBrand extends Model
{

    protected $fillable = ['name'];

    /**
     * Get the products associated with the brand.
     */
    public function products()
    {
        return $this->hasMany(Product::class);
    }

}
