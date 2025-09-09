<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('chats', function (Blueprint $table) {
            $table->id('idChat');
            $table->unsignedBigInteger('idCliente');
            $table->unsignedBigInteger('idEncargado');
            $table->unsignedBigInteger('idProyecto');
            $table->timestamps();

            $table->foreign('idCliente')->references('idUsuario')->on('usuarios');
            $table->foreign('idEncargado')->references('idUsuario')->on('usuarios');
            $table->foreign('idProyecto')->references('idProyecto')->on('proyectos')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('chats');
    }
};