<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
    use HasFactory;

    /**
     * La tabla asociada con el modelo.
     *
     * @var string
     */
    protected $table = 'logs';

    /**
     * Los atributos que se pueden asignar de manera masiva.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id_Usuario',
        'registro',
    ];

    /**
     * Relación inversa con el Usuario.
     * Un log pertenece a un usuario.
     */
    public function usuario()
    {
        // Nota: Especificamos las claves foránea y local porque no siguen la convención de Laravel
        return $this->belongsTo(User::class, 'id_Usuario', 'idUsuario');
    }
}