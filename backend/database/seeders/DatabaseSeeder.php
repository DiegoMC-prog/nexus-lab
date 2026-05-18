<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();
        $this->call([
            RolSeeder::class,
        ]);

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
    }
}
