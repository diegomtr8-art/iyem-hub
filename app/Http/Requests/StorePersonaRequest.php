<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePersonaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre_completo' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', 'unique:personas,email'],
            'telefono' => ['nullable', 'string', 'regex:/^\d{10,20}$/'],
            'telefono_secundario' => ['nullable', 'string', 'regex:/^\d{10,20}$/'],

            'curp' => [
                'nullable', 'string', 'size:18', 'unique:personas,curp',
                'regex:/^[A-Z]{4}\d{6}[HM][A-Z]{5}[0-9A-Z]\d$/',
            ],
            'rfc' => ['nullable', 'string', 'min:12', 'max:13', 'regex:/^[A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3}$/'],
            'ine_clave' => ['nullable', 'string', 'max:20'],

            'calle' => ['nullable', 'string', 'max:255'],
            'calle_2' => ['nullable', 'string', 'max:255'],
            'codigo_postal' => ['nullable', 'string', 'max:10'],
            'ciudad' => ['nullable', 'string', 'max:100'],
            'municipio' => ['nullable', 'string', 'max:100'],
            'localidad' => ['nullable', 'string', 'max:100'],
            'latitud' => ['nullable', 'numeric', 'between:19.4,21.9'],
            'longitud' => ['nullable', 'numeric', 'between:-90.5,-87.4'],
            'estado' => ['nullable', 'string', 'max:100'],
            'pais' => ['nullable', 'string', 'max:100'],

            'fecha_nacimiento' => ['nullable', 'date', 'before_or_equal:today'],
            'sexo' => ['nullable', Rule::in(['M', 'F', 'Otro'])],

            'nivel_educativo' => ['nullable', 'string', 'max:100'],
            'habla_maya' => ['nullable', 'boolean'],

            'facebook_negocio' => ['nullable', 'string', 'max:255'],
            'instagram_negocio' => ['nullable', 'string', 'max:255'],
            'tiktok_negocio' => ['nullable', 'string', 'max:255'],
            'sitio_web' => ['nullable', 'string', 'max:255'],

            'idioma' => ['nullable', 'string', 'max:50'],
            'medio_ingreso' => ['nullable', 'string', 'max:100'],

            'tipo_persona' => ['nullable', Rule::in(['fisica', 'moral'])],
            'estado_persona' => ['nullable', Rule::in(['activa', 'inactiva', 'bloqueada'])],

            'creado_por_modulo' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'curp.regex' => 'El CURP no tiene un formato válido (18 caracteres).',
            'rfc.regex' => 'El RFC no tiene un formato válido (12-13 caracteres).',
            'telefono.regex' => 'El teléfono debe tener al menos 10 dígitos.',
            'fecha_nacimiento.before_or_equal' => 'La fecha de nacimiento no puede ser futura.',
        ];
    }
}
