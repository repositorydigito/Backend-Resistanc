<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class Rebuild extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:rebuild';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
          $this->info('Starting application rebuild...');

        // Ejecutar migrate:fresh
        $this->info('Refreshing migrations...');
        Artisan::call('migrate:fresh', [
            '--force' => true // Forzar la ejecución en producción
        ]);
        $this->info('Migrations refreshed.');

        // Ejecutar shield:generate --all usando Process
        $this->info('Generating permissions and roles...');
        $process = new Process(['php', 'artisan', 'shield:generate', '--all']);
        $process->setWorkingDirectory(base_path()); // Asegúrate de que el directorio de trabajo sea el correcto
        $process->setInput('0'); // Simular la entrada del usuario
        $process->run();

        // Verificar si el proceso falló
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        $this->info('Permissions and roles generated: ' . $process->getOutput());

        // Ejecutar db:seed
        $this->info('Seeding the database...');
        Artisan::call('db:seed', [
            '--force' => true // Forzar la ejecución en producción
        ]);
        $this->info('Database seeded.');


        $this->info('Application rebuild completed!');
    }
}
