<?php

namespace App\Services\Consultas;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Filtros comunes a todas las consultas.
 *
 * Viven en la query string para que cualquier consulta sea compartible con
 * copiar y pegar la URL: es lo que pidió el instituto para poder mandarle
 * un resultado a dirección sin exportar nada.
 */
class FiltrosConsulta
{
    public function __construct(
        public readonly ?string $desde = null,
        public readonly ?string $hasta = null,
        public readonly ?string $municipio = null,
        /** @var array<string, mixed> Parámetros propios de cada consulta. */
        public readonly array $extra = [],
    ) {}

    public static function desdePeticion(Request $request): self
    {
        return new self(
            desde: $request->filled('desde') ? $request->date('desde')?->toDateString() : null,
            hasta: $request->filled('hasta') ? $request->date('hasta')?->toDateString() : null,
            municipio: $request->string('municipio')->toString() ?: null,
            extra: $request->except(['desde', 'hasta', 'municipio', 'page', 'consulta']),
        );
    }

    /**
     * Aplica los filtros comunes a una consulta sobre `personas`.
     *
     * El rango de fechas se mide contra `created_at`: la pregunta que hace
     * el instituto es "cuánta gente entró en este periodo", no "cuánta se
     * modificó".
     */
    public function aplicarAPersonas(Builder $consulta, string $tabla = 'personas'): Builder
    {
        return $consulta
            ->when($this->desde, fn ($q, $v) => $q->whereDate("{$tabla}.created_at", '>=', $v))
            ->when($this->hasta, fn ($q, $v) => $q->whereDate("{$tabla}.created_at", '<=', $v))
            ->when($this->municipio, fn ($q, $v) => $q->where("{$tabla}.municipio", $v));
    }

    public function hayAlguno(): bool
    {
        return $this->desde !== null || $this->hasta !== null || $this->municipio !== null;
    }

    /**
     * Descripción legible del periodo, para el encabezado de la exportación.
     */
    public function periodoLegible(): string
    {
        if ($this->desde && $this->hasta) {
            return "del {$this->desde} al {$this->hasta}";
        }

        if ($this->desde) {
            return "desde el {$this->desde}";
        }

        if ($this->hasta) {
            return "hasta el {$this->hasta}";
        }

        return 'histórico completo';
    }

    /** @return array<string, mixed> */
    public function aQueryString(): array
    {
        return array_filter([
            'desde' => $this->desde,
            'hasta' => $this->hasta,
            'municipio' => $this->municipio,
            ...$this->extra,
        ], fn ($valor) => $valor !== null && $valor !== '' && $valor !== []);
    }
}
