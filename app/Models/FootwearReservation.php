<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FootwearReservation extends Model
{
    protected $fillable = [
        'reservation_date',
        'scheduled_date',
        'expiration_date',
        'status',
        'class_schedules_id',
        'footwear_id',
        'user_client_id',
        'user_id',
        'loan_id',
    ];

    public function classSchedule()
    {
        return $this->belongsTo(ClassSchedule::class, 'class_schedules_id');
    }

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

    public function loan()
    {
        return $this->belongsTo(FootwearLoan::class, 'loan_id');
    }
} 