<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\LaboratorioController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::post('/login', [AuthController::class, 'login']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink']);
Route::post('/reset-password', [PasswordResetController::class, 'resetPassword']);
Route::post('/validate-reset-token', [PasswordResetController::class, 'validateToken']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', function () {
        $user = request()->user();

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'estado' => $user->estado,
            'role' => $user->role,
            'permisos' => $user->permisos,
        ]);
    });

    Route::get('/user/permisos', function () {
        $permisos = request()->user()->getAllPermissions()->pluck('name');
        
        return response()->json($permisos);
    });

    Route::apiResource('laboratorios', LaboratorioController::class);
    
    Route::get('users/roles', [UserController::class, 'getRoles'])->middleware('permission:usuarios.ver');
    Route::apiResource('users', UserController::class);

    Route::get('/roles/permisos', [RoleController::class, 'getPermisos']);
    Route::put('/roles/{id}/permissions', [RoleController::class, 'store']);
    Route::get('roles', [RoleController::class, 'index']);
});
