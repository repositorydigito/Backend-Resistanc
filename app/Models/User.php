<?php

declare(strict_types=1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Usuario principal del sistema RSISTANC
 *
 * @property int $id ID único del usuario
 * @property string $name Nombre completo del usuario
 * @property string $email Correo electrónico único
 * @property \Illuminate\Support\Carbon|null $email_verified_at Fecha de verificación del email
 * @property string $password Contraseña hasheada
 * @property string|null $remember_token Token de recordar sesión
 * @property \Illuminate\Support\Carbon|null $created_at Fecha de creación
 * @property \Illuminate\Support\Carbon|null $updated_at Fecha de actualización
 *
 * @property-read \App\Models\UserProfile|null $profile Perfil del usuario
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\UserContact> $contacts Contactos del usuario
 * @property-read \App\Models\UserContact|null $primaryContact Contacto principal
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SocialAccount> $socialAccounts Cuentas sociales
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\LoginAudit> $loginAudits Auditoría de logins
 * @property-read int|null $contacts_count Número de contactos
 * @property-read int|null $social_accounts_count Número de cuentas sociales
 * @property-read int|null $login_audits_count Número de auditorías de login
 * @property-read string $full_name Nombre completo calculado
 * @property-read bool $has_complete_profile Si tiene perfil completo
 */
final class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];



    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the user's profile.
     */
    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
    }

    /**
     * Get the user's contacts.
     */
    public function contacts(): HasMany
    {
        return $this->hasMany(UserContact::class);
    }

    /**
     * Get the user's primary contact.
     */
    public function primaryContact(): HasOne
    {
        return $this->hasOne(UserContact::class)->where('is_primary', true);
    }

    /**
     * Get the user's social accounts.
     */
    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class);
    }

    /**
     * Get the user's login audits.
     */
    public function loginAudits(): HasMany
    {
        return $this->hasMany(LoginAudit::class);
    }

    /**
     * Get the user's full name from profile.
     */
    public function getFullNameAttribute(): ?string
    {
        return $this->profile?->full_name;
    }

    /**
     * Check if user has a complete profile.
     */
    public function hasCompleteProfile(): bool
    {
        return $this->profile !== null &&
               $this->profile->first_name !== null &&
               $this->profile->last_name !== null;
    }

    /**
     * Check if user is admin.
     * TODO: Implement proper role system
     */
    public function isAdmin(): bool
    {
        // For now, return false. Implement role system later
        return false;
    }
}
