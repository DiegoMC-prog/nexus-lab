<?php

namespace App\Http\Controllers;

use App\Http\Requests\Comando\StoreComandoRequest;
use App\Http\Requests\Comando\UpdateComandoRequest;
use App\Models\Comando;
use Illuminate\Http\Request;

class ComandoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Comando::query();

        $query->when($request->filled('search'), function ($q) use ($request) {
            $search = $request->search;
            $q->where('nombre', 'ILIKE', "%{$search}%")
              ->orWhere('slug', 'ILIKE', "%{$search}%")
              ->orWhere('tipo', 'ILIKE', "%{$search}%");
        });

        $comandos = $query->latest()->paginate(10);

        return response()->json([
            'status' => 'success',
            'comandos' => $comandos
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreComandoRequest $request)
    {
        $comando = Comando::create($request->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Comando registrado con éxito.',
            'comando' => $comando
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Comando $comando)
    {
        return response()->json([
            'status' => 'success',
            'comando' => $comando
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateComandoRequest $request, Comando $comando)
    {
        $comando->update($request->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Comando actualizado correctamente.',
            'comando' => $comando
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Comando $comando)
    {
        $comando->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Comando eliminado correctamente.'
        ]);
    }
}
