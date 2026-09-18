<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class ImpulsateInscripcionesDemo extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create('es_MX');

        $personaIds = DB::table('personas')->pluck('id')->toArray();

        for ($i = 0; $i < 200; $i++) {
            DB::table('impulsate_inscripciones')->insert([
                'persona_id'        => $faker->randomElement($personaIds),
                'programa_id'       => $faker->numberBetween(1, 10),
                'programa_nombre'   => $faker->randomElement([
                    'Impúlsate Emprendedor',
                    'Impúlsate Digital',
                    'Impúlsate Negocios',
                    'Impúlsate Mujer'
                ]),
                'fecha_inscripcion' => $faker->date('Y-m-d', 'now'),
                // 'estado' omitido por completo
            ]);
        }
    }
}