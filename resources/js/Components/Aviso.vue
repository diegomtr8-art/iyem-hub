<script setup>
import { computed } from 'vue';

/**
 * Mensaje en línea: confirmaciones, advertencias y errores generales.
 * Fondo *-surface con texto ink-900 y el ícono en el color del estado.
 */
const props = defineProps({
    tipo: {
        type: String,
        default: 'info', // 'exito' | 'advertencia' | 'error' | 'info'
    },
});

const estilos = {
    exito: {
        fondo: 'bg-success-surface',
        icono: 'text-success',
        trazo: 'M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    },
    advertencia: {
        fondo: 'bg-warning-surface',
        icono: 'text-warning',
        trazo: 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z',
    },
    error: {
        fondo: 'bg-danger-surface',
        icono: 'text-danger',
        trazo: 'M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z',
    },
    info: {
        fondo: 'bg-surface-brand',
        icono: 'text-action',
        trazo: 'M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z',
    },
};

const estilo = computed(() => estilos[props.tipo] ?? estilos.info);
</script>

<template>
    <div
        class="flex items-start gap-3 rounded-md px-4 py-3 text-body text-ink"
        :class="estilo.fondo"
        :role="tipo === 'error' ? 'alert' : 'status'"
    >
        <svg class="mt-0.5 h-5 w-5 shrink-0" :class="estilo.icono" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" :d="estilo.trazo" />
        </svg>
        <div class="min-w-0 flex-1"><slot /></div>
    </div>
</template>
