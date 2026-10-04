<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    href: String,
    active: Boolean,
});

const classes = computed(() => [
    'inline-flex min-h-[44px] items-center border-b-2 px-1 text-body font-medium transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-focus',
    props.active ? 'border-action text-ink' : 'border-transparent text-ink-600 hover:border-line-strong hover:text-ink',
]);
</script>

<template>
    <Link :href="href" :class="classes" :aria-current="active ? 'page' : undefined">
        <slot />
    </Link>
</template>
