<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;


use Spatie\Permission\Traits\HasRoles;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;


use Laravel\Cashier\Billable;



final class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
      use HasApiTokens, HasFactory, Notifiable, HasRoles, Billable;

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
        'facebook_id',
        'google_id',
        'avatar',
        'document_type',
        'document_number',
        'business_name',
        'is_company',
        'effective_completed_classes',
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
            'is_company' => 'boolean',
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
     * Get the user memberships for free classes.
     */
    public function userMemberships(): HasMany
    {
        return $this->hasMany(UserMembership::class);
    }

    /**
     * Get the promo codes used by the user.
     */
    public function promoCodes(): BelongsToMany
    {
        return $this->belongsToMany(PromoCodes::class, 'promocodes_user', 'user_id', 'promo_codes_id')
            ->withPivot(['package_id', 'monto', 'discount_applied', 'original_price', 'final_price', 'created_at', 'updated_at'])
            ->withTimestamps();
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

    /**
     * Relación con los métodos de pago almacenados en la base de datos
     * Nota: Este método se renombró a storedPaymentMethods() para evitar conflicto
     * con paymentMethods() del trait Billable de Cashier que obtiene métodos de Stripe
     */
    public function storedPaymentMethods(): HasMany
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
     * Calcular y actualizar las clases completadas efectivas del usuario.
     * Esto incluye:
     * 1. Clases físicamente completadas de paquetes VIGENTES (ClassScheduleSeat con status 'Completed')
     *    - Solo cuenta clases cuyo paquete asociado (user_package_id) está activo y no expirado
     * 2. Clases otorgadas por membresías VIGENTES adquiridas por compra de paquete
     *    - Solo cuenta si la membresía está activa y no expirada
     *
     * @return int Número de clases efectivas completadas
     */
    public function calculateAndUpdateEffectiveCompletedClasses(): int
    {
        // 1. Contar clases físicamente completadas SOLO de paquetes o membresías vigentes
        // Una clase completa es válida si:
        // - Está completada (status = 'Completed')
        // - Y el paquete asociado (user_package_id) está activo y vigente
        // - O la membresía asociada (user_membership_id) está activa y vigente
        // - O no tiene paquete ni membresía asociada (se cuenta igual)
        $physicalCompletedClasses = ClassScheduleSeat::where('user_id', $this->id)
            ->where('status', 'Completed')
            ->where(function ($query) {
                // Clases sin paquete ni membresía asociada (se cuentan siempre)
                $query->where(function ($q) {
                    $q->whereNull('user_package_id')
                        ->whereNull('user_membership_id');
                })
                    // O clases con paquete asociado que esté activo y vigente
                    ->orWhereHas('userPackage', function ($q) {
                        $q->where('status', 'active')
                            ->where(function ($subQuery) {
                                $subQuery->whereNull('expiry_date')
                                    ->orWhere('expiry_date', '>', now());
                            })
                            // Asegurar que el paquete ya fue activado
                            ->where(function ($subQuery) {
                                $subQuery->whereNull('activation_date')
                                    ->orWhere('activation_date', '<=', now());
                            });
                    })
                    // O clases con membresía asociada que esté activa y vigente
                    ->orWhereHas('userMembership', function ($q) {
                        $q->where('status', 'active')
                            ->where(function ($subQuery) {
                                $subQuery->whereNull('expiry_date')
                                    ->orWhere('expiry_date', '>', now());
                            })
                            // Asegurar que la membresía ya fue activada
                            ->where(function ($subQuery) {
                                $subQuery->whereNull('activation_date')
                                    ->orWhere('activation_date', '<=', now());
                            });
                    });
            })
            ->count();

        // 2. Sumar clases otorgadas por membresías VIGENTES adquiridas por compra de paquete
        // Solo contar la membresía más alta adquirida por paquete (para no duplicar)
        $membershipGrantedClasses = 0;

        // Obtener la membresía más alta del usuario que fue adquirida por compra de paquete
        // Primero buscar en UserMembership
        $highestPackageMembership = \App\Models\UserMembership::where('user_id', $this->id)
            ->whereNotNull('source_package_id')
            ->where('status', 'active')
            ->where(function ($query) {
                // Membresía no expirada
                $query->whereNull('expiry_date')
                    ->orWhere('expiry_date', '>', now());
            })
            ->where(function ($query) {
                // Membresía ya activada
                $query->whereNull('activation_date')
                    ->orWhere('activation_date', '<=', now());
            })
            ->with(['membership'])
            ->whereHas('membership', function ($query) {
                $query->where('is_active', true);
            })
            ->orderBy('created_at', 'desc')
            ->first();

        $membershipGrantedClasses = 0;

        if ($highestPackageMembership && $highestPackageMembership->membership) {
            // Si el usuario adquirió una membresía por paquete, se le otorgan las clases que requiere esa membresía
            // Por ejemplo, si compró Rsistanc (requiere 100 clases), se le otorgan 100 clases base
            $membershipGrantedClasses = $highestPackageMembership->membership->class_completed ?? 0;
        } else {
            // Si no se encontró UserMembership, buscar directamente en los paquetes activos del usuario
            // que tengan una membresía asociada (is_membresia = true)
            $activePackage = \App\Models\UserPackage::where('user_id', $this->id)
                ->where('status', 'active')
                ->where(function ($query) {
                    $query->whereNull('expiry_date')
                        ->orWhere('expiry_date', '>', now());
                })
                ->where(function ($query) {
                    $query->whereNull('activation_date')
                        ->orWhere('activation_date', '<=', now());
                })
                ->with(['package.membership'])
                ->whereHas('package', function ($query) {
                    $query->where('is_membresia', true)
                        ->whereNotNull('membership_id')
                        ->where('status', 'active');
                })
                ->whereHas('package.membership', function ($query) {
                    $query->where('is_active', true);
                })
                ->orderBy('created_at', 'desc')
                ->first();

            if ($activePackage && $activePackage->package && $activePackage->package->membership) {
                // Encontramos un paquete activo con membresía, usar sus clases base
                $membershipGrantedClasses = $activePackage->package->membership->class_completed ?? 0;

                \App\Models\Log::create([
                    'user_id' => $this->id,
                    'action' => 'Cálculo de clases efectivas - Membresía encontrada desde paquete',
                    'description' => "Usuario {$this->id}: Se encontró membresía desde paquete activo: {$activePackage->package->membership->name} (requiere {$membershipGrantedClasses} clases base)",
                    'data' => [
                        'user_id' => $this->id,
                        'package_id' => $activePackage->package_id,
                        'membership_name' => $activePackage->package->membership->name,
                        'membership_class_completed' => $membershipGrantedClasses,
                        'physical_completed_classes' => $physicalCompletedClasses,
                    ],
                ]);
            } else {
                // Log para debug: no se encontró membresía base
                \App\Models\Log::create([
                    'user_id' => $this->id,
                    'action' => 'Cálculo de clases efectivas - Sin membresía base',
                    'description' => "Usuario {$this->id}: No se encontró UserMembership ni paquete con membresía activa. Solo se contarán {$physicalCompletedClasses} clases físicas.",
                    'data' => [
                        'user_id' => $this->id,
                        'physical_completed_classes' => $physicalCompletedClasses,
                        'note' => 'El usuario necesita tener una UserMembership activa o un paquete con membresía para obtener clases base',
                    ],
                ]);
            }
        }

        // 3. Calcular total efectivo
        // LÓGICA CORREGIDA:
        // - Si tienes membresía otorgada por paquete: clases base + clases físicas adicionales
        //   Ejemplo: Rsistanc (100 base) + 7 físicas = 107 clases efectivas
        // - Si NO tienes membresía por paquete pero sí clases físicas: solo cuentan las físicas
        //   Ejemplo: 50 clases físicas = 50 clases efectivas

        // IMPORTANTE: Solo contar clases físicas que sean ADICIONALES a la base de la membresía
        // Si ya tienes 100 clases físicas y la membresía otorga 100 base, no sumar 100+100=200
        // En ese caso, las clases físicas ya incluyen la base

        if ($membershipGrantedClasses > 0) {
            // Si tiene membresía por paquete, las clases físicas se suman a la base
            // PERO solo si las clases físicas son menores que la base
            // Si las clases físicas ya superan la base, significa que las ganó por completar clases
            // y no necesita sumar la base (ya está incluida)

            if ($physicalCompletedClasses < $membershipGrantedClasses) {
                // Tiene membresía base pero pocas clases físicas
                // Sumar: base + físicas = total
                // Ejemplo: 100 (base) + 7 (físicas) = 107
                $effectiveClasses = $membershipGrantedClasses + $physicalCompletedClasses;
            } else {
                // Ya tiene más clases físicas que la base de la membresía
                // Esto significa que las clases físicas ya incluyen la base
                // Solo contar las clases físicas
                // Ejemplo: 150 (físicas) > 100 (base), entonces = 150
                $effectiveClasses = $physicalCompletedClasses;
            }
        } else {
            // No tiene membresía por paquete, solo cuentan las clases físicas
            $effectiveClasses = $physicalCompletedClasses;
        }

        // 4. Actualizar el campo en la base de datos
        $this->update(['effective_completed_classes' => $effectiveClasses]);

        return $effectiveClasses;
    }

    /**
     * Incrementar las clases completadas efectivas cuando se completa una clase física.
     * Solo incrementa si el nuevo total es mayor que el valor actual.
     */
    public function incrementEffectiveCompletedClasses(int $classes = 1): void
    {
        // Recalcular desde cero para asegurar precisión
        $newTotal = $this->calculateAndUpdateEffectiveCompletedClasses();

        \App\Models\Log::create([
            'user_id' => $this->id,
            'action' => 'Clases efectivas actualizadas',
            'description' => "Clases efectivas completadas actualizadas a: {$newTotal} (incremento de {$classes} clase(s))",
            'data' => [
                'effective_completed_classes' => $newTotal,
                'increment' => $classes,
            ],
        ]);
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
        $userPackages = $this->userPackages()
            ->with('package.disciplines')
            ->where('status', 'active')
            ->where('expiry_date', '>', now())
            ->where('remaining_classes', '>', 0)
            ->get();

        $disciplineData = [];

        foreach ($userPackages as $userPackage) {
            foreach ($userPackage->package->disciplines as $discipline) {
                $disciplineId = $discipline->id;

                if (!isset($disciplineData[$disciplineId])) {
                    $disciplineData[$disciplineId] = [
                        'discipline_id' => $disciplineId,
                        'discipline_name' => $discipline->name,
                        'discipline_display_name' => $discipline->display_name,
                        'total_available_classes' => 0,
                        'packages_count' => 0,
                    ];
                }

                $disciplineData[$disciplineId]['total_available_classes'] += $userPackage->remaining_classes;
                $disciplineData[$disciplineId]['packages_count']++;
            }
        }

        return array_values($disciplineData);
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

    /**
     * Get the instructor profile associated with this user.
     */
    public function instructor(): HasOne
    {
        return $this->hasOne(Instructor::class);
    }

    /**
     * Send the email verification notification.
     *
     * @return void
     */
    // public function sendEmailVerificationNotification()
    // {
    //     $this->notify(new \App\Notifications\VerifyEmailNotification);
    // }

    public function juiceOrders(): HasMany
    {
        return $this->hasMany(JuiceOrder::class);
    }

    public function logs()
    {
        return $this->hasMany(Log::class);
    }
}
