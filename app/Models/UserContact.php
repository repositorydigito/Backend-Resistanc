<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Contacto de usuario (teléfono y dirección)
 *
 * @property int $id ID único del contacto
 * @property int $user_id ID del usuario propietario
 * @property string $phone Número de teléfono
 * @property string|null $address_line Dirección completa
 * @property string|null $city Ciudad
 * @property string $country Código de país (2 caracteres)
 * @property bool $is_primary Si es el contacto principal
 * @property \Illuminate\Support\Carbon|null $created_at Fecha de creación
 * @property \Illuminate\Support\Carbon|null $updated_at Fecha de actualización
 *
 * @property-read \App\Models\User $user Usuario propietario
 * @property-read string $full_address Dirección completa calculada
 */
final class UserContact extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'phone',
        'address_line',
        'city',
        'country',
        'is_primary',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
        ];
    }

    /**
     * Get the user that owns the contact.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the full address.
     */
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address_line,
            $this->city,
            $this->country,
        ]);

        return implode(', ', $parts);
    }

    /**
     * Scope to get primary contacts.
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    /**
     * Scope to get contacts by country.
     */
    public function scopeByCountry($query, string $country)
    {
        return $query->where('country', $country);
    }
}
