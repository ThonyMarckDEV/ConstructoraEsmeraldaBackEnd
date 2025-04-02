<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProyectosTable extends Migration
{
    public function up()
    {
        Schema::create('proyectos', function (Blueprint $table) {
            // Definición de columnas
            $table->bigIncrements('idProyecto');
            $table->unsignedBigInteger('idEncargado'); // FK: referencia a usuarios.idUsuario
            $table->unsignedBigInteger('idCliente'); // FK: referencia a usuarios.idUsuario
            $table->string('nombre', 255);
            $table->text('descripcion')->nullable();
            $table->date('fecha_inicio');
            $table->date('fecha_fin_estimada');
            $table->enum('estado', ['En Progreso', 'Finalizado'])->default('En Progreso');
            $table->string('fase', 100);
            $table->timestamps();
            
            // Definición de la claves foráneas
            $table->foreign('idEncargado')
                  ->references('idUsuario')
                  ->on('usuarios')
                  ->onDelete('cascade');

            $table->foreign('idCliente')
                  ->references('idUsuario')
                  ->on('usuarios')
                  ->onDelete('cascade');

        });
    }

    public function down()
    {
        Schema::dropIfExists('proyectos');
    }
}
