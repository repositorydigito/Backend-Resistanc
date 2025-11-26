<?php

namespace App\Console\Commands;

use App\Models\ClassSchedule;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class GenerateScheduleSeats extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'schedules:generate-seats 
                            {--schedule-id= : ID específico del horario}
                            {--studio-id= : Solo horarios de un estudio específico}
                            {--date= : Solo horarios de una fecha específica (YYYY-MM-DD)}
                            {--dry-run : Mostrar qué se haría sin ejecutar}
                            {--force : Regenerar asientos incluso si ya existen}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generar asientos automáticamente para horarios de clases';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🪑 Generando asientos para horarios de clases...');
        
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');
        $scheduleId = $this->option('schedule-id');
        $studioId = $this->option('studio-id');
        $date = $this->option('date');

        // Construir query
        $query = ClassSchedule::with(['studio', 'class']);

        if ($scheduleId) {
            $query->where('id', $scheduleId);
        }

        if ($studioId) {
            $query->where('studio_id', $studioId);
        }

        if ($date) {
            $query->whereDate('scheduled_date', $date);
        }

        // Solo horarios futuros por defecto
        if (!$scheduleId && !$date) {
            $query->where('scheduled_date', '>=', now());
        }

        $schedules = $query->get();

        if ($schedules->isEmpty()) {
            $this->warn('⚠️  No se encontraron horarios que coincidan con los criterios.');
            return self::SUCCESS;
        }

        $this->info("📋 Se encontraron {$schedules->count()} horarios para procesar:");

        // Mostrar tabla de horarios
        $tableData = [];
        foreach ($schedules as $schedule) {
            $existingSeats = $schedule->seatAssignments()->count();
            // ✅ Mostrar TODOS los asientos (activos e inactivos) del estudio
            $studioSeats = $schedule->studio?->seats()->count() ?? 0;
            
            $tableData[] = [
                'ID' => $schedule->id,
                'Clase' => $schedule->class->name ?? 'N/A',
                'Estudio' => $schedule->studio->name ?? 'N/A',
                'Fecha' => $schedule->scheduled_date->format('d/m/Y'),
                'Hora' => $schedule->start_time,
                'Asientos Existentes' => $existingSeats,
                'Asientos Estudio' => $studioSeats,
                'Estado' => $existingSeats > 0 ? '✅ Tiene asientos' : '❌ Sin asientos',
            ];
        }

        $this->table([
            'ID', 'Clase', 'Estudio', 'Fecha', 'Hora', 'Asientos Existentes', 'Asientos Estudio', 'Estado'
        ], $tableData);

        if ($dryRun) {
            $this->info('🔍 Modo DRY-RUN: No se generaron asientos realmente.');
            return self::SUCCESS;
        }

        if (!$force && !$this->confirm('¿Proceder con la generación de asientos?', true)) {
            $this->info('❌ Operación cancelada.');
            return self::SUCCESS;
        }

        // Procesar cada horario
        $processed = 0;
        $skipped = 0;
        $errors = 0;
        $totalSeatsGenerated = 0;

        foreach ($schedules as $schedule) {
            try {
                $existingSeats = $schedule->seatAssignments()->count();
                
                // Saltar si ya tiene asientos y no es forzado
                if ($existingSeats > 0 && !$force) {
                    $this->line("⏭️  Saltando horario {$schedule->id}: Ya tiene {$existingSeats} asientos");
                    $skipped++;
                    continue;
                }

                // Si es forzado y ya tiene asientos, eliminar primero
                if ($force && $existingSeats > 0) {
                    $this->line("🗑️  Eliminando {$existingSeats} asientos existentes del horario {$schedule->id}");
                    $schedule->seatAssignments()->delete();
                }

                // Generar asientos
                $seatsGenerated = $schedule->generateSeatsAutomatically();
                
                if ($seatsGenerated > 0) {
                    $this->line("✅ Horario {$schedule->id}: {$seatsGenerated} asientos generados");
                    $totalSeatsGenerated += $seatsGenerated;
                } else {
                    $this->line("⚠️  Horario {$schedule->id}: No se generaron asientos (estudio sin configuración)");
                }
                
                $processed++;

            } catch (\Exception $e) {
                $this->error("❌ Error en horario {$schedule->id}: " . $e->getMessage());
                $errors++;
                
                Log::error('Error generando asientos para horario', [
                    'schedule_id' => $schedule->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }

        // Resumen final
        $this->info("\n📊 Resumen:");
        $this->info("✅ Horarios procesados: {$processed}");
        $this->info("⏭️  Horarios saltados: {$skipped}");
        $this->info("❌ Errores: {$errors}");
        $this->info("🪑 Total asientos generados: {$totalSeatsGenerated}");

        if ($totalSeatsGenerated > 0) {
            $this->info("🎉 Proceso completado exitosamente.");
        }

        return self::SUCCESS;
    }
}
