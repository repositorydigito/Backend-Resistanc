<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WaitingClass extends Model
{

    protected $table = 'waiting_classes';

    protected $fillable = [
        'class_schedules_id',
        'user_id',
        'user_package_id',
        'status',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    // 🔗 Relaciones
    public function classSchedule()
    {
        return $this->belongsTo(ClassSchedule::class, 'class_schedules_id');
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function userPackage()
    {
        return $this->belongsTo(UserPackage::class);
    }
}
