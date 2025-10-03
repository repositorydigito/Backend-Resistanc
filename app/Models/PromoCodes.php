<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromoCodes extends Model
{

    protected $fillable = [
        'name',
        'name_supplier',
        'initial',
        'code',
        'status',
    ];

    protected static function boot()
    {
        parent::boot();

        // Generar código automáticamente al crear
        static::creating(function ($promoCode) {
            if (empty($promoCode->code) && !empty($promoCode->initial)) {
                $promoCode->code = $promoCode->generateUniqueCode();
            }
        });

        // Eliminar relaciones antes de eliminar el código promocional
        static::deleting(function ($promoCode) {
            $promoCode->packages()->detach();
            $promoCode->users()->detach();
        });
    }

    /**
     * Genera un código único basado en la inicial
     */
    public function generateUniqueCode()
    {
        $initial = strtoupper($this->initial);
        $maxAttempts = 10;
        $attempt = 0;

        do {
            $attempt++;
            
            // Generar parte aleatoria: 4 caracteres alfanuméricos
            $randomPart = strtoupper(substr(str_shuffle('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 4));
            
            // Formato: INICIAL + AÑO + RANDOM (ej: VER2025A3B7)
            $code = $initial . date('Y') . $randomPart;
            
            // Verificar si el código ya existe
            $exists = static::where('code', $code)->exists();
            
        } while ($exists && $attempt < $maxAttempts);

        // Si después de 10 intentos no se encuentra uno único, usar timestamp
        if ($attempt >= $maxAttempts) {
            $timestamp = substr(str_replace('.', '', microtime(true)), -6);
            $code = $initial . date('Y') . $timestamp;
        }

        return $code;
    }

    public function packages()
    {
        return $this->belongsToMany(Package::class, 'promocodes_package', 'promo_codes_id', 'package_id')
            ->withPivot(['quantity', 'discount', 'created_at', 'updated_at'])
            ->withTimestamps();
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'promocodes_user', 'promo_codes_id', 'user_id')
            ->withPivot(['monto', 'created_at', 'updated_at'])
            ->withTimestamps();
    }
}
