<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/**
 * Cuenta de pruebas para servicio social y demostraciones.
 *
 * Corre en cualquier entorno, incluido producción, porque el punto de esta
 * cuenta es poder enseñar la plataforma sin abrir datos reales. Lo que sí
 * cambia en producción es que queda registrada en el log y se le desactiva
 * la autenticación de dos factores, que en una cuenta compartida solo
 * estorbaría.
 */
class TesterSeeder extends Seeder
{
    public const CORREO = 'tester@iyemyucatan.com';

    public const DIAS_DE_VIGENCIA = 90;

    public function run(): void
    {
        $tester = User::updateOrCreate(
            ['email' => self::CORREO],
            [
                'name' => 'Usuario',
                'apellido' => 'Tester',
                // Contraseña fija y conocida a propósito: es una cuenta de
                // demostración compartida, no la cuenta de una persona.
                'password' => Hash::make('1234567123'),
                'estado' => true,
                'email_verified_at' => now(),
                'expira_at' => now()->addDays(self::DIAS_DE_VIGENCIA),
            ]
        );

        $tester->syncRoles('Tester');

        if (app()->environment('production')) {
            $tester->forceFill([
                'two_factor_secret' => null,
                'two_factor_recovery_codes' => null,
                'two_factor_confirmed_at' => null,
            ])->save();

            Log::warning('Se creó o actualizó la cuenta de pruebas en producción.', [
                'correo' => self::CORREO,
                'expira_at' => $tester->expira_at?->toDateTimeString(),
            ]);

            $this->command?->warn(
                'ATENCIÓN: la cuenta de pruebas quedó activa en PRODUCCIÓN y vence el '
                .$tester->expira_at?->toDateString().'.'
            );
        }

        $this->command?->info(
            'Cuenta de pruebas lista -> '.self::CORREO.' (vigencia: '
            .$tester->expira_at?->toDateString().')'
        );
    }
}
