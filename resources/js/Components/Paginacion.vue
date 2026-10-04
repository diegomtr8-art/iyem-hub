<script setup>
import { Link } from '@inertiajs/vue3';
import { numero } from '@/formato';

/**
 * Paginación de Laravel.
 *
 * En teléfono solo se muestran «Anterior / Siguiente» más el contador: la
 * lista completa de números no cabe en 390 px sin empujar la página a
 * scroll horizontal.
 */
defineProps({
    paginador: {
        type: Object,
        required: true,
    },
});

const claseBase = 'inline-flex items-center justify-center rounded-md text-small transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-focus focus-visible:ring-offset-2';
</script>

<template>
    <nav
        v-if="paginador.total > paginador.per_page"
        class="mt-4 flex flex-wrap items-center justify-between gap-3"
        aria-label="Paginación"
    >
        <p class="text-small text-ink-600">
            <span class="font-mono">{{ numero(paginador.from ?? 0) }}–{{ numero(paginador.to ?? 0) }}</span>
            de <span class="font-mono">{{ numero(paginador.total) }}</span>
        </p>

        <!-- Teléfono: solo anterior y siguiente -->
        <div class="flex gap-2 sm:hidden">
            <Link
                v-for="enlace in [
                    { url: paginador.prev_page_url, texto: 'Anterior' },
                    { url: paginador.next_page_url, texto: 'Siguiente' },
                ]"
                :key="enlace.texto"
                :href="enlace.url || '#'"
                preserve-scroll
                :class="[claseBase, 'toque-minimo border border-line-strong bg-surface px-4 font-medium text-ink hover:bg-surface-100', !enlace.url ? 'pointer-events-none opacity-50' : '']"
                :aria-disabled="!enlace.url ? 'true' : undefined"
            >
                {{ enlace.texto }}
            </Link>
        </div>

        <!-- Tablet y escritorio: numeración completa -->
        <div class="hidden flex-wrap gap-1 sm:flex">
            <Link
                v-for="(enlace, i) in paginador.links"
                :key="i"
                :href="enlace.url || '#'"
                preserve-scroll
                :class="[
                    claseBase,
                    'h-9 min-w-[2.25rem] px-3 font-mono',
                    enlace.active ? 'bg-action font-semibold text-action-ink' : 'text-ink-600 hover:bg-surface-100 hover:text-ink',
                    !enlace.url ? 'pointer-events-none opacity-50' : '',
                ]"
                :aria-current="enlace.active ? 'page' : undefined"
                v-html="enlace.label"
            />
        </div>
    </nav>
</template>
