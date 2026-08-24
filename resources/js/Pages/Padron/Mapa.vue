<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import IconoNav from '@/Components/IconoNav.vue';
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

const colorEstado = (estado) => (estado === 'activa' ? '#1F7A5C' : '#9ca3af');

/** Diámetro del círculo del municipio, según cuánta gente agrupa. */
const radioCluster = (total) => Math.min(46, 22 + Math.log2(total + 1) * 5);

function dibujar() {
    if (!mapa) return;

    capa?.remove();
    capa = L.layerGroup().addTo(mapa);

    const detalle = mapa.getZoom() >= ZOOM_DETALLE;

    if (detalle) {
        for (const persona of props.personas) {
            L.circleMarker([persona.latitud, persona.longitud], {
                radius: 7,
                color: '#ffffff',
                weight: 1.5,
                fillColor: colorEstado(persona.estado_persona),
                fillOpacity: 0.9,
            })
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

onMounted(() => {
    mapa = L.map(mapaEl.value, {
        center: CENTRO_MERIDA,
        zoom: 8,
        minZoom: 7,
        maxBounds: LIMITES_YUCATAN,
        maxBoundsViscosity: 1.0,
    });

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        minZoom: 7,
        maxZoom: 18,
    }).addTo(mapa);

    mapa.setMaxBounds(LIMITES_YUCATAN);
    mapa.on('zoomend', dibujar);

    dibujar();
});

// Los filtros llegan por Inertia y reemplazan `personas` sin remontar el
// componente, así que hay que volver a dibujar la capa a mano.
watch(() => props.personas, () => {
    seleccionada.value = null;
    dibujar();
});

onUnmounted(() => {
    mapa?.off('zoomend', dibujar);
    mapa?.remove();
    mapa = null;
});
</script>

<template>
    <AppLayout title="Mapa del padrón">
        <template #header>
            <div class="flex min-w-0 items-center gap-2">
                <Link
                    :href="route('padron.index')"
                    class="shrink-0 text-gray-400 transition hover:text-iyem-primario"
                    aria-label="Volver al padrón"
                >
                    <IconoNav icono="arrow" class="h-5 w-5 rotate-180" />
                </Link>
                <span class="truncate">Mapa del padrón</span>
            </div>
        </template>

        <!-- Filtros -->
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
            <select
                v-model="filtroEtiqueta"
                aria-label="Filtrar por etiqueta"
                class="h-11 rounded-lg border-gray-300 text-sm shadow-sm focus:border-iyem-secundario focus:ring-iyem-secundario"
            >
                <option value="">
                    Todas las etiquetas
                </option>
                <option v-for="etiqueta in etiquetasDisponibles" :key="etiqueta" :value="etiqueta">
                    {{ etiqueta }}
                </option>
            </select>

            <select
                v-model="filtroModulo"
                aria-label="Filtrar por módulo de origen"
                class="h-11 rounded-lg border-gray-300 text-sm shadow-sm focus:border-iyem-secundario focus:ring-iyem-secundario"
            >
                <option value="">
                    Todos los módulos de origen
                </option>
                <option v-for="modulo in modulosDisponibles" :key="modulo.slug" :value="modulo.slug">
                    {{ modulo.nombre }}
                </option>
            </select>

            <button
                v-if="hayFiltros"
                type="button"
                class="toque-minimo inline-flex items-center justify-center rounded-lg px-3 text-sm font-medium text-gray-500 transition hover:text-gray-700"
                @click="limpiarFiltros"
            >
                Limpiar
            </button>

            <p class="text-sm tabular-nums text-gray-500 sm:ml-auto">
                {{ personas.length.toLocaleString('es-MX') }} persona{{ personas.length === 1 ? '' : 's' }}
                en {{ municipios.length }} municipio{{ municipios.length === 1 ? '' : 's' }}
            </p>
        </div>

        <!--
            Mapa y panel lateral. En iPad horizontal y escritorio van lado a
            lado; en teléfono el panel cae debajo del mapa.
        -->
        <div class="mt-4 grid gap-4 lg:grid-cols-[1fr_20rem]">
            <div class="relative overflow-hidden rounded-2xl border border-iyem-200 shadow-soft">
                <div ref="mapaEl" class="h-[55dvh] w-full lg:h-[70dvh]" />

                <p class="pointer-events-none absolute bottom-3 left-3 z-[400] rounded-lg bg-white/90 px-2.5 py-1.5 text-xs text-gray-600 shadow-soft backdrop-blur-sm">
                    Acerca el mapa para ver persona por persona
                </p>
            </div>

            <aside class="rounded-2xl border border-iyem-200 bg-white p-4 shadow-soft">
                <template v-if="seleccionada">
                    <div class="flex items-start justify-between gap-2">
                        <h3 class="font-semibold text-gray-800">
                            {{ seleccionada.nombre_completo }}
                        </h3>
                        <button
                            type="button"
                            class="shrink-0 text-gray-400 transition hover:text-gray-600"
                            aria-label="Cerrar el detalle"
                            @click="seleccionada = null"
                        >
                            <IconoNav icono="close" class="h-4 w-4" />
                        </button>
                    </div>

                    <dl class="mt-3 space-y-2 text-sm">
                        <div v-if="seleccionada.municipio">
                            <dt class="text-gray-400">
                                Municipio
                            </dt>
                            <dd class="text-gray-700">
                                {{ seleccionada.municipio }}
                            </dd>
                        </div>
                        <div v-if="seleccionada.telefono">
                            <dt class="text-gray-400">
                                Teléfono
                            </dt>
                            <dd class="tabular-nums text-gray-700">
                                {{ seleccionada.telefono }}
                            </dd>
                        </div>
                        <div v-if="seleccionada.email">
                            <dt class="text-gray-400">
                                Correo
                            </dt>
                            <dd class="break-all text-gray-700">
                                {{ seleccionada.email }}
                            </dd>
                        </div>
                        <div v-if="seleccionada.etiquetas?.length">
                            <dt class="text-gray-400">
                                Etiquetas
                            </dt>
                            <dd class="mt-1 flex flex-wrap gap-1">
                                <span
                                    v-for="e in seleccionada.etiquetas"
                                    :key="e.etiqueta"
                                    class="rounded-full bg-iyem-claro px-2 py-0.5 text-xs font-medium text-iyem-700"
                                >{{ e.etiqueta }}</span>
                            </dd>
                        </div>
                    </dl>

                    <Link
                        :href="route('padron.show', seleccionada.id)"
                        class="toque-minimo mt-4 inline-flex w-full items-center justify-center gap-2 rounded-lg bg-iyem-gradient px-4 text-sm font-semibold text-white shadow-soft transition hover:shadow-glow"
                    >
                        Ver ficha completa
                        <IconoNav icono="arrow" class="h-4 w-4" />
                    </Link>
                </template>

                <div v-else>
                    <h3 class="font-semibold text-gray-800">
                        Municipios con presencia
                    </h3>
                    <ul class="scrollbar-fina mt-3 max-h-[45dvh] space-y-1.5 overflow-y-auto text-sm lg:max-h-[58dvh]">
                        <li
                            v-for="municipio in [...municipios].sort((a, b) => b.total - a.total)"
                            :key="municipio.nombre"
                            class="flex items-center justify-between gap-2 rounded-lg px-2 py-1.5 transition hover:bg-iyem-50"
                        >
                            <span class="min-w-0 truncate text-gray-700">{{ municipio.nombre }}</span>
                            <span class="shrink-0 tabular-nums font-semibold text-iyem-700">{{ municipio.total }}</span>
                        </li>
                    </ul>
                    <p v-if="!municipios.length" class="mt-2 text-sm text-gray-400">
                        Ninguna persona coincide con los filtros.
                    </p>
                </div>
            </aside>
        </div>
    </AppLayout>
</template>

<style>
/*
 * Sin `scoped`: el marcador se inyecta como HTML dentro de un divIcon de
 * Leaflet, fuera del árbol de Vue, así que un estilo con alcance no lo
 * alcanzaría.
 */
.cluster-municipio {
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 9999px;
    background: linear-gradient(135deg, rgba(105, 28, 50, 0.92), rgba(159, 34, 65, 0.92));
    color: #fff;
    font-weight: 700;
    font-size: 0.75rem;
    font-variant-numeric: tabular-nums;
    box-shadow: 0 0 0 4px rgba(159, 34, 65, 0.22);
    transition: transform 0.15s ease;
    cursor: pointer;
}

.cluster-municipio:hover {
    transform: scale(1.08);
}
</style>
