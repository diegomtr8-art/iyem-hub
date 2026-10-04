import './bootstrap';
import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import { token } from './paletaDatos';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: {
        // Índigo del logotipo IYEM ERP, leído de la variable CSS (con
        // respaldo en paletaDatos.js si la hoja aún no cargó).
        color: token('--brand-500'),
    },
});

/*
 * Registro del service worker.
 *
 * Solo en producción: en desarrollo, un worker sirviendo assets guardados
 * pelearía con el recarga-en-caliente de Vite y daría depuraciones falsas.
 * El worker solo cachea assets con huella de contenido; nunca HTML ni
 * respuestas de la API (ver public/sw.js).
 */
if ('serviceWorker' in navigator && import.meta.env.PROD) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(() => {
            // Sin service worker la aplicación funciona igual: solo se pierde
            // la instalación y el arranque en frío más rápido.
        });
    });
}
