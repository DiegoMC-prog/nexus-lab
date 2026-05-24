<?php

namespace Database\Seeders;

use App\Models\Estacion;
use App\Models\Laboratorio;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class EstacionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $laboratorios = Laboratorio::all();
        $estudiantes = User::role('estudiante')->get();

        if ($laboratorios->isEmpty()) {
            return;
        }

        $estudiantesCount = $estudiantes->count();

        // Crear 10 estaciones de trabajo realistas
        for ($i = 1; $i <= 10; $i++) {
            $lab = $laboratorios->get(($i - 1) % $laboratorios->count());
            // Asignar estudiante de manera alternada, algunos nulos
            $estudianteId = ($i % 3 === 0 && $estudiantesCount > 0)
                ? $estudiantes->get(($i / 3 - 1) % $estudiantesCount)->id
                : null;

            Estacion::create([
                'laboratorio_id' => $lab->id,
                'uuid' => Str::uuid(),
                'estudiante_actual_id' => $estudianteId,
                'hostname' => 'PC-LAB-' . str_pad($i, 2, '0', STR_PAD_LEFT),
                'direccion_mac' => sprintf('00:1A:2B:3C:4D:%02X', $i),
                'direccion_ip' => '192.168.' . (($i % 3) + 1) . '.' . (10 + $i),
                'so_info' => $i % 4 === 0 ? 'Ubuntu 22.04 LTS' : 'Windows 11 Pro',
                'estado' => $i === 10 ? 'inactivo' : ($i === 9 ? 'mantenimiento' : 'activo'),
                'version_agente' => 'v1.4.' . ($i % 3),
                'ultima_conexion' => now()->subMinutes(rand(1, 120)),
            ]);
        }
    }
}
