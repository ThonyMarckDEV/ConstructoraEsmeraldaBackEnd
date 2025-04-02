<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('usuarios', function (Blueprint $table) {
            $table->bigIncrements('idUsuario');
            $table->string('username')->unique();
            $table->string('password');
            $table->string('nombre')->nullable();
            $table->string('apellido')->nullable();
            $table->string('email')->nullable()->unique();
            $table->string('direccion')->nullable();
            $table->string('dni')->nullable();
            $table->string('ruc')->nullable();
            $table->string('telefono')->nullable();
            $table->enum('rol', ['admin', 'cliente', 'manager'])->default('cliente');
            $table->enum('estado', ['activo', 'inactivo'])->default('activo');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usuarios');
    }
};