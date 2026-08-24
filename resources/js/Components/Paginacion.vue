<script setup>
import { Link } from '@inertiajs/vue3';

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
</script>

<template>
    <nav
        v-if="paginador.total > paginador.per_page"
        class="mt-4 flex flex-wrap items-center justify-between gap-3"
        aria-label="Paginación"
    >
        <p class="text-sm tabular-nums text-gray-500">
            {{ paginador.from?.toLocaleString('es-MX') ?? 0 }}–{{ paginador.to?.toLocaleString('es-MX') ?? 0 }}
            de {{ paginador.total.toLocaleString('es-MX') }}
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
                class="toque-minimo inline-flex items-center rounded-lg border border-iyem-200 bg-white px-4 text-sm font-medium text-gray-700 transition hover:bg-iyem-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-iyem-secundario"
                :class="!enlace.url ? 'pointer-events-none opacity-40' : ''"
                :aria-disabled="!enlace.url"
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
                class="inline-flex h-9 min-w-[2.25rem] items-center justify-center rounded-lg px-3 text-sm tabular-nums transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-iyem-secundario"
                :class="[
                    enlace.active ? 'bg-iyem-gradient text-white shadow-glow' : 'bg-white text-gray-600 hover:bg-iyem-claro',
                    !enlace.url ? 'pointer-events-none opacity-40' : '',
                ]"
                :aria-current="enlace.active ? 'page' : undefined"
                v-html="enlace.label"
            />
        </div>
    </nav>
</template>
