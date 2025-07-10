<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
     protected $fillable = [
        'name',
        'legal_name',
        'tax_id',
        'address',
        'phone',
        'email',
        'logo_path',
        'website',
        'settings',
        'timezone',
        'currency',
        'locale',

        // Facturacion con nube Fac
        'url_facturacion',
        'token_facturacion'
    ];

    protected $casts = [
        'settings' => 'array',
        'established_at' => 'datetime'
    ];

    protected $appends = ['logo_url'];

    public function getLogoUrlAttribute()
    {
        if (!$this->logo_path) {
            return asset('images/default-company-logo.png');
        }

        return str_starts_with($this->logo_path, 'http')
            ? $this->logo_path
            : asset('storage/'.$this->logo_path);
    }

    public static function current()
    {
        return cache()->rememberForever('current_company', function() {
            return self::firstOrCreate([], [
                'name' => config('app.name'),
                'tax_id' => '000000000'
            ]);
        });
    }
}
