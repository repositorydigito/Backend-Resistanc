<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JuiceOrder extends Model
{
    protected $fillable = [
        'order_number',
        'user_id',
        'user_name',
        'user_email',
        'subtotal_soles',
        'tax_amount_soles',
        'discount_amount_soles',
        'total_amount_soles',
        'currency',
        'status',
        'payment_status',
        'is_membership_redeem',
        'user_membership_id',
        'redeemed_shakes_quantity',
        'delivery_method',
        'estimated_ready_at',
        'ready_at',
        'delivered_at',
        'special_instructions',
        'notes',
        'payment_method_name',
        'confirmed_at',
        'preparing_at'
    ];

    protected $casts = [
        'subtotal_soles' => 'decimal:2',
        'tax_amount_soles' => 'decimal:2',
        'discount_amount_soles' => 'decimal:2',
        'total_amount_soles' => 'decimal:2',
        'is_membership_redeem' => 'boolean',
        'redeemed_shakes_quantity' => 'integer',
        'estimated_ready_at' => 'datetime',
        'ready_at' => 'datetime',
        'delivered_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'preparing_at' => 'datetime'
    ];

    /**
     * Get the user that owns the juice order.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

   /**
    * Get the details for the juice order.
    */
   public function details()
   {
       return $this->hasMany(JuiceOrderDetail::class);
   }

    /**
     * Membership used for this order (if applicable).
     */
    public function userMembership()
    {
        return $this->belongsTo(UserMembership::class);
    }

    /**
     * Generate unique order number
     */
    public static function generateOrderNumber(): string
    {
        do {
            $number = 'JS-' . date('Ymd') . '-' . strtoupper(\Illuminate\Support\Str::random(6));
        } while (static::where('order_number', $number)->exists());

        return $number;
    }

    /**
     * Update order status with timestamps
     */
    public function updateStatus(string $status): bool
    {
        $updates = ['status' => $status];

        switch ($status) {
            case 'confirmed':
                $updates['confirmed_at'] = now();
                break;
            case 'preparing':
                $updates['preparing_at'] = now();
                break;
            case 'ready':
                $updates['ready_at'] = now();
                break;
            case 'delivered':
                $updates['delivered_at'] = now();
                break;
        }

        return $this->update($updates);
    }

    /**
     * Calculate estimated ready time
     */
    public function calculateEstimatedReadyTime(): \Carbon\Carbon
    {
        // Tiempo base: 5 minutos por bebida
        $totalDrinks = $this->details()->sum('quantity');
        $estimatedMinutes = $totalDrinks * 5;

        return now()->addMinutes($estimatedMinutes);
    }

    /**
     * Boot method to generate order number
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            if (empty($order->order_number)) {
                $order->order_number = static::generateOrderNumber();
            }

            // Set estimated ready time
            if (empty($order->estimated_ready_at)) {
                $order->estimated_ready_at = $order->calculateEstimatedReadyTime();
            }
        });
    }
}
