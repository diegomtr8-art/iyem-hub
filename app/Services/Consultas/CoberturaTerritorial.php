<?php

namespace App\Services\Consultas;

use App\Models\Persona;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as Paginador;
use Illuminate\Support\Collection;

/**
 * Dónde NO está el instituto.
 *
 * Las demás consultas miran lo que sí pasó. Esta mira los huecos, que es lo
 * que sirve para decidir a dónde llevar la próxima brigada.
 *
 * El universo de municipios sale de `config/municipios_yucatan.php`, que
 * hoy cubre los principales del estado y no los 106. Eso se declara en la
 * pantalla: decir "faltan 4 municipios" cuando el catálogo solo conoce 33
 * sería engañoso.
 */
class CoberturaTerritorial implements Consulta
{
    public function clave(): string
    {
        return 'cobertura-territorial';
    }

    public function titulo(): string
    {
        return 'Cobertura territorial';
    }

    public function descripcion(): string
    {
        return 'Qué municipios del catálogo no tienen ni una sola persona en el padrón, y cuáles tienen muy pocas.';
    }

    public function icono(): string
    {
        return 'mapa';
    }

    public function controles(): array
    {
        return [
            [
                'nombre' => 'umbral',
                'etiqueta' => 'Considerar "cobertura débil" por debajo de',
                'tipo' => 'numero',
                'ayuda' => 'Número de personas. Por omisión, 5.',
            ],
        ];
    }

    public function columnas(): array
    {
        return [
            'municipio' => 'Municipio',
            'personas' => 'Personas',
            'situacion' => 'Situación',
            'coordenadas' => 'Coordenadas',
        ];
    }

    public function resumen(FiltrosConsulta $filtros): array
    {
        $filas = $this->calcular($filtros);
        $umbral = $this->umbral($filtros);

        return [
            ['etiqueta' => 'Municipios en el catálogo', 'valor' => $filas->count()],
            [
                'etiqueta' => 'Sin ninguna persona',
                'valor' => $filas->where('personas', 0)->count(),
                'detalle' => 'Cobertura nula',
            ],
            [
                'etiqueta' => 'Con cobertura débil',
                'valor' => $filas->where('personas', '>', 0)->where('personas', '<', $umbral)->count(),
                'detalle' => "Menos de {$umbral} personas",
            ],
            [
                'etiqueta' => 'Con cobertura',
                'valor' => $filas->where('personas', '>=', $umbral)->count(),
            ],
        ];
    }

    public function tabla(FiltrosConsulta $filtros): LengthAwarePaginator
    {
        $filas = $this->calcular($filtros);
        $pagina = max(1, (int) request()->integer('page', 1));
        $porPagina = 20;

        return new Paginador(
            items: $filas->forPage($pagina, $porPagina)->values()->all(),
            total: $filas->count(),
            perPage: $porPagina,
            currentPage: $pagina,
            options: ['path' => request()->url(), 'query' => request()->query()],
        );
    }

    public function grafica(FiltrosConsulta $filtros): ?array
    {
        $filas = $this->calcular($filtros);
        $umbral = $this->umbral($filtros);

        return [
            'tipo' => 'doughnut',
            'etiquetas' => ['Sin presencia', 'Cobertura débil', 'Con cobertura'],
            'series' => [[
                'etiqueta' => 'Municipios',
                'datos' => [
                    $filas->where('personas', 0)->count(),
                    $filas->where('personas', '>', 0)->where('personas', '<', $umbral)->count(),
                    $filas->where('personas', '>=', $umbral)->count(),
                ],
            ]],
        ];
    }

    public function filasParaExportar(FiltrosConsulta $filtros): Collection
    {
        return $this->calcular($filtros);
    }

    private function calcular(FiltrosConsulta $filtros): Collection
    {
        $umbral = $this->umbral($filtros);

        $conteos = $filtros->aplicarAPersonas(Persona::query())
            ->whereNotNull('municipio')
            ->selectRaw('municipio, COUNT(*) as total')
            ->groupBy('municipio')
            ->pluck('total', 'municipio');

        return collect(config('municipios_yucatan'))
            ->map(function (array $centroide, string $municipio) use ($conteos, $umbral) {
                $personas = (int) ($conteos[$municipio] ?? 0);

                return [
                    'municipio' => $municipio,
                    'personas' => $personas,
                    'situacion' => match (true) {
                        $personas === 0 => 'Sin presencia',
                        $personas < $umbral => 'Cobertura débil',
                        default => 'Con cobertura',
                    },
                    'coordenadas' => implode(', ', $centroide),
                    'latitud' => $centroide[0],
                    'longitud' => $centroide[1],
                ];
            })
            // Los huecos primero: es lo que se viene a ver.
            ->sortBy('personas')
            ->values();
    }

    private function umbral(FiltrosConsulta $filtros): int
    {
        $umbral = (int) ($filtros->extra['umbral'] ?? 5);

        return max(1, min(1000, $umbral));
    }
}
