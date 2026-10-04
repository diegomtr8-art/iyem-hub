<script setup>
import { computed } from 'vue';
import { numero } from '@/formato';

/**
 * Indicador del tablero: etiqueta en overline, cifra en display-md mono y,
 * debajo, la referencia contra la que se lee. Una cifra sola no informa:
 * si no hay periodo anterior, la referencia es la fecha del corte.
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
    referencia: {
        type: String,
        default: null,
    },
});

const valorFormateado = computed(() => (typeof props.valor === 'number' ? numero(props.valor) : props.valor));
</script>

<template>
    <div class="rounded-lg border border-line bg-surface p-4 shadow-sm sm:p-5">
        <p class="text-overline text-ink-600">{{ etiqueta }}</p>

        <p v-if="valor !== null && valor !== undefined" class="mt-2 text-number-display text-ink">
            {{ valorFormateado }}
        </p>
        <div v-else class="mt-2 h-[30px] w-20 animate-pulse rounded-sm bg-surface-100" aria-hidden="true" />

        <p v-if="referencia" class="mt-1 text-small text-ink-600">{{ referencia }}</p>
    </div>
</template>
