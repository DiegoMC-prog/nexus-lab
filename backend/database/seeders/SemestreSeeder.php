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
        $nombreSemestres = ['1er Semestre', '2do Semestre', '3er Semestre', '4to Semestre', '5to Semestre'];

        foreach ($nombreSemestres as $nombre) {
            SemestreAcademico::create([
                'nombre' => $nombre,
            ]);
        }
    }
}
