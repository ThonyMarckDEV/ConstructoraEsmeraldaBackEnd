<?php

// Archivo: app/Models/Foto.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Foto extends Model
{
    protected $table = 'fotos';
    protected $primaryKey = 'idFoto';
    public $timestamps = false;

    protected $fillable = [
        'idFase',
        'tipo',
        'ruta',
        'descripcion'
    ];

    public function fase()
    {
        return $this->belongsTo(Fase::class, 'idFase');
    }
}