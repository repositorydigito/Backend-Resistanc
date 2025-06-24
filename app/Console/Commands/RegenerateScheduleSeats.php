<?php

namespace App\Console\Commands;

use App\Models\ClassSchedule;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RegenerateScheduleSeats extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'schedules:regenerate-seats 
                            {--schedule-id= : ID específico del horario a regenerar}
                            {--studio-id= : Regenerar todos los horarios de una sala específica}
                            {--all : Regenerar todos los horarios}
                            {--force : Forzar regeneración sin confirmación}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Regenerar asientos de horarios de clase';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $scheduleId = $this->option('schedule-id');
        $studioId = $this->option('studio-id');
        $all = $this->option('all');
        $force = $this->option('force');

        // Validar opciones
        if (!$scheduleId && !$studioId && !$all) {
            $this->error('Debes especificar --schedule-id, --studio-id, o --all');
            return 1;
        }

        // Construir query
        $query = ClassSchedule::query();

        if ($scheduleId) {
            $query->where('id', $scheduleId);
            $this->info("Regenerando asientos para horario ID: {$scheduleId}");
        } elseif ($studioId) {
            $query->where('studio_id', $studioId);
            $this->info("Regenerando asientos para todos los horarios de sala ID: {$studioId}");
        } elseif ($all) {
            $this->info("Regenerando asientos para TODOS los horarios");
        }

        $schedules = $query->with('studio')->get();

        if ($schedules->isEmpty()) {
            $this->warn('No se encontraron horarios para regenerar');
            return 0;
        }

        $this->info("Se encontraron {$schedules->count()} horarios para procesar");

        // Confirmar si no es forzado
        if (!$force && !$this->confirm('¿Estás seguro de que quieres regenerar los asientos?')) {
            $this->info('Operación cancelada');
            return 0;
        }

        $bar = $this->output->createProgressBar($schedules->count());
        $bar->start();

        $successCount = 0;
        $errorCount = 0;
        $totalSeatsCreated = 0;

        foreach ($schedules as $schedule) {
            try {
                $createdCount = $schedule->regenerateSeats();
                $totalSeatsCreated += $createdCount;
                $successCount++;

                Log::info("Asientos regenerados exitosamente", [
                    'schedule_id' => $schedule->id,
                    'studio_id' => $schedule->studio_id,
                    'seats_created' => $createdCount
                ]);

            } catch (\Exception $e) {
                $errorCount++;
                $this->error("\nError regenerando horario {$schedule->id}: " . $e->getMessage());

                Log::error("Error regenerando asientos", [
                    'schedule_id' => $schedule->id,
                    'error' => $e->getMessage()
                ]);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Resumen
        $this->info("✅ Proceso completado:");
        $this->info("   • Horarios procesados: {$schedules->count()}");
        $this->info("   • Horarios exitosos: {$successCount}");
        $this->info("   • Horarios con errores: {$errorCount}");
        $this->info("   • Total asientos creados: {$totalSeatsCreated}");

        if ($errorCount > 0) {
            return 1; // Código de error
        }

        return 0; // Éxito
    }
} 