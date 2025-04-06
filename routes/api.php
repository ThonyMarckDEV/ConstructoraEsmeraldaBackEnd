<?php

use App\Http\Controllers\ChatController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Rutas públicas (no requieren autenticación)
Route::post('/login', [AuthController::class, 'login']);
Route::post('/refresh', [AuthController::class, 'refresh']);


// RUTAS PARA cliente VALIDADA POR MIDDLEWARE AUTH (PARA TOKEN JWT) Y CHECKROLE (PARA VALIDAR ROL DEL TOKEN)
Route::middleware(['auth.jwt', 'checkRoleMW:cliente'])->group(function () { 

        // In api.php or your routes file
        Route::get('/client/projects-with-phases-client', [UserController::class, 'getClientProjectsWithPhasesClient']);

        // In api.php or your routes file
        Route::get('/client/project/{id}/with-phases', [UserController::class, 'getProjectWithPhases']);

        Route::get('/client/project/{id}/details', [UserController::class, 'getProjectDetails']);

});

// RUTAS PARA cliente VALIDADA POR MIDDLEWARE AUTH (PARA TOKEN JWT) Y CHECKROLE (PARA VALIDAR ROL DEL TOKEN)
Route::middleware(['auth.jwt', 'checkRoleMW:manager'])->group(function () { 

        // In api.php or your routes file
        Route::get('/manager/projects-with-phases-manager', [UserController::class, 'getClientProjectsWithPhasesManager']);

        // In api.php or your routes file
        Route::get('/manager/projects/{id}/with-phases', [UserController::class, 'getProjectWithPhasesClient']);

        Route::get('/manager/projects/{id}/details', [UserController::class, 'getProjectDetails']);

});
