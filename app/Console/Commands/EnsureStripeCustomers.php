<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Observers\UserObserver;
use Illuminate\Console\Command;

class EnsureStripeCustomers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stripe:ensure-customers 
                            {--user-id= : ID específico del usuario a procesar}
                            {--all : Procesar todos los usuarios verificados sin cliente de Stripe}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Asegura que los usuarios verificados tengan un cliente de Stripe válido';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $observer = new UserObserver();

        if ($this->option('user-id')) {
            // Procesar un usuario específico
            $user = User::find($this->option('user-id'));
            
            if (!$user) {
                $this->error("Usuario con ID {$this->option('user-id')} no encontrado.");
                return 1;
            }

            $this->info("Procesando usuario: {$user->email} (ID: {$user->id})");
            $this->processUser($user, $observer);
            
        } elseif ($this->option('all')) {
            // Procesar todos los usuarios verificados sin cliente de Stripe
            $users = User::whereNotNull('email_verified_at')
                ->where(function ($query) {
                    $query->whereNull('stripe_id')
                        ->orWhere('stripe_id', '');
                })
                ->get();

            if ($users->isEmpty()) {
                $this->info('No hay usuarios verificados sin cliente de Stripe.');
                return 0;
            }

            $this->info("Encontrados {$users->count()} usuarios verificados sin cliente de Stripe.");
            
            $bar = $this->output->createProgressBar($users->count());
            $bar->start();

            foreach ($users as $user) {
                $this->processUser($user, $observer);
                $bar->advance();
            }

            $bar->finish();
            $this->newLine();
            $this->info('Proceso completado.');
            
        } else {
            $this->error('Debes especificar --user-id=ID o --all');
            $this->info('Ejemplos:');
            $this->info('  php artisan stripe:ensure-customers --user-id=1');
            $this->info('  php artisan stripe:ensure-customers --all');
            return 1;
        }

        return 0;
    }

    /**
     * Procesa un usuario individual.
     */
    protected function processUser(User $user, UserObserver $observer): void
    {
        try {
            $this->line("  Verificando usuario: {$user->email}");
            
            $observer->ensureStripeCustomer($user);
            
            // Recargar el usuario para obtener el stripe_id actualizado
            $user->refresh();
            
            if ($user->stripe_id) {
                $this->info("  ✓ Cliente de Stripe creado/verificado: {$user->stripe_id}");
            } else {
                $this->warn("  ⚠ No se pudo crear el cliente de Stripe para {$user->email}");
            }
        } catch (\Throwable $e) {
            $this->error("  ✗ Error al procesar {$user->email}: {$e->getMessage()}");
        }
    }
}



