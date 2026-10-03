<?php

namespace App\Services\Modulos;

/**
 * Los indicadores de dirección de un módulo para un periodo.
 *
 * Cada cifra se presenta junto con el rango al que corresponde: "ingresos
 * $124,800" sin decir de cuándo no es un dato, es una afirmación.
 */
final class Resumen
{
    /**
     * @param  array<int, Indicador>  $indicadores
     * @param  array{desde: string, hasta: string, descripcion: string}|null  $rango
     */
    public function __construct(
        public readonly array $indicadores,
        public readonly ?array $rango,
        public readonly ?string $obtenidoEn,
        public readonly ?Falla $falla = null,
    ) {}

    public static function fallido(Falla $falla): self
    {
        return new self([], null, null, $falla);
    }

    public function aArreglo(): array
    {
        return [
            'indicadores' => array_map(fn (Indicador $i) => $i->aArreglo(), $this->indicadores),
            'rango' => $this->rango,
            'obtenido_en' => $this->obtenidoEn,
            'falla' => $this->falla?->aArreglo(),
        ];
    }
}
