<script setup>
defineProps({
    value: String,
});
</script>

<template>
    <label class="block text-body-strong text-ink">
        <span v-if="value">{{ value }}</span>
        <span v-else><slot /></span>
    </label>
</template>
