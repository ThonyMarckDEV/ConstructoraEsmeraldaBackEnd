<?php

use App\Http\Controllers\ProyectoController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ClientProjectController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ManagerController;
use App\Http\Controllers\ManagerProjectController;
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

Route::get('/manager/project/{id}/{idFase}/modelo-file', [ManagerProjectController::class, 'descargarmodelo']);


// RUTAS PARA cliente VALIDADA POR MIDDLEWARE AUTH (PARA TOKEN JWT) Y CHECKROLE (PARA VALIDAR ROL DEL TOKEN)
Route::middleware(['auth.jwt', 'checkRoleMW:cliente'])->group(function () { 

        // In api.php or your routes file
        Route::get('/client/projects-with-phases', [ClientProjectController::class, 'getClientProjectsWithPhases']);

        // In api.php or your routes file
        Route::get('/client/project/{id}/with-phases', [ClientProjectController::class, 'getProjectWithPhases']);

        Route::get('/client/project/{id}/details', [ClientProjectController::class, 'getProjectDetails']);

});

// RUTAS PARA cliente VALIDADA POR MIDDLEWARE AUTH (PARA TOKEN JWT) Y CHECKROLE (PARA VALIDAR ROL DEL TOKEN)
Route::middleware(['auth.jwt', 'checkRoleMW:manager'])->group(function () { 


        // Rutas en ManagerProjectController
        Route::get('/manager/projects-with-phases', [ManagerProjectController::class, 'getManagerProjectsWithPhases']);
        Route::get('/manager/project/{id}/with-phases', [ManagerProjectController::class, 'getProjectWithPhases']);
        Route::get('/manager/project/{id}/details', [ManagerProjectController::class, 'getProjectDetails']);
        Route::put('/manager/project/update-phase/{id}', [ManagerProjectController::class, 'updatePhase']);
         // Rutas para subida de archivos y fotos
        Route::post('/manager/project/fase/upload-file', [ManagerProjectController::class, 'uploadFile']);
        Route::post('/manager/project/fase/upload-photo', [ManagerProjectController::class, 'uploadPhoto']);
        Route::delete('/manager/project/files/delete', [ManagerProjectController::class, 'deleteFile']);
        Route::post('/manager/project/subir-modelo', [ManagerProjectController::class, 'subirModelo']);


         // Get projects analytics 
        Route::get('/encargados/{idEncargado}/projects/analytics', [DashboardController::class ,'getAnalytics']);
  
});

// RUTAS PARA cliente VALIDADA POR MIDDLEWARE AUTH (PARA TOKEN JWT) Y CHECKROLE (PARA VALIDAR ROL DEL TOKEN)
Route::middleware(['auth.jwt', 'checkRoleMW:admin'])->group(function () { 

         // ClientController CRUD routes
        Route::get('/admin/clientes', [ClientController::class, 'index']);
        Route::post('/admin/clientes', [ClientController::class, 'store']);
        Route::get('/admin/clientes/{id}', [ClientController::class, 'show']);
        Route::put('/admin/clientes/{id}', [ClientController::class, 'update']);
        Route::delete('/admin/clientes/{id}', [ClientController::class, 'destroy']);

        // ManagerController CRUD routes
        Route::get('/admin/encargados', [ManagerController::class, 'index']);
        Route::post('/admin/encargados', [ManagerController::class, 'store']);
        Route::get('/admin/encargados/{id}', [ManagerController::class, 'show']);
        Route::put('/admin/encargados/{id}', [ManagerController::class, 'update']);
        Route::delete('/admin/encargados/{id}', [ManagerController::class, 'destroy']);

        //Proyecto Controller CRUD routes
        Route::get('/admin/proyectos', [ProyectoController::class, 'index']);
        Route::post('/admin/proyectos', [ProyectoController::class, 'store']);
        Route::put('/admin/proyectos/{id}', [ProyectoController::class, 'update']);
        Route::delete('/admin/proyectos/{id}', [ProyectoController::class, 'destroy']);
        Route::post('/admin/proyectos/asignar', [ProyectoController::class, 'asignar']);
        Route::get('/admin/proyectos/encargados', [ProyectoController::class, 'getEncargados']);
        Route::get('/admin/proyectos/clientes', [ProyectoController::class, 'getClientes']);

  
});



// RUTAS PARA Roles cliente y manager
Route::middleware(['auth.jwt', 'checkRolesMW'])->group(function () { 

        Route::get('/project/{idproyecto}/{idfase}/modelo', [ManagerProjectController::class, 'obtenermodelo']);

        Route::get('/project/files/download/{path}', [ClientProjectController::class, 'download'])
        ->where('path', '.*')
        ->name('files.download');
  
});

        


