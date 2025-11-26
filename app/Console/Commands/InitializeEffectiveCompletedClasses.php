<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class InitializeEffectiveCompletedClasses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:initialize-effective-classes 
                            {--user-id= : ID específico del usuario a inicializar}
                            {--all : Inicializar para todos los usuarios}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Inicializa el campo effective_completed_classes para usuarios existentes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userId = $this->option('user-id');
        $all = $this->option('all');

        if (!$userId && !$all) {
            $this->error('Debes especificar --user-id=ID o --all para inicializar todos los usuarios');
            return 1;
        }

        $query = User::query();

        if ($userId) {
            $query->where('id', $userId);
            $this->info("Inicializando clases efectivas para usuario ID: {$userId}");
        } else {
            $this->info("Inicializando clases efectivas para todos los usuarios...");
        }

        $users = $query->get();
        $total = $users->count();
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $updated = 0;
        foreach ($users as $user) {
            try {
                $oldValue = $user->effective_completed_classes ?? 0;
                $newValue = $user->calculateAndUpdateEffectiveCompletedClasses();
                
                if ($oldValue !== $newValue) {
                    $updated++;
                    $this->newLine();
                    $this->line("Usuario ID {$user->id}: {$oldValue} -> {$newValue} clases efectivas");
                }
            } catch (\Exception $e) {
                $this->newLine();
                $this->error("Error procesando usuario ID {$user->id}: {$e->getMessage()}");
            }
            
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        
        $this->info("✓ Proceso completado");
        $this->info("  Total usuarios procesados: {$total}");
        $this->info("  Usuarios actualizados: {$updated}");
        $this->info("  Usuarios sin cambios: " . ($total - $updated));

        return 0;
    }
}
