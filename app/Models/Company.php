<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
     protected $fillable = [
        'name',
        'social_reason',
        'ruc',
        'commercial_name',

        'address',
        'ubigeo',
        'department',
        'province',
        'district',
        'urbanization',
        'establishment_code',
        'phone',
        'phone_whassap',
        'phone_help',
        'email',
        'logo_path',


        'signature_image', // Ruta de la imagen de la firma
        'social_networks', // Redes sociales

        // Redes sociales
        'facebook_url',
        'instagram_url',
        'twitter_url',
        'linkedin_url',
        'youtube_url',
        'tiktok_url',
        'whatsapp_url',
        'website_url',



        // Facturacion greenter
        'is_production',
        'invoice_series',
        'invoice_initial_correlative',
        'boleta_series',
        'boleta_initial_correlative',

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

        'stripe_commission_percentage', // Porcentaje de comisión de Stripe
        'months_points', // Cantidad de meses que duran los puntos

    ];

    protected $casts = [
        'social_networks' => 'array', // Esto convierte automáticamente el JSON a array
        'is_production' => 'boolean',
        'invoice_initial_correlative' => 'integer',
        'boleta_initial_correlative' => 'integer',
        'stripe_commission_percentage' => 'decimal:2',
        'months_points' => 'integer',
    ];

}
