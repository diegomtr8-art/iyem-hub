<script setup>
/**
 * Palabra marca del IYEM ERP.
 *
 * PROVISIONAL: mientras diseño entrega el SVG recortado del logotipo, la
 * marca se compone en Archivo con la misma estructura del original: "IYEM"
 * en tono neutro y "ERP" en índigo.
 *
 * `sobreOscuro` es para fondos brand-900: ahí "ERP" va en brand-300, porque
 * brand-500 sobre brand-900 no alcanza contraste.
 */
defineProps({
    sobreOscuro: {
        type: Boolean,
        default: false,
    },
    tamano: {
        type: String,
        default: 'md', // 'sm' | 'md' | 'lg'
    },
});

const tamanos = {
    sm: 'text-[18px] leading-none',
    md: 'text-[24px] leading-none',
    lg: 'text-[40px] leading-none',
};
</script>

<template>
    <span
        class="inline-flex select-none items-baseline font-display font-bold tracking-[0.04em]"
        :class="tamanos[tamano] ?? tamanos.md"
        role="img"
        aria-label="IYEM ERP"
    >
        <span aria-hidden="true" :class="sobreOscuro ? 'text-white' : 'text-ink'">IYEM</span>
        <span aria-hidden="true" :class="sobreOscuro ? 'text-brand-300' : 'text-brand-500'">ERP</span>
    </span>
</template>
