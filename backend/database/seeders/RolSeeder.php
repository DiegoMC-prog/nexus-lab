<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = Role::create(['name' => 'admin']);
        Role::create(['name' => 'docente']);
        Role::create(['name' => 'estudiante']);

        $modulos = ['usuarios', 'roles', 'horarios', 'laboratorios', 'monitoreo'];
        $acciones = ['ver', 'crear', 'editar', 'eliminar'];

        foreach ($modulos as $modulo) {
            foreach ($acciones as $accion) {
                $permisoName = $modulo . '.' . $accion;
                Permission::firstOrCreate([
                    'name' => $permisoName
                ]);
                $admin->givePermissionTo($permisoName);
            }
        }
    }
}
