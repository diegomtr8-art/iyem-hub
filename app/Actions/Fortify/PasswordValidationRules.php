<?php

namespace App\Actions\Fortify;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Validation\Rules\Password;

trait PasswordValidationRules
{
    /**
     * Reglas de contraseña de la plataforma.
     *
     * Mínimo 10 caracteres, con mayúscula, minúscula y número. Diez y no
     * ocho porque estas cuentas abren el padrón completo del instituto:
     * nombres, CURP, domicilios y teléfonos de gente real.
     *
     * No se exige símbolo a propósito. En una institución donde parte del
     * personal captura desde el celular, obligar a un símbolo empuja a la
     * contraseña escrita en un papel pegado al monitor, que es peor que dos
     * caracteres menos de entropía.
     *
     * EXCEPCIÓN DOCUMENTADA — cuenta de pruebas
     *
     * `tester@iyemyucatan.com` usa la contraseña fija `1234567123`, que no
     * cumple ninguna de estas reglas. Es deliberado: es una cuenta compartida
     * de demostración, no la cuenta de una persona. Se sostiene porque:
     *
     *   - Solo alcanza personas ficticias (`personas.demo = true`).
     *   - Ve los campos sensibles enmascarados.
     *   - No escribe, no exporta y no entra al panel de administración.
     *   - Caduca a los 90 días (`users.expira_at`).
     *
     * La contraseña se fija en `TesterSeeder` con `Hash::make()`, que no pasa
     * por estas reglas. Si alguna vez se cambia desde el formulario de
     * perfil, sí tendrá que cumplirlas.
     *
     * @return array<int, Rule|array<mixed>|string>
     */
    protected function passwordRules(): array
    {
        return [
            'required',
            'string',
            Password::min(10)->letters()->mixedCase()->numbers(),
            'confirmed',
        ];
    }
}
