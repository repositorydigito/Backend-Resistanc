<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class BookingExtraOption extends Model
{

    // No utilizado
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'additional_service_id',
        'option_name',
        'option_value',
        'price',
        'quantity',
        'total_price',
        'notes',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'quantity' => 'integer',
        'total_price' => 'decimal:2',
    ];

    /**
     * Get the booking for this extra option.
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Get the additional service for this option.
     */
    public function additionalService(): BelongsTo
    {
        return $this->belongsTo(AdditionalService::class);
    }

    /**
     * Calculate the total price.
     */
    public function calculateTotal(): float
    {
        return $this->price * $this->quantity;
    }

    /**
     * Update the total price.
     */
    public function updateTotal(): void
    {
        $this->update(['total_price' => $this->calculateTotal()]);
    }
}
