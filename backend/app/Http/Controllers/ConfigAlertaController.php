<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConfigAlerta\StoreConfigAlertaRequest;
use App\Http\Requests\ConfigAlerta\UpdateConfigAlertaRequest;
use App\Models\ConfigAlerta;
use Illuminate\Http\Request;

class ConfigAlertaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = ConfigAlerta::query();

        $query->when($request->filled('search'), function ($q) use ($request) {
            $search = $request->search;
            $q->where('metrica', 'ILIKE', "%{$search}%")
              ->orWhere('severidad', 'ILIKE', "%{$search}%");
        });

        $query->when($request->filled('activo'), function ($q) use ($request) {
            $q->where('activo', filter_var($request->activo, FILTER_VALIDATE_BOOLEAN));
        });

        $configAlertas = $query->latest()->paginate(10);

        return response()->json([
            'status' => 'success',
            'configAlertas' => $configAlertas
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreConfigAlertaRequest $request)
    {
        $configAlerta = ConfigAlerta::create($request->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Configuración de alerta registrada con éxito.',
            'configAlerta' => $configAlerta
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(ConfigAlerta $configAlerta)
    {
        return response()->json([
            'status' => 'success',
            'configAlerta' => $configAlerta
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateConfigAlertaRequest $request, ConfigAlerta $configAlerta)
    {
        $configAlerta->update($request->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Configuración de alerta actualizada correctamente.',
            'configAlerta' => $configAlerta
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ConfigAlerta $configAlerta)
    {
        $configAlerta->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Configuración de alerta eliminada correctamente.'
        ]);
    }
}
