<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    href: String,
    as: String,
});
</script>

<template>
    <div>
        <button v-if="as == 'button'" type="submit" class="flex min-h-[44px] w-full items-center px-4 text-start text-body text-ink transition-colors hover:bg-surface-100 focus:outline-none focus-visible:bg-surface-100 focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-focus">
            <slot />
        </button>

        <a v-else-if="as =='a'" :href="href" class="flex min-h-[44px] items-center px-4 text-body text-ink transition-colors hover:bg-surface-100 focus:outline-none focus-visible:bg-surface-100 focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-focus">
            <slot />
        </a>

        <Link v-else :href="href" class="flex min-h-[44px] items-center px-4 text-body text-ink transition-colors hover:bg-surface-100 focus:outline-none focus-visible:bg-surface-100 focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-focus">
            <slot />
        </Link>
    </div>
</template>
