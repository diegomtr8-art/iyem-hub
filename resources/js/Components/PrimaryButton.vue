<script setup>
/**
 * Botón primario: la acción principal, una por pantalla.
 * `procesando` lo bloquea mientras se envía; la etiqueta la cambia quien lo
 * usa ("Guardar cambios" → "Guardando…") para que no haya doble clic.
 */
defineProps({
    type: {
        type: String,
        default: 'submit',
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
        class="inline-flex h-10 items-center justify-center gap-2 rounded-md border border-transparent bg-action px-4 text-body-strong text-action-ink transition-colors hover:bg-action-hover focus:outline-none focus-visible:ring-2 focus-visible:ring-focus focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60"
    >
        <slot />
    </button>
</template>
