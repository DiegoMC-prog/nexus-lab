<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
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
            RestriccionAplicacionSeeder::class,
            EstacionSeeder::class,
            PerfilHardwareSeeder::class,
            // BiometriaVocalSeeder::class,
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
