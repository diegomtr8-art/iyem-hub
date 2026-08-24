<?php

namespace App\Services\Consultas;

use App\Models\Persona;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Cuánta gente atiende el instituto en cada municipio.
 *
 * Es la consulta que más se pide y la que sostiene el mapa: sin ella no hay
 * forma de saber si la operación está concentrada en Mérida o repartida.
 */
class PersonasPorMunicipio implements Consulta
{
    public function clave(): string
    {
        return 'personas-por-municipio';
    }

    public function titulo(): string
    {
        return 'Personas por municipio';
    }

    public function descripcion(): string
    {
        return 'Cuántas personas del padrón hay en cada municipio, y qué proporción del total representan.';
    }

    public function icono(): string
    {
        return 'mapa';
    }

    public function controles(): array
    {
        return [
            [
                'nombre' => 'estado_persona',
                'etiqueta' => 'Estado de la persona',
                'tipo' => 'select',
                'opciones' => [
                    '' => 'Todos',
                    'activa' => 'Activa',
                    'inactiva' => 'Inactiva',
                    'bloqueada' => 'Bloqueada',
                ],
            ],
        ];
    }

    public function columnas(): array
    {
        return [
            'municipio' => 'Municipio',
            'total' => 'Personas',
            'activas' => 'Activas',
            'porcentaje' => '% del padrón',
        ];
    }

    public function resumen(FiltrosConsulta $filtros): array
    {
        $base = $this->base($filtros);

        $total = (clone $base)->count();
        $municipios = (clone $base)->distinct()->count('municipio');
        $enMerida = (clone $base)->where('municipio', 'Mérida')->count();

        return [
            ['etiqueta' => 'Personas', 'valor' => $total],
            ['etiqueta' => 'Municipios con presencia', 'valor' => $municipios],
            [
                'etiqueta' => 'Concentración en Mérida',
                'valor' => $total > 0 ? round($enMerida / $total * 100, 1).' %' : '—',
                'detalle' => "{$enMerida} personas",
            ],
        ];
    }

    public function tabla(FiltrosConsulta $filtros): LengthAwarePaginator
    {
        $total = $this->base($filtros)->count();

        return $this->agrupado($filtros)
            ->paginate(20)
            ->withQueryString()
            ->through(fn ($fila) => $this->formatear($fila, $total));
    }

    public function grafica(FiltrosConsulta $filtros): ?array
    {
        // Solo los quince municipios con más gente: una barra por cada uno de
        // los 106 municipios de Yucatán no se lee en ninguna pantalla.
        $filas = $this->agrupado($filtros)->limit(15)->get();

        if ($filas->isEmpty()) {
            return null;
        }

        return [
            'tipo' => 'bar',
            'etiquetas' => $filas->pluck('municipio')->all(),
            'series' => [[
                'etiqueta' => 'Personas',
                'datos' => $filas->pluck('total')->map(fn ($v) => (int) $v)->all(),
            ]],
        ];
    }

    public function filasParaExportar(FiltrosConsulta $filtros): Collection
    {
        $total = $this->base($filtros)->count();

        return $this->agrupado($filtros)->get()->map(fn ($fila) => $this->formatear($fila, $total));
    }

    private function base(FiltrosConsulta $filtros)
    {
        return $filtros->aplicarAPersonas(Persona::query())
            ->whereNotNull('municipio')
            ->when(
                $filtros->extra['estado_persona'] ?? null,
                fn ($q, $v) => $q->where('estado_persona', $v)
            );
    }

    private function agrupado(FiltrosConsulta $filtros)
    {
        return $this->base($filtros)
            ->select('municipio')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN estado_persona = 'activa' THEN 1 ELSE 0 END) as activas")
            ->groupBy('municipio')
            ->orderByDesc(DB::raw('COUNT(*)'));
    }

    private function formatear($fila, int $total): array
    {
        return [
            'municipio' => $fila->municipio,
            'total' => (int) $fila->total,
            'activas' => (int) $fila->activas,
            'porcentaje' => $total > 0 ? round($fila->total / $total * 100, 1).' %' : '0 %',
        ];
    }
}
