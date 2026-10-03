<script setup>
import { computed } from 'vue';

/**
 * Badge del estado de un módulo.
 *
 * Ícono + texto siempre: el color es el tercer refuerzo, nunca el único.
 * El fondo es el tinte del estado y el texto va en ink-900 (texto de color
 * sobre su propio tinte no alcanza contraste); el ícono sí lleva el color.
 */
const props = defineProps({
    estado: {
        type: String,
        default: 'produccion',
    },
});

const estilos = {
    produccion: {
        texto: 'En operación',
        fondo: 'bg-success-surface',
        icono: 'text-success',
        trazo: 'M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    },
    beta: {
        texto: 'En pruebas',
        fondo: 'bg-warning-surface',
        icono: 'text-warning',
        trazo: 'M9.75 3h4.5M10.5 3v6.75L5.25 18a2.25 2.25 0 001.95 3.375h9.6A2.25 2.25 0 0018.75 18L13.5 9.75V3',
    },
    desarrollo: {
        texto: 'En desarrollo',
        fondo: 'bg-surface-brand',
        icono: 'text-action',
        trazo: 'M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085',
    },
    planeado: {
        texto: 'Planeado',
        fondo: 'bg-surface-100',
        icono: 'text-ink-400',
        trazo: 'M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z',
    },
};

const estilo = computed(() => estilos[props.estado] ?? estilos.planeado);
</script>

<template>
    <span
        class="inline-flex h-6 shrink-0 items-center gap-1 rounded-sm px-2 text-caption text-ink"
        :class="estilo.fondo"
    >
        <svg class="h-3.5 w-3.5 shrink-0" :class="estilo.icono" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" :d="estilo.trazo" />
        </svg>
        {{ estilo.texto }}
    </span>
</template>
