<?php

// Database/Seeders/GenerateDataSeeder.php
namespace Database\Seeders;

use Illuminate\Database\Seeder;

class GenerateDataSeeder extends Seeder
{
    public function run()
    {
        $this->call([
            UsuarioSeeder::class,
            ProyectoSeeder::class,
            FasesSeeder::class,
        ]);
    }
}
