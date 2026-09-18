<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class CitasAgendamientosDemo extends Seeder
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
            DB::table('citas_agendamientos')->insert([
                'persona_id'     => $faker->randomElement($personaIds),
                'tipo_cita'      => $faker->randomElement(['Presencial', 'Virtual', 'Telefónica']),
                'fecha_cita'     => $faker->dateTimeBetween('-1 month', '+1 month')->format('Y-m-d H:i:s'),
                'modulo_destino' => $faker->randomElement(['Ventanilla 1', 'Asesoría Jurídica', 'Créditos', 'Capacitación']),
            ]);
        }
    }
}