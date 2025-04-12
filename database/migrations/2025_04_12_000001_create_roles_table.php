<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->bigIncrements('idRol');
            $table->string('nombre')->unique();
            $table->string('descripcion')->nullable();
            $table->boolean('estado')->default(true);
            $table->timestamps();
        });
        
        // Insertar roles por defecto
        DB::table('roles')->insert([
            ['nombre' => 'admin', 'descripcion' => 'Administrador del sistema', 'estado' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'cliente', 'descripcion' => 'Cliente del sistema', 'estado' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'manager', 'descripcion' => 'Gestor de proeyctos', 'estado' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};