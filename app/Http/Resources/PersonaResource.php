<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Representación de una persona en la API del padrón.
 *
 * Los campos sensibles salen del modelo, no de `$this->resource->attributes`,
 * para que el enmascarado del rol Tester siga aplicando también aquí.
 */
class PersonaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'nombre_completo' => $this->nombre_completo,
            'email' => $this->email,
            'telefono' => $this->telefono,
            'telefono_secundario' => $this->telefono_secundario,

            'curp' => $this->curp,
            'rfc' => $this->rfc,
            'ine_clave' => $this->ine_clave,

            'domicilio' => [
                'calle' => $this->calle,
                'calle_2' => $this->calle_2,
                'codigo_postal' => $this->codigo_postal,
                'localidad' => $this->localidad,
                'ciudad' => $this->ciudad,
                'municipio' => $this->municipio,
                'estado' => $this->estado,
                'pais' => $this->pais,
                'latitud' => $this->latitud,
                'longitud' => $this->longitud,
            ],

            'demograficos' => [
                'fecha_nacimiento' => $this->fecha_nacimiento?->toDateString(),
                'edad' => $this->edad,
                'sexo' => $this->sexo,
                'nivel_educativo' => $this->nivel_educativo,
                'habla_maya' => $this->habla_maya,
            ],

            'tipo_persona' => $this->tipo_persona,
            'estado_persona' => $this->estado_persona,
            'idioma' => $this->idioma,
            'medio_ingreso' => $this->medio_ingreso,
            'creado_por_modulo' => $this->creado_por_modulo,
            'demo' => $this->demo,

            'etiquetas' => $this->whenLoaded(
                'etiquetas',
                fn () => $this->etiquetas->pluck('etiqueta')->values()
            ),

            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
