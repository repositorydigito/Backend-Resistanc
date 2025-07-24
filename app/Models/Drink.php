<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Drink extends Model
{
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

        // ✅ Cambiar $membership por $drink
        static::creating(function ($drink) {
            if (empty($drink->slug)) {
                $drink->slug = Str::slug($drink->name);

                // Asegurar que el slug sea único
                $originalSlug = $drink->slug;
                $counter = 1;
                while (static::where('slug', $drink->slug)->exists()) {
                    $drink->slug = $originalSlug . '-' . $counter;
                    $counter++;
                }
            }
        });

        static::updating(function ($drink) {
            if ($drink->isDirty('name') && empty($drink->slug)) {
                $drink->slug = Str::slug($drink->name);

                // Asegurar que el slug sea único
                $originalSlug = $drink->slug;
                $counter = 1;
                while (static::where('slug', $drink->slug)->where('id', '!=', $drink->id)->exists()) {
                    $drink->slug = $originalSlug . '-' . $counter;
                    $counter++;
                }
            }
        });
    }

    protected $casts = [
        'price' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relaciones correctas
    public function basesdrinks()
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

    public function typesdrinks()
    {
        return $this->belongsToMany(Typedrink::class, 'typedrink_drink', 'drink_id', 'typedrink_id')
            ->withTimestamps()
            ->withPivot('id');
    }


    public function users()
    {
        return $this->belongsToMany(User::class, 'drink_user', 'drink_id', 'user_id')
            ->withTimestamps()
            ->withPivot('quantity', 'classschedule_id');
    }

      public function userFavorites(): BelongsToMany
    {
        return $this->morphToMany(UserFavorite::class, 'favoritable', 'user_favorites', 'favoritable_id', 'user_id')
            ->withPivot('notes', 'priority')
            ->withTimestamps();
    }

}
