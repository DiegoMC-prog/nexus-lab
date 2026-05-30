<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolSeeder extends Seeder
{
    public function run(): void
    {
        // Limpiar caché de permisos de Spatie antes de regenerar
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // -------------------------------------------------------
        // 1. CREAR ROLES
        // -------------------------------------------------------
        $admin     = Role::firstOrCreate(['name' => 'admin']);
        $docente   = Role::firstOrCreate(['name' => 'docente']);
        $estudiante = Role::firstOrCreate(['name' => 'estudiante']);

        // -------------------------------------------------------
        // 2. DEFINIR TODOS LOS MÓDULOS Y ACCIONES DEL SISTEMA
        // -------------------------------------------------------
        $modulos = [
            'dashboard',
            'usuarios',
            'roles',
            'laboratorios',
            'estaciones',
            'horarios',
            'materias',
            'grupos',
            'carreras',
            'semestres',
            'monitoreo',
            'alertas',
            'comandos',
        ];

        $acciones = ['ver', 'crear', 'editar', 'eliminar'];

        // Crear todos los permisos y asignárselos al admin
        foreach ($modulos as $modulo) {
            foreach ($acciones as $accion) {
                $permisoName = $modulo . '.' . $accion;
                $permiso = Permission::firstOrCreate(['name' => $permisoName]);
                $admin->givePermissionTo($permiso);
            }
        }

        // -------------------------------------------------------
        // 3. PERMISOS DEL DOCENTE
        // Puede ver horarios, laboratorios, estaciones, materias,
        // grupos, carreras, semestres, dashboard y alertas.
        // -------------------------------------------------------
        $permisosDocente = [
            'dashboard.ver',
            'horarios.ver',
            'laboratorios.ver',
            'estaciones.ver',
            'materias.ver',
            'grupos.ver',
            'carreras.ver',
            'semestres.ver',
            'monitoreo.ver',
            'alertas.ver',
        ];

        foreach ($permisosDocente as $p) {
            $docente->givePermissionTo(Permission::firstOrCreate(['name' => $p]));
        }

        // -------------------------------------------------------
        // 4. PERMISOS DEL ESTUDIANTE
        // Solo puede ver horarios, grupos, materias, carreras y dashboard.
        // -------------------------------------------------------
        $permisosEstudiante = [
            'dashboard.ver',
            'horarios.ver',
            'grupos.ver',
            'materias.ver',
            'carreras.ver',
            'semestres.ver',
        ];

        foreach ($permisosEstudiante as $p) {
            $estudiante->givePermissionTo(Permission::firstOrCreate(['name' => $p]));
        }
    }
}
