<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('refresh_tokens', function (Blueprint $table) {
            $table->bigIncrements('idToken'); // ID personalizado
            $table->unsignedBigInteger('idUsuario'); // Usamos bigUnsignedInteger
            $table->foreign('idUsuario')->references('idUsuario')->on('usuarios')->onDelete('cascade'); // Relación con usuarios
            $table->string('token', 64)->unique(); // Guardamos el refresh token
            $table->timestamp('expires_at')->nullable(); // Fecha de expiración
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refresh_tokens');
    }
};
