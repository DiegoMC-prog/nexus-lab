<?php

namespace App\Http\Controllers;

use App\Http\Requests\SemestreAcademico\StoreSemestreAcademicoRequest;
use App\Http\Requests\SemestreAcademico\UpdateSemestreRequest;
use App\Models\SemestreAcademico;
use Illuminate\Http\Request;

class SemestreAcademicoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = SemestreAcademico::query();

        $query->when($request->filled('search'), function ($q) use ($request) {
            $search = $request->search;

            return $q->where(function ($subQuery) use ($search) {
                $subQuery->where('nombre', 'ILIKE', $search);
            });
        });

        $semestresAcademicos = $query->latest()->paginate(10);

        $semestresAcademicos->through(function ($semestreAcademico) {
            return [
                'id' => $semestreAcademico->id,
                'nombre' => $semestreAcademico->nombre,
            ];
        });

        return response()->json([
            'semestres_academicos' => $semestresAcademicos,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSemestreAcademicoRequest $request)
    {
        $data = (object) $request->validated();

        $semestreAcademico = SemestreAcademico::create([
            'nombre' => $data->nombre,
        ]);

        return response()->json([
            'message' => 'Semestre Academico guardo correctamente',
            'semestre_academico' => $semestreAcademico
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(SemestreAcademico $semestreAcademico)
    {
        return response()->json([
            'semestre_academico' => $semestreAcademico,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSemestreRequest $request, SemestreAcademico $semestre)
    {
        $data = (object) $request->validated();

        $semestre->update([
            'nombre' => $data->nombre,
        ]);

        $semestre->refresh();

        return response()->json([
            'message' => 'Semestre Academico actualizado',
            'semestre_academico' => $semestre,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SemestreAcademico $semestre)
    {
        $semestre->delete(null);

        return response()->json([
            'message' => 'Semestre eliminado exitosamente',
        ]);
    }

    public function getFormData()
    {
        $carreras = \App\Models\Carrera::orderBy('nombre', 'asc')
            ->get()
            ->map(fn($carrera) => [
                'id' => $carrera->id,
                'nombre' => $carrera->nombre,
                'codigo' => $carrera->codigo,
            ]);

        $semestres = \App\Models\SemestreAcademico::orderBy('nombre', 'asc')
            ->get()
            ->map(fn($semestre) => [
                'id' => $semestre->id,
                'nombre' => $semestre->nombre,
            ]);

        return response()->json([
            'carreras' => $carreras,
            'semestres' => $semestres,
        ]);
    }
}
