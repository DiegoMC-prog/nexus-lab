<?php

namespace Database\Seeders;

use App\Models\Grupo;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GrupoUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $grupos = Grupo::all();
        $estudiantes = User::role('estudiante')->get();

        if ($grupos->isEmpty() || $estudiantes->isEmpty()) {
            return;
        }

        // Asignar a cada estudiante a 2 o 3 grupos al azar para tener datos poblados
        foreach ($estudiantes as $estudiante) {
            $gruposAsignados = $grupos->random(rand(2, 3));
            
            foreach ($gruposAsignados as $grupo) {
                DB::table('grupo_user')->insert([
                    'grupo_id' => $grupo->id,
                    'user_id' => $estudiante->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
