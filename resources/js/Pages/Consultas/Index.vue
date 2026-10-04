<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import IconoNav from '@/Components/IconoNav.vue';

defineProps({
    catalogo: { type: Array, default: () => [] },
});
</script>

<template>
    <AppLayout title="Consultas 360°">
        <template #header>
            <span>Consultas 360°</span>
        </template>

        <div class="mx-auto max-w-7xl">
            <section aria-labelledby="titulo-consultas">
                <h1 id="titulo-consultas" class="text-display-lg text-ink">
                    Consultas 360°
                </h1>
                <p class="mt-2 max-w-2xl text-body text-ink-600">
                    Cada consulta cruza la información de los módulos usando el padrón central
                    como punto de encuentro. Los filtros viajan en la dirección web, así que
                    cualquier resultado se comparte copiando el enlace.
                </p>
            </section>

            <ul v-if="catalogo.length" class="mt-8 grid gap-6 sm:grid-cols-2 xl:grid-cols-3" role="list">
                <li v-for="consulta in catalogo" :key="consulta.clave" class="flex">
                    <Link
                        :href="route('consultas.index', { consulta: consulta.clave })"
                        class="group flex w-full flex-col rounded-lg border border-line bg-surface p-5 shadow-sm transition-colors hover:border-line-strong hover:bg-surface-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-focus focus-visible:ring-offset-2"
                    >
                        <span class="flex h-10 w-10 items-center justify-center rounded-md bg-surface-brand text-brand-500 dark:text-brand-300">
                            <IconoNav :icono="consulta.icono" class="h-6 w-6" />
                        </span>

                        <h2 class="mt-4 text-subtitle text-ink">
                            {{ consulta.titulo }}
                        </h2>
                        <p class="mt-1 flex-1 text-small text-ink-600">
                            {{ consulta.descripcion }}
                        </p>

                        <span class="mt-4 inline-flex items-center gap-1.5 border-t border-line pt-4 text-body-strong text-action group-hover:underline">
                            Abrir consulta
                            <IconoNav icono="arrow" class="h-4 w-4" />
                        </span>
                    </Link>
                </li>
            </ul>

            <div v-else class="mt-8 rounded-lg border border-line bg-surface p-6">
                <p class="text-body-strong text-ink">No hay consultas disponibles para tu cuenta.</p>
                <p class="mt-1 text-small text-ink-600">
                    Pide al administrador de la plataforma acceso a las consultas que necesitas.
                </p>
            </div>
        </div>
    </AppLayout>
</template>
