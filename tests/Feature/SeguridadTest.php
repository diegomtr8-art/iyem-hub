<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class SeguridadTest extends TestCase
{
    use RefreshDatabase;

    public function test_las_cabeceras_de_seguridad_viajan_en_la_respuesta(): void
    {
        $respuesta = $this->get(route('login'))->assertOk();

        $respuesta->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $respuesta->assertHeader('X-Content-Type-Options', 'nosniff');
        $respuesta->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $this->assertStringContainsString('camera=()', $respuesta->headers->get('Permissions-Policy'));
    }

    public function test_la_api_tambien_lleva_las_cabeceras(): void
    {
        $this->getJson('/api/v1/salud')
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_sin_https_no_se_manda_hsts(): void
    {
        // Mandar HSTS desde el XAMPP local dejaría al navegador forzando
        // https://localhost durante un año.
        $this->assertNull(
            $this->get(route('login'))->headers->get('Strict-Transport-Security')
        );
    }

    /* ---------------------------------------------------------------- *
     * Contraseñas
     * ---------------------------------------------------------------- */

    public function test_la_contrasena_exige_diez_caracteres_con_mayuscula_y_numero(): void
    {
        $usuario = User::factory()->create(['estado' => true]);

        $debiles = [
            'corta1A',          // menos de 10
            'todominuscula1',   // sin mayúscula
            'TODOMAYUSCULA1',   // sin minúscula
            'SinNumerosAqui',   // sin número
        ];

        foreach ($debiles as $contrasena) {
            $this->actingAs($usuario)
                ->put(route('user-password.update'), [
                    'current_password' => 'password',
                    'password' => $contrasena,
                    'password_confirmation' => $contrasena,
                ])
                ->assertSessionHasErrors('password', null, 'updatePassword');
        }
    }

    public function test_una_contrasena_que_cumple_se_acepta(): void
    {
        $usuario = User::factory()->create(['estado' => true]);

        $this->actingAs($usuario)
            ->put(route('user-password.update'), [
                'current_password' => 'password',
                'password' => 'IyemYucatan2026',
                'password_confirmation' => 'IyemYucatan2026',
            ])
            ->assertSessionHasNoErrors();
    }

    /* ---------------------------------------------------------------- *
     * Límite de intentos de inicio de sesión
     * ---------------------------------------------------------------- */

    public function test_el_inicio_de_sesion_tiene_limite_de_intentos(): void
    {
        RateLimiter::clear('login');

        $usuario = User::factory()->create(['estado' => true, 'email' => 'alguien@iyemyucatan.com']);

        // Fortify permite 5 intentos cada 15 minutos por correo e IP.
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login'), [
                'email' => $usuario->email,
                'password' => 'contrasena-equivocada',
            ])->assertSessionHasErrors('email');
        }

        // El sexto ya no llega al validador: lo frena el limitador con un
        // 429, que es lo correcto —no hay que gastar un `Hash::check` en un
        // intento que de todas formas se va a rechazar.
        $this->post(route('login'), [
            'email' => $usuario->email,
            'password' => 'contrasena-equivocada',
        ])->assertStatus(429);
    }
}
