<script setup>
import { ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    personas: Object,
    filtros: Object,
});

const page = usePage();
const busqueda = ref(props.filtros.busqueda ?? '');
const estadoPersona = ref(props.filtros.estado_persona ?? '');

const buscar = () => {
    router.get(route('padron.index'), { busqueda: busqueda.value, estado_persona: estadoPersona.value }, {
        preserveState: true,
        replace: true,
    });
};
</script>

<template>
    <AppLayout title="Padrón">
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Padrón de contactos
            </h2>
        </template>

        <div
            v-if="page.props.jetstream.flash?.success"
            class="mb-4 rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 shadow-soft"
        >
            {{ page.props.jetstream.flash.success }}
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3">
            <form class="flex flex-wrap gap-2" @submit.prevent="buscar">
                <input
                    v-model="busqueda"
                    type="text"
                    placeholder="Buscar por nombre, correo o municipio..."
                    class="w-64 rounded-lg border-gray-300 text-sm shadow-sm focus:border-iyem-secundario focus:ring-iyem-secundario"
                >
                <select
                    v-model="estadoPersona"
                    class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-iyem-secundario focus:ring-iyem-secundario"
                >
                    <option value="">Todos los estados</option>
                    <option value="activa">Activa</option>
                    <option value="inactiva">Inactiva</option>
                    <option value="bloqueada">Bloqueada</option>
                </select>
                <button type="submit" class="rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-200">
                    Filtrar
                </button>
            </form>

            <div class="flex items-center gap-2">
                <Link
                    :href="route('padron.mapa')"
                    class="inline-flex items-center gap-2 rounded-lg border border-iyem-claro bg-white px-4 py-2 text-sm font-medium text-iyem-700 shadow-soft transition hover:bg-iyem-50"
                >
                    Ver mapa
                </Link>
                <Link
                    :href="route('padron.create')"
                    class="inline-flex items-center gap-2 rounded-lg bg-iyem-gradient px-4 py-2 text-sm font-semibold text-white shadow-soft transition hover:shadow-glow"
                >
                    Nuevo contacto
                </Link>
            </div>
        </div>

        <div class="scrollbar-fina mt-4 overflow-x-auto rounded-2xl border border-iyem-claro bg-white shadow-soft">
            <table class="min-w-full divide-y divide-iyem-claro text-sm">
                <thead class="bg-iyem-50 text-left text-xs uppercase tracking-wide text-iyem-700">
                    <tr>
                        <th class="px-4 py-3">Nombre</th>
                        <th class="px-4 py-3">Correo</th>
                        <th class="px-4 py-3">Teléfono</th>
                        <th class="px-4 py-3">Municipio</th>
                        <th class="px-4 py-3">Estado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-iyem-claro">
                    <tr v-for="persona in personas.data" :key="persona.id" class="transition hover:bg-iyem-50/60">
                        <td class="px-4 py-3 font-medium text-gray-800">
                            {{ persona.nombre_completo }}
                        </td>
                        <td class="px-4 py-3 text-gray-600">
                            {{ persona.email || '—' }}
                        </td>
                        <td class="px-4 py-3 text-gray-600">
                            {{ persona.telefono || '—' }}
                        </td>
                        <td class="px-4 py-3 text-gray-600">
                            {{ persona.municipio || '—' }}
                        </td>
                        <td class="px-4 py-3">
                            <span
                                class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold"
                                :class="persona.estado_persona === 'activa' ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-600'"
                            >
                                {{ persona.estado_persona }}
                            </span>
                        </td>
                    </tr>
                    <tr v-if="!personas.data.length">
                        <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-400">
                            No se encontraron contactos.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="personas.links.length > 3" class="mt-4 flex flex-wrap gap-1">
            <Link
                v-for="(link, i) in personas.links"
                :key="i"
                :href="link.url || '#'"
                v-html="link.label"
                class="rounded-lg px-3 py-1.5 text-sm transition"
                :class="[
                    link.active ? 'bg-iyem-gradient text-white shadow-glow' : 'bg-white text-gray-600 hover:bg-iyem-claro',
                    !link.url ? 'pointer-events-none opacity-50' : '',
                ]"
            />
        </div>
    </AppLayout>
</template>
