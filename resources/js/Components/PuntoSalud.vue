<script setup>
import { computed } from 'vue';

/**
 * Semáforo de disponibilidad de un módulo.
 *
 * Cuatro estados, no dos: mientras el sondeo viaja se muestra "consultando",
 * y un módulo sin endpoint de salud se marca como no monitoreado. Pintarlo
 * verde por omisión sería afirmar algo que el hub no sabe.
 */
const props = defineProps({
    estado: {
        type: String,
        default: null, // null = todavía no llega la respuesta del sondeo
    },
    ms: {
        type: Number,
        default: null,
    },
});

const estilos = {
    en_linea: { color: 'bg-iyem-exito', anillo: 'bg-iyem-exito/30', texto: 'En línea' },
    caido: { color: 'bg-iyem-error', anillo: 'bg-iyem-error/30', texto: 'Sin respuesta' },
    sin_monitoreo: { color: 'bg-gray-300', anillo: 'bg-transparent', texto: 'Sin monitoreo' },
};

const estilo = computed(() => estilos[props.estado] ?? null);

const titulo = computed(() => {
    if (!estilo.value) return 'Consultando disponibilidad…';
    if (props.estado === 'en_linea' && props.ms) return `En línea (${props.ms} ms)`;
    return estilo.value.texto;
});
</script>

<template>
    <span class="relative flex h-2.5 w-2.5 shrink-0" :title="titulo" role="img" :aria-label="titulo">
        <!-- Sin respuesta todavía: pulso neutro para que se note que se está consultando. -->
        <span
            v-if="!estilo"
            class="h-2.5 w-2.5 animate-pulse rounded-full bg-gray-300"
        />
        <template v-else>
            <span
                v-if="estado === 'en_linea'"
                class="absolute inline-flex h-full w-full animate-ping rounded-full opacity-60"
                :class="estilo.anillo"
            />
            <span class="relative inline-flex h-2.5 w-2.5 rounded-full" :class="estilo.color" />
        </template>
    </span>
</template>
