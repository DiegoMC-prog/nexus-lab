<?php

namespace App\Http\Controllers;

use App\Http\Requests\Carrera\StoreCarreraRequest;
use App\Http\Requests\Carrera\UpdateCarreraRequest;
use App\Models\Carrera;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Override;

class CarreraController extends Controller implements HasMiddleware
{
    #[Override]
    public static function middleware()
    {
        return [
            new Middleware('permission:carreras.ver', only: ['index', 'show']),
            new Middleware('permission:carreras.crear', only: ['store']),
            new Middleware('permission:carreras.editar', only: ['update']),
            new Middleware('permission:carreras.eliminar', only: ['destroy']),
        ];
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Carrera::query();

        $query->when($request->filled('search'), function ($q) use ($request) {
            $search = $request->search;
            $q->where(function ($subQuery) use ($search) {
                $subQuery->where('nombre', 'LIKE', "%{$search}%")
                    ->orWhere('codigo', 'LIKE', "%{$search}%");
            });
        });

        $carreras = $query->latest()->paginate(10);

        $carreras->through(function ($carrera) {
            return [
                'id' => $carrera->id,
                'nombre' => $carrera->nombre,
                'codigo' => $carrera->codigo,
            ];
        });

        return response()->json([
            'carreras' => $carreras,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCarreraRequest $request)
    {
        $data = (object) $request->validated();

        $carrera = Carrera::create([
            'nombre' => $data->nombre,
            'codigo' => $data->codigo,
        ]);

        return response()->json([
            'message' => 'Carrera creada exitosamenete',
            'carrera' => [
                'id' => $carrera->id,
                'nombre' => $carrera->nombre,
                'codigo' => $carrera->codigo,
            ],
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Carrera $carrera)
    {
        return response()->json([
            'carrera' => $carrera,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCarreraRequest $request, Carrera $carrera)
    {
        $data = (object) $request->validated();

        $carrera->update([
            'nombre' => $data->nombre,
            'codigo' => $data->codigo,
        ]);

        $carrera->refresh();

        return response()->json([
            'message' => 'Carrera Edita con exito',
            'carrera' => [
                'id' => $carrera->id,
                'nombre' => $carrera->nombre,
                'codigo' => $carrera->codigo,
            ]
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Carrera $carrera)
    {
        $carrera->delete(null);

        return response()->json([
            'message' => 'Carrera eliminada exitosamente',
        ]);
    }
}
