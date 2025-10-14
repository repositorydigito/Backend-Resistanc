<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ShoppingCart extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',

        'total_amount',
        'item_count',
        'status',

        // Relaciones
        'user_id',
        'order_id',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
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
        $totalAmount = $this->items->sum('total_price');
        $itemCount = $this->items->sum('quantity');

        $this->update([
            'total_amount' => $totalAmount,
            'item_count' => $itemCount,
        ]);
    }

    /**
     * Convert cart to order.
     */
    public function convertToOrder(): Order
    {
        $subtotal = $this->items->sum('total_price');
        $totalAmount = $subtotal; // Simplificado sin impuestos

        $order = Order::create([
            'user_id' => $this->user_id,
            'order_number' => 'ORD-' . strtoupper(\Illuminate\Support\Str::random(10)),
            'status' => 'pending',
            'subtotal_soles' => $subtotal,
            'tax_amount_soles' => 0,
            'shipping_amount_soles' => 0,
            'discount_amount_soles' => 0,
            'total_amount_soles' => $totalAmount,
            'currency' => 'PEN',
            'payment_status' => 'paid', // Ya viene pagado desde la app
            'delivery_method' => 'pickup',
            'order_type' => 'purchase',
        ]);

        // Convert cart items to order items
        foreach ($this->items as $cartItem) {
            $order->orderItems()->create([
                'product_id' => $cartItem->product_id,
                'product_variant_id' => $cartItem->product_variant_id,
                'quantity' => $cartItem->quantity,
                'unit_price' => $cartItem->unit_price,
                'total_price' => $cartItem->total_price,
                'unit_price_soles' => $cartItem->unit_price,
                'total_price_soles' => $cartItem->total_price,
                'product_name' => $cartItem->product->name,
            ]);
        }

        // Clear the cart
        $this->clear();
        $this->update(['status' => 'converted']);

        return $order;
    }


    public function order()
    {
        return $this->hasOne(Order::class);
    }

}
