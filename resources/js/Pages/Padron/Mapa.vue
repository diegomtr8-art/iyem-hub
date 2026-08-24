<script setup>
import { onMounted, onUnmounted, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const props = defineProps({
    personas: Array,
});

const mapaEl = ref(null);
let mapa = null;

// Límites aproximados del estado de Yucatán: el mapa no permite salir de esta caja.
const LIMITES_YUCATAN = [
    [19.4, -90.6],
    [22.0, -87.3],
];
const CENTRO_MERIDA = [20.9674, -89.5926];

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

    props.personas.forEach((persona) => {
        const color = persona.estado_persona === 'activa' ? '#16a34a' : '#9ca3af';

        L.circleMarker([persona.latitud, persona.longitud], {
            radius: 7,
            color: '#ffffff',
            weight: 1.5,
            fillColor: color,
            fillOpacity: 0.9,
        })
            .addTo(mapa)
            .bindPopup(`
                <strong>${persona.nombre_completo}</strong><br>
                ${persona.municipio ?? ''}<br>
                ${persona.telefono ?? 'Sin teléfono'}
            `);
    });
});

onUnmounted(() => {
    mapa?.remove();
});
</script>

<template>
    <AppLayout title="Mapa del Padrón">
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Mapa del Padrón — Yucatán
                </h2>
                <Link
                    :href="route('padron.index')"
                    class="text-sm font-medium text-iyem-secundario hover:underline"
                >
                    &larr; Volver al listado
                </Link>
            </div>
        </template>

        <div class="rounded-2xl border border-iyem-claro bg-white p-2 shadow-soft">
            <div ref="mapaEl" class="h-[70vh] w-full rounded-xl" />
        </div>

        <p class="mt-3 text-sm text-gray-500">
            {{ personas.length }} persona(s) geolocalizada(s). Solo se muestran personas activas con domicilio registrado.
        </p>
    </AppLayout>
</template>
