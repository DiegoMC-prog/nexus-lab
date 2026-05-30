<?php

namespace App\Console\Commands;

use App\Services\SimuladorActividadService;
use Illuminate\Console\Command;

class SimulateScheduler extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nexus:simulate-scheduler';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ejecuta un ciclo de simulación de actividad de agente (latidos, telemetría e infracciones) cuando existe un horario activo.';

    /**
     * El servicio de simulación.
     */
    protected SimuladorActividadService $simuladorService;

    /**
     * Create a new command instance.
     */
    public function __construct(SimuladorActividadService $simuladorService)
    {
        parent::__construct();
        $this->simuladorService = $simuladorService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("==================================================================");
        $this->info("   NEXUS LAB - SIMULACIÓN DE AGENTE DE FONDO (LARAVEL SCHEDULER)  ");
        $this->info("==================================================================");

        $resultado = $this->simuladorService->ejecutarSimulacion();

        if ($resultado['status'] === 'idle') {
            $this->comment(" -> " . $resultado['message']);
            $this->info("==================================================================");
            return 0;
        }

        $this->info(" -> " . $resultado['message']);
        $this->line(" -> Clases/Laboratorios simulados: <fg=green;options=bold>" . $resultado['clases_simuladas'] . "</>");
        
        foreach ($resultado['detalles'] as $detalle) {
            $this->line("    • Laboratorio: <fg=cyan>{$detalle['laboratorio']}</> - Grupo: <fg=yellow>{$detalle['grupo']}</> (<fg=gray>{$detalle['materia']}</>)");
        }

        $this->info("==================================================================");
        return 0;
    }
}
