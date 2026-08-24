<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PersonaResource;
use App\Models\Persona;
use App\Models\SistemaIntegrado;
use App\Services\FichaPersona;
use App\Services\ResolvedorPersonas;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * API del Padrón Central, versión 1.
 *
 * La consumen los sistemas satélite del IYEM para resolver la identidad de
 * una persona sin migrar su base de datos. Ver `docs/API_PADRON.md`.
 */
class PersonaController extends Controller
{
    public function __construct(
        private readonly ResolvedorPersonas $resolvedor,
        private readonly FichaPersona $ficha,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $filtros = $request->validate([
            'municipio' => ['nullable', 'string', 'max:120'],
            'estado_persona' => ['nullable', Rule::in(['activa', 'inactiva', 'bloqueada'])],
            'etiqueta' => ['nullable', 'string', 'max:60'],
            'modulo_origen' => ['nullable', 'string', 'max:60'],
            'actualizadas_desde' => ['nullable', 'date'],
            'por_pagina' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $personas = Persona::query()
            ->when($filtros['municipio'] ?? null, fn ($q, $v) => $q->porMunicipio($v))
            ->when($filtros['estado_persona'] ?? null, fn ($q, $v) => $q->where('estado_persona', $v))
            ->when($filtros['etiqueta'] ?? null, fn ($q, $v) => $q->porEtiqueta($v))
            ->when($filtros['modulo_origen'] ?? null, fn ($q, $v) => $q->porModulo($v))
            // Permite a un módulo sincronizar solo lo que cambió desde su
            // última corrida, en vez de recorrer el padrón entero.
            ->when($filtros['actualizadas_desde'] ?? null, fn ($q, $v) => $q->where('updated_at', '>=', $v))
            ->with('etiquetas')
            ->orderBy('id')
            ->paginate($filtros['por_pagina'] ?? 25)
            ->withQueryString();

        return PersonaResource::collection($personas);
    }

    /**
     * Búsqueda libre por CURP, RFC, correo, teléfono o nombre.
     *
     * Pensada para el buscador de un módulo, no para sincronizar: para eso
     * está `index` con `actualizadas_desde`.
     */
    public function buscar(Request $request): AnonymousResourceCollection
    {
        $datos = $request->validate([
            'q' => ['required', 'string', 'min:3', 'max:120'],
            'por_pagina' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $termino = trim($datos['q']);
        $soloDigitos = preg_replace('/\D+/', '', $termino);

        $personas = Persona::query()
            ->where(function ($query) use ($termino, $soloDigitos) {
                $query->where('nombre_completo', 'like', "%{$termino}%")
                    ->orWhere('email', 'like', "%{$termino}%")
                    ->orWhere('curp', 'like', Str::upper($termino).'%')
                    ->orWhere('rfc', 'like', Str::upper($termino).'%');

                if ($soloDigitos !== '') {
                    $query->orWhere('telefono', 'like', "%{$soloDigitos}%")
                        ->orWhere('telefono_secundario', 'like', "%{$soloDigitos}%");
                }
            })
            ->with('etiquetas')
            ->orderBy('nombre_completo')
            ->paginate($datos['por_pagina'] ?? 20)
            ->withQueryString();

        return PersonaResource::collection($personas);
    }

    public function show(Persona $persona): PersonaResource
    {
        return PersonaResource::make($persona->load('etiquetas'));
    }

    /**
     * Todo lo que la persona tiene en los demás módulos.
     */
    public function vinculos(Persona $persona): JsonResponse
    {
        $persona->load([
            'creaSolicitudes', 'impulstateInscripciones', 'nodicoMembresias',
            'herenciaVivaClientes', 'juridicoAsesorias', 'citasAgendamientos',
        ]);

        return response()->json([
            'data' => [
                'persona_id' => $persona->id,
                'vinculos' => $this->ficha->vinculos($persona),
                'linea_de_tiempo' => $this->ficha->lineaDeTiempo($persona),
            ],
        ]);
    }

    /**
     * Alta de persona, idempotente por CURP.
     *
     * Si ya existe alguien con esa CURP se devuelve el registro existente
     * con 200 en lugar de 201, para que un reintento del módulo no acabe
     * duplicando a la persona.
     */
    public function store(Request $request): JsonResponse
    {
        /*
         * La CURP se valida en forma pero no en unicidad: si ya existe,
         * este endpoint devuelve a la persona existente en vez de fallar.
         * Con la regla `unique` puesta, un reintento del módulo recibiría
         * un 422 en lugar del `persona_id` que vino a buscar.
         */
        $datos = $request->validate($this->reglas(curpIdempotente: true));

        if (! empty($datos['curp'])) {
            $existente = Persona::withoutGlobalScope('aislamiento_demo')
                ->where('curp', Str::upper($datos['curp']))
                ->first();

            if ($existente) {
                return PersonaResource::make($existente->load('etiquetas'))
                    ->additional(['meta' => ['creada' => false, 'motivo' => 'ya_existia_esa_curp']])
                    ->response()
                    ->setStatusCode(200);
            }
        }

        $persona = Persona::create([
            ...$datos,
            'creado_por_modulo' => $datos['creado_por_modulo'] ?? $this->sistema($request)?->slug ?? 'api',
        ]);

        return PersonaResource::make($persona)
            ->additional(['meta' => ['creada' => true]])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Actualización parcial. Cada campo modificado queda en
     * `personas_auditorias` con el módulo que lo cambió.
     */
    public function update(Request $request, Persona $persona): PersonaResource
    {
        $datos = $request->validate($this->reglas(parcial: true, personaId: $persona->id));

        $modulo = $this->sistema($request)?->slug ?? 'api';

        foreach ($datos as $campo => $valorNuevo) {
            $valorAnterior = $persona->getRawOriginal($campo);

            if ((string) $valorAnterior === (string) $valorNuevo) {
                continue;
            }

            $persona->marcarAuditoria(
                campo: $campo,
                valor_anterior: $valorAnterior === null ? null : (string) $valorAnterior,
                valor_nuevo: $valorNuevo === null ? null : (string) $valorNuevo,
                usuario_id: null,
                modulo: $modulo
            );
        }

        $persona->update($datos);

        return PersonaResource::make($persona->fresh()->load('etiquetas'));
    }

    /**
     * Devuelve el `persona_id` que corresponde a los datos recibidos, y lo
     * crea si nadie coincide. Es el endpoint que evita que cada módulo
     * fabrique su propia copia de la misma persona.
     */
    public function resolver(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'curp' => ['nullable', 'string', 'size:18', 'regex:'.config('padron.reglas.curp')],
            'rfc' => ['nullable', 'string', 'between:12,13', 'regex:'.config('padron.reglas.rfc')],
            'email' => ['nullable', 'email:rfc', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'nombre' => ['required_without_all:curp,rfc,email', 'nullable', 'string', 'max:255'],
            'municipio' => ['nullable', 'string', 'max:120'],
            'estado' => ['nullable', 'string', 'max:120'],
        ]);

        $sistema = $this->sistema($request);

        $resultado = $this->resolvedor->resolver($datos, $sistema?->slug);

        return response()->json([
            'data' => PersonaResource::make($resultado['persona']->load('etiquetas')),
            'meta' => [
                'persona_id' => $resultado['persona']->id,
                'creada' => $resultado['creada'],
                'coincidio_por' => $resultado['coincidio_por'],
            ],
        ], $resultado['creada'] ? 201 : 200);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function reglas(bool $parcial = false, ?int $personaId = null, bool $curpIdempotente = false): array
    {
        $requerido = $parcial ? 'sometimes' : 'required';

        $reglasDeCurp = ['nullable', 'string', 'size:18', 'regex:'.config('padron.reglas.curp')];

        if (! $curpIdempotente) {
            $reglasDeCurp[] = Rule::unique('personas', 'curp')->ignore($personaId);
        }

        return [
            'nombre_completo' => [$requerido, 'string', 'max:255'],
            'email' => ['nullable', 'email:rfc', 'max:255', Rule::unique('personas', 'email')->ignore($personaId)],
            'telefono' => ['nullable', 'string', 'regex:'.config('padron.reglas.telefono')],
            'telefono_secundario' => ['nullable', 'string', 'regex:'.config('padron.reglas.telefono')],
            'curp' => $reglasDeCurp,
            'rfc' => ['nullable', 'string', 'between:12,13', 'regex:'.config('padron.reglas.rfc')],
            'ine_clave' => ['nullable', 'string', 'max:20'],
            'calle' => ['nullable', 'string', 'max:255'],
            'calle_2' => ['nullable', 'string', 'max:255'],
            'codigo_postal' => ['nullable', 'string', 'regex:'.config('padron.reglas.codigo_postal')],
            'localidad' => ['nullable', 'string', 'max:120'],
            'ciudad' => ['nullable', 'string', 'max:120'],
            'municipio' => ['nullable', 'string', 'max:120'],
            'estado' => ['nullable', 'string', 'max:120'],
            'pais' => ['nullable', 'string', 'max:120'],
            'fecha_nacimiento' => ['nullable', 'date', 'before:today'],
            'sexo' => ['nullable', Rule::in(['M', 'F', 'Otro'])],
            'nivel_educativo' => ['nullable', 'string', 'max:120'],
            'habla_maya' => ['nullable', 'boolean'],
            'sitio_web' => ['nullable', 'url', 'max:255'],
            'facebook_negocio' => ['nullable', 'url', 'max:255'],
            'instagram_negocio' => ['nullable', 'url', 'max:255'],
            'tiktok_negocio' => ['nullable', 'url', 'max:255'],
            'idioma' => ['nullable', 'string', 'max:10'],
            'medio_ingreso' => ['nullable', 'string', 'max:120'],
            'tipo_persona' => ['nullable', Rule::in(['fisica', 'moral'])],
            'estado_persona' => ['nullable', Rule::in(['activa', 'inactiva', 'bloqueada'])],
            'creado_por_modulo' => ['nullable', 'string', 'max:60'],
        ];
    }

    private function sistema(Request $request): ?SistemaIntegrado
    {
        $autenticado = $request->user();

        return $autenticado instanceof SistemaIntegrado ? $autenticado : null;
    }
}
