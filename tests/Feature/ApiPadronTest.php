<?php

namespace Tests\Feature;

use App\Models\EventoModulo;
use App\Models\Modulos\CreaSolicitud;
use App\Models\Persona;
use App\Models\SistemaIntegrado;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiPadronTest extends TestCase
{
    use RefreshDatabase;

    private SistemaIntegrado $sistema;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sistema = SistemaIntegrado::create([
            'nombre' => 'CREA',
            'slug' => 'crea',
            'url_base' => 'https://crea.iyemyucatan.com',
            'activo' => true,
        ]);
    }

    private function comoSistema(array $habilidades = ['padron:leer', 'padron:escribir', 'eventos:escribir']): SistemaIntegrado
    {
        Sanctum::actingAs($this->sistema, $habilidades);

        return $this->sistema;
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

    /* ---------------------------------------------------------------- *
     * Autenticación y habilidades
     * ---------------------------------------------------------------- */

    public function test_sin_token_la_api_responde_401(): void
    {
        $this->getJson('/api/v1/personas')->assertUnauthorized();
    }

    public function test_un_token_de_solo_lectura_no_puede_escribir(): void
    {
        $this->comoSistema(['padron:leer']);

        $this->getJson('/api/v1/personas')->assertOk();

        $this->postJson('/api/v1/personas', [
            'nombre_completo' => 'Nueva Persona',
        ])->assertForbidden();
    }

    public function test_el_health_check_responde_sin_token(): void
    {
        $this->getJson('/api/v1/salud')
            ->assertOk()
            ->assertJsonPath('estado', 'en_linea')
            ->assertJsonPath('version_api', 'v1')
            ->assertJsonStructure(['estado', 'servicio', 'base_de_datos', 'personas', 'hora']);
    }

    /* ---------------------------------------------------------------- *
     * Lectura
     * ---------------------------------------------------------------- */

    public function test_el_listado_pagina_y_filtra_por_municipio(): void
    {
        $this->comoSistema();

        $this->persona(['municipio' => 'Mérida']);
        $this->persona([
            'nombre_completo' => 'José Chi Yam',
            'email' => 'jose.chi@ejemplo-demo.mx',
            'curp' => 'CIYJ641216HZZHMS44',
            'rfc' => null,
            'telefono' => '9997654321',
            'municipio' => 'Valladolid',
        ]);

        $this->getJson('/api/v1/personas')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure(['data' => [['id', 'nombre_completo', 'curp', 'domicilio', 'demograficos']], 'meta']);

        $this->getJson('/api/v1/personas?municipio=Valladolid')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nombre_completo', 'José Chi Yam');
    }

    public function test_la_busqueda_encuentra_por_curp_correo_y_telefono(): void
    {
        $this->comoSistema();
        $persona = $this->persona();

        foreach (['POCC900101', 'candelaria.poot', '999-123-4567', 'Candelaria'] as $termino) {
            $this->getJson('/api/v1/personas/buscar?q='.urlencode($termino))
                ->assertOk()
                ->assertJsonPath('data.0.id', $persona->id);
        }
    }

    public function test_la_busqueda_exige_al_menos_tres_caracteres(): void
    {
        $this->comoSistema();

        $this->getJson('/api/v1/personas/buscar?q=ab')
            ->assertStatus(422)
            ->assertJsonValidationErrors('q');
    }

    public function test_la_ficha_devuelve_a_la_persona_completa(): void
    {
        $this->comoSistema();
        $persona = $this->persona();

        $this->getJson("/api/v1/personas/{$persona->id}")
            ->assertOk()
            ->assertJsonPath('data.curp', 'POCC900101MZZTNN01')
            ->assertJsonPath('data.domicilio.municipio', 'Mérida');
    }

    public function test_los_vinculos_reunen_los_registros_de_todos_los_modulos(): void
    {
        $this->comoSistema();
        $persona = $this->persona();

        CreaSolicitud::create([
            'persona_id' => $persona->id,
            'monto_solicitado' => 25000,
            'estado_solicitud' => 'aprobada',
            'fecha_solicitud' => now()->subMonth(),
        ]);

        $respuesta = $this->getJson("/api/v1/personas/{$persona->id}/vinculos")->assertOk();

        $respuesta->assertJsonPath('data.persona_id', $persona->id);
        $respuesta->assertJsonPath('data.vinculos.0.slug', 'crea');
        $respuesta->assertJsonPath('data.vinculos.0.total', 1);
        $this->assertNotEmpty($respuesta->json('data.linea_de_tiempo'));
    }

    /* ---------------------------------------------------------------- *
     * Escritura
     * ---------------------------------------------------------------- */

    public function test_el_alta_crea_a_la_persona_y_registra_el_modulo_de_origen(): void
    {
        $this->comoSistema();

        $this->postJson('/api/v1/personas', [
            'nombre_completo' => 'Wilberth Canul Dzul',
            'curp' => 'CADW880216HZZNZL05',
            'telefono' => '9995551234',
        ])
            ->assertCreated()
            ->assertJsonPath('meta.creada', true)
            ->assertJsonPath('data.creado_por_modulo', 'crea');

        $this->assertDatabaseHas('personas', ['curp' => 'CADW880216HZZNZL05']);
    }

    public function test_el_alta_es_idempotente_por_curp(): void
    {
        $this->comoSistema();
        $persona = $this->persona();

        // El módulo reintenta tras un timeout: no debe duplicar a nadie.
        $this->postJson('/api/v1/personas', [
            'nombre_completo' => 'Candelaria Poot',
            'curp' => 'POCC900101MZZTNN01',
        ])
            ->assertOk()
            ->assertJsonPath('meta.creada', false)
            ->assertJsonPath('meta.motivo', 'ya_existia_esa_curp')
            ->assertJsonPath('data.id', $persona->id);

        $this->assertSame(1, Persona::where('curp', 'POCC900101MZZTNN01')->count());
    }

    public function test_el_alta_rechaza_una_curp_mal_formada(): void
    {
        $this->comoSistema();

        $this->postJson('/api/v1/personas', [
            'nombre_completo' => 'Persona Inválida',
            'curp' => 'ESTONOESUNACURP123',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('curp');
    }

    public function test_la_actualizacion_deja_rastro_en_la_auditoria(): void
    {
        $this->comoSistema();
        $persona = $this->persona();

        $this->putJson("/api/v1/personas/{$persona->id}", [
            'telefono' => '9990001111',
            'municipio' => 'Progreso',
        ])
            ->assertOk()
            ->assertJsonPath('data.telefono', '9990001111');

        $this->assertDatabaseHas('personas_auditorias', [
            'persona_id' => $persona->id,
            'campo_modificado' => 'telefono',
            'valor_anterior' => '9991234567',
            'valor_nuevo' => '9990001111',
            'modulo_origen' => 'crea',
        ]);
    }

    /* ---------------------------------------------------------------- *
     * Resolver: la pieza que evita duplicados
     * ---------------------------------------------------------------- */

    public function test_resolver_encuentra_por_curp(): void
    {
        $this->comoSistema();
        $persona = $this->persona();

        $this->postJson('/api/v1/personas/resolver', ['curp' => 'POCC900101MZZTNN01'])
            ->assertOk()
            ->assertJsonPath('meta.persona_id', $persona->id)
            ->assertJsonPath('meta.creada', false)
            ->assertJsonPath('meta.coincidio_por', 'curp');
    }

    public function test_resolver_respeta_la_prioridad_curp_sobre_correo(): void
    {
        $this->comoSistema();
        $porCurp = $this->persona();
        $porCorreo = $this->persona([
            'nombre_completo' => 'Otra Persona',
            'curp' => 'OTPX900101MZZTRR02',
            'rfc' => null,
            'email' => 'otro.correo@ejemplo-demo.mx',
            'telefono' => '9998887777',
        ]);

        // Datos mezclados: la CURP de una y el correo de la otra. Gana la CURP.
        $this->postJson('/api/v1/personas/resolver', [
            'curp' => 'POCC900101MZZTNN01',
            'email' => 'otro.correo@ejemplo-demo.mx',
        ])
            ->assertOk()
            ->assertJsonPath('meta.persona_id', $porCurp->id)
            ->assertJsonPath('meta.coincidio_por', 'curp');

        $this->assertNotSame($porCorreo->id, $porCurp->id);
    }

    public function test_resolver_empareja_por_telefono_solo_si_el_nombre_se_parece(): void
    {
        $this->comoSistema();
        $persona = $this->persona();

        // Mismo teléfono y nombre equivalente (falta un apellido): coincide.
        $this->postJson('/api/v1/personas/resolver', [
            'telefono' => '999 123 4567',
            'nombre' => 'Candelaria Poot',
        ])
            ->assertOk()
            ->assertJsonPath('meta.persona_id', $persona->id)
            ->assertJsonPath('meta.coincidio_por', 'telefono_y_nombre');

        // Mismo teléfono, otra persona del mismo negocio: NO debe coincidir.
        $this->postJson('/api/v1/personas/resolver', [
            'telefono' => '9991234567',
            'nombre' => 'Wilberth Canul Dzul',
        ])
            ->assertCreated()
            ->assertJsonPath('meta.creada', true);
    }

    public function test_resolver_crea_a_quien_no_existe_y_lo_atribuye_al_modulo(): void
    {
        $this->comoSistema();

        $respuesta = $this->postJson('/api/v1/personas/resolver', [
            'nombre' => 'Nayeli Herrera Cetina',
            'curp' => 'HECN670715MZZRTY11',
            'municipio' => 'Tekax',
        ])->assertCreated();

        $this->assertTrue($respuesta->json('meta.creada'));
        $this->assertNull($respuesta->json('meta.coincidio_por'));

        $this->assertDatabaseHas('personas', [
            'curp' => 'HECN670715MZZRTY11',
            'creado_por_modulo' => 'crea',
        ]);
    }

    public function test_resolver_completa_los_huecos_sin_pisar_lo_ya_capturado(): void
    {
        $this->comoSistema();
        $persona = $this->persona(['municipio' => 'Mérida', 'rfc' => null]);

        $this->postJson('/api/v1/personas/resolver', [
            'curp' => 'POCC900101MZZTNN01',
            'rfc' => 'POCC900101XY9',      // hueco: se completa
            'municipio' => 'Valladolid',   // ya tenía valor: NO se pisa
        ])->assertOk();

        $persona->refresh();

        $this->assertSame('POCC900101XY9', $persona->getRawOriginal('rfc'));
        $this->assertSame('Mérida', $persona->municipio);
    }

    public function test_resolver_exige_al_menos_un_identificador(): void
    {
        $this->comoSistema();

        $this->postJson('/api/v1/personas/resolver', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('nombre');
    }

    /* ---------------------------------------------------------------- *
     * Eventos
     * ---------------------------------------------------------------- */

    public function test_un_modulo_puede_reportar_un_evento_resolviendo_a_la_persona(): void
    {
        $this->comoSistema();

        $this->postJson('/api/v1/eventos', [
            'persona' => ['curp' => 'HECN670715MZZRTY11', 'nombre' => 'Nayeli Herrera'],
            'tipo' => 'solicitud_aprobada',
            'titulo' => 'Solicitud de crédito aprobada',
            'detalle' => 'Crédito semilla por $25,000',
            'estado' => 'aprobada',
            'referencia_externa' => 'CREA-4471',
        ])
            ->assertCreated()
            ->assertJsonPath('meta.creado', true)
            ->assertJsonPath('data.modulo', 'crea');

        $this->assertDatabaseHas('eventos_modulo', [
            'modulo' => 'crea',
            'referencia_externa' => 'CREA-4471',
            'tipo' => 'solicitud_aprobada',
        ]);
    }

    public function test_reenviar_el_mismo_evento_no_lo_duplica(): void
    {
        $this->comoSistema();
        $persona = $this->persona();

        $cuerpo = [
            'persona_id' => $persona->id,
            'tipo' => 'solicitud_aprobada',
            'titulo' => 'Solicitud aprobada',
            'referencia_externa' => 'CREA-9001',
        ];

        $this->postJson('/api/v1/eventos', $cuerpo)->assertCreated();
        $this->postJson('/api/v1/eventos', [...$cuerpo, 'titulo' => 'Solicitud aprobada (corregido)'])
            ->assertOk()
            ->assertJsonPath('meta.creado', false);

        $this->assertSame(1, EventoModulo::where('referencia_externa', 'CREA-9001')->count());
        $this->assertDatabaseHas('eventos_modulo', [
            'referencia_externa' => 'CREA-9001',
            'titulo' => 'Solicitud aprobada (corregido)',
        ]);
    }

    public function test_reportar_un_evento_exige_la_habilidad_correspondiente(): void
    {
        $this->comoSistema(['padron:leer', 'padron:escribir']);
        $persona = $this->persona();

        $this->postJson('/api/v1/eventos', [
            'persona_id' => $persona->id,
            'tipo' => 'nota',
            'titulo' => 'Sin permiso',
        ])->assertForbidden();
    }
}
