<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FootwearLoan extends Model
{
    protected $fillable = [
        'footwear_id',
        'user_client_id',
        'user_id',
        'loan_date',
        'estimated_return_date',
        'return_date',
        'status',
        'notes',
    ];

    public function footwear()
    {
        return $this->belongsTo(Footwear::class);
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
