<?php

namespace Database\Seeders;

use App\Models\ClassModel;
use App\Models\Instructor;
use App\Models\Studio;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class InfoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin']);
        $instructornRole = Role::firstOrCreate(['name' => 'Instructor']);

        $user_admin = User::create([
            'name' => 'Diego Miguel Saravia',
            'email' => 'migelo5511@gmail.com',
            'password' => bcrypt('123456789'),
        ]);

        $user_admin->assignRole($superAdminRole);

        $user_instructor = User::create([
            'name' => 'Danna Cervantes Quispe',
            'email' => 'danna@gmail.com',
            'password' => bcrypt('123456789'),
        ]);
        $user_instructor->assignRole($instructornRole);



        // Clases

        ClassModel::insert([
            // ===== CYCLING CLASSES =====
            [
                'name' => 'Cycling Beats',
                'discipline_id' => 1, // cycling
                'type' => 'presencial',
                'duration_minutes' => 50,
                'max_capacity' => 20,
                'description' => 'Clase de cycling con música energizante y ritmos electrónicos',
                'difficulty_level' => 'all_levels',
                'music_genre' => 'electro',

                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Cycling Power',
                'discipline_id' => 1, // cycling
                'type' => 'presencial',
                'duration_minutes' => 45,
                'max_capacity' => 20,
                'description' => 'Entrenamiento intenso de cycling para quemar calorías y ganar resistencia',
                'difficulty_level' => 'advanced',
                'music_genre' => 'rock',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Cycling Cardio',
                'discipline_id' => 1, // cycling
                'type' => 'presencial',
                'duration_minutes' => 30,
                'max_capacity' => 15,
                'description' => 'Sesión corta pero intensa de cycling perfecto para el lunch break',
                'difficulty_level' => 'intermediate',
                'music_genre' => 'pop',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // ===== SOLID REFORMER CLASSES =====
            [
                'name' => 'Reformer Full Body',
                'discipline_id' => 2, // solidreformer
                'type' => 'presencial',
                'duration_minutes' => 55,
                'max_capacity' => 8,
                'description' => 'Entrenamiento completo en reformer para fortalecer todo el cuerpo',
                'difficulty_level' => 'intermediate',
                'music_genre' => 'instrumental',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Reformer Core Focus',
                'discipline_id' => 2, // solidreformer
                'type' => 'presencial',
                'duration_minutes' => 45,
                'max_capacity' => 10,
                'description' => 'Clase especializada en fortalecer el core usando el reformer',
                'difficulty_level' => 'advanced',
                'music_genre' => 'ambient',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // ===== PILATES MAT CLASSES =====
            [
                'name' => 'Pilates Fundamentals',
                'discipline_id' => 3, // pilates_mat
                'type' => 'presencial',
                'duration_minutes' => 50,
                'max_capacity' => 15,
                'description' => 'Clase de pilates en colchoneta ideal para principiantes',
                'difficulty_level' => 'beginner',
                'music_genre' => 'classical',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Pilates Flow',
                'discipline_id' => 3, // pilates_mat
                'type' => 'presencial',
                'duration_minutes' => 45,
                'max_capacity' => 12,
                'description' => 'Secuencia fluida de pilates que combina fuerza y flexibilidad',
                'difficulty_level' => 'intermediate',
                'music_genre' => 'new_age',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // ===== YOGA CLASSES =====
            [
                'name' => 'Hatha Yoga',
                'discipline_id' => 4, // yoga
                'type' => 'presencial',
                'duration_minutes' => 60,
                'max_capacity' => 20,
                'description' => 'Práctica de yoga suave enfocada en posturas básicas y respiración',
                'difficulty_level' => 'beginner',
                'music_genre' => 'meditation',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Vinyasa Flow',
                'discipline_id' => 4, // yoga
                'type' => 'presencial',
                'duration_minutes' => 75,
                'max_capacity' => 18,
                'description' => 'Yoga dinámico que sincroniza movimiento con respiración',
                'difficulty_level' => 'intermediate',
                'music_genre' => 'world',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Power Yoga',
                'discipline_id' => 4, // yoga
                'type' => 'presencial',
                'duration_minutes' => 60,
                'max_capacity' => 15,
                'description' => 'Yoga intenso que combina fuerza, flexibilidad y resistencia',
                'difficulty_level' => 'advanced',
                'music_genre' => 'upbeat',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // ===== BARRE CLASSES =====
            [
                'name' => 'Barre Classic',
                'discipline_id' => 5, // barre
                'type' => 'presencial',
                'duration_minutes' => 50,
                'max_capacity' => 12,
                'description' => 'Entrenamiento inspirado en ballet que tonifica y alarga los músculos',
                'difficulty_level' => 'all_levels',
                'music_genre' => 'contemporary',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Barre Burn',
                'discipline_id' => 5, // barre
                'type' => 'presencial',
                'duration_minutes' => 45,
                'max_capacity' => 10,
                'description' => 'Barre intenso con movimientos rápidos para quemar calorías',
                'difficulty_level' => 'intermediate',
                'music_genre' => 'dance',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // ===== CLASES VIRTUALES =====
            [
                'name' => 'Virtual Pilates',
                'discipline_id' => 3, // pilates_mat
                'type' => 'presencial',
                'duration_minutes' => 45,
                'max_capacity' => 50,
                'description' => 'Clase de pilates en línea desde la comodidad de tu hogar',
                'difficulty_level' => 'all_levels',
                'music_genre' => 'ambient',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Virtual Yoga Morning',
                'discipline_id' => 4, // yoga
                'type' => 'presencial',
                'duration_minutes' => 30,
                'max_capacity' => 100,
                'description' => 'Sesión matutina de yoga para comenzar el día con energía',
                'difficulty_level' => 'beginner',
                'music_genre' => 'nature',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // ===== CLASES ESPECIALES =====
            [
                'name' => 'Cycling + Abs',
                'discipline_id' => 1, // cycling
                'type' => 'presencial',
                'duration_minutes' => 60,
                'max_capacity' => 15,
                'description' => 'Combinación de cycling intenso seguido de entrenamiento de abdominales',
                'difficulty_level' => 'intermediate',
                'music_genre' => 'hip_hop',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Fin clases

        // Studios o Salas
        $studioOne = Studio::insert(
            [
                [
                    'name' => 'Cycling Studio A',
                    'location' => 'RSISTANC Miraflores - Planta Baja',
                    'max_capacity' => 20,
                    'equipment_available' => json_encode(['bicicletas_estáticas', 'sistema_audio', 'luces_led', 'ventiladores']),
                    'amenities' => json_encode(['vestuarios', 'duchas', 'casilleros', 'agua_fría', 'toallas_gratis']),
                    'studio_type' => 'cycling',
                    'addressing' => 'right_to_left',
                    'row' => 10,
                    'column' => 10,
                    'capacity_per_seat' => 15,
                    'is_active' => true,
                ],
                [
                    'name' => 'Cycling Studio B',
                    'location' => 'RSISTANC Miraflores - Planta Baja',
                    'max_capacity' => 20,
                    'equipment_available' => json_encode(['bicicletas_estáticas', 'sistema_audio', 'luces_led', 'ventiladores']),
                    'amenities' => json_encode(['vestuarios', 'duchas', 'casilleros', 'agua_fría', 'toallas_gratis']),
                    'studio_type' => 'cycling',
                    'addressing' => 'right_to_left',
                    'row' => 10,
                    'column' => 10,
                    'capacity_per_seat' => 15,
                    'is_active' => true,
                ],
                [
                    'name' => 'Cycling Studio C',
                    'location' => 'RSISTANC Miraflores - Planta Baja',
                    'max_capacity' => 20,
                    'equipment_available' => json_encode(['bicicletas_estáticas', 'sistema_audio', 'luces_led', 'ventiladores']),
                    'amenities' => json_encode(['vestuarios', 'duchas', 'casilleros', 'agua_fría', 'toallas_gratis']),
                    'studio_type' => 'cycling',
                    'addressing' => 'right_to_left',
                    'row' => 10,
                    'column' => 10,
                    'capacity_per_seat' => 15,
                    'is_active' => true,
                ],
                [
                    'name' => 'Cycling Studio D',
                    'location' => 'RSISTANC Miraflores - Planta Baja',
                    'max_capacity' => 20,
                    'equipment_available' => json_encode(['bicicletas_estáticas', 'sistema_audio', 'luces_led', 'ventiladores']),
                    'amenities' => json_encode(['vestuarios', 'duchas', 'casilleros', 'agua_fría', 'toallas_gratis']),
                    'studio_type' => 'cycling',
                    'addressing' => 'right_to_left',
                    'row' => 10,
                    'column' => 10,
                    'capacity_per_seat' => 15,
                    'is_active' => true,
                ]
            ],
        );
        // Fin Studios o salas

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
                    'availability_schedule' => json_encode([
                        [
                            'day' => 'monday',
                            'start_time' => '08:00:00',
                            'end_time' => '20:00:00',
                        ],
                        [
                            'day' => 'tuesday',
                            'start_time' => '08:00:00',
                            'end_time' => '20:00:00',
                        ],
                        [
                            'day' => 'wednesday',
                            'start_time' => '08:00:00',
                            'end_time' => '20:00:00',
                        ],
                        [
                            'day' => 'thursday',
                            'start_time' => '08:00:00',
                            'end_time' => '20:00:00',
                        ],
                        [
                            'day' => 'friday',
                            'start_time' => '08:00:00',
                            'end_time' => '20:00:00',
                        ],
                        [
                            'day' => 'saturday',
                            'start_time' => '08:00:00',
                            'end_time' => '14:00:00',
                        ],
                        [
                            'day' => 'sunday',
                            'start_time' => '08:00:00',
                            'end_time' => '14:00:00',
                        ],
                    ]),
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
                    'availability_schedule' => json_encode([
                        [
                            'day' => 'monday',
                            'start_time' => '08:00:00',
                            'end_time' => '20:00:00',
                        ],
                        [
                            'day' => 'tuesday',
                            'start_time' => '08:00:00',
                            'end_time' => '20:00:00',
                        ],
                        [
                            'day' => 'wednesday',
                            'start_time' => '08:00:00',
                            'end_time' => '20:00:00',
                        ],
                        [
                            'day' => 'thursday',
                            'start_time' => '08:00:00',
                            'end_time' => '20:00:00',
                        ],
                        [
                            'day' => 'friday',
                            'start_time' => '08:00:00',
                            'end_time' => '20:00:00',
                        ],
                        [
                            'day' => 'saturday',
                            'start_time' => '08:00:00',
                            'end_time' => '14:00:00',
                        ],
                        [
                            'day' => 'sunday',
                            'start_time' => '08:00:00',
                            'end_time' => '14:00:00',
                        ],
                    ]),
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
                    'availability_schedule' => json_encode([
                        [
                            'day' => 'monday',
                            'start_time' => '08:00:00',
                            'end_time' => '20:00:00',
                        ],
                        [
                            'day' => 'tuesday',
                            'start_time' => '08:00:00',
                            'end_time' => '20:00:00',
                        ],
                        [
                            'day' => 'wednesday',
                            'start_time' => '08:00:00',
                            'end_time' => '20:00:00',
                        ],
                        [
                            'day' => 'thursday',
                            'start_time' => '08:00:00',
                            'end_time' => '20:00:00',
                        ],
                        [
                            'day' => 'friday',
                            'start_time' => '08:00:00',
                            'end_time' => '20:00:00',
                        ],
                        [
                            'day' => 'saturday',
                            'start_time' => '08:00:00',
                            'end_time' => '14:00:00',
                        ],
                        [
                            'day' => 'sunday',
                            'start_time' => '08:00:00',
                            'end_time' => '14:00:00',
                        ],
                    ]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ],
        );
    }
}
