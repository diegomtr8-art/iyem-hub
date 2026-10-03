<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';
import IconoNav from '@/Components/IconoNav.vue';
import AvisoDeFalla from '@/Components/Modulo/AvisoDeFalla.vue';
import EncabezadoModulo from '@/Components/Modulo/EncabezadoModulo.vue';
import SelectorDePeriodo from '@/Components/Modulo/SelectorDePeriodo.vue';
import TablaReporte from '@/Components/Modulo/TablaReporte.vue';
import TarjetaIndicador from '@/Components/Modulo/TarjetaIndicador.vue';
import { momento } from '@/Components/Modulo/formato.js';
import { descargarCsv, useDatosDeModulo } from '@/Composables/useDatosDeModulo.js';

/*
 * Tablero de un módulo externo dentro del ERP. Genérico: no sabe de qué
 * módulo se trata. Recibe el marco del servidor y pide los datos, ya
 * normalizados por el adaptador del módulo, una vez pintada la página.
 */
const props = defineProps({
    modulo: { type: Object, required: true },
    periodo: { type: Object, required: true },
    periodos: { type: Array, required: true },
    informes: { type: Array, default: () => [] },
    puedeVerDatosPersonales: { type: Boolean, default: false },
});

const page = usePage();

/** Lo que el periodo pone en la query string. */
const consultaDelPeriodo = computed(() => (props.periodo.clave === 'personalizado'
    ? { periodo: 'personalizado', desde: props.periodo.desde, hasta: props.periodo.hasta }
    : { periodo: props.periodo.clave }));

/* ------------------------------------------------------------------ *
 * Datos
 * ------------------------------------------------------------------ */

const { datos, cargando, error, cargar } = useDatosDeModulo();

const recargar = () => cargar(route('modulos.datos', { slug: props.modulo.slug, ...consultaDelPeriodo.value }));

onMounted(recargar);
watch(() => props.periodo, recargar);

const resumen = computed(() => datos.value?.resumen ?? null);
const secciones = computed(() => datos.value?.secciones ?? []);

/*
 * Si el módulo no contestó a nada, un aviso basta: repetirlo en cada tabla
 * solo es ruido. Si falló una parte, cada tabla dice lo suyo.
 */
const moduloSinRespuesta = computed(() => !!resumen.value?.falla
    && secciones.value.length > 0
    && secciones.value.every((s) => s.falla?.tipo === resumen.value.falla.tipo));

/* ------------------------------------------------------------------ *
 * Periodo: viaja en la URL (visita parcial, sin volver a pintar todo)
 * ------------------------------------------------------------------ */

const cambiarPeriodo = (consulta) => {
    router.get(route('modulos.tablero', props.modulo.slug), consulta, {
        only: ['periodo', 'errors'],
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

/* ------------------------------------------------------------------ *
 * Semáforo: el mismo del dashboard, también después de pintar
 * ------------------------------------------------------------------ */

const salud = ref(null);

onMounted(async () => {
    try {
        const { data } = await axios.get(route('dashboard.salud'));
        salud.value = data.salud?.[props.modulo.slug] ?? null;
    } catch {
        // Se queda en "consultando": mejor que afirmar que está caído.
    }
});

/* ------------------------------------------------------------------ *
 * Exportación
 * ------------------------------------------------------------------ */

const exportando = ref(null);
const fallaDeExportacion = ref(null);

const exportar = async (informe) => {
    exportando.value = informe.clave;
    fallaDeExportacion.value = null;
    fallaDeExportacion.value = await descargarCsv(route('modulos.informe', {
        slug: props.modulo.slug,
        informe: informe.clave,
        ...consultaDelPeriodo.value,
    }));
    exportando.value = null;
};

const enlacePersonal = computed(() => (props.puedeVerDatosPersonales
    ? route('modulos.personas', { slug: props.modulo.slug, ...consultaDelPeriodo.value })
    : null));
</script>

<template>
    <AppLayout :title="modulo.nombre">
        <div class="mx-auto max-w-7xl">
            <nav aria-label="Ruta" class="mb-4">
                <Link
                    :href="route('dashboard')"
                    class="inline-flex items-center gap-1 rounded-sm text-small text-ink-600 hover:text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-focus focus-visible:ring-offset-2"
                >
                    <IconoNav icono="arrow" class="h-4 w-4 rotate-180" />
                    Tablero
                </Link>
            </nav>

            <EncabezadoModulo
                :modulo="modulo"
                :salud="salud"
                :informes="informes"
                :exportando="exportando"
                @exportar="exportar"
            />

            <section class="mt-6 rounded-md border border-line bg-surface p-4" aria-labelledby="titulo-periodo">
                <div class="flex flex-wrap items-baseline justify-between gap-2">
                    <h2 id="titulo-periodo" class="text-overline text-ink-600">Periodo</h2>
                    <p class="text-small text-ink-600">
                        {{ periodo.etiqueta }}
                    </p>
                </div>
                <div class="mt-3">
                    <SelectorDePeriodo
                        :periodo="periodo"
                        :opciones="periodos"
                        :errores="page.props.errors ?? {}"
                        @cambiar="cambiarPeriodo"
                    />
                </div>
            </section>

            <div v-if="fallaDeExportacion" class="mt-4">
                <AvisoDeFalla :falla="fallaDeExportacion" />
            </div>

            <!-- Anuncio para lectores de pantalla cuando cambian las cifras. -->
            <p class="sr-only" aria-live="polite">
                {{ cargando ? 'Consultando datos…' : (datos ? `Datos de ${periodo.etiqueta} cargados.` : '') }}
            </p>

            <!-- ============================================================
                 Indicadores de dirección
                 ============================================================ -->
            <section class="mt-6" aria-labelledby="titulo-indicadores" :aria-busy="cargando">
                <div class="flex flex-wrap items-baseline justify-between gap-2">
                    <h2 id="titulo-indicadores" class="text-title text-ink">Indicadores</h2>
                    <p v-if="resumen?.obtenido_en && !cargando" class="text-caption text-ink-400">
                        Consultado {{ momento(resumen.obtenido_en) }}
                    </p>
                </div>

                <div v-if="cargando" class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5">
                    <div v-for="n in 5" :key="n" class="h-40 animate-pulse rounded-md border border-line bg-surface-100" />
                </div>

                <div v-else-if="error" class="mt-3">
                    <AvisoDeFalla :falla="error" @reintentar="recargar" />
                </div>

                <div v-else-if="resumen?.falla" class="mt-3">
                    <AvisoDeFalla :falla="resumen.falla" @reintentar="recargar" />
                </div>

                <div v-else-if="resumen" class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5">
                    <TarjetaIndicador
                        v-for="indicador in resumen.indicadores"
                        :key="indicador.clave"
                        :indicador="indicador"
                        :rango="resumen.rango?.descripcion"
                        :enlace-personal="enlacePersonal"
                    />
                </div>
            </section>

            <!-- ============================================================
                 Reportes
                 ============================================================ -->
            <section v-if="!error" class="mt-8" aria-labelledby="titulo-reportes" :aria-busy="cargando">
                <h2 id="titulo-reportes" class="text-title text-ink">Reportes</h2>

                <div v-if="cargando" class="mt-3 grid grid-cols-1 gap-4 xl:grid-cols-2">
                    <div v-for="n in 4" :key="n" class="h-64 animate-pulse rounded-md border border-line bg-surface-100" />
                </div>

                <p v-else-if="moduloSinRespuesta" class="mt-3 text-body text-ink-600">
                    Los reportes tampoco se pudieron consultar. Aparecen aquí en cuanto el módulo responda.
                </p>

                <div v-else class="mt-3 grid grid-cols-1 gap-4 xl:grid-cols-2">
                    <TablaReporte
                        v-for="seccion in secciones"
                        :key="seccion.clave"
                        :seccion="seccion"
                        @reintentar="recargar"
                    />
                </div>
            </section>
        </div>
    </AppLayout>
</template>
