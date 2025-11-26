<?php

namespace Database\Seeders;

use App\Models\Discipline;
use App\Models\Studio;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class StudioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌱 Seeders de Studio o salas');

        // Obtener las disciplinas existentes
        $cycling = Discipline::where('name', 'Cycling')->first();
        $reformer = Discipline::where('name', 'Reformer')->first();
        $pilates = Discipline::where('name', 'Pilates')->first();
        $box = Discipline::where('name', 'Box')->first();

        // Studios o Salas con disciplinas específicas
        $studioA = Studio::create([
            'name' => 'Cycling Studio A',
            'location' => 'RSISTANC Miraflores - Planta Baja',
            'max_capacity' => 20,
            'equipment_available' => json_encode(['bicicletas_estáticas', 'sistema_audio', 'luces_led', 'ventiladores']),
            'amenities' => json_encode(['vestuarios', 'duchas', 'casilleros', 'agua_fría', 'toallas_gratis']),
            'addressing' => 'center',
            'row' => 3,
            'column' => 5,
            'capacity_per_seat' => 15,
            'is_active' => true,
        ]);
        // Asignar disciplina de Cycling
        if ($cycling) {
            $studioA->disciplines()->attach($cycling->id);
        }

        $studioB = Studio::create([
            'name' => 'Reformer Studio A',
            'location' => 'RSISTANC Miraflores - Planta Baja',
            'max_capacity' => 15,
            'equipment_available' => json_encode(['reformers', 'mats_pilates', 'pelotas', 'bandas_elásticas', 'aros_magia']),
            'amenities' => json_encode(['vestuarios', 'duchas', 'casilleros', 'agua_fría', 'toallas_gratis']),
            'addressing' => 'center',
            'row' => 10,
            'column' => 2,
            'capacity_per_seat' => 15,
            'is_active' => true,
        ]);
        // Asignar disciplina de Reformer
        if ($reformer) {
            $studioB->disciplines()->attach($reformer->id);
        }

        $studioC = Studio::create([
            'name' => 'Pilates Studio A',
            'location' => 'RSISTANC Miraflores - Planta Baja',
            'max_capacity' => 20,
            'equipment_available' => json_encode(['mats_pilates', 'pelotas', 'bandas_elásticas', 'bloques', 'rodillos']),
            'amenities' => json_encode(['vestuarios', 'duchas', 'casilleros', 'agua_fría', 'toallas_gratis']),
            'addressing' => 'center',
            'row' => 5,
            'column' => 4,
            'capacity_per_seat' => 20,
            'is_active' => true,
        ]);
        // Asignar disciplina de Pilates
        if ($pilates) {
            $studioC->disciplines()->attach($pilates->id);
        }

        $studioD = Studio::create([
            'name' => 'Box Studio A',
            'location' => 'RSISTANC Miraflores - Planta Baja',
            'max_capacity' => 25,
            'equipment_available' => json_encode(['sacos_boxeo', 'guantes', 'vendajes', 'cuerdas_saltar', 'espejos_pared']),
            'amenities' => json_encode(['vestuarios', 'duchas', 'casilleros', 'agua_fría', 'toallas_gratis']),
            'addressing' => 'center',
            'row' => 4,
            'column' => 3,
            'capacity_per_seat' => 12,
            'is_active' => true,
        ]);
        // Asignar disciplina de Box
        if ($box) {
            $studioD->disciplines()->attach($box->id);
        }

    }
}
