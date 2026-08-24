<script setup>
import { ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import IconoNav from '@/Components/IconoNav.vue';
import Paginacion from '@/Components/Paginacion.vue';
import { usePermisos } from '@/Composables/usePermisos';

const props = defineProps({
    personas: { type: Object, required: true },
    filtros: { type: Object, default: () => ({}) },
});

const { puede } = usePermisos();

const busqueda = ref(props.filtros.busqueda ?? '');
const estadoPersona = ref(props.filtros.estado_persona ?? '');

const buscar = () => {
    router.get(
        route('padron.index'),
        { busqueda: busqueda.value, estado_persona: estadoPersona.value },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

// El selector filtra al instante; el texto espera al submit para no disparar
// una consulta por cada tecla.
watch(estadoPersona, buscar);

const limpiar = () => {
    busqueda.value = '';
    estadoPersona.value = '';
    buscar();
};

const hayFiltros = () => busqueda.value !== '' || estadoPersona.value !== '';

const colorEstado = (estado) => ({
    activa: 'bg-iyem-exito/10 text-iyem-exito ring-iyem-exito/25',
    inactiva: 'bg-gray-100 text-gray-600 ring-gray-300',
    bloqueada: 'bg-iyem-error/10 text-iyem-error ring-iyem-error/25',
}[estado] ?? 'bg-gray-100 text-gray-600 ring-gray-300');
</script>

<template>
    <AppLayout title="Padrón">
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Padrón Central
            </h2>
        </template>

        <!-- Filtros y acciones -->
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <form class="flex flex-col gap-2 sm:flex-row" @submit.prevent="buscar">
                <div class="relative">
                    <IconoNav
                        icono="buscar"
                        class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"
                    />
                    <input
                        v-model="busqueda"
                        type="search"
                        inputmode="search"
                        enterkeyhint="search"
                        placeholder="Nombre, correo o municipio…"
                        aria-label="Buscar en el padrón"
                        class="h-11 w-full rounded-lg border-gray-300 pl-9 text-sm shadow-sm focus:border-iyem-secundario focus:ring-iyem-secundario sm:w-72"
                    >
                </div>

                <select
                    v-model="estadoPersona"
                    aria-label="Filtrar por estado"
                    class="h-11 rounded-lg border-gray-300 text-sm shadow-sm focus:border-iyem-secundario focus:ring-iyem-secundario"
                >
                    <option value="">
                        Todos los estados
                    </option>
                    <option value="activa">
                        Activa
                    </option>
                    <option value="inactiva">
                        Inactiva
                    </option>
                    <option value="bloqueada">
                        Bloqueada
                    </option>
                </select>

                <div class="flex gap-2">
                    <button
                        type="submit"
                        class="toque-minimo inline-flex flex-1 items-center justify-center rounded-lg bg-gray-100 px-4 text-sm font-medium text-gray-700 transition hover:bg-gray-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-iyem-secundario sm:flex-none"
                    >
                        Filtrar
                    </button>
                    <button
                        v-if="hayFiltros()"
                        type="button"
                        class="toque-minimo inline-flex items-center justify-center rounded-lg px-3 text-sm font-medium text-gray-500 transition hover:text-gray-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-iyem-secundario"
                        @click="limpiar"
                    >
                        Limpiar
                    </button>
                </div>
            </form>

            <div class="flex gap-2">
                <Link
                    :href="route('padron.mapa')"
                    class="toque-minimo inline-flex flex-1 items-center justify-center gap-2 rounded-lg border border-iyem-200 bg-white px-4 text-sm font-medium text-iyem-700 shadow-soft transition hover:bg-iyem-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-iyem-secundario lg:flex-none"
                >
                    <IconoNav icono="mapa" class="h-4 w-4" />
                    Mapa
                </Link>
                <Link
                    v-if="puede('crear-padron')"
                    :href="route('padron.create')"
                    class="toque-minimo inline-flex flex-1 items-center justify-center gap-2 rounded-lg bg-iyem-gradient px-4 text-sm font-semibold text-white shadow-soft transition hover:shadow-glow focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-iyem-secundario focus-visible:ring-offset-2 lg:flex-none"
                >
                    <IconoNav icono="mas" class="h-4 w-4" />
                    Nuevo contacto
                </Link>
            </div>
        </div>

        <!-- ============================================================
             Teléfono: tarjetas apiladas.
             Una tabla de cinco columnas en 390 px obliga a scroll
             horizontal; apilada se lee de un vistazo.
             ============================================================ -->
        <ul v-if="personas.data.length" class="mt-4 space-y-3 md:hidden">
            <li v-for="persona in personas.data" :key="persona.id">
                <Link
                    :href="route('padron.show', persona.id)"
                    class="block rounded-2xl border border-iyem-200 bg-white p-4 shadow-soft transition-all duration-200 active:scale-[0.99] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-iyem-secundario"
                >
                <div class="flex items-start justify-between gap-3">
                    <p class="min-w-0 flex-1 font-semibold text-gray-800">
                        {{ persona.nombre_completo }}
                    </p>
                    <span
                        class="shrink-0 rounded-full px-2 py-0.5 text-[0.7rem] font-semibold capitalize ring-1 ring-inset"
                        :class="colorEstado(persona.estado_persona)"
                    >
                        {{ persona.estado_persona }}
                    </span>
                </div>

                <dl class="mt-3 space-y-1.5 text-sm">
                    <div v-if="persona.email" class="flex gap-2">
                        <dt class="w-20 shrink-0 text-gray-400">
                            Correo
                        </dt>
                        <dd class="min-w-0 truncate text-gray-600">
                            {{ persona.email }}
                        </dd>
                    </div>
                    <div v-if="persona.telefono" class="flex gap-2">
                        <dt class="w-20 shrink-0 text-gray-400">
                            Teléfono
                        </dt>
                        <dd class="tabular-nums text-gray-600">
                            {{ persona.telefono }}
                        </dd>
                    </div>
                    <div v-if="persona.municipio" class="flex gap-2">
                        <dt class="w-20 shrink-0 text-gray-400">
                            Municipio
                        </dt>
                        <dd class="text-gray-600">
                            {{ persona.municipio }}
                        </dd>
                    </div>
                </dl>
                </Link>
            </li>
        </ul>

        <!-- ============================================================
             Tablet y escritorio: tabla.
             El scroll horizontal, si hace falta, ocurre dentro del
             contenedor y nunca en el body.
             ============================================================ -->
        <div
            v-if="personas.data.length"
            class="scrollbar-fina scroll-suave-ios mt-4 hidden overflow-x-auto rounded-2xl border border-iyem-200 bg-white shadow-soft md:block"
        >
            <table class="min-w-full divide-y divide-iyem-100 text-sm">
                <thead class="bg-iyem-50 text-left text-xs uppercase tracking-wide text-iyem-700">
                    <tr>
                        <th scope="col" class="px-4 py-3">
                            Nombre
                        </th>
                        <th scope="col" class="px-4 py-3">
                            Correo
                        </th>
                        <th scope="col" class="px-4 py-3">
                            Teléfono
                        </th>
                        <th scope="col" class="px-4 py-3">
                            Municipio
                        </th>
                        <th scope="col" class="px-4 py-3">
                            Estado
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-iyem-100">
                    <tr
                        v-for="persona in personas.data"
                        :key="persona.id"
                        class="transition-colors duration-150 hover:bg-iyem-50/60"
                    >
                        <td class="px-4 py-3 font-medium">
                            <Link
                                :href="route('padron.show', persona.id)"
                                class="text-gray-800 underline-offset-2 transition hover:text-iyem-700 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-iyem-secundario"
                            >
                                {{ persona.nombre_completo }}
                            </Link>
                        </td>
                        <td class="px-4 py-3 text-gray-600">
                            {{ persona.email || '—' }}
                        </td>
                        <td class="px-4 py-3 tabular-nums text-gray-600">
                            {{ persona.telefono || '—' }}
                        </td>
                        <td class="px-4 py-3 text-gray-600">
                            {{ persona.municipio || '—' }}
                        </td>
                        <td class="px-4 py-3">
                            <span
                                class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold capitalize ring-1 ring-inset"
                                :class="colorEstado(persona.estado_persona)"
                            >
                                {{ persona.estado_persona }}
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <p
            v-if="!personas.data.length"
            class="mt-4 rounded-2xl border border-dashed border-iyem-200 px-4 py-10 text-center text-sm text-gray-500"
        >
            {{ hayFiltros()
                ? 'Ningún contacto coincide con los filtros aplicados.'
                : 'El padrón todavía no tiene contactos.' }}
        </p>

        <Paginacion :paginador="personas" />
    </AppLayout>
</template>
