<script setup>
import { computed, onMounted, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';
import IconoModulo from '@/Components/IconoModulo.vue';
import IconoNav from '@/Components/IconoNav.vue';
import TarjetaKpi from '@/Components/TarjetaKpi.vue';
import TarjetaModulo from '@/Components/TarjetaModulo.vue';

const props = defineProps({
    modulos: { type: Array, default: () => [] },
    categorias: { type: Array, default: () => [] },
    indicadores: { type: Object, default: () => ({}) },
    actividades: { type: Array, default: () => [] },
    actividadesPlataforma: { type: Array, default: null },
});

const page = usePage();
const usuario = computed(() => page.props.auth.user);

/* ------------------------------------------------------------------ *
 * Encabezado
 * ------------------------------------------------------------------ */

const saludo = computed(() => {
    const hora = new Date().getHours();
    if (hora < 12) return 'Buenos días';
    if (hora < 19) return 'Buenas tardes';
    return 'Buenas noches';
});

const fechaLarga = computed(() =>
    new Date().toLocaleDateString('es-MX', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    }),
);

/* ------------------------------------------------------------------ *
 * Semáforo de disponibilidad
 *
 * Se pide después de montar la página. Hasta que llega, cada tarjeta
 * muestra el punto en gris pulsante: "consultando", no "en línea".
 * ------------------------------------------------------------------ */

const salud = ref({});
const saludCargada = ref(false);

onMounted(async () => {
    try {
        const { data } = await axios.get(route('dashboard.salud'));
        salud.value = data.salud ?? {};
    } catch {
        // Si el sondeo falla, los puntos se quedan en "consultando". Es
        // preferible a afirmar que todo está caído por un error nuestro.
    } finally {
        saludCargada.value = true;
    }
});

const modulosEnLinea = computed(() => {
    if (!saludCargada.value) return null;

    return props.modulos.filter((modulo) => {
        if (!modulo.navegable) return false;
        const estado = salud.value[modulo.slug]?.estado;
        // Un módulo sin endpoint de salud se cuenta como disponible: el hub
        // no tiene forma de desmentirlo y sí sabe que está en producción.
        return estado === 'en_linea' || estado === 'sin_monitoreo';
    }).length;
});

const sinMonitoreo = computed(
    () => props.modulos.filter(
        (m) => m.navegable && salud.value[m.slug]?.estado === 'sin_monitoreo',
    ).length,
);

/* ------------------------------------------------------------------ *
 * Cuadrícula: filtro por categoría y modo de vista
 * ------------------------------------------------------------------ */

const categoriaActiva = ref('todas');

const CLAVE_VISTA = 'iyem.dashboard.vista';

const vista = ref('tarjetas');

onMounted(() => {
    try {
        const guardada = localStorage.getItem(CLAVE_VISTA);
        if (guardada === 'lista' || guardada === 'tarjetas') vista.value = guardada;
    } catch {
        // Navegador con almacenamiento bloqueado: se queda en tarjetas.
    }
});

const cambiarVista = (nueva) => {
    vista.value = nueva;
    try {
        localStorage.setItem(CLAVE_VISTA, nueva);
    } catch {
        // Sin persistencia, pero la vista cambia igual en esta sesión.
    }
};

const modulosFiltrados = computed(() =>
    categoriaActiva.value === 'todas'
        ? props.modulos
        : props.modulos.filter((m) => m.categoria === categoriaActiva.value),
);

/* ------------------------------------------------------------------ *
 * Actividad reciente
 * ------------------------------------------------------------------ */

const pestanaActividad = ref('mia');

const nombresDeModulo = computed(
    () => Object.fromEntries(props.modulos.map((m) => [m.slug, m])),
);

const formateadorRelativo = new Intl.RelativeTimeFormat('es-MX', { numeric: 'auto' });

const UNIDADES = [
    ['year', 31536000],
    ['month', 2592000],
    ['day', 86400],
    ['hour', 3600],
    ['minute', 60],
];

const hace = (fecha) => {
    const segundos = (new Date(fecha) - new Date()) / 1000;

    for (const [unidad, tamano] of UNIDADES) {
        if (Math.abs(segundos) >= tamano) {
            return formateadorRelativo.format(Math.round(segundos / tamano), unidad);
        }
    }

    return 'hace un momento';
};

const fechaExacta = (fecha) =>
    new Date(fecha).toLocaleString('es-MX', { dateStyle: 'medium', timeStyle: 'short' });

const actividadVisible = computed(() =>
    pestanaActividad.value === 'plataforma' && props.actividadesPlataforma
        ? props.actividadesPlataforma
        : props.actividades,
);
</script>

<template>
    <AppLayout title="Dashboard">
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Dashboard
            </h2>
        </template>

        <!-- ============================================================
             Hero: saludo, fecha e indicadores globales
             ============================================================ -->
        <section class="relative overflow-hidden rounded-2xl bg-iyem-gradient shadow-soft-lg">
            <div class="pointer-events-none absolute inset-0 bg-iyem-mesh" aria-hidden="true" />
            <div class="patron-puntos pointer-events-none absolute inset-0 text-white/10" aria-hidden="true" />

            <div class="relative px-5 py-7 sm:px-8 sm:py-8">
                <p class="text-xs font-medium uppercase tracking-widest text-white/60">
                    Plataforma central IYEM
                </p>
                <h1 class="mt-1.5 text-2xl font-bold text-white sm:text-3xl">
                    {{ saludo }}, {{ usuario.name }}
                </h1>
                <p class="mt-1 text-sm capitalize text-white/70">
                    {{ fechaLarga }}
                </p>

                <div class="mt-6 grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4">
                    <TarjetaKpi
                        etiqueta="Personas en el padrón"
                        icono="user"
                        :valor="indicadores.personas"
                    />
                    <TarjetaKpi
                        etiqueta="Trámites activos"
                        icono="lista"
                        :valor="indicadores.tramites_activos"
                        detalle="En todos los módulos"
                    />
                    <TarjetaKpi
                        etiqueta="Módulos en línea"
                        icono="stack"
                        :valor="modulosEnLinea === null ? null : `${modulosEnLinea} / ${indicadores.modulos_visibles}`"
                        :detalle="sinMonitoreo ? `${sinMonitoreo} sin monitoreo` : null"
                    />
                    <TarjetaKpi
                        etiqueta="Accesos (24 h)"
                        icono="reloj"
                        :valor="indicadores.accesos_24h"
                    />
                </div>
            </div>
        </section>

        <!-- ============================================================
             Cuadrícula de módulos
             ============================================================ -->
        <section class="mt-8">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-lg font-semibold text-gray-800">
                    Módulos
                </h2>

                <!-- Conmutador de vista. 44 px de alto para cumplir el
                     mínimo táctil de Apple. -->
                <div
                    class="inline-flex rounded-xl border border-iyem-200 bg-white p-1"
                    role="group"
                    aria-label="Forma de ver los módulos"
                >
                    <button
                        v-for="opcion in [
                            { clave: 'tarjetas', icono: 'tarjetas', texto: 'Tarjetas' },
                            { clave: 'lista', icono: 'lista', texto: 'Lista' },
                        ]"
                        :key="opcion.clave"
                        type="button"
                        class="flex h-9 min-w-[2.75rem] items-center justify-center gap-1.5 rounded-lg px-3 text-sm font-medium transition-colors duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-iyem-secundario"
                        :class="vista === opcion.clave ? 'bg-iyem-claro text-iyem-700' : 'text-gray-500 hover:text-gray-700'"
                        :aria-pressed="vista === opcion.clave"
                        @click="cambiarVista(opcion.clave)"
                    >
                        <IconoNav :icono="opcion.icono" class="h-4 w-4" />
                        <span class="sr-only sm:not-sr-only">{{ opcion.texto }}</span>
                    </button>
                </div>
            </div>

            <!-- Chips de categoría. Scroll horizontal propio en móvil para
                 que nunca desplacen el body. -->
            <div
                v-if="categorias.length > 1"
                class="scrollbar-fina -mx-1 mt-4 flex gap-2 overflow-x-auto px-1 pb-1"
            >
                <button
                    v-for="chip in [{ clave: 'todas', nombre: 'Todas', total: modulos.length }, ...categorias]"
                    :key="chip.clave"
                    type="button"
                    class="inline-flex h-9 shrink-0 items-center gap-1.5 rounded-full border px-3.5 text-sm font-medium transition-all duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-iyem-secundario focus-visible:ring-offset-2"
                    :class="categoriaActiva === chip.clave
                        ? 'border-transparent bg-iyem-gradient text-white shadow-glow'
                        : 'border-iyem-200 bg-white text-gray-600 hover:border-iyem-300 hover:text-iyem-700'"
                    :aria-pressed="categoriaActiva === chip.clave"
                    @click="categoriaActiva = chip.clave"
                >
                    {{ chip.nombre }}
                    <span class="tabular-nums opacity-60">{{ chip.total }}</span>
                </button>
            </div>

            <div
                v-if="modulosFiltrados.length"
                class="mt-5 grid gap-4"
                :class="vista === 'lista'
                    ? 'grid-cols-1 lg:grid-cols-2'
                    : 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4'"
            >
                <TarjetaModulo
                    v-for="modulo in modulosFiltrados"
                    :key="modulo.slug"
                    :modulo="modulo"
                    :salud="salud[modulo.slug] ?? null"
                    :compacto="vista === 'lista'"
                />
            </div>

            <p v-else class="mt-5 rounded-2xl border border-dashed border-iyem-200 px-4 py-8 text-center text-sm text-gray-500">
                {{ modulos.length
                    ? 'No hay módulos en esta categoría.'
                    : 'No tienes módulos asignados. Contacta a un administrador.' }}
            </p>
        </section>

        <!-- ============================================================
             Actividad reciente
             ============================================================ -->
        <section class="mt-10">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-lg font-semibold text-gray-800">
                    Actividad reciente
                </h2>

                <div
                    v-if="actividadesPlataforma"
                    class="inline-flex rounded-xl border border-iyem-200 bg-white p-1"
                    role="tablist"
                >
                    <button
                        v-for="pestana in [
                            { clave: 'mia', texto: 'Mis accesos' },
                            { clave: 'plataforma', texto: 'Toda la plataforma' },
                        ]"
                        :key="pestana.clave"
                        type="button"
                        role="tab"
                        class="h-9 rounded-lg px-3 text-sm font-medium transition-colors duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-iyem-secundario"
                        :class="pestanaActividad === pestana.clave ? 'bg-iyem-claro text-iyem-700' : 'text-gray-500 hover:text-gray-700'"
                        :aria-selected="pestanaActividad === pestana.clave"
                        @click="pestanaActividad = pestana.clave"
                    >
                        {{ pestana.texto }}
                    </button>
                </div>
            </div>

            <div class="mt-4 overflow-hidden rounded-2xl border border-iyem-200 bg-white shadow-soft">
                <ul v-if="actividadVisible.length" class="divide-y divide-iyem-100">
                    <li
                        v-for="actividad in actividadVisible"
                        :key="actividad.id"
                        class="flex items-center gap-3 px-4 py-3.5"
                    >
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-iyem-claro text-iyem-primario">
                            <IconoModulo :icono="nombresDeModulo[actividad.modulo]?.icono ?? 'squares-2x2'" class="h-5 w-5" />
                        </div>

                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium text-gray-700">
                                {{ nombresDeModulo[actividad.modulo]?.nombre ?? actividad.modulo }}
                            </p>
                            <p class="truncate text-xs text-gray-400">
                                <span v-if="actividad.usuario">{{ actividad.usuario }} · </span>
                                <span class="tabular-nums">{{ actividad.ip_address ?? 'IP no registrada' }}</span>
                            </p>
                        </div>

                        <time
                            class="shrink-0 text-xs text-gray-400"
                            :datetime="actividad.accedido_at"
                            :title="fechaExacta(actividad.accedido_at)"
                        >
                            {{ hace(actividad.accedido_at) }}
                        </time>
                    </li>
                </ul>

                <p v-else class="px-4 py-8 text-center text-sm text-gray-400">
                    Aún no hay actividad registrada.
                </p>
            </div>
        </section>
    </AppLayout>
</template>
