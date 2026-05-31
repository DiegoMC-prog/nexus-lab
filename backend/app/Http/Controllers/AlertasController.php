<?php

namespace App\Http\Controllers;

use App\Http\Requests\Alerta\StoreAlertaRequest;
use App\Http\Requests\Alerta\UpdateAlertaRequest;
use App\Models\Alerta;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Override;

class AlertasController extends Controller implements HasMiddleware
{
    #[Override]
    public static function middleware()
    {
        return [
            new Middleware('permission:alertas.ver', only: ['index', 'show']),
            new Middleware('permission:alertas.crear', only: ['store']),
            new Middleware('permission:alertas.editar', only: ['update']),
            new Middleware('permission:alertas.eliminar', only: ['destroy']),
        ];
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Alerta::query()->with(['estacion', 'configuracionAlerta']);

        $query->when($request->filled('estacion_id'), function ($q) use ($request) {
            $q->where('estacion_id', $request->estacion_id);
        });

        $query->when($request->filled('estado'), function ($q) use ($request) {
            $q->where('estado', $request->estado);
        });

        $query->when($request->filled('search'), function ($q) use ($request) {
            $search = $request->search;
            $q->where('estado', 'ILIKE', "%{$search}%")
              ->orWhereHas('estacion', function ($sub) use ($search) {
                  $sub->where('hostname', 'ILIKE', "%{$search}%");
              })
              ->orWhereHas('configuracionAlerta', function ($sub) use ($search) {
                  $sub->where('metrica', 'ILIKE', "%{$search}%")
                      ->orWhere('severidad', 'ILIKE', "%{$search}%");
              });
        });

        $alertas = $query->latest()->paginate(15);

        return response()->json([
            'status' => 'success',
            'alertas' => $alertas
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAlertaRequest $request)
    {
        $alerta = Alerta::create($request->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Alerta registrada con éxito.',
            'alerta' => $alerta
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Alerta $alerta)
    {
        $alerta->load(['estacion', 'configuracionAlerta']);

        return response()->json([
            'status' => 'success',
            'alerta' => $alerta
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAlertaRequest $request, Alerta $alerta)
    {
        $alerta->update($request->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Alerta actualizada correctamente.',
            'alerta' => $alerta
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Alerta $alerta)
    {
        $alerta->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Alerta eliminada correctamente.'
        ]);
    }
}
