<?php

namespace Database\Seeders;

use App\Models\Typedrink;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DrinkPricingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Solo agregar precios a los tipos (typedrinks)
        // De ahí se jala la información de precio para las bebidas
        $typeDrinks = [
            ['name' => 'Proteico', 'price' => 10.00],
            ['name' => 'Detox', 'price' => 8.00],
        ];

        foreach ($typeDrinks as $typeDrink) {
            Typedrink::updateOrCreate(
                ['name' => $typeDrink['name']],
                ['price' => $typeDrink['price'], 'is_active' => true]
            );
        }

        $this->command->info('✅ Precios de typedrinks configurados exitosamente');
    }
}
