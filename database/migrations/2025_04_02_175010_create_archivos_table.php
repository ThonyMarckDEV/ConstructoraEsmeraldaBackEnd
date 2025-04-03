<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('archivos', function (Blueprint $table) {
            $table->id('idArchivo');
            $table->unsignedBigInteger('idFase');
            $table->enum('tipo', ['pdf', 'xls', 'docx', 'dwg']);
            $table->string('ruta');
            $table->text('descripcion');
            $table->timestamps();
        
            $table->foreign('idFase')->references('idFase')->on('fases');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('archivos');
    }
};
