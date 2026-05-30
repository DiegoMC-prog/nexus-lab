<?php

namespace App\Http\Controllers;

use App\Http\Requests\Restriccion\StoreRestriccionRequest;
use App\Http\Requests\Restriccion\UpdateRestriccionRequest;
use App\Models\RestriccionAplicacion;
use App\Models\LogAplicacionProhibida;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Override;

class RestriccionAplicacionController extends Controller implements HasMiddleware
{
    /**
     * Definición de Middleware del controlador (Laravel 11/12/13 style).
     */
    #[Override]
    public static function middleware()
    {
        return [
            new Middleware('permission:manage-restrictions', except: ['reportViolation']),
        ];
    }

    /**
     * Display a listing of the resource (CRUD Index).
     */
    public function index(Request $request)
    {
        $query = RestriccionAplicacion::query()->with('laboratorio:id,nombre');

        // Búsqueda por nombre de aplicación
        $query->when($request->filled('search'), function ($q) use ($request) {
            $search = $request->search;
            $q->where(function ($subQuery) use ($search) {
                $subQuery->where('nombre_aplicacion', 'ILIKE', "%{$search}%")
                    ->orWhere('nombre_proceso', 'ILIKE', "%{$search}%");
            });
        });

        // Filtrado por laboratorio
        $query->when($request->filled('laboratorio_id') && $request->laboratorio_id !== 'all', function ($q) use ($request) {
            if ($request->laboratorio_id === 'global') {
                $q->whereNull('laboratorio_id');
            } else {
                $q->where('laboratorio_id', $request->laboratorio_id);
            }
        });

        $restricciones = $query->latest()->paginate(10);

        return response()->json([
            'restricciones' => $restricciones,
        ]);
    }

    /**
     * Store a newly created resource in storage (CRUD Store).
     */
    public function store(StoreRestriccionRequest $request)
    {
        $data = $request->validated();

        $restriccion = RestriccionAplicacion::create([
            'laboratorio_id' => $data['laboratorio_id'] ?? null,
            'nombre_aplicacion' => $data['nombre_aplicacion'],
            'nombre_proceso' => $data['nombre_proceso'],
            'tipo_restriccion' => $data['tipo_restriccion'] ?? 'bloqueo_total',
            'activo' => isset($data['activo']) ? (bool) $data['activo'] : true,
        ]);

        $restriccion->load('laboratorio:id,nombre');

        return response()->json([
            'message' => 'Restricción de aplicación creada correctamente.',
            'restriccion' => $restriccion,
        ], 201);
    }

    /**
     * Display the specified resource (CRUD Show).
     */
    public function show(RestriccionAplicacion $restriccionAplicacion)
    {
        $restriccionAplicacion->load('laboratorio:id,nombre');

        return response()->json([
            'restriccion' => $restriccionAplicacion,
        ]);
    }

    /**
     * Update the specified resource in storage (CRUD Update).
     */
    public function update(UpdateRestriccionRequest $request, RestriccionAplicacion $restriccionAplicacion)
    {
        $data = $request->validated();

        $restriccionAplicacion->update($data);
        $restriccionAplicacion->refresh();
        $restriccionAplicacion->load('laboratorio:id,nombre');

        return response()->json([
            'message' => 'Restricción de aplicación actualizada correctamente.',
            'restriccion' => $restriccionAplicacion,
        ]);
    }

    /**
     * Remove the specified resource from storage (CRUD Destroy).
     */
    public function destroy(RestriccionAplicacion $restriccionAplicacion)
    {
        $restriccionAplicacion->delete();

        return response()->json([
            'message' => 'Restricción de aplicación eliminada correctamente.',
        ]);
    }

    /**
     * Endpoint de Reporte de Infracción (Llamado por el Agente en la Estación).
     */
    public function reportViolation(Request $request)
    {
        $request->validate([
            'estacion_id' => 'required|exists:estaciones,id',
            'usuario_id' => 'nullable|exists:users,id',
            'nombre_proceso' => 'required|string|max:255',
            'ruta_ejecutable' => 'nullable|string',
            'accion_tomada' => 'required|string|max:255',
        ]);

        // Priorizar el usuario logueado en la sesión de Sanctum, u opcionalmente el usuario_id recibido
        $usuarioId = $request->user()?->id ?? $request->input('usuario_id');

        $log = LogAplicacionProhibida::create([
            'estacion_id' => $request->estacion_id,
            'usuario_id' => $usuarioId,
            'nombre_proceso' => $request->nombre_proceso,
            'ruta_ejecutable' => $request->ruta_ejecutable,
            'accion_tomada' => $request->accion_tomada,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Infracción de aplicación reportada y registrada correctamente.',
            'log' => $log
        ], 201);
    }

    /**
     * Consulta de Logs de Infracciones (Usado en el Frontend para polling).
     */
    public function getLogs(Request $request)
    {
        $query = LogAplicacionProhibida::query()
            ->with(['estacion:id,hostname', 'usuario:id,name']);

        // Búsquedas y filtros
        $query->when($request->filled('search'), function ($q) use ($request) {
            $search = $request->search;
            $q->where(function ($subQuery) use ($search) {
                $subQuery->where('nombre_proceso', 'ILIKE', "%{$search}%")
                    ->orWhere('accion_tomada', 'ILIKE', "%{$search}%")
                    ->orWhereHas('estacion', function ($estQuery) use ($search) {
                        $estQuery->where('hostname', 'ILIKE', "%{$search}%");
                    })
                    ->orWhereHas('usuario', function ($usrQuery) use ($search) {
                        $usrQuery->where('name', 'ILIKE', "%{$search}%");
                    });
            });
        });

        // Filtrado por estación específica
        $query->when($request->filled('estacion_id'), function ($q) use ($request) {
            $q->where('estacion_id', $request->estacion_id);
        });

        $logs = $query->latest('created_at')->paginate(10);

        return response()->json([
            'logs' => $logs,
        ]);
    }
}
