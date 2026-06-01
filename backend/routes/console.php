<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Programar la simulación de fondo de Nexus Lab para ejecutarse automáticamente cada minuto
Schedule::command('nexus:simulate-scheduler')->everyTenSeconds();
