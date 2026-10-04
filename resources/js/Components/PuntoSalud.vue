<script setup>
import { computed } from 'vue';

/**
 * Semáforo de disponibilidad de un módulo.
 *
 * Cuatro estados, no dos: mientras el sondeo viaja se muestra "Consultando",
 * y un módulo sin endpoint de salud se marca como no monitoreado. Pintarlo
 * verde por omisión sería afirmar algo que el hub no sabe.
 *
 * Punto + texto: el color nunca es la única señal. `conTexto` en false deja
 * solo el punto (con su aria-label) para espacios muy estrechos.
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
    conTexto: {
        type: Boolean,
        default: true,
    },
});

const estilos = {
    en_linea: { punto: 'bg-success', texto: 'En línea' },
    caido: { punto: 'bg-danger', texto: 'Sin respuesta' },
    sin_monitoreo: { punto: 'border-2 border-ink-400 bg-transparent', texto: 'Sin monitoreo' },
    // El sondeo mismo falló: no sabemos nada del módulo.
    sin_datos: { punto: 'border-2 border-ink-400 bg-transparent', texto: 'Sin datos' },
};

const estilo = computed(() => estilos[props.estado] ?? null);

const texto = computed(() => estilo.value?.texto ?? 'Consultando…');

const titulo = computed(() => {
    if (props.estado === 'en_linea' && props.ms) return `En línea (${props.ms} ms)`;
    return estilo.value?.texto ?? 'Consultando disponibilidad…';
});
</script>

<template>
    <span class="inline-flex shrink-0 items-center gap-1.5 text-caption text-ink-600" :title="titulo">
        <span
            class="h-2 w-2 shrink-0 rounded-full"
            :class="estilo ? estilo.punto : 'animate-pulse border-2 border-ink-400'"
            aria-hidden="true"
        />
        <span v-if="conTexto">{{ texto }}</span>
        <span v-else class="sr-only">{{ titulo }}</span>
    </span>
</template>
