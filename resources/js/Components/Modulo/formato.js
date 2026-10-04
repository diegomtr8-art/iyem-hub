/*
 * Cómo se escribe cada unidad de los tableros de módulo.
 *
 * Las unidades son las de `App\Services\Modulos\Indicador`. Dinero siempre
 * con moneda ($124,800.00 MXN): el Instituto maneja presupuesto público y un
 * número sin moneda en un reporte es un hallazgo de auditoría esperando.
 *
 * `null` no es cero: es que el módulo no dio ese dato.
 */

export const SIN_DATO = 'Sin dato';

const NUMERICAS = ['porcentaje', 'moneda', 'entero', 'decimal', 'horas'];

export const esNumerica = (unidad) => NUMERICAS.includes(unidad);

const numero = (valor, decimales) =>
    Number(valor).toLocaleString('es-MX', {
        minimumFractionDigits: decimales,
        maximumFractionDigits: decimales,
    });

export function formatear(valor, unidad, moneda = 'MXN') {
    if (valor === null || valor === undefined || valor === '') return SIN_DATO;

    switch (unidad) {
        case 'moneda':
            return `$${numero(valor, 2)} ${moneda}`;
        case 'porcentaje':
            return `${Number(valor).toLocaleString('es-MX', { maximumFractionDigits: 1 })}%`;
        case 'horas':
            return `${Number(valor).toLocaleString('es-MX', { maximumFractionDigits: 1 })} h`;
        case 'entero':
            return numero(valor, 0);
        case 'decimal':
            return Number(valor).toLocaleString('es-MX', { maximumFractionDigits: 2 });
        case 'fecha':
            return fecha(valor);
        default:
            return String(valor);
    }
}

/** "1 oct 2026" a partir de "2026-10-01", sin que la zona horaria la corra un día. */
export function fecha(valor) {
    const [anio, mes, dia] = String(valor).slice(0, 10).split('-').map(Number);
    if (!anio || !mes || !dia) return String(valor);

    return new Date(anio, mes - 1, dia).toLocaleDateString('es-MX', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
}

/** "hoy a las 10:42" o la fecha completa, para "último dato" y "consultado". */
export function momento(iso) {
    if (!iso) return null;

    const instante = new Date(iso);
    const hora = instante.toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' });

    return instante.toDateString() === new Date().toDateString()
        ? `hoy a las ${hora}`
        : `${instante.toLocaleDateString('es-MX', { day: 'numeric', month: 'short', year: 'numeric' })}, ${hora}`;
}
