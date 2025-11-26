<?php

namespace App\Console\Commands;

use App\Models\Studio;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ReorderStudioSeatNumbers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'studio:reorder-seats 
                            {--studio-id= : ID específico de la sala para reordenar}
                            {--all : Reordenar todas las salas}
                            {--dry-run : Mostrar qué cambios se harían sin aplicarlos}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reordenar números de asientos en salas para mantener secuencia correlativa';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $studioId = $this->option('studio-id');
        $all = $this->option('all');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('🔍 MODO SIMULACIÓN - No se aplicarán cambios');
        }

        if ($studioId) {
            $this->reorderSpecificStudio($studioId, $dryRun);
        } elseif ($all) {
            $this->reorderAllStudios($dryRun);
        } else {
            $this->error('Debe especificar --studio-id=X o --all');
            $this->info('Ejemplos:');
            $this->info('  php artisan studio:reorder-seats --studio-id=1');
            $this->info('  php artisan studio:reorder-seats --all');
            $this->info('  php artisan studio:reorder-seats --all --dry-run');
            return 1;
        }

        return 0;
    }

    private function reorderSpecificStudio(int $studioId, bool $dryRun): void
    {
        $studio = Studio::find($studioId);

        if (!$studio) {
            $this->error("Sala con ID {$studioId} no encontrada");
            return;
        }

        $this->info("🎯 Reordenando asientos para sala: {$studio->name} (ID: {$studio->id})");

        $seats = $studio->seats()->orderBy('row')->orderBy('column')->get();
        
        if ($seats->isEmpty()) {
            $this->warn("La sala no tiene asientos");
            return;
        }

        $this->showCurrentSeats($seats);

        if ($dryRun) {
            $this->showReorderingPreview($seats);
        } else {
            $studio->reorderSeatNumbers();
            $this->info("✅ Números de asientos reordenados para sala: {$studio->name}");
            
            // Mostrar resultado
            $updatedSeats = $studio->seats()->orderBy('row')->orderBy('column')->get();
            $this->showCurrentSeats($updatedSeats, 'DESPUÉS');
        }
    }

    private function reorderAllStudios(bool $dryRun): void
    {
        $studios = Studio::with('seats')->get();

        if ($studios->isEmpty()) {
            $this->warn("No hay salas en el sistema");
            return;
        }

        $this->info("🎯 Reordenando asientos para todas las salas ({$studios->count()} salas)");

        $totalSeats = 0;
        $updatedStudios = 0;

        foreach ($studios as $studio) {
            $seatsCount = $studio->seats()->count();
            $totalSeats += $seatsCount;

            if ($seatsCount === 0) {
                $this->line("  ⏭️  {$studio->name} - Sin asientos");
                continue;
            }

            $this->line("  🔄 {$studio->name} - {$seatsCount} asientos");

            if (!$dryRun) {
                $studio->reorderSeatNumbers();
                $updatedStudios++;
            }
        }

        if ($dryRun) {
            $this->info("📊 SIMULACIÓN: Se reordenarían asientos en {$studios->where('seats_count', '>', 0)->count()} salas ({$totalSeats} asientos total)");
        } else {
            $this->info("✅ Reordenamiento completado: {$updatedStudios} salas actualizadas ({$totalSeats} asientos total)");
        }
    }

    private function showCurrentSeats($seats, string $label = 'ACTUAL'): void
    {
        $this->line("\n📋 Estado {$label}:");
        $this->line(str_repeat('-', 50));

        foreach ($seats as $seat) {
            $this->line("  Asiento {$seat->seat_number} → Fila {$seat->row}, Columna {$seat->column}");
        }
    }

    private function showReorderingPreview($seats): void
    {
        $this->line("\n🔮 PREVISUALIZACIÓN del reordenamiento:");
        $this->line(str_repeat('-', 50));

        $newNumber = 1;
        foreach ($seats as $seat) {
            $change = $seat->seat_number !== $newNumber ? " → {$newNumber}" : " (sin cambios)";
            $this->line("  Asiento {$seat->seat_number}{$change} → Fila {$seat->row}, Columna {$seat->column}");
            $newNumber++;
        }
    }
} 