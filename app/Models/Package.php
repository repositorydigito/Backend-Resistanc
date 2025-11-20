<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Stripe\StripeClient;
use Stripe\Exception\ApiErrorException;
use Illuminate\Support\Facades\Log;


final class Package extends Model
{
    use HasFactory;


    protected $fillable = [
        'name',
        'slug',
        'description',
        'short_description',
        'classes_quantity',
        'price_soles',
        'original_price_soles',

        'billing_type',
        'is_virtual_access',
        'priority_booking_days',
        'auto_renewal',
        'is_featured',
        'is_popular',
        'status',
        'display_order',
        'features',
        'restrictions',
        'target_audience',

        // nuevo
        'icon_url',
        'color_hex',
        'type',
        'mode_type',
        'commercial_type',
        'buy_type',
        'start_date',
        'end_date',
        'duration_in_months',
        'is_membresia',
        'recurrence_months', // Meses de recurrencia si es membresía
        'igv', // IGV en porcentaje
        'stripe_product_id', // ID del producto en Stripe
        'stripe_price_id', // ID del precio en Stripe


        // Relaciones
        'membership_id',

    ];

    protected $casts = [
        'price_soles' => 'decimal:2',
        'original_price_soles' => 'decimal:2',
        'igv' => 'decimal:2',
        'classes_quantity' => 'integer',
        'recurrence_months' => 'integer',

        'priority_booking_days' => 'integer',
        'features' => 'array',
        'restrictions' => 'array',
        'is_virtual_access' => 'boolean',
        'auto_renewal' => 'boolean',
        'is_featured' => 'boolean',
        'is_popular' => 'boolean',
        'is_membresia' => 'boolean',
        'display_order' => 'integer',
    ];

    /**
     * Get the user packages for this package.
     */
    public function userPackages(): HasMany
    {
        return $this->hasMany(UserPackage::class);
    }

    /**
     * Scope to get only active packages.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to order by display order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order');
    }

    /**
     * Scope to filter by package type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('package_type', $type);
    }

    /**
     * Check if the package is unlimited.
     */
    public function getIsUnlimitedAttribute(): bool
    {
        return $this->billing_type === 'monthly' || $this->classes_quantity >= 999;
    }

    /**
     * Check if the package is on sale.
     */
    public function getIsOnSaleAttribute(): bool
    {
        return $this->original_price_soles && $this->price_soles < $this->original_price_soles;
    }

    /**
     * Get the discount percentage.
     */
    public function getDiscountPercentageAttribute(): int
    {
        if (!$this->is_on_sale) {
            return 0;
        }

        return (int) round((($this->original_price_soles - $this->price_soles) / $this->original_price_soles) * 100);
    }

    /**
     * Get the features as a formatted string.
     */
    public function getFeaturesStringAttribute(): string
    {
        if (!$this->features || !is_array($this->features)) {
            return '';
        }

        return implode(', ', $this->features);
    }

    /**
     * Get the restrictions as a formatted string.
     */
    public function getRestrictionsStringAttribute(): string
    {
        if (!$this->restrictions || !is_array($this->restrictions)) {
            return '';
        }

        return implode(', ', $this->restrictions);
    }

    /**
     * Get the price per credit (for credit-based packages).
     */
    public function getPricePerCreditAttribute(): ?float
    {
        if (!$this->classes_quantity || $this->classes_quantity <= 0 || $this->classes_quantity >= 999) {
            return null;
        }

        return $this->price_soles / $this->classes_quantity;
    }

    /**
     * Get the package type display name.
     */
    public function getTypeDisplayNameAttribute(): string
    {
        // Validar que el valor no sea null antes de usar ucfirst
        return $this->type ? ucfirst($this->type) : '';

        // O si quieres un valor por defecto más específico:
        // return $this->type ? ucfirst($this->type) : 'Basic';
    }

    /**
     * Get the billing type display name.
     */
    public function getBillingTypeDisplayNameAttribute(): string
    {
        return match ($this->billing_type) {
            'one_time' => 'Pago Único',
            'monthly' => 'Mensual',
            'quarterly' => 'Trimestral',
            'yearly' => 'Anual',
            default => ucfirst($this->billing_type),
        };
    }



    protected static function boot()
    {
        parent::boot();

        static::creating(function ($membership) {
            if (empty($membership->slug)) {
                $membership->slug = Str::slug($membership->name);

                // Asegurar que el slug sea único
                $originalSlug = $membership->slug;
                $counter = 1;
                while (static::where('slug', $membership->slug)->exists()) {
                    $membership->slug = $originalSlug . '-' . $counter;
                    $counter++;
                }
            }
        });

        static::updating(function ($membership) {
            if ($membership->isDirty('name') && empty($membership->slug)) {
                $membership->slug = Str::slug($membership->name);

                // Asegurar que el slug sea único
                $originalSlug = $membership->slug;
                $counter = 1;
                while (static::where('slug', $membership->slug)->where('id', '!=', $membership->id)->exists()) {
                    $membership->slug = $originalSlug . '-' . $counter;
                    $counter++;
                }
            }
        });

        // Eliminar relaciones antes de eliminar el paquete
        static::deleting(function ($package) {
            $package->promocodes()->detach();
            $package->disciplines()->detach();
            // Desactivar producto en Stripe antes de eliminar el paquete
            $package->deactivateStripeProduct();
        });

        // Crear producto en Stripe al crear el paquete
        static::created(function ($package) {
            $package->createStripeProduct();
        });

        // Actualizar producto en Stripe al actualizar el paquete
        static::updated(function ($package) {
            // Solo sincronizar si cambian campos relevantes, no cuando solo se actualizan los IDs de Stripe
            $relevantFields = ['name', 'description', 'short_description', 'price_soles', 'igv', 'is_membresia', 'recurrence_months', 'status'];
            if ($package->wasChanged($relevantFields) && !$package->wasChanged(['stripe_product_id', 'stripe_price_id'])) {
                $package->syncStripeProduct();
            }
        });
    }

    /**
     * Crea o actualiza el producto y precio en Stripe
     */
    public function createStripeProduct(): void
    {
        try {
            $stripe = $this->makeStripeClient();
            
            // Calcular precio con IGV en centavos (Stripe usa centavos)
            $priceWithIgv = $this->price_soles * (1 + ($this->igv / 100));
            $amountInCents = (int) round($priceWithIgv * 100);

            // Crear o actualizar producto en Stripe
            if ($this->stripe_product_id) {
                // Actualizar producto existente
                $product = $stripe->products->update($this->stripe_product_id, [
                    'name' => $this->name,
                    'description' => $this->description ?? $this->short_description ?? '',
                    'active' => $this->status === 'active',
                ]);
            } else {
                // Crear nuevo producto
                $product = $stripe->products->create([
                    'name' => $this->name,
                    'description' => $this->description ?? $this->short_description ?? '',
                    'active' => $this->status === 'active',
                    'metadata' => [
                        'package_id' => $this->id,
                        'classes_quantity' => $this->classes_quantity,
                        'type' => $this->type ?? 'fixed',
                    ],
                ]);
                
                $this->stripe_product_id = $product->id;
            }

            // Si es membresía, crear precio recurrente
            if ($this->is_membresia) {
                $this->createOrUpdateRecurringPrice($stripe, $product->id, $amountInCents);
            } else {
                // Si no es membresía, crear precio único
                $this->createOrUpdateOneTimePrice($stripe, $product->id, $amountInCents);
            }

            // Guardar el stripe_product_id y stripe_price_id sin disparar eventos
            static::withoutEvents(function () {
                $this->update([
                    'stripe_product_id' => $this->stripe_product_id,
                    'stripe_price_id' => $this->stripe_price_id,
                ]);
            });

            Log::info('Producto de Stripe creado/actualizado exitosamente', [
                'package_id' => $this->id,
                'stripe_product_id' => $this->stripe_product_id,
                'stripe_price_id' => $this->stripe_price_id,
            ]);

        } catch (ApiErrorException $e) {
            Log::error('Error al crear/actualizar producto en Stripe', [
                'package_id' => $this->id,
                'error' => $e->getMessage(),
            ]);
            
            // No lanzamos la excepción para no interrumpir la creación del paquete
            // pero logueamos el error
        } catch (\Exception $e) {
            Log::error('Error inesperado al crear/actualizar producto en Stripe', [
                'package_id' => $this->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Sincroniza el producto con Stripe (usado en updates)
     */
    public function syncStripeProduct(): void
    {
        $this->createStripeProduct();
    }

    /**
     * Desactiva el producto en Stripe cuando se elimina el paquete
     */
    public function deactivateStripeProduct(): void
    {
        if (!$this->stripe_product_id) {
            return; // No hay producto en Stripe para desactivar
        }

        try {
            $stripe = $this->makeStripeClient();
            
            // Desactivar el producto en Stripe (no eliminarlo para mantener historial)
            $stripe->products->update($this->stripe_product_id, [
                'active' => false,
            ]);

            // También desactivar el precio si existe
            if ($this->stripe_price_id) {
                try {
                    $stripe->prices->update($this->stripe_price_id, [
                        'active' => false,
                    ]);
                } catch (ApiErrorException $e) {
                    // Si el precio ya no existe o no se puede actualizar, solo loguear
                    Log::warning('No se pudo desactivar el precio en Stripe', [
                        'package_id' => $this->id,
                        'stripe_price_id' => $this->stripe_price_id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            Log::info('Producto de Stripe desactivado exitosamente', [
                'package_id' => $this->id,
                'stripe_product_id' => $this->stripe_product_id,
                'stripe_price_id' => $this->stripe_price_id,
            ]);

        } catch (ApiErrorException $e) {
            Log::error('Error al desactivar producto en Stripe', [
                'package_id' => $this->id,
                'stripe_product_id' => $this->stripe_product_id,
                'error' => $e->getMessage(),
            ]);
            
            // No lanzamos la excepción para no interrumpir la eliminación del paquete
        } catch (\Exception $e) {
            Log::error('Error inesperado al desactivar producto en Stripe', [
                'package_id' => $this->id,
                'stripe_product_id' => $this->stripe_product_id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Crea o actualiza un precio recurrente para membresías
     */
    private function createOrUpdateRecurringPrice(StripeClient $stripe, string $productId, int $amountInCents): void
    {
        $recurrenceMonths = $this->recurrence_months ?? 1;
        
        // Determinar el intervalo de Stripe
        // Stripe soporta: day, week, month, year
        // Para meses, usamos 'month' con interval_count si es necesario
        $interval = 'month';
        $intervalCount = $recurrenceMonths;

        // Si son 12 meses, podemos usar 'year'
        if ($recurrenceMonths === 12) {
            $interval = 'year';
            $intervalCount = 1;
        }

        $recurringParams = [
            'interval' => $interval,
            'interval_count' => $intervalCount,
        ];

        // Si ya existe un precio, verificar si es recurrente
        if ($this->stripe_price_id) {
            try {
                // Verificar si el precio existe
                $existingPrice = $stripe->prices->retrieve($this->stripe_price_id);
                
                // Si el precio anterior NO era recurrente, desactivarlo y crear uno nuevo
                if (!isset($existingPrice->recurring)) {
                    // Desactivar precio único anterior
                    if ($existingPrice->active) {
                        $stripe->prices->update($this->stripe_price_id, ['active' => false]);
                    }
                    
                    // Crear nuevo precio recurrente
                    $price = $stripe->prices->create([
                        'product' => $productId,
                        'unit_amount' => $amountInCents,
                        'currency' => 'pen',
                        'recurring' => $recurringParams,
                    ]);
                    
                    $this->stripe_price_id = $price->id;
                } 
                // Si el precio cambió significativamente (monto o intervalo), crear uno nuevo
                elseif ($existingPrice->unit_amount != $amountInCents || 
                        $existingPrice->recurring->interval != $interval ||
                        $existingPrice->recurring->interval_count != $intervalCount) {
                    // Desactivar precio anterior si existe
                    if ($existingPrice->active) {
                        $stripe->prices->update($this->stripe_price_id, ['active' => false]);
                    }
                    
                    // Crear nuevo precio
                    $price = $stripe->prices->create([
                        'product' => $productId,
                        'unit_amount' => $amountInCents,
                        'currency' => 'pen',
                        'recurring' => $recurringParams,
                    ]);
                    
                    $this->stripe_price_id = $price->id;
                }
                // Si el precio no cambió, mantener el existente
            } catch (ApiErrorException $e) {
                // Si el precio no existe, crear uno nuevo
                $price = $stripe->prices->create([
                    'product' => $productId,
                    'unit_amount' => $amountInCents,
                    'currency' => 'pen',
                    'recurring' => $recurringParams,
                ]);
                
                $this->stripe_price_id = $price->id;
            }
        } else {
            // Crear nuevo precio recurrente
            $price = $stripe->prices->create([
                'product' => $productId,
                'unit_amount' => $amountInCents,
                'currency' => 'pen',
                'recurring' => $recurringParams,
            ]);
            
            $this->stripe_price_id = $price->id;
        }
    }

    /**
     * Crea o actualiza un precio único (no recurrente)
     */
    private function createOrUpdateOneTimePrice(StripeClient $stripe, string $productId, int $amountInCents): void
    {
        // Si ya existe un precio, verificar si es único o recurrente
        if ($this->stripe_price_id) {
            try {
                $existingPrice = $stripe->prices->retrieve($this->stripe_price_id);
                
                // Si el precio anterior ERA recurrente, desactivarlo y crear uno nuevo único
                if (isset($existingPrice->recurring)) {
                    // Desactivar precio recurrente anterior
                    if ($existingPrice->active) {
                        $stripe->prices->update($this->stripe_price_id, ['active' => false]);
                    }
                    
                    // Crear nuevo precio único
                    $price = $stripe->prices->create([
                        'product' => $productId,
                        'unit_amount' => $amountInCents,
                        'currency' => 'pen',
                    ]);
                    
                    $this->stripe_price_id = $price->id;
                }
                // Si el precio cambió (monto diferente), crear uno nuevo
                elseif ($existingPrice->unit_amount != $amountInCents) {
                    // Desactivar precio anterior
                    if ($existingPrice->active) {
                        $stripe->prices->update($this->stripe_price_id, ['active' => false]);
                    }
                    
                    // Crear nuevo precio único
                    $price = $stripe->prices->create([
                        'product' => $productId,
                        'unit_amount' => $amountInCents,
                        'currency' => 'pen',
                    ]);
                    
                    $this->stripe_price_id = $price->id;
                }
                // Si el precio no cambió, mantener el existente
            } catch (ApiErrorException $e) {
                // Si el precio no existe, crear uno nuevo
                $price = $stripe->prices->create([
                    'product' => $productId,
                    'unit_amount' => $amountInCents,
                    'currency' => 'pen',
                ]);
                
                $this->stripe_price_id = $price->id;
            }
        } else {
            // Crear nuevo precio único
            $price = $stripe->prices->create([
                'product' => $productId,
                'unit_amount' => $amountInCents,
                'currency' => 'pen',
            ]);
            
            $this->stripe_price_id = $price->id;
        }
    }

    /**
     * Crea un cliente de Stripe
     */
    private function makeStripeClient(): StripeClient
    {
        $secret = config('services.stripe.secret');

        if (!$secret) {
            throw new \RuntimeException('Stripe no está configurado correctamente. Falta services.stripe.secret.');
        }

        return new StripeClient($secret);
    }



    // Relacion uno a uno
    public function membership()
    {
        return $this->belongsTo(Membership::class);
    }

    public function userPackage()
    {
        return $this->hasMany(UserPackage::class);
    }
    public function users()
    {
        return $this->hasMany(User::class);
    }
    public function disciplines()
    {
        return $this->belongsToMany(Discipline::class);
    }
    public function classSchedules()
    {
        return $this->hasMany(ClassSchedule::class);
    }


    public function promocodes()
    {
        return $this->belongsToMany(PromoCodes::class, 'promocodes_package', 'package_id', 'promo_codes_id')
            ->withPivot(['quantity', 'discount', 'created_at', 'updated_at'])
            ->withTimestamps();
    }
}
