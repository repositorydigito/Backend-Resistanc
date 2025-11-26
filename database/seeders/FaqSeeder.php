<?php

namespace Database\Seeders;

use App\Models\Faq;
use Illuminate\Database\Seeder;

class FaqSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faqs = [
            [
                'question' => '¿Qué incluye mi membresía RSISTANC?',
                'answer' => 'Tu membresía te da acceso a nuestras cuatro disciplinas: Cycling, Reformer, Pilates y Box. Además, acumulas puntos con cada clase que puedes canjear por recompensas exclusivas. Gestiona todo desde nuestra app.',
                'order' => 1,
                'is_active' => true,
            ],
            [
                'question' => '¿Ofrecen clases de prueba para nuevos miembros?',
                'answer' => 'Sí, ofrecemos clases de prueba gratuitas para nuevos miembros. Solo necesitas registrarte en nuestra app y reservar tu clase desde allí.',
                'order' => 2,
                'is_active' => true,
            ],
            [
                'question' => '¿Dónde se encuentran los estudios de RSISTANC?',
                'answer' => 'Nuestro estudio principal está ubicado en Avenida Surco 123, Santiago de Surco, Lima, Perú. Pronto abriremos más sedes en otras zonas.',
                'order' => 3,
                'is_active' => true,
            ],
            [
                'question' => '¿Puedo combinar diferentes clases en mi paquete?',
                'answer' => 'Sí, puedes mixear cualquier combinación de clases según tu preferencia. Elige entre Cycling, Reformer, Pilates y Box sin límites.',
                'order' => 4,
                'is_active' => true,
            ],
            [
                'question' => '¿Cómo puedo reservar una clase?',
                'answer' => 'Reserva tu clase desde nuestra app móvil o web. Solo selecciona la fecha, hora y disciplina, y listo. ¡Tu lugar ya está asegurado!',
                'order' => 5,
                'is_active' => true,
            ],
        ];

        foreach ($faqs as $faq) {
            Faq::create($faq);
        }
    }
}