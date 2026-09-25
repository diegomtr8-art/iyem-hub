<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class CurpValida implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Convertimos a mayúsculas para evitar fallos por minúsculas
        $curp = strtoupper((string) $value);

        // Patrón para CURP de 18 caracteres
        $patron = '/^[A-Z]{4}\d{6}[HM][A-Z]{5}[0-9A-Z]\d$/';

        if (!preg_match($patron, $curp)) {
            $fail('La CURP debe tener 18 caracteres válidos. Revisa que la fecha de nacimiento esté en formato AAMMDD y contenga la estructura correcta.');
        }
    }
}
