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
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Consultas 360°
            </h2>
        </template>

        <section class="relative overflow-hidden rounded-2xl bg-iyem-gradient shadow-soft-lg">
            <div class="pointer-events-none absolute inset-0 bg-iyem-mesh" aria-hidden="true" />
            <div class="relative px-5 py-6 sm:px-8 sm:py-7">
                <h1 class="text-xl font-bold text-white sm:text-2xl">
                    Preguntas que el instituto no podía responder
                </h1>
                <p class="mt-2 max-w-2xl text-sm text-white/75">
                    Cada consulta cruza la información de los módulos usando el padrón central
                    como punto de encuentro. Los filtros viajan en la dirección web, así que
                    cualquier resultado se comparte copiando el enlace.
                </p>
            </div>
        </section>

        <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <Link
                v-for="consulta in catalogo"
                :key="consulta.clave"
                :href="route('consultas.index', { consulta: consulta.clave })"
                class="group flex flex-col rounded-2xl border border-iyem-200 bg-white/90 p-5 shadow-soft backdrop-blur-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-iyem-300 hover:shadow-soft-lg focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-iyem-secundario focus-visible:ring-offset-2"
            >
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-iyem-gradient text-white shadow-glow">
                    <IconoNav :icono="consulta.icono" class="h-6 w-6" />
                </div>

                <h3 class="mt-4 font-semibold text-gray-800">
                    {{ consulta.titulo }}
                </h3>
                <p class="mt-1 flex-1 text-sm text-gray-500">
                    {{ consulta.descripcion }}
                </p>

                <span class="mt-4 inline-flex items-center gap-1.5 text-sm font-semibold text-iyem-700 transition-all group-hover:gap-2.5">
                    Abrir consulta
                    <IconoNav icono="arrow" class="h-4 w-4" />
                </span>
            </Link>
        </div>
    </AppLayout>
</template>
