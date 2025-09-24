<?php

namespace Database\Seeders;

use App\Models\LegalFaq;
use Illuminate\Database\Seeder;

class LegalFaqSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faqs = [
            // FAQs de Políticas de Privacidad
            [
                'type' => LegalFaq::TYPE_PRIVACY,
                'question' => '¿Qué información personal recopilamos?',
                'answer' => '<p>Recopilamos información que nos proporcionas directamente, como:</p><ul><li><strong>Datos personales:</strong> nombre, email, teléfono</li><li><strong>Datos de pago:</strong> información de tarjetas y transacciones</li><li><strong>Datos de uso:</strong> información sobre tu uso de nuestros servicios</li></ul>',
                'order' => 1,
                'is_active' => true,
            ],
            [
                'type' => LegalFaq::TYPE_PRIVACY,
                'question' => '¿Cómo utilizamos tu información personal?',
                'answer' => '<p>Utilizamos tu información para:</p><ol><li>Proporcionarte nuestros servicios</li><li>Procesar pagos y transacciones</li><li>Comunicarnos contigo</li><li>Mejorar nuestros servicios</li><li>Cumplir con obligaciones legales</li></ol>',
                'order' => 2,
                'is_active' => true,
            ],
            [
                'type' => LegalFaq::TYPE_PRIVACY,
                'question' => '¿Compartimos tu información con terceros?',
                'answer' => 'No vendemos tu información personal. Solo compartimos información con terceros cuando es necesario para proporcionar nuestros servicios, como procesadores de pago, o cuando la ley lo requiere.',
                'order' => 3,
                'is_active' => true,
            ],
            [
                'type' => LegalFaq::TYPE_PRIVACY,
                'question' => '¿Cómo protegemos tu información?',
                'answer' => 'Implementamos medidas de seguridad técnicas y organizativas para proteger tu información personal contra acceso no autorizado, alteración, divulgación o destrucción.',
                'order' => 4,
                'is_active' => true,
            ],
            [
                'type' => LegalFaq::TYPE_PRIVACY,
                'question' => '¿Cuáles son tus derechos sobre tu información?',
                'answer' => 'Tienes derecho a acceder, rectificar, eliminar o limitar el procesamiento de tu información personal. También puedes solicitar la portabilidad de tus datos o retirar tu consentimiento.',
                'order' => 5,
                'is_active' => true,
            ],

            // FAQs de Términos y Condiciones
            [
                'type' => LegalFaq::TYPE_TERMS,
                'question' => '¿Quién puede usar los servicios de RSISTANC?',
                'answer' => '<p>Nuestros servicios están disponibles para:</p><ul><li><strong>Personas mayores de 18 años</strong></li><li><strong>Menores con autorización</strong> de sus padres o tutores</li></ul><p><strong>Requisitos:</strong></p><ul><li>Proporcionar información precisa</li><li>Mantener la confidencialidad de tu cuenta</li></ul>',
                'order' => 1,
                'is_active' => true,
            ],
            [
                'type' => LegalFaq::TYPE_TERMS,
                'question' => '¿Cuáles son las políticas de cancelación y reembolso?',
                'answer' => 'Las clases pueden cancelarse hasta 12 horas antes del inicio sin penalización. Los paquetes de clases no utilizados pueden reembolsarse según nuestras políticas específicas de reembolso.',
                'order' => 2,
                'is_active' => true,
            ],
            [
                'type' => LegalFaq::TYPE_TERMS,
                'question' => '¿Qué responsabilidades tienes como usuario?',
                'answer' => 'Debes usar nuestros servicios de manera responsable, seguir las reglas del estudio, respetar a otros usuarios y al personal, y notificar cualquier lesión o condición médica relevante.',
                'order' => 3,
                'is_active' => true,
            ],
            [
                'type' => LegalFaq::TYPE_TERMS,
                'question' => '¿Cuáles son nuestras limitaciones de responsabilidad?',
                'answer' => 'RSISTANC no se hace responsable por lesiones que puedan ocurrir durante las actividades físicas. Los usuarios participan bajo su propio riesgo y deben consultar con un médico antes de comenzar cualquier programa de ejercicios.',
                'order' => 4,
                'is_active' => true,
            ],
            [
                'type' => LegalFaq::TYPE_TERMS,
                'question' => '¿Cómo se modifican estos términos?',
                'answer' => 'Nos reservamos el derecho de modificar estos términos en cualquier momento. Te notificaremos sobre cambios importantes y tu uso continuado de nuestros servicios constituye aceptación de los nuevos términos.',
                'order' => 5,
                'is_active' => true,
            ],
        ];

        foreach ($faqs as $faq) {
            LegalFaq::create($faq);
        }
    }
}