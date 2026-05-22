<?php

namespace Database\Seeders;

use App\Models\DispositivoConocido;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DispositivoConocidoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $usuarios = User::all();

        if ($usuarios->isEmpty()) {
            return;
        }

        $dispositivos = [
            'Laptop HP EliteBook',
            'iPhone 15 Pro Max',
            'MacBook Air M2',
            'Samsung Galaxy S24 Ultra',
            'Desktop Custom PC',
            'Lenovo ThinkPad X1',
            'iPad Pro 12.9',
            'Xiaomi 14 Pro',
            'Dell XPS 15',
            'Asus ROG Zephyrus',
        ];

        // Crear un dispositivo conocido para cada uno de los 10 usuarios
        $index = 0;
        foreach ($usuarios as $usuario) {
            DispositivoConocido::create([
                'user_id' => $usuario->id,
                'nombre_dispositivo' => $dispositivos[$index % count($dispositivos)],
                'fingerprint' => Str::uuid()->toString(),
            ]);
            $index++;
        }
    }
}
