<script setup>
/**
 * Botón destructivo: eliminar, fusionar, revertir. Siempre va detrás de una
 * confirmación que nombra qué se va a borrar.
 */
defineProps({
    type: {
        type: String,
        default: 'button',
    },
    procesando: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <button
        :type="type"
        :disabled="procesando || undefined"
        :aria-busy="procesando ? 'true' : undefined"
        class="inline-flex h-10 items-center justify-center gap-2 rounded-md border border-transparent bg-danger-fill px-4 text-body-strong text-white transition hover:brightness-110 focus:outline-none focus-visible:ring-2 focus-visible:ring-focus focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60"
    >
        <slot />
    </button>
</template>
