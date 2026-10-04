import { readonly, ref } from 'vue';

/*
 * Tema claro / oscuro.
 *
 * El tema inicial ya lo aplicó el script en línea de app.blade.php antes de
 * pintar (así no hay destello blanco). Este composable solo lo lee de
 * <html data-theme>, lo alterna y recuerda la elección en localStorage, que
 * es lo único que la plataforma guarda ahí.
 *
 * El estado es de módulo: todos los interruptores de la página comparten el
 * mismo valor.
 */

const CLAVE = 'tema';

const leerInicial = () => {
    if (typeof document === 'undefined') return 'light';
    const actual = document.documentElement.getAttribute('data-theme');
    if (actual === 'dark' || actual === 'light') return actual;
    try {
        return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    } catch {
        return 'light';
    }
};

const tema = ref(leerInicial());

const aplicar = (valor) => {
    tema.value = valor;
    document.documentElement.setAttribute('data-theme', valor);
    try {
        localStorage.setItem(CLAVE, valor);
    } catch {
        // Ventana privada o almacenamiento bloqueado: el tema se aplica igual,
        // solo no se recuerda para la próxima visita.
    }
};

export function useTema() {
    const alternar = () => aplicar(tema.value === 'dark' ? 'light' : 'dark');

    return { tema: readonly(tema), alternar };
}

/**
 * Llama a `alCambiar` cada vez que cambia el tema de la página. Lo usan
 * Chart.js y Leaflet, que pintan con colores de JavaScript y no heredan CSS.
 * Devuelve la función para dejar de escuchar.
 */
export function observarTema(alCambiar) {
    const observador = new MutationObserver(() => alCambiar(document.documentElement.getAttribute('data-theme')));
    observador.observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });
    return () => observador.disconnect();
}
