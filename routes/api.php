<?php

use App\Http\Controllers\ClientController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ManagerController;

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
        Route::get('/client/projects-with-phases', [ClientController::class, 'getClientProjectsWithPhases']);

        // In api.php or your routes file
        Route::get('/client/project/{id}/with-phases', [ClientController::class, 'getProjectWithPhases']);

        Route::get('/client/project/{id}/details', [ClientController::class, 'getProjectDetails']);

});

// RUTAS PARA cliente VALIDADA POR MIDDLEWARE AUTH (PARA TOKEN JWT) Y CHECKROLE (PARA VALIDAR ROL DEL TOKEN)
Route::middleware(['auth.jwt', 'checkRoleMW:manager'])->group(function () { 

        // In api.php or your routes file
        Route::get('/manager/projects-with-phases', [ManagerController::class, 'getClientProjectsWithPhases']);

        // In api.php or your routes file
        Route::get('/manager/project/{id}/with-phases', [ManagerController::class, 'getProjectWithPhases']);

        Route::get('/manager/project/{id}/details', [ManagerController::class, 'getProjectDetails']);
});
