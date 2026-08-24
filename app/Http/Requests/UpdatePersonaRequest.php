<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdatePersonaRequest extends StorePersonaRequest
{
    public function rules(): array
    {
        $persona = $this->route('persona');

        $rules = parent::rules();
        $rules['nombre_completo'] = ['sometimes', 'string', 'max:255'];
        $rules['email'] = ['nullable', 'email', 'max:255', Rule::unique('personas', 'email')->ignore($persona)];
        $rules['curp'] = [
            'nullable', 'string', 'size:18', Rule::unique('personas', 'curp')->ignore($persona),
            'regex:/^[A-Z]{4}\d{6}[HM][A-Z]{5}[0-9A-Z]\d$/',
        ];

        return $rules;
    }
}
