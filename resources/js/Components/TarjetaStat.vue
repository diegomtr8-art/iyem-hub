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
    <div class="flex items-center gap-4 rounded-2xl border border-iyem-claro bg-white p-5 shadow-soft">
        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-iyem-gradient text-white shadow-glow">
            <IconoNav :icono="icono" />
        </div>
        <div>
            <p class="text-sm text-gray-500">
                {{ etiqueta }}
            </p>
            <p class="mt-0.5 text-2xl font-bold text-gray-800">
                {{ valor }}
            </p>
        </div>
    </div>
</template>
