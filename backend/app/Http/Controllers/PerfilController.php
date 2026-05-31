<?php

namespace App\Http\Controllers;

use App\Models\Perfil;
use Illuminate\Http\Request;

class PerfilController extends Controller
{
    /**
     * Display the authenticated user's profile.
     */
    public function show(Request $request)
    {
        $user = $request->user();

        // Obtener o crear el perfil asociado al usuario autenticado
        $perfil = Perfil::firstOrCreate(
            ['user_id' => $user->id],
            ['telefono' => '']
        );

        $perfil->load('usuario');

        return response()->json([
            'status' => 'success',
            'perfil' => $perfil
        ]);
    }

    /**
     * Update the authenticated user's profile and account settings.
     */
    public function update(Request $request)
    {
        $user = $request->user();

        // Validación de datos personales, profesionales y credenciales
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'telefono' => 'required|string|max:50',
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        // Actualizar datos de la cuenta de usuario principal
        $user->name = $validated['name'];
        $user->email = $validated['email'];

        if (!empty($validated['password'])) {
            $user->password = bcrypt($validated['password']);
        }
        
        $user->save();

        // Actualizar datos complementarios de perfil
        $perfil = Perfil::firstOrCreate(
            ['user_id' => $user->id],
            ['telefono' => '']
        );

        $perfil->update([
            'telefono' => $validated['telefono'],
        ]);

        $perfil->load('usuario');

        return response()->json([
            'status' => 'success',
            'message' => 'Perfil actualizado correctamente.',
            'perfil' => $perfil
        ]);
    }
}
