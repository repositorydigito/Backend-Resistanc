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
            // Promoción por Consumo - Siempre válida mientras esté activa
            [
                'name' => 'Descuento Verano 2025',
                'name_supplier' => 'Proveedor Ejemplo S.A.',
                'initial' => 'VER',
                'code' => 'VER2025',
                'type' => 'consumption',
                'start_date' => null,
                'end_date' => null,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Promoción por Temporada - Válida solo en el rango de fechas
            [
                'name' => 'Black Friday 2025',
                'name_supplier' => 'Distribuidora ABC',
                'initial' => 'BF',
                'code' => 'BF2025',
                'type' => 'season',
                'start_date' => now()->setDate(2025, 11, 24)->setTime(0, 0, 0), // 24 de noviembre 2025
                'end_date' => now()->setDate(2025, 11, 30)->setTime(23, 59, 59), // 30 de noviembre 2025
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Promoción por Temporada - Navidad 2025
            [
                'name' => 'Especial Navidad 2025',
                'name_supplier' => 'Tienda XYZ',
                'initial' => 'NAV',
                'code' => 'NAV2025',
                'type' => 'season',
                'start_date' => now()->setDate(2025, 12, 15)->setTime(0, 0, 0), // 15 de diciembre 2025
                'end_date' => now()->setDate(2025, 12, 31)->setTime(23, 59, 59), // 31 de diciembre 2025
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Promoción por Consumo - Inactiva
            [
                'name' => 'Promoción Antigua',
                'name_supplier' => 'Proveedor ABC',
                'initial' => 'OLD',
                'code' => 'OLD2024',
                'type' => 'consumption',
                'start_date' => null,
                'end_date' => null,
                'status' => 'inactive',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Promoción por Temporada - Año Nuevo 2026
            [
                'name' => 'Año Nuevo 2026',
                'name_supplier' => 'Proveedor Premium',
                'initial' => 'ANY',
                'code' => 'ANY2026',
                'type' => 'season',
                'start_date' => now()->setDate(2025, 12, 26)->setTime(0, 0, 0), // 26 de diciembre 2025
                'end_date' => now()->setDate(2026, 1, 5)->setTime(23, 59, 59), // 5 de enero 2026
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->command->info('✅ Se crearon ' . PromoCodes::count() . ' códigos promocionales');
    }
}
