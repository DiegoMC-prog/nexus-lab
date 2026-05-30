<?php

namespace Database\Seeders;

use App\Models\Materia;
use App\Models\Carrera;
use App\Models\SemestreAcademico;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MateriaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener semestres y carreras para asignar IDs válidos
        $semestres = SemestreAcademico::all();
        $carreras = Carrera::all();

        if ($semestres->isEmpty() || $carreras->isEmpty()) {
            return;
        }

        $materias = [
            [
                'codigo' => 'INF-111',
                'nombre' => 'Programación I',
                'creditos' => 5,
                'carrera_codigo' => 'INF-SIS',
                'semestre_index' => 7 // 2026-I
            ],
            [
                'codigo' => 'INF-121',
                'nombre' => 'Estructura de Datos',
                'creditos' => 5,
                'carrera_codigo' => 'INF-INF',
                'semestre_index' => 7 
            ],
            [
                'codigo' => 'SFW-111',
                'nombre' => 'Ingeniería de Requisitos',
                'creditos' => 4,
                'carrera_codigo' => 'INF-SFW',
                'semestre_index' => 7
            ],
            [
                'codigo' => 'TEL-121',
                'nombre' => 'Redes de Computadoras I',
                'creditos' => 4,
                'carrera_codigo' => 'TEL-TEL',
                'semestre_index' => 7
            ],
            [
                'codigo' => 'MEC-111',
                'nombre' => 'Sistemas Embebidos',
                'creditos' => 4,
                'carrera_codigo' => 'MEC-MEC',
                'semestre_index' => 7
            ],
            [
                'codigo' => 'RED-111',
                'nombre' => 'Seguridad en Redes',
                'creditos' => 3,
                'carrera_codigo' => 'RED-RED',
                'semestre_index' => 7
            ],
        ];

        foreach ($materias as $mat) {
            $carrera = $carreras->where('codigo', $mat['carrera_codigo'])->first();
            $semestre = $semestres->values()->get($mat['semestre_index']);

            if ($carrera && $semestre) {
                Materia::create([
                    'semestre_academico_id' => $semestre->id,
                    'carrera_id' => $carrera->id,
                    'codigo' => $mat['codigo'],
                    'nombre' => $mat['nombre'],
                    'creditos' => $mat['creditos'],
                ]);
            }
        }
    }
}
