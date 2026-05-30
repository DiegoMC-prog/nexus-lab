<?php

namespace Database\Seeders;

use App\Models\Grupo;
use App\Models\Horario;
use App\Models\Laboratorio;
use App\Models\Materia;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class HorarioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $laboratorios = Laboratorio::all();
        $docentes = User::role('docente')->get();
        $grupos = Grupo::all();

        if ($laboratorios->isEmpty() || $docentes->isEmpty() || $grupos->isEmpty()) {
            return;
        }

        $now = Carbon::now();
        $diaSemanaActual = $now->dayOfWeekIso; // 1 = Lunes, 7 = Domingo

        // Horarios para que la simulación se ejecute AHORA mismo
        $horaInicioActual = $now->copy()->subMinutes(30)->format('H:i:s');
        $horaFinActual = $now->copy()->addHours(2)->format('H:i:s');
        
        $fechaInicioSemestre = $now->copy()->subMonths(1)->format('Y-m-d');
        $fechaFinSemestre = $now->copy()->addMonths(4)->format('Y-m-d');

        // Crear 6 horarios en total
        for ($i = 0; $i < 6; $i++) {
            $grupo = $grupos->get($i % $grupos->count());
            $laboratorio = $laboratorios->get($i % $laboratorios->count());
            $docente = $docentes->get($i % $docentes->count());

            // Los primeros dos horarios serán "activos" en este instante
            if ($i < 2) {
                $diaSemana = $diaSemanaActual;
                $horaInicio = $horaInicioActual;
                $horaFin = $horaFinActual;
            } else {
                // Otros horarios aleatorios en la semana
                $diaSemana = (($diaSemanaActual + $i) % 6) + 1; // Otros días
                $horaInicio = sprintf('%02d:00:00', 8 + ($i % 4) * 2);
                $horaFin = sprintf('%02d:30:00', 9 + ($i % 4) * 2);
            }

            Horario::create([
                'laboratorio_id' => $laboratorio->id,
                'docente_id' => $docente->id,
                'grupo_id' => $grupo->id,
                'dia_semana' => $diaSemana,
                'hora_inicio' => $horaInicio,
                'hora_fin' => $horaFin,
                'fecha_inicio' => $fechaInicioSemestre,
                'fecha_fin' => $fechaFinSemestre,
            ]);
        }
    }
}
