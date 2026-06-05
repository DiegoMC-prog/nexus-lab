<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Evaluador Administrador (Requerido por Rúbrica)
        $adminRubrica = User::factory()->create([
            'name' => 'Admin Evaluador',
            'email' => 'admin@prueba.com',
            'password' => Hash::make('Admin123!'),
            'totp_secret' => 'JBSWY3DPEHPK3PXP',
        ]);
        $adminRubrica->assignRole('admin');

        // Evaluador Usuario Regular (Requerido por Rúbrica)
        $userRubrica = User::factory()->create([
            'name' => 'Usuario Evaluador',
            'email' => 'user@prueba.com',
            'password' => Hash::make('User123!'),
            'totp_secret' => 'KNRW24TMMJQXEZLJ',
        ]);
        // Asignamos rol docente (usuario regular del sistema)
        $userRubrica->assignRole('docente');

        // Administrador Original
        $user = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'diegomarca736@gmail.com',
            'password' => Hash::make('password'),
        ]);
        $user->assignRole('admin');

        // 3 Docentes (1 base + 2 extras)
        $docenteBase = User::factory()->create([
            'name' => 'Docente Principal',
            'email' => 'rmcu987@gmail.com',
            'password' => Hash::make('docente123'),
        ]);
        $docenteBase->assignRole('docente');

        for ($i = 2; $i <= 3; $i++) {
            $docente = User::factory()->create([
                'name' => 'Docente Extra ' . $i,
                'email' => 'docente' . $i . '@example.com',
                'password' => Hash::make('docente123'),
            ]);
            $docente->assignRole('docente');
        }

        // 30 Estudiantes (1 base + 29 extras)
        $estudianteBase = User::factory()->create([
            'name' => 'Estudiante Base',
            'email' => 'estudiante@gmail.com',
            'password' => Hash::make('estudiante123'),
        ]);
        $estudianteBase->assignRole('estudiante');

        $estudiantes = User::factory(29)->create();
        foreach ($estudiantes as $est) {
            $est->assignRole('estudiante');
        }
    }
}
