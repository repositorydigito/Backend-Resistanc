<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserFavorite extends Model
{

    use HasFactory;
    protected $fillable = [
        'user_id',
        'favoritable_type',
        'favoritable_id',
        'notes',
        'priority',
    ];

    /**
     * Get the user that owns the favorite.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the favoritable model.
     */
    public function favoritable()
    {
        return $this->morphTo();
    }
    /**
     * Scope a query to only include favorites of a specific type.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Builder
     */

}
