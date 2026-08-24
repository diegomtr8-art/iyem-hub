<?php

namespace Tests\Feature;

use App\Models\Modulos\CreaSolicitud;
use App\Models\Persona;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BuscadorGlobalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function usuarioCon(string $rol): User
    {
        return User::factory()->create(['estado' => true])->assignRole($rol);
    }

    private function persona(array $atributos = []): Persona
    {
        return Persona::create([
            'nombre_completo' => 'Candelaria Poot Canul',
            'email' => 'candelaria.poot@ejemplo-demo.mx',
            'telefono' => '9991234567',
            'curp' => 'POCC900101MZZTNN01',
            'municipio' => 'Mérida',
            ...$atributos,
        ]);
    }

    public function test_encuentra_por_nombre_curp_correo_y_telefono(): void
    {
        $persona = $this->persona();
        $usuario = $this->usuarioCon('Super Admin');

        foreach (['Candelaria', 'POCC900101', 'candelaria.poot', '1234567'] as $termino) {
            $this->actingAs($usuario)
                ->getJson(route('buscar', ['q' => $termino]))
                ->assertOk()
                ->assertJsonPath('resultados.0.id', $persona->id);
        }
    }

    public function test_devuelve_los_modulos_donde_la_persona_ya_aparece(): void
    {
        $persona = $this->persona();

        CreaSolicitud::create([
            'persona_id' => $persona->id,
            'estado_solicitud' => 'aprobada',
            'fecha_solicitud' => now(),
        ]);

        $this->actingAs($this->usuarioCon('Super Admin'))
            ->getJson(route('buscar', ['q' => 'Candelaria']))
            ->assertOk()
            ->assertJsonPath('resultados.0.modulos.0.slug', 'crea')
            ->assertJsonPath('resultados.0.modulos.0.total', 1);
    }

    public function test_por_debajo_de_tres_caracteres_no_busca(): void
    {
        $this->persona();

        $this->actingAs($this->usuarioCon('Super Admin'))
            ->getJson(route('buscar', ['q' => 'Ca']))
            ->assertOk()
            ->assertJsonCount(0, 'resultados')
            ->assertJsonPath('minimo', 3);
    }

    public function test_no_corta_a_lo_ancho_los_resultados_pero_avisa_del_total(): void
    {
        $usuario = $this->usuarioCon('Super Admin');

        for ($i = 0; $i < 25; $i++) {
            $this->persona([
                'nombre_completo' => "Candelaria Prueba {$i}",
                'email' => "candelaria{$i}@ejemplo-demo.mx",
                'curp' => null,
            ]);
        }

        $this->actingAs($usuario)
            ->getJson(route('buscar', ['q' => 'Candelaria']))
            ->assertOk()
            ->assertJsonCount(20, 'resultados')
            ->assertJsonPath('total', 25)
            ->assertJsonPath('truncado', true);
    }

    public function test_el_tester_solo_encuentra_personas_de_demostracion(): void
    {
        $this->persona(['nombre_completo' => 'Candelaria Real', 'demo' => false, 'curp' => null, 'email' => 'real@ejemplo.mx']);
        $demo = $this->persona(['nombre_completo' => 'Candelaria Demo', 'demo' => true, 'curp' => null, 'email' => 'demo@ejemplo.mx']);

        $this->actingAs($this->usuarioCon('Tester'))
            ->getJson(route('buscar', ['q' => 'Candelaria']))
            ->assertOk()
            ->assertJsonCount(1, 'resultados')
            ->assertJsonPath('resultados.0.id', $demo->id);
    }

    public function test_sin_permiso_de_padron_el_buscador_esta_cerrado(): void
    {
        // Operario sí tiene ver-padron; se le quita para probar la barrera.
        $usuario = User::factory()->create(['estado' => true]);

        $this->actingAs($usuario)
            ->getJson(route('buscar', ['q' => 'Candelaria']))
            ->assertForbidden();
    }
}
