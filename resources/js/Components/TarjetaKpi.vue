<script setup>
import { computed } from 'vue';
import IconoNav from '@/Components/IconoNav.vue';

/**
 * Indicador del encabezado del dashboard.
 *
 * Se dibuja sobre el gradiente guinda del hero, así que el vidrio es
 * translúcido en blanco y no en gris.
 */
const props = defineProps({
    etiqueta: {
        type: String,
        required: true,
    },
    valor: {
        type: [Number, String],
        default: null, // null mientras carga: se muestra el esqueleto
    },
    detalle: {
        type: String,
        default: null,
    },
    icono: {
        type: String,
        default: 'grid',
    },
});

const valorFormateado = computed(() =>
    typeof props.valor === 'number' ? props.valor.toLocaleString('es-MX') : props.valor,
);
</script>

<template>
    <div class="rounded-2xl border border-white/15 bg-white/10 p-4 backdrop-blur-sm transition-colors duration-200 hover:bg-white/15">
        <div class="flex items-center gap-2 text-white/70">
            <IconoNav :icono="icono" class="h-4 w-4" />
            <p class="text-xs font-medium uppercase tracking-wide">
                {{ etiqueta }}
            </p>
        </div>

        <p v-if="valor !== null" class="mt-2 text-2xl font-bold tabular-nums text-white sm:text-3xl">
            {{ valorFormateado }}
        </p>
        <div v-else class="mt-3 h-7 w-20 animate-pulse rounded-md bg-white/20" aria-hidden="true" />

        <p v-if="detalle" class="mt-1 text-xs text-white/60">
            {{ detalle }}
        </p>
    </div>
</template>
