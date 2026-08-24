<?php

namespace App\Http\Controllers;

use App\Models\Persona;
use App\Models\PersonaFusion;
use App\Services\DetectorDuplicados;
use App\Services\FusionadorPersonas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Revisión y fusión de personas duplicadas.
 *
 * Solo el Super Admin llega aquí. Fusionar dos expedientes junta bajo una
 * identidad los trámites de módulos distintos; si se hace mal, se mezcla el
 * historial de dos personas reales.
 */
class PadronDuplicadosController extends Controller
{
    public function __construct(
        private readonly DetectorDuplicados $detector,
        private readonly FusionadorPersonas $fusionador,
    ) {}

    public function index(Request $request): Response
    {
        $incluirSimilitud = ! $request->boolean('sin_similitud');

        $grupos = $this->detector->detectar($incluirSimilitud);
        $resumen = $this->detector->resumen($grupos);

        return Inertia::render('Padron/Duplicados', [
            'grupos' => $grupos->take(100)->map(fn (array $grupo) => [
                'criterio' => $grupo['criterio'],
                'etiqueta' => $grupo['etiqueta'],
                'confianza' => $grupo['confianza'],
                'valor' => $grupo['valor'],
                'personas' => $grupo['personas']->map(fn (Persona $persona) => [
                    'id' => $persona->id,
                    'nombre_completo' => $persona->nombre_completo,
                    'curp' => $persona->curp,
                    'rfc' => $persona->rfc,
                    'email' => $persona->email,
                    'telefono' => $persona->telefono,
                    'municipio' => $persona->municipio,
                    'estado_persona' => $persona->estado_persona,
                    'creado_por_modulo' => $persona->creado_por_modulo,
                    'alta' => $persona->created_at?->toIso8601String(),
                    'url' => route('padron.show', $persona->id),
                ])->values(),
            ])->values(),
            'resumen' => $resumen,
            'truncado' => $grupos->count() > 100,
            'incluyeSimilitud' => $incluirSimilitud,
            'umbralSimilitud' => DetectorDuplicados::SIMILITUD_MINIMA,
            'diasParaRevertir' => FusionadorPersonas::DIAS_PARA_REVERTIR,
            'fusionesRecientes' => $this->fusionesRecientes(),
        ]);
    }

    public function fusionar(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'principal_id' => ['required', 'integer', 'exists:personas,id'],
            'duplicada_id' => ['required', 'integer', 'exists:personas,id', 'different:principal_id'],
            'criterio' => ['nullable', 'string', 'max:60'],
            'motivo' => ['nullable', 'string', 'max:500'],
        ], [
            'duplicada_id.different' => 'No se puede fusionar una persona consigo misma.',
        ]);

        $principal = Persona::sinAislamientoDemo()->findOrFail($datos['principal_id']);
        $duplicada = Persona::sinAislamientoDemo()->findOrFail($datos['duplicada_id']);

        $fusion = $this->fusionador->fusionar(
            principal: $principal,
            duplicada: $duplicada,
            usuario: $request->user(),
            criterio: $datos['criterio'] ?? null,
            motivo: $datos['motivo'] ?? null,
        );

        return back()->with('flash', [
            'success' => "Se fusionó «{$duplicada->nombre_completo}» dentro de «{$principal->nombre_completo}». ".
                'Puedes deshacerlo hasta el '.$fusion->revertible_hasta->translatedFormat('d \d\e F \d\e Y').'.',
        ]);
    }

    public function revertir(Request $request, PersonaFusion $fusion): RedirectResponse
    {
        try {
            $this->fusionador->revertir($fusion, $request->user());
        } catch (\RuntimeException $error) {
            return back()->with('flash', ['error' => $error->getMessage()]);
        }

        return back()->with('flash', ['success' => 'La fusión se deshizo. Ambas fichas volvieron a su estado anterior.']);
    }

    private function fusionesRecientes()
    {
        return PersonaFusion::query()
            ->with(['principal:id,nombre_completo', 'usuario:id,name,apellido'])
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn (PersonaFusion $fusion) => [
                'id' => $fusion->id,
                'principal' => $fusion->principal?->nombre_completo ?? "Persona #{$fusion->principal_id}",
                'principal_id' => $fusion->principal_id,
                'duplicada' => $fusion->snapshot_duplicada['nombre_completo'] ?? "Persona #{$fusion->duplicada_id}",
                'criterio' => $fusion->criterio,
                'motivo' => $fusion->motivo,
                'usuario' => $fusion->usuario
                    ? trim("{$fusion->usuario->name} {$fusion->usuario->apellido}")
                    : 'Usuario eliminado',
                'fecha' => $fusion->created_at?->toIso8601String(),
                'revertible_hasta' => $fusion->revertible_hasta->toIso8601String(),
                'revertida_at' => $fusion->revertida_at?->toIso8601String(),
                'es_revertible' => $fusion->esRevertible(),
                'vinculos_movidos' => collect($fusion->vinculos_movidos ?? [])
                    ->map(fn (array $ids) => count($ids))
                    ->filter()
                    ->all(),
            ]);
    }
}
