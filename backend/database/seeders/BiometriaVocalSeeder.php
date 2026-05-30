<?php

namespace Database\Seeders;

use App\Models\BiometriaVocal;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BiometriaVocalSeeder extends Seeder
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

        // Crear una firma de biometría vocal para cada uno de los 10 usuarios
        $index = 1;
        foreach ($usuarios as $usuario) {
            BiometriaVocal::create([
                'usuario_id' => $usuario->id,
                'firma_mcc' => base64_encode(random_bytes(128)), // Datos binarios seguros codificados en base64
                'frecuencia_muestreo' => $index % 2 === 0 ? 16000 : 44100, // Frecuencias comunes para análisis
                'frase_registro' => 'Nexus Lab, autorizar ingreso para ' . $usuario->name,
                'umbral_confianza' => 0.70 + ($index % 5) * 0.05, // Umbrales entre 0.70 y 0.90
            ]);
            $index++;
        }
    }
}
