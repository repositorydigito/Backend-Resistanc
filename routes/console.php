<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Programar procesamiento de comprobantes pendientes a SUNAT
// Ejecutar todos los días a las 12:00 AM (medianoche)
Schedule::command('sunat:process-pending')
    ->dailyAt('00:00')
    ->timezone('America/Lima')
    ->withoutOverlapping()
    ->description('Procesar comprobantes pendientes de SUNAT diariamente a medianoche');
