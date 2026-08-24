<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UpdatePasswordTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Contraseña que cumple la política del hub: 10 caracteres o más, con
     * mayúscula, minúscula y número. La original de Jetstream
     * (`new-password`) dejó de servir al endurecer las reglas en
     * `PasswordValidationRules`.
     */
    private const NUEVA_CONTRASENA = 'IyemYucatan2026';

    public function test_password_can_be_updated(): void
    {
        $this->actingAs($user = User::factory()->create());

        $this->put('/user/password', [
            'current_password' => 'password',
            'password' => self::NUEVA_CONTRASENA,
            'password_confirmation' => self::NUEVA_CONTRASENA,
        ]);

        $this->assertTrue(Hash::check(self::NUEVA_CONTRASENA, $user->fresh()->password));
    }

    public function test_current_password_must_be_correct(): void
    {
        $this->actingAs($user = User::factory()->create());

        $response = $this->put('/user/password', [
            'current_password' => 'wrong-password',
            'password' => self::NUEVA_CONTRASENA,
            'password_confirmation' => self::NUEVA_CONTRASENA,
        ]);

        $response->assertSessionHasErrors();

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_new_passwords_must_match(): void
    {
        $this->actingAs($user = User::factory()->create());

        $response = $this->put('/user/password', [
            'current_password' => 'password',
            'password' => self::NUEVA_CONTRASENA,
            'password_confirmation' => 'OtraDistinta2026',
        ]);

        $response->assertSessionHasErrors();

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }
}
