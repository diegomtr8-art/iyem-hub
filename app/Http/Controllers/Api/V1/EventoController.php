<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\EventoModulo;
use App\Models\Persona;
use App\Models\SistemaIntegrado;
use App\Services\ResolvedorPersonas;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Recibe los hechos que los módulos reportan sobre una persona.
 *
 * El hub no valida la lógica de negocio del módulo que reporta: si CREA
 * dice que aprobó una solicitud, el hub lo anota. Su trabajo es tener la
 * historia completa, no auditar decisiones ajenas.
 */
class EventoController extends Controller
{
    public function __construct(private readonly ResolvedorPersonas $resolvedor) {}

    public function store(Request $request): JsonResponse
    {
        $datos = $request->validate([
            // O se manda el persona_id que el módulo ya guarda, o se mandan
            // los datos para resolverlo aquí mismo.
            'persona_id' => ['required_without:persona', 'nullable', 'integer', 'exists:personas,id'],
            'persona' => ['required_without:persona_id', 'nullable', 'array'],
            'persona.curp' => ['nullable', 'string', 'size:18'],
            'persona.rfc' => ['nullable', 'string', 'between:12,13'],
            'persona.email' => ['nullable', 'email:rfc'],
            'persona.telefono' => ['nullable', 'string', 'max:20'],
            'persona.nombre' => ['nullable', 'string', 'max:255'],

            'tipo' => ['required', 'string', 'max:60'],
            'titulo' => ['required', 'string', 'max:255'],
            'detalle' => ['nullable', 'string', 'max:2000'],
            'estado' => ['nullable', 'string', 'max:60'],
            'referencia_externa' => ['nullable', 'string', 'max:120'],
            'ocurrio_at' => ['nullable', 'date'],
            'carga' => ['nullable', 'array'],
        ]);

        $sistema = $request->user() instanceof SistemaIntegrado ? $request->user() : null;
        $modulo = $sistema?->slug ?? 'api';

        $persona = isset($datos['persona_id'])
            ? Persona::withoutGlobalScope('aislamiento_demo')->findOrFail($datos['persona_id'])
            : $this->resolvedor->resolver($datos['persona'], $modulo)['persona'];

        $atributos = [
            'persona_id' => $persona->id,
            'modulo' => $modulo,
            'tipo' => $datos['tipo'],
            'titulo' => $datos['titulo'],
            'detalle' => $datos['detalle'] ?? null,
            'estado' => $datos['estado'] ?? null,
            'carga' => $datos['carga'] ?? null,
            'ocurrio_at' => $datos['ocurrio_at'] ?? now(),
            'sistema_id' => $sistema?->id,
        ];

        /*
         * Idempotencia por referencia externa: si el módulo reintenta el
         * envío tras un timeout, el evento se actualiza en lugar de
         * aparecer dos veces en la línea de tiempo.
         */
        if (! empty($datos['referencia_externa'])) {
            $evento = EventoModulo::updateOrCreate(
                ['modulo' => $modulo, 'referencia_externa' => $datos['referencia_externa']],
                $atributos
            );

            $creado = $evento->wasRecentlyCreated;
        } else {
            $evento = EventoModulo::create($atributos);
            $creado = true;
        }

        return response()->json([
            'data' => [
                'evento_id' => $evento->id,
                'persona_id' => $persona->id,
                'modulo' => $modulo,
                'ocurrio_at' => $evento->ocurrio_at->toIso8601String(),
            ],
            'meta' => ['creado' => $creado],
        ], $creado ? 201 : 200);
    }
}
