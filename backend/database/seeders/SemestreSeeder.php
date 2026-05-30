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
        $nombreSemestres = [
            '2022-II',
            '2023-I',
            '2023-II',
            '2024-I',
            '2024-II',
            '2025-I',
            '2025-II',
            '2026-I (Vigente)',
        ];

        foreach ($nombreSemestres as $nombre) {
            SemestreAcademico::create([
                'nombre' => $nombre,
            ]);
        }
    }
}
