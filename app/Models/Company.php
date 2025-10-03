<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
     protected $fillable = [
        'name',
        'social_reason',

        'address',
        'phone',
        'phone_whassap',
        'phone_help',
        'email',
        'logo_path',


        'signature_image', // Ruta de la imagen de la firma
        'social_networks', // Redes sociales

        // Facturacion greenter
        'is_production',

        // Es produccion
        'sol_user_production',
        'sol_user_password_production',
        'cert_path_production',

        'client_id_production',
        'client_secret_production',

        // QA
        'sol_user_evidence',
        'sol_user_password_evidence',
        'cert_path_evidence',

        'client_id_evidence',
        'client_secret_evidence',


    ];

        protected $casts = [
        'social_networks' => 'array', // Esto convierte automáticamente el JSON a array
        'is_production' => 'boolean',
    ];

}
