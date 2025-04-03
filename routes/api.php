<?php

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

        // Get all projects for the client
        Route::get('/client/projects', [UserController::class, 'getProjects']);
        
        // Get phases for a specific project
        Route::get('/client/projects/{id}/phases', [UserController::class, 'getProjectPhases']);

        // In api.php or your routes file
        Route::get('/client/projects/{id}/with-phases', [UserController::class, 'getProjectWithPhases']);

        Route::get('/client/projects/{id}/details', [UserController::class, 'getProjectDetails']);

});