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

        // En caso de que no existan carreras o semestres (por seguridad, aunque el orden del DatabaseSeeder lo garantiza)
        if ($semestres->isEmpty() || $carreras->isEmpty()) {
            return;
        }

        $materias = [
            [
                'codigo' => 'INF-111',
                'nombre' => 'Programación I',
                'creditos' => 5,
                'carrera_codigo' => 'INF-SIS',
                'semestre_index' => 0 // 1er Semestre
            ],
            [
                'codigo' => 'INF-121',
                'nombre' => 'Estructura de Datos',
                'creditos' => 5,
                'carrera_codigo' => 'INF-INF',
                'semestre_index' => 1 // 2do Semestre
            ],
            [
                'codigo' => 'ECO-111',
                'nombre' => 'Introducción a la Economía',
                'creditos' => 4,
                'carrera_codigo' => 'ECO-ECO',
                'semestre_index' => 0
            ],
            [
                'codigo' => 'ECO-121',
                'nombre' => 'Microeconomía',
                'creditos' => 4,
                'carrera_codigo' => 'ECO-ECO',
                'semestre_index' => 1
            ],
            [
                'codigo' => 'MKT-111',
                'nombre' => 'Principios de Marketing',
                'creditos' => 4,
                'carrera_codigo' => 'MKT-PUB',
                'semestre_index' => 0
            ],
            [
                'codigo' => 'DSN-111',
                'nombre' => 'Diseño Vectorial',
                'creditos' => 3,
                'carrera_codigo' => 'DSN-GRA',
                'semestre_index' => 0
            ],
            [
                'codigo' => 'ADM-111',
                'nombre' => 'Teoría de la Administración',
                'creditos' => 4,
                'carrera_codigo' => 'ADM-EMP',
                'semestre_index' => 0
            ],
            [
                'codigo' => 'DER-111',
                'nombre' => 'Introducción al Derecho',
                'creditos' => 4,
                'carrera_codigo' => 'DER-DER',
                'semestre_index' => 0
            ],
            [
                'codigo' => 'TEL-111',
                'nombre' => 'Circuitos Eléctricos',
                'creditos' => 5,
                'carrera_codigo' => 'TEL-TEL',
                'semestre_index' => 1
            ],
            [
                'codigo' => 'FIN-111',
                'nombre' => 'Contabilidad Básica',
                'creditos' => 4,
                'carrera_codigo' => 'ING-FIN',
                'semestre_index' => 0
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
