<?php

namespace App\Http\Controllers;

use App\Models\Estacion;
use App\Models\Horario;
use App\Models\Materia;
use App\Models\LogAplicacionProhibida;
use Illuminate\Http\Request;

class DocenteDashboardController extends Controller
{
    /**
     * Obtiene estadísticas de KPIs y el cronograma de hoy del docente autenticado.
     */
    public function getHomeDashboard(Request $request)
    {
        $user = $request->user();
        $hoyNum = date('N'); // 1 (Lunes) a 7 (Domingo)

        // 1. Materias Asignadas (Únicas vinculadas a los grupos del docente en horarios)
        $grupoIds = Horario::where('docente_id', $user->id)->pluck('grupo_id')->unique();
        $materiasCount = Materia::whereIn('id', function($q) use ($grupoIds) {
            $q->select('materia_id')->from('grupos')->whereIn('id', $grupoIds)->whereNull('deleted_at');
        })->count();

        // 2. Grupos a Cargo
        $gruposCount = $grupoIds->count();

        // 3. Clases de Hoy
        $clasesHoyCount = Horario::where('docente_id', $user->id)
            ->where('dia_semana', $hoyNum)
            ->count();

        // 4. Laboratorios Reservados (Diferentes laboratorios asignados al docente)
        $laboratoriosReservadosCount = Horario::where('docente_id', $user->id)
            ->pluck('laboratorio_id')
            ->unique()
            ->filter()
            ->count();

        // 5. Cronograma de Hoy ordenado por hora_inicio
        $cronogramaHoy = Horario::with(['laboratorio', 'grupo.materia'])
            ->where('docente_id', $user->id)
            ->where('dia_semana', $hoyNum)
            ->orderBy('hora_inicio', 'asc')
            ->get()
            ->map(function ($h) {
                return [
                    'id' => $h->id,
                    'hora_inicio' => substr($h->hora_inicio, 0, 5), // 'HH:MM'
                    'hora_fin' => substr($h->hora_fin, 0, 5),
                    'materia' => $h->grupo?->materia ? [
                        'nombre' => $h->grupo->materia->nombre,
                        'codigo' => $h->grupo->materia->codigo,
                    ] : null,
                    'grupo' => $h->grupo ? [
                        'nombre' => $h->grupo->nombre,
                    ] : null,
                    'laboratorio' => $h->laboratorio ? [
                        'id' => $h->laboratorio->id,
                        'nombre' => $h->laboratorio->nombre,
                        'pabellon' => $h->laboratorio->pabellon,
                        'piso' => $h->laboratorio->piso,
                    ] : null,
                ];
            });

        return response()->json([
            'kpis' => [
                'materias_asignadas' => $materiasCount,
                'grupos_a_cargo' => $gruposCount,
                'clases_hoy' => $clasesHoyCount,
                'laboratorios_reservados' => $laboratoriosReservadosCount,
            ],
            'cronograma_hoy' => $cronogramaHoy
        ], 200);
    }

    /**
     * Monitoreo en tiempo real de la clase actualmente activa.
     */
    public function getClaseActivaRealTime(Request $request)
    {
        $user = $request->user();
        $currentTime = date('H:i:s');
        $hoyNum = date('N');

        // Determinar si el docente tiene una clase activa en este instante
        $claseActiva = Horario::with(['laboratorio', 'grupo.materia'])
            ->where('docente_id', $user->id)
            ->where('dia_semana', $hoyNum)
            ->where('hora_inicio', '<=', $currentTime)
            ->where('hora_fin', '>=', $currentTime)
            ->first();

        if (!$claseActiva) {
            return response()->json([
                'clase_activa' => false,
                'message' => 'No tienes ninguna clase en curso en este momento.'
            ], 200);
        }

        // 1. Obtener PCs (Estaciones) del laboratorio asignado
        $estaciones = Estacion::with(['estudianteActual:id,name', 'ultimaTelemetria'])
            ->where('laboratorio_id', $claseActiva->laboratorio_id)
            ->orderBy('hostname', 'asc')
            ->get()
            ->map(function ($estacion) {
                // Online si su última conexión es menor a 5 minutos, de lo contrario Offline
                $isOnline = $estacion->ultima_conexion && $estacion->ultima_conexion >= now()->subMinutes(5);

                // Si está bloqueada en la base de datos, mantenemos su estado como bloqueado
                $estadoFinal = $estacion->estado === 'bloqueado' ? 'bloqueado' : ($isOnline ? 'Online' : 'Offline');

                return [
                    'id' => $estacion->id,
                    'uuid' => $estacion->uuid,
                    'hostname' => $estacion->hostname,
                    'direccion_ip' => $estacion->direccion_ip,
                    'so_info' => $estacion->so_info,
                    'estado' => $estadoFinal,
                    'estudiante' => $estacion->estudianteActual ? [
                        'name' => $estacion->estudianteActual->name
                    ] : null,
                    'telemetria' => $estacion->ultimaTelemetria ? [
                        'carga_cpu' => $estacion->ultimaTelemetria->carga_cpu,
                        'uso_ram_mb' => $estacion->ultimaTelemetria->uso_ram_mb,
                    ] : null,
                ];
            });

        // 2. Obtener infracciones ocurridas en este laboratorio durante la clase hoy
        $estacionesIds = Estacion::where('laboratorio_id', $claseActiva->laboratorio_id)->pluck('id');
        
        $infracciones = LogAplicacionProhibida::with(['estacion:id,hostname', 'usuario:id,name'])
            ->whereIn('estacion_id', $estacionesIds)
            ->whereBetween('created_at', [
                now()->toDateString() . ' ' . $claseActiva->hora_inicio,
                now()->toDateString() . ' ' . $claseActiva->hora_fin
            ])
            ->latest('created_at')
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'estacion' => $log->estacion ? [
                        'hostname' => $log->estacion->hostname,
                    ] : null,
                    'usuario' => $log->usuario ? [
                        'name' => $log->usuario->name,
                    ] : null,
                    'nombre_proceso' => $log->nombre_proceso,
                    'ruta_ejecutable' => $log->ruta_ejecutable,
                    'accion_tomada' => $log->accion_tomada,
                    'created_at' => $log->created_at->toDateTimeString(),
                ];
            });

        return response()->json([
            'clase_activa' => true,
            'grupo' => $claseActiva->grupo ? [
                'id' => $claseActiva->grupo->id,
                'nombre' => $claseActiva->grupo->nombre,
                'materia' => $claseActiva->grupo->materia ? [
                    'nombre' => $claseActiva->grupo->materia->nombre,
                    'codigo' => $claseActiva->grupo->materia->codigo,
                ] : null,
            ] : null,
            'laboratorio' => [
                'id' => $claseActiva->laboratorio->id,
                'nombre' => $claseActiva->laboratorio->nombre,
                'pabellon' => $claseActiva->laboratorio->pabellon,
                'piso' => $claseActiva->laboratorio->piso,
            ],
            'hora_inicio' => substr($claseActiva->hora_inicio, 0, 5),
            'hora_fin' => substr($claseActiva->hora_fin, 0, 5),
            'estaciones' => $estaciones,
            'infracciones' => $infracciones
        ], 200);
    }

    /**
     * Ejecuta una acción remota (bloquear, desbloquear, cerrar sesión) sobre una estación específica.
     */
    public function ejecutarAccionEstacion(Request $request, $id)
    {
        $request->validate([
            'accion' => 'required|in:bloquear,desbloquear,cerrar_sesion',
        ]);

        $accion = $request->accion;
        $estacion = Estacion::findOrFail($id);

        // Buscar o crear el comando correspondiente en el catálogo
        $comando = \App\Models\Comando::where('slug', $accion)->first();
        if (!$comando) {
            $comando = \App\Models\Comando::create([
                'nombre' => ucfirst(str_replace('_', ' ', $accion)),
                'slug' => $accion,
                'tipo' => 'sistema',
                'require_auth' => false,
            ]);
        }

        // Registrar la orden en el historial de logs de comandos
        \App\Models\LogsComando::create([
            'estacion_id' => $estacion->id,
            'usuario_id' => $request->user()->id,
            'comando_id' => $comando->id,
            'estado' => 'ejecutado',
            'origen' => 'docente_dashboard',
            'mensaje_respuesta' => 'Acción "' . $accion . '" transmitida exitosamente al agente Nexus de ' . $estacion->hostname . '.',
        ]);

        // Simulación: Cambiamos el estado de la estación de acuerdo a la acción remota solicitada
        if ($accion === 'cerrar_sesion') {
            $estacion->update([
                'estudiante_actual_id' => null,
                'estado' => 'activo',
            ]);
        } elseif ($accion === 'bloquear') {
            $estacion->update([
                'estado' => 'bloqueado',
            ]);
        } elseif ($accion === 'desbloquear') {
            $estacion->update([
                'estado' => 'activo',
            ]);
        }

        return response()->json([
            'message' => 'Acción "' . $accion . '" enviada correctamente a la estación ' . $estacion->hostname . '.',
            'estacion_id' => $estacion->id,
            'accion' => $accion,
        ], 200);
    }
}

