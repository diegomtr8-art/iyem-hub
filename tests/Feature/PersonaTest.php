<?php

namespace Tests\Feature;

use App\Models\Modulos\CreaSolicitud;
use App\Models\Persona;
use Database\Seeders\PersonaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PersonaTest extends TestCase
{
    use RefreshDatabase;

    public function test_puede_crear_persona_con_todos_los_campos(): void
    {
        $persona = Persona::create([
            'nombre_completo' => 'Juana Pérez Dzul',
            'email' => 'juana.perez@ejemplo.com',
            'telefono' => '9991234567',
            'curp' => 'PEDJ900101MYUXXX01',
            'rfc' => 'PEDJ900101AB1',
            'municipio' => 'Mérida',
            'estado' => 'Yucatán',
            'fecha_nacimiento' => '1990-01-01',
            'sexo' => 'F',
            'habla_maya' => true,
            'tipo_persona' => 'fisica',
            'estado_persona' => 'activa',
            'creado_por_modulo' => 'padron',
        ]);

        $this->assertDatabaseHas('personas', [
            'id' => $persona->id,
            'nombre_completo' => 'Juana Pérez Dzul',
            'curp' => 'PEDJ900101MYUXXX01',
        ]);
        $this->assertNotNull($persona->edad);
    }

    public function test_persona_se_relaciona_con_modulos(): void
    {
        $persona = Persona::create([
            'nombre_completo' => 'Carlos Ek',
            'creado_por_modulo' => 'crea',
        ]);

        $persona->creaSolicitudes()->create([
            'monto_solicitado' => 15000,
            'tipo_credito' => 'emprendimiento',
            'estado_solicitud' => 'enviada',
            'fecha_solicitud' => now(),
        ]);

        $this->assertCount(1, $persona->fresh()->creaSolicitudes);
        $this->assertInstanceOf(CreaSolicitud::class, $persona->creaSolicitudes->first());
    }

    public function test_soft_delete_no_elimina_el_registro_fisicamente(): void
    {
        $persona = Persona::create(['nombre_completo' => 'Rosa Chan']);

        $persona->delete();

        $this->assertSoftDeleted('personas', ['id' => $persona->id]);
        $this->assertDatabaseHas('personas', ['id' => $persona->id]);
    }

    public function test_los_cambios_quedan_registrados_en_auditoria(): void
    {
        $persona = Persona::create(['nombre_completo' => 'Marco Uc']);

        $persona->update(['nombre_completo' => 'Marco Uc Actualizado']);

        $this->assertDatabaseHas('personas_auditorias', [
            'persona_id' => $persona->id,
            'campo_modificado' => 'nombre_completo',
            'valor_nuevo' => 'Marco Uc Actualizado',
        ]);
    }

    public function test_puede_etiquetar_y_desetiquetar_una_persona(): void
    {
        $persona = Persona::create(['nombre_completo' => 'Lucía Balam']);

        $persona->agregarEtiqueta('emprendedor');
        $this->assertCount(1, $persona->etiquetas);

        $persona->removerEtiqueta('emprendedor');
        $this->assertCount(0, $persona->fresh()->etiquetas);
    }

    public function test_seeder_genera_al_menos_diez_personas(): void
    {
        (new PersonaSeeder())->run();

        $this->assertGreaterThanOrEqual(10, Persona::count());
    }
}
