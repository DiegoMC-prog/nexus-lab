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
            SemestreSeeder::class,
            CarreraSeeder::class,
            UserSeeder::class,
            MateriaSeeder::class,
            GrupoSeeder::class,
            GrupoUserSeeder::class,
            LaboratorioSeeder::class,
            HorarioSeeder::class,
            EstacionSeeder::class,
            PerfilHardwareSeeder::class,
            BiometriaVocalSeeder::class,
            LogsTelemetriaSeeder::class,
            ConfigAlertaSeeder::class,
            AlertaSeeder::class,
            ComandoSeeder::class,
            LogsComandoSeeder::class,
            // DispositivoConocidoSeeder::class,
            // BiometriaFacialSeeder::class,
            PerfilSeeder::class,
        ]);
    }
}
