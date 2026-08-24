<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Siembra la base.
     *
     * PROTECCIÓN CONTRA UN ACCIDENTE EN PRODUCCIÓN
     *
     * `PersonaSeeder` y `PadronDemoSeeder` crean 240 personas ficticias.
     * En un entorno local eso es lo que se quiere; en el padrón real del
     * instituto es contaminación, y `db:seed --force` aparece en casi
     * cualquier guion de despliegue.
     *
     * Por eso en producción solo corre lo que es seguro correr ahí: roles,
     * permisos y la cuenta de pruebas. Los padrones ficticios quedan fuera
     * salvo que alguien lo pida a mano y a conciencia:
     *
     *     php artisan db:seed --class=PadronDemoSeeder --force
     */
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
        ]);

        if (app()->environment('production')) {
            // TesterSeeder sí corre: la cuenta de demostración existe
            // justamente para poder enseñar la plataforma en producción sin
            // exponer datos reales. Ver su propia documentación.
            $this->call([TesterSeeder::class]);

            $this->command?->warn(
                'Entorno de producción: se omitieron UserSeeder, PersonaSeeder y '
                .'PadronDemoSeeder para no meter datos ficticios al padrón real.'
            );

            return;
        }

        $this->call([
            UserSeeder::class,
            PersonaSeeder::class,
            // El orden importa: TesterSeeder necesita que el rol Tester ya
            // exista, y el padrón de demostración es lo único que ese rol ve.
            TesterSeeder::class,
            PadronDemoSeeder::class,
        ]);
    }
}
