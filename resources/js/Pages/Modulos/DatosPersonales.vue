<script setup>
import { computed, onMounted, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import IconoNav from '@/Components/IconoNav.vue';
import AvisoDeFalla from '@/Components/Modulo/AvisoDeFalla.vue';
import SelectorDePeriodo from '@/Components/Modulo/SelectorDePeriodo.vue';
import TablaReporte from '@/Components/Modulo/TablaReporte.vue';
import { useDatosDeModulo } from '@/Composables/useDatosDeModulo.js';

/*
 * Listas con personas identificables de un módulo. Vista aparte del tablero
 * y detrás de su propio permiso; cada consulta queda registrada a nombre de
 * quien la hizo.
 */
const props = defineProps({
    modulo: { type: Object, required: true },
    periodo: { type: Object, required: true },
    periodos: { type: Array, required: true },
});

const page = usePage();

const consultaDelPeriodo = computed(() => (props.periodo.clave === 'personalizado'
    ? { periodo: 'personalizado', desde: props.periodo.desde, hasta: props.periodo.hasta }
    : { periodo: props.periodo.clave }));

const { datos, cargando, error, cargar } = useDatosDeModulo();

const recargar = () => cargar(route('modulos.personas.datos', { slug: props.modulo.slug, ...consultaDelPeriodo.value }));

onMounted(recargar);
watch(() => props.periodo, recargar);

const cambiarPeriodo = (consulta) => {
    router.get(route('modulos.personas', props.modulo.slug), consulta, {
        only: ['periodo', 'errors'],
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};
</script>

<template>
    <AppLayout :title="`${modulo.nombre} · Personas`">
        <div class="mx-auto max-w-7xl">
            <nav aria-label="Ruta" class="mb-4">
                <Link
                    :href="route('modulos.tablero', { slug: modulo.slug, ...consultaDelPeriodo })"
                    class="inline-flex items-center gap-1 rounded-sm text-small text-ink-600 hover:text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-focus focus-visible:ring-offset-2"
                >
                    <IconoNav icono="arrow" class="h-4 w-4 rotate-180" />
                    {{ modulo.nombre }}
                </Link>
            </nav>

            <header>
                <h1 class="text-display-md text-ink">Personas de {{ modulo.nombre }}</h1>
                <p class="mt-1 max-w-3xl text-body text-ink-600">
                    Nombres y datos de contacto. Cada consulta queda registrada en la bitácora del ERP con tu usuario.
                    No los copies fuera de la plataforma.
                </p>
                <span
                    v-if="modulo.entorno_de_prueba"
                    class="mt-2 inline-flex min-h-6 items-center gap-1 rounded-sm bg-warning-surface px-2 py-0.5 text-caption text-ink"
                >
                    <IconoNav icono="alerta" class="h-3.5 w-3.5 text-warning" />
                    Entorno de prueba: los datos son ficticios
                </span>
            </header>

            <section class="mt-6 rounded-md border border-line bg-surface p-4" aria-labelledby="titulo-periodo">
                <div class="flex flex-wrap items-baseline justify-between gap-2">
                    <h2 id="titulo-periodo" class="text-overline text-ink-600">Periodo</h2>
                    <p class="text-small text-ink-600">{{ periodo.etiqueta }}</p>
                </div>
                <div class="mt-3">
                    <SelectorDePeriodo :periodo="periodo" :opciones="periodos" :errores="page.props.errors ?? {}" @cambiar="cambiarPeriodo" />
                </div>
            </section>

            <p class="sr-only" aria-live="polite">{{ cargando ? 'Consultando datos…' : '' }}</p>

            <div v-if="cargando" class="mt-6 grid grid-cols-1 gap-4" :aria-busy="true">
                <div v-for="n in 2" :key="n" class="h-64 animate-pulse rounded-md border border-line bg-surface-100" />
            </div>

            <div v-else-if="error" class="mt-6">
                <AvisoDeFalla :falla="error" @reintentar="recargar" />
            </div>

            <div v-else class="mt-6 grid grid-cols-1 gap-4">
                <TablaReporte
                    v-for="seccion in datos?.secciones ?? []"
                    :key="seccion.clave"
                    :seccion="seccion"
                    @reintentar="recargar"
                />
            </div>
        </div>
    </AppLayout>
</template>
