<?php 
// Modelo: Fase.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Fase extends Model
{
    use HasFactory;

    protected $table = 'fases';
    protected $primaryKey = 'idFase';

    public $timestamps = false;

    protected $fillable = [
        'idProyecto',
        'nombreFase',
        'descripcion',
        'fecha_inicio',
        'fecha_fin',
        'modelo',
    ];

    public function proyecto()
    {
        return $this->belongsTo(Proyecto::class, 'idProyecto');
    }

    public function archivos()
    {
        return $this->hasMany(Archivo::class, 'idFase');
    }

    public function fotos()
    {
        return $this->hasMany(Foto::class, 'idFase');
    }
}
