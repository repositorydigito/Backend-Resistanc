<?php

namespace App\Jobs;

use App\Models\ClassScheduleSeat;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ReleaseExpiredSeatReservationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('🔍 Iniciando liberación automática de reservas expiradas');

        // Buscar reservas expiradas
        $expiredReservations = ClassScheduleSeat::where('status', 'reserved')
            ->where('expires_at', '<', now())
            ->with(['classSchedule.class', 'seat', 'user'])
            ->get();

        if ($expiredReservations->isEmpty()) {
            Log::info('✅ No se encontraron reservas expiradas');
            return;
        }

        $releasedCount = 0;
        $errors = [];

        foreach ($expiredReservations as $reservation) {
            try {
                $reservation->release();
                $releasedCount++;
                
                Log::info('Reserva de asiento liberada automáticamente', [
                    'reservation_id' => $reservation->id,
                    'class_schedule_id' => $reservation->class_schedules_id,
                    'seat_id' => $reservation->seats_id,
                    'user_id' => $reservation->user_id,
                    'user_name' => $reservation->user?->name,
                    'class_name' => $reservation->classSchedule?->class?->name,
                    'seat_number' => $reservation->seat?->seat_number,
                    'expired_at' => $reservation->expires_at,
                    'released_at' => now(),
                ]);
                
            } catch (\Exception $e) {
                $errors[] = [
                    'reservation_id' => $reservation->id,
                    'error' => $e->getMessage(),
                ];
                
                Log::error('Error liberando reserva de asiento', [
                    'reservation_id' => $reservation->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        // Log del resumen
        Log::info('📊 Resumen de liberación de reservas expiradas', [
            'total_found' => $expiredReservations->count(),
            'released_count' => $releasedCount,
            'errors_count' => count($errors),
            'errors' => $errors,
        ]);

        if ($releasedCount > 0) {
            Log::info("🎉 Se liberaron {$releasedCount} reservas expiradas exitosamente");
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('❌ Falló el job de liberación de reservas expiradas', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
