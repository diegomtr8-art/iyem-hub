<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class HerenciaVivaClientesDemo extends Seeder
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
            DB::table('herencia_viva_clientes')->insert([
                'persona_id'          => $faker->randomElement($personaIds),
                'numero_cliente'      => strtoupper($faker->bothify('CLI-#####')),
                'fecha_primer_compra' => $faker->date('Y-m-d', 'now'),
                'total_gastado'       => $faker->randomFloat(2, 500, 25000),
                'numero_compras'      => $faker->numberBetween(1, 50),
                'es_mayorista'        => $faker->boolean(25),
            ]);
        }
    }
}