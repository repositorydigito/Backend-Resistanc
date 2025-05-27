<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\StudioLocation;
use Illuminate\Database\Seeder;

class StudioLocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('📍 Seeding studio locations...');

        $locations = [
            [
                'name' => 'RSISTANC Miraflores',
                'address_line' => 'Av. Larco 1234, Miraflores',
                'city' => 'Lima',
                'country' => 'PE',
                'latitude' => -12.1191,
                'longitude' => -77.0292,
                'phone' => '+51 1 234-5678',
                'is_active' => true,
            ],
            [
                'name' => 'RSISTANC San Isidro',
                'address_line' => 'Av. Conquistadores 456, San Isidro',
                'city' => 'Lima',
                'country' => 'PE',
                'latitude' => -12.0964,
                'longitude' => -77.0428,
                'phone' => '+51 1 345-6789',
                'is_active' => true,
            ],
            [
                'name' => 'RSISTANC Surco',
                'address_line' => 'Av. Primavera 789, Santiago de Surco',
                'city' => 'Lima',
                'country' => 'PE',
                'latitude' => -12.1348,
                'longitude' => -76.9836,
                'phone' => '+51 1 456-7890',
                'is_active' => true,
            ],
            [
                'name' => 'RSISTANC La Molina',
                'address_line' => 'Av. Javier Prado Este 2345, La Molina',
                'city' => 'Lima',
                'country' => 'PE',
                'latitude' => -12.0769,
                'longitude' => -76.9447,
                'phone' => '+51 1 567-8901',
                'is_active' => true,
            ],
            [
                'name' => 'RSISTANC Barranco',
                'address_line' => 'Av. Grau 567, Barranco',
                'city' => 'Lima',
                'country' => 'PE',
                'latitude' => -12.1464,
                'longitude' => -77.0206,
                'phone' => '+51 1 678-9012',
                'is_active' => true,
            ],
            [
                'name' => 'RSISTANC San Borja',
                'address_line' => 'Av. San Luis 890, San Borja',
                'city' => 'Lima',
                'country' => 'PE',
                'latitude' => -12.1067,
                'longitude' => -77.0031,
                'phone' => '+51 1 789-0123',
                'is_active' => true,
            ],
            [
                'name' => 'RSISTANC Magdalena',
                'address_line' => 'Av. Brasil 1122, Magdalena del Mar',
                'city' => 'Lima',
                'country' => 'PE',
                'latitude' => -12.0908,
                'longitude' => -77.0747,
                'phone' => '+51 1 890-1234',
                'is_active' => true,
            ],
            [
                'name' => 'RSISTANC Jesús María',
                'address_line' => 'Av. Salaverry 1455, Jesús María',
                'city' => 'Lima',
                'country' => 'PE',
                'latitude' => -12.0732,
                'longitude' => -77.0567,
                'phone' => '+51 1 901-2345',
                'is_active' => true,
            ],
            [
                'name' => 'RSISTANC Lince',
                'address_line' => 'Av. Arequipa 1678, Lince',
                'city' => 'Lima',
                'country' => 'PE',
                'latitude' => -12.0851,
                'longitude' => -77.0364,
                'phone' => '+51 1 012-3456',
                'is_active' => true,
            ],
            [
                'name' => 'RSISTANC Pueblo Libre',
                'address_line' => 'Av. La Marina 1890, Pueblo Libre',
                'city' => 'Lima',
                'country' => 'PE',
                'latitude' => -12.0742,
                'longitude' => -77.0642,
                'phone' => '+51 1 123-4567',
                'is_active' => true,
            ],
        ];

        foreach ($locations as $locationData) {
            StudioLocation::create($locationData);
            $this->command->line("✅ Created location: {$locationData['name']}");
        }

        $this->command->info("🎉 Created " . count($locations) . " studio locations successfully!");
    }
}
