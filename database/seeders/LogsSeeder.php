<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LogsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Limpiar la tabla antes de insertar nuevos datos
        DB::table('logs')->delete();

        $userIds = [1, 2, 3]; // IDs de los usuarios creados
        $registrosPosibles = [
            'El usuario ha iniciado sesión.',
            'Vio las analiticas.',
            'Obtuvo los proyectos del cliente con sus fases.',
            'Obtuvo el detalle de un proyecto del cliente con sus fases.',
            'Subio un modelo al proyecto.',
            'Cerró sesión exitosamente.',
            'Vio un reporte de avances.',
        ];

        $startDate = Carbon::create(2025, 8, 11);
        $endDate = Carbon::create(2025, 10, 3);
        
        $registros = [];

        // Generar 150 registros aleatorios
        for ($i = 0; $i < 150; $i++) {
            $randomTimestamp = Carbon::createFromTimestamp(rand(
                $startDate->timestamp,
                $endDate->timestamp
            ));

            $registros[] = [
                'id_Usuario' => $userIds[array_rand($userIds)],
                'registro' => $registrosPosibles[array_rand($registrosPosibles)],
                'created_at' => $randomTimestamp,
                'updated_at' => $randomTimestamp,
            ];
        }

        // Insertar todos los registros en una sola consulta
        DB::table('logs')->insert($registros);
    }
}
