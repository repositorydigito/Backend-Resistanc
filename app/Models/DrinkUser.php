<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DrinkUser extends Model
{
   protected $table = 'drink_user';

   protected $fillable = [
       'user_id',
       'drink_id',
       'classschedule_id',
       'quantity',
   ];
    public $timestamps = false;
    protected $primaryKey = 'id';
    protected $casts = [
        'user_id' => 'integer',
        'drink_id' => 'integer',
        'classschedule_id' => 'integer',
        'quantity' => 'integer',
    ];
    /**
     * Get the user that owns the drink.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    /**
     * Get the drink associated with the user.
     */
    public function drink()
    {
        return $this->belongsTo(Drink::class, 'drink_id');
    }
    /**
     * Get the class schedule associated with the user.
     */
    public function classSchedule()
    {
        return $this->belongsTo(ClassSchedule::class, 'classschedule_id');
    }
    /**
     * Get the class schedule associated with the user.
     */

}
