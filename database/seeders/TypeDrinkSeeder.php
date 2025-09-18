<?php

namespace Database\Seeders;

use App\Models\Typedrink;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TypeDrinkSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $this->command->info('🌱 Seeders de tipo de bebidas');
        // tipo de bebida

        Typedrink::insert([
            [
                'name' => 'Proteico',
                // 'image_url' => 'https://i.imgur.com/1234567.png',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Detox',
                // 'image_url' => 'https://i.imgur.com/1234567.png',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
        // Fin tipo de bebida
    }
}
