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
        // 3 usuarios obligatorios solicitados
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);
        $user->assignRole('admin');

        $user = User::factory()->create([
            'name' => 'docente',
            'email' => 'docente@gmail.com',
            'password' => Hash::make('docente123'),
        ]);
        $user->assignRole('docente');

        $user = User::factory()->create([
            'name' => 'estudiante',
            'email' => 'estudiante@gmail.com',
            'password' => Hash::make('estudiante123'),
        ]);
        $user->assignRole('estudiante');

        // 7 usuarios adicionales para completar 10 usuarios
        // 2 Docentes adicionales
        for ($i = 2; $i <= 3; $i++) {
            $docente = User::factory()->create([
                'name' => 'Docente Extra ' . $i,
                'email' => 'docente' . $i . '@example.com',
                'password' => Hash::make('password123'),
            ]);
            $docente->assignRole('docente');
        }

        // 5 Estudiantes adicionales
        for ($i = 2; $i <= 6; $i++) {
            $estudiante = User::factory()->create([
                'name' => 'Estudiante Extra ' . $i,
                'email' => 'estudiante' . $i . '@example.com',
                'password' => Hash::make('password123'),
            ]);
            $estudiante->assignRole('estudiante');
        }
    }
}
