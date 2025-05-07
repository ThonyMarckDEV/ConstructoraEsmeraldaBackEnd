<?php

// Database/Seeders/ProyectoSeeder.php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class ProyectoSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();

        $clientes = DB::table('usuarios')
            ->join('roles', 'usuarios.idRol', '=', 'roles.idRol')
            ->where('roles.nombre', 'cliente')
            ->pluck('usuarios.idUsuario')
            ->toArray();

        $manager = DB::table('usuarios')
            ->join('roles', 'usuarios.idRol', '=', 'roles.idRol')
            ->where('roles.nombre', 'manager')
            ->value('idUsuario');

        foreach ($clientes as $clienteId) {
            DB::table('proyectos')->insert([
                'idEncargado' => $manager,
                'idCliente' => $clienteId,
                'nombre' => $faker->words(3, true),
                'descripcion' => $faker->paragraph,
                'fecha_inicio' => now(),
                'fecha_fin_estimada' => now()->addMonths(6),
                'estado' => 'en progreso',
                'fase' => 'Planificación',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
