<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'quantity',
        'unit_price',
        'total_price',
        'order_id',
        'product_id',
        'product_variant_id',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    /**
     * Get the order that owns this item.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
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
     * Get the product name from the relationship.
     */
    public function getProductNameAttribute(): string
    {
        return $this->product ? $this->product->name : 'Producto no encontrado';
    }
}
