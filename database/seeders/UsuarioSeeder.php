<?php

// Database/Seeders/UsuarioSeeder.php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Faker\Factory as Faker;

class UsuarioSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();

        // Insert cliente fijo
        $clienteDatosId1 = DB::table('datos')->insertGetId([
            'nombre' => 'Anthony Marck',
            'apellido' => 'Mendoza Sanchez',
            'email' => 'thonymarck385213xd@gmail.com',
            'direccion' => 'Av. Siempre Viva 123',
            'dni' => '12345678',
            'ruc' => '20123456789',
            'telefono' => '987654321',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Insert manager
        $managerDatosId = DB::table('datos')->insertGetId([
            'nombre' => 'Pedro',
            'apellido' => 'Suarez Vertiz',
            'email' => 'pedrosuarez@example.com',
            'direccion' => 'Calle Falsa 456',
            'dni' => '87654321',
            'ruc' => '20987654321',
            'telefono' => '912345678',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $clienteDatosIds = [$clienteDatosId1];

        // Insert 3 clientes adicionales
        for ($i = 0; $i < 3; $i++) {
            $clienteDatosIds[] = DB::table('datos')->insertGetId([
                'nombre' => $faker->firstName,
                'apellido' => $faker->lastName,
                'email' => $faker->unique()->safeEmail,
                'direccion' => $faker->address,
                'dni' => $faker->numerify('########'),
                'ruc' => $faker->numerify('20#########'),
                'telefono' => $faker->phoneNumber,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Obtener roles
        $managerRolId = DB::table('roles')->where('nombre', 'manager')->value('idRol');
        $clienteRolId = DB::table('roles')->where('nombre', 'cliente')->value('idRol');

        // Insert usuarios
        DB::table('usuarios')->insert([
            [
                'username' => 'thonymarck',
                'password' => Hash::make('12345678'),
                'idDatos' => $clienteDatosIds[0],
                'idRol' => $clienteRolId,
                'estado' => 'activo',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'username' => 'pedrosuarez',
                'password' => Hash::make('12345678'),
                'idDatos' => $managerDatosId,
                'idRol' => $managerRolId,
                'estado' => 'activo',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        foreach (array_slice($clienteDatosIds, 1) as $i => $idDatos) {
            DB::table('usuarios')->insert([
                'username' => 'cliente' . ($i + 2),
                'password' => Hash::make('12345678'),
                'idDatos' => $idDatos,
                'idRol' => $clienteRolId,
                'estado' => 'activo',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
