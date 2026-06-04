<?php

namespace App\Http\Controllers;

use App\Http\Requests\SemestreAcademico\StoreSemestreAcademicoRequest;
use App\Http\Requests\SemestreAcademico\UpdateSemestreRequest;
use App\Models\SemestreAcademico;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Override;

class SemestreAcademicoController extends Controller implements HasMiddleware
{
    #[Override]
    public static function middleware()
    {
        return [
            new Middleware('permission:semestres.ver', only: ['index', 'show']),
            new Middleware('permission:semestres.crear', only: ['store']),
            new Middleware('permission:semestres.editar', only: ['update']),
            new Middleware('permission:semestres.eliminar', only: ['destroy']),
        ];
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = SemestreAcademico::query();

        $query->when($request->filled('search'), function ($q) use ($request) {
            $search = $request->search;

            return $q->where(function ($subQuery) use ($search) {
                $subQuery->where('nombre', 'ILIKE', '%' . $search . '%');
            });
        });

        $query->when($request->filled('fecha_inicio'), function ($q) use ($request) {
            $q->where('fecha_inicio', '>=', $request->fecha_inicio);
        });

        $query->when($request->filled('fecha_fin'), function ($q) use ($request) {
            $q->where('fecha_fin', '<=', $request->fecha_fin);
        });

        $semestresAcademicos = $query->latest()->paginate(10);

        $semestresAcademicos->through(function ($semestreAcademico) {
            return [
                'id' => $semestreAcademico->id,
                'nombre' => $semestreAcademico->nombre,
                'fecha_inicio' => $semestreAcademico->fecha_inicio,
                'fecha_fin' => $semestreAcademico->fecha_fin,
                'estado' => $semestreAcademico->estado,
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
            'fecha_inicio' => $data->fecha_inicio,
            'fecha_fin' => $data->fecha_fin,
        ]);

        return response()->json([
            'message' => 'Semestre Academico guardo correctamente',
            'semestre_academico' => $semestreAcademico
        ], 201);
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
            'fecha_inicio' => $data->fecha_inicio,
            'fecha_fin' => $data->fecha_fin,
        ]);

        $semestre->refresh();

        return response()->json([
            'message' => 'Semestre Academico actualizado',
            'semestre_academico' => $semestre,
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SemestreAcademico $semestre)
    {
        if ($semestre->isClosed()) {
            return response()->json([
                'message' => 'No se puede eliminar un semestre que ya ha sido cerrado.',
            ], 422);
        }

        $semestre->delete(null);

        return response()->json([
            'message' => 'Semestre eliminado exitosamente',
        ], 200);
    }

    /**
     * Close the specified resource.
     */
    public function close(SemestreAcademico $semestre)
    {
        if ($semestre->isClosed()) {
            return response()->json([
                'message' => 'El semestre ya se encuentra cerrado.',
                'errors' => [
                    'estado' => ['El semestre ya se encuentra cerrado.']
                ]
            ], 422);
        }

        $semestre->update([
            'estado' => 'cerrado'
        ]);

        return response()->json([
            'message' => 'Semestre Académico cerrado correctamente',
            'semestre_academico' => $semestre,
        ], 200);
    }
}
