<?php

namespace App\Console\Commands;

use App\Jobs\ProcessWaitingListAfterClassStartJob;
use App\Models\ClassSchedule;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessStartedClassesWaitingListCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'waiting-list:process-started-classes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Procesa las clases que ya empezaron y actualiza el estado de usuarios en waiting list';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Buscando clases que ya empezaron...');

        try {
            // Buscar clases que ya empezaron (start_time pasado) y que no están canceladas
            $now = now();
            
            $startedClasses = ClassSchedule::where('status', '!=', 'cancelled')
                ->where(function($query) use ($now) {
                    // Construir la fecha y hora de inicio de la clase
                    $query->whereRaw("CONCAT(scheduled_date, ' ', start_time) <= ?", [$now->toDateTimeString()]);
                })
                ->whereHas('waitingClasses', function($q) {
                    // Solo procesar clases que tienen usuarios en waiting
                    $q->where('status', 'waiting');
                })
                ->with(['class'])
                ->get();

            if ($startedClasses->isEmpty()) {
                $this->info('✅ No se encontraron clases que hayan empezado con usuarios en waiting');
                return Command::SUCCESS;
            }

            $this->info("📋 Se encontraron {$startedClasses->count()} clases que ya empezaron con usuarios en waiting");

            $processedCount = 0;
            $errors = [];

            foreach ($startedClasses as $classSchedule) {
                try {
                    // Despachar job para procesar esta clase
                    ProcessWaitingListAfterClassStartJob::dispatch($classSchedule->id);
                    $processedCount++;

                    $this->line("  ✓ Despachado job para clase ID: {$classSchedule->id} - {$classSchedule->class->name ?? 'N/A'}");

                } catch (\Exception $e) {
                    $errors[] = [
                        'class_schedule_id' => $classSchedule->id,
                        'error' => $e->getMessage(),
                    ];

                    $this->error("  ✗ Error al procesar clase ID: {$classSchedule->id} - {$e->getMessage()}");
                    
                    Log::error('Error procesando clase en waiting list', [
                        'class_schedule_id' => $classSchedule->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            }

            $this->info("✅ Se despacharon {$processedCount} jobs para procesar clases");
            
            if (!empty($errors)) {
                $this->warn("⚠️  Se encontraron " . count($errors) . " errores durante el procesamiento");
            }

            return Command::SUCCESS;

        } catch (\Throwable $e) {
            $this->error("❌ Error fatal: {$e->getMessage()}");
            
            Log::error('Error fatal en comando de procesamiento de waiting list', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }
}

