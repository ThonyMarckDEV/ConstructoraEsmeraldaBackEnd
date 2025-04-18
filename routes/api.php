<?php

use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ManagerController;
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

Route::post('/validate-refresh-token', [AuthController::class, 'validateRefreshToken']);

Route::get('/manager/project/{id}/modelo-file', [ManagerController::class, 'descargarmodelo']);


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
        Route::get('/manager/projects-with-phases', [ManagerController::class, 'getManagerProjectsWithPhases']);

        // In api.php or your routes file
        Route::get('/manager/project/{id}/with-phases', [ManagerController::class, 'getProjectWithPhases']);

        Route::get('/manager/project/{id}/details', [ManagerController::class, 'getProjectDetails']);

        Route::put('/manager/project/update-phase/{id}', [ManagerController::class, 'updatePhase']);

         // Rutas para subida de archivos y fotos
        Route::post('/manager/project/fase/upload-file', [ManagerController::class, 'uploadFile']);
        Route::post('/manager/project/fase/upload-photo', [ManagerController::class, 'uploadPhoto']);
        Route::delete('/manager/project/files/delete', [ManagerController::class, 'deleteFile']);

        Route::post('/manager/project/subir-modelo', [ManagerController::class, 'subirModelo']);

         // Get projects analytics 
        Route::get('/encargados/{idEncargado}/projects/analytics', [DashboardController::class ,'getAnalytics']);
  
});


// RUTAS PARA Roles cliente y manager
Route::middleware(['auth.jwt', 'checkRolesMW'])->group(function () { 

        Route::get('/project/{id}/modelo', [ManagerController::class, 'obtenermodelo']);

        Route::get('/project/files/download/{path}', [ClientController::class, 'download'])
        ->where('path', '.*')
        ->name('files.download');
  
});

        


