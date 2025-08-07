<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Perfil detallado del usuario
 *
 * @property int $id ID único del perfil
 * @property int $user_id ID del usuario propietario
 * @property string|null $first_name Nombre(s) del usuario
 * @property string|null $last_name Apellido(s) del usuario
 * @property \Illuminate\Support\Carbon|null $birth_date Fecha de nacimiento
 * @property \App\Enums\Gender|null $gender Género del usuario
 * @property int|null $shoe_size_eu Talla de zapato europea (20-60)
 * @property \Illuminate\Support\Carbon|null $created_at Fecha de creación
 * @property \Illuminate\Support\Carbon|null $updated_at Fecha de actualización
 *
 * @property-read \App\Models\User $user Usuario propietario
 * @property-read string $full_name Nombre completo calculado
 * @property-read int $age Edad calculada en años
 */
final class UserProfile extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'birth_date',
        'gender',
        'shoe_size_eu',
        'profile_image',
        'bio',
        'emergency_contact_name',
        'emergency_contact_phone',
        'medical_conditions',
        'fitness_goals',

        'is_active',
        'observations',
        // Relaciones
        'user_id',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'shoe_size_eu' => 'integer',
        ];
    }

    /**
     * Get the user that owns the profile.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // En el modelo UserProfile
    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * Get the user's age in years.
     */
    public function getAgeAttribute(): ?int
    {
        if (!$this->birth_date) {
            return null;
        }

        return $this->birth_date->age;
    }
}
