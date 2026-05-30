<?php

namespace Database\Seeders;

use App\Models\Comando;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ComandoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $comandos = [
            [
                'nombre' => 'Apagar Equipo',
                'slug' => 'apagar-equipo',
                'tipo' => 'sistema',
                'require_auth' => true
            ],
            [
                'nombre' => 'Reiniciar Equipo',
                'slug' => 'reiniciar-equipo',
                'tipo' => 'sistema',
                'require_auth' => true
            ],
            [
                'nombre' => 'Bloquear Pantalla',
                'slug' => 'bloquear-pantalla',
                'tipo' => 'seguridad',
                'require_auth' => false
            ],
            [
                'nombre' => 'Captura de Pantalla',
                'slug' => 'captura-pantalla',
                'tipo' => 'monitoreo',
                'require_auth' => true
            ],
            [
                'nombre' => 'Enviar Mensaje Emergente',
                'slug' => 'enviar-mensaje',
                'tipo' => 'notificacion',
                'require_auth' => false
            ],
            [
                'nombre' => 'Actualizar Agente',
                'slug' => 'actualizar-agente',
                'tipo' => 'mantenimiento',
                'require_auth' => true
            ],
            [
                'nombre' => 'Cerrar Sesión Activa',
                'slug' => 'cerrar-sesion',
                'tipo' => 'sistema',
                'require_auth' => true
            ],
            [
                'nombre' => 'Obtener Lista de Procesos',
                'slug' => 'obtener-procesos',
                'tipo' => 'monitoreo',
                'require_auth' => false
            ],
            [
                'nombre' => 'Bloquear Acceso Internet',
                'slug' => 'bloquear-internet',
                'tipo' => 'seguridad',
                'require_auth' => true
            ],
            [
                'nombre' => 'Desbloquear Acceso Internet',
                'slug' => 'desbloquear-internet',
                'tipo' => 'seguridad',
                'require_auth' => true
            ],
        ];

        foreach ($comandos as $comando) {
            Comando::create($comando);
        }
    }
}
