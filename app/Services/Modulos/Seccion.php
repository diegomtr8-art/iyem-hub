<?php

namespace App\Services\Modulos;

/**
 * Una tabla de reporte de un módulo, ya normalizada.
 *
 * Las columnas declaran su tipo con las mismas unidades de `Indicador`; la
 * página alinea y formatea según el tipo, sin conocer los campos del módulo.
 * Cada sección sale de su propia llamada y puede fallar sola: que no llegue la
 * ocupación no borra los ingresos.
 */
final class Seccion
{
    /**
     * @param  array<int, array{clave: string, etiqueta: string, tipo: string}>  $columnas
     * @param  array<int, array<string, mixed>>  $filas
     * @param  array<string, mixed>|null  $totales  Pie de la tabla, por clave de columna.
     */
    public function __construct(
        public readonly string $clave,
        public readonly string $titulo,
        public readonly ?string $descripcion,
        public readonly array $columnas,
        public readonly array $filas = [],
        public readonly ?array $totales = null,
        /** Clave del informe CSV equivalente, si el módulo lo ofrece. */
        public readonly ?string $informe = null,
        public readonly ?array $rango = null,
        public readonly ?string $obtenidoEn = null,
        public readonly ?Falla $falla = null,
    ) {}

    /** La misma sección sin datos, con el motivo. */
    public function conFalla(Falla $falla): self
    {
        return new self($this->clave, $this->titulo, $this->descripcion, $this->columnas, informe: $this->informe, falla: $falla);
    }

    public function aArreglo(): array
    {
        return [
            'clave' => $this->clave,
            'titulo' => $this->titulo,
            'descripcion' => $this->descripcion,
            'columnas' => $this->columnas,
            'filas' => $this->filas,
            'totales' => $this->totales,
            'informe' => $this->informe,
            'rango' => $this->rango,
            'obtenido_en' => $this->obtenidoEn,
            'falla' => $this->falla?->aArreglo(),
        ];
    }
}
