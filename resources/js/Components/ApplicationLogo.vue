<script setup>
import MarcaErp from '@/Components/MarcaErp.vue';

/** Compatibilidad con Jetstream: el logotipo de la aplicación es la marca IYEM ERP. */
defineProps({
    variant: {
        type: String,
        default: 'normal', // 'normal' | 'blanco'
    },
});
</script>

<template>
    <MarcaErp tamano="sm" :sobre-oscuro="variant === 'blanco'" />
</template>
