<?php

namespace Tests\Feature;

use App\Models\Modulos\CreaSolicitud;
use App\Models\Modulos\ImpulsateInscripcion;
use App\Models\Persona;
use App\Models\PersonaFusion;
use App\Models\User;
use App\Services\DetectorDuplicados;
use App\Services\FusionadorPersonas;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DuplicadosTest extends TestCase
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
            'municipio' => 'Mérida',
            'estado' => 'Yucatán',
            ...$atributos,
        ]);
    }

    /* ---------------------------------------------------------------- *
     * Detección
     * ---------------------------------------------------------------- */

    public function test_detecta_duplicados_por_rfc(): void
    {
        $this->persona(['nombre_completo' => 'Adriana Peniche Gómez', 'rfc' => 'PEGA880216E57']);
        $this->persona(['nombre_completo' => 'Adriana Peniche G.', 'rfc' => 'PEGA880216E57']);
        $this->persona(['nombre_completo' => 'Sin Relación Alguna', 'rfc' => 'XXXX000000AAA']);

        $grupos = app(DetectorDuplicados::class)->detectar(incluirSimilitud: false);

        $this->assertCount(1, $grupos);
        $this->assertSame('rfc', $grupos[0]['criterio']);
        $this->assertSame('alta', $grupos[0]['confianza']);
        $this->assertCount(2, $grupos[0]['personas']);
    }

    public function test_detecta_duplicados_por_telefono(): void
    {
        $this->persona(['nombre_completo' => 'Ana Canul', 'telefono' => '9991112233']);
        $this->persona(['nombre_completo' => 'Ana Canul Poot', 'telefono' => '9991112233']);

        $grupos = app(DetectorDuplicados::class)->detectar(incluirSimilitud: false);

        $this->assertCount(1, $grupos);
        $this->assertSame('telefono', $grupos[0]['criterio']);
        $this->assertSame('media', $grupos[0]['confianza']);
    }

    public function test_la_similitud_acepta_erratas_y_rechaza_hermanos(): void
    {
        $detector = app(DetectorDuplicados::class);

        // Acento y errata: por encima del umbral.
        $this->assertGreaterThanOrEqual(
            DetectorDuplicados::SIMILITUD_MINIMA,
            $detector->similitud('José Chi Yam', 'Jose Chi Yam')
        );
        $this->assertGreaterThanOrEqual(
            DetectorDuplicados::SIMILITUD_MINIMA,
            $detector->similitud('Ana Canul Poot', 'Ana Canul Pool')
        );

        // Dos hermanos comparten apellidos pero no son la misma persona.
        $this->assertLessThan(
            DetectorDuplicados::SIMILITUD_MINIMA,
            $detector->similitud('Ana Canul Poot', 'Beto Canul Poot')
        );
    }

    public function test_detecta_nombres_parecidos_en_el_mismo_municipio(): void
    {
        $this->persona(['nombre_completo' => 'José Chi Yam', 'municipio' => 'Valladolid']);
        $this->persona(['nombre_completo' => 'Jose Chi Yam', 'municipio' => 'Valladolid']);

        $grupos = app(DetectorDuplicados::class)->detectar();

        $this->assertTrue($grupos->contains(fn ($g) => $g['criterio'] === 'similitud_nombre'));
    }

    public function test_el_detector_reporta_los_criterios_que_el_esquema_impide(): void
    {
        // `curp` y `email` son UNIQUE: esos duplicados no pueden existir, y
        // el detector debe decirlo en vez de reportar cero como si fuera un
        // logro de calidad.
        $bloqueados = app(DetectorDuplicados::class)->criteriosImposibles();

        $this->assertContains('curp', $bloqueados);
        $this->assertContains('email', $bloqueados);
    }

    /* ---------------------------------------------------------------- *
     * Fusión
     * ---------------------------------------------------------------- */

    public function test_la_fusion_mueve_vinculos_etiquetas_y_completa_huecos(): void
    {
        $principal = $this->persona([
            'nombre_completo' => 'Ana Canul Poot',
            'telefono' => '9991112233',
            'curp' => null,
        ]);
        $duplicada = $this->persona([
            'nombre_completo' => 'Ana Canul',
            'telefono' => '9991112233',
            'curp' => 'CAPA900101MZZNTN01',
            'rfc' => 'CAPA900101AB1',
        ]);

        CreaSolicitud::create([
            'persona_id' => $duplicada->id,
            'estado_solicitud' => 'aprobada',
            'fecha_solicitud' => now()->subMonth(),
        ]);
        ImpulsateInscripcion::create([
            'persona_id' => $duplicada->id,
            'programa_nombre' => 'Impúlsate Básico',
            'estado' => 'completada',
        ]);
        $duplicada->agregarEtiqueta('artesano');
        $principal->agregarEtiqueta('emprendedor');

        $fusion = app(FusionadorPersonas::class)->fusionar(
            $principal, $duplicada, $this->usuarioCon('Super Admin'), 'telefono', 'Se confirmó por teléfono.'
        );

        $principal->refresh();

        // Los trámites cambiaron de dueño.
        $this->assertSame(1, $principal->creaSolicitudes()->count());
        $this->assertSame(1, $principal->impulstateInscripciones()->count());

        // El hueco de CURP se llenó con el de la duplicada.
        $this->assertSame('CAPA900101MZZNTN01', $principal->getRawOriginal('curp'));

        // Las etiquetas se juntaron.
        $this->assertEqualsCanonicalizing(
            ['emprendedor', 'artesano'],
            $principal->etiquetas()->pluck('etiqueta')->all()
        );

        // La duplicada quedó archivada, no borrada.
        $this->assertSoftDeleted('personas', ['id' => $duplicada->id]);

        // Quedó constancia y ventana para deshacer.
        $this->assertTrue($fusion->esRevertible());
        $this->assertSame('telefono', $fusion->criterio);
        $this->assertDatabaseHas('personas_auditorias', [
            'persona_id' => $principal->id,
            'campo_modificado' => '__fusion__',
        ]);
    }

    public function test_la_fusion_no_pisa_un_dato_que_la_principal_ya_tenia(): void
    {
        $principal = $this->persona(['telefono' => '9991112233', 'municipio' => 'Mérida']);
        $duplicada = $this->persona(['telefono' => '9991112233', 'municipio' => 'Valladolid']);

        app(FusionadorPersonas::class)->fusionar($principal, $duplicada, $this->usuarioCon('Super Admin'));

        $this->assertSame('Mérida', $principal->refresh()->municipio);
    }

    public function test_no_se_puede_fusionar_una_persona_consigo_misma(): void
    {
        $persona = $this->persona();

        $this->expectException(\InvalidArgumentException::class);

        app(FusionadorPersonas::class)->fusionar($persona, $persona, $this->usuarioCon('Super Admin'));
    }

    /* ---------------------------------------------------------------- *
     * Reversión
     * ---------------------------------------------------------------- */

    public function test_la_fusion_se_puede_deshacer_y_todo_vuelve_a_su_lugar(): void
    {
        $principal = $this->persona(['nombre_completo' => 'Ana Canul Poot', 'curp' => null]);
        $duplicada = $this->persona(['nombre_completo' => 'Ana Canul', 'curp' => 'CAPA900101MZZNTN01']);

        $solicitud = CreaSolicitud::create([
            'persona_id' => $duplicada->id,
            'estado_solicitud' => 'aprobada',
            'fecha_solicitud' => now()->subMonth(),
        ]);
        $duplicada->agregarEtiqueta('artesano');

        $usuario = $this->usuarioCon('Super Admin');
        $fusionador = app(FusionadorPersonas::class);

        $fusion = $fusionador->fusionar($principal, $duplicada, $usuario);
        $fusionador->revertir($fusion, $usuario);

        $principal->refresh();

        // El trámite volvió con su dueña original.
        $this->assertSame($duplicada->id, $solicitud->fresh()->persona_id);

        // La CURP prestada se volvió a vaciar.
        $this->assertNull($principal->getRawOriginal('curp'));

        // La etiqueta regresó.
        $this->assertSame([], $principal->etiquetas()->pluck('etiqueta')->all());
        $this->assertDatabaseHas('personas_etiquetas', [
            'persona_id' => $duplicada->id,
            'etiqueta' => 'artesano',
        ]);

        // La ficha archivada revivió.
        $this->assertNotSoftDeleted('personas', ['id' => $duplicada->id]);
        $this->assertNotNull($fusion->fresh()->revertida_at);
    }

    public function test_al_revertir_no_se_arrastran_los_registros_creados_despues(): void
    {
        $principal = $this->persona(['nombre_completo' => 'Ana Canul Poot']);
        $duplicada = $this->persona(['nombre_completo' => 'Ana Canul']);

        $delaDuplicada = CreaSolicitud::create([
            'persona_id' => $duplicada->id,
            'estado_solicitud' => 'aprobada',
            'fecha_solicitud' => now()->subMonth(),
        ]);

        $usuario = $this->usuarioCon('Super Admin');
        $fusionador = app(FusionadorPersonas::class);
        $fusion = $fusionador->fusionar($principal, $duplicada, $usuario);

        // Después de la fusión, la principal recibe un trámite propio.
        $posterior = CreaSolicitud::create([
            'persona_id' => $principal->id,
            'estado_solicitud' => 'enviada',
            'fecha_solicitud' => now(),
        ]);

        $fusionador->revertir($fusion, $usuario);

        // Solo regresa el que se movió; el nuevo se queda con la principal.
        $this->assertSame($duplicada->id, $delaDuplicada->fresh()->persona_id);
        $this->assertSame($principal->id, $posterior->fresh()->persona_id);
    }

    public function test_una_fusion_ya_revertida_no_se_revierte_dos_veces(): void
    {
        $usuario = $this->usuarioCon('Super Admin');
        $fusionador = app(FusionadorPersonas::class);

        $fusion = $fusionador->fusionar($this->persona(), $this->persona(), $usuario);
        $fusionador->revertir($fusion, $usuario);

        $this->expectException(\RuntimeException::class);
        $fusionador->revertir($fusion->fresh(), $usuario);
    }

    public function test_pasada_la_ventana_de_30_dias_ya_no_se_puede_deshacer(): void
    {
        $usuario = $this->usuarioCon('Super Admin');
        $fusionador = app(FusionadorPersonas::class);

        $fusion = $fusionador->fusionar($this->persona(), $this->persona(), $usuario);

        $fusion->update(['revertible_hasta' => now()->subDay()]);

        $this->assertFalse($fusion->fresh()->esRevertible());

        $this->expectException(\RuntimeException::class);
        $fusionador->revertir($fusion->fresh(), $usuario);
    }

    /* ---------------------------------------------------------------- *
     * Pantalla y permisos
     * ---------------------------------------------------------------- */

    public function test_la_pantalla_de_duplicados_es_solo_del_super_admin(): void
    {
        $this->actingAs($this->usuarioCon('Super Admin'))
            ->get(route('padron.duplicados.index'))
            ->assertOk();

        $this->actingAs($this->usuarioCon('Admin Área'))
            ->get(route('padron.duplicados.index'))
            ->assertForbidden();
    }

    public function test_fusionar_desde_la_pantalla_deja_bitacora(): void
    {
        $principal = $this->persona(['telefono' => '9991112233']);
        $duplicada = $this->persona(['telefono' => '9991112233']);

        $this->actingAs($this->usuarioCon('Super Admin'))
            ->post(route('padron.duplicados.fusionar'), [
                'principal_id' => $principal->id,
                'duplicada_id' => $duplicada->id,
                'criterio' => 'telefono',
                'motivo' => 'Confirmado con la persona.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('personas_fusiones', [
            'principal_id' => $principal->id,
            'duplicada_id' => $duplicada->id,
            'motivo' => 'Confirmado con la persona.',
        ]);
    }

    public function test_la_pantalla_rechaza_fusionar_una_persona_consigo_misma(): void
    {
        $persona = $this->persona();

        $this->actingAs($this->usuarioCon('Super Admin'))
            ->post(route('padron.duplicados.fusionar'), [
                'principal_id' => $persona->id,
                'duplicada_id' => $persona->id,
            ])
            ->assertSessionHasErrors('duplicada_id');

        $this->assertSame(0, PersonaFusion::count());
    }
}
