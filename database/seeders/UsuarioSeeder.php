<?php

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

        // Insert datos for cliente
        $clienteDatosId = DB::table('datos')->insertGetId([
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

        // Insert datos for manager
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

        // Get role IDs
        $managerRolId = DB::table('roles')->where('nombre', 'manager')->value('idRol');
        $clienteRolId = DB::table('roles')->where('nombre', 'cliente')->value('idRol');

        // Insert usuarios
        DB::table('usuarios')->insert([
            [
                'username' => 'thonymarck',
                'password' => Hash::make('12345678'),
                'idDatos' => $clienteDatosId,
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

        // // Generate 10 random clients
        // for ($i = 0; $i < 1000; $i++) {
        //     $clienteDatosId = DB::table('datos')->insertGetId([
        //         'nombre' => $faker->firstName,
        //         'apellido' => $faker->lastName,
        //         'email' => $faker->unique()->safeEmail,
        //         'direccion' => $faker->streetAddress,
        //         'dni' => $faker->numerify('########'),
        //         'ruc' => '20' . $faker->numerify('#########'),
        //         'telefono' => $faker->numerify('9########'),
        //         'created_at' => now(),
        //         'updated_at' => now(),
        //     ]);

        //     DB::table('usuarios')->insert([
        //         'username' => null,
        //         'password' => null, 
        //         'idDatos' => $clienteDatosId,
        //         'idRol' => $clienteRolId,
        //         'estado' => 'activo',
        //         'created_at' => now(),
        //         'updated_at' => now(),
        //     ]);
        // }
    }
}