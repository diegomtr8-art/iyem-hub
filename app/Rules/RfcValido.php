<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class RfcValido implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Convertimos a mayúsculas
        $rfc = strtoupper((string) $value);

        // Patrón para RFC (12 o 13 caracteres)
        $patron = '/^([A-ZÑ&]{3,4})\d{6}([A-Z\d]{3})$/';

        if (!preg_match($patron, $rfc)) {
            $fail('El RFC no tiene una estructura válida (debe tener 12 caracteres para personas morales o 13 para personas físicas).');
        }
    }
}
