<?php

namespace App\Http\Controllers;

use App\Http\Requests\LogsComando\StoreLogsComandoRequest;
use App\Http\Requests\LogsComando\UpdateLogsComandoRequest;
use App\Models\LogsComando;
use Illuminate\Http\Request;

class LogsComandoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = LogsComando::query()->with(['usuario', 'estacion', 'comando']);

        $query->when($request->filled('usuario_id'), function ($q) use ($request) {
            $q->where('usuario_id', $request->usuario_id);
        });

        $query->when($request->filled('estacion_id'), function ($q) use ($request) {
            $q->where('estacion_id', $request->estacion_id);
        });

        $query->when($request->filled('comando_id'), function ($q) use ($request) {
            $q->where('comando_id', $request->comando_id);
        });

        $query->when($request->filled('estado'), function ($q) use ($request) {
            $q->where('estado', $request->estado);
        });

        $query->when($request->filled('search'), function ($q) use ($request) {
            $search = $request->search;
            $q->where('origen', 'ILIKE', "%{$search}%")
              ->orWhere('mensaje_respuesta', 'ILIKE', "%{$search}%")
              ->orWhereHas('estacion', function ($sub) use ($search) {
                  $sub->where('hostname', 'ILIKE', "%{$search}%");
              })
              ->orWhereHas('usuario', function ($sub) use ($search) {
                  $sub->where('name', 'ILIKE', "%{$search}%");
              })
              ->orWhereHas('comando', function ($sub) use ($search) {
                  $sub->where('nombre', 'ILIKE', "%{$search}%");
              });
        });

        $logsComando = $query->latest()->paginate(15);

        return response()->json([
            'status' => 'success',
            'logsComando' => $logsComando
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreLogsComandoRequest $request)
    {
        $logsComando = LogsComando::create($request->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Log de comando registrado con éxito.',
            'logsComando' => $logsComando
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(LogsComando $logsComando)
    {
        $logsComando->load(['usuario', 'estacion', 'comando']);

        return response()->json([
            'status' => 'success',
            'logsComando' => $logsComando
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateLogsComandoRequest $request, LogsComando $logsComando)
    {
        $logsComando->update($request->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Log de comando actualizado correctamente.',
            'logsComando' => $logsComando
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(LogsComando $logsComando)
    {
        $logsComando->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Log de comando eliminado correctamente.'
        ]);
    }
}
