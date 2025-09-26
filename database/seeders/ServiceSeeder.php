<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $services = [
            [
                'title' => 'Cycling',
                'subtitle' => 'Pedalea hacia tu mejor versión',
                'description' => 'Clases de cycling de alta intensidad con música motivadora y instructores expertos que te llevarán al límite.',
                'image' => '/image/logos/logoBlancoR.svg',
                'order' => 1,
                'is_active' => true,
            ],
            [
                'title' => 'Reformer',
                'subtitle' => 'Fortalece tu core',
                'description' => 'Entrenamiento en máquinas Reformer para mejorar tu fuerza, flexibilidad y postura corporal.',
                'image' => '/image/logos/logoBlancoR.svg',
                'order' => 2,
                'is_active' => true,
            ],
            [
                'title' => 'Pilates',
                'subtitle' => 'Equilibrio y control',
                'description' => 'Clases de Pilates que combinan fuerza, flexibilidad y control mental para un cuerpo equilibrado.',
                'image' => '/image/logos/logoBlancoR.svg',
                'order' => 3,
                'is_active' => true,
            ],
            [
                'title' => 'Box',
                'subtitle' => 'Libera tu fuerza interior',
                'description' => 'Entrenamiento de boxeo para mejorar tu condición física, coordinación y liberar el estrés.',
                'image' => '/image/logos/logoBlancoR.svg',
                'order' => 4,
                'is_active' => true,
            ],
            [
                'title' => 'Nutrición',
                'subtitle' => 'Alimenta tu progreso',
                'description' => 'Asesoría nutricional personalizada y shakes proteicos para complementar tu entrenamiento.',
                'image' => '/image/logos/logoBlancoR.svg',
                'order' => 5,
                'is_active' => true,
            ],
            [
                'title' => 'Recovery',
                'subtitle' => 'Recuperación activa',
                'description' => 'Sesiones de recuperación, masajes y terapias para optimizar tu rendimiento y bienestar.',
                'image' => '/image/logos/logoBlancoR.svg',
                'order' => 6,
                'is_active' => true,
            ],
        ];

        foreach ($services as $service) {
            Service::create($service);
        }
    }
}