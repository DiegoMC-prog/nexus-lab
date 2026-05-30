<?php

namespace Database\Seeders;

use App\Models\Comando;
use App\Models\Estacion;
use App\Models\LogsComando;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LogsComandoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $usuarios = User::all();
        $estaciones = Estacion::all();
        $comandos = Comando::all();

        if ($usuarios->isEmpty() || $estaciones->isEmpty() || $comandos->isEmpty()) {
            return;
        }

        $usuariosCount = $usuarios->count();
        $comandosCount = $comandos->count();

        // Crear 10 logs de comandos ejecutados
        for ($i = 0; $i < 10; $i++) {
            $usuario = $usuarios->get($i % $usuariosCount);
            $estacion = $estaciones->get($i % $estaciones->count());
            $comando = $comandos->get($i % $comandosCount);

            $estado = $i % 3 === 0 ? 'exitoso' : ($i % 3 === 1 ? 'pendiente' : 'fallido');
            $mensajeRespuesta = $estado === 'exitoso' 
                ? 'Comando "' . $comando->nombre . '" ejecutado con éxito en la estación.'
                : ($estado === 'fallido' ? 'Error: Tiempo de espera agotado al conectar con el agente.' : 'Comando encolado, esperando respuesta del agente.');

            LogsComando::create([
                'usuario_id' => $usuario->id,
                'estacion_id' => $estacion->id,
                'comando_id' => $comando->id,
                'origen' => $i % 2 === 0 ? 'web_admin' : 'api_gateway',
                'estado' => $estado,
                'mensaje_respuesta' => $mensajeRespuesta,
            ]);
        }
    }
}
