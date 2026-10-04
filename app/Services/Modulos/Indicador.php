<?php

namespace App\Services\Modulos;

/**
 * Una cifra del tablero, ya en el idioma del ERP.
 *
 * La página no sabe cómo llama cada módulo a sus campos: recibe la etiqueta,
 * el valor y la unidad, y con la unidad decide cómo escribirlo (`%`, moneda,
 * horas). `null` es ausencia —el módulo no dio ese dato—, nunca cero.
 */
final class Indicador
{
    public const PORCENTAJE = 'porcentaje';

    public const MONEDA = 'moneda';

    public const ENTERO = 'entero';

    public const DECIMAL = 'decimal';

    public const HORAS = 'horas';

    public const FECHA = 'fecha';

    public const TEXTO = 'texto';

    /**
     * @param  array<int, Indicador>  $desglose  Cifras menores que acompañan a la principal.
     */
    public function __construct(
        public readonly string $clave,
        public readonly string $etiqueta,
        public readonly int|float|null $valor,
        public readonly string $unidad,
        public readonly array $desglose = [],
        /** La cifra resume una lista de personas que tiene su propia vista. */
        public readonly bool $detallePersonal = false,
        public readonly string $moneda = 'MXN',
    ) {}

    public function aArreglo(): array
    {
        return [
            'clave' => $this->clave,
            'etiqueta' => $this->etiqueta,
            'valor' => $this->valor,
            'unidad' => $this->unidad,
            'moneda' => $this->unidad === self::MONEDA ? $this->moneda : null,
            'desglose' => array_map(fn (Indicador $i) => $i->aArreglo(), $this->desglose),
            'detalle_personal' => $this->detallePersonal,
        ];
    }
}
