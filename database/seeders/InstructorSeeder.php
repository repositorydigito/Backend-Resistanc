<?php

namespace Database\Seeders;

use App\Models\Instructor;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class InstructorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $this->command->info('🌱 Seeders de instructores');
        // Instructore
        $instructornRole = Role::firstOrCreate(['name' => 'Instructor']);


        $user_instructor = User::create([
            'name' => 'Danna Cervantes Quispe',
            'email' => 'danna@gmail.com',
            'password' => bcrypt('123456789'),
        ]);
        $user_instructor->assignRole($instructornRole);

        $user_instructor_two = User::create([
            'name' => 'Alex Palomino',
            'email' => 'palomino@gmail.com',
            'password' => bcrypt('123456789'),
        ]);
        $user_instructor_two->assignRole($instructornRole);

        $user_instructor_three = User::create([
            'name' => 'Lucero Saravia',
            'email' => 'lulu@gmail.com',
            'password' => bcrypt('123456789'),
        ]);
        $user_instructor_three->assignRole($instructornRole);

        // Fin instructores

        // Instructores
        $instructors = Instructor::insert(
            [
                [
                    'name' => 'Juan Pérez',
                    'email' => 'juan@gmail.com',
                    'phone' => '987654321',
                    'specialties' => json_encode([1, 2, 3, 4, 5]),
                    'bio' => 'Instructor con más de 10 años de experiencia en Cycling, Reforma, Pilates, Yoga y Barre. Certificado en Spinning, Pilates Reformer, Mat Pilates, Yoga Alliance y Barre Method. Apasionado por ayudar a los estudiantes a alcanzar sus objetivos de fitness y bienestar.',
                    'certifications' => json_encode(['Spinning Certified', 'Pilates Reformer Certified', 'Mat Pilates Certified', 'Yoga Alliance RYT-200', 'Barre Method Certified']),
                    'profile_image' => '/images/instructors/diego_miguel_saravia.jpg',
                    'instagram_handle' => '@diego_miguel_saravia',
                    'is_head_coach' => true,
                    'experience_years' => 10,
                    'rating_average' => 4.8,
                    'total_classes_taught' => 500,
                    'hire_date' => '2015-01-01',
                    'hourly_rate_soles' => 150.00,
                    'status' => 'active',
                    'type_document' => 'dni',
                    'document_number' => '12345674',

                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'Pedro Gómez',
                    'email' => 'pedro@gmail.com',
                    'phone' => '987654321',
                    'specialties' => json_encode([1, 2, 3, 4, 5]),
                    'bio' => 'Instructor con más de 10 años de experiencia en Cycling, Reforma, Pilates, Yoga y Barre. Certificado en Spinning, Pilates Reformer, Mat Pilates, Yoga Alliance y Barre Method. Apasionado por ayudar a los estudiantes a alcanzar sus objetivos de fitness y bienestar.',
                    'certifications' => json_encode(['Spinning Certified', 'Pilates Reformer Certified', 'Mat Pilates Certified', 'Yoga Alliance RYT-200', 'Barre Method Certified']),
                    'profile_image' => '/images/instructors/danna_cervantes_quispe.jpg',
                    'instagram_handle' => '@danna_cervantes_quispe',
                    'is_head_coach' => false,
                    'experience_years' => 10,
                    'rating_average' => 4.8,
                    'total_classes_taught' => 500,
                    'hire_date' => '2015-01-01',
                    'hourly_rate_soles' => 150.00,
                    'status' => 'active',
                    'type_document' => 'dni',
                    'document_number' => '12345678',

                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'Ana López',
                    'email' => 'ana@gmail.com',
                    'phone' => '987654321',
                    'specialties' => json_encode([1, 2, 3, 4, 5]),
                    'bio' => 'Instructor con más de 10 años de experiencia en Cycling, Reforma, Pilates, Yoga y Barre. Certificado en Spinning, Pilates Reformer, Mat Pilates, Yoga Alliance y Barre Method. Apasionado por ayudar a los estudiantes a alcanzar sus objetivos de fitness y bienestar.',
                    'certifications' => json_encode(['Spinning Certified', 'Pilates Reformer Certified', 'Mat Pilates Certified', 'Yoga Alliance RYT-200', 'Barre Method Certified']),
                    'profile_image' => '/images/instructors/diego_miguel_saravia.jpg',
                    'instagram_handle' => '@diego_miguel_saravia',
                    'is_head_coach' => false,
                    'experience_years' => 10,
                    'rating_average' => 4.8,
                    'total_classes_taught' => 500,
                    'hire_date' => '2015-01-01',
                    'hourly_rate_soles' => 150.00,
                    'status' => 'active',
                    'type_document' => 'dni',
                    'document_number' => '12345673',

                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ],
        );



        // Reemplaza esta parte del código:

        $instructors = Instructor::all();

        foreach ($instructors as $instructor) {
            $instructor->disciplines()->sync([1, 2, 3]);
        }
        // Fin instructores




    }
}
