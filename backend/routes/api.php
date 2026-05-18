<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\LaboratorioController;
use App\Http\Controllers\PasswordResetController;
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
        return request()->user();
    });

    Route::apiResource('laboratorios', LaboratorioController::class);
    Route::apiResource('users', UserController::class);
    Route::get('/roles', [UserController::class, 'getRoles']);
});