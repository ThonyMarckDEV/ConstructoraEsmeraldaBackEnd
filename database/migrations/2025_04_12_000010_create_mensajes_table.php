<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('mensajes', function (Blueprint $table) {
            $table->id('idMensaje');
            $table->unsignedBigInteger('idChat');
            $table->unsignedBigInteger('idUsuario'); // quién envió el mensaje
            $table->text('contenido');
            $table->boolean('leido')->default(false);
            $table->timestamps();

            $table->foreign('idChat')->references('idChat')->on('chats');
            $table->foreign('idUsuario')->references('idUsuario')->on('usuarios');
        });
    }

    public function down()
    {
        Schema::dropIfExists('mensajes');
    }
};