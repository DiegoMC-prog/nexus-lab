<?php

namespace App\Http\Controllers;

use App\Http\Requests\Materia\StoreMateriaRequest;
use App\Http\Requests\Materia\UpdateMateriaRequest;
use App\Models\Materia;
use Illuminate\Http\Request;

class MateriaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Materia::query()->with(['carrera', 'semestreAcademico']);

        $query->when($request->filled('search'), function ($q) use ($request) {
            $search = "%{$request->search}%";

            $q->where(function ($subQuery) use ($search) {
                $subQuery->where('materias.nombre', 'ILIKE', $search)
                    ->orWhere('materias.codigo', 'ILIKE', $search)

                    ->orWhereHas('carrera', function ($carreraQuery) use ($search) {
                        $carreraQuery->where('carreras.nombre', 'ILIKE', $search);
                    })

                    ->orWhereHas('semestreAcademico', function ($semestreQuery) use ($search) {
                        $semestreQuery->where('semestres_academicos.nombre', 'ILIKE', $search);
                    });
            });
        });

        $query->when($request->filled('semestre_academico_id') && $request->semestre_academico_id !== 'all', function ($q) use ($request) {
            $q->where('semestre_academico_id', $request->semestre_academico_id);
        });

        $query->when($request->filled('carrera_id') && $request->carrera_id !== 'all', function ($q) use ($request) {
            $q->where('carrera_id', $request->carrera_id);
        });

        $materias = $query->latest()->paginate(10);

        $materias->through(function ($materia) {
            return [
                'id' => $materia->id,
                'codigo' => $materia->codigo,
                'nombre' => $materia->nombre,
                'creditos' => $materia->creditos ?? 'sin creditos',
                'semestre_academico_id' => $materia->semestreAcademico->id,
                'nombre_semestre' => $materia->semestreAcademico->nombre,
                'carrera_id' => $materia->carrera->id,
                'nombre_carrera' => $materia->carrera->nombre,
            ];
        });

        return response()->json([
            'materias' => $materias,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMateriaRequest $request)
    {
        $data = (object) $request->validated();

        $materia = Materia::create([
            'carrera_id' => $data->carrera_id,
            'codigo' => $data->codigo,
            'nombre' => $data->nombre,
            'creditos' => $data->creditos ?? 0,
            'semestre_academico_id' => $data->semestre_academico_id,
        ]);

        return response()->json([
            'message' => 'Materia creada correctamente',
            'materia' => [
                'id' => $materia->id,
                'carrera_id' => $materia->carrera_id,
                'semestre_academico_id' => $materia->semestre_academico_id,
                'codigo' => $materia->codigo,
                'nombre' => $materia->nombre,
                'creditos' => $materia->creditos,
            ]
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Materia $materia)
    {
        return response()->json([
            'materia' => [
                'id' => $materia->id,
                'carrera_id' => $materia->carrera_id,
                'semestre_academico_id' => $materia->semestre_academico_id,
                'codigo' => $materia->codigo,
                'nombre' => $materia->nombre,
                'creditos' => $materia->creditos,
            ]
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMateriaRequest $request, Materia $materia)
    {
        $data = (object) $request->validated();

        $materia->update([
            'carrera_id' => $data->carrera_id,
            'semestre_academico_id' => $data->semestre_academico_id,
            'codigo' => $data->codigo,
            'nombre' => $data->nombre,
            'creditos' => $data->creditos,
        ]);

        $materia->refresh();

        return response()->json([
            'message' => 'Materia editada exitosamente',
            'materia' => $materia
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Materia $materia)
    {
        $materia->delete(null);

        return response()->json([
            'message' => 'Materia eliminada correctamente',
        ]);
    }

    public function MateriaFormData()
    {
        $semestres = \App\Models\SemestreAcademico::query()->select(['id', 'nombre'])->latest('id')->get();
        $carreras = \App\Models\Carrera::query()->select(['id', 'nombre'])->latest('id')->get();

        return response()->json([
            'semestres' => $semestres,
            'carreras' => $carreras,
        ]);
    }
}
