<?php

namespace App\Http\Controllers;

use App\Models\Alerta;
use App\Models\Estacion;
use App\Models\Horario;
use App\Models\Laboratorio;
use App\Models\Materia;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Get system-wide dashboard statistics.
     */
    public function getStats(Request $request)
    {
        $user = $request->user();
        $hoyNum = date('N'); // 1 (Lunes) a 7 (Domingo)

        if ($user->hasRole('docente')) {
            // --- ROL DOCENTE ---
            
            // 1. Obtener grupos y materias asignadas al docente
            $grupoIds = Horario::where('docente_id', $user->id)->pluck('grupo_id')->unique();
            
            $misMateriasCount = Materia::whereIn('id', function($q) use ($grupoIds) {
                $q->select('materia_id')->from('grupos')->whereIn('id', $grupoIds)->whereNull('deleted_at');
            })->count();
            
            $misGruposCount = $grupoIds->count();
            
            $clasesHoyCount = Horario::where('docente_id', $user->id)
                ->where('dia_semana', $hoyNum)
                ->count();
                
            $laboratoriosAsignadosCount = Horario::where('docente_id', $user->id)
                ->pluck('laboratorio_id')
                ->unique()
                ->count();

            // 2. Horarios del docente para hoy
            $horariosHoy = Horario::with(['laboratorio', 'grupo.materia'])
                ->where('docente_id', $user->id)
                ->where('dia_semana', $hoyNum)
                ->orderBy('hora_inicio', 'asc')
                ->get()
                ->map(function ($horario) {
                    return [
                        'id' => $horario->id,
                        'dia_semana' => $horario->dia_semana,
                        'hora_inicio' => substr($horario->hora_inicio, 0, 5), // 'HH:MM'
                        'hora_fin' => substr($horario->hora_fin, 0, 5),
                        'laboratorio' => $horario->laboratorio ? [
                            'nombre' => $horario->laboratorio->nombre,
                            'pabellon' => $horario->laboratorio->pabellon
                        ] : null,
                        'grupo' => $horario->grupo ? [
                            'nombre' => $horario->grupo->nombre,
                            'materia' => $horario->grupo->materia ? [
                                'nombre' => $horario->grupo->materia->nombre,
                                'codigo' => $horario->grupo->materia->codigo
                            ] : null
                        ] : null
                    ];
                });

            // 3. Estado de laboratorios activos asignados u ocupación general limpia
            $laboratoriosStatus = Laboratorio::where('activo', true)
                ->withCount([
                    'estaciones',
                    'estaciones as estaciones_activas_count' => function ($query) {
                        $query->where('estado', 'activo');
                    }
                ])->get()->map(function ($lab) {
                    return [
                        'id' => $lab->id,
                        'nombre' => $lab->nombre,
                        'pabellon' => $lab->pabellon,
                        'piso' => $lab->piso,
                        'activo' => $lab->activo,
                        'estaciones_count' => $lab->estaciones_count,
                        'estaciones_activas_count' => $lab->estaciones_activas_count,
                    ];
                });

            return response()->json([
                'docente_kpis' => [
                    'mis_materias_count' => $misMateriasCount,
                    'mis_grupos_count' => $misGruposCount,
                    'clases_hoy_count' => $clasesHoyCount,
                    'laboratorios_asignados_count' => $laboratoriosAsignadosCount,
                ],
                'laboratorios' => [
                    'total' => $laboratoriosStatus->count(),
                    'activos' => $laboratoriosStatus->count(),
                    'inactivos' => 0,
                    'status_list' => $laboratoriosStatus,
                ],
                'horarios_hoy' => $horariosHoy,
                // Estructura segura compatible para evitar errores en frontend
                'estaciones' => ['total' => 0, 'activas' => 0, 'inactivas' => 0, 'mantenimiento' => 0, 'desconectadas' => 0],
                'materias' => ['total' => 0],
                'docentes' => ['total' => 0],
                'estudiantes' => ['total' => 0],
                'alertas' => ['totales' => 0, 'pendientes' => 0, 'resueltas' => 0, 'recientes' => []]
            ]);
        }

        if ($user->hasRole('estudiante')) {
            // --- ROL ESTUDIANTE ---
            
            // 1. Obtener grupos inscritos del estudiante
            $grupoIds = \Illuminate\Support\Facades\DB::table('grupo_user')
                ->where('user_id', $user->id)
                ->pluck('grupo_id');
            
            $misMateriasCount = Materia::whereIn('id', function($q) use ($grupoIds) {
                $q->select('materia_id')->from('grupos')->whereIn('id', $grupoIds)->whereNull('deleted_at');
            })->count();
            
            $misGruposCount = $grupoIds->count();
            
            $clasesHoyCount = Horario::whereIn('grupo_id', $grupoIds)
                ->where('dia_semana', $hoyNum)
                ->count();
                
            $laboratoriosActivosCount = Laboratorio::where('activo', true)->count();

            // 2. Horarios del estudiante para hoy
            $horariosHoy = Horario::with(['laboratorio', 'docente', 'grupo.materia'])
                ->whereIn('grupo_id', $grupoIds)
                ->where('dia_semana', $hoyNum)
                ->orderBy('hora_inicio', 'asc')
                ->get()
                ->map(function ($horario) {
                    return [
                        'id' => $horario->id,
                        'dia_semana' => $horario->dia_semana,
                        'hora_inicio' => substr($horario->hora_inicio, 0, 5), // 'HH:MM'
                        'hora_fin' => substr($horario->hora_fin, 0, 5),
                        'laboratorio' => $horario->laboratorio ? [
                            'nombre' => $horario->laboratorio->nombre,
                            'pabellon' => $horario->laboratorio->pabellon
                        ] : null,
                        'docente' => $horario->docente ? [
                            'name' => $horario->docente->name,
                            'email' => $horario->docente->email
                        ] : null,
                        'grupo' => $horario->grupo ? [
                            'nombre' => $horario->grupo->nombre,
                            'materia' => $horario->grupo->materia ? [
                                'nombre' => $horario->grupo->materia->nombre,
                                'codigo' => $horario->grupo->materia->codigo
                            ] : null
                        ] : null
                    ];
                });

            // 3. Estado de laboratorios para estudio libre
            $laboratoriosStatus = Laboratorio::where('activo', true)
                ->withCount([
                    'estaciones',
                    'estaciones as estaciones_activas_count' => function ($query) {
                        $query->where('estado', 'activo');
                    }
                ])->get()->map(function ($lab) {
                    return [
                        'id' => $lab->id,
                        'nombre' => $lab->nombre,
                        'pabellon' => $lab->pabellon,
                        'piso' => $lab->piso,
                        'activo' => $lab->activo,
                        'estaciones_count' => $lab->estaciones_count,
                        'estaciones_activas_count' => $lab->estaciones_activas_count,
                    ];
                });

            return response()->json([
                'estudiante_kpis' => [
                    'mis_materias_count' => $misMateriasCount,
                    'mis_grupos_count' => $misGruposCount,
                    'clases_hoy_count' => $clasesHoyCount,
                    'laboratorios_activos_count' => $laboratoriosActivosCount,
                ],
                'laboratorios' => [
                    'total' => $laboratoriosStatus->count(),
                    'activos' => $laboratoriosStatus->count(),
                    'inactivos' => 0,
                    'status_list' => $laboratoriosStatus,
                ],
                'horarios_hoy' => $horariosHoy,
                // Estructura segura compatible para evitar errores en frontend
                'estaciones' => ['total' => 0, 'activas' => 0, 'inactivas' => 0, 'mantenimiento' => 0, 'desconectadas' => 0],
                'materias' => ['total' => 0],
                'docentes' => ['total' => 0],
                'estudiantes' => ['total' => 0],
                'alertas' => ['totales' => 0, 'pendientes' => 0, 'resueltas' => 0, 'recientes' => []]
            ]);
        }

        // --- ROL ADMINISTRADOR (Por Defecto) ---

        // 1. Conteo de Estaciones por estado
        $estacionesTotal = Estacion::count();
        $estacionesActivas = Estacion::where('estado', 'activo')->count();
        $estacionesInactivas = Estacion::where('estado', 'inactivo')->count();
        $estacionesMantenimiento = Estacion::where('estado', 'mantenimiento')->count();
        $estacionesDesconectadas = Estacion::where('estado', 'desconectado')->count();

        // 2. Conteo de Laboratorios
        $laboratoriosTotal = Laboratorio::count();
        $laboratoriosActivos = Laboratorio::where('activo', true)->count();
        $laboratoriosInactivos = Laboratorio::where('activo', false)->count();

        // 3. Conteo de Materias
        $materiasTotal = Materia::count();

        // 4. Conteo de Usuarios por Rol
        $docentesTotal = User::role('docente')->count();
        $estudiantesTotal = User::role('estudiante')->count();

        // 5. Alertas
        $alertasTotales = Alerta::count();
        $alertasPendientes = Alerta::where('estado', 'pendiente')->count();
        $alertasResueltas = Alerta::where('estado', 'resuelto')->count();

        // Obtener las 5 alertas pendientes más recientes
        $alertasRecientes = Alerta::with(['estacion', 'configuracionAlerta'])
            ->where('estado', 'pendiente')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($alerta) {
                return [
                    'id' => $alerta->id,
                    'estacion_id' => $alerta->estacion_id,
                    'config_alerta_id' => $alerta->config_alerta_id,
                    'valor_actual' => $alerta->valor_actual,
                    'estado' => $alerta->estado,
                    'created_at' => $alerta->created_at,
                    'estacion' => $alerta->estacion ? [
                        'id' => $alerta->estacion->id,
                        'hostname' => $alerta->estacion->hostname,
                        'direccion_ip' => $alerta->estacion->direccion_ip,
                    ] : null,
                    'configuracion_alerta' => $alerta->configuracionAlerta ? [
                        'metrica' => $alerta->configuracionAlerta->metrica,
                        'operador' => $alerta->configuracionAlerta->operador,
                        'valor_umbral' => $alerta->configuracionAlerta->valor_umbral,
                        'severidad' => $alerta->configuracionAlerta->severidad,
                    ] : null,
                ];
            });

        // 6. Estado de salud por Laboratorio
        $laboratoriosStatus = Laboratorio::withCount([
            'estaciones',
            'estaciones as estaciones_activas_count' => function ($query) {
                $query->where('estado', 'activo');
            }
        ])->get()->map(function ($lab) {
            return [
                'id' => $lab->id,
                'nombre' => $lab->nombre,
                'pabellon' => $lab->pabellon,
                'piso' => $lab->piso,
                'activo' => $lab->activo,
                'estaciones_count' => $lab->estaciones_count,
                'estaciones_activas_count' => $lab->estaciones_activas_count,
            ];
        });

        // 7. Horarios programados para hoy
        $horariosHoy = Horario::with(['laboratorio', 'docente', 'grupo.materia'])
            ->where('dia_semana', $hoyNum)
            ->orderBy('hora_inicio', 'asc')
            ->get()
            ->map(function ($horario) {
                return [
                    'id' => $horario->id,
                    'dia_semana' => $horario->dia_semana,
                    'hora_inicio' => substr($horario->hora_inicio, 0, 5), // 'HH:MM'
                    'hora_fin' => substr($horario->hora_fin, 0, 5),
                    'laboratorio' => $horario->laboratorio ? [
                        'nombre' => $horario->laboratorio->nombre,
                        'pabellon' => $horario->laboratorio->pabellon
                    ] : null,
                    'docente' => $horario->docente ? [
                        'name' => $horario->docente->name,
                        'email' => $horario->docente->email
                    ] : null,
                    'grupo' => $horario->grupo ? [
                        'nombre' => $horario->grupo->nombre,
                        'materia' => $horario->grupo->materia ? [
                            'nombre' => $horario->grupo->materia->nombre,
                            'codigo' => $horario->grupo->materia->codigo
                        ] : null
                    ] : null
                ];
            });

        return response()->json([
            'estaciones' => [
                'total' => $estacionesTotal,
                'activas' => $estacionesActivas,
                'inactivas' => $estacionesInactivas,
                'mantenimiento' => $estacionesMantenimiento,
                'desconectadas' => $estacionesDesconectadas,
            ],
            'laboratorios' => [
                'total' => $laboratoriosTotal,
                'activos' => $laboratoriosActivos,
                'inactivos' => $laboratoriosInactivos,
                'status_list' => $laboratoriosStatus,
            ],
            'materias' => [
                'total' => $materiasTotal,
            ],
            'docentes' => [
                'total' => $docentesTotal,
            ],
            'estudiantes' => [
                'total' => $estudiantesTotal,
            ],
            'alertas' => [
                'totales' => $alertasTotales,
                'pendientes' => $alertasPendientes,
                'resueltas' => $alertasResueltas,
                'recientes' => $alertasRecientes,
            ],
            'horarios_hoy' => $horariosHoy,
        ]);
    }
}
