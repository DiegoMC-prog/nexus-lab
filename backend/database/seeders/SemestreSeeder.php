<?php

namespace Database\Seeders;

use App\Models\SemestreAcademico;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SemestreSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $semestres = [
            ['nombre' => '2022-II', 'fecha_inicio' => '2022-08-01', 'fecha_fin' => '2022-12-31'],
            ['nombre' => '2023-I', 'fecha_inicio' => '2023-02-01', 'fecha_fin' => '2023-07-31'],
            ['nombre' => '2023-II', 'fecha_inicio' => '2023-08-01', 'fecha_fin' => '2023-12-31'],
            ['nombre' => '2024-I', 'fecha_inicio' => '2024-02-01', 'fecha_fin' => '2024-07-31'],
            ['nombre' => '2024-II', 'fecha_inicio' => '2024-08-01', 'fecha_fin' => '2024-12-31'],
            ['nombre' => '2025-I', 'fecha_inicio' => '2025-02-01', 'fecha_fin' => '2025-07-31'],
            ['nombre' => '2025-II', 'fecha_inicio' => '2025-08-01', 'fecha_fin' => '2025-12-31'],
            ['nombre' => '2026-I (Vigente)', 'fecha_inicio' => '2026-02-01', 'fecha_fin' => '2026-07-31'],
        ];

        foreach ($semestres as $semestre) {
            SemestreAcademico::updateOrCreate(
                ['nombre' => $semestre['nombre']],
                [
                    'fecha_inicio' => $semestre['fecha_inicio'],
                    'fecha_fin' => $semestre['fecha_fin'],
                ]
            );
        }
    }
}
