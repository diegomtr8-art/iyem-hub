<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class PadronDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create('es_Mx');

        for ($i = 0; $i < 200; $i++) {
            DB::table('personas')->insert([
                'nombre_completo' => $faker->name(),
                'email' => $faker->unique()->safeEmail(),
                'telefono' => $faker->phoneNumber(),
                'curp' => strtoupper($faker->bothify('????######??????##')),
                'municipio' => 'Yucatan',
                'fecha_nacimiento' => $faker->date('Y-m-d', '-18 years'),
                'sexo' => $faker->randomElement(['M', 'F']),
                'nivel_educativo' => $faker->randomElement(['Primaria', 'Secundaria', 'Bachillerato', 'Licenciatura']),
                'creado_por_modulo' => 'seeder',
                'demo' => true,
            ]);
        }

    }
}
