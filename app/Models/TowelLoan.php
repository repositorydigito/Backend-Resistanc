<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TowelLoan extends Model
{
    protected $fillable = [
        'loan_date',
        'estimated_return_date',
        'return_date',
        'status',
        'notes',
        'towel_id',
        'user_client_id',
        'user_id',
    ];

    protected $casts = [
        'loan_date' => 'datetime',
        'estimated_return_date' => 'datetime',
        'return_date' => 'datetime',
    ];

    public function towel(): BelongsTo
    {
        return $this->belongsTo(Towel::class);
    }

    public function userClient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_client_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
