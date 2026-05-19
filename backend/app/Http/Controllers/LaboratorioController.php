<?php

namespace App\Http\Controllers;

use App\Http\Requests\Laboratorio\StoreLaboratorioRequest;
use App\Http\Requests\Laboratorio\UpdateLaboratorioRequest;
use App\Models\Laboratorio;
use Illuminate\Http\Request;

class LaboratorioController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Laboratorio::query();

        $query->when($request->search, function ($q, $search) {
            $q->where(function ($subQuery) use ($search) {
                $subQuery->where('nombre', 'ILIKE', "%{$search}%")
                    ->orWhere('pabellon', 'ILIKE', "%{$search}%")
                    ->orWhere('piso', 'ILIKE', "%{$search}%");
            });
        });

        $query->when($request->filled('activo') && $request->activo !== 'all', function ($q) use ($request) {
            $activoValue = filter_var($request->activo, FILTER_VALIDATE_BOOLEAN);
            $q->where('activo', $activoValue);
        });

        $laboratorios = $query->latest()->paginate(6);

        $laboratorios->through(function ($laboratorio) {
            return [
                'id' => $laboratorio->id,
                'nombre' => $laboratorio->nombre,
                'pabellon' => $laboratorio->pabellon,
                'piso' => $laboratorio->piso,
                'activo' => $laboratorio->activo,
            ];
        });

        return response()->json([
            'laboratorios' => $laboratorios,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreLaboratorioRequest $request)
    {
        $data = (object) $request->validated();

        $laboratorio = Laboratorio::create([
            'nombre' => $data->nombre,
            'pabellon' => $data->pabellon,
            'piso' => $data->piso,
            'activo' => (bool) $data->activo,
        ]);

        return response()->json([
            'message' => 'Laboratorio Registrado Correctamente',
            'laboratorio' => [
                'id' => $laboratorio->id,
                'nombre' => $laboratorio->nombre,
                'pabellon' => $laboratorio->pabellon,
                'piso' => $laboratorio->piso,
                'activo' => $laboratorio->activo,
            ],
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Laboratorio $laboratorio)
    {
        return response()->json([
            'laboratorio' => [
                'id' => $laboratorio->id,
                'nombre' => $laboratorio->nombre,
                'pabellon' => $laboratorio->pabellon,
                'piso' => $laboratorio->piso,
                'activo' => $laboratorio->activo,
            ],
        ], 200);
    }

    /**
     * Update the specifi0d resource in storage.
     */
    public function update(UpdateLaboratorioRequest $request, Laboratorio $laboratorio)
    {
        $data = (object) $request->validated();

        $laboratorio->update([
            'nombre' => $data->nombre,
            'pabellon' => $data->pabellon,
            'piso' => $data->piso,
            'activo' => $data->activo,
        ]);

        $laboratorio->refresh();

        return response()->json([
            'message' => 'El registro se actualizo correctamente',
            'laboratorio' => [
                'nombre' => $laboratorio->nombre,
                'pabellon' => $laboratorio->pabellon,
                'piso' => $laboratorio->piso,
                'activo' => $laboratorio->activo,
            ],
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Laboratorio $laboratorio)
    {
        $laboratorio->delete();

        return response()->json([
            'message' => 'Laboratorio eliminado correctamente'
        ]);
    }
}
