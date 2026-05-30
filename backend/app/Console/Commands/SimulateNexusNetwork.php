<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Events\EstacionDetectadaEnCanal;
use Illuminate\Support\Str;

class SimulateNexusNetwork extends Command
{
    // El comando recibirá el ID del laboratorio donde el docente abrió la interfaz de vinculación
    protected $signature = 'nexus:simulate-registration {laboratorio_id}';
    protected $description = 'Simula el descubrimiento estocástico y paulatino de estaciones en la subred de un laboratorio';

    public function handle()
    {
        $labId = $this->argument('laboratorio_id');
        $this->info("=== Simulador de Descubrimiento de Red Activo para Lab ID: {$labId} ===");
        $this->info("Presiona Ctrl+C para detener el escaneo artificial.");

        $estacionesEmitidas = 0;
        $maxEstacionesAEncontrar = 10; // Meta de la demo

        while ($estacionesEmitidas < $maxEstacionesAEncontrar) {
            // Generamos un número aleatorio entre 1 y 100 para evaluar la probabilidad
            $probabilidadArrancado = rand(1, 100);

            // 🎯 40% de probabilidad en cada ciclo (cada 2 segundos) de que una PC "encienda"
            // Esto causa que a veces entren PCs seguidas y a veces tarde unos segundos más (Simulación Realista)
            if ($probabilidadArrancado <= 40) {
                $estacionesEmitidas++;

                // Formateamos los metadatos técnicos que el Agente C# enviaría en su JSON inicial
                $mockPayload = [
                    'laboratorio_target_id' => (int) $labId,
                    'uuid'                  => (string) Str::uuid(), // UUID persistente que no existe en la BD
                    'hostname'              => "LAB-" . Str::padLeft($labId, 2, '0') . "-PC" . Str::padLeft($estacionesEmitidas, 2, '0'),
                    'direccion_mac'         => "00:1A:3F:8A:BC:" . Str::padLeft(dechex($estacionesEmitidas + 10), 2, '0', STR_PAD_LEFT),
                    'direccion_ip'          => "192.168.{$labId}." . (10 + $estacionesEmitidas),
                    'so_info'               => 'Windows 11 Professional (x64) Build 22631',
                    'version_agente'        => 'v2.1.0-wpf',
                    'estado'                => 'pendiente'
                ];

                // Transmitimos el evento por el WebSocket directo al canal abierto en Vue
                broadcast(new EstacionDetectadaEnCanal($mockPayload))->toOthers();

                $this->line("<fg=green>[Detectado]</> Transmitiendo handshake de {$mockPayload['hostname']} a través del WebSocket (Chance: {$probabilidadArrancado}%)");
            } else {
                $this->line("<fg=gray>[Escaneando...]</> Escucha activa en la subred... Ningún nodo nuevo inició secuencia de arranque.");
            }

            // Espera 2 segundos antes de tirar los dados probabilísticos otra vez
            sleep(2);
        }

        $this->info("=== Simulación finalizada: Se enviaron {$estacionesEmitidas} estaciones entrantes al canal ===");
    }
}
