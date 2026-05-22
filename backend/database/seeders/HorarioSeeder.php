<?php

namespace Database\Seeders;

use App\Models\Grupo;
use App\Models\Horario;
use App\Models\Laboratorio;
use App\Models\Materia;
use App\Models\User;
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
        $materias = Materia::all();
        $grupos = Grupo::all();

        if ($laboratorios->isEmpty() || $docentes->isEmpty() || $materias->isEmpty() || $grupos->isEmpty()) {
            return;
        }

        $docentesCount = $docentes->count();

        // Crear 10 horarios para las 10 materias, distribuidas en los laboratorios y asignadas a docentes
        for ($i = 0; $i < 10; $i++) {
            $materia = $materias->get($i);
            $grupo = $grupos->where('materia_id', $materia->id)->first();
            $laboratorio = $laboratorios->get($i % $laboratorios->count());
            $docente = $docentes->get($i % $docentesCount);

            if ($grupo && $laboratorio && $docente) {
                Horario::create([
                    'laboratorio_id' => $laboratorio->id,
                    'docente_id' => $docente->id,
                    'grupo_id' => $grupo->id,
                    'dia_semana' => ($i % 5) + 1, // Lunes a Viernes
                    'hora_inicio' => sprintf('%02d:00:00', 8 + ($i % 4) * 2), // Ej: 08:00, 10:00, 12:00, 14:00
                    'hora_fin' => sprintf('%02d:30:00', 9 + ($i % 4) * 2), // Ej: 09:30, 11:30, 13:30, 15:30
                    'fecha_inicio' => '2026-02-02',
                    'fecha_fin' => '2026-06-25',
                ]);
            }
        }
    }
}
