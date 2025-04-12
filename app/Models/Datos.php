<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Datos extends Model
{
    use HasFactory;

    /**
     * La tabla asociada con el modelo.
     *
     * @var string
     */
    protected $table = 'datos';

    /**
     * La clave primaria asociada con la tabla.
     *
     * @var string
     */
    protected $primaryKey = 'idDatos';

    /**
     * Los atributos que se pueden asignar de manera masiva.
     *
     * @var array<string>
     */
    protected $fillable = [
        'nombre',
        'apellido',
        'email',
        'direccion',
        'dni',
        'ruc',
        'telefono',
    ];

    /**
     * Relación con los usuarios
     */
    public function usuario()
    {
        return $this->hasOne(User::class, 'idDatos', 'idDatos');
    }
}