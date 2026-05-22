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
        $roles = Role::query()
            ->select(['id', 'name'])
            ->addSelect(['users_count' => function ($query) {
                $query->selectRaw('count(*)')
                    ->from(config('permission.table_names.model_has_roles'))
                    ->whereColumn(config('permission.column_names.role_pivot_key') ?: 'role_id', 'roles.id');
            }])
            ->with('permissions:id,name')
            ->get()
            ->map(function ($role) {
                return [
                    'id'          => $role->id,
                    'name'        => $role->name,
                    'permissions' => $role->permissions->pluck('name'),
                    'users_count' => (int) $role->users_count,
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
