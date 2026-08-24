<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
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

    public function test_el_dashboard_carga_para_un_super_admin(): void
    {
        $this->actingAs($this->usuarioCon('Super Admin'))
            ->get(route('dashboard'))
            ->assertOk();
    }

    public function test_cada_rol_solo_ve_los_modulos_que_su_permiso_alcanza(): void
    {
        $esperado = [
            'Super Admin' => 13,
            'Admin Área' => 11,
            'Supervisor' => 6,
            'Operario' => 2,
            'Tester' => 13,
        ];

        foreach ($esperado as $rol => $total) {
            $respuesta = $this->actingAs($this->usuarioCon($rol))->get(route('dashboard'));

            $respuesta->assertOk();
            $modulos = $respuesta->viewData('page')['props']['modulos'];

            $this->assertCount($total, $modulos, "El rol {$rol} debería ver {$total} módulos.");
        }
    }

    public function test_la_bitacora_de_toda_la_plataforma_solo_la_ve_el_super_admin(): void
    {
        $props = $this->actingAs($this->usuarioCon('Super Admin'))
            ->get(route('dashboard'))->viewData('page')['props'];
        $this->assertIsArray($props['actividadesPlataforma']);

        $props = $this->actingAs($this->usuarioCon('Operario'))
            ->get(route('dashboard'))->viewData('page')['props'];
        $this->assertNull($props['actividadesPlataforma']);
    }

    public function test_un_modulo_en_desarrollo_no_se_puede_abrir(): void
    {
        // `bitacora` sigue en desarrollo: se muestra en el dashboard con su
        // badge, pero no lleva a ninguna parte.
        $this->actingAs($this->usuarioCon('Super Admin'))
            ->get(route('dashboard.acceder', 'bitacora'))
            ->assertNotFound();
    }

    public function test_un_modulo_planeado_tampoco_se_puede_abrir(): void
    {
        $this->actingAs($this->usuarioCon('Super Admin'))
            ->get(route('dashboard.acceder', 'tienda'))
            ->assertNotFound();
    }

    public function test_abrir_un_modulo_en_produccion_deja_registro_en_la_bitacora(): void
    {
        $usuario = $this->usuarioCon('Super Admin');

        $this->actingAs($usuario)->get(route('dashboard.acceder', 'crea'))->assertRedirect();

        $this->assertDatabaseHas('accesos', [
            'user_id' => $usuario->id,
            'modulo' => 'crea',
        ]);
    }
}
