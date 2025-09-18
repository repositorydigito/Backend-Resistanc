<?php

namespace Database\Seeders;

use App\Models\TemplateEmail;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TemplateEmailSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        TemplateEmail::insert([
            [
                'name' => 'Activar tu cuenta',
                'subject' => '🎉 ¡Activa tu cuenta y empieza a rodar con RSISTANC STUDIO!',
                'body' => '
                <p>¡Esperamos estés teniendo un súper día! Ya formas parte de una red de entrenamiento que combina energía, motivación y resultados.</p>
<p>Solo falta un paso para que empieces a reservar tus clases, disfrutar de nuestros entrenamientos y aprovechar los beneficios exclusivos para miembros.</p>
<p>Haz clic en el botón de abajo para activar tu cuenta:</p>

                ',
            ],
            [
                'name' => 'Bienvenida',
                'subject' => 'Bienvenida/o a tu RSISTANC ERA ✨',

                'body' => '
                <p>Gracias por unirte a RSISTANC, donde el movimiento se convierte en tu mejor hábito.</p>
                <p>Ya formas parte de una comunidad que entrena con propósito y vive con energía.</p>
                <p>Desde hoy, cada clase que tomes suma RSISTANC POINTS y te acerca a más beneficios.</p>
                <p> Prepárate para sudar, sentir y resistir con nosotras. ¿Lista para comenzar tu journey?</p>
                ',
            ],
            [
                'name' => 'Confirmación de paquete',
                'subject' => ' ¡Tu paquete ya está activo! 🎟️',
                'body' => '
                <p>¡Esperamos estés teniendo un súper día! Te dejamos acá el detalle de lo que incluye el paquete RSISTANC que acabas de adquirir ❤️‍🔥</p>
                ',
            ],
            [
                'name' => 'Notificación de clases por vencer',
                'subject' => 'Tus clases están por expirar ⌛ ¡Aprovéchalas!',
                'body' => '
                <p>Tienes clases activas que están por vencer en los próximos días.</p>
                <p>Recuerda que cada clase que tomas suma puntos y te hace subir de nivel.</p>
                <p>¡No pierdas tu progreso! 💥</p>
                ',
            ],
            [
                'name' => 'Subiendo de nivel',
                'subject' => 'Estás a 5 clases de llegar a GOLD 💛',
                'body' => '',
            ],

            [
                'name' => 'Código de verificación',
                'subject' => 'DON’T WORRY! Te ayudamos a recuperar tu cuenta ❤️‍🔥',
                'body' => '
                <p>Recibimos una solicitud para restablecer tu contraseña.</p>
                <p>Tu código de verificación es:</p>
                ',
            ],
            [
                'name' => 'Cambio de instructor',
                'subject' => 'Different RSTAR, same energy! 🔥',
                'body' => '
                <p>Sabemos que cada coach aporta su estilo, y estamos seguras de que esta clase será POWER</p>
                ',
            ],

        ]);
    }
}
