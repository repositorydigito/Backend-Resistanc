<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class CartItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'shopping_cart_id',
        'product_id',
        'product_variant_id',
        'quantity',
        'unit_price',
        'total_price',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    /**
     * Get the shopping cart that owns this item.
     */
    public function shoppingCart(): BelongsTo
    {
        return $this->belongsTo(ShoppingCart::class);
    }

    /**
     * Get the product for this item.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the product variant for this item.
     */
    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    /**
     * Calculate the total price based on quantity and unit price.
     */
    public function calculateTotal(): float
    {
        return $this->quantity * $this->unit_price;
    }

    /**
     * Update the total price.
     */
    public function updateTotal(): void
    {
        $this->update(['total_price' => $this->calculateTotal()]);
    }

    /**
     * Increase quantity.
     */
    public function increaseQuantity(int $amount = 1): void
    {
        $this->increment('quantity', $amount);
        $this->updateTotal();
    }

    /**
     * Decrease quantity.
     */
    public function decreaseQuantity(int $amount = 1): void
    {
        $newQuantity = max(0, $this->quantity - $amount);
        
        if ($newQuantity <= 0) {
            $this->delete();
        } else {
            $this->update(['quantity' => $newQuantity]);
            $this->updateTotal();
        }
    }
}
