<?php

namespace Tests\Feature;

use App\Models\Modulos\CreaSolicitud;
use App\Models\Modulos\ImpulsateInscripcion;
use App\Models\Persona;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PadronFichaTest extends TestCase
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
            'rfc' => 'POCC900101AB1',
            'municipio' => 'Mérida',
            'estado' => 'Yucatán',
            'creado_por_modulo' => 'padron',
            ...$atributos,
        ]);
    }

    public function test_la_ficha_carga_con_sus_cinco_pestanas(): void
    {
        $persona = $this->persona();

        $props = $this->actingAs($this->usuarioCon('Super Admin'))
            ->get(route('padron.show', $persona))
            ->assertOk()
            ->viewData('page')['props'];

        $this->assertSame('Candelaria Poot Canul', $props['persona']['nombre_completo']);
        $this->assertNotEmpty($props['secciones']);
        $this->assertNotEmpty($props['lineaDeTiempo']);
        $this->assertArrayHasKey('data', $props['auditorias']);
    }

    public function test_la_linea_de_tiempo_reune_eventos_de_varios_modulos(): void
    {
        $persona = $this->persona();

        ImpulsateInscripcion::create([
            'persona_id' => $persona->id,
            'programa_nombre' => 'Impúlsate Básico',
            'fecha_inscripcion' => now()->subMonths(6),
            'estado' => 'completada',
        ]);

        CreaSolicitud::create([
            'persona_id' => $persona->id,
            'monto_solicitado' => 40000,
            'tipo_credito' => 'Semilla',
            'estado_solicitud' => 'aprobada',
            'fecha_solicitud' => now()->subMonths(2),
        ]);

        $props = $this->actingAs($this->usuarioCon('Super Admin'))
            ->get(route('padron.show', $persona))->viewData('page')['props'];

        $modulos = collect($props['lineaDeTiempo'])->pluck('modulo');

        $this->assertTrue($modulos->contains('impulsate'));
        $this->assertTrue($modulos->contains('crea'));
        $this->assertTrue($modulos->contains('padron'));

        // Más reciente primero: la solicitud CREA va antes que Impúlsate.
        $this->assertLessThan(
            $modulos->search('impulsate'),
            $modulos->search('crea'),
        );

        $vinculos = collect($props['vinculos'])->pluck('total', 'slug');
        $this->assertSame(1, $vinculos['crea']);
        $this->assertSame(1, $vinculos['impulsate']);
    }

    public function test_el_tester_ve_los_campos_sensibles_enmascarados(): void
    {
        $persona = $this->persona(['demo' => true]);

        $props = $this->actingAs($this->usuarioCon('Tester'))
            ->get(route('padron.show', $persona))->viewData('page')['props'];

        $campos = collect($props['secciones'])
            ->flatMap(fn ($seccion) => $seccion['campos'])
            ->keyBy('nombre');

        $this->assertSame('********01', $campos['curp']['valor']);
        $this->assertSame('********67', $campos['telefono']['valor']);
        // El nombre no es sensible: debe verse completo.
        $this->assertSame('Candelaria Poot Canul', $campos['nombre_completo']['valor']);
    }

    public function test_el_tester_no_alcanza_las_personas_reales(): void
    {
        $real = $this->persona(['demo' => false]);

        $this->actingAs($this->usuarioCon('Tester'))
            ->get(route('padron.show', $real))
            ->assertNotFound();
    }

    public function test_el_tester_no_puede_agregar_etiquetas(): void
    {
        $persona = $this->persona(['demo' => true]);

        $this->actingAs($this->usuarioCon('Tester'))
            ->post(route('padron.etiquetas.store', $persona), ['etiqueta' => 'vip'])
            ->assertForbidden();
    }

    public function test_agregar_y_quitar_una_etiqueta_queda_auditado(): void
    {
        $persona = $this->persona();
        $usuario = $this->usuarioCon('Super Admin');

        $this->actingAs($usuario)
            ->post(route('padron.etiquetas.store', $persona), ['etiqueta' => 'Artesano'])
            ->assertRedirect();

        // Se normaliza a minúsculas.
        $this->assertDatabaseHas('personas_etiquetas', [
            'persona_id' => $persona->id,
            'etiqueta' => 'artesano',
        ]);

        $this->assertDatabaseHas('personas_auditorias', [
            'persona_id' => $persona->id,
            'campo_modificado' => 'etiqueta',
            'valor_nuevo' => 'artesano',
        ]);

        $this->actingAs($usuario)
            ->delete(route('padron.etiquetas.destroy', [$persona, 'artesano']))
            ->assertRedirect();

        $this->assertDatabaseMissing('personas_etiquetas', [
            'persona_id' => $persona->id,
            'etiqueta' => 'artesano',
        ]);
    }
}
