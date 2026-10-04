<?php

namespace App\Services\Modulos;

use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Traduce la API de reportes de un módulo al idioma del ERP.
 *
 * La página del tablero no sabe que Nódico llama `tasa_no_show_pct` a algo ni
 * que envuelve sus respuestas en `data`: eso se queda aquí. Para sumar un
 * módulo se escribe su adaptador y se registra en `RegistroDeAdaptadores`; la
 * página, el controlador y el cliente HTTP no se tocan.
 *
 * Todo es lectura. Un adaptador no cancela, cobra ni edita nada en su módulo.
 */
interface AdaptadorDeModulo
{
    /** La clave con que se declara en `config/modulos.php` → `tablero`. */
    public function clave(): string;

    /** Los indicadores de dirección del periodo. */
    public function resumen(Periodo $periodo): Resumen;

    /**
     * Tablas de reporte del periodo, sin datos personales.
     *
     * @return array<int, Seccion>
     */
    public function secciones(Periodo $periodo): array;

    /** Si `datosPersonales()` tiene algo que mostrar. Sin llamar a la API. */
    public function ofreceDatosPersonales(): bool;

    /**
     * Listas con personas identificables (nombres, contacto). Van detrás de
     * `ver-modulo-datos-personales` y cada consulta queda en `accesos`.
     *
     * @return array<int, Seccion> vacío si el módulo no ofrece ninguna
     */
    public function datosPersonales(Periodo $periodo): array;

    /**
     * Informes CSV que ofrece el módulo.
     *
     * @return array<string, array{etiqueta: string, personal: bool}>
     */
    public function informesExportables(): array;

    /** El CSV del módulo, transmitido tal cual, o por qué no se pudo. */
    public function exportar(string $informe, Periodo $periodo): StreamedResponse|Falla;
}
