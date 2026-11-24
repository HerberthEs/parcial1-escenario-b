<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\MantenimientoController;
use App\Http\Controllers\RenoController;
use App\Http\Controllers\VueloController;
use Illuminate\Support\Facades\Route;

/*
| Rutas -> Controladores -> Servicios -> Repositorios.
| 'rol:JEFE_ESTABLO' = middleware AutenticarJwt (401 sin token / 403 rol distinto).
| Todas las rutas quedan bajo el prefijo /api.
*/

Route::post('/auth/login', [AuthController::class, 'login']);

// Renos
Route::get('/renos', [RenoController::class, 'index']);
Route::get('/renos/{id}', [RenoController::class, 'show'])->whereNumber('id');

Route::middleware('rol:JEFE_ESTABLO')->group(function () {
    Route::post('/renos', [RenoController::class, 'store']);
    Route::put('/renos/{id}', [RenoController::class, 'update'])->whereNumber('id');
    Route::delete('/renos/{id}', [RenoController::class, 'destroy'])->whereNumber('id');
    Route::patch('/renos/{id}/asignacion', [RenoController::class, 'asignar'])->whereNumber('id');
});

// Mantenimientos (públicos)
Route::post('/mantenimientos', [MantenimientoController::class, 'store']);
Route::get('/mantenimientos', [MantenimientoController::class, 'index']);
Route::get('/mantenimientos/{id}', [MantenimientoController::class, 'show'])->whereNumber('id');
Route::patch('/mantenimientos/{id}/estado', [MantenimientoController::class, 'cambiarEstado'])->whereNumber('id');

// Extra
Route::get('/vuelo/resumen', [VueloController::class, 'resumen']);
