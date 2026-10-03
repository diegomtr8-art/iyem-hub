/*
 * Paleta de datos para Chart.js y Leaflet.
 *
 * Esas librerías pintan con colores que se les pasan en JavaScript y no
 * heredan CSS, así que aquí se leen de las variables de tokens.css en el
 * momento de dibujar. Hay que volver a llamarlas al cambiar de tema (ver
 * `observarTema` en Composables/useTema.js).
 *
 * Las cinco series se distinguen por luminosidad, no solo por tono: índigo
 * oscuro, índigo claro, ámbar, verde y gris. Así se separan también para
 * quien no distingue rojo de verde.
 */

const SERIES = ['--brand-600', '--brand-300', '--warning', '--success', '--ink-400'];

// Respaldo por si el CSS aún no cargó (tema claro).
const RESPALDO = {
    '--brand-600': '#4a56d6',
    '--brand-300': '#9fa7f9',
    '--warning': '#8a5200',
    '--success': '#146138',
    '--ink-400': '#6e7490',
    '--ink-600': '#4d5370',
    '--ink-900': '#171a2b',
    '--border': '#d7dae8',
    '--surface-000': '#ffffff',
    '--surface-inverse': '#171a2b',
    '--ink-inverse': '#ffffff',
};

export function token(nombre) {
    if (typeof document === 'undefined') return RESPALDO[nombre] ?? '';
    const valor = getComputedStyle(document.documentElement).getPropertyValue(nombre).trim();
    return valor || RESPALDO[nombre] || '';
}

/** Las cinco series, en orden. */
export function seriesDatos() {
    return SERIES.map(token);
}

/** Colores de soporte: ejes, rejilla, texto y tooltip. */
export function coloresGrafica() {
    return {
        texto: token('--ink-600'),
        textoFuerte: token('--ink-900'),
        rejilla: token('--border'),
        lienzo: token('--surface-000'),
        tooltipFondo: token('--surface-inverse'),
        tooltipTexto: token('--ink-inverse'),
    };
}

export function temaActual() {
    if (typeof document === 'undefined') return 'light';
    return document.documentElement.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
}
