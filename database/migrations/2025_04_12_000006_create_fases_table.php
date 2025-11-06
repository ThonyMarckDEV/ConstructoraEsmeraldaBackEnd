<?php

// Migration: create_fases_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFasesTable extends Migration
{
    public function up()
    {
        Schema::create('fases', function (Blueprint $table) {
            $table->bigIncrements('idFase');
            $table->unsignedBigInteger('idProyecto');
            $table->enum('nombreFase', [
                'Planificación',
                'Preparación del Terreno',
                'Construcción de Cimientos',
                'Estructura y Superestructura',
                'Instalaciones',
                'Acabados',
                'Inspección y Pruebas',
                'Entrega'
            ]);
            $table->text('descripcion')->nullable();
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();
            $table->string('modelo')->nullable();
            $table->timestamps();
            
            // Definición de la clave foránea
            $table->foreign('idProyecto')
                  ->references('idProyecto')
                  ->on('proyectos')
                  ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('fases');
    }
}
