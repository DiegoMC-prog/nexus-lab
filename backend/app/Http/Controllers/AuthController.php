<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(LoginRequest $request)
    {
        $credenciales = (object) $request->validated();

        $user = User::query()->where('email', $credenciales->email)->first();

        if (!$user) {
            return response()->json([
                'message' => 'El correo electrónico no se encuentra registrado.',
            ], 404);
        }

        if ($user->estado === 'inactivo') {
            return response()->json([
                'message' => 'Su cuenta está inactiva. Por favor, contacte al administrador.',
            ], 403);
        }

        if ($user->estado === 'bloqueado por administrador' || $user->estado === 'bloqueado') {
            return response()->json([
                'message' => 'Su cuenta ha sido bloqueada por el administrador.',
            ], 403);
        }

        if (!Hash::check($credenciales->password, $user->password)) {
            return response()->json([
                'message' => 'Credenciales incorrectas.',
            ], 401);
        }

        $user->tokens()->delete();

        $dispositivo = $user->dispositivos()->where('fingerprint', $credenciales->fingerprint)->exists();

        if ($dispositivo) {
            return response()->json([
                'message' => 'Usuario autenticado, Bienvenido de vuelta',
                'token' => $user->createToken('token')->plainTextToken,
                'user' =>  [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'estado' => $user->estado,
                    'role' => $user->role,
                    'permisos' => $user->permisos,
                ]
            ]);
        }

        $response = [
            'requires_otp' => true,
            'message' => 'Por favor, ingrese el código de su aplicación autenticadora (TOTP).'
        ];

        return response()->json($response);
    }

    public function verifyOtp(VerifyOtpRequest $request)
    {
        $validado = (object) $request->validated();

        $user = User::query()->where('email', $validado->email)->first();

        if (empty($user->totp_secret)) {
            return response()->json(['message' => 'Autenticación 2FA no configurada para este usuario.'], 422);
        }

        $google2fa = app('pragmarx.google2fa');
        $valid = $google2fa->verifyKey($user->totp_secret, $validado->otp_code);

        if (!$valid) {
            return response()->json(['message' => 'Código incorrecto o expirado'], 422);
        }

        if ($user->estado === 'inactivo') {
            return response()->json([
                'message' => 'Su cuenta está inactiva. Por favor, contacte al administrador.',
            ], 403);
        }

        if ($user->estado === 'bloqueado por administrador' || $user->estado === 'bloqueado') {
            return response()->json([
                'message' => 'Su cuenta ha sido bloqueada por el administrador.',
            ], 403);
        }

        $user->dispositivos()->create([
            'fingerprint' => $request->fingerprint,
            'nombre_dispositivo' => 'pc_' . rand(0, 1000000),
        ]);



        return response()->json([
            'message' => 'Usuario autenticado, Bienvenido de vuelta',
            'token' => $user->createToken('token')->plainTextToken,
            'user' =>  [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'estado' => $user->estado,
                'role' => $user->role,
                'permisos' => $user->permisos,
            ]
        ]);
    }

    public function logout(Request $request)
    {
        $user = $request->user();

        if ($user) {
            $user->tokens()->delete();

            return response()->json([
                'message' => 'Sesión cerrada exitosamente.'
            ], 200);
        }

        return response()->json(['message' => 'Usuario no encontrado'], 401);
    }
}
