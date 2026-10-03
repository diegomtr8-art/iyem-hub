<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import IconoNav from '@/Components/IconoNav.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { observarTema } from '@/Composables/useTema';
import { token } from '@/paletaDatos';
import { numero } from '@/formato';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const props = defineProps({
    personas: { type: Array, default: () => [] },
    filtros: { type: Object, default: () => ({}) },
    etiquetasDisponibles: { type: Array, default: () => [] },
    modulosDisponibles: { type: Array, default: () => [] },
});

const mapaEl = ref(null);
const seleccionada = ref(null);
let mapa = null;
let capa = null;
let teselas = null;
let dejarDeObservar = null;

/* Límites aproximados del estado: el mapa no deja salir de esta caja. */
const LIMITES_YUCATAN = [
    [19.4, -90.6],
    [22.0, -87.3],
];
const CENTRO_MERIDA = [20.9674, -89.5926];

/*
 * A partir de este nivel de acercamiento se dibuja una persona por punto;
 * por debajo, un círculo por municipio con el conteo adentro.
 *
 * Se agrupa a mano en vez de traer leaflet.markercluster: el padrón se
 * reparte entre poco más de cien municipios, así que agrupar por municipio
 * da un mapa más legible —y más útil para el instituto— que el agrupamiento
 * geométrico de la librería, sin sumar 40 KB de dependencia.
 */
const ZOOM_DETALLE = 11;

const filtroEtiqueta = ref(props.filtros.etiqueta ?? '');
const filtroModulo = ref(props.filtros.modulo ?? '');

const aplicarFiltros = () => {
    router.get(
        route('padron.mapa'),
        { etiqueta: filtroEtiqueta.value, modulo: filtroModulo.value },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

watch([filtroEtiqueta, filtroModulo], aplicarFiltros);

const limpiarFiltros = () => {
    filtroEtiqueta.value = '';
    filtroModulo.value = '';
};

const hayFiltros = computed(() => filtroEtiqueta.value !== '' || filtroModulo.value !== '');

/** Personas agrupadas por municipio, con el centroide del grupo. */
const municipios = computed(() => {
    const grupos = new Map();

    for (const persona of props.personas) {
        const clave = persona.municipio || 'Sin municipio';
        if (!grupos.has(clave)) grupos.set(clave, []);
        grupos.get(clave).push(persona);
    }

    return [...grupos.entries()].map(([nombre, personas]) => ({
        nombre,
        personas,
        total: personas.length,
        lat: personas.reduce((s, p) => s + Number(p.latitud), 0) / personas.length,
        lng: personas.reduce((s, p) => s + Number(p.longitud), 0) / personas.length,
    }));
});

const municipiosOrdenados = computed(() => [...municipios.value].sort((a, b) => b.total - a.total));

/** Diámetro del círculo del municipio, según cuánta gente agrupa. */
const radioCluster = (total) => Math.min(46, 22 + Math.log2(total + 1) * 5);

/*
 * Teselas: las mismas de OpenStreetMap que ya usaba la plataforma, sin
 * agregar otro proveedor (los mapas base oscuros de terceros piden clave de
 * API). En tema oscuro la capa se oscurece con un filtro CSS (ver <style>),
 * que no toca los marcadores: esos se repintan con los tokens del tema.
 */
function ponerTeselas() {
    if (!mapa || teselas) return;

    teselas = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        className: 'teselas-padron',
        minZoom: 7,
        maxZoom: 18,
    }).addTo(mapa);
}

/*
 * Marcadores. Los colores se leen de las variables CSS en cada dibujo, así
 * que al cambiar de tema basta con volver a dibujar.
 *
 * Activa = punto relleno en brand-600; cualquier otro estado = punto hueco
 * con borde ink-400. La forma distingue el estado, no solo el color.
 */
function estiloPersona(persona) {
    const activa = persona.estado_persona === 'activa';

    return activa
        ? { radius: 7, color: token('--surface-000'), weight: 1.5, fillColor: token('--brand-600'), fillOpacity: 0.95 }
        : { radius: 6, color: token('--ink-400'), weight: 2, fillColor: token('--surface-000'), fillOpacity: 1 };
}

function dibujar() {
    if (!mapa) return;

    capa?.remove();
    capa = L.layerGroup().addTo(mapa);

    const detalle = mapa.getZoom() >= ZOOM_DETALLE;

    if (detalle) {
        for (const persona of props.personas) {
            L.circleMarker([persona.latitud, persona.longitud], estiloPersona(persona))
                .on('click', () => { seleccionada.value = persona; })
                .addTo(capa);
        }

        return;
    }

    for (const municipio of municipios.value) {
        const lado = radioCluster(municipio.total);

        L.marker([municipio.lat, municipio.lng], {
            icon: L.divIcon({
                className: '',
                html: `<div class="cluster-municipio" style="width:${lado}px;height:${lado}px">
                           <span>${municipio.total}</span>
                       </div>`,
                iconSize: [lado, lado],
                iconAnchor: [lado / 2, lado / 2],
            }),
            title: `${municipio.nombre}: ${municipio.total} persona${municipio.total === 1 ? '' : 's'}`,
        })
            .on('click', () => {
                mapa.flyTo([municipio.lat, municipio.lng], ZOOM_DETALLE, { duration: 0.6 });
            })
            .addTo(capa);
    }
}

const alCambiarTema = () => {
    ponerTeselas();
    dibujar();
};

onMounted(() => {
    mapa = L.map(mapaEl.value, {
        center: CENTRO_MERIDA,
        zoom: 8,
        minZoom: 7,
        maxBounds: LIMITES_YUCATAN,
        maxBoundsViscosity: 1.0,
    });

    ponerTeselas();

    mapa.setMaxBounds(LIMITES_YUCATAN);
    mapa.on('zoomend', dibujar);

    dibujar();

    dejarDeObservar = observarTema(alCambiarTema);
});

// Los filtros llegan por Inertia y reemplazan `personas` sin remontar el
// componente, así que hay que volver a dibujar la capa a mano.
watch(() => props.personas, () => {
    seleccionada.value = null;
    dibujar();
});

onUnmounted(() => {
    dejarDeObservar?.();
    mapa?.off('zoomend', dibujar);
    mapa?.remove();
    mapa = null;
});

const claseSelect = 'h-10 w-full rounded-md border-line-strong bg-surface text-body text-ink focus:border-focus focus:ring-2 focus:ring-focus focus:ring-offset-2';
</script>

<template>
    <AppLayout title="Mapa del padrón">
        <template #header>
            <div class="flex min-w-0 items-center gap-2">
                <Link
                    :href="route('padron.index')"
                    class="toque-minimo -ml-2 flex shrink-0 items-center justify-center rounded-md text-ink-600 transition-colors hover:text-ink focus:outline-none focus-visible:ring-2 focus-visible:ring-focus"
                    aria-label="Volver al padrón"
                >
                    <IconoNav icono="arrow" class="h-5 w-5 rotate-180" />
                </Link>
                <span>Mapa del padrón</span>
            </div>
        </template>

        <div class="mx-auto max-w-7xl">
            <h1 class="text-display-lg text-ink">Mapa del padrón</h1>
            <p class="mt-1 text-small text-ink-600">
                <span class="font-mono">{{ numero(personas.length) }}</span> persona{{ personas.length === 1 ? '' : 's' }}
                con ubicación en <span class="font-mono">{{ numero(municipios.length) }}</span> municipio{{ municipios.length === 1 ? '' : 's' }}.
            </p>

            <!-- Filtros -->
            <div class="mt-6 flex flex-col gap-4 rounded-lg border border-line bg-surface-50 p-4 md:flex-row md:items-end">
                <div class="md:w-64">
                    <label for="filtro-etiqueta" class="block text-body-strong text-ink">Etiqueta</label>
                    <select id="filtro-etiqueta" v-model="filtroEtiqueta" :class="[claseSelect, 'mt-1.5']">
                        <option value="">Todas las etiquetas</option>
                        <option v-for="etiqueta in etiquetasDisponibles" :key="etiqueta" :value="etiqueta">{{ etiqueta }}</option>
                    </select>
                </div>

                <div class="md:w-64">
                    <label for="filtro-modulo" class="block text-body-strong text-ink">Módulo de origen</label>
                    <select id="filtro-modulo" v-model="filtroModulo" :class="[claseSelect, 'mt-1.5']">
                        <option value="">Todos los módulos</option>
                        <option v-for="modulo in modulosDisponibles" :key="modulo.slug" :value="modulo.slug">{{ modulo.nombre }}</option>
                    </select>
                </div>

                <SecondaryButton v-if="hayFiltros" @click="limpiarFiltros">
                    Limpiar filtros
                </SecondaryButton>
            </div>

            <!--
                Mapa y panel lateral. En iPad horizontal y escritorio van lado a
                lado; en teléfono el panel cae debajo del mapa.
            -->
            <div class="mt-6 grid gap-6 lg:grid-cols-[1fr_20rem]">
                <div class="mapa-padron relative overflow-hidden rounded-lg border border-line bg-surface-50 shadow-sm">
                    <div ref="mapaEl" class="h-[55dvh] w-full lg:h-[70dvh]" role="region" aria-label="Mapa de personas del padrón por municipio" />

                    <!-- Leyenda: forma + texto, el color no es la única señal. -->
                    <div class="pointer-events-none absolute bottom-3 left-3 z-[400] max-w-[calc(100%-1.5rem)] rounded-md border border-line bg-surface px-3 py-2 text-caption text-ink-600 shadow-md">
                        <p class="text-ink">Acerca el mapa para ver persona por persona.</p>
                        <ul class="mt-1.5 flex flex-wrap gap-x-4 gap-y-1">
                            <li class="flex items-center gap-1.5">
                                <span class="h-2.5 w-2.5 rounded-full bg-brand-600" aria-hidden="true" />
                                Activa
                            </li>
                            <li class="flex items-center gap-1.5">
                                <span class="h-2.5 w-2.5 rounded-full border-2 border-ink-400 bg-surface" aria-hidden="true" />
                                Otro estado
                            </li>
                            <li class="flex items-center gap-1.5">
                                <span class="flex h-4 w-4 items-center justify-center rounded-full bg-brand-600 font-mono text-[9px] text-ink-inverse dark:bg-brand-400" aria-hidden="true">n</span>
                                Personas por municipio
                            </li>
                        </ul>
                    </div>
                </div>

                <aside class="rounded-lg border border-line bg-surface p-5 shadow-sm" aria-live="polite">
                    <template v-if="seleccionada">
                        <div class="flex items-start justify-between gap-2">
                            <h3 class="text-title text-ink">{{ seleccionada.nombre_completo }}</h3>
                            <button
                                type="button"
                                class="toque-minimo -mr-2 -mt-2 flex shrink-0 items-center justify-center rounded-md text-ink-600 transition-colors hover:text-ink focus:outline-none focus-visible:ring-2 focus-visible:ring-focus"
                                aria-label="Cerrar el detalle"
                                @click="seleccionada = null"
                            >
                                <IconoNav icono="close" class="h-4 w-4" />
                            </button>
                        </div>

                        <dl class="mt-4 space-y-3">
                            <div v-if="seleccionada.municipio">
                                <dt class="text-small text-ink-600">Municipio</dt>
                                <dd class="text-body text-ink">{{ seleccionada.municipio }}</dd>
                            </div>
                            <div v-if="seleccionada.telefono">
                                <dt class="text-small text-ink-600">Teléfono</dt>
                                <dd class="font-mono text-body text-ink">{{ seleccionada.telefono }}</dd>
                            </div>
                            <div v-if="seleccionada.email">
                                <dt class="text-small text-ink-600">Correo</dt>
                                <dd class="break-all text-body text-ink">{{ seleccionada.email }}</dd>
                            </div>
                            <div v-if="seleccionada.etiquetas?.length">
                                <dt class="text-small text-ink-600">Etiquetas</dt>
                                <dd class="mt-1 flex flex-wrap gap-1.5">
                                    <span
                                        v-for="e in seleccionada.etiquetas"
                                        :key="e.etiqueta"
                                        class="inline-flex h-6 items-center rounded-sm bg-surface-brand px-2 text-caption text-ink"
                                    >{{ e.etiqueta }}</span>
                                </dd>
                            </div>
                        </dl>

                        <Link
                            :href="route('padron.show', seleccionada.id)"
                            class="mt-5 inline-flex h-10 w-full items-center justify-center gap-2 rounded-md bg-action px-4 text-body-strong text-action-ink transition-colors hover:bg-action-hover focus:outline-none focus-visible:ring-2 focus-visible:ring-focus focus-visible:ring-offset-2"
                        >
                            Ver ficha completa
                            <IconoNav icono="arrow" class="h-4 w-4" />
                        </Link>
                    </template>

                    <div v-else>
                        <h3 class="text-title text-ink">Municipios con presencia</h3>
                        <ul v-if="municipios.length" class="scrollbar-fina mt-3 max-h-[45dvh] divide-y divide-line overflow-y-auto lg:max-h-[58dvh]">
                            <li
                                v-for="municipio in municipiosOrdenados"
                                :key="municipio.nombre"
                                class="flex min-h-[40px] items-center justify-between gap-2"
                            >
                                <span class="min-w-0 truncate text-body text-ink">{{ municipio.nombre }}</span>
                                <span class="shrink-0 text-number text-ink">{{ numero(municipio.total) }}</span>
                            </li>
                        </ul>
                        <div v-else class="mt-3">
                            <p class="text-body text-ink">Ninguna persona coincide con los filtros.</p>
                            <button
                                v-if="hayFiltros"
                                type="button"
                                class="mt-2 inline-flex min-h-[44px] items-center rounded-sm text-small text-action underline-offset-2 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-focus focus-visible:ring-offset-2"
                                @click="limpiarFiltros"
                            >
                                Quitar los filtros
                            </button>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </AppLayout>
</template>

<style>
/*
 * Sin `scoped`: el marcador se inyecta como HTML dentro de un divIcon de
 * Leaflet, fuera del árbol de Vue, así que un estilo con alcance no lo
 * alcanzaría. Los colores salen de los tokens del tema.
 */
.cluster-municipio {
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 9999px;
    background: var(--brand-600);
    border: 2px solid var(--surface-000);
    color: var(--ink-inverse);
    font-family: 'JetBrains Mono', ui-monospace, monospace;
    font-weight: 600;
    font-size: 12px;
    font-variant-numeric: tabular-nums;
    box-shadow: var(--shadow-md);
    transition: transform 0.15s ease;
    cursor: pointer;
}

/* Claro: brand-600 con texto blanco (5.8:1). Oscuro: brand-400 con tinta
   oscura, igual que el primario; --ink-inverse ya cambia con el tema. */
[data-theme='dark'] .cluster-municipio {
    background: var(--brand-400);
}

.cluster-municipio:hover {
    transform: scale(1.08);
}

/* Controles de Leaflet con los tokens: por omisión son blancos fijos. */
.mapa-padron .leaflet-container {
    background: var(--surface-050);
    font-family: 'Inter', ui-sans-serif, system-ui, sans-serif;
}
.mapa-padron .leaflet-bar {
    border: 1px solid var(--border-strong);
    box-shadow: var(--shadow-sm);
}
.mapa-padron .leaflet-bar a {
    background: var(--surface-000);
    color: var(--ink-900);
    border-bottom-color: var(--border);
}
.mapa-padron .leaflet-bar a:hover,
.mapa-padron .leaflet-bar a:focus-visible {
    background: var(--surface-100);
}
.mapa-padron .leaflet-bar a:focus-visible {
    outline: 2px solid var(--focus-ring);
    outline-offset: 2px;
}
.mapa-padron .leaflet-control-attribution {
    background: var(--surface-000);
    color: var(--ink-600);
}
.mapa-padron .leaflet-control-attribution a {
    color: var(--action);
}
.mapa-padron .leaflet-container {
    background: var(--surface-050);
}
/* Mapa base oscuro sin cambiar de proveedor: se invierte la luminosidad de
   las teselas y se devuelve el tono, así el agua sigue azul y las calles
   quedan claras sobre fondo oscuro. Solo afecta a la capa de teselas. */
[data-theme='dark'] .mapa-padron .teselas-padron {
    filter: invert(1) hue-rotate(180deg) brightness(0.9) contrast(0.85) saturate(0.6);
}
</style>
