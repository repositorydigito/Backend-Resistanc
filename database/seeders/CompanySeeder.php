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
            'legal_name' => 'RSISTANCE S.A.C.',
            'tax_id' => '12345678901',
            'address' => 'Av. Ejemplo 123, Lima, Perú',
            'phone' => '+51 987654321',
            'email' => 'info@rsistance.com',
            'logo_path' => 'https://i.imgur.com/1234567.png',
            'website' => 'https://www.rsistance.com',
            'settings' => [
                'default_currency' => 'PEN',
                'default_locale' => 'es_PE',
                'default_timezone' => 'America/Lima',
            ],
            'timezone' => 'America/Lima',
            'currency' => 'PEN',
            'locale' => 'es_PE',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
