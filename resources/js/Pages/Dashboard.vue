<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import IconoModulo from '@/Components/IconoModulo.vue';
import IconoNav from '@/Components/IconoNav.vue';

const props = defineProps({
    modulos: Array,
    actividades: Array,
});

const page = usePage();

const formatearFecha = (fecha) => new Date(fecha).toLocaleString('es-MX', {
    dateStyle: 'medium',
    timeStyle: 'short',
});

const infoModulo = computed(() => Object.fromEntries(props.modulos.map((m) => [m.slug, m])));
</script>

<template>
    <AppLayout title="Dashboard">
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Dashboard
            </h2>
        </template>

        <div class="relative overflow-hidden rounded-2xl bg-iyem-gradient px-6 py-8 text-white shadow-soft-lg sm:px-8">
            <div class="patron-puntos pointer-events-none absolute inset-0 text-white/10" />
            <div class="relative">
                <p class="text-sm font-medium uppercase tracking-widest text-white/70">
                    Plataforma Centralizada IYEM
                </p>
                <h1 class="mt-1 text-2xl font-bold sm:text-3xl">
                    Bienvenido, {{ page.props.auth.user.name }}
                </h1>
                <p class="mt-2 text-sm text-white/80">
                    Tienes acceso a {{ modulos.length }} módulo{{ modulos.length === 1 ? '' : 's' }} del ecosistema IYEM.
                </p>
            </div>
        </div>

        <div class="mt-8 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
            <a
                v-for="modulo in modulos"
                :key="modulo.slug"
                :href="modulo.externo ? route('dashboard.acceder', modulo.slug) : modulo.url"
                class="group flex flex-col rounded-2xl border border-iyem-claro bg-white p-5 shadow-soft transition duration-200 hover:-translate-y-1 hover:shadow-soft-lg"
            >
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-iyem-gradient text-white shadow-glow">
                    <IconoModulo :icono="modulo.icono" />
                </div>
                <h3 class="mt-4 font-semibold text-gray-800">
                    {{ modulo.nombre }}
                </h3>
                <p class="mt-1 flex-1 text-sm text-gray-500">
                    {{ modulo.descripcion }}
                </p>
                <span class="mt-5 inline-flex w-fit items-center gap-1.5 text-sm font-semibold text-iyem-700 transition group-hover:gap-2.5">
                    Acceder
                    <IconoNav icono="arrow" class="h-4 w-4" />
                </span>
            </a>

            <p v-if="!modulos.length" class="text-sm text-gray-500">
                No tienes módulos asignados. Contacta a un administrador.
            </p>
        </div>

        <div class="mt-10">
            <h2 class="text-lg font-semibold text-gray-800">
                Últimas actividades
            </h2>
            <div class="mt-3 overflow-hidden rounded-2xl border border-iyem-claro bg-white">
                <ul v-if="actividades.length" class="divide-y divide-iyem-claro">
                    <li v-for="(actividad, i) in actividades" :key="i" class="flex items-center gap-3 px-4 py-3.5 text-sm">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-iyem-claro text-iyem-primario">
                            <IconoModulo :icono="infoModulo[actividad.modulo]?.icono ?? 'squares-2x2'" />
                        </div>
                        <span class="flex-1 font-medium text-gray-700">
                            {{ infoModulo[actividad.modulo]?.nombre ?? actividad.modulo }}
                        </span>
                        <span class="text-gray-400">
                            {{ formatearFecha(actividad.accedido_at) }}
                        </span>
                    </li>
                </ul>
                <p v-else class="px-4 py-6 text-center text-sm text-gray-400">
                    Aún no hay actividad registrada.
                </p>
            </div>
        </div>
    </AppLayout>
</template>
