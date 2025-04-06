<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Proyecto extends Model
{
    // Especificamos la tabla (opcional si sigues la convención plural)
    protected $table = 'proyectos';

    // Definimos la clave primaria
    protected $primaryKey = 'idProyecto';

    // Definimos los campos asignables
    protected $fillable = [
        'idEncargado',
        'idCliente',
        'nombre',
        'descripcion',
        'fecha_inicio',
        'fecha_fin_estimada',
        'estado',
        'fase',
        'modelo'
    ];

    /**
     * Relación: Un proyecto pertenece a un encargado (usuario)
     */
    public function encargado()
    {
        return $this->belongsTo(User::class, 'idEncargado', 'idUsuario');
    }

    /**
     * Relación: Un proyecto pertenece a un cliente (usuario)
     */
    public function cliente()
    {
        return $this->belongsTo(User::class, 'idCliente', 'idUsuario');
    }



}
