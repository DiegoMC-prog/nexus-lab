<?php

namespace Database\Seeders;

use App\Models\Estacion;
use App\Models\LogsTelemetria;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LogsTelemetriaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $estaciones = Estacion::all();

        if ($estaciones->isEmpty()) {
            return;
        }

        // Crear 1 registro de telemetría para cada una de las 10 estaciones
        $index = 1;
        foreach ($estaciones as $estacion) {
            LogsTelemetria::create([
                'estacion_id' => $estacion->id,
                'carga_cpu' => 15.5 + ($index % 8) * 8.5, // CPU entre 15% y 80%
                'uso_ram_mb' => 2048 + ($index % 5) * 2048, // RAM entre 2GB y 10GB
                'temp_cpu' => 45.0 + ($index % 6) * 5.5, // Temperatura entre 45C y 78C
                'uso_disco' => 25.4 + ($index % 7) * 8.2, // Disco entre 25% y 82%
                'latencia_red' => 5 + ($index % 10) * 12, // Latencia entre 5ms y 125ms
            ]);
            $index++;
        }
    }
}
