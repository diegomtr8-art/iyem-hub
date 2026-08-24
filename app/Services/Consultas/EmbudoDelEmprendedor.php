<?php

namespace App\Services\Consultas;

use App\Models\Persona;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Cuánta gente avanza de una etapa del instituto a la siguiente.
 *
 * El recorrido que el IYEM espera del emprendedor es:
 * capacitarse en Impúlsate → pedir crédito en CREA → instalarse en Nódico →
 * vender en Herencia Viva.
 *
 * Cada etapa cuenta a quienes **además** cumplieron todas las anteriores.
 * Contarlas por separado daría cuatro cifras grandes que no dicen nada del
 * recorrido; el embudo enseña dónde se pierde a la gente.
 */
class EmbudoDelEmprendedor implements Consulta
{
    private const ETAPAS = [
        ['slug' => 'impulsate', 'relacion' => 'impulstateInscripciones', 'nombre' => 'Se capacitó en Impúlsate'],
        ['slug' => 'crea', 'relacion' => 'creaSolicitudes', 'nombre' => 'Solicitó crédito en CREA'],
        ['slug' => 'nodico', 'relacion' => 'nodicoMembresias', 'nombre' => 'Rentó espacio en Nódico'],
        ['slug' => 'herenciaviva', 'relacion' => 'herenciaVivaClientes', 'nombre' => 'Vende en Herencia Viva'],
    ];

    public function clave(): string
    {
        return 'embudo-del-emprendedor';
    }

    public function titulo(): string
    {
        return 'Embudo del emprendedor';
    }

    public function descripcion(): string
    {
        return 'Cuántas personas pasan de Impúlsate a CREA, de ahí a Nódico y finalmente a Herencia Viva.';
    }

    public function icono(): string
    {
        return 'chart';
    }

    public function controles(): array
    {
        return [];
    }

    public function columnas(): array
    {
        return [
            'etapa' => 'Etapa',
            'personas' => 'Personas',
            'conversion_etapa' => 'Conversión desde la etapa anterior',
            'conversion_total' => 'Conversión desde el inicio',
            'perdidas' => 'Se quedaron en el camino',
        ];
    }

    public function resumen(FiltrosConsulta $filtros): array
    {
        $etapas = $this->calcular($filtros);
        $primera = $etapas->first();
        $ultima = $etapas->last();

        // La etapa donde más gente se cae es la que hay que atender.
        $mayorCaida = $etapas->skip(1)->sortByDesc('perdidas')->first();

        return [
            ['etiqueta' => 'Entran al embudo', 'valor' => $primera['personas'], 'detalle' => $primera['etapa']],
            ['etiqueta' => 'Llegan al final', 'valor' => $ultima['personas'], 'detalle' => $ultima['etapa']],
            [
                'etiqueta' => 'Conversión total',
                'valor' => $ultima['conversion_total'],
                'detalle' => 'De la primera etapa a la última',
            ],
            [
                'etiqueta' => 'Mayor fuga',
                'valor' => $mayorCaida ? $mayorCaida['perdidas'] : 0,
                'detalle' => $mayorCaida ? 'Antes de: '.$mayorCaida['etapa'] : '—',
            ],
        ];
    }

    public function tabla(FiltrosConsulta $filtros): LengthAwarePaginator
    {
        // Son cuatro filas fijas: se pagina para respetar el contrato, pero
        // nunca habrá una segunda página.
        $etapas = $this->calcular($filtros);

        return new \Illuminate\Pagination\LengthAwarePaginator(
            items: $etapas->all(),
            total: $etapas->count(),
            perPage: 20,
            currentPage: 1,
            options: ['path' => request()->url(), 'query' => request()->query()],
        );
    }

    public function grafica(FiltrosConsulta $filtros): ?array
    {
        $etapas = $this->calcular($filtros);

        if ($etapas->first()['personas'] === 0) {
            return null;
        }

        return [
            'tipo' => 'bar',
            'etiquetas' => $etapas->pluck('etapa')->all(),
            'series' => [[
                'etiqueta' => 'Personas',
                'datos' => $etapas->pluck('personas')->all(),
            ]],
        ];
    }

    public function filasParaExportar(FiltrosConsulta $filtros): Collection
    {
        return $this->calcular($filtros);
    }

    /**
     * Cada etapa exige haber cumplido todas las anteriores. Sin eso no es un
     * embudo, es una lista de cuatro conteos sueltos.
     */
    private function calcular(FiltrosConsulta $filtros): Collection
    {
        $acumuladas = [];
        $resultado = collect();
        $anterior = null;
        $inicial = null;

        foreach (self::ETAPAS as $etapa) {
            $acumuladas[] = $etapa['relacion'];

            $consulta = $filtros->aplicarAPersonas(Persona::query());

            foreach ($acumuladas as $relacion) {
                $consulta->whereHas($relacion);
            }

            $personas = $consulta->count();
            $inicial ??= $personas;

            $resultado->push([
                'etapa' => $etapa['nombre'],
                'slug' => $etapa['slug'],
                'personas' => $personas,
                'conversion_etapa' => $anterior === null
                    ? '100 %'
                    : ($anterior > 0 ? round($personas / $anterior * 100, 1).' %' : '0 %'),
                'conversion_total' => $inicial > 0 ? round($personas / $inicial * 100, 1).' %' : '0 %',
                'perdidas' => $anterior === null ? 0 : max(0, $anterior - $personas),
            ]);

            $anterior = $personas;
        }

        return $resultado;
    }
}
