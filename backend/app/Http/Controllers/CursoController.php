<?php

namespace App\Http\Controllers;

use App\Http\Requests\Curso\StoreCursoRequest;
use App\Http\Requests\Curso\UpdateCursoRequest;
use App\Models\Curso;
use Illuminate\Http\Request;

class CursoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Curso::query()->with('semestreAcademico', 'carrera');

        $query->when($request->filled('carrera_id'), function ($q) use ($request) {
            return $q->where('carrera_id', $request->carrera_id);
        });

        $query->when($request->filled('semestre_academico_id'), function ($q) use ($request) {
            return $q->where('semestre_academico_id', $request->semestre_academico_id);
        });

        $query->when($request->filled('search'), function ($q) use ($request) {
            $search = $request->search;

            return $q->where(function ($subQuery) use ($search) {

                $subQuery->whereHas('carrera', function ($carreraQuery) use ($search) {
                    $carreraQuery->where('nombre', 'ILIKE', "%{$search}%")
                        ->orWhere('codigo', 'ILIKE', "%{$search}%");
                })
                    ->orWhereHas('semestreAcademico', function ($semestreQuery) use ($search) {
                        $semestreQuery->where('nombre', 'ILIKE', "%{$search}%");
                    });
            });
        });

        $cursos = $query->latest()->paginate(10);

        $cursos->through(function ($curso) {
            return [
                'id' => $curso->id,
                'carrera_id' => $curso->carrera_id,
                'carrera' => $curso->carrera->nombre,
                'semestre_academico_id' => $curso->semestre_academico_id,
                'semestre' => $curso->semestreAcademico->nombre,
            ];
        });

        return response()->json([
            'cursos' => $cursos,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCursoRequest $request)
    {
        $data = (object) $request->validated();

        /** @var Curso $curso */
        $curso = Curso::create([
            'carrera_id' => $data->carrera_id,
            'semestre_academico_id' => $data->semestre_academico_id,
        ]);

        $curso->load('carrera', 'semestreAcademico');

        return response()->json([
            'message' => 'Curso registrado con éxito.',
            'curso' => [
                'id' => $curso->id,
                'carrera_id' => $curso->carrera_id,
                'carrera' => $curso->carrera->nombre,
                'semestre_academico_id' => $curso->semestre_academico_id,
                'nombre' => $curso->semestreAcademico->nombre,
            ]
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Curso $curso)
    {
        $curso->load('carrera', 'semestreAcademico');

        return response()->json([
            'curso' => [
                'id' => $curso->id,
                'carrera_id' => $curso->carrera_id,
                'carrera' => $curso->carrera->nombre,
                'semestre_academico_id' => $curso->semestre_academico_id,
                'nombre' => $curso->semestreAcademico->nombre,
            ]
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCursoRequest $request, Curso $curso)
    {
        $data = (object) $request->validated();

        $curso->update([
            'carrera_id' => $data->carrera_id,
            'semestre_academico_id' => $data->semestre_academico_id,
        ]);

        $curso->load('carrera', 'semestreAcademico');

        return response()->json([
            'message' => 'Curso actualizado con éxito.',
            'curso' => [
                'id' => $curso->id,
                'carrera_id' => $curso->carrera_id,
                'carrera' => $curso->carrera->nombre,
                'semestre_academico_id' => $curso->semestre_academico_id,
                'nombre' => $curso->semestreAcademico->nombre,
            ]
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Curso $curso)
    {
        $curso->delete(null);

        return response()->json([
            'message' => 'Curso eliminado con éxito.'
        ]);
    }
}
