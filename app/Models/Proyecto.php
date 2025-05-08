<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Proyecto extends Model
{
    protected $table = 'proyectos';
    protected $primaryKey = 'idProyecto';
    public $incrementing = true;
    protected $fillable = [
        'idEncargado',
        'idCliente',
        'nombre',
        'descripcion',
        'fecha_inicio',
        'fecha_fin_estimada',
        'estado',
        'fase',
    ];

    public function encargado()
    {
        return $this->belongsTo(User::class, 'idEncargado', 'idUsuario');
    }

    public function cliente()
    {
        return $this->belongsTo(User::class, 'idCliente', 'idUsuario');
    }

    public function fases()
    {
        return $this->hasMany(Fase::class, 'idProyecto', 'idProyecto');
    }

    public function chat()
    {
        return $this->hasOne(Chat::class, 'idProyecto', 'idProyecto');
    }
}