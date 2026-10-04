<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    active: Boolean,
    href: String,
    as: String,
});

const classes = computed(() => [
    'flex min-h-[44px] w-full items-center px-4 text-start text-body font-medium transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-focus',
    props.active ? 'bg-surface-brand text-action' : 'text-ink-600 hover:bg-surface-100 hover:text-ink',
]);
</script>

<template>
    <div>
        <button v-if="as == 'button'" :class="classes">
            <slot />
        </button>

        <a v-else-if="as == 'a'" :class="classes" :href="href">
            <slot />
        </a>

        <Link v-else :href="href" :class="classes" :aria-current="active ? 'page' : undefined">
            <slot />
        </Link>
    </div>
</template>
