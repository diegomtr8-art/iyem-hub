<?php

namespace App\Services\Consultas;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Contrato de una consulta predefinida del módulo Consultas 360°.
 *
 * Son consultas cerradas y no un constructor libre a propósito: el
 * instituto tiene seis preguntas concretas que hoy no puede responder, y
 * resolverlas bien vale más que un generador genérico que nadie sabría usar.
 */
interface Consulta
{
    public function clave(): string;

    public function titulo(): string;

    public function descripcion(): string;

    public function icono(): string;

    /**
     * Controles adicionales que la consulta necesita, más allá del rango de
     * fechas y el municipio.
     *
     * @return array<int, array{nombre: string, etiqueta: string, tipo: string, opciones?: array}>
     */
    public function controles(): array;

    /**
     * Cifras de encabezado.
     *
     * @return array<int, array{etiqueta: string, valor: int|string, detalle?: string}>
     */
    public function resumen(FiltrosConsulta $filtros): array;

    /**
     * Filas de la tabla, paginadas del lado del servidor.
     */
    public function tabla(FiltrosConsulta $filtros): LengthAwarePaginator;

    /**
     * Encabezados de la tabla, en el orden en que se muestran.
     *
     * @return array<string, string> clave => etiqueta
     */
    public function columnas(): array;

    /**
     * Datos para Chart.js.
     *
     * @return array{tipo: string, etiquetas: array, series: array}|null
     */
    public function grafica(FiltrosConsulta $filtros): ?array;

    /**
     * Todas las filas, sin paginar, para la exportación.
     */
    public function filasParaExportar(FiltrosConsulta $filtros): Collection;
}
