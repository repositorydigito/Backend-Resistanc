<?php

namespace Database\Seeders;

use App\Models\Post;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PostSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Post::insert([
            ['title' => 'Beneficios del Ciclismo Indoor', 'slug' => 'beneficios_del_ciclismo_indoor', 'category_id' => 1, 'content' => '<h2 style="color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 10px;">Beneficios del Ciclismo Indoor: Más que un Ejercicio, una Experiencia</h2>

<div style="background-color: #f8f9fa; padding: 15px; border-left: 4px solid #3498db; margin: 20px 0;">
    <p style="margin: 0; font-style: italic; color: #555;">"El ciclismo indoor no es solo sobre pedalear, es sobre superar tus límites en cada sesión"</p>
</div>

<p>El <strong>ciclismo indoor</strong> se ha convertido en una de las actividades más populares en los gimnasios de todo el mundo. Combina el entrenamiento cardiovascular intenso con la motivación grupal, creando una experiencia fitness única.</p>

<h3 style="color: #2c3e50; margin-top: 25px;">¿Qué es el Ciclismo Indoor?</h3>

<p>El ciclismo indoor es un ejercicio aeróbico que se realiza sobre una bicicleta estática especialmente diseñada para este fin. A diferencia de las bicicletas tradicionales, estas permiten ajustar la resistencia para simular diferentes terrenos y intensidades.</p>

<h3 style="color: #2c3e50; margin-top: 25px;">Principales Beneficios</h3>

<ul style="background-color: #f8f9fa; padding: 20px 20px 20px 40px; border-radius: 8px;">
    <li style="margin-bottom: 10px;"><strong>Quema calórica intensa:</strong> Una sesión de 45 minutos puede quemar entre 400-600 calorías.</li>
    <li style="margin-bottom: 10px;"><strong>Bajo impacto articular:</strong> Ideal para personas con problemas de rodillas o articulaciones.</li>
    <li style="margin-bottom: 10px;"><strong>Fortalece el sistema cardiovascular:</strong> Mejora la salud de tu corazón y capacidad pulmonar.</li>
    <li style="margin-bottom: 10px;"><strong>Tonifica piernas y glúteos:</strong> Trabaja los principales grupos musculares de la parte inferior del cuerpo.</li>
    <li style="margin-bottom: 10px;"><strong>Reduce el estrés:</strong> La combinación de música y ejercicio libera endorfinas.</li>
</ul>

<h3 style="color: #2c3e50; margin-top: 25px;">Tipos de Clases de Ciclismo Indoor</h3>

<table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
    <thead>
        <tr style="background-color: #3498db; color: white;">
            <th style="padding: 12px; text-align: left; border: 1px solid #ddd;">Tipo de Clase</th>
            <th style="padding: 12px; text-align: left; border: 1px solid #ddd;">Enfoque</th>
            <th style="padding: 12px; text-align: left; border: 1px solid #ddd;">Duración</th>
        </tr>
    </thead>
    <tbody>
        <tr style="background-color: #f2f2f2;">
            <td style="padding: 12px; border: 1px solid #ddd;">HIIT Cycling</td>
            <td style="padding: 12px; border: 1px solid #ddd;">Intervalos de alta intensidad</td>
            <td style="padding: 12px; border: 1px solid #ddd;">30-45 min</td>
        </tr>
        <tr>
            <td style="padding: 12px; border: 1px solid #ddd;">Endurance Ride</td>
            <td style="padding: 12px; border: 1px solid #ddd;">Resistencia y capacidad aeróbica</td>
            <td style="padding: 12px; border: 1px solid #ddd;">45-60 min</td>
        </tr>
        <tr style="background-color: #f2f2f2;">
            <td style="padding: 12px; border: 1px solid #ddd;">Rhythm Cycling</td>
            <td style="padding: 12px; border: 1px solid #ddd;">Sincronización con la música</td>
            <td style="padding: 12px; border: 1px solid #ddd;">45 min</td>
        </tr>
    </tbody>
</table>

<h3 style="color: #2c3e50; margin-top: 25px;">Consejos para Principiantes</h3>

<ol style="background-color: #e8f4fc; padding: 20px 20px 20px 40px; border-radius: 8px;">
    <li style="margin-bottom: 10px;">Ajusta correctamente la bicicleta: altura del sillín y manillar.</li>
    <li style="margin-bottom: 10px;">Mantén una hidratación constante durante la clase.</li>
    <li style="margin-bottom: 10px;">No te compares con otros, ve a tu propio ritmo.</li>
    <li style="margin-bottom: 10px;">Usa ropa cómoda y preferiblemente de materiales técnicos.</li>
    <li style="margin-bottom: 10px;">Comunica cualquier molestia al instructor.</li>
</ol>

<div style="background-color: #e74c3c; color: white; padding: 20px; border-radius: 8px; margin: 25px 0;">
    <h4 style="margin-top: 0;">¡Precaución!</h4>
    <p>Si tienes problemas cardíacos, lesiones recientes o estás embarazada, consulta con tu médico antes de comenzar cualquier programa de ciclismo indoor.</p>
</div>

<p>El ciclismo indoor es una excelente forma de mejorar tu condición física mientras te diviertes. ¿Listo para subirte a la bici?</p>

<p><em>Publicado el 15 de marzo de 2024 por Departamento de Fitness</em></p>', 'user_id' => 1, 'status' => 'published'],
            ['title' => 'Segundo Post', 'slug' => 'segundo_post', 'category_id' => 2, 'content' => 'Contenido del segundo post', 'user_id' => 1, 'status' => 'draft'],
            ['title' => 'Tercer Post', 'slug' => 'tercer_post', 'category_id' => 3, 'content' => 'Contenido del tercer post', 'user_id' => 1, 'status' => 'draft'],
        ]);
    }
}
