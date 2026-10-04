<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Modulos\ClienteDeModulo;
use App\Services\Modulos\Falla;
use App\Services\Modulos\RespuestaDeModulo;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as PeticionHttp;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Tablero de Nódico 2.0 dentro del ERP: el patrón "módulo con tablero".
 *
 * La API de Nódico se falsea con `Http::fake()`; las formas de las respuestas
 * son las de `docs/API-MOVIL.md §6.11` del repositorio de CoworkHub.
 */
class ModuloTableroTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'token-de-prueba-que-nunca-debe-salir-del-servidor';

    private const API = 'https://nodico.test/api/v1';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        config([
            'modulos.coworkhub.api_base' => self::API,
            'services.modulos.coworkhub.token' => self::TOKEN,
        ]);
    }

    private function usuarioCon(string $rol, array $permisosExtra = []): User
    {
        $usuario = User::factory()->create(['estado' => true])->assignRole($rol);

        return $permisosExtra ? $usuario->givePermissionTo($permisosExtra) : $usuario;
    }

    private function resumenDeNodico(): array
    {
        return ['data' => [
            'rango' => ['desde' => '2026-10-01', 'hasta' => '2026-10-03', 'etiqueta' => '1 oct 2026 — 3 oct 2026', 'dias' => 3],
            'ocupacion_media_pct' => 62,
            'reservas' => 148,
            'horas_reservadas' => 310.5,
            'ingresos' => 124800.0,
            'ingresos_membresias' => 98800.0,
            'facturado_pagado' => 61000.0,
            'ingresos_salones' => 26000.0,
            'tasa_no_show_pct' => 7.4,
            'miembros_en_riesgo' => 9,
        ]];
    }

    private function falsearNodico(array $sobrescribir = []): void
    {
        $rango = ['desde' => '2026-10-01', 'hasta' => '2026-10-03', 'etiqueta' => '1 oct 2026 — 3 oct 2026', 'dias' => 3];

        Http::fake([
            ...$sobrescribir,
            // Primero: `reportes/ocupacion*` también atraparía `reportes/ocupacion/csv`.
            self::API.'/reportes/*/csv*' => Http::response("espacio,reservas\nSala Uxmal,20\n", 200, ['Content-Type' => 'text/csv']),
            self::API.'/reportes/resumen*' => Http::response($this->resumenDeNodico()),
            self::API.'/reportes/ocupacion*' => Http::response(['data' => [
                'rango' => $rango,
                'por_espacio' => [
                    ['espacio' => 'Sala Uxmal', 'tipo' => 'Sala de juntas', 'reservas' => 20, 'horas' => 40.0, 'capacidad_horas' => 80.0, 'ocupacion_pct' => 50],
                    ['espacio' => 'Escritorio 1', 'tipo' => 'Escritorio', 'reservas' => 10, 'horas' => 20.0, 'capacidad_horas' => 20.0, 'ocupacion_pct' => 100],
                ],
                'por_franja' => [['hora' => '09:00', 'reservas' => 12, 'pct' => 100]],
            ]]),
            self::API.'/reportes/ingresos*' => Http::response(['data' => [
                'rango' => $rango,
                'por_plan' => [['plan' => 'Nodo Pro', 'membresias' => 4, 'ingreso' => 98800.0]],
                'salones' => ['eventos' => 2, 'ingreso' => 26000.0, 'cobrado' => 13000.0],
                'total_membresias' => 98800.0,
                'facturado_pagado' => 61000.0,
            ]]),
            self::API.'/reportes/consumo*' => Http::response(['data' => ['rango' => $rango, 'filas' => []]]),
            self::API.'/reportes/en-riesgo*' => Http::response(['data' => [
                ['miembro' => 'Ana Pech', 'email' => 'ana@example.com', 'telefono' => '9990000000', 'plan' => 'Nodo Pro', 'vence' => '2026-11-01', 'ultimo_acceso' => '2026-09-01', 'dias_sin_venir' => 32],
            ]]),
            self::API.'/reportes/no-show*' => Http::response(['data' => [
                'rango' => $rango, 'total_reservas' => 100, 'no_show' => 1, 'tasa_pct' => 1.0, 'horas_perdidas' => 2.0,
                'por_miembro' => [['miembro' => 'Ana Pech', 'faltas' => 1, 'horas' => 2.0]],
            ]]),
        ]);
    }

    private function consultarResumen(): RespuestaDeModulo
    {
        return app(ClienteDeModulo::class)->consultar('coworkhub', 'reportes/resumen', ['desde' => '2026-10-01', 'hasta' => '2026-10-03']);
    }

    // ── 1. Normalización ────────────────────────────────────────────────────

    public function test_el_adaptador_traduce_el_resumen_al_idioma_del_erp(): void
    {
        $this->falsearNodico();

        $datos = $this->actingAs($this->usuarioCon('Super Admin'))
            ->getJson(route('modulos.datos', ['slug' => 'coworkhub', 'periodo' => 'personalizado', 'desde' => '2026-10-01', 'hasta' => '2026-10-03']))
            ->assertOk()
            ->json();

        $indicadores = collect($datos['resumen']['indicadores'])->keyBy('clave');

        $this->assertNull($datos['resumen']['falla']);
        $this->assertSame('1 oct 2026 — 3 oct 2026', $datos['resumen']['rango']['descripcion']);

        $this->assertEquals(62, $indicadores['ocupacion']['valor']);
        $this->assertSame('porcentaje', $indicadores['ocupacion']['unidad']);
        $this->assertEquals(124800, $indicadores['ingresos']['valor']);
        $this->assertSame('MXN', $indicadores['ingresos']['moneda']);
        $this->assertEquals(
            [98800, 26000, 61000],
            array_column($indicadores['ingresos']['desglose'], 'valor'),
        );
        $this->assertEquals(148, $indicadores['reservas']['valor']);
        $this->assertEquals(310.5, $indicadores['reservas']['desglose'][0]['valor']);
        $this->assertEquals(7.4, $indicadores['no_show']['valor']);
        $this->assertEquals(9, $indicadores['en_riesgo']['valor']);
        $this->assertTrue($indicadores['en_riesgo']['detalle_personal']);

        // La página no ve un solo nombre de campo de Nódico.
        $this->assertStringNotContainsString('tasa_no_show_pct', json_encode($datos));
        $this->assertStringNotContainsString('ocupacion_media_pct', json_encode($datos));

        // Pie de tabla: ocupación del conjunto = horas / capacidad (60 / 100).
        $espacios = collect($datos['secciones'])->firstWhere('clave', 'ocupacion_espacio');
        $this->assertEquals(60, $espacios['totales']['ocupacion_pct']);
        $this->assertEquals(30, $espacios['totales']['reservas']);

        // El periodo de la URL es el que se le pide a Nódico.
        Http::assertSent(fn (PeticionHttp $p) => str_contains($p->url(), 'reportes/resumen')
            && $p['desde'] === '2026-10-01' && $p['hasta'] === '2026-10-03');
    }

    public function test_un_campo_que_no_llega_es_ausencia_no_cero(): void
    {
        $sinOcupacion = $this->resumenDeNodico();
        unset($sinOcupacion['data']['ocupacion_media_pct']);
        Http::fake([self::API.'/*' => Http::response($sinOcupacion)]);

        $datos = $this->actingAs($this->usuarioCon('Super Admin'))
            ->getJson(route('modulos.datos', 'coworkhub'))->json();

        $this->assertNull(collect($datos['resumen']['indicadores'])->firstWhere('clave', 'ocupacion')['valor']);
    }

    // ── 2–5. Fallas ─────────────────────────────────────────────────────────

    public function test_sin_red_devuelve_ausencia_con_un_reintento(): void
    {
        Http::fake([self::API.'/*' => Http::failedConnection()]);

        $datos = $this->actingAs($this->usuarioCon('Super Admin'))
            ->getJson(route('modulos.datos', 'coworkhub'))
            ->assertOk()
            ->json();

        $this->assertSame(Falla::SIN_RED, $datos['resumen']['falla']['tipo']);
        $this->assertTrue($datos['resumen']['falla']['reintentable']);
        $this->assertSame([], $datos['resumen']['indicadores']);

        foreach ($datos['secciones'] as $seccion) {
            $this->assertSame(Falla::SIN_RED, $seccion['falla']['tipo']);
            $this->assertSame([], $seccion['filas']);
            $this->assertNull($seccion['totales']);
        }

        // Dos intentos al primer endpoint; los demás ya no esperan otro timeout.
        Http::assertSentCount(2);
    }

    public function test_un_error_5xx_se_reintenta_una_vez(): void
    {
        Http::fake([self::API.'/*' => Http::sequence()->push('', 500)->push($this->resumenDeNodico())]);

        $this->assertTrue($this->consultarResumen()->ok());
        Http::assertSentCount(2);
    }

    public function test_un_403_explica_que_revisar_y_no_se_reintenta(): void
    {
        Http::fake([self::API.'/*' => Http::response(['message' => 'Esta sección no es para tu tipo de cuenta.', 'codigo' => 'sin_permiso'], 403)]);

        $falla = $this->consultarResumen()->falla;

        $this->assertSame(Falla::SIN_PERMISO, $falla->tipo);
        $this->assertStringContainsString('no tiene permiso para consultar reportes', $falla->mensaje);
        $this->assertStringContainsString('ver-reportes', $falla->accion);
        $this->assertFalse($falla->reintentable);
        Http::assertSentCount(1);
    }

    public function test_en_mantenimiento_se_muestra_el_mensaje_de_nodico_tal_cual(): void
    {
        Http::fake([self::API.'/*' => Http::response(['message' => 'Cambiamos de servidor. Volvemos a las 14:00.', 'codigo' => 'mantenimiento'], 503)]);

        $falla = $this->consultarResumen()->falla;

        $this->assertSame(Falla::MANTENIMIENTO, $falla->tipo);
        $this->assertSame('Cambiamos de servidor. Volvemos a las 14:00.', $falla->mensaje);
        Http::assertSentCount(1);
    }

    public function test_un_429_no_se_reintenta(): void
    {
        Http::fake([self::API.'/*' => Http::response(['message' => 'Vas muy rápido.', 'codigo' => 'demasiadas_peticiones', 'reintentar_en' => 30], 429)]);

        $falla = $this->consultarResumen()->falla;

        $this->assertSame(Falla::LIMITE, $falla->tipo);
        $this->assertFalse($falla->reintentable);
        Http::assertSentCount(1);
    }

    public function test_la_falla_dice_cuando_fue_el_ultimo_dato_bueno(): void
    {
        Http::fake([self::API.'/*' => Http::sequence()
            ->push($this->resumenDeNodico())
            ->whenEmpty(Http::response(['codigo' => 'sin_permiso'], 403))]);

        $bueno = $this->consultarResumen();
        $this->travel(11)->minutes();
        $malo = $this->consultarResumen();

        $this->assertSame($bueno->obtenidoEn, $malo->falla->ultimoDato);
    }

    // ── 6. Caché ────────────────────────────────────────────────────────────

    public function test_dos_consultas_seguidas_con_el_mismo_periodo_hacen_una_sola_llamada(): void
    {
        $this->falsearNodico();

        $this->consultarResumen();
        $this->consultarResumen();

        Http::assertSentCount(1);

        // Otro periodo es otra consulta.
        app(ClienteDeModulo::class)->consultar('coworkhub', 'reportes/resumen', ['desde' => '2026-09-01', 'hasta' => '2026-09-30']);
        Http::assertSentCount(2);
    }

    public function test_no_se_manda_x_app_version_y_el_token_va_en_la_cabecera(): void
    {
        $this->falsearNodico();

        $this->consultarResumen();

        Http::assertSent(fn (PeticionHttp $p) => ! $p->hasHeader('X-App-Version')
            && $p->hasHeader('Authorization', 'Bearer '.self::TOKEN));
    }

    // ── 7. Permisos ─────────────────────────────────────────────────────────

    public function test_sin_ver_coworkhub_no_se_entra_al_tablero(): void
    {
        // Operario no ve Nódico 2.0, aunque se le dé el permiso de tableros.
        $operario = $this->usuarioCon('Operario', ['ver-modulo-tablero']);

        $this->actingAs($operario)->get(route('modulos.tablero', 'coworkhub'))->assertForbidden();
        $this->actingAs($operario)->getJson(route('modulos.datos', 'coworkhub'))->assertForbidden();
    }

    public function test_sin_ver_modulo_tablero_tampoco(): void
    {
        $usuario = $this->usuarioCon('Operario', ['ver-coworkhub']);

        $this->actingAs($usuario)->get(route('modulos.tablero', 'coworkhub'))->assertForbidden();

        // Y su tarjeta se comporta como antes: sin tablero.
        $modulos = collect($this->actingAs($usuario)->get(route('dashboard'))->viewData('page')['props']['modulos']);
        $this->assertNull($modulos->firstWhere('slug', 'coworkhub')['url_tablero']);
    }

    public function test_sin_ver_modulo_datos_personales_no_se_ve_la_lista_de_miembros(): void
    {
        $this->falsearNodico();
        $admin = $this->usuarioCon('Admin Área');

        $props = $this->actingAs($admin)->get(route('modulos.tablero', 'coworkhub'))->viewData('page')['props'];
        $this->assertFalse($props['puedeVerDatosPersonales']);
        $this->assertNotContains('en_riesgo', array_column($props['informes'], 'clave'));

        $this->actingAs($admin)->get(route('modulos.personas', 'coworkhub'))->assertForbidden();
        $this->actingAs($admin)->getJson(route('modulos.personas.datos', 'coworkhub'))->assertForbidden();
        $this->actingAs($admin)->get(route('modulos.informe', ['slug' => 'coworkhub', 'informe' => 'en_riesgo']))->assertForbidden();

        // El tablero solo trae el conteo, nunca nombres.
        $this->actingAs($admin)->getJson(route('modulos.datos', 'coworkhub'))
            ->assertOk()
            ->assertDontSee('Ana Pech');
    }

    // ── 8. Registro ─────────────────────────────────────────────────────────

    public function test_entrar_al_tablero_queda_en_accesos(): void
    {
        $usuario = $this->usuarioCon('Super Admin');

        $this->actingAs($usuario)->get(route('modulos.tablero', 'coworkhub'))->assertOk();

        $this->assertDatabaseHas('accesos', ['user_id' => $usuario->id, 'modulo' => 'coworkhub', 'detalle' => 'tablero']);
    }

    public function test_consultar_los_miembros_en_riesgo_queda_registrado_con_el_usuario(): void
    {
        $this->falsearNodico();
        $usuario = $this->usuarioCon('Super Admin');

        $this->actingAs($usuario)->getJson(route('modulos.personas.datos', 'coworkhub'))
            ->assertOk()
            ->assertJsonPath('secciones.0.filas.0.miembro', 'Ana Pech');

        $this->assertDatabaseHas('accesos', [
            'user_id' => $usuario->id,
            'modulo' => 'coworkhub',
            'detalle' => 'datos-personales: miembros_en_riesgo, inasistencias',
        ]);
    }

    public function test_los_datos_personales_no_se_guardan_en_cache(): void
    {
        $this->falsearNodico();
        $usuario = $this->usuarioCon('Super Admin');

        $this->actingAs($usuario)->getJson(route('modulos.personas.datos', 'coworkhub'))->assertOk();
        $this->actingAs($usuario)->getJson(route('modulos.personas.datos', 'coworkhub'))->assertOk();

        Http::assertSentCount(4);
    }

    // ── Exportación ─────────────────────────────────────────────────────────

    public function test_el_csv_pasa_por_el_erp_y_queda_registrado(): void
    {
        $this->falsearNodico();
        $usuario = $this->usuarioCon('Super Admin');

        $respuesta = $this->actingAs($usuario)
            ->get(route('modulos.informe', ['slug' => 'coworkhub', 'informe' => 'ocupacion', 'periodo' => 'personalizado', 'desde' => '2026-10-01', 'hasta' => '2026-10-03']))
            ->assertOk();

        $this->assertStringContainsString('Sala Uxmal,20', $respuesta->streamedContent());
        $this->assertStringContainsString('nodico-ocupacion-2026-10-01-2026-10-03.csv', $respuesta->headers->get('content-disposition'));
        $this->assertDatabaseHas('accesos', ['user_id' => $usuario->id, 'detalle' => 'informe: ocupacion.csv']);
    }

    public function test_un_csv_que_falla_no_baja_un_archivo_vacio(): void
    {
        Http::fake([self::API.'/*' => Http::failedConnection()]);

        $this->actingAs($this->usuarioCon('Super Admin'))
            ->get(route('modulos.informe', ['slug' => 'coworkhub', 'informe' => 'ocupacion']))
            ->assertStatus(502)
            ->assertJsonPath('falla.tipo', Falla::SIN_RED);
    }

    public function test_un_informe_que_el_modulo_no_ofrece_da_404(): void
    {
        $this->actingAs($this->usuarioCon('Super Admin'))
            ->get(route('modulos.informe', ['slug' => 'coworkhub', 'informe' => 'nomina']))
            ->assertNotFound();
    }

    // ── 9. El token nunca sale ──────────────────────────────────────────────

    public function test_el_token_no_aparece_en_ninguna_respuesta_ni_en_el_registro(): void
    {
        $this->falsearNodico();
        $registro = [];
        Event::listen(MessageLogged::class, function (MessageLogged $e) use (&$registro) {
            $registro[] = $e->message.json_encode($e->context);
        });

        $usuario = $this->usuarioCon('Super Admin');

        $tablero = $this->actingAs($usuario)->get(route('modulos.tablero', 'coworkhub'))->assertOk();
        $this->recorrerSinToken($tablero->viewData('page'));
        $tablero->assertDontSee(self::TOKEN);

        $dashboard = $this->actingAs($usuario)->get(route('dashboard'))->assertOk();
        $this->recorrerSinToken($dashboard->viewData('page'));

        foreach (['modulos.datos', 'modulos.personas.datos'] as $ruta) {
            $this->actingAs($usuario)->getJson(route($ruta, 'coworkhub'))->assertOk()->assertDontSee(self::TOKEN);
        }

        $this->assertNotEmpty($registro, 'Cada llamada a Nódico debe quedar en el registro.');
        foreach ($registro as $linea) {
            $this->assertStringNotContainsString(self::TOKEN, $linea);
        }
    }

    private function recorrerSinToken(mixed $valor, string $ruta = 'page'): void
    {
        if (is_array($valor)) {
            foreach ($valor as $clave => $hijo) {
                $this->assertStringNotContainsString(self::TOKEN, (string) $clave, "El token apareció como clave en {$ruta}.");
                $this->recorrerSinToken($hijo, "{$ruta}.{$clave}");
            }

            return;
        }

        if (is_scalar($valor)) {
            $this->assertStringNotContainsString(self::TOKEN, (string) $valor, "El token apareció en {$ruta}.");
        }
    }

    // ── 10. Módulo sin adaptador ────────────────────────────────────────────

    public function test_un_modulo_sin_adaptador_no_tiene_tablero(): void
    {
        $usuario = $this->usuarioCon('Super Admin');

        $this->actingAs($usuario)->get(route('modulos.tablero', 'crea'))->assertNotFound();
        $this->actingAs($usuario)->get(route('modulos.tablero', 'no-existe'))->assertNotFound();

        $modulos = collect($this->actingAs($usuario)->get(route('dashboard'))->viewData('page')['props']['modulos']);

        $crea = $modulos->firstWhere('slug', 'crea');
        $this->assertNull($crea['url_tablero']);
        $this->assertSame(route('dashboard.acceder', 'crea'), $crea['url']);

        $this->assertSame(route('modulos.tablero', 'coworkhub'), $modulos->firstWhere('slug', 'coworkhub')['url_tablero']);
    }

    // ── Periodo ─────────────────────────────────────────────────────────────

    public function test_un_periodo_de_mas_de_un_anio_se_rechaza(): void
    {
        $this->actingAs($this->usuarioCon('Super Admin'))
            ->getJson(route('modulos.datos', ['slug' => 'coworkhub', 'periodo' => 'personalizado', 'desde' => '2024-01-01', 'hasta' => '2026-01-01']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('desde');
    }

    public function test_el_periodo_viaja_en_la_url(): void
    {
        $props = $this->actingAs($this->usuarioCon('Super Admin'))
            ->get(route('modulos.tablero', ['slug' => 'coworkhub', 'periodo' => 'personalizado', 'desde' => '2026-09-01', 'hasta' => '2026-09-15']))
            ->viewData('page')['props'];

        $this->assertSame(['clave' => 'personalizado', 'desde' => '2026-09-01', 'hasta' => '2026-09-15'], array_intersect_key($props['periodo'], array_flip(['clave', 'desde', 'hasta'])));
    }

    public function test_el_tablero_se_pinta_sin_llamar_a_nodico(): void
    {
        Http::fake();

        $this->actingAs($this->usuarioCon('Super Admin'))->get(route('modulos.tablero', 'coworkhub'))->assertOk();

        Http::assertNothingSent();
    }

    public function test_los_permisos_nuevos_existen_y_solo_el_super_admin_ve_datos_personales(): void
    {
        $this->assertNotNull(Permission::findByName('ver-modulo-tablero'));
        $this->assertTrue($this->usuarioCon('Super Admin')->can('ver-modulo-datos-personales'));
        $this->assertFalse($this->usuarioCon('Admin Área')->can('ver-modulo-datos-personales'));
        $this->assertFalse($this->usuarioCon('Tester')->can('ver-modulo-tablero'));
    }
}
