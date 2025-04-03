<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
    use HasFactory;

    protected $table = 'chats';
    protected $primaryKey = 'idChat';
    protected $fillable = [
        'idCliente', 
        'idEncargado', 
        'idProyecto'
    ];

    public function cliente()
    {
        return $this->belongsTo(User::class, 'idCliente', 'idUsuario');
    }

    public function encargado()
    {
        return $this->belongsTo(User::class, 'idEncargado', 'idUsuario');
    }

    public function proyecto()
    {
        return $this->belongsTo(Proyecto::class, 'idProyecto', 'idProyecto');
    }

    public function mensajes()
    {
        return $this->hasMany(Mensaje::class, 'idChat', 'idChat');
    }
}