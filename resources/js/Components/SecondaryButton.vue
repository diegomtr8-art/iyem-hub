<script setup>
/** Botón secundario: Cancelar, Exportar, Filtrar. */
defineProps({
    type: {
        type: String,
        default: 'button',
    },
});
</script>

<template>
    <button
        :type="type"
        class="inline-flex h-10 items-center justify-center gap-2 rounded-md border border-line-strong bg-surface px-4 text-body-strong text-ink transition-colors hover:bg-surface-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-focus focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60"
    >
        <slot />
    </button>
</template>
