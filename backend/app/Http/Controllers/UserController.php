<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Mail\TemporaryPasswordMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Str;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = User::query()->with('roles:id,name');

        $query->when($request->filled('search'), function ($q) use ($request) {
            $search = $request->search;
            $q->where(function ($subQuery) use ($search) {
                $subQuery->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%");
            });
        });

        $query->when($request->filled('estado') && $request->estado !== 'all', function ($q) use ($request) {
            $q->where('estado', $request->estado);
        });

        $query->when($request->filled('role') && $request->role !== 'all', function ($q) use ($request) {
            $q->role($request->role);
        });

        $users = $query->latest()->paginate(10);

        $users->through(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'estado' => $user->estado,
                'role' => $user->roles->first()?->name ?? 'sin_rol',
            ];
        });

        return response()->json([
            'users' => $users,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request)
    {
        $data = (object) $request->validated();

        $passwordTemp = Str::random(10);

        /** @var User $user */
        $user = User::create([
            'name' => $data->name,
            'email' => $data->email,
            'estado' => $data->estado,
            'password' => $passwordTemp,
        ]);

        $user->syncRoles([$data->role]);

        Mail::to($user->email)->send(new TemporaryPasswordMail($user, $passwordTemp));

        return response()->json([
            'message' => 'Usuario creado correctamente',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'estado' => $user->estado,
                'role' => $user->roles()->first()->name,
            ]
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->roles()->first()->name ?? 'sin_rol',
                'estado' => $user->estado
            ]
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        $data = $request->validated();

        if ($data['email'] !== $user->email) {
            $tempPassword = Str::random(10);

            $data['password'] = Hash::make($tempPassword);
        }

        $user->update($data);

        $user->syncRoles([$data['role']]);

        $user->refresh();

        if (isset($tempPassword)) {
            Mail::to($user->email)->send(new TemporaryPasswordMail($user, $tempPassword));
        }

        return response()->json([
            'message' => 'Usuario actualizado correctamente',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->roles()->first() ?? 'sin_rol',
                'estado' => $user->estado
            ]
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        $user->delete(null);

        return response()->json([
            "message" => 'Usuario eliminado correctamente',
        ]);
    }

    public function getRoles()
    {
        $roles = Role::select('id', 'name')->get();

        return response()->json($roles);
    }
}
