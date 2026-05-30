<?php

namespace App\Http\Controllers;

use App\Models\Estacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AgenteController extends Controller
{
    public function registrarEstacion(Request $request)
    {
        $request->validate([
            'uuid'           => 'required|uuid',
            'hostname'       => 'required|string',
            'direccion_mac'  => 'required|string',
            'direccion_ip'   => 'required|string',
            'so_info'        => 'required|string',
            'version_agente' => 'required|string',
            'estado' => 'required|string',
            //perfil hardware
            'cpu_modelo'     => 'required|string',
            'ram_total_gb'   => 'required|integer',
            'cantidad_almacenamiento' => 'required|string',
            'gpu_modelo' => 'required|string',
        ]);

        DB::beginTransaction();

        try {
            $estacion = Estacion::updateOrCreate(
                ['uuid' => $request->uuid],
                [
                    'hostname'       => $request->hostname,
                    'direccion_mac'  => $request->direccion_mac,
                    'direccion_ip'   => $request->direccion_ip,
                    'so_info'        => $request->so_info,
                    'version_agente' => $request->version_agete,
                    'ultima_conexion' => now(),
                    'estado' => 'pendiente',
                ]
            );

            $estacion->perfilHardware()->updateOrCreate([
                ['estacion_id' => $estacion->uuid],
                [
                    'cpu_modelo' => $request->cpu_modelo,
                    'ram_total_gb' => $request->ram_total_gb,
                    'cantidad_almacenamiento' => $request->cantidad_almacenamiento,
                    'gpu_modelo' => $request->gpu_modelo ?? 'sin gpu',
                ]
            ]);

            DB::commit();

            if (is_null($estacion->laboratorio_id)) {
                return response()->json([
                    'status' => 'esperando asignacion'
                ], 201);
            }

            return response()->json(['status' => 'registrado', 'laboratorio_id' => $estacion->laboratorio_id], 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['error' => 'Fallo en la persistencia: ' . $e->getMessage()], 500);
        }
    }

    public function chequearEstatus(string $uuid)
    {
        $estacion = Estacion::select(['laboratorio_id', 'estado'])->find($uuid);

        if (!$estacion) {
            return response()->json(['status' => 'no_registrado'], 404);
        }

        if (!is_null($estacion->laboratorio_id)) {
            return response()->json([
                'status' => 'asignado',
                'laboratorio_id' => $estacion->laboratorio_id
            ], 200);
        }

        return response()->json(['status' => 'esperando_asignacion'], 200);
    }
}
