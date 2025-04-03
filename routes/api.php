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
        Route::get('/client/projects-with-phases', [UserController::class, 'getClientProjectsWithPhases']);

        // In api.php or your routes file
        Route::get('/client/projects/{id}/with-phases', [UserController::class, 'getProjectWithPhases']);

        Route::get('/client/projects/{id}/details', [UserController::class, 'getProjectDetails']);

});


Route::middleware(['auth.jwt', 'checkRolesMW'])->group(function () { 
    // Rutas de chat
    Route::get('/chats', [ChatController::class, 'getChats']);
    Route::get('/chats/{idChat}', [ChatController::class, 'getChat']);
    Route::post('/chats/message', [ChatController::class, 'sendMessage']);
    Route::post('/chats/create', [ChatController::class, 'createChat']);
    Route::put('/chats/message/read/{idMensaje}', [ChatController::class, 'markMessageAsRead']);
});