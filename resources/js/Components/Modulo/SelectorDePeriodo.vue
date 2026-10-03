<script setup>
import { ref, watch } from 'vue';

/**
 * El control principal de un tablero: de qué periodo son las cifras.
 *
 * Los predefinidos aplican al tocarlos; el personalizado espera al botón,
 * para no consultar con una fecha a medio escribir. Quien lo usa decide la
 * URL (el periodo viaja en la query string).
 */
const props = defineProps({
    periodo: { type: Object, required: true },
    opciones: { type: Array, required: true },
    errores: { type: Object, default: () => ({}) },
});

const emit = defineEmits(['cambiar']);

const clave = ref(props.periodo.clave);
const desde = ref(props.periodo.desde);
const hasta = ref(props.periodo.hasta);

watch(() => props.periodo, (nuevo) => {
    clave.value = nuevo.clave;
    desde.value = nuevo.desde;
    hasta.value = nuevo.hasta;
});

const elegir = (opcion) => {
    clave.value = opcion;
    if (opcion !== 'personalizado') emit('cambiar', { periodo: opcion });
};

const aplicarPersonalizado = () => {
    emit('cambiar', { periodo: 'personalizado', desde: desde.value, hasta: hasta.value });
};
</script>

<template>
    <div>
        <div class="flex flex-wrap gap-2" role="group" aria-label="Periodo del tablero">
            <button
                v-for="opcion in opciones"
                :key="opcion.clave"
                type="button"
                class="inline-flex h-10 items-center rounded px-3.5 text-small font-semibold transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-focus focus-visible:ring-offset-2"
                :class="clave === opcion.clave
                    ? 'bg-action text-action-ink hover:bg-action-hover'
                    : 'border border-line-strong bg-surface text-ink hover:bg-surface-50'"
                :aria-pressed="clave === opcion.clave"
                @click="elegir(opcion.clave)"
            >
                {{ opcion.nombre }}
            </button>
        </div>

        <form
            v-if="clave === 'personalizado'"
            class="mt-3 flex flex-wrap items-end gap-3"
            @submit.prevent="aplicarPersonalizado"
        >
            <label class="flex flex-col gap-1">
                <span class="text-caption text-ink-600">Desde</span>
                <input
                    v-model="desde"
                    type="date"
                    required
                    class="h-10 rounded border-line-strong bg-surface text-body text-ink focus:border-action focus:ring-focus"
                    :aria-invalid="!!errores.desde"
                    :aria-describedby="errores.desde ? 'error-periodo' : undefined"
                >
            </label>
            <label class="flex flex-col gap-1">
                <span class="text-caption text-ink-600">Hasta</span>
                <input
                    v-model="hasta"
                    type="date"
                    required
                    :min="desde"
                    class="h-10 rounded border-line-strong bg-surface text-body text-ink focus:border-action focus:ring-focus"
                    :aria-invalid="!!errores.hasta"
                    :aria-describedby="errores.hasta ? 'error-periodo' : undefined"
                >
            </label>
            <button
                type="submit"
                class="inline-flex h-10 items-center rounded bg-action px-4 text-small font-semibold text-action-ink hover:bg-action-hover focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-focus focus-visible:ring-offset-2"
            >
                Aplicar
            </button>
        </form>

        <p v-if="errores.desde || errores.hasta" id="error-periodo" class="mt-2 text-small text-danger" role="alert">
            {{ errores.desde ?? errores.hasta }}
        </p>
    </div>
</template>
