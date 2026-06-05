<?php

namespace App\Http\Controllers;

use App\Http\Requests\Horario\StoreHorarioRequest;
use App\Http\Requests\Horario\UpdateHorarioRequest;
use App\Models\Horario;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Override;

class HorarioController extends Controller implements HasMiddleware
{
    #[Override]
    public static function middleware()
    {
        return [
            new Middleware('permission:horarios.ver', only: ['index', 'show', 'horarioFormData']),
            new Middleware('permission:horarios.crear', only: ['store']),
            new Middleware('permission:horarios.editar', only: ['update']),
            new Middleware('permission:horarios.eliminar', only: ['destroy']),
        ];
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Horario::query()->with(['laboratorio', 'docente', 'grupo']);

        // 1. Lógica de seguridad: Si el usuario es docente o estudiante, forzar su filtro
        if (request()->user()->hasRole('docente')) {
            $query->where('docente_id', request()->user()->id);
        } elseif (request()->user()->hasRole('estudiante')) {
            $grupoIds = \Illuminate\Support\Facades\DB::table('grupo_user')
                ->where('user_id', request()->user()->id)
                ->pluck('grupo_id');
            $query->whereIn('grupo_id', $grupoIds);
        } else {
            // 2. Filtro de Docente con validación de rol (solo para Admins)
            $query->when($request->filled('docente_id') && $request->docente_id !== 'all', function ($q) use ($request) {
                $q->where('docente_id', $request->docente_id)
                    ->whereHas('docente', function ($sub) {
                        $sub->role('docente');
                    });
            });
        }

        // 3. Filtros estándar
        $query->when($request->filled('laboratorio_id') && $request->laboratorio_id !== 'all', function ($q) use ($request) {
            $q->where('laboratorio_id', $request->laboratorio_id);
        });

        $query->when($request->filled('grupo_id') && $request->grupo_id !== 'all', function ($q) use ($request) {
            $q->where('grupo_id', $request->grupo_id);
        });

        // Filtro de fechas mejorado con whereBetween
        $query->when($request->filled('fecha_inicio') && $request->filled('fecha_fin'), function ($q) use ($request) {
            $q->whereBetween('fecha_inicio', [$request->fecha_inicio, $request->fecha_fin]);
        });

        $horarios = $query->latest()->paginate(10);

        // 4. Transformación segura (usando operador nullsafe ?)
        $horarios->through(function ($horario) {
            return [
                'id' => $horario->id,
                'laboratorio_id' => $horario->laboratorio_id,
                'nombre_laboratorio' => $horario->laboratorio?->nombre ?? 'Sin asignar',
                'docente_id' => $horario->docente_id,
                'nombre_docente' => $horario->docente?->name ?? 'Sin asignar',
                'grupo_id' => $horario->grupo_id,
                'nombre_grupo' => $horario->grupo?->nombre ?? 'Sin asignar',
                'dia_semana' => $horario->dia_semana,
                'dia_sema' => $horario->dia_semana,
                'hora_inicio' => $horario->hora_inicio,
                'hora_fin' => $horario->hora_fin,
                'fecha_inicio' => $horario->fecha_inicio,
                'fecha_fin' => $horario->fecha_fin,
            ];
        });

        return response()->json([
            'horarios' => $horarios,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreHorarioRequest $request)
    {
        $data = (object) $request->validated();

        $horario = Horario::create([
            'laboratorio_id' => $data->laboratorio_id,
            'docente_id' => $data->docente_id,
            'grupo_id' => $data->grupo_id,
            'dia_semana' => $data->dia_semana,
            'hora_inicio' => $data->hora_inicio,
            'hora_fin' => $data->hora_fin,
            'fecha_inicio' => $data->fecha_inicio,
            'fecha_fin' => $data->fecha_fin,
        ]);

        return response()->json([
            'message' => 'Horario creado correctamente',
            'horario' => [
                'id' => $horario->id,
                'laboratorio_id' => $horario->laboratorio_id,
                'nombre_laboratorio' => $horario->laboratorio?->nombre ?? 'Sin asignar',
                'docente_id' => $horario->docente_id,
                'nombre_docente' => $horario->docente?->name ?? 'Sin asignar',
                'grupo_id' => $horario->grupo_id,
                'nombre_grupo' => $horario->grupo?->nombre ?? 'Sin asignar',
                'dia_semana' => $horario->dia_semana,
                'hora_inicio' => $horario->hora_inicio,
                'hora_fin' => $horario->hora_fin,
                'fecha_inicio' => $horario->fecha_inicio,
                'fecha_fin' => $horario->fecha_fin,
            ],
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Horario $horario)
    {
        $horario->load(['laboratorio', 'docente', 'grupo']);

        return response()->json([
            'horario' => [
                'id' => $horario->id,
                'laboratorio_id' => $horario->laboratorio_id,
                'nombre_laboratorio' => $horario->laboratorio?->nombre ?? 'Sin asignar',
                'docente_id' => $horario->docente_id,
                'nombre_docente' => $horario->docente?->name ?? 'Sin asignar',
                'grupo_id' => $horario->grupo_id,
                'nombre_grupo' => $horario->grupo?->nombre ?? 'Sin asignar',
                'dia_semana' => $horario->dia_semana,
                'hora_inicio' => $horario->hora_inicio,
                'hora_fin' => $horario->hora_fin,
                'fecha_inicio' => $horario->fecha_inicio,
                'fecha_fin' => $horario->fecha_fin,
            ],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateHorarioRequest $request, Horario $horario)
    {
        $data = (object) $request->validated();

        $horario->update([
            'laboratorio_id' => $data->laboratorio_id,
            'docente_id' => $data->docente_id,
            'grupo_id' => $data->grupo_id,
            'dia_semana' => $data->dia_semana,
            'hora_inicio' => $data->hora_inicio,
            'hora_fin' => $data->hora_fin,
            'fecha_inicio' => $data->fecha_inicio,
            'fecha_fin' => $data->fecha_fin,
        ]);

        return response()->json([
            'message' => 'Horario actualizado correctamente',
            'horario' => [
                'id' => $horario->id,
                'laboratorio_id' => $horario->laboratorio_id,
                'nombre_laboratorio' => $horario->laboratorio?->nombre ?? 'Sin asignar',
                'docente_id' => $horario->docente_id,
                'nombre_docente' => $horario->docente?->name ?? 'Sin asignar',
                'grupo_id' => $horario->grupo_id,
                'nombre_grupo' => $horario->grupo?->nombre ?? 'Sin asignar',
                'dia_semana' => $horario->dia_semana,
                'hora_inicio' => $horario->hora_inicio,
                'hora_fin' => $horario->hora_fin,
                'fecha_inicio' => $horario->fecha_inicio,
                'fecha_fin' => $horario->fecha_fin,
            ],
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Horario $horario)
    {
        if ($horario->grupo && $horario->grupo->materia && $horario->grupo->materia->semestreAcademico && $horario->grupo->materia->semestreAcademico->isClosed()) {
            return response()->json([
                'message' => 'No se puede eliminar un horario que pertenece a un semestre cerrado.',
            ], 422);
        }

        $horario->delete(null);

        return response()->json([
            'message' => 'Horario eliminado correctamente',
        ]);
    }

    public function horarioFormData()
    {
        $laboratorios = \App\Models\Laboratorio::query()
            ->where('activo', true)
            ->select(['id', 'nombre'])
            ->latest()
            ->get();

        $docentes = \App\Models\User::query()
            ->role('docente')
            ->where('estado', 'activo')
            ->select(['id', 'name as nombre'])
            ->latest()
            ->get();

        $grupos = \App\Models\Grupo::query()
            ->with('materia:id,nombre')
            ->select(['id', 'nombre', 'materia_id'])
            ->latest()
            ->get()
            ->map(function ($grupo) {
                return [
                    'id' => $grupo->id,
                    'nombre' => $grupo->materia ? ($grupo->materia->nombre . ' - ' . $grupo->nombre) : $grupo->nombre,
                ];
            });

        return response()->json([
            'laboratorios' => $laboratorios,
            'docentes' => $docentes,
            'grupos' => $grupos,
        ]);
    }
}
