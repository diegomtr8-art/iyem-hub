/*
 * Service worker del IYEM Hub.
 *
 * Deliberadamente conservador: solo cachea assets con huella de contenido
 * (`/build/...`, que Vite renombra en cada compilación) y los iconos.
 *
 * NO cachea HTML, respuestas de la API ni nada que dependa de la sesión.
 * En una plataforma con datos personales y varios roles, servir una página
 * guardada podría enseñarle a un usuario lo que vio el anterior, o dejar
 * viva una sesión ya cerrada. El ahorro no vale ese riesgo.
 */

const VERSION = 'iyem-hub-v1';
const CACHE_ASSETS = `${VERSION}-assets`;

/** Recursos que valen la pena tener listos desde la instalación. */
const PRECARGA = [
    '/favicon-192x192.png',
    '/icono-512.png',
    '/icono-maskable-512.png',
    '/apple-touch-icon.png',
];

self.addEventListener('install', (evento) => {
    evento.waitUntil(
        caches
            .open(CACHE_ASSETS)
            // addAll falla en bloque si un solo recurso no está; se piden uno
            // por uno para que un icono faltante no impida la instalación.
            .then((cache) => Promise.allSettled(PRECARGA.map((url) => cache.add(url))))
            .then(() => self.skipWaiting()),
    );
});

self.addEventListener('activate', (evento) => {
    evento.waitUntil(
        caches
            .keys()
            .then((claves) =>
                Promise.all(
                    claves
                        .filter((clave) => !clave.startsWith(VERSION))
                        .map((clave) => caches.delete(clave)),
                ),
            )
            .then(() => self.clients.claim()),
    );
});

/** ¿Este recurso se puede guardar sin arriesgar datos de sesión? */
function esCacheable(url) {
    if (url.origin !== self.location.origin) return false;

    return (
        url.pathname.startsWith('/build/')
        || /\.(png|svg|ico|webp|woff2?)$/i.test(url.pathname)
    );
}

self.addEventListener('fetch', (evento) => {
    const peticion = evento.request;

    if (peticion.method !== 'GET') return;

    const url = new URL(peticion.url);

    if (!esCacheable(url)) return; // Pasa de largo: red directa, sin caché.

    evento.respondWith(
        caches.match(peticion).then((guardada) => {
            if (guardada) return guardada;

            return fetch(peticion).then((respuesta) => {
                // Solo se guarda lo que llegó bien y del mismo origen.
                if (respuesta.ok && respuesta.type === 'basic') {
                    const copia = respuesta.clone();
                    caches.open(CACHE_ASSETS).then((cache) => cache.put(peticion, copia));
                }

                return respuesta;
            });
        }),
    );
});

/*
 * Permite que la aplicación fuerce la actualización del worker tras un
 * despliegue, sin esperar a que el usuario cierre todas las pestañas.
 */
self.addEventListener('message', (evento) => {
    if (evento.data === 'actualizar') self.skipWaiting();
});
