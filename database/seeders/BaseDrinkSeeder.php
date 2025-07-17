<?php

namespace Database\Seeders;

use App\Models\Basedrink;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BaseDrinkSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $this->command->info('🌱 Seeders de Base de shake');
        // base drink
        Basedrink::insert([
            [
                'name' => 'Avena',
                'image_url' => 'https://i.imgur.com/1234567.png',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Almendra',
                'image_url' => 'https://i.imgur.com/1234567.png',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Agua',
                'image_url' => 'https://i.imgur.com/1234567.png',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
        // fin base drink
    }
}
