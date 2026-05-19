<?php

namespace App\Http\Controllers;

use App\Http\Requests\Role\StoreRoleRequest;
use Spatie\Permission\Models\Role;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $roles = Role::query()->with('permissions:id,name')->get(['id', 'name'])->map(function ($role) {
            return [
                'id'          => $role->id,
                'name'        => $role->name,
                'permissions' => $role->permissions->pluck('name'),
            ];
        });

        return response()->json([
            "roles" => $roles,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRoleRequest $request, string | int $id)
    {
        $data = (object) $request->validated();

        $role = Role::findOrFail($id);

        $role->syncPermissions($data->permissions);

        return response()->json([
            'status' => 'success',
            'message' => 'Permisos actualizados correctamente para el rol: ' . $role->name
        ], 200);
    }

    public function getPermisos()
    {
        $permisos = Permission::pluck('name');

        return response()->json([
            'permisos' => $permisos,
        ]);
    }
}
