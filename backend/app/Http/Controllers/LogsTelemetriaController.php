<?php

namespace App\Http\Controllers;

use App\Models\LogsTelemetria;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Override;

class LogsTelemetriaController extends Controller implements HasMiddleware
{
    #[Override]
    public static function middleware()
    {
        return [
            new Middleware('permission:monitoreo.ver', only: ['index', 'show']),
            new Middleware('permission:monitoreo.crear', only: ['store']),
            new Middleware('permission:monitoreo.editar', only: ['update']),
            new Middleware('permission:monitoreo.eliminar', only: ['destroy']),
        ];
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = LogsTelemetria::query()->with('estacion');

        $query->when($request->filled('estacion_id'), function ($q) use ($request) {
            $q->where('estacion_id', $request->estacion_id);
        });

        $query->when($request->filled('search'), function ($q) use ($request) {
            $search = $request->search;
            $q->whereHas('estacion', function ($sub) use ($search) {
                $sub->where('hostname', 'ILIKE', "%{$search}%")
                    ->orWhere('direccion_ip', 'ILIKE', "%{$search}%")
                    ->orWhere('direccion_mac', 'ILIKE', "%{$search}%");
            });
        });

        // Ordenamos por el registro de telemetría más reciente
        $logs = $query->latest()->paginate(15);

        return response()->json([
            'status' => 'success',
            'logs' => $logs
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'estacion_id'  => 'required|integer|exists:estaciones,id',
            'carga_cpu'    => 'required|numeric|min:0|max:100',
            'uso_ram_mb'   => 'required|integer|min:0',
            'temp_cpu'     => 'required|numeric',
            'uso_disco'    => 'required|numeric|min:0|max:100',
            'latencia_red' => 'required|integer|min:0',
        ]);

        $log = LogsTelemetria::create($validated);

        return response()->json([
            'status'  => 'success',
            'message' => 'Log de telemetría registrado con éxito.',
            'log'     => $log
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(LogsTelemetria $logsTelemetria)
    {
        $logsTelemetria->load('estacion');

        return response()->json([
            'status' => 'success',
            'log'    => $logsTelemetria
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, LogsTelemetria $logsTelemetria)
    {
        $validated = $request->validate([
            'carga_cpu'    => 'sometimes|required|numeric|min:0|max:100',
            'uso_ram_mb'   => 'sometimes|required|integer|min:0',
            'temp_cpu'     => 'sometimes|required|numeric',
            'uso_disco'    => 'sometimes|required|numeric|min:0|max:100',
            'latencia_red' => 'sometimes|required|integer|min:0',
        ]);

        $logsTelemetria->update($validated);

        return response()->json([
            'status'  => 'success',
            'message' => 'Log de telemetría actualizado correctamente.',
            'log'     => $logsTelemetria
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(LogsTelemetria $logsTelemetria)
    {
        $logsTelemetria->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Log de telemetría eliminado correctamente.'
        ]);
    }
}
