<script setup>
import { computed } from 'vue';

/**
 * Badge del estado de una persona del padrón (activa, inactiva, bloqueada).
 * Mismo patrón que BadgeEstado: tinte del estado, texto ink-900 e ícono en
 * el color del estado. El color nunca es la única señal.
 */
const props = defineProps({
    estado: {
        type: String,
        default: 'activa',
    },
});

const estilos = {
    activa: {
        texto: 'Activa',
        fondo: 'bg-success-surface',
        icono: 'text-success',
        trazo: 'M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    },
    inactiva: {
        texto: 'Inactiva',
        fondo: 'bg-surface-100',
        icono: 'text-ink-400',
        trazo: 'M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z',
    },
    bloqueada: {
        texto: 'Bloqueada',
        fondo: 'bg-danger-surface',
        icono: 'text-danger',
        trazo: 'M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636',
    },
};

const estilo = computed(() => estilos[props.estado] ?? { ...estilos.inactiva, texto: props.estado });
</script>

<template>
    <span class="inline-flex h-6 shrink-0 items-center gap-1 rounded-sm px-2 text-caption text-ink" :class="estilo.fondo">
        <svg class="h-3.5 w-3.5 shrink-0" :class="estilo.icono" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" :d="estilo.trazo" />
        </svg>
        {{ estilo.texto }}
    </span>
</template>
