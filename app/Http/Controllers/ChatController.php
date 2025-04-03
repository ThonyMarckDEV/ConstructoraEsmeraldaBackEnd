<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\Mensaje;
use App\Models\Proyecto;
use App\Models\Usuario;
use App\Events\NewMessageEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    // Obtener todos los chats del usuario autenticado
    public function getChats(Request $request)
    {
        $idUsuario = $request->user()->idUsuario;
        $rol = $request->user()->rol;
        
        $query = Chat::with(['cliente', 'encargado', 'proyecto'])
                ->withCount(['mensajes as mensajes_no_leidos' => function($q) use ($idUsuario) {
                    $q->where('leido', false)->where('idUsuario', '!=', $idUsuario);
                }]);
        
        if ($rol === 'cliente') {
            $query->where('idCliente', $idUsuario);
        } else if ($rol === 'manager') {
            $query->where('idEncargado', $idUsuario);
        }
        
        $chats = $query->get();
        
        return response()->json($chats);
    }
    
    // Obtener un chat específico con sus mensajes
    public function getChat($idChat, Request $request)
    {
        $idUsuario = $request->user()->idUsuario;
        
        $chat = Chat::with(['cliente', 'encargado', 'proyecto', 'mensajes.usuario'])
                ->findOrFail($idChat);
        
        // Verificar que el usuario tenga acceso a este chat
        if ($chat->idCliente != $idUsuario && $chat->idEncargado != $idUsuario) {
            return response()->json(['error' => 'No tienes acceso a este chat'], 403);
        }
        
        // Marcar como leídos todos los mensajes que no son del usuario actual
        Mensaje::where('idChat', $idChat)
              ->where('idUsuario', '!=', $idUsuario)
              ->where('leido', false)
              ->update(['leido' => true]);
        
        return response()->json($chat);
    }
    
    // Enviar un mensaje
    public function sendMessage(Request $request)
    {
        $request->validate([
            'idChat' => 'required|exists:chats,idChat',
            'contenido' => 'required|string'
        ]);
        
        $idUsuario = $request->user()->idUsuario;
        $chat = Chat::findOrFail($request->idChat);
        
        // Verificar que el usuario tenga acceso a este chat
        if ($chat->idCliente != $idUsuario && $chat->idEncargado != $idUsuario) {
            return response()->json(['error' => 'No tienes acceso a este chat'], 403);
        }
        
        $mensaje = Mensaje::create([
            'idChat' => $request->idChat,
            'idUsuario' => $idUsuario,
            'contenido' => $request->contenido,
            'leido' => false
        ]);
        
        $mensaje->load('usuario');
        
        // Broadcast del nuevo mensaje
        broadcast(new NewMessageEvent($mensaje))->toOthers();
        
        return response()->json($mensaje);
    }
    
    // Crear un nuevo chat
    public function createChat(Request $request)
    {
        $request->validate([
            'idProyecto' => 'required|exists:proyectos,idProyecto',
        ]);
        
        $idUsuario = $request->user()->idUsuario;
        $rol = $request->user()->rol;
        
        // Solo los clientes pueden crear chats
        if ($rol !== 'cliente') {
            return response()->json(['error' => 'Solo los clientes pueden crear chats'], 403);
        }
        
        $proyecto = Proyecto::findOrFail($request->idProyecto);
        
        // Verificar si ya existe un chat para este proyecto y cliente
        $existingChat = Chat::where('idProyecto', $proyecto->idProyecto)
                        ->where('idCliente', $idUsuario)
                        ->first();
        
        if ($existingChat) {
            return response()->json($existingChat);
        }
        
        // Crear nuevo chat
        $chat = Chat::create([
            'idProyecto' => $proyecto->idProyecto,
            'idCliente' => $idUsuario,
            'idEncargado' => $proyecto->idEncargado
        ]);
        
        $chat->load(['cliente', 'encargado', 'proyecto']);
        
        return response()->json($chat);
    }
    
    // Marcar un mensaje como leído
    public function markMessageAsRead($idMensaje, Request $request)
    {
        $idUsuario = $request->user()->idUsuario;
        
        $mensaje = Mensaje::findOrFail($idMensaje);
        $chat = Chat::findOrFail($mensaje->idChat);
        
        // Verificar que el usuario tenga acceso a este chat
        if ($chat->idCliente != $idUsuario && $chat->idEncargado != $idUsuario) {
            return response()->json(['error' => 'No tienes acceso a este chat'], 403);
        }
        
        // Solo marcar como leído si el mensaje no es del usuario actual
        if ($mensaje->idUsuario != $idUsuario) {
            $mensaje->leido = true;
            $mensaje->save();
        }
        
        return response()->json(['success' => true]);
    }
}