<?php

namespace App\Http\Controllers;

use App\Events\EstacionDetectadaEnCanal;
use App\Http\Requests\Estacion\StoreEstacionRequest;
use App\Http\Requests\Estacion\UpdateEstacionRequest;
use App\Models\Estacion;
use Illuminate\Http\Request;

class EstacionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Estacion::query()->with(['laboratorio', 'estudianteActual']);

        $query->when($request->filled('search'), function ($q) use ($request) {
            $search = $request->search;

            $q->where(function ($sub) use ($search) {
                $sub->where('hostname', 'ILIKE', "%{$search}%")
                    ->orWhere('direccion_ip', 'ILIKE', "%{$search}%")
                    ->orWhere('direccion_mac', 'ILIKE', "%{$search}%")
                    ->orWhere('so_info', 'ILIKE', "%{$search}%");
            });
        });

        $query->when($request->filled('laboratorio_id') && $request->laboratorio_id !== 'all', function ($q) use ($request) {
            $q->where('laboratorio_id', $request->laboratorio_id);
        });

        $query->when($request->filled('estado') && $request->estado !== 'all', function ($q) use ($request) {
            $q->where('estado', $request->estado);
        });

        $query->when($request->filled('online'), function ($q) {
            $q->where('ultima_conexion', '>=', now()->subMinutes(5));
        });

        $estaciones = $query->latest()->paginate(10);

        $estaciones->through(function ($estacion) {
            return [
                'id' => $estacion->id,
                'laboratorio_id' => $estacion->laboratorio_id,
                'nombre_laboratorio' => $estacion->laboratorio?->nombre ?? 'sin laboratorio',
                'nombre_estudiante_actual_id' => $estacion->estudianteActual->id ?? 'sin estudiante',
                'nombre_estudiante_actual' => $estacion->estudianteActual->name ?? 'sin nombre',
                'hostname' => $estacion->hostname,
                'direccion_mac' => $estacion->direccion_mac,
                'direccion_ip' => $estacion->direccion_ip,
                'so_info' => $estacion->so_info,
                'estado' => $estacion->estado,
                'version_agente' => $estacion->version_agente,
                'ultima_conexion' => $estacion->ultima_conexion ?? 'sin conexion',
            ];
        });

        return response()->json([
            'estaciones' => $estaciones,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreEstacionRequest $request)
    {
        $data = (object) $request->validated();

        if ($request->filled('laboratorio_id')) {
            $estacion = Estacion::updateOrCreate(
                ['uuid' => $data->uuid],
                [
                    'laboratorio_id'       => $data->laboratorio_id,
                    'estudiante_actual_id' => $data->estudiante_actual_id ?? null,
                    'hostname'             => $data->hostname,
                    'direccion_mac'        => $data->direccion_mac,
                    'direccion_ip'         => $data->direccion_ip,
                    'so_info'              => $data->so_info,
                    'estado'               => 'bloqueado', // Estado Kiosko inicial
                    'version_agente'       => $data->version_agente,
                    'ultima_conexion'      => now(),
                ]
            );

            $estacion->load(['laboratorio']);

            return response()->json([
                'status'   => 'success',
                'message'  => 'Estación consolidada y registrada en el laboratorio.',
                'estacion' => $this->transformarEstacion($estacion)
            ]);
        }

        $existe = Estacion::whereUuid($data->uuid)->first();

        if (!$existe) {
            $payloadPendiente = [
                'laboratorio_target_id' => $request->header('X-Laboratorio-Id') ?? 1, // Laboratorio donde se abrió el canal de escucha
                'uuid'                  => $data->uuid,
                'hostname'              => $data->hostname,
                'direccion_mac'         => $data->direccion_mac,
                'direccion_ip'          => $data->direccion_ip,
                'so_info'               => $data->so_info,
                'version_agente'        => $data->version_agente,
                'estado'                => 'pendiente'
            ];

            // 🚀 DISPARO REACTIVO: Envía la máquina directo al WebSocket para que Vue la encole en "Pendientes"
            broadcast(new EstacionDetectadaEnCanal($payloadPendiente))->toOthers();

            return response()->json([
                'status'  => 'pending',
                'message' => 'Estación en espera de aprobación por el administrador del laboratorio.'
            ], 202); // Código HTTP 202 Accepted (Procesamiento pendiente)
        }

        // Si ya existía, solo actualiza su marca de tiempo (Heartbeat regular)
        $existe->update(['ultima_conexion' => now()]);
        return response()->json(['status' => 'success', 'message' => 'Keep-alive recibido.']);
    }

    /**
     * Display the specified resource.
     */
    public function show(Estacion $estacion)
    {
        $estacion->load(['laboratorio', 'estudianteActual']);

        return response()->json([
            'estacion' => [
                'id' => $estacion->id,
                'laboratorio_id' => $estacion->laboratorio_id,
                'nombre_laboratorio' => $estacion->laboratorio->nombre ?? 'sin laboratorio',
                'estudiante_actual_id' => $estacion->estudianteActual->id ?? 'sin estudiante',
                'nombre_estudiante_actual' => $estacion->estudianteActual->name ?? 'sin nombre',
                'hostname' => $estacion->hostname,
                'direccion_mac' => $estacion->direccion_mac,
                'direccion_ip' => $estacion->direccion_ip,
                'so_info' => $estacion->so_info,
                'estado' => $estacion->estado,
                'version_agente' => $estacion->version_agente,
                'ultima_conexion' => $estacion->ultima_conexion ?? 'sin conexion',
            ],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateEstacionRequest $request, Estacion $estacion)
    {
        $data = (object) $request->validated();

        $estacion->update([
            'laboratorio_id' => $data->laboratorio_id,
            'estudiante_actual_id' => $data->estudiante_actual_id,
            'hostname' => $data->hostname,
            'direccion_mac' => $data->direccion_mac,
            'direccion_ip' => $data->direccion_ip,
            'so_info' => $data->so_info,
            'estado' => $data->estado,
            'version_agente' => $data->version_agente,
        ]);

        $estacion->load(['laboratorio', 'estudianteActual']);

        return response()->json([
            'message' => 'Estacion actualizada correctamente',
            'estacion' => [
                'id' => $estacion->id,
                'laboratorio_id' => $estacion->laboratorio_id,
                'nombre_laboratorio' => $estacion->laboratorio?->nombre ?? 'sin laboratorio',
                'nombre_estudiante_actual_id' => $estacion->estudianteActual?->id ?? 'sin estudiante',
                'nombre_estudiante_actual' => $estacion->estudianteActual?->name ?? 'sin nombre',
                'hostname' => $estacion->hostname,
                'direccion_mac' => $estacion->direccion_mac,
                'direccion_ip' => $estacion->direccion_ip,
                'so_info' => $estacion->so_info,
                'estado' => $estacion->estado,
                'version_agente' => $estacion->version_agente,
                'ultima_conexion' => $estacion->ultima_conexion ?? 'sin conexion',
            ],
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Estacion $estacion)
    {
        $estacion->delete();

        return response()->json([
            'message' => 'Estacion eliminada correctamente',
        ]);
    }


    private function transformarEstacion(Estacion $estacion)
    {
        return [
            'id'                          => $estacion->id,
            'laboratorio_id'              => $estacion->laboratorio_id,
            'nombre_laboratorio'          => $estacion->laboratorio?->nombre ?? 'sin laboratorio',
            'nombre_estudiante_actual_id' => $estacion->estudianteActual?->id ?? 'sin estudiante',
            'nombre_estudiante_actual'    => $estacion->estudianteActual?->name ?? 'sin nombre',
            'hostname'                    => $estacion->hostname,
            'direccion_mac'               => $estacion->direccion_mac,
            'direccion_ip'                => $estacion->direccion_ip,
            'so_info'                     => $estacion->so_info,
            'estado'                      => $estacion->estado,
            'version_agente'              => $estacion->version_agente,
            'ultima_conexion'             => $estacion->ultima_conexion ?? 'sin conexion',
        ];
    }
}
