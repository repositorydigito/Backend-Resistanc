<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'order_type',
        'subtotal_soles',
        'tax_amount_soles',
        'shipping_amount_soles',
        'discount_amount_soles',
        'total_amount_soles',
        'currency',
        'status',
        'payment_status',
        'delivery_method',
        'delivery_date',
        'delivery_time_slot',
        'delivery_address',
        'special_instructions',
        'promocode_used',
        'notes',
        'items',
        'payment_method_name',
        'user_id',
    ];

    protected $casts = [
        'subtotal_soles' => 'decimal:2',
        'tax_amount_soles' => 'decimal:2',
        'shipping_amount_soles' => 'decimal:2',
        'discount_amount_soles' => 'decimal:2',
        'total_amount_soles' => 'decimal:2',
        'delivery_address' => 'array',
        'items' => 'array', // Campo JSON para historial de items
        'delivery_date' => 'date',
    ];

    /**
     * Get the user who placed this order.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the order items for this order.
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get the transactions for this order.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Scope to get pending orders.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to get confirmed orders.
     */
    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    /**
     * Scope to get delivered orders.
     */
    public function scopeDelivered($query)
    {
        return $query->where('status', 'delivered');
    }

    /**
     * Scope to get cancelled orders.
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    /**
     * Get the status display name.
     */
    public function getStatusDisplayNameAttribute(): string
    {
        $statuses = [
            'pending' => 'Pendiente',
            'confirmed' => 'Confirmado',
            'processing' => 'Procesando',
            'preparing' => 'Preparando',
            'ready' => 'Listo',
            'delivered' => 'Entregado',
            'cancelled' => 'Cancelado',
            'refunded' => 'Reembolsado',
        ];

        return $statuses[$this->status] ?? ucfirst($this->status);
    }

    /**
     * Get the payment status display name.
     */
    public function getPaymentStatusDisplayNameAttribute(): string
    {
        $statuses = [
            'pending' => 'Pendiente',
            'authorized' => 'Autorizado',
            'paid' => 'Pagado',
            'partially_paid' => 'Parcialmente Pagado',
            'failed' => 'Fallido',
            'refunded' => 'Reembolsado',
        ];

        return $statuses[$this->payment_status] ?? ucfirst($this->payment_status);
    }

    /**
     * Get the payment method display name.
     */
    public function getPaymentMethodDisplayNameAttribute(): string
    {
        $methods = [
            'card' => 'Tarjeta',
            'cash' => 'Efectivo',
            'transfer' => 'Transferencia',
            'yape' => 'Yape',
            'plin' => 'Plin',
        ];

        return $methods[$this->payment_method] ?? ucfirst($this->payment_method);
    }

    /**
     * Get the shipping method display name.
     */
    public function getShippingMethodDisplayNameAttribute(): string
    {
        $methods = [
            'pickup' => 'Recojo en Tienda',
            'delivery' => 'Delivery',
            'express' => 'Express',
        ];

        return $methods[$this->shipping_method] ?? ucfirst($this->shipping_method);
    }

    /**
     * Check if the order can be cancelled.
     */
    public function getCanCancelAttribute(): bool
    {
        return in_array($this->status, ['pending', 'confirmed']);
    }

    /**
     * Check if the order is paid.
     */
    public function getIsPaidAttribute(): bool
    {
        return $this->payment_status === 'paid';
    }

    /**
     * Get the total items count.
     */
    public function getTotalItemsAttribute(): int
    {
        return $this->orderItems->sum('quantity');
    }

    /**
     * Get the delivery address formatted.
     */
    public function getDeliveryAddressFormattedAttribute(): string
    {
        if (!$this->delivery_address) {
            return '';
        }

        $address = $this->delivery_address;
        $parts = array_filter([
            $address['address_line'] ?? '',
            $address['district'] ?? '',
            $address['city'] ?? '',
        ]);

        return implode(', ', $parts);
    }

    /**
     * Calculate the order total.
     */
    public function calculateTotal(): float
    {
        return $this->subtotal_soles - $this->discount_amount_soles + $this->tax_amount_soles + $this->shipping_amount_soles;
    }

    /**
     * Confirm the order.
     */
    public function confirm(): void
    {
        $this->update([
            'status' => 'confirmed',
            'payment_status' => 'paid',
        ]);
    }

    /**
     * Mark as ready for pickup/delivery.
     */
    public function markReady(): void
    {
        $this->update([
            'status' => 'ready',
        ]);
    }

    /**
     * Mark as delivered.
     */
    public function deliver(): void
    {
        $this->update([
            'status' => 'delivered',
        ]);
    }

    /**
     * Cancel the order.
     */
    public function cancel(string $reason): void
    {
        $this->update([
            'status' => 'cancelled',
            'notes' => $reason,
        ]);
    }

    /**
     * Add an item to the order.
     */
    public function addItem(Product $product, int $quantity, ?ProductVariant $variant = null): OrderItem
    {
        $price = $variant ? $variant->final_price : $product->final_price;

        return $this->orderItems()->create([
            'product_id' => $product->id,
            'product_variant_id' => $variant?->id,
            'quantity' => $quantity,
            'unit_price_soles' => $price,
            'total_price_soles' => $price * $quantity,
            'product_name' => $product->name,
            'product_sku' => $variant ? $variant->full_sku : $product->sku,
        ]);
    }

    /**
     * Recalculate order totals.
     */
    public function recalculateTotals(): void
    {
        $subtotal = $this->orderItems->sum('total_price_soles');
        $taxAmount = ($subtotal - $this->discount_amount_soles) * 0.18; // IGV 18%
        $totalAmount = $subtotal - $this->discount_amount_soles + $taxAmount + $this->shipping_amount_soles;

        $this->update([
            'subtotal_soles' => $subtotal,
            'tax_amount_soles' => $taxAmount,
            'total_amount_soles' => $totalAmount,
        ]);
    }

    public function shoppingCart()
    {
        return $this->belongsTo(ShoppingCart::class);
    }
}
