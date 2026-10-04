/*
 * Formatos de cifras y fechas de la interfaz.
 *
 * Las fechas se muestran como DD/MM/AAAA (y HH:mm cuando importa la hora),
 * siempre en columna con fuente mono para que alineen.
 */

const dos = (n) => String(n).padStart(2, '0');

const aFecha = (valor) => {
    if (!valor) return null;
    const fecha = valor instanceof Date ? valor : new Date(valor);
    return Number.isNaN(fecha.getTime()) ? null : fecha;
};

/** 03/10/2026 */
export function fecha(valor) {
    const f = aFecha(valor);
    if (!f) return '—';
    return `${dos(f.getDate())}/${dos(f.getMonth() + 1)}/${f.getFullYear()}`;
}

/** 03/10/2026 14:05 */
export function fechaHora(valor) {
    const f = aFecha(valor);
    if (!f) return '—';
    return `${fecha(f)} ${dos(f.getHours())}:${dos(f.getMinutes())}`;
}

/** 3 de octubre de 2026 — para textos corridos, no para columnas. */
export function fechaLarga(valor = new Date()) {
    const f = aFecha(valor);
    if (!f) return '';
    return f.toLocaleDateString('es-MX', { day: 'numeric', month: 'long', year: 'numeric' });
}

/** 1,284 */
export function numero(valor) {
    if (valor === null || valor === undefined || valor === '') return '—';
    const n = Number(valor);
    return Number.isNaN(n) ? String(valor) : n.toLocaleString('es-MX');
}

const relativo = new Intl.RelativeTimeFormat('es-MX', { numeric: 'auto' });

const UNIDADES = [
    ['year', 31536000],
    ['month', 2592000],
    ['day', 86400],
    ['hour', 3600],
    ['minute', 60],
];

/** "hace 3 horas" */
export function hace(valor) {
    const f = aFecha(valor);
    if (!f) return '';
    const segundos = (f - new Date()) / 1000;

    for (const [unidad, tamano] of UNIDADES) {
        if (Math.abs(segundos) >= tamano) {
            return relativo.format(Math.round(segundos / tamano), unidad);
        }
    }

    return 'hace un momento';
}
