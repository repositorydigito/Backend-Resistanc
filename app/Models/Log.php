<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
    protected $table = 'logs';

    protected $fillable = [
        'user_id',
        'action',
        'description',
        'data',

    ];
    protected $casts = [
        'data' => 'array', // Esto es clave para guardar y leer arrays como JSON
    ];

    // Relación con usuario (si aplica)
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
