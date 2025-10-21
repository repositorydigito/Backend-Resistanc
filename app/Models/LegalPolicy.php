<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LegalPolicy extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'title',
        'content',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];



}
