<?php

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
 * @property string $code Código único del usuario (formato tipo tarjeta de crédito)
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
        'code',
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
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            if (empty($user->code)) {
                $user->code = static::generateUniqueCode();
            }
        });
    }

    /**
     * Generate a unique code similar to a credit card number.
     * Format: XXXX-XXXX-XXXX-XXXX (16 digits with hyphens)
     */
    public static function generateUniqueCode(): string
    {
        $maxAttempts = 100; // Prevent infinite loops
        $attempts = 0;

        do {
            // Generate 16 random digits
            $digits = '';
            for ($i = 0; $i < 16; $i++) {
                $digits .= mt_rand(0, 9);
            }

            // Format as XXXX-XXXX-XXXX-XXXX
            $code = substr($digits, 0, 4) . '-' .
                   substr($digits, 4, 4) . '-' .
                   substr($digits, 8, 4) . '-' .
                   substr($digits, 12, 4);

            $attempts++;

                        // If we've tried too many times, add a timestamp to ensure uniqueness
            if ($attempts >= $maxAttempts) {
                $timestamp = (string) time();
                $code = substr($digits, 0, 4) . '-' .
                       substr($digits, 4, 4) . '-' .
                       substr($digits, 8, 4) . '-' .
                       substr($timestamp, -4);
                break;
            }

        } while (static::where('code', $code)->exists());

        return $code;
    }

    /**
     * Validate if a code has the correct format.
     * Format: XXXX-XXXX-XXXX-XXXX (16 digits with hyphens)
     */
    public static function isValidCodeFormat(string $code): bool
    {
        return preg_match('/^\d{4}-\d{4}-\d{4}-\d{4}$/', $code) === 1;
    }

    /**
     * Get the code without hyphens (raw digits only).
     */
    public function getCodeDigitsAttribute(): string
    {
        return str_replace('-', '', $this->code);
    }

    /**
     * Get the masked code for display (shows only first and last 4 digits).
     * Format: XXXX-****-****-XXXX
     */
    public function getMaskedCodeAttribute(): string
    {
        if (!$this->code) {
            return '';
        }

        $parts = explode('-', $this->code);
        if (count($parts) !== 4) {
            return $this->code;
        }

        return $parts[0] . '-****-****-' . $parts[3];
    }

    /**
     * Regenerate the user's code.
     * Useful when a code needs to be changed.
     */
    public function regenerateCode(): bool
    {
        $this->code = static::generateUniqueCode();
        return $this->save();
    }

    /**
     * Scope to find users by code (with or without hyphens).
     */
    public function scopeByCode($query, string $code)
    {
        // Remove hyphens for comparison
        $cleanCode = str_replace('-', '', $code);

        return $query->whereRaw('REPLACE(code, "-", "") = ?', [$cleanCode]);
    }

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

    public function package()
    {
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
    public function classScheduleSeats(): HasMany
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

    public function drinks()
    {
        return $this->belongsToMany(Drink::class, 'drink_user', 'user_id', 'drink_id')
            ->withPivot('quantity', 'classschedule_id')
            ->withTimestamps();
    }

    public function userFavorites()
    {
        return $this->hasMany(UserFavorite::class, 'user_id');
    }

    public function favoriteDrinks()
    {
        return $this->morphedByMany(Drink::class, 'favoritable', 'user_favorites', 'user_id', 'favoritable_id')
            ->withPivot('notes', 'priority')
            ->withTimestamps();
    }

    public function favoriteProducts()
    {
        return $this->morphedByMany(Product::class, 'favoritable', 'user_favorites', 'user_id', 'favoritable_id')
            ->withPivot('notes', 'priority')
            ->withTimestamps();
    }

    public function favoriteClasses()
    {
        return $this->morphedByMany(ClassModel::class, 'favoritable', 'user_favorites', 'user_id', 'favoritable_id')
            ->withPivot('notes', 'priority')
            ->withTimestamps();
    }

    public function favoriteInstructors()
    {
        return $this->morphedByMany(Instructor::class, 'favoritable', 'user_favorites', 'user_id', 'favoritable_id')
            ->withPivot('notes', 'priority')
            ->withTimestamps();
    }

    public function waitingClasses()
    {
        return $this->hasMany(WaitingClass::class); // O el modelo correspondiente
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function completedSeatReservations(): HasMany
    {
        return $this->hasMany(ClassScheduleSeat::class)
            ->where('status', 'Completed');
    }
    public function pendingSeatReservations(): HasMany
    {
        return $this->hasMany(ClassScheduleSeat::class)
            ->where('status', 'reserved');
    }

    /**
     * Get the total number of available classes from user's active packages.
     */
    public function getAvailableClassesCount(): int
    {
        $count = $this->userPackages()
            ->where('status', 'active')
            ->where('expiry_date', '>', now())
            ->where('remaining_classes', '>', 0)
            ->sum('remaining_classes');

        return (int) $count;
    }

    /**
     * Get the total number of active packages the user has.
     */
    public function getActivePackagesCount(): int
    {
        $count = $this->userPackages()
            ->where('status', 'active')
            ->where('expiry_date', '>', now())
            ->count();

        return (int) $count;
    }

    /**
     * Get the total number of available classes by discipline.
     */
    public function getAvailableClassesByDiscipline(): array
    {
        return $this->userPackages()
            ->with('package.discipline')
            ->where('status', 'active')
            ->where('expiry_date', '>', now())
            ->where('remaining_classes', '>', 0)
            ->get()
            ->groupBy('package.discipline_id')
            ->map(function ($packages, $disciplineId) {
                $discipline = $packages->first()->package->discipline;
                return [
                    'discipline_id' => $disciplineId,
                    'discipline_name' => $discipline->name,
                    'total_available_classes' => $packages->sum('remaining_classes'),
                    'packages_count' => $packages->count(),
                ];
            })
            ->values()
            ->toArray();
    }

    public function footwearLoansAsClient(): HasMany
    {
        return $this->hasMany(FootwearLoan::class, 'user_client_id');
    }

    public function footwearLoansAsManager(): HasMany
    {
        return $this->hasMany(FootwearLoan::class, 'user_id');
    }

    public function footwearReservations()
    {
        // Reservas hechas por el cliente
        return $this->hasMany(FootwearReservation::class, 'user_client_id');
    }

    public function footwearLoans()
    {
        // Préstamos recibidos por el cliente
        return $this->hasMany(FootwearLoan::class, 'user_client_id');
    }

    public function footwearReservationsCreated()
    {
        // Reservas registradas por el usuario (admin/empleado)
        return $this->hasMany(FootwearReservation::class, 'user_id');
    }

    public function footwearLoansManaged()
    {
        // Préstamos gestionados por el usuario (admin/empleado)
        return $this->hasMany(FootwearLoan::class, 'user_id');
    }

    /**
     * Get all towels created by this user.
     */

    public function towels(): HasMany
    {
        return $this->hasMany(Towel::class, 'user_id');
    }

    public function towelsUpdated(): HasMany
    {
        return $this->hasMany(Towel::class, 'user_updated_id');
    }

    // Relaciones para préstamos de toallas
    public function towelLoans(): HasMany
    {
        return $this->hasMany(TowelLoan::class, 'user_client_id');
    }

    public function towelLoansManaged(): HasMany
    {
        return $this->hasMany(TowelLoan::class, 'user_id');
    }

    public function juiceCartCodes(): HasMany
    {
        return $this->hasMany(JuiceCartCodes::class);
    }

}
