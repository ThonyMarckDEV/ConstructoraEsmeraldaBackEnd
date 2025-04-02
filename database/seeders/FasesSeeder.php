<?php

namespace Database\Seeders;

// Seeder: FasesSeeder.php
use Illuminate\Database\Seeder;
use App\Models\Fase;
use Illuminate\Support\Carbon;

class FasesSeeder extends Seeder
{
    public function run()
    {
        $idProyecto = 1; // Cambia esto por el ID del proyecto deseado
        
        $fases = [
            ['nombreFase' => 'Planificación', 'descripcion' => 'Fase de planificación y diseño.'],
            ['nombreFase' => 'Preparación del Terreno', 'descripcion' => 'Preparación del área para la construcción.'],
            ['nombreFase' => 'Construcción de Cimientos', 'descripcion' => 'Creación de la base estructural.'],
            ['nombreFase' => 'Estructura y Superestructura', 'descripcion' => 'Levantamiento de la estructura principal.'],
            ['nombreFase' => 'Instalaciones', 'descripcion' => 'Instalación de sistemas eléctricos, sanitarios y mecánicos.'],
            ['nombreFase' => 'Acabados', 'descripcion' => 'Revestimientos, pintura y detalles finales.'],
            ['nombreFase' => 'Inspección y Pruebas', 'descripcion' => 'Verificación de calidad y pruebas finales.'],
            ['nombreFase' => 'Entrega', 'descripcion' => 'Entrega final del proyecto al cliente.']
        ];

        foreach ($fases as $fase) {
            Fase::create([
                'idProyecto' => $idProyecto,
                'nombreFase' => $fase['nombreFase'],
                'descripcion' => $fase['descripcion'],
                'fecha_inicio' => now(),
                'fecha_fin' => now()->addDays(30),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);
        }
    }
}
