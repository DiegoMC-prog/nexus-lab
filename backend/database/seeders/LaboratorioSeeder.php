<?php

namespace Database\Seeders;

use App\Models\Laboratorio;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LaboratorioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $laboratorios = [
            [
                'nombre' => 'Laboratorio de Redes y Telecomunicaciones',
                'pabellon' => 'Pabellón A',
                'piso' => 'Piso 1',
                'activo' => true
            ],
            [
                'nombre' => 'Laboratorio de Inteligencia Artificial',
                'pabellon' => 'Pabellón B',
                'piso' => 'Piso 2',
                'activo' => true
            ],
            [
                'nombre' => 'Laboratorio de Computación Gráfica y Diseño',
                'pabellon' => 'Pabellón C',
                'piso' => 'Piso 1',
                'activo' => true
            ],
            [
                'nombre' => 'Laboratorio de Desarrollo de Software',
                'pabellon' => 'Pabellón A',
                'piso' => 'Piso 2',
                'activo' => true
            ],
            [
                'nombre' => 'Laboratorio de Base de Datos',
                'pabellon' => 'Pabellón A',
                'piso' => 'Piso 3',
                'activo' => true
            ],
            [
                'nombre' => 'Laboratorio de Ciberseguridad',
                'pabellon' => 'Pabellón B',
                'piso' => 'Piso 3',
                'activo' => true
            ],
            [
                'nombre' => 'Laboratorio de Sistemas Embebidos',
                'pabellon' => 'Pabellón D',
                'piso' => 'Piso 1',
                'activo' => true
            ],
            [
                'nombre' => 'Laboratorio de Simulación Económica',
                'pabellon' => 'Pabellón E',
                'piso' => 'Piso 1',
                'activo' => true
            ],
            [
                'nombre' => 'Laboratorio de Fotografía y Multimedia',
                'pabellon' => 'Pabellón C',
                'piso' => 'Piso 2',
                'activo' => true
            ],
            [
                'nombre' => 'Laboratorio de Hardware y Robótica',
                'pabellon' => 'Pabellón D',
                'piso' => 'Piso 2',
                'activo' => false
            ],
        ];

        foreach ($laboratorios as $lab) {
            Laboratorio::create($lab);
        }
    }
}
