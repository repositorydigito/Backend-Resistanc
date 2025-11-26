<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $this->command->info('🌱 Seeders de la Compania');

        Company::create([
            'name' => 'RSISTANCE',
            'social_reason' => 'RSISTANCE S.A.C.',

            'address' => 'Av. Ejemplo 123, Lima, Perú',
            'phone' => '+51 987654321',
            'phone_whassap' => '+51 987654321',
            'phone_help' => '+51 987654321',
            'email' => 'info@rsistance.com',
            'logo_path' => null,
            'signature_image' => null,
            // 'social_networks' => [
            //     ["name" => "Facebook", "url" => "https://facebook.com/rsistance"],
            //     ["name" => "Instagram", "url" => "https://instagram.com/rsistance"],
            //     ["name" => "Twitter", "url" => "https://twitter.com/rsistance"]
            // ],

            // Nuevos campos de redes sociales
            'facebook_url' => 'https://facebook.com/rsistance',
            'instagram_url' => 'https://instagram.com/rsistance',
            'twitter_url' => 'https://twitter.com/rsistance',
            'linkedin_url' => 'https://linkedin.com/company/rsistance',
            'youtube_url' => 'https://youtube.com/c/rsistance',
            'tiktok_url' => 'https://tiktok.com/@rsistance',
            'whatsapp_url' => 'https://wa.me/51987654321',
            'website_url' => 'https://rsistance.com',


            'is_production' => false,

            'sol_user_production' => null,
            'sol_user_password_production' => null,
            'cert_path_production' => null,

            'client_id_production' => null,
            'client_secret_production' => null,

            'sol_user_evidence' => null,
            'sol_user_password_evidence' => null,
            'cert_path_evidence' => null,

            'client_id_evidence' => null,
            'client_secret_evidence' => null,
        ]);
    }
}
