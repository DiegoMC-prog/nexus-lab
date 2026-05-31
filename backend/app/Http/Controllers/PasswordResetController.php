<?php

namespace App\Http\Controllers;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    public function sendResetLink(Request $request)
    {
        $request->validate(['email' => 'required|email|exists:users,email']);

        $token = Str::random(64);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            [
                'token' => $token,
                'created_at' => Carbon::now()
            ]
        );

        $frontendUrl = env('FRONTEND_URL') . "/reset-password?token={$token}&email={$request->email}";

        Mail::send('emails.auth.reset_password', ['reset_url' => $frontendUrl], function ($message) use ($request) {
            $message->to($request->email)
                ->subject('Restablecer Contraseña - NEXUSLAB');
        });

        return response()->json(['message' => 'Te hemos enviado un correo con el enlace de recuperación.']);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'token' => 'required|string', // El token largo que viajó por la URL
            'password' => 'required|string|min:8|confirmed',
        ]);

        $tokenRecord = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->where('token', $request->token)
            ->first();

        if (!$tokenRecord || Carbon::parse($tokenRecord->created_at)->addMinutes(60)->isPast()) {
            return response()->json(['message' => 'El enlace de recuperación es inválido o ha expirado.'], 400);
        }

        $user = User::query()->where('email', $request->email)->first();
        $user->password = Hash::make($request->password);
        $user->save();

        DB::table('password_reset_tokens')->where('email', $request->email)->delete();
        $user->tokens()->delete();

        return response()->json(['message' => 'Contraseña cambiada con éxito. Ya puedes iniciar sesión.']);
    }

    public function validateToken(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'token' => 'required|string',
        ]);

        // Buscar si el token existe
        $tokenRecord = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->where('token', $request->token)
            ->first();

        // Si no existe o pasaron más de 60 minutos, el token ya no sirve
        if (!$tokenRecord || \Carbon\Carbon::parse($tokenRecord->created_at)->addMinutes(60)->isPast()) {
            return response()->json([
                'valid' => false,
                'message' => 'El enlace de recuperación ya ha sido utilizado o ha expirado.'
            ], 400);
        }

        return response()->json([
            'valid' => true,
            'message' => 'Token válido.'
        ], 200);
    }
}
