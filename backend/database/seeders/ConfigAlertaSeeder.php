<?php

namespace Database\Seeders;

use App\Models\ConfigAlerta;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ConfigAlertaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $configs = [
            [
                'metrica' => 'carga_cpu',
                'operador' => '>',
                'valor_umbral' => 90.0,
                'severidad' => 'alta',
                'activo' => true
            ],
            [
                'metrica' => 'uso_ram_mb',
                'operador' => '>',
                'valor_umbral' => 14336.0, // 14 GB en MB
                'severidad' => 'alta',
                'activo' => true
            ],
            [
                'metrica' => 'procesos_no_autorizados',
                'operador' => '>=',
                'valor_umbral' => 1.0,
                'severidad' => 'critica',
                'activo' => true
            ],
        ];

        foreach ($configs as $config) {
            ConfigAlerta::create($config);
        }
    }
}
