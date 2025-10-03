<?php

namespace Database\Seeders;

use App\Models\PromoCodes;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PromoCodesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {


        $this->command->info('🌱 Seeders de Codigo de promociones');

        PromoCodes::insert([
            [
                'name' => 'Descuento Verano 2025',
                'name_supplier' => 'Proveedor Ejemplo S.A.',
                'initial' => 'VER',
                'code' => 'VER2025',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Oferta Especial',
                'name_supplier' => 'Distribuidora ABC',
                'initial' => 'OFE',
                'code' => 'OFE2025',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Promoción Invierno',
                'name_supplier' => 'Tienda XYZ',
                'initial' => 'INV',
                'code' => 'INV2025',
                'status' => 'inactive',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
