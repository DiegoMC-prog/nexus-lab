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

        // Crear 10 grupos, uno para cada una de las 10 materias
        $index = 1;
        foreach ($materias as $materia) {
            Grupo::create([
                'materia_id' => $materia->id,
                'nombre' => 'Paralelo ' . ($index % 2 === 0 ? 'B' : 'A'),
                'gestion' => 'I/2026',
                'cupo_maximo' => 40,
            ]);
            $index++;
        }
    }
}
