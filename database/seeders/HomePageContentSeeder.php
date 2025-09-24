<?php

namespace Database\Seeders;

use App\Models\HomePageContent;
use Illuminate\Database\Seeder;

class HomePageContentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $contents = [
            // Hero Section
            [
                'section' => 'hero',
                'key' => 'title_line_1',
                'value' => 'TRAIN YOUR RSISTANC.',
                'type' => 'text',
                'order' => 1,
            ],
            [
                'section' => 'hero',
                'key' => 'title_line_2',
                'value' => 'LIVE UNSTOPPABLE.',
                'type' => 'text',
                'order' => 2,
            ],
            [
                'section' => 'hero',
                'key' => 'description',
                'value' => 'Clases que te transforman. Energía que te eleva. Una comunidad que te empuja a más.',
                'type' => 'textarea',
                'order' => 3,
            ],
            [
                'section' => 'hero',
                'key' => 'primary_button_text',
                'value' => 'EMPIEZA HOY',
                'type' => 'text',
                'order' => 4,
            ],
            [
                'section' => 'hero',
                'key' => 'primary_button_link',
                'value' => '#membresias',
                'type' => 'url',
                'order' => 5,
            ],
            [
                'section' => 'hero',
                'key' => 'secondary_button_text',
                'value' => 'RESERVA TU CLASE DE PRUEBA',
                'type' => 'text',
                'order' => 6,
            ],
            [
                'section' => 'hero',
                'key' => 'secondary_button_link',
                'value' => '#disciplinas',
                'type' => 'url',
                'order' => 7,
            ],

            // Disciplines Section
            [
                'section' => 'disciplines',
                'key' => 'title',
                'value' => 'ELIGE CÓMO QUIERES MOVERTE',
                'type' => 'text',
                'order' => 1,
            ],

            // Packages Section
            [
                'section' => 'packages',
                'key' => 'card_1_title_line_1',
                'value' => 'PAQUETES',
                'type' => 'text',
                'order' => 1,
            ],
            [
                'section' => 'packages',
                'key' => 'card_1_title_line_2',
                'value' => 'QUE SE ADAPTAN',
                'type' => 'text',
                'order' => 2,
            ],
            [
                'section' => 'packages',
                'key' => 'card_1_title_line_3',
                'value' => 'A TU RITMO.',
                'type' => 'text',
                'order' => 3,
            ],
            [
                'section' => 'packages',
                'key' => 'card_1_subtitle',
                'value' => 'Desde 1 hasta 40 clases.',
                'type' => 'text',
                'order' => 4,
            ],
            [
                'section' => 'packages',
                'key' => 'card_1_description_line_1',
                'value' => 'Mixea disciplinas, suma puntos,',
                'type' => 'text',
                'order' => 5,
            ],
            [
                'section' => 'packages',
                'key' => 'card_1_description_line_2',
                'value' => 'sube de nivel.',
                'type' => 'text',
                'order' => 6,
            ],
            [
                'section' => 'packages',
                'key' => 'card_1_button_text',
                'value' => 'VER PAQUETES →',
                'type' => 'text',
                'order' => 7,
            ],
            [
                'section' => 'packages',
                'key' => 'card_1_button_link',
                'value' => '#membresias',
                'type' => 'url',
                'order' => 8,
            ],
            [
                'section' => 'packages',
                'key' => 'card_2_title',
                'value' => 'MÁS RESISTANCE, MÁS REWARDS.',
                'type' => 'text',
                'order' => 9,
            ],
            [
                'section' => 'packages',
                'key' => 'card_2_subtitle',
                'value' => 'Entrenar tiene beneficios reales:',
                'type' => 'text',
                'order' => 10,
            ],
            [
                'section' => 'packages',
                'key' => 'card_2_description_line_1',
                'value' => 'Early access, descuentos y shakes gratis',
                'type' => 'text',
                'order' => 11,
            ],
            [
                'section' => 'packages',
                'key' => 'card_2_description_line_2',
                'value' => 'alcanzando la categoría GOLD y BLACK.',
                'type' => 'text',
                'order' => 12,
            ],
            [
                'section' => 'packages',
                'key' => 'card_2_button_text',
                'value' => 'VER BENEFICIOS →',
                'type' => 'text',
                'order' => 13,
            ],
            [
                'section' => 'packages',
                'key' => 'card_2_button_link',
                'value' => '#beneficios',
                'type' => 'url',
                'order' => 14,
            ],

            // Services Section
            [
                'section' => 'services',
                'key' => 'title',
                'value' => 'SERVICIOS',
                'type' => 'text',
                'order' => 1,
            ],
            [
                'section' => 'services',
                'key' => 'subtitle',
                'value' => 'Explora lo que hace única tu experiencia en R STUDIO, dentro y fuera del training floor.',
                'type' => 'textarea',
                'order' => 2,
            ],
            [
                'section' => 'services',
                'key' => 'card_footer_text',
                'value' => '⚡Shake it, wear it, own it.',
                'type' => 'text',
                'order' => 3,
            ],

            // Download Section
            [
                'section' => 'download',
                'key' => 'image',
                'value' => '/image/pages/vistaCel.svg',
                'type' => 'image',
                'order' => 1,
            ],
            [
                'section' => 'download',
                'key' => 'title',
                'value' => 'TU RSISTANC VA CONTIGO.',
                'type' => 'text',
                'order' => 2,
            ],
            [
                'section' => 'download',
                'key' => 'subtitle_line_1',
                'value' => 'Reserva, compra, suma puntos y ve tu progreso desde nuestra app.',
                'type' => 'text',
                'order' => 3,
            ],
            [
                'section' => 'download',
                'key' => 'subtitle_line_2',
                'value' => 'Simple, rápida, tuya.',
                'type' => 'text',
                'order' => 4,
            ],
            [
                'section' => 'download',
                'key' => 'ios_icon',
                'value' => '/image/logos/iconos/ios.svg',
                'type' => 'image',
                'order' => 5,
            ],
            [
                'section' => 'download',
                'key' => 'android_icon',
                'value' => '/image/logos/iconos/android.svg',
                'type' => 'image',
                'order' => 6,
            ],

            // Location Section
            [
                'section' => 'location',
                'key' => 'title_line_1',
                'value' => 'ENCUENTRA',
                'type' => 'text',
                'order' => 1,
            ],
            [
                'section' => 'location',
                'key' => 'title_line_2',
                'value' => 'STUDIO',
                'type' => 'text',
                'order' => 2,
            ],
            [
                'section' => 'location',
                'key' => 'logo_image',
                'value' => '/image/logos/iconos/logor.svg',
                'type' => 'image',
                'order' => 3,
            ],
            [
                'section' => 'location',
                'key' => 'description',
                'value' => 'Ubicado en Surco, diseñado para que te muevas libre y con flow.',
                'type' => 'textarea',
                'order' => 4,
            ],
            [
                'section' => 'location',
                'key' => 'address_icon',
                'value' => '/image/logos/iconos/iconomapa.svg',
                'type' => 'image',
                'order' => 5,
            ],
            [
                'section' => 'location',
                'key' => 'address',
                'value' => 'Avenida Surco 123, Santiago de Surco, Lima, Perú',
                'type' => 'text',
                'order' => 6,
            ],
            [
                'section' => 'location',
                'key' => 'phone_icon',
                'value' => '/image/logos/iconos/iconocel.svg',
                'type' => 'image',
                'order' => 7,
            ],
            [
                'section' => 'location',
                'key' => 'phone',
                'value' => '+51 966532455',
                'type' => 'phone',
                'order' => 8,
            ],
            [
                'section' => 'location',
                'key' => 'email_icon',
                'value' => '/image/logos/iconos/iconomail.svg',
                'type' => 'image',
                'order' => 9,
            ],
            [
                'section' => 'location',
                'key' => 'email',
                'value' => 'hola@rsistanc.com',
                'type' => 'email',
                'order' => 10,
            ],
            [
                'section' => 'location',
                'key' => 'map_image',
                'value' => '/image/pages/mapa.svg',
                'type' => 'image',
                'order' => 11,
            ],
            [
                'section' => 'location',
                'key' => 'map_link',
                'value' => 'https://www.google.com/maps?q=Avenida+Surco+123,+Santiago+de+Surco,+Lima,+Perú',
                'type' => 'url',
                'order' => 12,
            ],

            // FAQ Section
            [
                'section' => 'faq',
                'key' => 'title',
                'value' => 'FAQs',
                'type' => 'text',
                'order' => 1,
            ],
            [
                'section' => 'faq',
                'key' => 'subtitle',
                'value' => '¿Tienes dudas? Resolvemos todo.',
                'type' => 'text',
                'order' => 2,
            ],
        ];

        foreach ($contents as $content) {
            HomePageContent::updateOrCreate(
                [
                    'section' => $content['section'],
                    'key' => $content['key'],
                ],
                $content
            );
        }
    }
}