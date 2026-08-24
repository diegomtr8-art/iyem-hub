<script setup>
import iconoIyem from '../../images/iyem-icono.png';

defineProps({
    variant: {
        type: String,
        default: 'guinda', // 'guinda' | 'blanco'
    },
});
</script>

<template>
    <div class="flex items-center gap-2.5 leading-none select-none">
        <img :src="iconoIyem" alt="" class="h-8 w-8 shrink-0">
        <div class="flex items-baseline gap-1.5">
            <span
                class="text-2xl font-extrabold tracking-tight"
                :class="variant === 'blanco' ? 'text-white' : 'text-iyem-primario'"
            >
                IYEM
            </span>
            <span
                class="text-[0.6rem] font-semibold uppercase tracking-[0.2em]"
                :class="variant === 'blanco' ? 'text-white/80' : 'text-iyem-secundario'"
            >
                Yucatán
            </span>
        </div>
    </div>
</template>
