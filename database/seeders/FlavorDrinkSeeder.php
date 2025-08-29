<?php

namespace Database\Seeders;

use App\Models\Flavordrink;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FlavorDrinkSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $this->command->info('🌱 Seeders de sabores');
        // Sabor de bebida
        Flavordrink::insert([
            [
                'name' => 'Berry Recovery',
                // 'image_url' => 'https://i.imgur.com/1234567.png',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Strawberry Matcha',
                // 'image_url' => 'https://i.imgur.com/1234567.png',
                'created_at' => now(),
                'updated_at' => now(),
            ],

        ]);
        // Fin sabor de bebida
    }
}
