<?php

namespace Database\Seeders;

use App\Models\Perfil;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PerfilSeeder extends Seeder
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

        // Crear un perfil para cada usuario
        $index = 1;
        foreach ($usuarios as $usuario) {
            Perfil::create([
                'user_id' => $usuario->id,
                'telefono' => '7' . rand(1000000, 9999999), // Teléfonos celulares realistas en Bolivia (comienzan con 7)
            ]);
            $index++;
        }
    }
}
