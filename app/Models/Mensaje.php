<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mensaje extends Model
{
    use HasFactory;

    protected $table = 'mensajes';
    protected $primaryKey = 'idMensaje';
    protected $fillable = [
        'idChat',
        'idUsuario',
        'contenido',
        'leido'
    ];

    public function chat()
    {
        return $this->belongsTo(Chat::class, 'idChat', 'idChat');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'idUsuario', 'idUsuario');
    }
}