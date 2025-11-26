<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Laravel\Cashier\Billable;
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
       use HasApiTokens, HasFactory, Notifiable, HasRoles, Billable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'name',
        'email',
        'password',
        'facebook_id',
        'google_id',
        'avatar',
        'document_type',
        'document_number',
        'business_name',
        'is_company',
        'effective_completed_classes',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }


    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            if (empty($user->code)) {
                $user->code = static::generateUniqueCode();
            }
        });
    }

    public static function generateUniqueCode(): string
    {
        $maxAttempts = 100; // Prevent infinite loops
        $attempts = 0;

        do {
            // Generate 16 random digits
            $digits = '';
            for ($i = 0; $i < 16; $i++) {
                $digits .= mt_rand(0, 9);
            }

            // Format as XXXX-XXXX-XXXX-XXXX
            $code = substr($digits, 0, 4) . '-' .
                substr($digits, 4, 4) . '-' .
                substr($digits, 8, 4) . '-' .
                substr($digits, 12, 4);

            $attempts++;

            // If we've tried too many times, add a timestamp to ensure uniqueness
            if ($attempts >= $maxAttempts) {
                $timestamp = (string) time();
                $code = substr($digits, 0, 4) . '-' .
                    substr($digits, 4, 4) . '-' .
                    substr($digits, 8, 4) . '-' .
                    substr($timestamp, -4);
                break;
            }
        } while (static::where('code', $code)->exists());

        return $code;
    }
}
