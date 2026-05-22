<?php

namespace App\Http\Controllers;

use App\Http\Requests\Grupo\StoreGrupoRequest;
use App\Http\Requests\Grupo\UpdateGrupoRequest;
use App\Models\Grupo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GrupoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Grupo::query()->with(['materia']);

        $query->when($request->filled('search'), function ($q) use ($request) {
            $search = $request->search;

            $q->where(function ($subQuery) use ($search) {
                $subQuery->where('grupos.nombre', 'ILIKE', "%{$search}%")
                    ->orWhereHas('materia', function ($materiaQuery) use ($search) {
                        $materiaQuery->where('materias.nombre', 'ILIKE', "%{$search}%")
                            ->orWhere('materias.codigo', 'ILIKE', "%{$search}%");
                    });
            });
        });

        $query->when($request->filled('materia_id') && $request->materia_id !== 'all', function ($q) use ($request) {
            $q->where('materia_id', $request->materia_id);
        });

        $grupos = $query->latest()->paginate(10);

        $grupos->through(function ($grupo) {
            return [
                'id' => $grupo->id,
                'materia_id' => $grupo->materia_id,
                'nombre' => $grupo->nombre,
                'nombre_grupo' => $grupo->nombre,
                'nombre_materia' => $grupo->materia->nombre,
                'gestion' => $grupo->gestion,
                'cupo_maximo' => $grupo->cupo_maximo ?? 0,
            ];
        });

        return response()->json([
            'grupos' => $grupos,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreGrupoRequest $request)
    {
        $data = (object) $request->validated();

        $grupo = Grupo::create([
            'materia_id'  => $data->materia_id,
            'nombre'      => $data->nombre,
            'gestion'     => $data->gestion,
            'cupo_maximo' => $data->cupo_maximo ?? null,
        ]);

        return response()->json([
            'message' => 'Grupo académico registrado correctamente',
            'grupo'   => [
                'id'           => $grupo->id,
                'materia_id'   => $grupo->materia_id,
                'nombre_grupo' => $grupo->nombre,
                'gestion'      => $grupo->gestion,
                'cupo_maximo'  => $grupo->cupo_maximo ?? 0,
            ]
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Grupo $grupo)
    {
        return response()->json([
            'grupo' => [
                'id' => $grupo->id,
                'materia_id' => $grupo->materia_id,
                'nombre_grupo' => $grupo->nombre,
                'gestion' => $grupo->gestion,
                'cupo_maximo' => $grupo->cupo_maximo ?? 0,
                'materia' => $grupo->materia ? [
                    'id' => $grupo->materia->id,
                    'codigo' => $grupo->materia->codigo,
                    'nombre' => $grupo->materia->nombre,
                ] : null
            ]
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateGrupoRequest $request, Grupo $grupo)
    {
        $data = (object) $request->validated();

        $grupo->update([
            'materia_id'  => $data->materia_id,
            'nombre'      => $data->nombre,
            'gestion'     => $data->gestion,
            'cupo_maximo' => $data->cupo_maximo ?? null,
        ]);

        // Forzamos la recarga fresca desde PostgreSQL antes de responder
        $grupo->refresh();

        $grupo->load(['materia']);

        return response()->json([
            'message' => 'Grupo académico actualizado correctamente',
            'grupo'   => [
                'id'           => $grupo->id,
                'materia_id'   => $grupo->materia_id,
                'materia_nombre' => $grupo->materia->nombre,
                'nombre_grupo' => $grupo->nombre,
                'gestion'      => $grupo->gestion,
                'cupo_maximo'  => $grupo->cupo_maximo ?? 0,
            ]
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Grupo $grupo)
    {
        // Control de integridad relacional: Si tiene horarios en laboratorios, denegamos el borrado
        if ($grupo->horarios()->exists()) {
            return response()->json([
                'error'   => 'Conflict',
                'message' => 'No se puede eliminar el grupo porque tiene horarios activos vinculados.'
            ], 409); // 409 Conflict: Estado correcto cuando la solicitud choca con reglas de integridad
        }

        // Transacción atómica segura para remover inscripciones y aplicar softdelete
        DB::transaction(function () use ($grupo) {
            // Desvincula alumnos de la tabla intermedia muchos a muchos (grupo_user)
            $grupo->estudiantes()->detach();

            // Softdelete en PostgreSQL
            $grupo->delete(null);
        });

        return response()->json([
            'message' => 'Grupo eliminado exitosamente',
        ], 200);
    }

    public function GrupoFormData()
    {
        $materias = \App\Models\Materia::select(['id', 'nombre'])->latest('id')->get();

        return response()->json([
            'materias' => $materias,
        ]);
    }
}
