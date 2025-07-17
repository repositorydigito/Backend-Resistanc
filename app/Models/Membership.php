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
        'classes_before',
        'duration',
        'colors',
        'icon',
        'is_benefit_shake',
        'typeDrink_id',
        'shake_quantity',
        'is_benefit_discipline',
        'discipline_id',
        'discipline_quantity',
        'is_active',
    ];

    protected $casts = [
        'colors' => 'array',
        'is_active' => 'boolean',
        'is_benefit_shake' => 'boolean',
        'is_benefit_discipline' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
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

    // Relacion uno a muchos
    public function packages()
    {
        return $this->hasMany(Package::class);
    }

    // Relación con TypeDrink
    // public function typeDrink()
    // {
    //     return $this->belongsTo(TypeDrink::class, 'typeDrink_id');
    // }

    // Relación con Discipline
    public function discipline()
    {
        return $this->belongsTo(Discipline::class, 'discipline_id');
    }
}
