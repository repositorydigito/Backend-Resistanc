<?php

namespace Database\Seeders;

use App\Models\ClassSchedule;
use App\Models\ClassScheduleSeat;
use App\Models\Seat;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class ClassScheduleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $this->command->info('🌱 Seeders de Horarios');
        // horarios

        $baseDate = Carbon::parse('next monday'); // Lunes de la siguiente semana


        // Horarios por 2 semanas
        $mapping = [
            'Monday' => 1,
            'Wednesday' => 3,
            'Friday' => 5,
        ];

        $classTemplates = [
            ['start' => '08:00:00', 'end' => '09:00:00', 'class_id' => 1],
            ['start' => '18:00:00', 'end' => '19:00:00', 'class_id' => 2],
        ];

        $classSchedule = [];
        $startDate = Carbon::today();
        $endDate = $startDate->copy()->addWeeks(2);

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $dayName = $date->format('l'); // Ej: Monday

            if (!array_key_exists($dayName, $mapping)) continue;

            foreach ($classTemplates as $template) {
                $classSchedule[] = [
                    'class_id' => $template['class_id'],
                    'instructor_id' => 1, // ajusta según lógica
                    'studio_id' => 1,     // ajusta según lógica
                    'scheduled_date' => $date->format('Y-m-d'),
                    'start_time' => $template['start'],
                    'end_time' => $template['end'],
                    'max_capacity' => 10,
                    'available_spots' => 10,
                    'booked_spots' => 0,
                    'waitlist_spots' => 0,
                    'status' => 'scheduled',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        ClassSchedule::insert($classSchedule);

        // Crear asientos relacionados
        $allSchedules = ClassSchedule::whereDate('scheduled_date', '>=', Carbon::today())->get();

        foreach ($allSchedules as $schedule) {
            $studio = $schedule->studio;
            if (!$studio) continue;

            $rows = $studio->row;
            $columns = $studio->column;

            $counter = 1; // ← contador incremental
            for ($r = 1; $r <= $rows; $r++) {
                for ($c = 1; $c <= $columns; $c++) {
                    $seat = Seat::firstOrCreate(
                        [
                            'studio_id' => $studio->id,
                            'row' => $r,
                            'column' => $c,
                        ],
                        [

                            'is_active' => true,
                        ]
                    );

                    $code = 'SCH-' . $schedule->id . '-SEAT-' . $seat->id;

                    ClassScheduleSeat::create([
                        'class_schedules_id' => $schedule->id,
                        'seats_id' => $seat->id,
                        'status' => 'available',
                        'code' => $code,
                    ]);
                }
            }
        }
    }
}
