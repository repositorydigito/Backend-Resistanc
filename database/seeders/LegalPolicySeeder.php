<?php

namespace Database\Seeders;

use App\Models\LegalPolicy;
use Illuminate\Database\Seeder;

class LegalPolicySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $policies = [
            [
                'type' => LegalPolicy::TYPE_PRIVACY,
                'title' => 'Políticas de Privacidad',
                'subtitle' => 'Tu privacidad es importante para nosotros',

                'is_active' => true,
            ],
            [
                'type' => LegalPolicy::TYPE_TERMS,
                'title' => 'Términos y Condiciones',
                'subtitle' => 'Condiciones de uso de nuestros servicios',

                'is_active' => true,
            ],
        ];

        foreach ($policies as $policy) {
            LegalPolicy::updateOrCreate(
                [
                    'type' => $policy['type'],
                ],
                $policy
            );
        }
    }
}