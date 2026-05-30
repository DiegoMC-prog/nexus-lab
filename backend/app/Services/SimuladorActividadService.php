<?php

namespace App\Services;

use App\Models\Estacion;
use App\Models\Horario;
use App\Models\LogAplicacionProhibida;
use App\Models\LogsTelemetria;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class SimuladorActividadService
{
    /**
     * Lista de aplicaciones prohibidas por defecto para inicializar la base de datos si está vacía.
     */
    protected array $appsProhibidasDefault = [
        [
            'nombre_aplicacion' => 'Steam Client',
            'nombre_proceso' => 'steam.exe',
            'tipo_restriccion' => 'bloqueo_total'
        ],
        [
            'nombre_aplicacion' => 'Discord Chat',
            'nombre_proceso' => 'discord.exe',
            'tipo_restriccion' => 'bloqueo_total'
        ],
        [
            'nombre_aplicacion' => 'Spotify Music',
            'nombre_proceso' => 'spotify.exe',
            'tipo_restriccion' => 'bloqueo_total'
        ],
        [
            'nombre_aplicacion' => 'uTorrent Client',
            'nombre_proceso' => 'utorrent.exe',
            'tipo_restriccion' => 'bloqueo_total'
        ],
        [
            'nombre_aplicacion' => 'Google Chrome Incógnito',
            'nombre_proceso' => 'chrome_incognito',
            'tipo_restriccion' => 'bloqueo_total'
        ],
    ];

    /**
     * Mapeo de rutas de ejecutables simuladas para los logs.
     */
    protected array $rutasEjecutables = [
        'steam.exe' => 'C:\\Program Files (x86)\\Steam\\steam.exe',
        'discord.exe' => 'C:\\Users\\Estudiante\\AppData\\Local\\Discord\\app-1.0.9001\\discord.exe',
        'spotify.exe' => 'C:\\Users\\Estudiante\\AppData\\Local\\Microsoft\\WindowsApps\\Spotify.exe',
        'utorrent.exe' => 'C:\\Users\\Estudiante\\AppData\\Roaming\\uTorrent\\uTorrent.exe',
        'chrome_incognito' => 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe --incognito'
    ];

    /**
     * Ejecuta el proceso de simulación para todos los horarios activos en este instante.
     *
     * @return array Resumen del proceso ejecutado.
     */
    public function ejecutarSimulacion(): array
    {
        $horariosActivos = $this->detectarHorariosActivos();

        if ($horariosActivos->isEmpty()) {
            return [
                'status' => 'idle',
                'message' => 'No existen horarios académicos activos en este momento.',
                'clases_simuladas' => 0
            ];
        }

        $clasesSimuladas = [];
        $estudiantes = $this->obtenerEstudiantes();

        foreach ($horariosActivos as $horario) {
            $this->simularParaHorario($horario, $estudiantes);
            $clasesSimuladas[] = [
                'horario_id' => $horario->id,
                'laboratorio' => $horario->laboratorio?->nombre ?? 'N/A',
                'grupo' => $horario->grupo?->nombre ?? 'N/A',
                'materia' => $horario->grupo?->materia?->nombre ?? 'N/A',
            ];
        }

        return [
            'status' => 'success',
            'message' => 'Simulación de actividad ejecutada con éxito para los laboratorios activos.',
            'clases_simuladas' => count($clasesSimuladas),
            'detalles' => $clasesSimuladas
        ];
    }

    /**
     * Detecta todos los horarios activos en la fecha, día de la semana y hora actual.
     */
    public function detectarHorariosActivos()
    {
        $hoyNum = date('N'); // 1 (Lunes) a 7 (Domingo)
        $currentTime = now()->format('H:i:s');
        $currentDate = now()->toDateString();

        return Horario::with(['laboratorio', 'grupo.materia'])
            ->where('dia_semana', $hoyNum)
            ->where('hora_inicio', '<=', $currentTime)
            ->where('hora_fin', '>=', $currentTime)
            ->where('fecha_inicio', '<=', $currentDate)
            ->where('fecha_fin', '>=', $currentDate)
            ->get();
    }

    /**
     * Asegura que existan restricciones en la base de datos para simular.
     * Si está vacía la tabla, insertamos algunas por defecto globales.
     */
    protected function asegurarRestriccionesExistentes(): void
    {
        if (\App\Models\RestriccionAplicacion::count() === 0) {
            foreach ($this->appsProhibidasDefault as $app) {
                \App\Models\RestriccionAplicacion::create([
                    'laboratorio_id' => null, // Global
                    'nombre_aplicacion' => $app['nombre_aplicacion'],
                    'nombre_proceso' => $app['nombre_proceso'],
                    'tipo_restriccion' => $app['tipo_restriccion'],
                    'activo' => true
                ]);
            }
        }
    }

    /**
     * Simula la actividad (latidos, telemetría e infracciones) para las estaciones de un horario específico.
     */
    protected function simularParaHorario(Horario $horario, $estudiantes): void
    {
        $laboratorioId = $horario->laboratorio_id;

        // Asegurar que existan estaciones en el laboratorio (mínimo 12 de simulación)
        $estacionesCount = Estacion::where('laboratorio_id', $laboratorioId)->count();
        $stationLimit = 12;

        if ($estacionesCount < $stationLimit) {
            for ($i = $estacionesCount + 1; $i <= $stationLimit; $i++) {
                Estacion::create([
                    'laboratorio_id' => $laboratorioId,
                    'uuid' => (string) Str::uuid(),
                    'hostname' => "LAB-" . Str::padLeft($laboratorioId, 2, '0') . "-PC" . Str::padLeft($i, 2, '0'),
                    'direccion_mac' => "00:1A:3F:8A:BC:" . Str::padLeft(dechex($i + 32), 2, '0', STR_PAD_LEFT),
                    'direccion_ip' => "192.168.{$laboratorioId}." . (10 + $i),
                    'so_info' => $i % 4 === 0 ? 'Ubuntu 22.04 LTS (x64)' : 'Windows 11 Professional (x64) Build 22631',
                    'estado' => 'activo',
                    'version_agente' => 'v2.1.0-wpf',
                    'ultima_conexion' => now()
                ]);
            }
        }

        // Recargar estaciones del laboratorio
        $estaciones = Estacion::where('laboratorio_id', $laboratorioId)->get();

        // Asegurar que existan restricciones activas en la base de datos
        $this->asegurarRestriccionesExistentes();

        // Obtener restricciones activas aplicables a este laboratorio (globales o específicas)
        $restricciones = \App\Models\RestriccionAplicacion::where('activo', true)
            ->where(function($query) use ($laboratorioId) {
                $query->whereNull('laboratorio_id')
                      ->orWhere('laboratorio_id', $laboratorioId);
            })
            ->get();

        foreach ($estaciones as $estacion) {
            $isBlocked = $estacion->estado === 'bloqueado';
            $estudianteId = $estacion->estudiante_actual_id;

            if ($isBlocked) {
                // Si la estación está bloqueada por el docente, preservamos su estado e inmovilidad,
                // pero actualizamos su latido para simular que el servicio Nexus sigue activo de fondo.
                $estacion->update([
                    'ultima_conexion' => now()
                ]);
            } else {
                // Simulación inteligente de presencia de estudiantes (evita que salten de PC en PC cada minuto)
                $tieneEstudiante = $estudianteId !== null;

                if (!$tieneEstudiante) {
                    // Si el PC está libre, hay un 75% de probabilidad de que ingrese un nuevo alumno
                    if (rand(1, 100) <= 75 && $estudiantes->isNotEmpty()) {
                        // Intentar obtener un estudiante que no esté sentado ya en este laboratorio
                        $estudiantesOcupados = Estacion::where('laboratorio_id', $laboratorioId)
                            ->whereNotNull('estudiante_actual_id')
                            ->pluck('estudiante_actual_id')
                            ->toArray();

                        $disponibles = $estudiantes->reject(fn($u) => in_array($u->id, $estudiantesOcupados));
                        if ($disponibles->isNotEmpty()) {
                            $estudianteId = $disponibles->random()->id;
                        } else {
                            $estudianteId = $estudiantes->random()->id;
                        }
                    }
                } else {
                    // Si ya hay un estudiante en el PC, hay un 5% de probabilidad de que cierre sesión en este minuto
                    if (rand(1, 100) <= 5) {
                        $estudianteId = null;
                    }
                }

                $estacion->update([
                    'ultima_conexion' => now(),
                    'estudiante_actual_id' => $estudianteId,
                    'estado' => 'activo'
                ]);
            }

            // Generar un nuevo registro de telemetría remota realista
            LogsTelemetria::create([
                'estacion_id' => $estacion->id,
                'carga_cpu' => rand(5, 95),
                'uso_ram_mb' => rand(1600, 7800), // 1.6GB a 7.8GB
                'temp_cpu' => rand(40, 78),
                'uso_disco' => rand(15, 80),
                'latencia_red' => rand(4, 75)
            ]);

            // Simular Alertas de Infracción (10% de probabilidad por minuto para estaciones activas no bloqueadas)
            if (!$isBlocked && $estudianteId !== null && $restricciones->isNotEmpty() && rand(1, 100) <= 10) {
                // Obtener una restricción de aplicación prohibida aleatoria de la base de datos
                $restriccion = $restricciones->random();
                $nombreProceso = $restriccion->nombre_proceso;
                $ruta = $this->rutasEjecutables[$nombreProceso] ?? "C:\\Program Files\\Simulado\\" . $nombreProceso;

                LogAplicacionProhibida::create([
                    'estacion_id' => $estacion->id,
                    'usuario_id' => $estudianteId,
                    'nombre_proceso' => $nombreProceso,
                    'ruta_ejecutable' => $ruta,
                    'accion_tomada' => 'Proceso Bloqueado y Cerrado'
                ]);

                Log::warning("Scheduler simuló alerta de seguridad desde la base de datos (Restricciones): {$nombreProceso} bloqueado en {$estacion->hostname}.");
            }
        }
    }

    /**
     * Obtiene todos los estudiantes registrados o crea de demostración si es necesario.
     */
    protected function obtenerEstudiantes()
    {
        $estudiantes = User::role('estudiante')->get();

        if ($estudiantes->isEmpty()) {
            $nombresDemos = ["Diego Torres", "Andrea Rojas", "Carlos Mendoza", "Fernanda Ortiz", "Gael Castillo", "Hugo Romero", "Irene Vega", "Jorge Luna", "Karen Cruz", "Luis Silva", "Monica Herrera", "Nelson Vargas"];
            foreach ($nombresDemos as $k => $nombre) {
                $est = User::create([
                    'name' => $nombre,
                    'email' => 'estudiante.scheduler' . ($k + 1) . '@example.com',
                    'password' => bcrypt('password123'),
                ]);
                $est->assignRole('estudiante');
            }
            $estudiantes = User::role('estudiante')->get();
        }

        return $estudiantes;
    }
}
