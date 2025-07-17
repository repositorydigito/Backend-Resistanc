<?php

namespace Database\Seeders;

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

        // Studios o Salas
        Studio::create([
            'name' => 'Cycling Studio A',
            'location' => 'RSISTANC Miraflores - Planta Baja',
            'max_capacity' => 20,
            'equipment_available' => json_encode(['bicicletas_estáticas', 'sistema_audio', 'luces_led', 'ventiladores']),
            'amenities' => json_encode(['vestuarios', 'duchas', 'casilleros', 'agua_fría', 'toallas_gratis']),
            'studio_type' => 'cycling',
            'addressing' => 'right_to_left',
            'row' => 3,
            'column' => 5,
            'capacity_per_seat' => 15,
            'is_active' => true,
        ]);

        Studio::create([
            'name' => 'Cycling Studio B',
            'location' => 'RSISTANC Miraflores - Planta Baja',
            'max_capacity' => 20,
            'equipment_available' => json_encode(['bicicletas_estáticas', 'sistema_audio', 'luces_led', 'ventiladores']),
            'amenities' => json_encode(['vestuarios', 'duchas', 'casilleros', 'agua_fría', 'toallas_gratis']),
            'studio_type' => 'cycling',
            'addressing' => 'right_to_left',
            'row' => 10,
            'column' => 2,
            'capacity_per_seat' => 20,
            'is_active' => true,
        ]);
        Studio::create([
            'name' => 'Cycling Studio C',
            'location' => 'RSISTANC Miraflores - Planta Baja',
            'max_capacity' => 20,
            'equipment_available' => json_encode(['bicicletas_estáticas', 'sistema_audio', 'luces_led', 'ventiladores']),
            'amenities' => json_encode(['vestuarios', 'duchas', 'casilleros', 'agua_fría', 'toallas_gratis']),
            'studio_type' => 'cycling',
            'addressing' => 'center',
            'row' => 5,
            'column' => 4,
            'capacity_per_seat' => 15,
            'is_active' => true,
        ]);

        Studio::create([
            'name' => 'Cycling Studio D',
            'location' => 'RSISTANC Miraflores - Planta Baja',
            'max_capacity' => 20,
            'equipment_available' => json_encode(['bicicletas_estáticas', 'sistema_audio', 'luces_led', 'ventiladores']),
            'amenities' => json_encode(['vestuarios', 'duchas', 'casilleros', 'agua_fría', 'toallas_gratis']),
            'studio_type' => 'cycling',
            'addressing' => 'left_to_right',
            'row' => 4,
            'column' => 3,
            'capacity_per_seat' => 12,
            'is_active' => true,
        ]);
    }
}
