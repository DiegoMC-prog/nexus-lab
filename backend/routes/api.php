<?php

use App\Events\EstacionDetectadaEnCanal;
use App\Http\Controllers\AgenteController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CarreraController;
use App\Http\Controllers\CursoController;
use App\Http\Controllers\EstacionController;
use App\Http\Controllers\GrupoController;
use App\Http\Controllers\HorarioController;
use App\Http\Controllers\LaboratorioController;
use App\Http\Controllers\LogsTelemetriaController;
use App\Http\Controllers\MateriaController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SemestreAcademicoController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ComandoController;
use App\Http\Controllers\ConfigAlertaController;
use App\Http\Controllers\LogsComandoController;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\AlertasController;
use App\Http\Controllers\RestriccionAplicacionController;
use App\Http\Controllers\DocenteDashboardController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/test-websocket', function () {

    broadcast(new EstacionDetectadaEnCanal([
        'laboratorio_target_id' => 1,
        'mensaje' => 'Hola desde API Laravel',
        'hora' => now()->toDateTimeString(),
    ]));

    return response()->json([
        'ok' => true
    ]);
});

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

    //modulo gestion de estaciones
    Route::apiResource('estaciones', EstacionController::class)->parameters([
        'estaciones' => 'estacion'
    ]);;

    Route::apiResource('logs-telemetria', LogsTelemetriaController::class)->parameters([
        'logs-telemetria' => 'logsTelemetria'
    ]);

    Route::apiResource('comandos', ComandoController::class);

    Route::apiResource('config-alertas', ConfigAlertaController::class)->parameters([
        'config-alertas' => 'configAlerta'
    ]);

    Route::apiResource('logs-comandos', LogsComandoController::class)->parameters([
        'logs-comandos' => 'logsComando'
    ]);

    Route::get('perfil', [PerfilController::class, 'show']);
    Route::put('perfil', [PerfilController::class, 'update']);

    Route::apiResource('alertas', AlertasController::class)->parameters([
        'alertas' => 'alerta'
    ]);

    //modulo gestion de carreras
    Route::apiResource('carreras', CarreraController::class);

    //modulo semestres
    Route::post('semestres/{semestre}/close', [SemestreAcademicoController::class, 'close']);
    Route::apiResource('semestres', SemestreAcademicoController::class);

    //modulo materia
    Route::get('/materias/form-data', [MateriaController::class, 'MateriaFormData']);
    Route::apiResource('materias', MateriaController::class);

    //modulo grupos
    Route::get('/grupos/estudiantes/search', [GrupoController::class, 'searchEstudiante']);
    Route::get('/grupos/{grupo}/estudiantes', [GrupoController::class, 'listarEstudiantes']);
    Route::put('grupos/{grupo}/estudiantes', [GrupoController::class, 'actualizarEstudiantesGrupo']);
    Route::get('/grupos/form-data', [GrupoController::class, 'GrupoFormData']);
    Route::apiResource('grupos', GrupoController::class);

    //modulo horarios
    Route::get('/horarios/form-data', [HorarioController::class, 'horarioFormData']);
    Route::apiResource('horarios', HorarioController::class);

    Route::apiResource('laboratorios', LaboratorioController::class);

    //modulo gestion de usuarios
    Route::get('users/roles', [UserController::class, 'getRoles'])->middleware('permission:usuarios.ver');
    Route::apiResource('users', UserController::class);

    //modulo roles
    Route::get('/roles/permisos', [RoleController::class, 'getPermisos']);
    Route::put('/roles/{id}/permissions', [RoleController::class, 'store']);
    Route::get('roles', [RoleController::class, 'index']);

    // General Dashboard
    Route::get('/dashboard/stats', [\App\Http\Controllers\DashboardController::class, 'getStats']);

    // Modulo Restricciones de Aplicaciones
    Route::apiResource('restricciones-aplicaciones', RestriccionAplicacionController::class)->parameters([
        'restricciones-aplicaciones' => 'restriccionAplicacion'
    ]);
    Route::get('logs-aplicaciones-prohibidas', [RestriccionAplicacionController::class, 'getLogs']);
    Route::post('restricciones/reportar-infraccion', [RestriccionAplicacionController::class, 'reportViolation']);

    // Modulo Docente Dashboard y Monitoreo en Vivo
    Route::get('/docente/dashboard', [DocenteDashboardController::class, 'getHomeDashboard']);
    Route::get('/docente/clase-activa', [DocenteDashboardController::class, 'getClaseActivaRealTime']);
    Route::post('/docente/estaciones/{id}/accion', [DocenteDashboardController::class, 'ejecutarAccionEstacion']);
});
