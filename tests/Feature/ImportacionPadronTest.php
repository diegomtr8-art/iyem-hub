<?php

namespace Tests\Feature;

use App\Models\PadronImportacion;
use App\Models\Persona;
use App\Models\User;
use App\Services\ImportadorPadron;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImportacionPadronTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        Storage::fake('local');
    }

    private function usuarioCon(string $rol): User
    {
        return User::factory()->create(['estado' => true])->assignRole($rol);
    }

    /**
     * CSV con una fila buena, una sin nombre y una con CURP inválida.
     */
    private function archivoDePrueba(): UploadedFile
    {
        $contenido = implode("\n", [
            'Nombre,CURP,Correo,Teléfono,Municipio',
            'Ana Canul Poot,CAPA900101MZZNTN01,ana.canul@ejemplo-demo.mx,999 111 2233,Mérida',
            ',SINCURP,otro@ejemplo-demo.mx,9992223344,Valladolid',
            'Beto Chi Dzul,ESTONOESCURP12345,beto.chi@ejemplo-demo.mx,555,Tekax',
        ]);

        return UploadedFile::fake()->createWithContent('padron.csv', $contenido);
    }

    /* ---------------------------------------------------------------- *
     * Mapeo de columnas
     * ---------------------------------------------------------------- */

    public function test_propone_el_mapeo_reconociendo_los_encabezados(): void
    {
        $mapeo = app(ImportadorPadron::class)->proponerMapeo([
            'Nombre', 'CURP', 'Correo', 'Teléfono', 'Municipio', 'Columna Rara',
        ]);

        $this->assertSame('Nombre', $mapeo['nombre_completo']);
        $this->assertSame('CURP', $mapeo['curp']);
        $this->assertSame('Correo', $mapeo['email']);
        $this->assertSame('Teléfono', $mapeo['telefono']);
        $this->assertSame('Municipio', $mapeo['municipio']);
        // Un campo sin columna equivalente queda sin mapear, no adivinado.
        $this->assertNull($mapeo['rfc']);
    }

    /* ---------------------------------------------------------------- *
     * Validación fila por fila
     * ---------------------------------------------------------------- */

    public function test_valida_y_normaliza_una_fila_correcta(): void
    {
        $resultado = app(ImportadorPadron::class)->validarFila(
            ['Nombre' => 'Ana Canul Poot', 'CURP' => ' capa900101mzzntn01 ', 'Teléfono' => '(999) 111-2233'],
            ['nombre_completo' => 'Nombre', 'curp' => 'CURP', 'telefono' => 'Teléfono'],
            2
        );

        $this->assertTrue($resultado['valida']);
        // La CURP se normaliza a mayúsculas y sin espacios.
        $this->assertSame('CAPA900101MZZNTN01', $resultado['datos']['curp']);
        // El teléfono se queda solo con los dígitos.
        $this->assertSame('9991112233', $resultado['datos']['telefono']);
    }

    public function test_rechaza_una_fila_sin_nombre(): void
    {
        $resultado = app(ImportadorPadron::class)->validarFila(
            ['Nombre' => '', 'CURP' => 'CAPA900101MZZNTN01'],
            ['nombre_completo' => 'Nombre', 'curp' => 'CURP'],
            3
        );

        $this->assertFalse($resultado['valida']);
        $this->assertContains('Falta el nombre.', $resultado['errores']);
    }

    public function test_rechaza_una_curp_con_estructura_invalida(): void
    {
        $resultado = app(ImportadorPadron::class)->validarFila(
            ['Nombre' => 'Beto Chi', 'CURP' => 'ESTONOESCURP12345'],
            ['nombre_completo' => 'Nombre', 'curp' => 'CURP'],
            4
        );

        $this->assertFalse($resultado['valida']);
        $this->assertNotEmpty($resultado['errores']);
    }

    /* ---------------------------------------------------------------- *
     * Flujo completo: previsualizar y confirmar
     * ---------------------------------------------------------------- */

    public function test_la_previsualizacion_no_escribe_nada_en_el_padron(): void
    {
        $usuario = $this->usuarioCon('Admin Área');

        $this->actingAs($usuario)
            ->post(route('padron.importar.previsualizar'), ['archivo' => $this->archivoDePrueba()])
            ->assertRedirect()
            ->assertSessionHas('vistaPrevia');

        // El lote quedó registrado, pero el padrón sigue vacío.
        $this->assertSame(0, Persona::count());
        $this->assertDatabaseHas('padron_importaciones', [
            'archivo_original' => 'padron.csv',
            'estado' => 'previsualizada',
        ]);
    }

    public function test_la_previsualizacion_cuenta_validas_y_rechazadas(): void
    {
        $usuario = $this->usuarioCon('Admin Área');

        $vistaPrevia = $this->actingAs($usuario)
            ->post(route('padron.importar.previsualizar'), ['archivo' => $this->archivoDePrueba()])
            ->getSession()->get('vistaPrevia');

        $this->assertSame(3, $vistaPrevia['total_filas']);
        $this->assertSame(1, $vistaPrevia['validas']);
        $this->assertSame(2, $vistaPrevia['rechazadas']);
        $this->assertNotEmpty($vistaPrevia['errores_frecuentes']);
    }

    public function test_al_confirmar_solo_entran_las_filas_validas(): void
    {
        $usuario = $this->usuarioCon('Admin Área');

        $this->actingAs($usuario)
            ->post(route('padron.importar.previsualizar'), ['archivo' => $this->archivoDePrueba()]);

        $lote = PadronImportacion::latest()->first();

        $this->actingAs($usuario)
            ->post(route('padron.importar.confirmar', $lote), ['mapeo' => $lote->mapeo])
            ->assertRedirect(route('padron.importar.index'));

        $lote->refresh();

        $this->assertSame(1, $lote->filas_creadas);
        $this->assertSame(2, $lote->filas_rechazadas);
        $this->assertSame('confirmada', $lote->estado);

        $this->assertSame(1, Persona::count());
        $this->assertDatabaseHas('personas', [
            'curp' => 'CAPA900101MZZNTN01',
            'creado_por_modulo' => 'importacion',
        ]);
    }

    public function test_la_importacion_no_duplica_a_quien_ya_esta_en_el_padron(): void
    {
        $usuario = $this->usuarioCon('Admin Área');

        // Ana ya existe, con la misma CURP pero sin correo.
        $existente = Persona::create([
            'nombre_completo' => 'Ana Canul',
            'curp' => 'CAPA900101MZZNTN01',
            'municipio' => 'Mérida',
        ]);

        $this->actingAs($usuario)
            ->post(route('padron.importar.previsualizar'), ['archivo' => $this->archivoDePrueba()]);

        $lote = PadronImportacion::latest()->first();
        $this->actingAs($usuario)->post(route('padron.importar.confirmar', $lote), ['mapeo' => $lote->mapeo]);

        $lote->refresh();

        // No se creó a nadie: se completó la ficha existente.
        $this->assertSame(0, $lote->filas_creadas);
        $this->assertSame(1, $lote->filas_actualizadas);
        $this->assertSame(1, Persona::count());

        // El correo que faltaba se llenó; el nombre original no se pisó.
        $existente->refresh();
        $this->assertSame('ana.canul@ejemplo-demo.mx', $existente->email);
        $this->assertSame('Ana Canul', $existente->nombre_completo);
    }

    public function test_el_lote_genera_archivo_de_rechazos_descargable(): void
    {
        $usuario = $this->usuarioCon('Admin Área');

        $this->actingAs($usuario)
            ->post(route('padron.importar.previsualizar'), ['archivo' => $this->archivoDePrueba()]);

        $lote = PadronImportacion::latest()->first();
        $this->actingAs($usuario)->post(route('padron.importar.confirmar', $lote), ['mapeo' => $lote->mapeo]);

        $lote->refresh();

        $this->assertTrue($lote->tieneRechazos());
        Storage::assertExists($lote->ruta_rechazos);

        $contenido = Storage::get($lote->ruta_rechazos);
        $this->assertStringContainsString('_motivo', $contenido);
        $this->assertStringContainsString('Falta el nombre.', $contenido);

        $this->actingAs($usuario)
            ->get(route('padron.importar.rechazos', $lote))
            ->assertOk()
            ->assertDownload();
    }

    public function test_un_lote_ya_confirmado_no_se_procesa_dos_veces(): void
    {
        $usuario = $this->usuarioCon('Admin Área');

        $this->actingAs($usuario)
            ->post(route('padron.importar.previsualizar'), ['archivo' => $this->archivoDePrueba()]);

        $lote = PadronImportacion::latest()->first();
        $this->actingAs($usuario)->post(route('padron.importar.confirmar', $lote), ['mapeo' => $lote->mapeo]);

        $this->actingAs($usuario)
            ->post(route('padron.importar.confirmar', $lote->fresh()), ['mapeo' => $lote->mapeo])
            ->assertStatus(409);
    }

    /* ---------------------------------------------------------------- *
     * Permisos
     * ---------------------------------------------------------------- */

    public function test_sin_permiso_de_importar_no_se_entra(): void
    {
        $this->actingAs($this->usuarioCon('Supervisor'))
            ->get(route('padron.importar.index'))
            ->assertForbidden();
    }

    public function test_el_tester_no_puede_exportar_el_padron(): void
    {
        $this->actingAs($this->usuarioCon('Tester'))
            ->get(route('padron.exportar', ['formato' => 'csv']))
            ->assertForbidden();
    }

    /* ---------------------------------------------------------------- *
     * Exportación
     * ---------------------------------------------------------------- */

    public function test_la_exportacion_csv_respeta_los_filtros(): void
    {
        Persona::create(['nombre_completo' => 'Ana Canul Poot', 'municipio' => 'Mérida', 'estado_persona' => 'activa']);
        Persona::create(['nombre_completo' => 'Beto Chi Dzul', 'municipio' => 'Valladolid', 'estado_persona' => 'activa']);
        Persona::create(['nombre_completo' => 'Carmen Uc', 'municipio' => 'Mérida', 'estado_persona' => 'inactiva']);

        $contenido = $this->actingAs($this->usuarioCon('Super Admin'))
            ->get(route('padron.exportar', ['formato' => 'csv', 'municipio' => 'Mérida']))
            ->assertOk()
            ->streamedContent();

        $this->assertStringStartsWith("\xEF\xBB\xBF", $contenido);
        $this->assertStringContainsString('Ana Canul Poot', $contenido);
        $this->assertStringContainsString('Carmen Uc', $contenido);
        $this->assertStringNotContainsString('Beto Chi Dzul', $contenido);
    }

    public function test_un_formato_no_soportado_se_rechaza(): void
    {
        $this->actingAs($this->usuarioCon('Super Admin'))
            ->get(route('padron.exportar', ['formato' => 'pdf']))
            ->assertStatus(422);
    }
}
