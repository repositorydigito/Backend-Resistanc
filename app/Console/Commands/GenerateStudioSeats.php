<?php

namespace App\Console\Commands;

use App\Models\Studio;
use Illuminate\Console\Command;

class GenerateStudioSeats extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'studio:generate-seats {--studio= : ID específico de la sala} {--force : Regenerar asientos existentes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate seats for studios based on their row and column configuration';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Generando asientos para las salas...');

        $query = Studio::query();

        // Si se especifica una sala específica
        if ($studioId = $this->option('studio')) {
            $query->where('id', $studioId);
        }

        $studios = $query->get();

        if ($studios->isEmpty()) {
            $this->warn('No se encontraron salas para procesar.');
            return 0;
        }

        $processed = 0;
        $skipped = 0;

        foreach ($studios as $studio) {
            $existingSeats = $studio->seats()->count();
            $seatCapacity = $studio->capacity_per_seat ?? 0;
            $maxPossible = ($studio->row ?? 0) * ($studio->column ?? 0);

            // Skip if seats already exist and force is not used
            if ($existingSeats > 0 && !$this->option('force')) {
                $this->line("Saltando sala '{$studio->name}' - ya tiene {$existingSeats} asientos");
                $skipped++;
                continue;
            }

            if ($seatCapacity <= 0 || $maxPossible <= 0) {
                $this->warn("Saltando sala '{$studio->name}' - configuración inválida (capacidad por asiento: {$seatCapacity}, filas: {$studio->row}, columnas: {$studio->column})");
                $skipped++;
                continue;
            }

            try {
                $studio->generateSeats();
                $newSeatsCount = $studio->seats()->count();

                $this->info("✓ Sala '{$studio->name}': {$newSeatsCount} asientos generados de {$seatCapacity} capacidad por asiento (configuración: {$studio->row}×{$studio->column}, direccionamiento: {$studio->addressing})");
                $processed++;
            } catch (\Exception $e) {
                $this->error("✗ Error en sala '{$studio->name}': " . $e->getMessage());
            }
        }

        $this->newLine();
        $this->info("Resumen:");
        $this->info("- Salas procesadas: {$processed}");
        $this->info("- Salas saltadas: {$skipped}");
        $this->info("¡Proceso completado!");

        return 0;
    }
}
