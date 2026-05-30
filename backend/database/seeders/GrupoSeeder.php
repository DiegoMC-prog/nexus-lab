<?php

namespace Database\Seeders;

use App\Models\Grupo;
use App\Models\Materia;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class GrupoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $materias = Materia::all();

        if ($materias->isEmpty()) {
            return;
        }

        // Crear 30 grupos: 10 paralelos para las primeras 3 materias
        $materiasParaGrupos = $materias->take(3);

        foreach ($materiasParaGrupos as $materia) {
            for ($i = 1; $i <= 10; $i++) {
                Grupo::create([
                    'materia_id' => $materia->id,
                    'nombre' => 'Paralelo ' . $i,
                    'gestion' => 'I/2026',
                    'cupo_maximo' => 40,
                ]);
            }
        }
    }
}
