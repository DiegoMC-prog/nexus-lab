<?php

namespace Database\Seeders;

use App\Models\Alerta;
use App\Models\ConfigAlerta;
use App\Models\Estacion;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AlertaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $estaciones = Estacion::all();
        $configs = ConfigAlerta::all();

        if ($estaciones->isEmpty() || $configs->isEmpty()) {
            return;
        }

        $configsCount = $configs->count();

        // Crear 10 alertas disparadas en las estaciones de trabajo
        for ($i = 0; $i < 10; $i++) {
            $estacion = $estaciones->get($i % $estaciones->count());
            $config = $configs->get($i % $configsCount);

            // Determinar valor actual superando el umbral de configuración
            $valorActual = $config->valor_umbral + ($i + 1) * 1.5;
            $estado = $i % 3 === 0 ? 'resuelta' : ($i % 3 === 1 ? 'disparada' : 'reconocida');

            Alerta::create([
                'estacion_id' => $estacion->id,
                'config_alerta_id' => $config->id,
                'valor_actual' => $valorActual,
                'estado' => $estado,
                // Como resuelto_at no es nullable en la migración, le asignamos una fecha por defecto
                'resuelto_at' => $estado === 'resuelta' ? now()->subMinutes(rand(10, 60)) : now(),
            ]);
        }
    }
}
