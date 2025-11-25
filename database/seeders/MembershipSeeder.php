<?php

namespace Database\Seeders;

use App\Models\Membership;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MembershipSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $this->command->info('🌱 Seeders de Membresías');
        Membership::insert([
            [
                'id' => 1,
                'name' => 'Rsistanc',
                'slug' => 'rsistanc',
                'level' => 1,
                'description' => 'Membresía básica de Rsistanc',
                'classes_before' => 7,
                'duration' => 8,
                'colors' => json_encode([['color' => '#9D5AA9'], ['color' => '#A36BB6']]),
                'icon' => 'fa-solid fa-dumbbell',
                'is_benefit_shake' => false,
                'shake_quantity' => 0,
                'is_benefit_discipline' => false,
                'discipline_id' => null,
                'class_completed' => 0,
                'discipline_quantity' => 0,
            ],
            [
                'id' => 2,
                'name' => 'Rsistanc Gold',
                'slug' => 'rsistanc-gold',
                'level' => 2,
                'description' => 'Membresía avanzada de Rsistanc con beneficios adicionales',
                'classes_before' => 9,
                'duration' => 12,
                'colors' => json_encode([['color' => '#8B4E23'], ['color' => '#AC6832'], ['color' => '#D78945']]),
                'icon' => 'fa-solid fa-dumbbell',
                'is_benefit_shake' => true,
                'shake_quantity' => 2,
                'is_benefit_discipline' => false,
                'discipline_id' => null, // Assuming discipline with ID 1 exists
                'class_completed' => 100,
                'discipline_quantity' => 2,
            ],
            [
                'id' => 3,
                'name' => 'Rsistanc Black',
                'slug' => 'rsistanc-black',
                'level' => 3,
                'description' => 'Membresía premium de Rsistanc con todos los beneficios',
                'classes_before' => 10,
                'duration' => 24,
                'colors' => json_encode([['color' => '#B0694C'], ['color' => '#A267B4']]),
                'icon' => 'fa-solid fa-dumbbell',
                'is_benefit_shake' => true,
                'shake_quantity' => 4,
                'is_benefit_discipline' => true,
                'discipline_id' => 3, // Assuming discipline with ID 3 exists
                'class_completed' => 300,
                'discipline_quantity' => 1,
            ],
        ]);
    }
}
