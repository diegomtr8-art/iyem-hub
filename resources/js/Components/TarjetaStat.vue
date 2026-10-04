<script setup>
import IconoNav from '@/Components/IconoNav.vue';

defineProps({
    etiqueta: String,
    valor: [String, Number],
    icono: {
        type: String,
        default: 'grid',
    },
});
</script>

<template>
    <div class="flex items-center gap-4 rounded-lg border border-line bg-surface p-5 shadow-sm">
        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-surface-brand text-action">
            <IconoNav :icono="icono" />
        </span>
        <div class="min-w-0">
            <p class="text-overline text-ink-600">{{ etiqueta }}</p>
            <p class="mt-1 text-number-display text-ink">{{ valor }}</p>
        </div>
    </div>
</template>
