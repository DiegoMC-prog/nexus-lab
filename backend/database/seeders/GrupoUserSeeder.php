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

        // Insertar exactamente 10 relaciones en la tabla pivot `grupo_user`
        $relationsCount = 0;
        $estudiantesCount = $estudiantes->count();

        foreach ($grupos as $grupo) {
            if ($relationsCount >= 10) {
                break;
            }

            // Asignar el estudiante actual de la rotación a este grupo
            $estudiante = $estudiantes->get($relationsCount % $estudiantesCount);

            DB::table('grupo_user')->insert([
                'grupo_id' => $grupo->id,
                'user_id' => $estudiante->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $relationsCount++;
        }

        // Si por alguna razón hay menos de 10 grupos y no se completaron 10 registros
        while ($relationsCount < 10) {
            $grupo = $grupos->random();
            $estudiante = $estudiantes->random();

            // Evitar duplicados en la pivot si ya existe la combinación
            $exists = DB::table('grupo_user')
                ->where('grupo_id', $grupo->id)
                ->where('user_id', $estudiante->id)
                ->exists();

            if (!$exists) {
                DB::table('grupo_user')->insert([
                    'grupo_id' => $grupo->id,
                    'user_id' => $estudiante->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $relationsCount++;
            }
        }
    }
}
