<?php

namespace Tests\Feature;

use App\Models\Persona;
use App\Models\SistemaIntegrado;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Rutas de API sin versionar, anteriores a `/api/v1`.
 *
 * Siguen vivas porque no hay forma de saber desde el hub si algún sistema
 * satélite en producción todavía las llama. Estas pruebas existen para que
 * nadie las rompa por accidente antes de que se confirme que ya nadie las
 * usa; el día que se retiren, se borra este archivo con ellas.
 */
class ApiLegadaTest extends TestCase
{
    use RefreshDatabase;

    private function comoSistema(): void
    {
        $sistema = SistemaIntegrado::create([
            'nombre' => 'CREA', 'slug' => 'crea', 'activo' => true,
        ]);

        Sanctum::actingAs($sistema, ['*']);
    }

    private function persona(): Persona
    {
        return Persona::create([
            'nombre_completo' => 'Candelaria Poot Canul',
            'email' => 'candelaria.poot@ejemplo-demo.mx',
            'municipio' => 'Mérida',
            'estado_persona' => 'activa',
        ]);
    }

    public function test_el_listado_sin_versionar_sigue_respondiendo(): void
    {
        $this->comoSistema();
        $persona = $this->persona();

        $this->getJson('/api/personas')
            ->assertOk()
            ->assertJsonPath('data.0.id', $persona->id);
    }

    public function test_la_ficha_sin_versionar_sigue_respondiendo(): void
    {
        $this->comoSistema();
        $persona = $this->persona();

        $this->getJson("/api/personas/{$persona->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $persona->id);
    }

    public function test_la_busqueda_sin_versionar_sigue_respondiendo(): void
    {
        $this->comoSistema();
        $persona = $this->persona();

        $this->getJson('/api/personas/buscar?q=Candelaria')
            ->assertOk()
            ->assertJsonPath('data.0.id', $persona->id);
    }

    public function test_por_municipio_sigue_respondiendo(): void
    {
        $this->comoSistema();
        $this->persona();

        $this->getJson('/api/personas/por-municipio/M%C3%A9rida')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_sin_token_las_rutas_legadas_tambien_estan_cerradas(): void
    {
        $this->getJson('/api/personas')->assertUnauthorized();
    }
}
