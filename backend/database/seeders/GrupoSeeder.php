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

        // Crear 9 grupos: 3 paralelos para las primeras 3 materias
        $materiasParaGrupos = $materias->take(3);

        foreach ($materiasParaGrupos as $materia) {
            $paralelos = ['A', 'B', 'C'];
            foreach ($paralelos as $paralelo) {
                Grupo::create([
                    'materia_id' => $materia->id,
                    'nombre' => 'Paralelo ' . $paralelo,
                    'gestion' => 'I/2026',
                    'cupo_maximo' => 40,
                ]);
            }
        }
    }
}
