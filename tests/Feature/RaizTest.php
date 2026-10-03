<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class RaizTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_raiz_muestra_la_pantalla_de_inicio_sin_datos_internos(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $pagina) => $pagina
                ->component('Inicio')
                ->has('modulos.0', fn (AssertableInertia $modulo) => $modulo
                    ->hasAll(['slug', 'nombre', 'descripcion', 'icono', 'estado', 'categoria'])
                    ->missingAll(['url', 'url_destino', 'api_salud', 'responsable'])));
    }

    public function test_la_raiz_manda_al_tablero_a_quien_ya_inicio_sesion(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/')
            ->assertRedirect(route('dashboard'));
    }

    public function test_el_health_check_responde(): void
    {
        $this->get('/up')->assertOk();
    }
}
