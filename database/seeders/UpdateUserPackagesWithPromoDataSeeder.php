<?php

namespace Database\Seeders;

use App\Models\UserPackage;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UpdateUserPackagesWithPromoDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Actualizar algunos UserPackages existentes con datos de códigos promocionales de ejemplo
        $userPackages = UserPackage::whereNull('promo_code_used')
            ->where('status', 'active')
            ->take(5) // Solo actualizar 5 registros como ejemplo
            ->get();

        foreach ($userPackages as $userPackage) {
            // Simular que algunos paquetes fueron comprados con códigos promocionales
            if (rand(1, 3) === 1) { // 33% de probabilidad
                $originalPrice = $userPackage->amount_paid_soles;
                $discountPercentage = rand(15, 30); // Descuento entre 15% y 30%
                $discountAmount = ($originalPrice * $discountPercentage) / 100;
                $finalPrice = $originalPrice - $discountAmount;

                $userPackage->update([
                    'promo_code_used' => 'EJEMPLO' . rand(100, 999),
                    'discount_percentage' => $discountPercentage,
                    'original_package_price_soles' => $originalPrice,
                    'real_amount_paid_soles' => $finalPrice,
                ]);

                $this->command->info("Actualizado UserPackage ID {$userPackage->id} con código promocional");
            }
        }

        $this->command->info('Actualización de UserPackages con datos promocionales completada');
    }
}
