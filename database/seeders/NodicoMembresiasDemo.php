<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class NodicoMembresiasDemo extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create('es_MX');

        // Obtenemos los IDs existentes de la tabla personas
        $personaIds = DB::table('personas')->pluck('id')->toArray();

        for ($i = 0; $i < 30; $i++) {
            $fechaInicio = $faker->date('Y-m-d', 'now');

            DB::table('nodico_membresias')->insert([
                'persona_id'       => $faker->randomElement($personaIds),
                'tipo_membresia'   => $faker->randomElement(['Básica', 'Pro', 'Premium', 'Corporativa']),
                'fecha_inicio'     => $fechaInicio,
                'fecha_fin'        => $faker->dateTimeBetween($fechaInicio, '+1 year')->format('Y-m-d'),
            ]);
        }
    }
}