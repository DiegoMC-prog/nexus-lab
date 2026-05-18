<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Mail\UserOtpMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    public function login(LoginRequest $request)
    {
        $credenciales = (object) $request->validated();

        $user = User::query()->where('email', $credenciales->email)->first();

        if (!$user || !Hash::check($credenciales->password, $user->password)) {
            return response()->json([
                'message' => 'Credenciales Invalidas',
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

        $otp = str_pad((string)random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

        $user->update([
            'otp_code' => Hash::make($otp),
            'otp_expires_at' => now()->addMinutes(15),
        ]);

        Mail::to($user->email)->send(new UserOtpMail($otp));

        $response = [
            'requires_otp' => true,
            'message' => 'Código enviado a tu correo.'
        ];

        if (app()->environment('local')) {
            $response['otp'] = $otp;
        }

        return response()->json($response);
    }

    public function verifyOtp(VerifyOtpRequest $request)
    {
        $validado = (object) $request->validated();

        $user = User::query()->where('email', $validado->email)->first();

        if (!Hash::check($validado->otp_code, $user->otp_code) || now()->isAfter($user->otp_expires_at)) {
            return response()->json(['message' => 'Código incorrecto o expirado'], 422);
        }

        $user->dispositivos()->create([
            'fingerprint' => $request->fingerprint,
            'nombre_dispositivo' => 'pc_' . rand(0, 1000000),
        ]);

        $user->update([
            'otp_code' => null,
            'otp_expires_at' => null
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
