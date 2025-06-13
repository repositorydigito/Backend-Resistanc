<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Str;



class Drink extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'image_url',
        'price',
    ];

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

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function basedrinks()
    {
        return $this->belongsToMany(Basedrink::class, 'basedrink_drink', 'drink_id', 'basedrink_id')
            ->withTimestamps()
            ->withPivot('id');
    }
    public function flavordrinks()
    {
        return $this->belongsToMany(Flavordrink::class, 'flavordrink_drink', 'drink_id', 'flavordrink_id')
            ->withTimestamps()
            ->withPivot('id');
    }
    public function typedrinks()
    {
        return $this->belongsToMany(Typedrink::class, 'typedrink_drink', 'drink_id', 'typedrink_id')
            ->withTimestamps()
            ->withPivot('id');
    }

}
