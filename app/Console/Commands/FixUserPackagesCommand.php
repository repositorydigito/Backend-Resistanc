<?php

namespace App\Console\Commands;

use App\Models\UserPackage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixUserPackagesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'packages:fix {--user-id= : ID específico del usuario} {--dry-run : Solo mostrar qué se corregiría sin hacer cambios}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verifica y corrige inconsistencias en los paquetes de usuario';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userId = $this->option('user-id');
        $dryRun = $this->option('dry-run');

        $this->info('🔍 Verificando paquetes de usuario...');

        $query = UserPackage::query();
        
        if ($userId) {
            $query->where('user_id', $userId);
        }

        $packages = $query->get();
        
        $this->info("📦 Encontrados {$packages->count()} paquetes para verificar");

        $fixedCount = 0;
        $errors = [];

        foreach ($packages as $package) {
            $this->line("Verificando paquete {$package->package_code} (ID: {$package->id})");
            
            $originalRemaining = $package->remaining_classes;
            $originalUsed = $package->used_classes;
            $originalTotal = $package->total_classes;
            
            // Verificar si la suma de used + remaining = total
            $expectedRemaining = $originalTotal - $originalUsed;
            
            if ($expectedRemaining !== $originalRemaining) {
                $this->warn("  ⚠️  Inconsistencia detectada:");
                $this->warn("     Total: {$originalTotal}");
                $this->warn("     Usadas: {$originalUsed}");
                $this->warn("     Restantes actuales: {$originalRemaining}");
                $this->warn("     Restantes esperadas: {$expectedRemaining}");
                
                if (!$dryRun) {
                    try {
                        DB::transaction(function () use ($package, $expectedRemaining) {
                            $package->update([
                                'remaining_classes' => max(0, $expectedRemaining)
                            ]);
                        });
                        
                        $this->info("  ✅ Corregido: remaining_classes = {$expectedRemaining}");
                        $fixedCount++;
                    } catch (\Exception $e) {
                        $errors[] = "Error corrigiendo paquete {$package->id}: " . $e->getMessage();
                        $this->error("  ❌ Error: " . $e->getMessage());
                    }
                } else {
                    $this->info("  🔄 Se corregiría: remaining_classes = {$expectedRemaining}");
                    $fixedCount++;
                }
            } else {
                $this->info("  ✅ Paquete consistente");
            }
            
            // Verificar si el paquete está activo pero no tiene activation_date
            if ($package->status === 'active' && !$package->activation_date) {
                $this->warn("  ⚠️  Paquete activo sin fecha de activación");
                
                if (!$dryRun) {
                    try {
                        $package->update([
                            'activation_date' => $package->purchase_date
                        ]);
                        $this->info("  ✅ Fecha de activación establecida");
                    } catch (\Exception $e) {
                        $errors[] = "Error estableciendo activation_date en paquete {$package->id}: " . $e->getMessage();
                        $this->error("  ❌ Error: " . $e->getMessage());
                    }
                } else {
                    $this->info("  🔄 Se establecería activation_date = {$package->purchase_date}");
                }
            }
        }

        $this->newLine();
        
        if ($dryRun) {
            $this->info("🔍 Modo dry-run: Se corregirían {$fixedCount} paquetes");
        } else {
            $this->info("✅ Proceso completado: {$fixedCount} paquetes corregidos");
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