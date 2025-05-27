<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ShoppingCart extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'session_id',
        'status',
        'subtotal',
        'tax_amount',
        'total_amount',
        'currency',
        'expires_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'expires_at' => 'datetime',
    ];

    /**
     * Get the user that owns this cart.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the items in this cart.
     */
    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    /**
     * Scope to get active carts.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to get expired carts.
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now());
    }

    /**
     * Get the total items count.
     */
    public function getTotalItemsAttribute(): int
    {
        return $this->items->sum('quantity');
    }

    /**
     * Check if the cart is empty.
     */
    public function getIsEmptyAttribute(): bool
    {
        return $this->items->count() === 0;
    }

    /**
     * Check if the cart is expired.
     */
    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Add an item to the cart.
     */
    public function addItem(Product $product, int $quantity = 1, ?ProductVariant $variant = null): CartItem
    {
        $price = $variant ? $variant->final_price : $product->final_price;

        // Check if item already exists
        $existingItem = $this->items()
            ->where('product_id', $product->id)
            ->where('product_variant_id', $variant?->id)
            ->first();

        if ($existingItem) {
            $existingItem->increaseQuantity($quantity);
            return $existingItem;
        }

        // Create new item
        $item = $this->items()->create([
            'product_id' => $product->id,
            'product_variant_id' => $variant?->id,
            'quantity' => $quantity,
            'unit_price' => $price,
            'total_price' => $price * $quantity,
        ]);

        $this->recalculateTotals();

        return $item;
    }

    /**
     * Remove an item from the cart.
     */
    public function removeItem(int $itemId): void
    {
        $this->items()->where('id', $itemId)->delete();
        $this->recalculateTotals();
    }

    /**
     * Clear all items from the cart.
     */
    public function clear(): void
    {
        $this->items()->delete();
        $this->recalculateTotals();
    }

    /**
     * Recalculate cart totals.
     */
    public function recalculateTotals(): void
    {
        $subtotal = $this->items->sum('total_price');
        $taxAmount = $subtotal * 0.18; // IGV 18%
        $totalAmount = $subtotal + $taxAmount;

        $this->update([
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total_amount' => $totalAmount,
        ]);
    }

    /**
     * Convert cart to order.
     */
    public function convertToOrder(): Order
    {
        $order = Order::create([
            'user_id' => $this->user_id,
            'order_number' => 'RST-' . date('Y') . '-' . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT),
            'status' => 'pending',
            'subtotal' => $this->subtotal,
            'tax_amount' => $this->tax_amount,
            'total_amount' => $this->total_amount,
            'currency' => $this->currency,
            'payment_status' => 'pending',
        ]);

        // Convert cart items to order items
        foreach ($this->items as $cartItem) {
            $order->orderItems()->create([
                'product_id' => $cartItem->product_id,
                'product_variant_id' => $cartItem->product_variant_id,
                'quantity' => $cartItem->quantity,
                'unit_price' => $cartItem->unit_price,
                'total_price' => $cartItem->total_price,
                'product_name' => $cartItem->product->name,
                'product_sku' => $cartItem->productVariant ? $cartItem->productVariant->full_sku : $cartItem->product->sku,
            ]);
        }

        // Clear the cart
        $this->clear();
        $this->update(['status' => 'converted']);

        return $order;
    }

    /**
     * Extend cart expiration.
     */
    public function extend(int $hours = 24): void
    {
        $this->update(['expires_at' => now()->addHours($hours)]);
    }
}
