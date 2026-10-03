<?php

namespace App\Services\Modulos\Adaptadores;

use App\Services\Modulos\AdaptadorDeModulo;
use App\Services\Modulos\ClienteDeModulo;
use App\Services\Modulos\Falla;
use App\Services\Modulos\Indicador;
use App\Services\Modulos\Periodo;
use App\Services\Modulos\RespuestaDeModulo;
use App\Services\Modulos\Resumen;
use App\Services\Modulos\Seccion;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Nódico 2.0 (CoworkHub): la "cara reportes" de su API, `/api/v1/reportes/*`
 * (docs/API-MOVIL.md §6.11 en el repositorio de CoworkHub).
 *
 * Da los mismos números que el panel de Nódico porque los calcula el mismo
 * código del otro lado (`GeneradorDeReportes`). Aquí no se recalcula nada:
 * solo se renombra y, en los pies de tabla, se suman las filas que llegaron.
 * Si un número parece mal, se corrige en Nódico, no aquí.
 */
class NodicoAdaptador implements AdaptadorDeModulo
{
    private const INFORMES = [
        'ocupacion' => ['etiqueta' => 'Ocupación por espacio', 'personal' => false],
        'consumo' => ['etiqueta' => 'Aprovechamiento por plan', 'personal' => false],
        'ingresos' => ['etiqueta' => 'Ingresos por plan', 'personal' => false],
        'no_show' => ['etiqueta' => 'Inasistencias por miembro', 'personal' => true],
        'en_riesgo' => ['etiqueta' => 'Miembros en riesgo', 'personal' => true],
    ];

    public function __construct(
        private readonly ClienteDeModulo $cliente,
        private readonly string $slug,
    ) {}

    public function clave(): string
    {
        return 'nodico';
    }

    public function resumen(Periodo $periodo): Resumen
    {
        $respuesta = $this->cliente->consultar($this->slug, 'reportes/resumen', $periodo->consulta());

        if (! $respuesta->ok()) {
            return Resumen::fallido($respuesta->falla);
        }

        $d = $respuesta->datos;

        return new Resumen(
            indicadores: [
                new Indicador('ocupacion', 'Ocupación media', $this->numero($d, 'ocupacion_media_pct'), Indicador::PORCENTAJE),
                new Indicador('ingresos', 'Ingresos del periodo', $this->numero($d, 'ingresos'), Indicador::MONEDA, [
                    new Indicador('ingresos_membresias', 'Membresías', $this->numero($d, 'ingresos_membresias'), Indicador::MONEDA),
                    new Indicador('ingresos_salones', 'Salones', $this->numero($d, 'ingresos_salones'), Indicador::MONEDA),
                    new Indicador('facturado_pagado', 'Facturado y pagado', $this->numero($d, 'facturado_pagado'), Indicador::MONEDA),
                ]),
                new Indicador('reservas', 'Reservas', $this->numero($d, 'reservas'), Indicador::ENTERO, [
                    new Indicador('horas_reservadas', 'Horas reservadas', $this->numero($d, 'horas_reservadas'), Indicador::HORAS),
                ]),
                new Indicador('no_show', 'Tasa de inasistencia', $this->numero($d, 'tasa_no_show_pct'), Indicador::PORCENTAJE),
                new Indicador('en_riesgo', 'Miembros en riesgo', $this->numero($d, 'miembros_en_riesgo'), Indicador::ENTERO, detallePersonal: true),
            ],
            rango: $this->rango($d),
            obtenidoEn: $respuesta->obtenidoEn,
        );
    }

    public function secciones(Periodo $periodo): array
    {
        $ocupacion = $this->cliente->consultar($this->slug, 'reportes/ocupacion', $periodo->consulta());
        $ingresos = $this->cliente->consultar($this->slug, 'reportes/ingresos', $periodo->consulta());
        $consumo = $this->cliente->consultar($this->slug, 'reportes/consumo', $periodo->consulta());

        return [
            $this->armar($this->porEspacio(), $ocupacion, function (array $d) {
                $filas = $this->lista($d, 'por_espacio');
                $horas = $this->suma($filas, 'horas');
                $capacidad = $this->suma($filas, 'capacidad_horas');

                return [$filas, [
                    'espacio' => 'Total',
                    'reservas' => $this->suma($filas, 'reservas'),
                    'horas' => $horas,
                    'capacidad_horas' => $capacidad,
                    // La ocupación del conjunto es horas entre capacidad, no el
                    // promedio de los porcentajes.
                    'ocupacion_pct' => $capacidad > 0 ? (int) round($horas / $capacidad * 100) : null,
                ]];
            }),
            $this->armar($this->porFranja(), $ocupacion, fn (array $d) => [
                $filas = $this->lista($d, 'por_franja'),
                ['hora' => 'Total', 'reservas' => $this->suma($filas, 'reservas')],
            ]),
            $this->armar($this->ingresosPorPlan(), $ingresos, fn (array $d) => [
                $filas = $this->lista($d, 'por_plan'),
                ['plan' => 'Total', 'membresias' => $this->suma($filas, 'membresias'), 'ingreso' => $this->numero($d, 'total_membresias')],
            ]),
            $this->armar($this->aprovechamiento(), $consumo, fn (array $d) => [$this->lista($d, 'filas'), null]),
        ];
    }

    public function ofreceDatosPersonales(): bool
    {
        return true;
    }

    public function datosPersonales(Periodo $periodo): array
    {
        // Sin caché: los nombres y teléfonos no se quedan guardados en el ERP.
        $enRiesgo = $this->cliente->consultar($this->slug, 'reportes/en-riesgo', cachear: false);
        $noShow = $this->cliente->consultar($this->slug, 'reportes/no-show', $periodo->consulta(), cachear: false);

        return [
            // `en-riesgo` no depende del periodo: es la foto de hoy.
            $this->armar($this->miembrosEnRiesgo(), $enRiesgo, fn (array $d) => [array_values($d), null], conRango: false),
            $this->armar($this->inasistencias(), $noShow, fn (array $d) => [
                $filas = $this->lista($d, 'por_miembro'),
                ['miembro' => 'Total', 'faltas' => $this->suma($filas, 'faltas'), 'horas' => $this->suma($filas, 'horas')],
            ]),
        ];
    }

    public function informesExportables(): array
    {
        return self::INFORMES;
    }

    public function exportar(string $informe, Periodo $periodo): StreamedResponse|Falla
    {
        return $this->cliente->descargar(
            $this->slug,
            "reportes/{$informe}/csv",
            $periodo->consulta(),
            "nodico-{$informe}-{$periodo->desde->toDateString()}-{$periodo->hasta->toDateString()}.csv",
        );
    }

    /* ------------------------------------------------------------------ *
     * Estructura de cada tabla
     * ------------------------------------------------------------------ */

    private function porEspacio(): Seccion
    {
        return new Seccion('ocupacion_espacio', 'Ocupación por espacio', 'Horas reservadas contra horas disponibles de cada espacio.', [
            ['clave' => 'espacio', 'etiqueta' => 'Espacio', 'tipo' => Indicador::TEXTO],
            ['clave' => 'tipo', 'etiqueta' => 'Tipo', 'tipo' => Indicador::TEXTO],
            ['clave' => 'reservas', 'etiqueta' => 'Reservas', 'tipo' => Indicador::ENTERO],
            ['clave' => 'horas', 'etiqueta' => 'Horas', 'tipo' => Indicador::HORAS],
            ['clave' => 'capacidad_horas', 'etiqueta' => 'Capacidad', 'tipo' => Indicador::HORAS],
            ['clave' => 'ocupacion_pct', 'etiqueta' => 'Ocupación', 'tipo' => Indicador::PORCENTAJE],
        ], informe: 'ocupacion');
    }

    private function porFranja(): Seccion
    {
        return new Seccion('ocupacion_franja', 'Ocupación por franja horaria', 'Reservas que empiezan en cada hora. El porcentaje es contra la franja más concurrida.', [
            ['clave' => 'hora', 'etiqueta' => 'Hora', 'tipo' => Indicador::TEXTO],
            ['clave' => 'reservas', 'etiqueta' => 'Reservas', 'tipo' => Indicador::ENTERO],
            ['clave' => 'pct', 'etiqueta' => 'Respecto a la más concurrida', 'tipo' => Indicador::PORCENTAJE],
        ]);
    }

    private function ingresosPorPlan(): Seccion
    {
        return new Seccion('ingresos_plan', 'Ingresos por plan', 'Membresías vendidas en el periodo. Los salones van aparte, en el indicador de ingresos.', [
            ['clave' => 'plan', 'etiqueta' => 'Plan', 'tipo' => Indicador::TEXTO],
            ['clave' => 'membresias', 'etiqueta' => 'Membresías', 'tipo' => Indicador::ENTERO],
            ['clave' => 'ingreso', 'etiqueta' => 'Ingreso', 'tipo' => Indicador::MONEDA],
        ], informe: 'ingresos');
    }

    private function aprovechamiento(): Seccion
    {
        return new Seccion('aprovechamiento_plan', 'Aprovechamiento por plan', 'Cuánto de las horas incluidas en cada plan usan sus miembros.', [
            ['clave' => 'plan', 'etiqueta' => 'Plan', 'tipo' => Indicador::TEXTO],
            ['clave' => 'bolsa', 'etiqueta' => 'Bolsa', 'tipo' => Indicador::TEXTO],
            ['clave' => 'miembros', 'etiqueta' => 'Miembros', 'tipo' => Indicador::ENTERO],
            ['clave' => 'incluidas_por_miembro', 'etiqueta' => 'Incluidas c/u', 'tipo' => Indicador::HORAS],
            ['clave' => 'usadas_total', 'etiqueta' => 'Usadas', 'tipo' => Indicador::HORAS],
            ['clave' => 'promedio_por_miembro', 'etiqueta' => 'Promedio c/u', 'tipo' => Indicador::HORAS],
            ['clave' => 'aprovechamiento_pct', 'etiqueta' => 'Aprovechamiento', 'tipo' => Indicador::PORCENTAJE],
            ['clave' => 'al_tope', 'etiqueta' => 'Al tope', 'tipo' => Indicador::ENTERO],
        ], informe: 'consumo');
    }

    private function miembrosEnRiesgo(): Seccion
    {
        return new Seccion('miembros_en_riesgo', 'Miembros en riesgo', 'Con membresía activa y sin venir en las últimas semanas. Es la foto de hoy: no depende del periodo.', [
            ['clave' => 'miembro', 'etiqueta' => 'Miembro', 'tipo' => Indicador::TEXTO],
            ['clave' => 'email', 'etiqueta' => 'Correo', 'tipo' => Indicador::TEXTO],
            ['clave' => 'telefono', 'etiqueta' => 'Teléfono', 'tipo' => Indicador::TEXTO],
            ['clave' => 'plan', 'etiqueta' => 'Plan', 'tipo' => Indicador::TEXTO],
            ['clave' => 'vence', 'etiqueta' => 'Vence', 'tipo' => Indicador::FECHA],
            ['clave' => 'ultimo_acceso', 'etiqueta' => 'Última visita', 'tipo' => Indicador::FECHA],
            ['clave' => 'dias_sin_venir', 'etiqueta' => 'Días sin venir', 'tipo' => Indicador::ENTERO],
        ], informe: 'en_riesgo');
    }

    private function inasistencias(): Seccion
    {
        return new Seccion('inasistencias', 'Inasistencias por miembro', 'Reservas a las que el miembro no llegó en el periodo.', [
            ['clave' => 'miembro', 'etiqueta' => 'Miembro', 'tipo' => Indicador::TEXTO],
            ['clave' => 'faltas', 'etiqueta' => 'Faltas', 'tipo' => Indicador::ENTERO],
            ['clave' => 'horas', 'etiqueta' => 'Horas perdidas', 'tipo' => Indicador::HORAS],
        ], informe: 'no_show');
    }

    /* ------------------------------------------------------------------ *
     * Normalización
     * ------------------------------------------------------------------ */

    /**
     * Llena la estructura con los datos, o la deja vacía con su falla.
     *
     * @param  callable(array): array{0: array, 1: ?array}  $llenar  devuelve [filas, totales]
     */
    private function armar(Seccion $seccion, RespuestaDeModulo $respuesta, callable $llenar, bool $conRango = true): Seccion
    {
        if (! $respuesta->ok()) {
            return $seccion->conFalla($respuesta->falla);
        }

        [$filas, $totales] = $llenar($respuesta->datos);
        $columnas = array_column($seccion->columnas, 'clave');

        return new Seccion(
            $seccion->clave,
            $seccion->titulo,
            $seccion->descripcion,
            $seccion->columnas,
            // Solo las columnas declaradas: un campo nuevo de Nódico no se cuela
            // en la tabla (ni un `url` interno, ni un dato que nadie revisó).
            array_map(fn (array $fila) => array_intersect_key($fila, array_flip($columnas)), $filas),
            $totales,
            $seccion->informe,
            $conRango ? $this->rango($respuesta->datos) : null,
            $respuesta->obtenidoEn,
        );
    }

    /** `rango` de Nódico trae `etiqueta`; en el ERP se llama `descripcion`. */
    private function rango(array $datos): ?array
    {
        $rango = $datos['rango'] ?? null;

        if (! is_array($rango) || ! isset($rango['desde'], $rango['hasta'])) {
            return null;
        }

        return [
            'desde' => $rango['desde'],
            'hasta' => $rango['hasta'],
            'descripcion' => $rango['etiqueta'] ?? "{$rango['desde']} – {$rango['hasta']}",
        ];
    }

    /** Un campo numérico, o `null` si no vino. Nunca cero por omisión. */
    private function numero(array $datos, string $campo): int|float|null
    {
        $valor = $datos[$campo] ?? null;

        return is_numeric($valor) ? $valor + 0 : null;
    }

    private function lista(array $datos, string $campo): array
    {
        return is_array($datos[$campo] ?? null) ? array_values($datos[$campo]) : [];
    }

    private function suma(array $filas, string $campo): int|float
    {
        return round(array_sum(array_map(fn (array $f) => is_numeric($f[$campo] ?? null) ? $f[$campo] + 0 : 0, $filas)), 2);
    }
}
