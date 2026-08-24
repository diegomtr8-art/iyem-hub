<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PersonaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $verDatosSensibles = $request->user()?->esSuperAdmin() ?? false;

        return [
            'id' => $this->id,
            'nombre_completo' => $this->nombre_completo,
            'email' => $this->email,
            'telefono' => $this->telefono,
            'telefono_secundario' => $this->telefono_secundario,

            'curp' => $verDatosSensibles ? $this->curp : null,
            'rfc' => $verDatosSensibles ? $this->rfc : null,
            'ine_clave' => $verDatosSensibles ? $this->ine_clave : null,

            'calle' => $this->calle,
            'calle_2' => $this->calle_2,
            'codigo_postal' => $this->codigo_postal,
            'ciudad' => $this->ciudad,
            'municipio' => $this->municipio,
            'localidad' => $this->localidad,
            'latitud' => $this->latitud,
            'longitud' => $this->longitud,
            'estado' => $this->estado,
            'pais' => $this->pais,

            'fecha_nacimiento' => $this->fecha_nacimiento?->toDateString(),
            'edad' => $this->edad,
            'sexo' => $this->sexo,

            'nivel_educativo' => $this->nivel_educativo,
            'habla_maya' => $this->habla_maya,

            'facebook_negocio' => $this->facebook_negocio,
            'instagram_negocio' => $this->instagram_negocio,
            'tiktok_negocio' => $this->tiktok_negocio,
            'sitio_web' => $this->sitio_web,

            'idioma' => $this->idioma,
            'medio_ingreso' => $this->medio_ingreso,

            'tipo_persona' => $this->tipo_persona,
            'estado_persona' => $this->estado_persona,

            'creado_por_modulo' => $this->creado_por_modulo,
            'etiquetas' => $this->whenLoaded('etiquetas', fn () => $this->etiquetas->pluck('etiqueta')),

            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
