<?php

namespace Database\Seeders;

use App\Models\Estacion;
use App\Models\PerfilHardware;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PerfilHardwareSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $estaciones = Estacion::all();

        if ($estaciones->isEmpty()) {
            return;
        }

        // Crear perfiles de hardware para cada estación
        $index = 1;
        foreach ($estaciones as $estacion) {
            PerfilHardware::create([
                'estacion_id' => $estacion->id,
                'cpu_modelo' => $index % 3 === 0 ? 'AMD Ryzen 7 5700X (8 Cores, 3.4GHz)' : ($index % 3 === 1 ? 'Intel Core i7-12700 (12 Cores, 2.1GHz)' : 'Intel Core i5-11400 (6 Cores, 2.6GHz)'),
                'ram_total_gb' => $index % 4 === 0 ? '32' : ($index % 4 === 3 ? '8' : '16'),
                'cantidad_almacenamiento' => $index % 2 === 0 ? '512 GB NVMe PCIe M.2 SSD' : '1 TB NVMe PCIe M.2 SSD',
                'gpu_modelo' => $index % 3 === 0 ? 'NVIDIA GeForce RTX 3060 12GB' : ($index % 3 === 1 ? 'AMD Radeon RX 6600 XT 8GB' : 'Intel UHD Graphics 730 (Integrada)'),
            ]);
            $index++;
        }
    }
}
