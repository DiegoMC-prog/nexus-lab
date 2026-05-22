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
            ['nombre' => 'Economía', 'codigo' => 'ECO-ECO'],
            ['nombre' => 'Marketing y Publicidad', 'codigo' => 'MKT-PUB'],
            ['nombre' => 'Diseño Gráfico', 'codigo' => 'DSN-GRA'],
            ['nombre' => 'Administración de Empresas', 'codigo' => 'ADM-EMP'],
            ['nombre' => 'Derecho', 'codigo' => 'DER-DER'],
            ['nombre' => 'Ingeniería de Telecomunicaciones', 'codigo' => 'TEL-TEL'],
            ['nombre' => 'Ingeniería Financiera', 'codigo' => 'ING-FIN'],
            ['nombre' => 'Comunicación Social', 'codigo' => 'COM-SOC'],
        ];

        foreach ($carreras as $carrera) {
            Carrera::create($carrera);
        }
    }
}
