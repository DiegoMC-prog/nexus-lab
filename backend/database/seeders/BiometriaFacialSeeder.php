<?php

namespace Database\Seeders;

use App\Models\BiometriaFacial;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BiometriaFacialSeeder extends Seeder
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

        // Crear una biometría facial para cada uno de los 10 usuarios
        $index = 1;
        foreach ($usuarios as $usuario) {
            BiometriaFacial::create([
                'user_id' => $usuario->id,
                'landmarks' => [
                    'ojos' => [
                        ['x' => 120.5 + $index, 'y' => 240.2],
                        ['x' => 180.3 + $index, 'y' => 238.8]
                    ],
                    'nariz' => [
                        ['x' => 150.1, 'y' => 280.5 + $index]
                    ],
                    'boca' => [
                        ['x' => 135.2, 'y' => 320.0],
                        ['x' => 165.8, 'y' => 320.0]
                    ],
                    'confianza' => 0.95 + ($index % 5) * 0.01,
                ],
                'face_photo_base64' => 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////wgALCAABAAEBAREA/8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPxA=',
            ]);
            $index++;
        }
    }
}
