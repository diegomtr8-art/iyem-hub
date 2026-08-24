<script setup>
defineProps({
    type: {
        type: String,
        default: 'submit',
    },
});
</script>

<template>
    <button :type="type" class="inline-flex items-center rounded-lg border border-transparent bg-iyem-gradient px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white shadow-soft transition duration-150 ease-in-out hover:shadow-glow focus:outline-none focus:ring-2 focus:ring-iyem-secundario focus:ring-offset-2 active:brightness-95 disabled:opacity-50">
        <slot />
    </button>
</template>
