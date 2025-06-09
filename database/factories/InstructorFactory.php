<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Instructor;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Instructor>
 */
class InstructorFactory extends Factory
{
    protected $model = Instructor::class;

    public function definition(): array
    {
        $specialties = [
            [1], // cycling
            [2], // reformer
            [3], // pilates mat
            [1, 2], // cycling + reformer
            [2, 3], // reformer + mat
            [3, 4], // mat + yoga
            [1, 2, 3], // cycling + reformer + mat
        ];

        $certifications = [
            'cycling' => ['Spinning Certified', 'Indoor Cycling Instructor', 'Schwinn Certified'],
            'reformer' => ['Pilates Reformer Certified', 'BASI Pilates', 'Romana\'s Pilates'],
            'mat' => ['Mat Pilates Certified', 'Yoga Alliance RYT-200', 'Pilates Method Alliance'],
            'general' => ['CPR Certified', 'First Aid Certified', 'Fitness Nutrition Specialist'],
        ];

        $experienceYears = $this->faker->numberBetween(1, 15);
        $totalClasses = $experienceYears * $this->faker->numberBetween(100, 500);
        $rating = $this->faker->randomFloat(2, 4.0, 5.0);

        $firstName = $this->faker->firstName();
        $lastName = $this->faker->lastName();
        $name = $firstName . ' ' . $lastName;
        $email = strtolower($firstName . '.' . $lastName . '@rsistanc.com');

        $selectedSpecialties = $this->faker->randomElement($specialties);
        $selectedCertifications = [];

        foreach ($selectedSpecialties as $specialty) {
            switch ($specialty) {
                case 1:
                    $selectedCertifications = array_merge($selectedCertifications, $this->faker->randomElements($certifications['cycling'], 1));
                    break;
                case 2:
                    $selectedCertifications = array_merge($selectedCertifications, $this->faker->randomElements($certifications['reformer'], 1));
                    break;
                case 3:
                    $selectedCertifications = array_merge($selectedCertifications, $this->faker->randomElements($certifications['mat'], 1));
                    break;
            }
        }

        $selectedCertifications = array_merge($selectedCertifications, $this->faker->randomElements($certifications['general'], 1));

        // ✅ CORREGIR: Generar horarios en el formato que espera el Repeater
        $availabilitySchedule = $this->generateAvailabilityForRepeater();

        return [
            'user_id' => $this->faker->boolean(70) ? User::factory() : null,
            'name' => $name,
            'email' => $email,
            'phone' => '+51 9' . $this->faker->numerify('########'),
            'specialties' => $selectedSpecialties, // ✅ Como array, el modelo lo convertirá
            'bio' => $this->generateBio($name, $experienceYears, $selectedSpecialties),
            'certifications' => $selectedCertifications, // ✅ Como array, el modelo lo convertirá
            'profile_image' => '/images/instructors/' . strtolower(str_replace(' ', '_', $name)) . '.jpg',
            'instagram_handle' => '@' . strtolower(str_replace(' ', '', $name)) . '_rsistanc',
            'is_head_coach' => $this->faker->boolean(15),
            'experience_years' => $experienceYears,
            'rating_average' => $rating,
            'total_classes_taught' => $totalClasses,
            'hire_date' => $this->faker->dateTimeBetween('-' . $experienceYears . ' years', '-6 months'),
            'hourly_rate_soles' => $this->faker->randomFloat(2, 80.00, 200.00),
            'status' => $this->faker->randomElement(['active', 'active', 'active', 'active', 'inactive', 'on_leave']),
            'availability_schedule' => $availabilitySchedule, // ✅ Nuevo formato
            'created_at' => $this->faker->dateTimeBetween('-2 years', '-6 months'),
            'updated_at' => $this->faker->dateTimeBetween('-6 months', 'now'),
        ];
    }

    /**
     * ✅ NUEVO: Generar horarios en formato compatible con Repeater
     */
    private function generateAvailabilityForRepeater(): array
    {
        $schedules = [];
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

        // Generar 3-5 días de trabajo aleatorios
        $workingDays = $this->faker->randomElements($days, $this->faker->numberBetween(3, 5));

        foreach ($workingDays as $day) {
            // Cada día puede tener 1-2 bloques de horarios
            $shifts = $this->faker->numberBetween(1, 2);

            for ($i = 0; $i < $shifts; $i++) {
                if ($i === 0) {
                    // Primer turno - mañana/tarde
                    $startHour = $this->faker->numberBetween(6, 10);
                    $endHour = $startHour + $this->faker->numberBetween(3, 6);
                } else {
                    // Segundo turno - tarde/noche
                    $startHour = $this->faker->numberBetween(15, 18);
                    $endHour = $startHour + $this->faker->numberBetween(2, 4);
                }

                $schedules[] = [
                    'day' => $day,
                    'start_time' => sprintf('%02d:00', $startHour),
                    'end_time' => sprintf('%02d:00', min($endHour, 22)), // Máximo hasta las 22:00
                ];
            }
        }

        return $schedules;
    }

    // ✅ ELIMINAR o SIMPLIFICAR este método ya que no lo usamos
    private function generateDaySchedule(): array
    {
        // Este método ya no se usa con el nuevo formato
        return [];
    }

    /**
     * Generate a bio for the instructor.
     */
    private function generateBio(string $name, int $experience, array $specialties): string
    {
        $disciplineNames = [
            1 => 'cycling',
            2 => 'reformer',
            3 => 'pilates mat',
            4 => 'yoga',
        ];

        $specialtyNames = array_map(fn($id) => $disciplineNames[$id] ?? 'fitness', $specialties);
        $specialtyText = implode(', ', $specialtyNames);

        $bios = [
            "Instructor certificado con {$experience} años de experiencia en {$specialtyText}. Apasionado por ayudar a los estudiantes a alcanzar sus objetivos de fitness y bienestar.",
            "Con {$experience} años de experiencia, {$name} se especializa en {$specialtyText}. Su enfoque se centra en la técnica correcta y la motivación constante.",
            "Instructor dedicado con {$experience} años transformando vidas a través de {$specialtyText}. Cree firmemente en el poder del ejercicio para mejorar la calidad de vida.",
            "Especialista en {$specialtyText} con {$experience} años de experiencia. Su filosofía se basa en crear un ambiente inclusivo y desafiante para todos los niveles.",
        ];

        return $this->faker->randomElement($bios);
    }

    /**
     * Indicate that the instructor is active.
     */
    public function active(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'active',
        ]);
    }

    /**
     * Indicate that the instructor is a head coach.
     */
    public function headCoach(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_head_coach' => true,
            'experience_years' => $this->faker->numberBetween(5, 15),
            'rating_average' => $this->faker->randomFloat(2, 4.5, 5.0),
            'hourly_rate_soles' => $this->faker->randomFloat(2, 150.00, 250.00),
        ]);
    }

    /**
     * Create a cycling instructor.
     */
    public function cycling(): static
    {
        return $this->state(fn(array $attributes) => [
            'specialties' => json_encode([1]), // cycling
            'certifications' => json_encode(['Spinning Certified', 'Indoor Cycling Instructor', 'CPR Certified']),
        ]);
    }

    /**
     * Create a reformer instructor.
     */
    public function reformer(): static
    {
        return $this->state(fn(array $attributes) => [
            'specialties' => json_encode([2]), // reformer
            'certifications' => json_encode(['Pilates Reformer Certified', 'BASI Pilates', 'First Aid Certified']),
        ]);
    }
}
