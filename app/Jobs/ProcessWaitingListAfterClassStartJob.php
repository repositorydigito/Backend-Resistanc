<?php

namespace App\Jobs;

use App\Models\ClassSchedule;
use App\Models\WaitingClass;
use App\Models\ClassScheduleSeat;
use App\Services\PackageValidationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ProcessWaitingListAfterClassStartJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $classScheduleId
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('🔍 Iniciando procesamiento de lista de espera después del inicio de clase', [
            'class_schedule_id' => $this->classScheduleId
        ]);

        try {
            $classSchedule = ClassSchedule::with(['class.discipline'])
                ->find($this->classScheduleId);

            if (!$classSchedule) {
                Log::warning('❌ Horario de clase no encontrado', [
                    'class_schedule_id' => $this->classScheduleId
                ]);
                return;
            }

            // Calcular fecha y hora de inicio de la clase
            $scheduledDate = $classSchedule->scheduled_date instanceof \Carbon\Carbon
                ? $classSchedule->scheduled_date->format('Y-m-d')
                : $classSchedule->scheduled_date;
            
            $startDateTime = \Carbon\Carbon::parse($scheduledDate . ' ' . $classSchedule->start_time);
            
            // Solo procesar si la clase ya empezó
            if ($startDateTime->isFuture()) {
                Log::info('⏰ La clase aún no ha empezado, no se procesa', [
                    'class_schedule_id' => $this->classScheduleId,
                    'start_datetime' => $startDateTime->toDateTimeString()
                ]);
                return;
            }

            // Obtener todos los usuarios en waiting para esta clase
            $waitingUsers = WaitingClass::where('class_schedules_id', $this->classScheduleId)
                ->where('status', 'waiting') // Solo procesar los que siguen en waiting
                ->with(['user', 'userPackage'])
                ->orderBy('created_at', 'asc')
                ->get();

            if ($waitingUsers->isEmpty()) {
                Log::info('✅ No hay usuarios en waiting para procesar', [
                    'class_schedule_id' => $this->classScheduleId
                ]);
                return;
            }

            Log::info('📋 Usuarios en waiting encontrados', [
                'class_schedule_id' => $this->classScheduleId,
                'waiting_count' => $waitingUsers->count()
            ]);

            $processedCount = 0;
            $expiredCount = 0;
            $errors = [];

            foreach ($waitingUsers as $waitingUser) {
                try {
                    // Verificar si el usuario tiene un asiento asignado (reservado u ocupado)
                    $hasSeat = ClassScheduleSeat::where('class_schedules_id', $this->classScheduleId)
                        ->where(function($q) use ($waitingUser) {
                            $q->where('user_id', $waitingUser->user_id)
                              ->orWhere('user_waiting_id', $waitingUser->user_id);
                        })
                        ->whereIn('status', ['reserved', 'occupied'])
                        ->exists();

                    if ($hasSeat) {
                        // El usuario tiene un asiento asignado, no hacer nada
                        // Las clases ya se consumieron al asignar el asiento
                        Log::info('✅ Usuario tiene asiento asignado, no se procesa', [
                            'waiting_id' => $waitingUser->id,
                            'user_id' => $waitingUser->user_id,
                            'user_name' => $waitingUser->user?->name
                        ]);
                        continue;
                    }

                    // El usuario NO tiene asiento asignado y la clase ya empezó
                    // Como NO se consumieron clases al agregar a waiting, no hay nada que regresar
                    // Pero podemos marcar la entrada como expirada o cancelada
                    
                    // Si el usuario tenía un user_package_id asignado, solo era una referencia
                    // No se consumieron clases, así que no hay nada que regresar
                    
                    // Actualizar estado de waiting a expired
                    $waitingUser->update([
                        'status' => 'expired'
                    ]);
                    
                    $expiredCount++;
                    
                    Log::info('⏰ Entrada de waiting marcada como expirada (clases no consumidas)', [
                        'waiting_id' => $waitingUser->id,
                        'user_id' => $waitingUser->user_id,
                        'user_name' => $waitingUser->user?->name,
                        'user_package_id' => $waitingUser->user_package_id,
                        'note' => 'No se consumieron clases al agregar a waiting, por lo tanto no hay clases que regresar'
                    ]);
                    
                } catch (\Exception $e) {
                    $errors[] = [
                        'waiting_id' => $waitingUser->id,
                        'user_id' => $waitingUser->user_id,
                        'error' => $e->getMessage(),
                    ];
                    
                    Log::error('❌ Error procesando usuario en waiting', [
                        'waiting_id' => $waitingUser->id,
                        'user_id' => $waitingUser->user_id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            }

            // Log del resumen
            Log::info('📊 Resumen de procesamiento de lista de espera', [
                'class_schedule_id' => $this->classScheduleId,
                'total_waiting_users' => $waitingUsers->count(),
                'processed_count' => $processedCount,
                'expired_count' => $expiredCount,
                'errors_count' => count($errors),
                'errors' => $errors,
            ]);

            if ($expiredCount > 0) {
                Log::info("🎉 Se procesaron {$expiredCount} entradas de waiting como expiradas");
            }

        } catch (\Throwable $e) {
            Log::error('❌ Error fatal procesando lista de espera', [
                'class_schedule_id' => $this->classScheduleId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('❌ Falló el job de procesamiento de lista de espera', [
            'class_schedule_id' => $this->classScheduleId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}

