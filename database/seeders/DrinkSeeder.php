<?php

namespace Database\Seeders;

use App\Models\Drink;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DrinkSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌱 Seeders de Bebidas');
        // Bebidas

        $drink_one = Drink::create([
            'name' => 'Batido de Vainilla',
            'slug' => 'batido-de-vainilla',
            'description' => 'Delicioso batido de vainilla',

            // 'image_url' => 'https://i.imgur.com/1234567.png',
            // // 'price' => 10.00,
            'created_at' => now(),
        ]);
        $drink_one->basesdrinks()->attach(1);
        $drink_one->typesdrinks()->attach(1);
        $drink_one->flavordrinks()->attach(1);
        $drink_one->save();

        $drink_two = Drink::create([
            'name' => 'Batido de Chocolate',
            'slug' => 'batido-de-chocolate',
            'description' => 'Delicioso batido de chocolate',

            // 'image_url' => 'https://i.imgur.com/1234567.png',
            // 'price' => 12.00,
            'created_at' => now(),
        ]);
        $drink_two->basesdrinks()->attach(2);
        $drink_two->typesdrinks()->attach(1);
        $drink_two->flavordrinks()->attach(2);
        $drink_two->save();
        // Fin bebidas
    }
}
