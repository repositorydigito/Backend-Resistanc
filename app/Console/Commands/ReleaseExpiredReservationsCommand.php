<?php

namespace App\Console\Commands;

use App\Models\ClassScheduleSeat;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReleaseExpiredReservationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reservations:release-expired {--dry-run : Solo mostrar qué se liberaría sin hacer cambios}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Libera automáticamente las reservas de asientos expiradas';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');

        $this->info('🔍 Buscando reservas expiradas...');

        // Buscar reservas expiradas
        $expiredReservations = ClassScheduleSeat::where('status', 'reserved')
            ->where('expires_at', '<', now())
            ->with(['user', 'userPackage.package', 'classSchedule.class', 'seat'])
            ->get();

        $this->info("📦 Encontradas {$expiredReservations->count()} reservas expiradas");

        if ($expiredReservations->isEmpty()) {
            $this->info('✅ No hay reservas expiradas para liberar');
            return 0;
        }

        $releasedCount = 0;
        $errors = [];

        foreach ($expiredReservations as $reservation) {
            $this->line("Reserva ID: {$reservation->id}");
            $this->line("  Horario: {$reservation->classSchedule->class->name} ({$reservation->classSchedule->scheduled_date})");
            $this->line("  Asiento: {$reservation->seat->row}.{$reservation->seat->column}");
            
            if ($reservation->user) {
                $this->line("  Usuario: {$reservation->user->name} (ID: {$reservation->user->id})");
            }
            
            if ($reservation->userPackage) {
                $this->line("  Paquete: {$reservation->userPackage->package_code} ({$reservation->userPackage->package->name})");
                $this->line("    Clases restantes antes: {$reservation->userPackage->remaining_classes}");
            }
            
            $this->line("  Expiró: {$reservation->expires_at}");

            if (!$dryRun) {
                try {
                    DB::transaction(function () use ($reservation) {
                        // Si tenía un paquete asignado, devolver la clase
                        if ($reservation->user_package_id) {
                            $userPackage = $reservation->userPackage;
                            if ($userPackage && $userPackage->user_id === $reservation->user_id) {
                                $userPackage->refundClasses(1);
                                $this->line("    ✅ Clase devuelta al paquete. Nuevas clases restantes: {$userPackage->remaining_classes}");
                            }
                        }

                        // Liberar el asiento
                        $reservation->update([
                            'user_id' => null,
                            'status' => 'available',
                            'reserved_at' => null,
                            'expires_at' => null,
                            'user_package_id' => null
                        ]);
                    });
                    
                    $this->info("  ✅ Reserva liberada exitosamente");
                    $releasedCount++;
                } catch (\Exception $e) {
                    $errors[] = "Error liberando reserva {$reservation->id}: " . $e->getMessage();
                    $this->error("  ❌ Error: " . $e->getMessage());
                }
            } else {
                $this->info("  🔄 Se liberaría esta reserva");
                $releasedCount++;
            }
        }

        $this->newLine();
        
        if ($dryRun) {
            $this->info("🔍 Modo dry-run: Se liberarían {$releasedCount} reservas expiradas");
        } else {
            $this->info("✅ Proceso completado: {$releasedCount} reservas liberadas");
        }
        
        if (!empty($errors)) {
            $this->error("❌ Errores encontrados:");
            foreach ($errors as $error) {
                $this->error("  - {$error}");
            }
        }

        return 0;
    }
} 