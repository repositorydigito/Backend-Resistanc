<?php

namespace Database\Seeders;

use App\Models\Basedrink;
use App\Models\ClassModel;
use App\Models\Discipline;
use App\Models\Drink;
use App\Models\Flavordrink;
use App\Models\Instructor;
use App\Models\Package;
use App\Models\Studio;
use App\Models\Typedrink;
use App\Models\User;
use Carbon\Carbon;
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
        $clienteRole = Role::firstOrCreate(['name' => 'Cliente']);

        $user_admin = User::create([
            'name' => 'Diego Miguel Saravia',
            'email' => 'migelo5511@gmail.com',
            'password' => bcrypt('123456789'),
        ]);

        $user_admin->assignRole($superAdminRole);

        // Instructore

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

        // Clientes
        $user_cliente = User::create([
            'name' => 'Melany Mercedes',
            'email' => 'melany@gmail.com',
            'password' => bcrypt('123456789'),
        ]);
        $user_cliente->assignRole($clienteRole);

        $user_cliente_two = User::create([
            'name' => 'Maryory Martines',
            'email' => 'maryory@gmail.com',
            'password' => bcrypt('123456789'),
        ]);
        $user_cliente_two->assignRole($clienteRole);

        $user_cliente_three = User::create([
            'name' => 'Ana Lucía Torres',
            'email' => 'ana@gmail.com',
            'password' => bcrypt('123456789'),
        ]);
        $user_cliente_three->assignRole($clienteRole);

        $user_cliente_four = User::create([
            'name' => 'Maria Molina',
            'email' => 'maria@gmail.com',
            'password' => bcrypt('123456789'),
        ]);
        $user_cliente_four->assignRole($clienteRole);

        // Fin clientes

        // Disciplinas

        $disciplines = Discipline::insert([
            [
                'name' => 'Indoor Cycling',
                'display_name' => 'Indoor Cycling',
                'description' => 'Entrenamiento cardiovascular intenso en bicicletas estáticas con música energizante y luces dinámicas. Quema calorías mientras te diviertes en un ambiente motivador.',
                'icon_url' => '/images/disciplines/cycling.svg',
                'color_hex' => '#945527',
                'equipment_required' => json_encode(['bicicleta_estática', 'toalla', 'botella_agua', 'zapatillas_deportivas']),
                'difficulty_level' => 'all_levels',
                'calories_per_hour_avg' => 600,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Solid Reformer',
                'display_name' => 'Solid Reformer',
                'description' => 'Entrenamiento de fuerza y flexibilidad en una reforma de Pilates. Fortalece todo el cuerpo y mejora la postura.',
                'icon_url' => '/images/disciplines/solid_reformer.svg',
                'color_hex' => '#9D5AA9',
                'equipment_required' => json_encode(['reformer', 'mat', 'props', 'calcetines_antideslizantes']),
                'difficulty_level' => 'intermediate',
                'calories_per_hour_avg' => 350,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Pilates Mat',
                'display_name' => 'Pilates Mat',
                'description' => 'Clases de Pilates en colchoneta para tonificar y fortalecer el core. Ideal para principiantes.',
                'icon_url' => '/images/disciplines/pilates_mat.svg',
                'color_hex' => '#2F59A6',
                'equipment_required' => json_encode(['mat', 'pelota', 'banda_elastica', 'bloque']),
                'difficulty_level' => 'beginner',
                'calories_per_hour_avg' => 250,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],

        ]);

        // Fin discipinas

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

            // // ===== YOGA CLASSES =====
            // [
            //     'name' => 'Hatha Yoga',
            //     'discipline_id' => 3, // yoga
            //     'type' => 'presencial',
            //     'duration_minutes' => 60,
            //     'max_capacity' => 20,
            //     'description' => 'Práctica de yoga suave enfocada en posturas básicas y respiración',
            //     'difficulty_level' => 'beginner',
            //     'music_genre' => 'meditation',
            //     'created_at' => now(),
            //     'updated_at' => now(),
            // ],
            // [
            //     'name' => 'Vinyasa Flow',
            //     'discipline_id' => 3, // yoga
            //     'type' => 'presencial',
            //     'duration_minutes' => 75,
            //     'max_capacity' => 18,
            //     'description' => 'Yoga dinámico que sincroniza movimiento con respiración',
            //     'difficulty_level' => 'intermediate',
            //     'music_genre' => 'world',
            //     'created_at' => now(),
            //     'updated_at' => now(),
            // ],
            // [
            //     'name' => 'Power Yoga',
            //     'discipline_id' => 3, // yoga
            //     'type' => 'presencial',
            //     'duration_minutes' => 60,
            //     'max_capacity' => 15,
            //     'description' => 'Yoga intenso que combina fuerza, flexibilidad y resistencia',
            //     'difficulty_level' => 'advanced',
            //     'music_genre' => 'upbeat',
            //     'created_at' => now(),
            //     'updated_at' => now(),
            // ],

            // // ===== BARRE CLASSES =====
            // [
            //     'name' => 'Barre Classic',
            //     'discipline_id' => 5, // barre
            //     'type' => 'presencial',
            //     'duration_minutes' => 50,
            //     'max_capacity' => 12,
            //     'description' => 'Entrenamiento inspirado en ballet que tonifica y alarga los músculos',
            //     'difficulty_level' => 'all_levels',
            //     'music_genre' => 'contemporary',
            //     'created_at' => now(),
            //     'updated_at' => now(),
            // ],
            // [
            //     'name' => 'Barre Burn',
            //     'discipline_id' => 5, // barre
            //     'type' => 'presencial',
            //     'duration_minutes' => 45,
            //     'max_capacity' => 10,
            //     'description' => 'Barre intenso con movimientos rápidos para quemar calorías',
            //     'difficulty_level' => 'intermediate',
            //     'music_genre' => 'dance',
            //     'created_at' => now(),
            //     'updated_at' => now(),
            // ],

            // // ===== CLASES VIRTUALES =====
            // [
            //     'name' => 'Virtual Pilates',
            //     'discipline_id' => 3, // pilates_mat
            //     'type' => 'presencial',
            //     'duration_minutes' => 45,
            //     'max_capacity' => 50,
            //     'description' => 'Clase de pilates en línea desde la comodidad de tu hogar',
            //     'difficulty_level' => 'all_levels',
            //     'music_genre' => 'ambient',
            //     'created_at' => now(),
            //     'updated_at' => now(),
            // ],
            // [
            //     'name' => 'Virtual Yoga Morning',
            //     'discipline_id' => 4, // yoga
            //     'type' => 'presencial',
            //     'duration_minutes' => 30,
            //     'max_capacity' => 100,
            //     'description' => 'Sesión matutina de yoga para comenzar el día con energía',
            //     'difficulty_level' => 'beginner',
            //     'music_genre' => 'nature',
            //     'created_at' => now(),
            //     'updated_at' => now(),
            // ],

            // // ===== CLASES ESPECIALES =====
            // [
            //     'name' => 'Cycling + Abs',
            //     'discipline_id' => 1, // cycling
            //     'type' => 'presencial',
            //     'duration_minutes' => 60,
            //     'max_capacity' => 15,
            //     'description' => 'Combinación de cycling intenso seguido de entrenamiento de abdominales',
            //     'difficulty_level' => 'intermediate',
            //     'music_genre' => 'hip_hop',
            //     'created_at' => now(),
            //     'updated_at' => now(),
            // ],
        ]);

        // Fin clases

        // Studios o Salas
        Studio::create([
            'name' => 'Cycling Studio A',
            'location' => 'RSISTANC Miraflores - Planta Baja',
            'max_capacity' => 20,
            'equipment_available' => json_encode(['bicicletas_estáticas', 'sistema_audio', 'luces_led', 'ventiladores']),
            'amenities' => json_encode(['vestuarios', 'duchas', 'casilleros', 'agua_fría', 'toallas_gratis']),
            'studio_type' => 'cycling',
            'addressing' => 'right_to_left',
            'row' => 3,
            'column' => 5,
            'capacity_per_seat' => 15,
            'is_active' => true,
        ]);

        Studio::create([
            'name' => 'Cycling Studio B',
            'location' => 'RSISTANC Miraflores - Planta Baja',
            'max_capacity' => 20,
            'equipment_available' => json_encode(['bicicletas_estáticas', 'sistema_audio', 'luces_led', 'ventiladores']),
            'amenities' => json_encode(['vestuarios', 'duchas', 'casilleros', 'agua_fría', 'toallas_gratis']),
            'studio_type' => 'cycling',
            'addressing' => 'right_to_left',
            'row' => 10,
            'column' => 2,
            'capacity_per_seat' => 20,
            'is_active' => true,
        ]);
        Studio::create([
            'name' => 'Cycling Studio C',
            'location' => 'RSISTANC Miraflores - Planta Baja',
            'max_capacity' => 20,
            'equipment_available' => json_encode(['bicicletas_estáticas', 'sistema_audio', 'luces_led', 'ventiladores']),
            'amenities' => json_encode(['vestuarios', 'duchas', 'casilleros', 'agua_fría', 'toallas_gratis']),
            'studio_type' => 'cycling',
            'addressing' => 'center',
            'row' => 5,
            'column' => 4,
            'capacity_per_seat' => 15,
            'is_active' => true,
        ]);

        Studio::create([
            'name' => 'Cycling Studio D',
            'location' => 'RSISTANC Miraflores - Planta Baja',
            'max_capacity' => 20,
            'equipment_available' => json_encode(['bicicletas_estáticas', 'sistema_audio', 'luces_led', 'ventiladores']),
            'amenities' => json_encode(['vestuarios', 'duchas', 'casilleros', 'agua_fría', 'toallas_gratis']),
            'studio_type' => 'cycling',
            'addressing' => 'left_to_right',
            'row' => 4,
            'column' => 3,
            'capacity_per_seat' => 12,
            'is_active' => true,
        ]);



        // $studioOne = Studio::insert(
        //     [
        //         [
        //             'name' => 'Cycling Studio A',
        //             'location' => 'RSISTANC Miraflores - Planta Baja',
        //             'max_capacity' => 20,
        //             'equipment_available' => json_encode(['bicicletas_estáticas', 'sistema_audio', 'luces_led', 'ventiladores']),
        //             'amenities' => json_encode(['vestuarios', 'duchas', 'casilleros', 'agua_fría', 'toallas_gratis']),
        //             'studio_type' => 'cycling',
        //             'addressing' => 'right_to_left',
        //             'row' => 3,
        //             'column' => 5,
        //             'capacity_per_seat' => 15,
        //             'is_active' => true,
        //         ],
        //         [
        //             'name' => 'Cycling Studio B',
        //             'location' => 'RSISTANC Miraflores - Planta Baja',
        //             'max_capacity' => 20,
        //             'equipment_available' => json_encode(['bicicletas_estáticas', 'sistema_audio', 'luces_led', 'ventiladores']),
        //             'amenities' => json_encode(['vestuarios', 'duchas', 'casilleros', 'agua_fría', 'toallas_gratis']),
        //             'studio_type' => 'cycling',
        //             'addressing' => 'right_to_left',
        //             'row' => 10,
        //             'column' => 2,
        //             'capacity_per_seat' => 20,
        //             'is_active' => true,
        //         ],
        //         [
        //             'name' => 'Cycling Studio C',
        //             'location' => 'RSISTANC Miraflores - Planta Baja',
        //             'max_capacity' => 20,
        //             'equipment_available' => json_encode(['bicicletas_estáticas', 'sistema_audio', 'luces_led', 'ventiladores']),
        //             'amenities' => json_encode(['vestuarios', 'duchas', 'casilleros', 'agua_fría', 'toallas_gratis']),
        //             'studio_type' => 'cycling',
        //             'addressing' => 'center',
        //             'row' => 5,
        //             'column' => 4,
        //             'capacity_per_seat' => 15,
        //             'is_active' => true,
        //         ],
        //         [
        //             'name' => 'Cycling Studio D',
        //             'location' => 'RSISTANC Miraflores - Planta Baja',
        //             'max_capacity' => 20,
        //             'equipment_available' => json_encode(['bicicletas_estáticas', 'sistema_audio', 'luces_led', 'ventiladores']),
        //             'amenities' => json_encode(['vestuarios', 'duchas', 'casilleros', 'agua_fría', 'toallas_gratis']),
        //             'studio_type' => 'cycling',
        //             'addressing' => 'left_to_right',
        //             'row' => 4,
        //             'column' => 3,
        //             'capacity_per_seat' => 12,
        //             'is_active' => true,
        //         ]
        //     ],
        // );
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



        // Reemplaza esta parte del código:

        $instructors = Instructor::all();

        foreach ($instructors as $instructor) {
            $instructor->disciplines()->sync([1, 2, 3]);
        }
        // Fin instructores

        // Paquetes

        $currentYear =  now()->year;

        $packages = [
            // CYCLING PACKAGES
            [
                'name' => 'Cycling - Prueba Gratis',
                'slug' => 'cycling-prueba-gratis',
                'description' => 'Prueba gratuita de cycling para nuevos usuarios',
                'short_description' => '1 clase gratis para conocer nuestro cycling',
                'classes_quantity' => 1,
                'price_soles' => 0.00,
                'original_price_soles' => 35.00,
                'validity_days' => 30,
                'mode_type' => 'presencial',
                'billing_type' => 'one_time',
                'type' => 'temporary',
                'commercial_type' => 'promotion',
                'start_date' => now(),
                'end_date' => now()->addMonths(3),
                'is_virtual_access' => false,
                'priority_booking_days' => 1,
                'auto_renewal' => false,
                'is_featured' => true,
                'is_popular' => false,
                'status' => 'active',
                'display_order' => 1,
                'features' => json_encode(['Clase de prueba gratuita', 'Acceso completo a instalaciones']),
                'restrictions' => json_encode(['Solo para nuevos usuarios', 'Una vez por persona']),
                'target_audience' => 'beginner',
                'discipline_id' => 1, // CYCLING
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Cycling - 1 Clase',
                'slug' => 'cycling-1-clase',
                'description' => 'Paquete básico de cycling con 1 clase',
                'short_description' => '1 clase de cycling válida por 1 mes',
                'classes_quantity' => 1,
                'price_soles' => 35.00,
                'original_price_soles' => 35.00,
                'validity_days' => 30,
                'mode_type' => 'presencial',
                'billing_type' => 'one_time',
                'type' => 'fixed',
                'commercial_type' => 'basic',
                'start_date' => null,
                'end_date' => null,
                'is_virtual_access' => false,
                'priority_booking_days' => 1,
                'auto_renewal' => false,
                'is_featured' => false,
                'is_popular' => false,
                'status' => 'active',
                'display_order' => 2,
                'features' => json_encode(['Acceso a clases de cycling', 'Equipamiento incluido']),
                'restrictions' => json_encode(['Válido por 30 días']),
                'target_audience' => 'beginner',
                'discipline_id' => 1, // CYCLING
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Cycling - 5 Clases',
                'slug' => 'cycling-5-clases',
                'description' => 'Paquete mensual de cycling con 5 clases',
                'short_description' => '5 clases de cycling válidas por 1 mes',
                'classes_quantity' => 5,
                'price_soles' => 250.00,
                'original_price_soles' => 250.00,
                'validity_days' => 30,
                'mode_type' => 'presencial',
                'billing_type' => 'one_time',
                'type' => 'fixed',
                'commercial_type' => 'basic',
                'start_date' => null,
                'end_date' => null,
                'is_virtual_access' => false,
                'priority_booking_days' => 2,
                'auto_renewal' => false,
                'is_featured' => false,
                'is_popular' => true,
                'status' => 'active',
                'display_order' => 3,
                'features' => json_encode(['5 clases de cycling', 'Equipamiento incluido', 'Asesoría básica']),
                'restrictions' => json_encode(['Válido por 30 días']),
                'target_audience' => 'intermediate',
                'discipline_id' => 1, // CYCLING
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Cycling - 12 Clases',
                'slug' => 'cycling-12-clases',
                'description' => 'Paquete bimestral de cycling con 12 clases',
                'short_description' => '12 clases de cycling válidas por 2 meses',
                'classes_quantity' => 12,
                'price_soles' => 450.00,
                'original_price_soles' => 450.00,
                'validity_days' => 60,
                'mode_type' => 'presencial',
                'billing_type' => 'one_time',
                'type' => 'fixed',
                'commercial_type' => 'basic',
                'start_date' => null,
                'end_date' => null,
                'is_virtual_access' => false,
                'priority_booking_days' => 3,
                'auto_renewal' => false,
                'is_featured' => false,
                'is_popular' => false,
                'status' => 'active',
                'display_order' => 4,
                'features' => json_encode(['12 clases de cycling', 'Equipamiento incluido', 'Asesoría personalizada']),
                'restrictions' => json_encode(['Válido por 60 días']),
                'target_audience' => 'intermediate',
                'discipline_id' => 1, // CYCLING
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Cycling - 24 Clases',
                'slug' => 'cycling-24-clases',
                'description' => 'Paquete semestral de cycling con 24 clases',
                'short_description' => '24 clases de cycling válidas por 6 meses',
                'classes_quantity' => 24,
                'price_soles' => 850.00,
                'original_price_soles' => 850.00,
                'validity_days' => 180,
                'mode_type' => 'presencial',
                'billing_type' => 'one_time',
                'type' => 'fixed',
                'commercial_type' => 'offer',
                'start_date' => null,
                'end_date' => null,
                'is_virtual_access' => false,
                'priority_booking_days' => 5,
                'auto_renewal' => false,
                'is_featured' => true,
                'is_popular' => false,
                'status' => 'active',
                'display_order' => 5,
                'features' => json_encode(['24 clases de cycling', 'Equipamiento incluido', 'Asesoría personalizada', 'Prioridad en reservas']),
                'restrictions' => json_encode(['Válido por 6 meses']),
                'target_audience' => 'advanced',
                'discipline_id' => 1, // CYCLING
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Cycling - 40 Clases',
                'slug' => 'cycling-40-clases',
                'description' => 'Paquete anual de cycling con 40 clases',
                'short_description' => '40 clases de cycling válidas por 1 año',
                'classes_quantity' => 40,
                'price_soles' => 1700.00,
                'original_price_soles' => 1700.00,
                'validity_days' => 365,
                'mode_type' => 'presencial',
                'billing_type' => 'one_time',
                'type' => 'fixed',
                'commercial_type' => 'basic',
                'start_date' => null,
                'end_date' => null,
                'is_virtual_access' => false,
                'priority_booking_days' => 7,
                'auto_renewal' => false,
                'is_featured' => false,
                'is_popular' => false,
                'status' => 'active',
                'display_order' => 6,
                'features' => json_encode(['40 clases de cycling', 'Equipamiento incluido', 'Asesoría personalizada', 'Máxima prioridad en reservas', 'Acceso a eventos especiales']),
                'restrictions' => json_encode(['Válido por 1 año']),
                'target_audience' => 'advanced',
                'discipline_id' => 1, // CYCLING
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // PILATES PACKAGES
            [
                'name' => 'Pilates - Prueba Gratis',
                'slug' => 'pilates-prueba-gratis',
                'description' => 'Prueba gratuita de pilates para nuevos usuarios',
                'short_description' => '1 clase gratis para conocer nuestro pilates',
                'classes_quantity' => 1,
                'price_soles' => 0.00,
                'original_price_soles' => 69.00,
                'validity_days' => 30,
                'mode_type' => 'presencial',
                'billing_type' => 'one_time',
                'type' => 'temporary',
                'commercial_type' => 'promotion',
                'start_date' => now(),
                'end_date' => now()->addMonths(3),
                'is_virtual_access' => false,
                'priority_booking_days' => 1,
                'auto_renewal' => false,
                'is_featured' => true,
                'is_popular' => false,
                'status' => 'active',
                'display_order' => 7,
                'features' => json_encode(['Clase de prueba gratuita', 'Acceso completo a instalaciones', 'Evaluación postural']),
                'restrictions' => json_encode(['Solo para nuevos usuarios', 'Una vez por persona']),
                'target_audience' => 'beginner',
                'discipline_id' => 2, // PILATES
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Pilates - 1 Clase',
                'slug' => 'pilates-1-clase',
                'description' => 'Paquete básico de pilates con 1 clase',
                'short_description' => '1 clase de pilates válida por 1 mes',
                'classes_quantity' => 1,
                'price_soles' => 69.00,
                'original_price_soles' => 69.00,
                'validity_days' => 30,
                'mode_type' => 'presencial',
                'billing_type' => 'one_time',
                'type' => 'fixed',
                'commercial_type' => 'basic',
                'start_date' => null,
                'end_date' => null,
                'is_virtual_access' => false,
                'priority_booking_days' => 1,
                'auto_renewal' => false,
                'is_featured' => false,
                'is_popular' => false,
                'status' => 'active',
                'display_order' => 8,
                'features' => json_encode(['Acceso a clases de pilates', 'Equipamiento especializado', 'Instrucción personalizada']),
                'restrictions' => json_encode(['Válido por 30 días']),
                'target_audience' => 'beginner',
                'discipline_id' => 2, // PILATES
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Pilates - 4 Clases',
                'slug' => 'pilates-4-clases',
                'description' => 'Paquete mensual de pilates con 4 clases',
                'short_description' => '4 clases de pilates válidas por 1 mes',
                'classes_quantity' => 4,
                'price_soles' => 250.00,
                'original_price_soles' => 250.00,
                'validity_days' => 30,
                'mode_type' => 'presencial',
                'billing_type' => 'one_time',
                'type' => 'fixed',
                'commercial_type' => 'basic',
                'start_date' => null,
                'end_date' => null,
                'is_virtual_access' => false,
                'priority_booking_days' => 2,
                'auto_renewal' => false,
                'is_featured' => false,
                'is_popular' => true,
                'status' => 'active',
                'display_order' => 9,
                'features' => json_encode(['4 clases de pilates', 'Equipamiento especializado', 'Instrucción personalizada', 'Evaluación de progreso']),
                'restrictions' => json_encode(['Válido por 30 días']),
                'target_audience' => 'intermediate',
                'discipline_id' => 2, // PILATES
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Pilates - 8 Clases',
                'slug' => 'pilates-8-clases',
                'description' => 'Paquete bimestral de pilates con 8 clases',
                'short_description' => '8 clases de pilates válidas por 2 meses',
                'classes_quantity' => 8,
                'price_soles' => 450.00,
                'original_price_soles' => 450.00,
                'validity_days' => 60,
                'mode_type' => 'presencial',
                'billing_type' => 'one_time',
                'type' => 'fixed',
                'commercial_type' => 'basic',
                'start_date' => null,
                'end_date' => null,
                'is_virtual_access' => false,
                'priority_booking_days' => 3,
                'auto_renewal' => false,
                'is_featured' => false,
                'is_popular' => false,
                'status' => 'active',
                'display_order' => 10,
                'features' => json_encode(['8 clases de pilates', 'Equipamiento especializado', 'Instrucción personalizada', 'Evaluación de progreso']),
                'restrictions' => json_encode(['Válido por 60 días']),
                'target_audience' => 'intermediate',
                'discipline_id' => 2, // PILATES
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Pilates - 12 Clases',
                'slug' => 'pilates-12-clases',
                'description' => 'Paquete bimestral extendido de pilates con 12 clases',
                'short_description' => '12 clases de pilates válidas por 2 meses',
                'classes_quantity' => 12,
                'price_soles' => 540.00,
                'original_price_soles' => 540.00,
                'validity_days' => 60,
                'mode_type' => 'presencial',
                'billing_type' => 'one_time',
                'type' => 'fixed',
                'commercial_type' => 'basic',
                'start_date' => null,
                'end_date' => null,
                'is_virtual_access' => false,
                'priority_booking_days' => 3,
                'auto_renewal' => false,
                'is_featured' => false,
                'is_popular' => false,
                'status' => 'active',
                'display_order' => 11,
                'features' => json_encode(['12 clases de pilates', 'Equipamiento especializado', 'Instrucción personalizada', 'Evaluación de progreso', 'Plan personalizado']),
                'restrictions' => json_encode(['Válido por 60 días']),
                'target_audience' => 'intermediate',
                'discipline_id' => 2, // PILATES
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Pilates - 20 Clases',
                'slug' => 'pilates-20-clases',
                'description' => 'Paquete semestral de pilates con 20 clases',
                'short_description' => '20 clases de pilates válidas por 6 meses',
                'classes_quantity' => 20,
                'price_soles' => 950.00,
                'original_price_soles' => 950.00,
                'validity_days' => 180,
                'mode_type' => 'presencial',
                'billing_type' => 'one_time',
                'type' => 'fixed',
                'commercial_type' => 'offer',
                'start_date' => null,
                'end_date' => null,
                'is_virtual_access' => false,
                'priority_booking_days' => 5,
                'auto_renewal' => false,
                'is_featured' => true,
                'is_popular' => false,
                'status' => 'active',
                'display_order' => 12,
                'features' => json_encode(['20 clases de pilates', 'Equipamiento especializado', 'Instrucción personalizada', 'Evaluación de progreso', 'Plan personalizado', 'Prioridad en reservas']),
                'restrictions' => json_encode(['Válido por 6 meses']),
                'target_audience' => 'advanced',
                'discipline_id' => 2, // PILATES
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Pilates - 40 Clases',
                'slug' => 'pilates-40-clases',
                'description' => 'Paquete anual de pilates con 40 clases',
                'short_description' => '40 clases de pilates válidas por 1 año',
                'classes_quantity' => 40,
                'price_soles' => 1800.00,
                'original_price_soles' => 1800.00,
                'validity_days' => 365,
                'mode_type' => 'presencial',
                'billing_type' => 'one_time',
                'type' => 'fixed',
                'commercial_type' => 'basic',
                'start_date' => null,
                'end_date' => null,
                'is_virtual_access' => false,
                'priority_booking_days' => 7,
                'auto_renewal' => false,
                'is_featured' => false,
                'is_popular' => false,
                'status' => 'active',
                'display_order' => 13,
                'features' => json_encode(['40 clases de pilates', 'Equipamiento especializado', 'Instrucción personalizada', 'Evaluación de progreso', 'Plan personalizado', 'Máxima prioridad en reservas', 'Acceso a eventos especiales', 'Sesiones de evaluación trimestrales']),
                'restrictions' => json_encode(['Válido por 6 meses']),
                'target_audience' => 'advanced',
                'discipline_id' => 2, // PILATES
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'name' => 'San Valentín - Cycling para Parejas',
                'slug' => 'san-valentin-cycling-parejas',
                'description' => 'Paquete especial de San Valentín para parejas que quieren entrenar juntas',
                'short_description' => '8 clases de cycling para compartir en pareja',
                'classes_quantity' => 8,
                'price_soles' => 320.00,
                'original_price_soles' => 400.00,
                'validity_days' => 45,
                'mode_type' => 'presencial',
                'billing_type' => 'one_time',
                'type' => 'temporary',
                'commercial_type' => 'promotion',
                'start_date' => Carbon::create($currentYear, 2, 1),
                'end_date' => Carbon::create($currentYear, 2, 20),
                'is_virtual_access' => false,
                'priority_booking_days' => 3,
                'auto_renewal' => false,
                'is_featured' => true,
                'is_popular' => true,
                'status' => 'active',
                'display_order' => 1,
                'features' => json_encode(['8 clases de cycling', 'Descuento para parejas', 'Reservas conjuntas', 'Regalo especial San Valentín']),
                'restrictions' => json_encode(['Válido solo en febrero', 'Para 2 personas', 'Debe reservar en pareja']),
                'target_audience' => 'intermediate',
                'discipline_id' => 1, // CYCLING
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'San Valentín - Pilates Duo',
                'slug' => 'san-valentin-pilates-duo',
                'description' => 'Especial de San Valentín para fortalecer el vínculo a través del pilates',
                'short_description' => '6 clases de pilates en pareja',
                'classes_quantity' => 6,
                'price_soles' => 450.00,
                'original_price_soles' => 550.00,
                'validity_days' => 45,
                'mode_type' => 'presencial',
                'billing_type' => 'one_time',
                'type' => 'temporary',
                'commercial_type' => 'promotion',
                'start_date' => Carbon::create($currentYear, 2, 1),
                'end_date' => Carbon::create($currentYear, 2, 20),
                'is_virtual_access' => false,
                'priority_booking_days' => 3,
                'auto_renewal' => false,
                'is_featured' => true,
                'is_popular' => false,
                'status' => 'active',
                'display_order' => 2,
                'features' => json_encode(['6 clases de pilates', 'Sesiones en pareja', 'Evaluación postural conjunta', 'Kit romántico']),
                'restrictions' => json_encode(['Válido solo en febrero', 'Para 2 personas']),
                'target_audience' => 'intermediate',
                'discipline_id' => 2, // PILATES
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // DÍA DE LA MADRE - Segundo domingo de mayo
            [
                'name' => 'Día de la Madre - Wellness Package',
                'slug' => 'dia-madre-wellness-package',
                'description' => 'Paquete especial para celebrar a mamá con bienestar y relajación',
                'short_description' => '10 clases mixtas para el bienestar de mamá',
                'classes_quantity' => 10,
                'price_soles' => 380.00,
                'original_price_soles' => 500.00,
                'validity_days' => 60,
                'mode_type' => 'presencial',
                'billing_type' => 'one_time',
                'type' => 'temporary',
                'commercial_type' => 'promotion',
                'start_date' => Carbon::create($currentYear, 5, 1),
                'end_date' => Carbon::create($currentYear, 5, 20),
                'is_virtual_access' => false,
                'priority_booking_days' => 4,
                'auto_renewal' => false,
                'is_featured' => true,
                'is_popular' => true,
                'status' => 'active',
                'display_order' => 3,
                'features' => json_encode(['10 clases mixtas', 'Clases de relajación', 'Gift especial para mamá', 'Horarios flexibles']),
                'restrictions' => json_encode(['Válido en mayo', 'Promoción especial mamás']),
                'target_audience' => 'beginner',
                'discipline_id' => 2, // PILATES
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // DÍA DEL PADRE - Tercer domingo de junio
            [
                'name' => 'Día del Padre - Power Training',
                'slug' => 'dia-padre-power-training',
                'description' => 'Paquete intensivo para papás que quieren mantenerse en forma',
                'short_description' => '12 clases de cycling de alta intensidad',
                'classes_quantity' => 12,
                'price_soles' => 420.00,
                'original_price_soles' => 520.00,
                'validity_days' => 90,
                'mode_type' => 'presencial',
                'billing_type' => 'one_time',
                'type' => 'temporary',
                'commercial_type' => 'promotion',
                'start_date' => Carbon::create($currentYear, 6, 1),
                'end_date' => Carbon::create($currentYear, 6, 25),
                'is_virtual_access' => false,
                'priority_booking_days' => 4,
                'auto_renewal' => false,
                'is_featured' => true,
                'is_popular' => false,
                'status' => 'active',
                'display_order' => 4,
                'features' => json_encode(['12 clases de cycling', 'Entrenamiento intensivo', 'Plan nutricional básico', 'Regalo especial papá']),
                'restrictions' => json_encode(['Válido en junio', 'Promoción especial papás']),
                'target_audience' => 'advanced',
                'discipline_id' => 1, // CYCLING
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // FIESTAS PATRIAS - 28 de Julio
            [
                'name' => 'Fiestas Patrias - Perú Fuerte',
                'slug' => 'fiestas-patrias-peru-fuerte',
                'description' => 'Celebra la independencia con fuerza y energía peruana',
                'short_description' => '15 clases para celebrar nuestro Perú',
                'classes_quantity' => 15,
                'price_soles' => 480.00,
                'original_price_soles' => 650.00,
                'validity_days' => 75,
                'mode_type' => 'presencial',
                'billing_type' => 'one_time',
                'type' => 'temporary',
                'commercial_type' => 'promotion',
                'start_date' => Carbon::create($currentYear, 7, 15),
                'end_date' => Carbon::create($currentYear, 8, 5),
                'is_virtual_access' => false,
                'priority_booking_days' => 5,
                'auto_renewal' => false,
                'is_featured' => true,
                'is_popular' => true,
                'status' => 'active',
                'display_order' => 5,
                'features' => json_encode(['15 clases mixtas', 'Evento especial 28 de julio', 'Música peruana en clases', 'Merchandising patrio']),
                'restrictions' => json_encode(['Válido julio-agosto', 'Edición limitada Fiestas Patrias']),
                'target_audience' => 'intermediate',
                'discipline_id' => 1, // CYCLING
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // HALLOWEEN - 31 de octubre
            [
                'name' => 'Halloween - Dark Training',
                'slug' => 'halloween-dark-training',
                'description' => 'Entrena en la oscuridad con música temática de Halloween',
                'short_description' => '8 clases con ambiente Halloween',
                'classes_quantity' => 8,
                'price_soles' => 300.00,
                'original_price_soles' => 380.00,
                'validity_days' => 40,
                'mode_type' => 'presencial',
                'billing_type' => 'one_time',
                'type' => 'temporary',
                'commercial_type' => 'promotion',
                'start_date' => Carbon::create($currentYear, 10, 15),
                'end_date' => Carbon::create($currentYear, 11, 5),
                'is_virtual_access' => false,
                'priority_booking_days' => 3,
                'auto_renewal' => false,
                'is_featured' => true,
                'is_popular' => false,
                'status' => 'active',
                'display_order' => 6,
                'features' => json_encode(['8 clases temáticas', 'Ambiente Halloween', 'Música de terror fitness', 'Premios por disfraces']),
                'restrictions' => json_encode(['Válido octubre-noviembre', 'Temática Halloween']),
                'target_audience' => 'intermediate',
                'discipline_id' => 1, // CYCLING
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // CYBER DAYS - 24 al 28 de marzo
            [
                'name' => 'Cyber Days - Mega Pack Digital',
                'slug' => 'cyber-days-mega-pack',
                'description' => 'Aprovecha los Cyber Days con descuentos increíbles',
                'short_description' => '20 clases con 40% de descuento',
                'classes_quantity' => 20,
                'price_soles' => 600.00,
                'original_price_soles' => 1000.00,
                'validity_days' => 120,
                'mode_type' => 'mixto',
                'billing_type' => 'one_time',
                'type' => 'temporary',
                'commercial_type' => 'offer',
                'start_date' => Carbon::create($currentYear, 3, 24),
                'end_date' => Carbon::create($currentYear, 3, 28),
                'is_virtual_access' => true,
                'priority_booking_days' => 7,
                'auto_renewal' => false,
                'is_featured' => true,
                'is_popular' => true,
                'status' => 'active',
                'display_order' => 7,
                'features' => json_encode(['20 clases mixtas', '40% de descuento', 'Acceso virtual incluido', 'Solo por Cyber Days']),
                'restrictions' => json_encode(['Solo del 24-28 marzo', 'Cantidad limitada', 'No acumulable']),
                'target_audience' => 'intermediate',
                'discipline_id' => 1, // CYCLING
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // CYBER WOW ABRIL - 7 al 10 de abril
            [
                'name' => 'Cyber Wow - Power Pack',
                'slug' => 'cyber-wow-abril-power-pack',
                'description' => 'Cyber Wow de abril con ofertas explosivas',
                'short_description' => '16 clases con precio increíble',
                'classes_quantity' => 16,
                'price_soles' => 480.00,
                'original_price_soles' => 720.00,
                'validity_days' => 90,
                'mode_type' => 'presencial',
                'billing_type' => 'one_time',
                'type' => 'temporary',
                'commercial_type' => 'offer',
                'start_date' => Carbon::create($currentYear, 4, 7),
                'end_date' => Carbon::create($currentYear, 4, 10),
                'is_virtual_access' => false,
                'priority_booking_days' => 5,
                'auto_renewal' => false,
                'is_featured' => true,
                'is_popular' => true,
                'status' => 'active',
                'display_order' => 8,
                'features' => json_encode(['16 clases de cycling', '33% de descuento', 'Solo 4 días disponible', 'Cyber Wow exclusivo']),
                'restrictions' => json_encode(['Solo del 7-10 abril', 'Oferta limitada']),
                'target_audience' => 'intermediate',
                'discipline_id' => 1, // CYCLING
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // CYBER WOW JULIO - 14 al 17 de julio
            [
                'name' => 'Cyber Wow Julio - Summer Pack',
                'slug' => 'cyber-wow-julio-summer-pack',
                'description' => 'Prepárate para el verano con ofertas Cyber Wow',
                'short_description' => '18 clases para el cuerpo de verano',
                'classes_quantity' => 18,
                'price_soles' => 540.00,
                'original_price_soles' => 810.00,
                'validity_days' => 100,
                'mode_type' => 'presencial',
                'billing_type' => 'one_time',
                'type' => 'temporary',
                'commercial_type' => 'offer',
                'start_date' => Carbon::create($currentYear, 7, 14),
                'end_date' => Carbon::create($currentYear, 7, 17),
                'is_virtual_access' => false,
                'priority_booking_days' => 5,
                'auto_renewal' => false,
                'is_featured' => true,
                'is_popular' => false,
                'status' => 'active',
                'display_order' => 9,
                'features' => json_encode(['18 clases mixtas', 'Operación bikini', 'Plan nutricional verano', 'Descuento 33%']),
                'restrictions' => json_encode(['Solo del 14-17 julio', 'Edición verano']),
                'target_audience' => 'intermediate',
                'discipline_id' => 2, // PILATES
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // BLACK FRIDAY - Finales de noviembre
            [
                'name' => 'Black Friday - Mega Pack Anual',
                'slug' => 'black-friday-mega-pack-anual',
                'description' => 'La mejor oferta del año en Black Friday',
                'short_description' => '50 clases con descuento histórico',
                'classes_quantity' => 50,
                'price_soles' => 1200.00,
                'original_price_soles' => 2000.00,
                'validity_days' => 365,
                'mode_type' => 'mixto',
                'billing_type' => 'one_time',
                'type' => 'temporary',
                'commercial_type' => 'offer',
                'start_date' => Carbon::create($currentYear, 11, 24),
                'end_date' => Carbon::create($currentYear, 11, 27),
                'is_virtual_access' => true,
                'priority_booking_days' => 10,
                'auto_renewal' => false,
                'is_featured' => true,
                'is_popular' => true,
                'status' => 'active',
                'display_order' => 10,
                'features' => json_encode(['50 clases mixtas', '40% de descuento', 'Acceso virtual incluido', 'Válido 1 año completo', 'Máxima prioridad reservas']),
                'restrictions' => json_encode(['Solo Black Friday', 'Cantidad muy limitada', 'Oferta histórica']),
                'target_audience' => 'advanced',
                'discipline_id' => 1, // CYCLING
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // CYBER MONDAY
            [
                'name' => 'Cyber Monday - Digital Boost',
                'slug' => 'cyber-monday-digital-boost',
                'description' => 'Cyber Monday con acceso digital premium',
                'short_description' => '25 clases con acceso virtual premium',
                'classes_quantity' => 25,
                'price_soles' => 750.00,
                'original_price_soles' => 1100.00,
                'validity_days' => 150,
                'mode_type' => 'virtual',
                'billing_type' => 'one_time',
                'type' => 'temporary',
                'commercial_type' => 'offer',
                'start_date' => Carbon::create($currentYear, 11, 27),
                'end_date' => Carbon::create($currentYear, 11, 27),
                'is_virtual_access' => true,
                'priority_booking_days' => 7,
                'auto_renewal' => false,
                'is_featured' => true,
                'is_popular' => false,
                'status' => 'active',
                'display_order' => 11,
                'features' => json_encode(['25 clases virtuales', 'Acceso premium digital', '32% de descuento', 'Solo un día disponible']),
                'restrictions' => json_encode(['Solo Cyber Monday', 'Modalidad virtual únicamente']),
                'target_audience' => 'intermediate',
                'discipline_id' => 2, // PILATES
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // CYBER WOW NOVIEMBRE - 3 al 6 de noviembre
            [
                'name' => 'Cyber Wow Nov - Pre Black Friday',
                'slug' => 'cyber-wow-noviembre-pre-black',
                'description' => 'Anticípate al Black Friday con Cyber Wow de noviembre',
                'short_description' => '22 clases antes del Black Friday',
                'classes_quantity' => 22,
                'price_soles' => 660.00,
                'original_price_soles' => 990.00,
                'validity_days' => 120,
                'mode_type' => 'presencial',
                'billing_type' => 'one_time',
                'type' => 'temporary',
                'commercial_type' => 'offer',
                'start_date' => Carbon::create($currentYear, 11, 3),
                'end_date' => Carbon::create($currentYear, 11, 6),
                'is_virtual_access' => false,
                'priority_booking_days' => 6,
                'auto_renewal' => false,
                'is_featured' => true,
                'is_popular' => false,
                'status' => 'active',
                'display_order' => 12,
                'features' => json_encode(['22 clases de cycling', 'Pre Black Friday', '33% de descuento', 'Preparación para fin de año']),
                'restrictions' => json_encode(['Solo del 3-6 noviembre', 'Antes del Black Friday']),
                'target_audience' => 'intermediate',
                'discipline_id' => 1, // CYCLING
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // DÍA DEL CÁNCER DE MAMA - 19 de octubre
            [
                'name' => 'Octubre Rosa - Lucha Contra el Cáncer',
                'slug' => 'octubre-rosa-lucha-cancer',
                'description' => 'Mes rosa dedicado a la lucha contra el cáncer de mama',
                'short_description' => '10 clases especiales para la causa rosa',
                'classes_quantity' => 10,
                'price_soles' => 350.00,
                'original_price_soles' => 450.00,
                'validity_days' => 60,
                'mode_type' => 'presencial',
                'billing_type' => 'one_time',
                'type' => 'temporary',
                'commercial_type' => 'promotion',
                'start_date' => Carbon::create($currentYear, 10, 1),
                'end_date' => Carbon::create($currentYear, 10, 31),
                'is_virtual_access' => false,
                'priority_booking_days' => 4,
                'auto_renewal' => false,
                'is_featured' => true,
                'is_popular' => true,
                'status' => 'active',
                'display_order' => 13,
                'features' => json_encode(['10 clases especiales', 'Donación a la causa', 'Ambiente rosa en studio', 'Charlas de prevención']),
                'restrictions' => json_encode(['Solo octubre', 'Parte de donación incluida']),
                'target_audience' => 'beginner',
                'discipline_id' => 2, // PILATES
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // PRIDE MONTH - Junio completo
            [
                'name' => 'Pride Month - Diversity Pack',
                'slug' => 'pride-month-diversity-pack',
                'description' => 'Celebra la diversidad durante todo el mes del orgullo',
                'short_description' => '30 clases para celebrar la diversidad',
                'classes_quantity' => 30,
                'price_soles' => 900.00,
                'original_price_soles' => 1200.00,
                'validity_days' => 90,
                'mode_type' => 'presencial',
                'billing_type' => 'one_time',
                'type' => 'temporary',
                'commercial_type' => 'promotion',
                'start_date' => Carbon::create($currentYear, 6, 1),
                'end_date' => Carbon::create($currentYear, 6, 30),
                'is_virtual_access' => false,
                'priority_booking_days' => 5,
                'auto_renewal' => false,
                'is_featured' => true,
                'is_popular' => true,
                'status' => 'active',
                'display_order' => 14,
                'features' => json_encode(['30 clases mixtas', 'Celebración diversidad', 'Decoración Pride', 'Evento especial Pride']),
                'restrictions' => json_encode(['Solo junio', 'Mes completo Pride']),
                'target_audience' => 'intermediate',
                'discipline_id' => 1, // CYCLING
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        Package::insert($packages);

        // Fin paquetes

        // tipo de bebida

        Typedrink::insert([
            [
                'name' => 'Proteico',
                'image_url' => 'https://i.imgur.com/1234567.png',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Detox',
                'image_url' => 'https://i.imgur.com/1234567.png',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
        // Fin tipo de bebida

        // Sabor de bebida
        Flavordrink::insert([
            [
                'name' => 'Vainilla',
                'image_url' => 'https://i.imgur.com/1234567.png',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Chocolate',
                'image_url' => 'https://i.imgur.com/1234567.png',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Fresa',
                'image_url' => 'https://i.imgur.com/1234567.png',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
        // Fin sabor de bebida

        // base drink
        Basedrink::insert([
            [
                'name' => 'Avena',
                'image_url' => 'https://i.imgur.com/1234567.png',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Almendra',
                'image_url' => 'https://i.imgur.com/1234567.png',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Agua',
                'image_url' => 'https://i.imgur.com/1234567.png',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
        // fin base drink
        // Bebidas

        $drink_one = Drink::create([
            'name' => 'Batido de Vainilla',
            'slug' => 'batido-de-vainilla',
            'description' => 'Delicioso batido de vainilla',

            'image_url' => 'https://i.imgur.com/1234567.png',
            'price' => 10.00,
            'created_at' => now(),
        ]);
        $drink_one->basedrinks()->attach(1);
        $drink_one->typedrinks()->attach(1);
        $drink_one->flavordrinks()->attach(1);
        $drink_one->save();

        $drink_two = Drink::create([
            'name' => 'Batido de Chocolate',
            'slug' => 'batido-de-chocolate',
            'description' => 'Delicioso batido de chocolate',

            'image_url' => 'https://i.imgur.com/1234567.png',
            'price' => 12.00,
            'created_at' => now(),
        ]);
        $drink_two->basedrinks()->attach(2);
        $drink_two->typedrinks()->attach(1);
        $drink_two->flavordrinks()->attach(2);
        $drink_two->save();
        // Fin bebidas

    }
}
