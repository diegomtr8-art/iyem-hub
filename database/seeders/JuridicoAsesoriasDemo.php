<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class JuridicoAsesoriasDemo extends Seeder
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
            DB::table('juridico_asesorias')->insert([
                'persona_id'    => $faker->randomElement($personaIds),
                'tipo_asesoria' => $faker->randomElement(['Laboral', 'Mercantil', 'Civil', 'Fiscal', 'Corporativo']),
                'fecha_asesoria'=> $faker->date('Y-m-d', 'now'),
                'notas'         => $faker->optional(0.7)->sentence(), // 70% de probabilidad de tener notas
            ]);
        }
    }
}