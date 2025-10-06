<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('logs', function (Blueprint $table) {
            $table->id(); // Columna 'id' autoincremental y clave primaria
            
            // Columna para la llave foránea del usuario
            // Hacemos referencia a la tabla 'usuarios' y a la columna 'idUsuario'
            $table->foreignId('id_Usuario')
                  ->constrained('usuarios', 'idUsuario')
                  ->onDelete('cascade'); // Si se borra un usuario, se borran sus logs

            $table->text('registro'); // Columna para guardar la acción (ej: "Inició sesión")
            $table->timestamps(); // Columnas 'created_at' y 'updated_at'
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('logs');
    }
};