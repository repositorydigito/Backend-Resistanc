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
            'logo_path' => '',
            'signature_image' => '',
            'social_networks' => json_encode([
                ["name" => "Facebook", "url" => "https://facebook.com/rsistance"],
                ["name" => "Instagram", "url" => "https://instagram.com/rsistance"],
                ["name" => "Twitter", "url" => "https://twitter.com/rsistance"]
            ]),

            'is_production' => false,

            'sol_user_production' => '',
            'sol_user_password_production' => '',
            'cert_path_production' => '',

            'client_id_production' => '',
            'client_secret_production' => '',

            'sol_user_evidence' => '',
            'sol_user_password_evidence' => '',
            'cert_path_evidence' => '',

            'client_id_evidence' => '',
            'client_secret_evidence' => '',


            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
