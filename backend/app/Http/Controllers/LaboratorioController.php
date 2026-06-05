<?php

namespace App\Http\Controllers;

use App\Http\Requests\Laboratorio\StoreLaboratorioRequest;
use App\Http\Requests\Laboratorio\UpdateLaboratorioRequest;
use App\Models\Estacion;
use App\Models\Laboratorio;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Override;

class LaboratorioController extends Controller implements HasMiddleware
{
    #[Override]
    public static function middleware()
    {
        return [
            new Middleware('permission:laboratorios.ver', only: ['index', 'show', 'obtenerPcsHuerfanas']),
            new Middleware('permission:laboratorios.crear', only: ['store']),
            new Middleware('permission:laboratorios.editar', only: ['update', 'vincularPcs']),
            new Middleware('permission:laboratorios.eliminar', only: ['destroy']),
        ];
    }
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
                'capacidad' => $laboratorio->capacidad,
                'estaciones_count' => $laboratorio->estaciones()->count(),
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
            'capacidad' => $data->capacidad ?? 30,
        ]);

        return response()->json([
            'message' => 'Laboratorio Registrado Correctamente',
            'laboratorio' => [
                'id' => $laboratorio->id,
                'nombre' => $laboratorio->nombre,
                'pabellon' => $laboratorio->pabellon,
                'piso' => $laboratorio->piso,
                'activo' => $laboratorio->activo,
                'capacidad' => $laboratorio->capacidad,
                'estaciones_count' => 0,
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
                'capacidad' => $laboratorio->capacidad,
                'estaciones_count' => $laboratorio->estaciones()->count(),
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
            'capacidad' => $data->capacidad ?? $laboratorio->capacidad,
        ]);

        $laboratorio->refresh();

        return response()->json([
            'message' => 'El registro se actualizo correctamente',
            'laboratorio' => [
                'nombre' => $laboratorio->nombre,
                'pabellon' => $laboratorio->pabellon,
                'piso' => $laboratorio->piso,
                'activo' => $laboratorio->activo,
                'capacidad' => $laboratorio->capacidad,
                'estaciones_count' => $laboratorio->estaciones()->count(),
            ],
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Laboratorio $laboratorio)
    {
        if ($laboratorio->horarios()->exists()) {
            return response()->json([
                'message' => 'El laboratorio tiene horarios asignados.',
                'errors' => ['laboratorio' => ['No se puede eliminar el laboratorio porque tiene horarios de materias asignados.']]
            ], 422);
        }

        $laboratorio->delete();

        return response()->json([
            'message' => 'Laboratorio eliminado correctamente'
        ]);
    }

    // Obtiene las PCs que no pertenecen a ningún laboratorio actualmente
    public function obtenerPcsHuerfanas()
    {
        $pcs = \App\Models\Estacion::whereNull('laboratorio_id')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($pcs);
    }

    // Vincula las PCs seleccionadas al laboratorio correspondiente
    public function vincularPcs(Request $request)
    {
        $request->validate([
            'laboratorio_id' => 'required|exists:laboratorios,id',
            'uuids'          => 'required|array',
            'uuids.*'        => 'uuid'
        ]);

        $laboratorio = Laboratorio::findOrFail($request->laboratorio_id);
        $nuevas = count($request->uuids);
        $actuales = $laboratorio->estaciones()->count();
        $capacidad = $laboratorio->capacidad ?? 30;

        if (($actuales + $nuevas) > $capacidad) {
            $disponibles = $capacidad - $actuales;
            return response()->json([
                'message' => "El laboratorio ha alcanzado su aforo máximo.",
                'errors'  => [
                    'capacidad' => ["El laboratorio '" . $laboratorio->nombre . "' tiene capacidad para {$capacidad} estaciones. Actualmente hay {$actuales} registradas y solo puede incorporar {$disponibles} más."]
                ]
            ], 422);
        }

        // Actualización masiva eficiente mediante Eloquent
        Estacion::whereIn('uuid', $request->uuids)
            ->update([
                'laboratorio_id' => $request->laboratorio_id,
                'estado'         => 'desconectado'
            ]);

        return response()->json(['status' => 'success', 'message' => 'Estaciones vinculadas correctamente.']);
    }
}
