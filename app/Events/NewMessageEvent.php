<?php

namespace App\Events;

use App\Models\Mensaje;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewMessageEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $mensaje;

    public function __construct(Mensaje $mensaje)
    {
        $this->mensaje = $mensaje;
    }

    public function broadcastOn()
    {
        return new Channel('chat.' . $this->mensaje->idChat);
    }

    public function broadcastWith()
    {
        return [
            'idMensaje' => $this->mensaje->idMensaje,
            'idChat' => $this->mensaje->idChat,
            'idUsuario' => $this->mensaje->idUsuario,
            'contenido' => $this->mensaje->contenido,
            'leido' => $this->mensaje->leido,
            'created_at' => $this->mensaje->created_at,
            'usuario' => [
                'idUsuario' => $this->mensaje->usuario->idUsuario,
                'nombre' => $this->mensaje->usuario->nombre,
                'apellido' => $this->mensaje->usuario->apellido,
            ]
        ];
    }
}