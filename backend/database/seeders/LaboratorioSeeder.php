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
                'nombre' => 'Laboratorio de Sistemas 1',
                'pabellon' => 'Pabellón A',
                'piso' => 'Piso 1',
                'activo' => true
            ],
            [
                'nombre' => 'Laboratorio de Redes 2',
                'pabellon' => 'Pabellón B',
                'piso' => 'Piso 2',
                'activo' => true
            ],
        ];

        foreach ($laboratorios as $lab) {
            Laboratorio::create($lab);
        }
    }
}
