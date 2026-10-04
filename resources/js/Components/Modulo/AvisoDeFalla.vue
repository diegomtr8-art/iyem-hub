<script setup>
import IconoNav from '@/Components/IconoNav.vue';
import { momento } from '@/Components/Modulo/formato.js';

/**
 * Por qué no hay datos de un módulo.
 *
 * Siempre un aviso explícito, nunca un cero ni una tarjeta vacía. En
 * advertencia y no en rojo: que la API de un módulo no conteste no es un
 * error de quien está mirando.
 */
defineProps({
    falla: { type: Object, required: true },
    compacto: { type: Boolean, default: false },
    reintentando: { type: Boolean, default: false },
});

defineEmits(['reintentar']);
</script>

<template>
    <div
        class="flex gap-3 rounded-sm bg-warning-surface text-ink"
        :class="compacto ? 'p-3' : 'p-4'"
        role="status"
    >
        <IconoNav icono="alerta" class="mt-0.5 h-5 w-5 shrink-0 text-warning" />

        <div class="min-w-0 flex-1">
            <p class="text-body-strong">{{ falla.mensaje }}</p>
            <p v-if="falla.accion && !compacto" class="mt-1 text-small text-ink-600">
                {{ falla.accion }}
            </p>
            <p v-if="falla.ultimo_dato" class="mt-1 text-small text-ink-600">
                Último dato obtenido: {{ momento(falla.ultimo_dato) }}.
            </p>

            <button
                v-if="falla.reintentable"
                type="button"
                class="mt-3 inline-flex h-9 items-center gap-1.5 rounded border border-line-strong bg-surface px-3 text-small font-semibold text-ink transition-colors hover:bg-surface-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-focus focus-visible:ring-offset-2 disabled:opacity-60"
                :disabled="reintentando"
                @click="$emit('reintentar')"
            >
                <IconoNav icono="reloj" class="h-4 w-4" :class="reintentando ? 'animate-spin' : ''" />
                {{ reintentando ? 'Consultando…' : 'Reintentar' }}
            </button>
        </div>
    </div>
</template>
