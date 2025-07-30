<?php

namespace Database\Seeders;

use App\Models\Towel;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TowelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $this->command->info('🌱 Seeders de Toallas');

        $towals = Towel::insert([
            [
                'code' => 'TWL-001',
                'size' => 70,
                'color' => 'blue',
            ],
            [
                'code' => 'TWL-002',
                'size' => 80,
                'color' => 'green',
            ],
            [
                'code' => 'TWL-003',
                'size' => 90,
                'color' => 'red',
            ],
            [
                'code' => 'TWL-004',
                'size' => 100,
                'color' => 'yellow',
            ],
            [
                'code' => 'TWL-005',
                'size' => 110,
                'color' => 'black',
            ],
            [
                'code' => 'TWL-006',
                'size' => 120,
                'color' => 'white',
            ],
        ]);
    }
}
