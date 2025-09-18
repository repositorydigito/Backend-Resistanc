<?php

namespace App\Console\Commands;

use App\Models\Studio;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class GenerateStudioSeatsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'studios:generate-seats 
                            {--studio-id= : ID específico del estudio}
                            {--studio-name= : Nombre específico del estudio}
                            {--dry-run : Mostrar qué se haría sin ejecutar}
                            {--force : Regenerar asientos incluso si ya existen}
                            {--only-empty : Solo estudios sin asientos}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generar asientos automáticamente para estudios/salas';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🪑 Generando asientos para estudios/salas...');
        
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');
        $studioId = $this->option('studio-id');
        $studioName = $this->option('studio-name');
        $onlyEmpty = $this->option('only-empty');

        // Construir query
        $query = Studio::query();

        if ($studioId) {
            $query->where('id', $studioId);
        }

        if ($studioName) {
            $query->where('name', 'like', '%' . $studioName . '%');
        }

        if ($onlyEmpty) {
            $query->whereDoesntHave('seats');
        }

        $studios = $query->get();

        if ($studios->isEmpty()) {
            $this->warn('⚠️  No se encontraron estudios que coincidan con los criterios.');
            return self::SUCCESS;
        }

        $this->info("📋 Se encontraron {$studios->count()} estudios para procesar:");

        // Mostrar tabla de estudios
        $tableData = [];
        foreach ($studios as $studio) {
            $existingSeats = $studio->seats()->count();
            $maxPossibleSeats = ($studio->row ?? 0) * ($studio->column ?? 0);
            $configuredCapacity = $studio->capacity_per_seat ?? 0;
            
            $tableData[] = [
                'ID' => $studio->id,
                'Nombre' => $studio->name,
                'Tipo' => $studio->studio_type ?? 'N/A',
                'Filas' => $studio->row ?? 0,
                'Columnas' => $studio->column ?? 0,
                'Capacidad' => $configuredCapacity,
                'Asientos Actuales' => $existingSeats,
                'Max Posible' => $maxPossibleSeats,
                'Estado' => $existingSeats > 0 ? '✅ Tiene asientos' : '❌ Sin asientos',
            ];
        }

        $this->table([
            'ID', 'Nombre', 'Tipo', 'Filas', 'Columnas', 'Capacidad', 'Asientos Actuales', 'Max Posible', 'Estado'
        ], $tableData);

        if ($dryRun) {
            $this->info('🔍 Modo DRY-RUN: No se generaron asientos realmente.');
            return self::SUCCESS;
        }

        if (!$force && !$this->confirm('¿Proceder con la generación de asientos?', true)) {
            $this->info('❌ Operación cancelada.');
            return self::SUCCESS;
        }

        // Procesar cada estudio
        $processed = 0;
        $skipped = 0;
        $errors = 0;
        $totalSeatsGenerated = 0;

        foreach ($studios as $studio) {
            try {
                $existingSeats = $studio->seats()->count();
                
                // Saltar si ya tiene asientos y no es forzado
                if ($existingSeats > 0 && !$force) {
                    $this->line("⏭️  Saltando estudio '{$studio->name}': Ya tiene {$existingSeats} asientos");
                    $skipped++;
                    continue;
                }

                // Verificar configuración válida
                if (!$studio->row || !$studio->column || !$studio->capacity_per_seat) {
                    $this->line("⚠️  Saltando estudio '{$studio->name}': Configuración incompleta (filas: {$studio->row}, columnas: {$studio->column}, capacidad: {$studio->capacity_per_seat})");
                    $skipped++;
                    continue;
                }

                // Si es forzado y ya tiene asientos, mostrar info
                if ($force && $existingSeats > 0) {
                    $this->line("🔄 Regenerando asientos para estudio '{$studio->name}' (tenía {$existingSeats} asientos)");
                }

                // Generar asientos
                $studio->generateSeats();
                $newSeatsCount = $studio->seats()->count();
                
                if ($newSeatsCount > 0) {
                    $this->line("✅ Estudio '{$studio->name}': {$newSeatsCount} asientos generados");
                    $totalSeatsGenerated += $newSeatsCount;
                } else {
                    $this->line("⚠️  Estudio '{$studio->name}': No se generaron asientos");
                }
                
                $processed++;

            } catch (\Exception $e) {
                $this->error("❌ Error en estudio '{$studio->name}': " . $e->getMessage());
                $errors++;
                
                Log::error('Error generando asientos para estudio', [
                    'studio_id' => $studio->id,
                    'studio_name' => $studio->name,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }

        // Resumen final
        $this->info("\n📊 Resumen:");
        $this->info("✅ Estudios procesados: {$processed}");
        $this->info("⏭️  Estudios saltados: {$skipped}");
        $this->info("❌ Errores: {$errors}");
        $this->info("🪑 Total asientos generados: {$totalSeatsGenerated}");

        if ($totalSeatsGenerated > 0) {
            $this->info("🎉 Proceso completado exitosamente.");
        }

        return self::SUCCESS;
    }
}
