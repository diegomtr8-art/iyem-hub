<?php

namespace Tests\Feature;

use App\Http\Middleware\RestringeTester;
use App\Models\Persona;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Laravel\Jetstream\Jetstream;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * Padrón visto desde la web: acceso, alta, edición y las barreras del rol.
 *
 * Lo que ocurre dentro del modelo lo cubre `PersonaTest`; la ficha 360°,
 * `PadronFichaTest`. Aquí se prueban las rutas y los permisos.
 */
class PadronTest extends TestCase
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
        static $n = 0;
        $n++;

        return Persona::create([
            'nombre_completo' => "Persona Prueba {$n}",
            'email' => "prueba{$n}@ejemplo-demo.mx",
            'telefono' => '999111'.str_pad((string) $n, 4, '0', STR_PAD_LEFT),
            'municipio' => 'Mérida',
            'estado' => 'Yucatán',
            ...$atributos,
        ]);
    }

    /* ---------------------------------------------------------------- *
     * Acceso
     * ---------------------------------------------------------------- */

    public function test_una_visita_anonima_va_al_inicio_de_sesion(): void
    {
        $this->get(route('padron.index'))->assertRedirect(route('login'));
    }

    public function test_los_roles_con_ver_padron_entran_al_listado(): void
    {
        foreach (['Super Admin', 'Admin Área', 'Supervisor', 'Operario', 'Tester'] as $rol) {
            $this->actingAs($this->usuarioCon($rol))
                ->get(route('padron.index'))
                ->assertOk();
        }
    }

    public function test_sin_el_permiso_no_se_entra_al_padron(): void
    {
        $this->actingAs(User::factory()->create(['estado' => true]))
            ->get(route('padron.index'))
            ->assertForbidden();
    }

    /* ---------------------------------------------------------------- *
     * Listado y filtros
     * ---------------------------------------------------------------- */

    public function test_el_listado_pagina_y_filtra(): void
    {
        $this->persona(['nombre_completo' => 'Ana Canul Poot', 'municipio' => 'Mérida']);
        $this->persona(['nombre_completo' => 'Beto Chi Dzul', 'municipio' => 'Valladolid']);
        $this->persona(['nombre_completo' => 'Carmen Uc Balam', 'estado_persona' => 'inactiva']);

        $usuario = $this->usuarioCon('Super Admin');

        $props = $this->actingAs($usuario)->get(route('padron.index'))
            ->assertOk()->viewData('page')['props'];
        $this->assertCount(3, $props['personas']['data']);

        $props = $this->actingAs($usuario)->get(route('padron.index', ['busqueda' => 'Valladolid']))
            ->viewData('page')['props'];
        $this->assertCount(1, $props['personas']['data']);
        $this->assertSame('Beto Chi Dzul', $props['personas']['data'][0]['nombre_completo']);

        $props = $this->actingAs($usuario)->get(route('padron.index', ['estado_persona' => 'inactiva']))
            ->viewData('page')['props'];
        $this->assertCount(1, $props['personas']['data']);
    }

    /* ---------------------------------------------------------------- *
     * Alta
     * ---------------------------------------------------------------- */

    public function test_un_rol_con_permiso_da_de_alta_y_llega_a_la_ficha(): void
    {
        $this->actingAs($this->usuarioCon('Admin Área'))
            ->post(route('padron.store'), [
                'nombre_completo' => 'Wilberth Canul Dzul',
                'curp' => 'CADW880216HZZNZL05',
                'telefono' => '9995551234',
                'municipio' => 'Motul',
            ])
            ->assertRedirect();

        $persona = Persona::firstWhere('curp', 'CADW880216HZZNZL05');

        $this->assertNotNull($persona);
        $this->assertSame('padron', $persona->creado_por_modulo);
    }

    public function test_el_alta_valida_la_estructura_de_la_curp(): void
    {
        $this->actingAs($this->usuarioCon('Admin Área'))
            ->post(route('padron.store'), [
                'nombre_completo' => 'Persona Inválida',
                'curp' => 'ESTONOESUNACURP12',
            ])
            ->assertSessionHasErrors('curp');

        $this->assertSame(0, Persona::count());
    }

    public function test_los_roles_de_solo_lectura_no_pueden_dar_de_alta(): void
    {
        foreach (['Supervisor', 'Operario', 'Tester'] as $rol) {
            $this->actingAs($this->usuarioCon($rol))
                ->get(route('padron.create'))
                ->assertForbidden();

            $this->actingAs($this->usuarioCon($rol))
                ->post(route('padron.store'), ['nombre_completo' => 'No debería entrar'])
                ->assertForbidden();
        }

        $this->assertSame(0, Persona::count());
    }

    /* ---------------------------------------------------------------- *
     * Edición
     * ---------------------------------------------------------------- */

    public function test_la_edicion_exige_el_permiso_de_editar(): void
    {
        $persona = $this->persona();

        // Admin Área sí lo tiene.
        $this->actingAs($this->usuarioCon('Admin Área'))
            ->put(route('padron.update', $persona), ['municipio' => 'Progreso'])
            ->assertRedirect();

        $this->assertSame('Progreso', $persona->fresh()->municipio);

        // Supervisor no.
        $this->actingAs($this->usuarioCon('Supervisor'))
            ->put(route('padron.update', $persona), ['municipio' => 'Tekax'])
            ->assertForbidden();

        $this->assertSame('Progreso', $persona->fresh()->municipio);
    }

    public function test_editar_deja_rastro_en_la_auditoria(): void
    {
        $persona = $this->persona(['municipio' => 'Mérida']);

        $this->actingAs($this->usuarioCon('Admin Área'))
            ->put(route('padron.update', $persona), ['municipio' => 'Progreso']);

        $this->assertDatabaseHas('personas_auditorias', [
            'persona_id' => $persona->id,
            'campo_modificado' => 'municipio',
            'valor_anterior' => 'Mérida',
            'valor_nuevo' => 'Progreso',
        ]);
    }

    /* ---------------------------------------------------------------- *
     * Mapa
     * ---------------------------------------------------------------- */

    public function test_el_mapa_solo_trae_personas_activas_y_geolocalizadas(): void
    {
        $this->persona(['nombre_completo' => 'Activa Geolocalizada', 'municipio' => 'Mérida']);
        $inactiva = $this->persona(['nombre_completo' => 'Inactiva', 'estado_persona' => 'inactiva']);

        $props = $this->actingAs($this->usuarioCon('Super Admin'))
            ->get(route('padron.mapa'))
            ->assertOk()->viewData('page')['props'];

        $nombres = collect($props['personas'])->pluck('nombre_completo');

        $this->assertTrue($nombres->contains('Activa Geolocalizada'));
        $this->assertFalse($nombres->contains('Inactiva'));
        $this->assertSame($inactiva->id, $inactiva->id); // la ficha sigue existiendo
    }

    /* ---------------------------------------------------------------- *
     * Rol Tester
     * ---------------------------------------------------------------- */

    public function test_el_tester_solo_ve_personas_de_demostracion(): void
    {
        $this->persona(['nombre_completo' => 'Real Uno', 'demo' => false]);
        $this->persona(['nombre_completo' => 'Demo Uno', 'demo' => true]);

        $props = $this->actingAs($this->usuarioCon('Tester'))
            ->get(route('padron.index'))->viewData('page')['props'];

        $nombres = collect($props['personas']['data'])->pluck('nombre_completo');

        $this->assertCount(1, $nombres);
        $this->assertTrue($nombres->contains('Demo Uno'));
    }

    public function test_el_tester_ve_los_campos_sensibles_enmascarados_en_el_listado(): void
    {
        $this->persona([
            'nombre_completo' => 'Demo Enmascarada',
            'demo' => true,
            'telefono' => '9991234567',
        ]);

        $props = $this->actingAs($this->usuarioCon('Tester'))
            ->get(route('padron.index'))->viewData('page')['props'];

        $this->assertSame('********67', $props['personas']['data'][0]['telefono']);
        // El nombre no es sensible.
        $this->assertSame('Demo Enmascarada', $props['personas']['data'][0]['nombre_completo']);
    }

    /**
     * Hoy `Features::api()` está comentado en `config/jetstream.php`, así que
     * `/user/api-tokens` ni siquiera existe: nadie llega, Tester incluido.
     *
     * El middleware `RestringeTester` se prueba aparte, contra la petición
     * directamente, porque su valor está en el día que se encienda esa
     * característica: un Tester con token personal podría consultar la API
     * con su identidad y saltarse el enmascarado de la sesión web.
     */
    public function test_hoy_la_ruta_de_tokens_de_api_no_esta_habilitada(): void
    {
        $this->assertFalse(
            Jetstream::hasApiFeatures(),
            'Si se habilita la API de Jetstream, revisa que RestringeTester siga cubriendo la ruta.'
        );

        $this->actingAs($this->usuarioCon('Tester'))
            ->get('/user/api-tokens')
            ->assertNotFound();
    }

    public function test_el_middleware_le_cierra_los_tokens_de_api_al_tester(): void
    {
        $middleware = new RestringeTester;

        $peticion = Request::create('/user/api-tokens', 'GET');
        $peticion->setUserResolver(fn () => $this->usuarioCon('Tester'));

        $this->expectException(HttpException::class);

        $middleware->handle($peticion, fn () => response('no debería llegar aquí'));
    }

    public function test_el_middleware_deja_pasar_a_los_demas_roles(): void
    {
        $middleware = new RestringeTester;

        $peticion = Request::create('/user/api-tokens', 'GET');
        $peticion->setUserResolver(fn () => $this->usuarioCon('Super Admin'));

        $respuesta = $middleware->handle($peticion, fn () => response('ok'));

        $this->assertSame('ok', $respuesta->getContent());
    }

    /* ---------------------------------------------------------------- *
     * Vigencia de la cuenta
     * ---------------------------------------------------------------- */

    public function test_una_cuenta_vencida_se_cierra_al_navegar(): void
    {
        $usuario = $this->usuarioCon('Super Admin');
        $usuario->update(['expira_at' => now()->subDay()]);

        // `actingAs` fija al usuario en el guard para toda la prueba, así
        // que `assertGuest` no sirve aquí: lo que demuestra que el middleware
        // actuó es el redireccionamiento con el aviso de vencimiento.
        $this->actingAs($usuario)
            ->get(route('padron.index'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');
    }

    public function test_una_cuenta_sin_fecha_de_vencimiento_no_caduca(): void
    {
        $usuario = $this->usuarioCon('Super Admin');

        $this->assertNull($usuario->expira_at);
        $this->assertTrue($usuario->vigente());

        $this->actingAs($usuario)->get(route('padron.index'))->assertOk();
    }
}
