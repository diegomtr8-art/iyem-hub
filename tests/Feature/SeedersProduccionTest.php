<?php

namespace Tests\Feature;

use App\Models\Persona;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El sembrado en producción no debe meter datos ficticios al padrón real.
 *
 * `db:seed --force` aparece en casi cualquier guion de despliegue; si
 * `DatabaseSeeder` arrastra los padrones de demostración, 240 personas
 * inventadas terminan mezcladas con las reales.
 */
class SeedersProduccionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Siembra tal como lo haría un despliegue: entorno de producción y
     * `--force`, que es lo que se pasa para saltar la confirmación
     * interactiva de `db:seed`.
     */
    private function sembrarComoProduccion(): void
    {
        app()->detectEnvironment(fn () => 'production');

        $this->artisan('db:seed', [
            '--class' => DatabaseSeeder::class,
            '--force' => true,
        ])->assertSuccessful();
    }

    public function test_en_produccion_no_se_siembran_personas_ficticias(): void
    {
        $this->sembrarComoProduccion();

        $this->assertSame(0, Persona::withoutGlobalScope('aislamiento_demo')->count());
    }

    public function test_en_produccion_si_se_siembran_roles_y_la_cuenta_de_pruebas(): void
    {
        $this->sembrarComoProduccion();

        $this->assertDatabaseHas('roles', ['name' => 'Tester']);
        $this->assertDatabaseHas('roles', ['name' => 'Super Admin']);

        $tester = User::firstWhere('email', 'tester@iyemyucatan.com');

        $this->assertNotNull($tester);
        $this->assertTrue($tester->hasRole('Tester'));
        $this->assertNotNull($tester->expira_at, 'La cuenta de pruebas debe caducar.');
    }

    public function test_fuera_de_produccion_si_se_siembra_el_padron_de_demostracion(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertGreaterThan(
            0,
            Persona::withoutGlobalScope('aislamiento_demo')->where('demo', true)->count()
        );
    }
}
