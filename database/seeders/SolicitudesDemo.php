<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class SolicitudesDemo extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create('es_MX');

        $personaIds = DB::table('personas')->pluck('id')->toArray();

        for ($i = 0; $i < 200; $i++) {
            DB::table('crea_solicitudes')->insert([
                'persona_id'       => $faker->randomElement($personaIds),
                'monto_solicitado' => $faker->numberBetween(1000, 10000),
                'tipo_credito'     => $faker->randomElement(['Sustentable', 'Artesanal', 'Empresarial']),
                'fecha_solicitud'  => $faker->date('Y-m-d', 'now'),
            ]);
        }
    }
}