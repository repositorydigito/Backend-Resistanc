<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class JuiceCartCodes extends Model
{

    protected $table = 'juice_cart_codes';

    protected $fillable = [
        'code',
        'user_id',
        'is_used',
        'juice_order_id',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($juiceCartCode) {
            if (empty($juiceCartCode->code)) {
                $juiceCartCode->code = static::generateUniqueCode();
            }
        });
    }

    /**
     * Genera un código único para el carrito
     */
    protected static function generateUniqueCode(): string
    {
        do {
            // Genera un código de 8 caracteres alfanuméricos
            $code = strtoupper(Str::random(8));
        } while (static::where('code', $code)->exists());

        return $code;
    }

    /**
     * Genera un código personalizado con prefijo
     */
    public static function generateCustomCode(string $prefix = 'JC'): string
    {
        do {
            // Genera un código con prefijo + 6 dígitos aleatorios
            $code = $prefix . str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        } while (static::where('code', $code)->exists());

        return $code;
    }

    public function drinks()
    {
        return $this->belongsToMany(Drink::class, 'juice_cart_drink', 'juice_cart_code_id', 'drink_id')->withPivot('quantity')->withTimestamps();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function juiceOrder()
    {
        return $this->belongsTo(JuiceOrder::class, 'juice_order_id');
    }
}
