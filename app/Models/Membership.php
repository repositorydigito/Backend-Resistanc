<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Membership extends Model
{

    public $fillable = [
        'name',
        'slug',
        'level',
        'description',
        'benefits',
        'color_hex',
        'is_active',
        'display_order'
    ];


    protected $casts = [
        'benefits' => 'array',      // Para campo JSON - REQUERIDO
        'features' => 'array',      // Para campo JSON
        'restrictions' => 'array',  // Para campo JSON
        'is_active' => 'boolean',   // Para el campo is_active
        'is_virtual_access' => 'boolean',
        'auto_renewal' => 'boolean',
        'is_featured' => 'boolean',
        'is_popular' => 'boolean',
    ];

    /**
     * Boot the model.
     */
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

    // Relacion uno a uno

    public function packages()
    {
        return $this->hasMany(Package::class);
    }
}
