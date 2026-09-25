<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class TelefonoMexicano implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Limpiamos la cadena quitando espacios, guiones y paréntesis
        $soloNumeros = preg_replace('/[^\d]/', '', (string) $value);

        // Verificamos que tenga exactamente 10 dígitos
        if (strlen($soloNumeros) !== 10) {
            $fail('El teléfono debe contener exactamente 10 dígitos después de omitir espacios, guiones o paréntesis.');
        }
    }
}
