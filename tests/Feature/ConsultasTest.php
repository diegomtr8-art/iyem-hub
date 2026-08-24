<?php

namespace Tests\Feature;

use App\Models\Modulos\CreaSolicitud;
use App\Models\Modulos\HerenciaVivaCliente;
use App\Models\Modulos\ImpulsateInscripcion;
use App\Models\Modulos\NodicoMembresia;
use App\Models\Persona;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConsultasTest extends TestCase
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

    private function persona(string $nombre, string $municipio = 'Mérida', array $extra = []): Persona
    {
        static $contador = 0;
        $contador++;

        return Persona::create([
            'nombre_completo' => $nombre,
            'email' => "persona{$contador}@ejemplo-demo.mx",
            'municipio' => $municipio,
            'estado' => 'Yucatán',
            ...$extra,
        ]);
    }

    /**
     * Padrón de prueba con un embudo conocido:
     *
     *   - Ana:    Impúlsate → CREA → Nódico → Herencia Viva  (llega al final)
     *   - Beto:   Impúlsate → CREA                            (se cae en Nódico)
     *   - Carmen: Impúlsate                                   (se cae en CREA)
     *   - Diego:  nada                                        (nunca entra)
     */
    private function sembrarEmbudo(): array
    {
        $ana = $this->persona('Ana Canul Poot', 'Mérida', ['curp' => 'CAPA900101MZZNTN01', 'telefono' => '9991111111']);
        $beto = $this->persona('Beto Chi Dzul', 'Valladolid', ['telefono' => '9992222222']);
        $carmen = $this->persona('Carmen Uc Balam', 'Valladolid');
        $diego = $this->persona('Diego Tun Ek', 'Tekax');

        foreach ([$ana, $beto, $carmen] as $persona) {
            ImpulsateInscripcion::create([
                'persona_id' => $persona->id,
                'programa_nombre' => 'Impúlsate Básico',
                'fecha_inscripcion' => now()->subMonths(10),
                'estado' => 'completada',
            ]);
        }

        foreach ([$ana, $beto] as $persona) {
            CreaSolicitud::create([
                'persona_id' => $persona->id,
                'monto_solicitado' => 30000,
                'estado_solicitud' => 'aprobada',
                'fecha_solicitud' => now()->subMonths(6),
            ]);
        }

        NodicoMembresia::create([
            'persona_id' => $ana->id,
            'tipo_membresia' => 'Hot desk',
            'estado_membresia' => 'activa',
            'fecha_inicio' => now()->subMonths(4)->toDateString(),
        ]);

        HerenciaVivaCliente::create([
            'persona_id' => $ana->id,
            'numero_cliente' => 'HV-0001',
            'fecha_primer_compra' => now()->subMonths(2),
            'total_gastado' => 5400,
            'numero_compras' => 6,
        ]);

        return compact('ana', 'beto', 'carmen', 'diego');
    }

    private function abrir(string $clave, array $parametros = [], string $rol = 'Super Admin'): array
    {
        return $this->actingAs($this->usuarioCon($rol))
            ->get(route('consultas.index', ['consulta' => $clave, ...$parametros]))
            ->assertOk()
            ->viewData('page')['props'];
    }

    /* ---------------------------------------------------------------- *
     * Índice y permisos
     * ---------------------------------------------------------------- */

    public function test_el_indice_lista_las_seis_consultas(): void
    {
        $props = $this->actingAs($this->usuarioCon('Super Admin'))
            ->get(route('consultas.index'))
            ->assertOk()
            ->viewData('page')['props'];

        $this->assertCount(6, $props['catalogo']);
    }

    public function test_sin_permiso_de_consultas_no_se_entra(): void
    {
        $this->actingAs($this->usuarioCon('Operario'))
            ->get(route('consultas.index'))
            ->assertForbidden();
    }

    /* ---------------------------------------------------------------- *
     * 1. Personas por municipio
     * ---------------------------------------------------------------- */

    public function test_personas_por_municipio_agrupa_y_calcula_porcentajes(): void
    {
        $this->sembrarEmbudo();

        $props = $this->abrir('personas-por-municipio');
        $filas = collect($props['tabla']['data'])->keyBy('municipio');

        $this->assertSame(2, $filas['Valladolid']['total']);
        $this->assertSame(1, $filas['Mérida']['total']);
        $this->assertSame('50 %', $filas['Valladolid']['porcentaje']);
        $this->assertNotNull($props['grafica']);
    }

    public function test_el_filtro_de_municipio_acota_el_resultado(): void
    {
        $this->sembrarEmbudo();

        $props = $this->abrir('personas-por-municipio', ['municipio' => 'Tekax']);

        $this->assertCount(1, $props['tabla']['data']);
        $this->assertSame('Tekax', $props['tabla']['data'][0]['municipio']);
    }

    /* ---------------------------------------------------------------- *
     * 2. Cruce de módulos
     * ---------------------------------------------------------------- */

    public function test_el_cruce_con_y_devuelve_solo_a_quien_esta_en_ambos(): void
    {
        $this->sembrarEmbudo();

        $props = $this->abrir('cruce-de-modulos', [
            'modulos' => ['crea', 'impulsate'],
            'operador' => 'y',
        ]);

        $nombres = collect($props['tabla']['data'])->pluck('nombre_completo');

        $this->assertCount(2, $nombres);
        $this->assertTrue($nombres->contains('Ana Canul Poot'));
        $this->assertTrue($nombres->contains('Beto Chi Dzul'));
        $this->assertFalse($nombres->contains('Carmen Uc Balam'));
    }

    public function test_el_cruce_con_o_suma_a_quien_este_en_cualquiera(): void
    {
        $this->sembrarEmbudo();

        $props = $this->abrir('cruce-de-modulos', [
            'modulos' => ['crea', 'impulsate'],
            'operador' => 'o',
        ]);

        $this->assertCount(3, $props['tabla']['data']);
    }

    public function test_el_cruce_con_sin_devuelve_a_quien_no_esta_en_ninguno(): void
    {
        $this->sembrarEmbudo();

        $props = $this->abrir('cruce-de-modulos', [
            'modulos' => ['crea', 'impulsate'],
            'operador' => 'sin',
        ]);

        $this->assertCount(1, $props['tabla']['data']);
        $this->assertSame('Diego Tun Ek', $props['tabla']['data'][0]['nombre_completo']);
    }

    public function test_con_un_solo_modulo_el_cruce_no_devuelve_el_padron_entero(): void
    {
        $this->sembrarEmbudo();

        $props = $this->abrir('cruce-de-modulos', ['modulos' => ['crea'], 'operador' => 'y']);

        $this->assertCount(0, $props['tabla']['data']);
        $this->assertNull($props['grafica']);
    }

    /* ---------------------------------------------------------------- *
     * 3. Embudo del emprendedor
     * ---------------------------------------------------------------- */

    public function test_el_embudo_solo_cuenta_a_quien_cumplio_las_etapas_anteriores(): void
    {
        $this->sembrarEmbudo();

        $props = $this->abrir('embudo-del-emprendedor');
        $etapas = collect($props['tabla']['data']);

        $this->assertSame([3, 2, 1, 1], $etapas->pluck('personas')->all());
        $this->assertSame('100 %', $etapas[0]['conversion_etapa']);
        $this->assertSame('66.7 %', $etapas[1]['conversion_etapa']);
        $this->assertSame('33.3 %', $etapas[2]['conversion_total']);
        // Entre Impúlsate y CREA se pierde una persona (Carmen).
        $this->assertSame(1, $etapas[1]['perdidas']);
    }

    /* ---------------------------------------------------------------- *
     * 4. Cobertura territorial
     * ---------------------------------------------------------------- */

    public function test_la_cobertura_señala_los_municipios_sin_presencia(): void
    {
        $this->sembrarEmbudo();

        $props = $this->abrir('cobertura-territorial');
        $filas = collect($props['tabla']['data']);

        // Los huecos van primero.
        $this->assertSame('Sin presencia', $filas->first()['situacion']);
        $this->assertSame(0, $filas->first()['personas']);

        $resumen = collect($props['resumen'])->keyBy('etiqueta');
        $this->assertSame(count(config('municipios_yucatan')), $resumen['Municipios en el catálogo']['valor']);
    }

    /* ---------------------------------------------------------------- *
     * 5. Personas sin actividad
     * ---------------------------------------------------------------- */

    public function test_sin_actividad_encuentra_a_quien_no_tiene_movimientos_recientes(): void
    {
        $gente = $this->sembrarEmbudo();

        // Un movimiento reciente saca a la persona de la lista.
        CreaSolicitud::create([
            'persona_id' => $gente['diego']->id,
            'estado_solicitud' => 'enviada',
            'fecha_solicitud' => now(),
        ]);

        $props = $this->abrir('personas-sin-actividad', ['meses' => 6]);
        $nombres = collect($props['tabla']['data'])->pluck('nombre_completo');

        $this->assertFalse($nombres->contains('Diego Tun Ek'), 'Diego tuvo un movimiento hoy.');
        $this->assertTrue($nombres->contains('Carmen Uc Balam'), 'Carmen no se mueve desde hace 10 meses.');
    }

    /* ---------------------------------------------------------------- *
     * 6. Calidad de datos
     * ---------------------------------------------------------------- */

    public function test_la_calidad_de_datos_mide_campo_por_campo(): void
    {
        $this->sembrarEmbudo();

        $props = $this->abrir('calidad-de-datos');
        $filas = collect($props['tabla']['data'])->keyBy('clave');

        // De cuatro personas, solo Ana tiene CURP.
        $this->assertSame(1, $filas['curp']['capturados']);
        $this->assertSame(3, $filas['curp']['vacios']);
        $this->assertSame('25 %', $filas['curp']['porcentaje']);

        // Todas tienen municipio.
        $this->assertSame(4, $filas['municipio']['capturados']);
        $this->assertSame('100 %', $filas['municipio']['porcentaje']);

        $this->assertSame('Crítico', $filas['curp']['importancia']);
    }

    /* ---------------------------------------------------------------- *
     * Exportación
     * ---------------------------------------------------------------- */

    public function test_la_exportacion_csv_trae_todas_las_filas_y_el_bom(): void
    {
        $this->sembrarEmbudo();

        $respuesta = $this->actingAs($this->usuarioCon('Super Admin'))
            ->get(route('consultas.exportar', [
                'clave' => 'personas-por-municipio',
                'formato' => 'csv',
            ]))
            ->assertOk();

        $contenido = $respuesta->streamedContent();

        $this->assertStringStartsWith("\xEF\xBB\xBF", $contenido, 'Sin BOM, Excel rompe los acentos.');
        $this->assertStringContainsString('Personas por municipio', $contenido);
        $this->assertStringContainsString('Valladolid', $contenido);
        $this->assertStringContainsString('Tekax', $contenido);
    }

    public function test_sin_permiso_de_exportar_no_se_descarga(): void
    {
        $this->actingAs($this->usuarioCon('Operario'))
            ->get(route('consultas.exportar', ['clave' => 'personas-por-municipio', 'formato' => 'csv']))
            ->assertForbidden();
    }

    public function test_una_consulta_inexistente_da_404_al_exportar(): void
    {
        $this->actingAs($this->usuarioCon('Super Admin'))
            ->get(route('consultas.exportar', ['clave' => 'no-existe', 'formato' => 'csv']))
            ->assertNotFound();
    }

    public function test_los_filtros_viajan_en_la_url_para_poder_compartirse(): void
    {
        $this->sembrarEmbudo();

        $props = $this->abrir('personas-por-municipio', [
            'municipio' => 'Valladolid',
            'desde' => '2020-01-01',
        ]);

        $this->assertSame('Valladolid', $props['filtros']['municipio']);
        $this->assertSame('2020-01-01', $props['filtros']['desde']);
    }
}
