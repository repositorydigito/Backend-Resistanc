<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FootwearLoan extends Model
{
    protected $fillable = [
        'loan_date',
        'estimated_return_date',
        'return_date',
        'status',
        'notes',
        'footwear_id',
        'reservation_id',
        'user_client_id',
        'user_id',
    ];

    public function footwear()
    {
        return $this->belongsTo(Footwear::class);
    }

    public function reservation()
    {
        return $this->belongsTo(FootwearReservation::class, 'reservation_id');
    }

    public function userClient()
    {
        return $this->belongsTo(User::class, 'user_client_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
