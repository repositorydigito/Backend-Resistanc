<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Footwear extends Model
{

    protected $fillable = [
        // 'code',
        'model',
        'brand',
        'size',
        'color',
        'type',
        'gender',
        'description',
        'status',
        'image',
        'images_gallery',
    ];

    protected $casts = [
        'size' => 'integer',
        'images_gallery' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->generateUniqueCode();
        });
    }

    public function generateUniqueCode()
    {
        do {
            // Generar código de 4 caracteres alfanuméricos (mayúsculas y números)
            $code = Str::upper(Str::random(2)) . rand(10, 99);
        } while (self::where('code', $code)->exists());

        $this->attributes['code'] = $code;
    }

    /**
     * Local Scope para buscar por código
     */
    public function scopeByCode($query, $code)
    {
        return $query->where('code', $code);
    }

    public function loans()
    {
        return $this->hasMany(FootwearLoan::class);
    }

    public function activeLoan()
    {
        return $this->hasOne(FootwearLoan::class)->where('status', 'in_use');
    }
}
