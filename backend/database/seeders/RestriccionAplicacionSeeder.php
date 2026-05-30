<?php

namespace Database\Seeders;

use App\Models\Laboratorio;
use App\Models\RestriccionAplicacion;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RestriccionAplicacionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $laboratorios = Laboratorio::all();

        if ($laboratorios->isEmpty()) {
            return;
        }

        $aplicaciones = [
            [
                'nombre_aplicacion' => 'Tor Browser',
                'nombre_proceso' => 'tor.exe',
                'tipo_restriccion' => 'bloquear',
            ],
            [
                'nombre_aplicacion' => 'BitTorrent',
                'nombre_proceso' => 'bittorrent.exe',
                'tipo_restriccion' => 'bloquear',
            ],
            [
                'nombre_aplicacion' => 'Steam',
                'nombre_proceso' => 'steam.exe',
                'tipo_restriccion' => 'bloquear',
            ],
            [
                'nombre_aplicacion' => 'Discord',
                'nombre_proceso' => 'discord.exe',
                'tipo_restriccion' => 'advertir',
            ],
            [
                'nombre_aplicacion' => 'Cheat Engine',
                'nombre_proceso' => 'cheatengine.exe',
                'tipo_restriccion' => 'bloquear',
            ],
        ];

        // Crear restricciones para todos los laboratorios
        foreach ($laboratorios as $lab) {
            foreach ($aplicaciones as $app) {
                RestriccionAplicacion::create([
                    'laboratorio_id' => $lab->id,
                    'nombre_aplicacion' => $app['nombre_aplicacion'],
                    'nombre_proceso' => $app['nombre_proceso'],
                    'tipo_restriccion' => $app['tipo_restriccion'],
                    'activo' => true,
                ]);
            }
        }
    }
}
