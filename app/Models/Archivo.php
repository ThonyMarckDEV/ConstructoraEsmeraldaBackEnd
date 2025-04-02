<?php

// Archivo: app/Models/Archivo.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Archivo extends Model
{
    protected $table = 'archivos';
    protected $primaryKey = 'idArchivo';
    public $timestamps = false;

    protected $fillable = [
        'idFase', 
        'tipo',
        'ruta'
    ];

    public function fase()
    {
        return $this->belongsTo(Fase::class, 'idFase');
    }
}