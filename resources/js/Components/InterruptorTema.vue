<script setup>
import { computed } from 'vue';
import { useTema } from '@/Composables/useTema';

/**
 * Botón de tema claro / oscuro. `aria-pressed` indica si el oscuro está
 * activo; la etiqueta dice qué hace el botón, no el estado actual.
 *
 * Sobre fondos brand-900 se coloca dentro de un contenedor con
 * data-theme="dark": los tokens ya dan el contraste correcto.
 */
const { tema, alternar } = useTema();
const oscuro = computed(() => tema.value === 'dark');
</script>

<template>
    <button
        type="button"
        class="toque-minimo inline-flex items-center justify-center rounded-md transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-focus focus-visible:ring-offset-2 text-ink-600 hover:bg-surface-100 hover:text-ink"
        :aria-pressed="oscuro ? 'true' : 'false'"
        :aria-label="oscuro ? 'Cambiar a tema claro' : 'Cambiar a tema oscuro'"
        :title="oscuro ? 'Tema claro' : 'Tema oscuro'"
        @click="alternar"
    >
        <!-- Sol: se muestra en oscuro, lleva al claro. -->
        <svg v-if="oscuro" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" />
        </svg>
        <!-- Luna: se muestra en claro, lleva al oscuro. -->
        <svg v-else class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z" />
        </svg>
    </button>
</template>
