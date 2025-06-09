<?php

namespace App\Console\Commands;

use App\Models\ClassScheduleSeat;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ReleaseExpiredSeatReservations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seats:release-expired 
                            {--dry-run : Show what would be released without actually doing it}
                            {--minutes=0 : Additional minutes to consider as expired}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Release expired seat reservations automatically';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $additionalMinutes = (int) $this->option('minutes');
        
        $this->info('🔍 Buscando reservas de asientos expiradas...');
        
        // Calcular tiempo de expiración
        $expiredBefore = now()->subMinutes($additionalMinutes);
        
        // Buscar reservas expiradas
        $expiredReservations = ClassScheduleSeat::where('status', 'reserved')
            ->where('expires_at', '<', $expiredBefore)
            ->with(['classSchedule.class', 'seat', 'user'])
            ->get();

        if ($expiredReservations->isEmpty()) {
            $this->info('✅ No se encontraron reservas expiradas.');
            return self::SUCCESS;
        }

        $this->warn("⚠️  Se encontraron {$expiredReservations->count()} reservas expiradas:");
        
        // Mostrar tabla con las reservas expiradas
        $tableData = [];
        foreach ($expiredReservations as $reservation) {
            $tableData[] = [
                'ID' => $reservation->id,
                'Clase' => $reservation->classSchedule->class->name ?? 'N/A',
                'Fecha' => $reservation->classSchedule->scheduled_date->format('d/m/Y'),
                'Hora' => $reservation->classSchedule->start_time,
                'Asiento' => $reservation->seat->seat_number ?? 'N/A',
                'Usuario' => $reservation->user->name ?? 'Sin usuario',
                'Reservado' => $reservation->reserved_at?->format('d/m/Y H:i'),
                'Expira' => $reservation->expires_at?->format('d/m/Y H:i'),
                'Expiró hace' => $reservation->expires_at?->diffForHumans(),
            ];
        }

        $this->table([
            'ID', 'Clase', 'Fecha', 'Hora', 'Asiento', 'Usuario', 'Reservado', 'Expira', 'Expiró hace'
        ], $tableData);

        if ($dryRun) {
            $this->info('🔍 Modo DRY-RUN: No se liberaron las reservas.');
            $this->info('💡 Ejecuta sin --dry-run para liberar realmente las reservas.');
            return self::SUCCESS;
        }

        // Confirmar antes de proceder
        if (!$this->confirm('¿Deseas liberar estas reservas expiradas?', true)) {
            $this->info('❌ Operación cancelada.');
            return self::SUCCESS;
        }

        // Liberar reservas
        $releasedCount = 0;
        $errors = [];

        foreach ($expiredReservations as $reservation) {
            try {
                $reservation->release();
                $releasedCount++;
                
                $this->line("✅ Liberado: Asiento {$reservation->seat->seat_number} - {$reservation->classSchedule->class->name}");
                
                // Log para auditoría
                Log::info('Reserva de asiento liberada automáticamente', [
                    'reservation_id' => $reservation->id,
                    'class_schedule_id' => $reservation->class_schedules_id,
                    'seat_id' => $reservation->seats_id,
                    'user_id' => $reservation->user_id,
                    'expired_at' => $reservation->expires_at,
                    'released_at' => now(),
                ]);
                
            } catch (\Exception $e) {
                $errors[] = "Error liberando reserva {$reservation->id}: " . $e->getMessage();
                $this->error("❌ Error liberando reserva {$reservation->id}: " . $e->getMessage());
                
                Log::error('Error liberando reserva de asiento', [
                    'reservation_id' => $reservation->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Resumen final
        $this->info("\n📊 Resumen:");
        $this->info("✅ Reservas liberadas: {$releasedCount}");
        
        if (!empty($errors)) {
            $this->error("❌ Errores: " . count($errors));
            foreach ($errors as $error) {
                $this->error("   • {$error}");
            }
        }

        if ($releasedCount > 0) {
            $this->info("🎉 Proceso completado exitosamente.");
        }

        return self::SUCCESS;
    }
}
