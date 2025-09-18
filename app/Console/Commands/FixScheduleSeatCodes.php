<?php

namespace App\Console\Commands;

use App\Models\ClassScheduleSeat;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FixScheduleSeatCodes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'schedules:fix-seat-codes 
                            {--dry-run : Solo mostrar qué se haría sin ejecutar cambios}
                            {--force : Forzar ejecución sin confirmación}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Arreglar códigos duplicados o inválidos en class_schedule_seat';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        $this->info('🔍 Analizando códigos de asientos...');

        // Encontrar códigos duplicados
        $duplicates = DB::table('class_schedule_seat')
            ->select('code', DB::raw('COUNT(*) as count'))
            ->groupBy('code')
            ->having('count', '>', 1)
            ->get();

        // Encontrar códigos inválidos (números negativos o vacíos)
        $invalidCodes = DB::table('class_schedule_seat')
            ->where(function ($query) {
                $query->where('code', '<=', 0)
                      ->orWhere('code', '')
                      ->orWhereNull('code');
            })
            ->get();

        $this->info("📊 Resultados del análisis:");
        $this->info("   • Códigos duplicados: {$duplicates->count()}");
        $this->info("   • Códigos inválidos: {$invalidCodes->count()}");

        if ($duplicates->isEmpty() && $invalidCodes->isEmpty()) {
            $this->info('✅ No se encontraron problemas con los códigos');
            return 0;
        }

        if ($dryRun) {
            $this->warn('🔍 MODO DRY-RUN - No se realizarán cambios');
            
            if ($duplicates->isNotEmpty()) {
                $this->info("\n📋 Códigos duplicados encontrados:");
                foreach ($duplicates as $duplicate) {
                    $this->line("   • Código '{$duplicate->code}' aparece {$duplicate->count} veces");
                }
            }

            if ($invalidCodes->isNotEmpty()) {
                $this->info("\n📋 Códigos inválidos encontrados:");
                foreach ($invalidCodes as $invalid) {
                    $this->line("   • ID {$invalid->id}: código '{$invalid->code}'");
                }
            }

            return 0;
        }

        // Confirmar si no es forzado
        if (!$force && !$this->confirm('¿Estás seguro de que quieres arreglar los códigos?')) {
            $this->info('Operación cancelada');
            return 0;
        }

        $fixedCount = 0;

        try {
            DB::beginTransaction();

            // Arreglar códigos duplicados
            foreach ($duplicates as $duplicate) {
                $records = ClassScheduleSeat::where('code', $duplicate->code)->get();
                
                foreach ($records as $index => $record) {
                    if ($index === 0) {
                        // Mantener el primer registro con el código original
                        continue;
                    }
                    
                    // Regenerar código para los duplicados
                    $newCode = $record->generateScheduleSeatCode($record->class_schedules_id, $record->seats_id);
                    $record->update(['code' => $newCode]);
                    $fixedCount++;
                    
                    $this->line("   • Arreglado duplicado ID {$record->id}: {$duplicate->code} → {$newCode}");
                }
            }

            // Arreglar códigos inválidos
            foreach ($invalidCodes as $invalid) {
                $record = ClassScheduleSeat::find($invalid->id);
                if ($record) {
                    $newCode = $record->generateScheduleSeatCode($record->class_schedules_id, $record->seats_id);
                    $record->update(['code' => $newCode]);
                    $fixedCount++;
                    
                    $this->line("   • Arreglado inválido ID {$record->id}: '{$invalid->code}' → {$newCode}");
                }
            }

            DB::commit();

            $this->info("\n✅ Proceso completado exitosamente");
            $this->info("   • Códigos arreglados: {$fixedCount}");

            Log::info("Códigos de asientos arreglados", [
                'fixed_count' => $fixedCount,
                'duplicates_found' => $duplicates->count(),
                'invalid_found' => $invalidCodes->count()
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            $this->error("❌ Error durante el proceso: " . $e->getMessage());
            
            Log::error("Error arreglando códigos de asientos", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return 1;
        }

        return 0;
    }
} 