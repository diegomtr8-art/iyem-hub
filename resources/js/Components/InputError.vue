<script setup>
/**
 * Mensaje de error de un campo: ícono + texto en danger. Cuando hay error,
 * reemplaza a la ayuda del campo. El `id` permite enlazarlo con
 * aria-describedby desde el control.
 */
defineProps({
    message: String,
    id: {
        type: String,
        default: undefined,
    },
});
</script>

<template>
    <p v-if="message" :id="id" class="flex items-start gap-1.5 text-small text-danger">
        <svg class="mt-px h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
        </svg>
        <span>{{ message }}</span>
    </p>
</template>
