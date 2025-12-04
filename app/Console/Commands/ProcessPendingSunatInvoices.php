<?php

namespace App\Console\Commands;

use App\Jobs\ProcessSunatInvoice;
use App\Models\Invoice;
use Illuminate\Console\Command;

class ProcessPendingSunatInvoices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sunat:process-pending 
                            {--limit=100 : Número máximo de comprobantes a procesar en este lote}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Procesa todos los comprobantes electrónicos pendientes de envío a SUNAT';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $limit = (int) $this->option('limit');

        $this->info('Buscando comprobantes pendientes...');

        // Obtener comprobantes pendientes
        $pendingInvoices = Invoice::where('envio_estado', Invoice::ENVIO_PENDIENTE)
            ->orderBy('created_at', 'asc')
            ->limit($limit)
            ->get();

        if ($pendingInvoices->isEmpty()) {
            $this->info('No hay comprobantes pendientes para procesar.');
            return Command::SUCCESS;
        }

        $this->info("Se encontraron {$pendingInvoices->count()} comprobante(s) pendiente(s).");
        
        $processed = 0;
        $failed = 0;

        $bar = $this->output->createProgressBar($pendingInvoices->count());
        $bar->start();

        foreach ($pendingInvoices as $invoice) {
            try {
                // Procesar directamente usando el job
                $job = new ProcessSunatInvoice($invoice->id);
                $job->handle(app(\App\Services\SunatServices::class));
                $processed++;
            } catch (\Exception $e) {
                $failed++;
                $this->newLine();
                $this->error("Error al procesar comprobante {$invoice->serie}-{$invoice->numero}: {$e->getMessage()}");
            }
            
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Resumen
        $this->info("✅ Procesados exitosamente: {$processed}");
        if ($failed > 0) {
            $this->warn("❌ Fallidos: {$failed}");
        }

        return Command::SUCCESS;
    }
}
