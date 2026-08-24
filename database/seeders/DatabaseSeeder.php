<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            UserSeeder::class,
            PersonaSeeder::class,
            // El orden importa: TesterSeeder necesita que el rol Tester ya
            // exista, y el padrón de demostración es lo único que ese rol ve.
            TesterSeeder::class,
            PadronDemoSeeder::class,
        ]);
    }
}
