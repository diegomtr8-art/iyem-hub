<?php

namespace App\Services\Consultas;

use App\Models\Persona;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * "¿Quiénes tienen crédito CREA **y** asistieron a Impúlsate?"
 *
 * Es la pregunta que dio origen al hub. Hoy nadie puede responderla porque
 * cada módulo tiene su propia base; aquí se responde con un JOIN sobre
 * `persona_id`, sin mover un registro de su lugar.
 */
class CruceDeModulos implements Consulta
{
    /**
     * Relación de Eloquent por módulo. Solo se listan los módulos que ya
     * guardan `persona_id` en el hub; los que todavía no se integran no
     * pueden cruzarse y sería deshonesto ofrecerlos en el selector.
     */
    public const RELACIONES = [
        'crea' => 'creaSolicitudes',
        'impulsate' => 'impulstateInscripciones',
        'nodico' => 'nodicoMembresias',
        'herenciaviva' => 'herenciaVivaClientes',
        'juridico' => 'juridicoAsesorias',
    ];

    public const OPERADORES = [
        'y' => 'Y (en todos los seleccionados)',
        'o' => 'O (en al menos uno)',
        'sin' => 'SIN (en ninguno de ellos)',
    ];

    public function clave(): string
    {
        return 'cruce-de-modulos';
    }

    public function titulo(): string
    {
        return 'Cruce de módulos';
    }

    public function descripcion(): string
    {
        return 'Quiénes aparecen en dos o tres módulos a la vez, en alguno de ellos, o en ninguno.';
    }

    public function icono(): string
    {
        return 'stack';
    }

    public function controles(): array
    {
        return [
            [
                'nombre' => 'modulos',
                'etiqueta' => 'Módulos a cruzar',
                'tipo' => 'checkbox-multiple',
                'ayuda' => 'Elige entre dos y tres módulos.',
                'opciones' => collect(self::RELACIONES)
                    ->map(fn ($relacion, $slug) => config("modulos.{$slug}.nombre") ?? $slug)
                    ->all(),
            ],
            [
                'nombre' => 'operador',
                'etiqueta' => 'Operador',
                'tipo' => 'select',
                'opciones' => self::OPERADORES,
            ],
        ];
    }

    public function columnas(): array
    {
        return [
            'nombre_completo' => 'Nombre',
            'municipio' => 'Municipio',
            'telefono' => 'Teléfono',
            'modulos' => 'Aparece en',
            'estado_persona' => 'Estado',
        ];
    }

    public function resumen(FiltrosConsulta $filtros): array
    {
        $seleccionados = $this->modulosSeleccionados($filtros);
        $operador = $this->operador($filtros);

        if (count($seleccionados) < 2) {
            return [[
                'etiqueta' => 'Sin cruce',
                'valor' => '—',
                'detalle' => 'Elige al menos dos módulos',
            ]];
        }

        $coinciden = $this->consulta($filtros)->count();
        $universo = $filtros->aplicarAPersonas(Persona::query())->count();

        $nombres = collect($seleccionados)
            ->map(fn (string $slug) => config("modulos.{$slug}.nombre") ?? $slug)
            ->implode(self::OPERADORES[$operador] === self::OPERADORES['o'] ? ' o ' : ' y ');

        return [
            ['etiqueta' => 'Personas que coinciden', 'valor' => $coinciden, 'detalle' => $nombres],
            [
                'etiqueta' => 'Del padrón filtrado',
                'valor' => $universo > 0 ? round($coinciden / $universo * 100, 1).' %' : '—',
                'detalle' => number_format($universo).' personas en el universo',
            ],
        ];
    }

    public function tabla(FiltrosConsulta $filtros): LengthAwarePaginator
    {
        return $this->consulta($filtros)
            ->orderBy('nombre_completo')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Persona $persona) => $this->formatear($persona));
    }

    public function grafica(FiltrosConsulta $filtros): ?array
    {
        $seleccionados = $this->modulosSeleccionados($filtros);

        if (count($seleccionados) < 2) {
            return null;
        }

        // Cuánta gente hay en cada módulo por separado, frente a cuánta cae
        // en la intersección: es lo que hace evidente si el cruce es grande
        // o marginal.
        $etiquetas = [];
        $datos = [];

        foreach ($seleccionados as $slug) {
            $etiquetas[] = config("modulos.{$slug}.nombre") ?? $slug;
            $datos[] = $filtros->aplicarAPersonas(Persona::query())
                ->whereHas(self::RELACIONES[$slug])
                ->count();
        }

        $etiquetas[] = 'Coinciden';
        $datos[] = $this->consulta($filtros)->count();

        return [
            'tipo' => 'bar',
            'etiquetas' => $etiquetas,
            'series' => [['etiqueta' => 'Personas', 'datos' => $datos]],
        ];
    }

    public function filasParaExportar(FiltrosConsulta $filtros): Collection
    {
        return $this->consulta($filtros)
            ->orderBy('nombre_completo')
            ->get()
            ->map(fn (Persona $persona) => $this->formatear($persona));
    }

    private function consulta(FiltrosConsulta $filtros): Builder
    {
        $seleccionados = $this->modulosSeleccionados($filtros);
        $operador = $this->operador($filtros);

        $consulta = $filtros->aplicarAPersonas(Persona::query())
            ->withCount(array_values(self::RELACIONES));

        if (count($seleccionados) < 2) {
            // Sin cruce válido no se devuelve el padrón entero: eso daría la
            // falsa impresión de que todos coinciden.
            return $consulta->whereRaw('1 = 0');
        }

        return match ($operador) {
            'o' => $consulta->where(function (Builder $query) use ($seleccionados) {
                foreach ($seleccionados as $slug) {
                    $query->orWhereHas(self::RELACIONES[$slug]);
                }
            }),
            'sin' => $consulta->where(function (Builder $query) use ($seleccionados) {
                foreach ($seleccionados as $slug) {
                    $query->whereDoesntHave(self::RELACIONES[$slug]);
                }
            }),
            default => tap($consulta, function (Builder $query) use ($seleccionados) {
                foreach ($seleccionados as $slug) {
                    $query->whereHas(self::RELACIONES[$slug]);
                }
            }),
        };
    }

    /** @return array<int, string> */
    private function modulosSeleccionados(FiltrosConsulta $filtros): array
    {
        $crudos = $filtros->extra['modulos'] ?? [];

        if (is_string($crudos)) {
            $crudos = explode(',', $crudos);
        }

        return collect($crudos)
            ->map(fn ($slug) => trim((string) $slug))
            ->filter(fn (string $slug) => isset(self::RELACIONES[$slug]))
            ->unique()
            // Tres es el tope: con cuatro módulos la intersección casi
            // siempre queda vacía y la pantalla deja de decir algo.
            ->take(3)
            ->values()
            ->all();
    }

    private function operador(FiltrosConsulta $filtros): string
    {
        $operador = $filtros->extra['operador'] ?? 'y';

        return isset(self::OPERADORES[$operador]) ? $operador : 'y';
    }

    private function formatear(Persona $persona): array
    {
        $presentes = collect(self::RELACIONES)
            ->filter(fn (string $relacion, string $slug) => $persona->{"{$this->contador($relacion)}"} > 0)
            ->map(fn (string $relacion, string $slug) => config("modulos.{$slug}.nombre") ?? $slug)
            ->values();

        return [
            'id' => $persona->id,
            'nombre_completo' => $persona->nombre_completo,
            'municipio' => $persona->municipio ?? '—',
            'telefono' => $persona->telefono ?? '—',
            'modulos' => $presentes->implode(', ') ?: '—',
            'estado_persona' => $persona->estado_persona,
        ];
    }

    private function contador(string $relacion): string
    {
        return Str::snake($relacion).'_count';
    }
}
