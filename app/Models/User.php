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
use Spatie\Permission\Traits\HasRoles;

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
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\UserPackage> $userPackages Paquetes del usuario
 * @property-read int|null $contacts_count Número de contactos
 * @property-read int|null $social_accounts_count Número de cuentas sociales
 * @property-read int|null $login_audits_count Número de auditorías de login
 * @property-read int|null $user_packages_count Número de paquetes del usuario
 * @property-read string $full_name Nombre completo calculado
 * @property-read bool $has_complete_profile Si tiene perfil completo
 */
final class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

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
     * Get the user's packages.
     */
    public function userPackages(): HasMany
    {
        return $this->hasMany(UserPackage::class);
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

    public function package(){
        return $this->belongsToMany(Package::class, 'user_packages');
    }

    /**
     * Get the user's avatar URL for Filament.
     */
    public function getFilamentAvatarUrl(): ?string
    {
        return $this->profile?->avatar_url ?? null;
    }

    /**
     * Get the user's name for Filament.
     */
    public function getFilamentName(): string
    {
        return $this->name ?? $this->email;
    }

    public function paymentMethods(): HasMany
    {
        return $this->hasMany(UserPaymentMethod::class);
    }

    // 🎫 Relaciones con reservas de asientos

    /**
     * Get all seat reservations for this user.
     */
    public function seatReservations(): HasMany
    {
        return $this->hasMany(ClassScheduleSeat::class);
    }

    /**
     * Get active seat reservations (reserved or occupied).
     */
    public function activeSeatReservations(): HasMany
    {
        return $this->hasMany(ClassScheduleSeat::class)
            ->whereIn('status', ['reserved', 'occupied'])
            ->with(['classSchedule.class', 'classSchedule.studio', 'seat']);
    }

    /**
     * Get upcoming seat reservations.
     */
    public function upcomingSeatReservations(): HasMany
    {
        return $this->hasMany(ClassScheduleSeat::class)
            ->whereIn('status', ['reserved', 'occupied'])
            ->whereHas('classSchedule', function ($query) {
                $query->where('scheduled_date', '>=', now()->toDateString());
            })
            ->with(['classSchedule.class', 'classSchedule.studio', 'seat'])
            ->orderBy('reserved_at');
    }

    /**
     * Get past seat reservations.
     */
    public function pastSeatReservations(): HasMany
    {
        return $this->hasMany(ClassScheduleSeat::class)
            ->whereIn('status', ['occupied', 'Completed'])
            ->whereHas('classSchedule', function ($query) {
                $query->where('scheduled_date', '<', now()->toDateString());
            })
            ->with(['classSchedule.class', 'classSchedule.studio', 'seat'])
            ->orderBy('reserved_at', 'desc');
    }

    /**
     * Get expired reservations that need to be released.
     */
    public function expiredReservations(): HasMany
    {
        return $this->hasMany(ClassScheduleSeat::class)
            ->where('status', 'reserved')
            ->where('expires_at', '<', now());
    }

    /**
     * Check if user has a reservation for a specific class schedule.
     */
    public function hasReservationForSchedule(int $classScheduleId): bool
    {
        return $this->seatReservations()
            ->where('class_schedules_id', $classScheduleId)
            ->whereIn('status', ['reserved', 'occupied'])
            ->exists();
    }

    /**
     * Get user's reservation for a specific class schedule.
     */
    public function getReservationForSchedule(int $classScheduleId): ?ClassScheduleSeat
    {
        return $this->seatReservations()
            ->where('class_schedules_id', $classScheduleId)
            ->whereIn('status', ['reserved', 'occupied'])
            ->with(['seat', 'classSchedule'])
            ->first();
    }

    /**
     * Release all expired reservations for this user.
     */
    public function releaseExpiredReservations(): int
    {
        $expired = $this->expiredReservations()->get();

        foreach ($expired as $reservation) {
            $reservation->release();
        }

        return $expired->count();
    }
}
