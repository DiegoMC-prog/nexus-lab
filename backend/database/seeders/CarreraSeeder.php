<?php

namespace Database\Seeders;

use App\Models\Carrera;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CarreraSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $carreras = [
            ['nombre' => 'Ingeniería de Sistemas', 'codigo' => 'INF-SIS'],
            ['nombre' => 'Ingeniería Informática', 'codigo' => 'INF-INF'],
            ['nombre' => 'Ingeniería de Software', 'codigo' => 'INF-SFW'],
            ['nombre' => 'Ingeniería de Telecomunicaciones', 'codigo' => 'TEL-TEL'],
            ['nombre' => 'Ingeniería Mecatrónica', 'codigo' => 'MEC-MEC'],
            ['nombre' => 'Ingeniería de Redes', 'codigo' => 'RED-RED'],
            ['nombre' => 'Ciencias de la Computación', 'codigo' => 'COM-CIE'],
        ];

        foreach ($carreras as $carrera) {
            Carrera::create($carrera);
        }
    }
}
