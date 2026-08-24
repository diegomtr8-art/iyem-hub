<script setup>
import { computed, ref, watch } from 'vue';
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import IconoNav from '@/Components/IconoNav.vue';

const props = defineProps({
    campos: { type: Array, default: () => [] },
    historial: { type: Array, default: () => [] },
});

const page = usePage();

/** La vista previa llega por flash tras subir el archivo. */
const vistaPrevia = computed(() => page.props.vistaPrevia ?? null);

/* ------------------------------------------------------------------ *
 * Paso 1: subir
 * ------------------------------------------------------------------ */

const formArchivo = useForm({ archivo: null });
const arrastrando = ref(false);

const elegirArchivo = (evento) => {
    formArchivo.archivo = evento.target.files?.[0] ?? null;
};

const soltarArchivo = (evento) => {
    arrastrando.value = false;
    formArchivo.archivo = evento.dataTransfer.files?.[0] ?? null;
};

const subir = () => {
    formArchivo.post(route('padron.importar.previsualizar'), {
        preserveScroll: true,
        forceFormData: true,
    });
};

/* ------------------------------------------------------------------ *
 * Paso 2: mapear y confirmar
 * ------------------------------------------------------------------ */

const mapeo = ref({});

watch(vistaPrevia, (valor) => {
    if (valor) mapeo.value = { ...valor.mapeo };
}, { immediate: true });

const formConfirmar = useForm({ mapeo: {} });

const confirmar = () => {
    formConfirmar.mapeo = mapeo.value;
    formConfirmar.post(route('padron.importar.confirmar', vistaPrevia.value.importacion_id));
};

const faltaObligatorio = computed(() => !mapeo.value.nombre_completo);

const camposSinMapear = computed(() =>
    props.campos.filter((c) => !mapeo.value[c.clave]).length,
);

const fecha = (valor) =>
    valor ? new Date(valor).toLocaleString('es-MX', { dateStyle: 'medium', timeStyle: 'short' }) : '—';
</script>

<template>
    <AppLayout title="Importar al padrón">
        <template #header>
            <div class="flex min-w-0 items-center gap-2">
                <Link
                    :href="route('padron.index')"
                    class="shrink-0 text-gray-400 transition hover:text-iyem-primario"
                    aria-label="Volver al padrón"
                >
                    <IconoNav icono="arrow" class="h-5 w-5 rotate-180" />
                </Link>
                <span class="truncate">Importar al padrón</span>
            </div>
        </template>

        <div
            v-if="page.props.flash?.success"
            class="rounded-2xl border border-iyem-exito/30 bg-iyem-exito/10 px-4 py-3 text-sm text-iyem-exito"
        >
            {{ page.props.flash.success }}
        </div>

        <!-- ============================================================
             Paso 1: subir el archivo
             ============================================================ -->
        <section v-if="!vistaPrevia" class="mt-4">
            <div class="rounded-2xl border border-iyem-200 bg-white p-5 shadow-soft sm:p-6">
                <h2 class="font-semibold text-gray-800">
                    1. Sube el archivo
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    CSV o XLSX, hasta 10 MB. La primera fila debe traer los nombres de las columnas.
                    Nada se escribe todavía: primero verás una vista previa.
                </p>

                <form class="mt-4" @submit.prevent="subir">
                    <label
                        class="flex cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed px-4 py-10 text-center transition-colors duration-200"
                        :class="arrastrando ? 'border-iyem-400 bg-iyem-50' : 'border-iyem-200 hover:border-iyem-300'"
                        @dragover.prevent="arrastrando = true"
                        @dragleave.prevent="arrastrando = false"
                        @drop.prevent="soltarArchivo"
                    >
                        <IconoNav icono="subir" class="h-8 w-8 text-iyem-300" />
                        <span class="mt-2 text-sm font-medium text-gray-700">
                            {{ formArchivo.archivo?.name ?? 'Arrastra el archivo o toca para elegirlo' }}
                        </span>
                        <span class="mt-0.5 text-xs text-gray-400">CSV, XLSX o XLS</span>
                        <input
                            type="file"
                            accept=".csv,.xlsx,.xls,text/csv"
                            class="sr-only"
                            @change="elegirArchivo"
                        >
                    </label>

                    <p v-if="formArchivo.errors.archivo" class="mt-2 text-sm text-iyem-error">
                        {{ formArchivo.errors.archivo }}
                    </p>

                    <button
                        type="submit"
                        :disabled="!formArchivo.archivo || formArchivo.processing"
                        class="toque-minimo mt-4 inline-flex w-full items-center justify-center gap-2 rounded-lg bg-iyem-gradient px-4 text-sm font-semibold text-white shadow-soft transition hover:shadow-glow disabled:opacity-50 sm:w-auto"
                    >
                        <IconoNav icono="buscar" class="h-4 w-4" />
                        {{ formArchivo.processing ? 'Revisando…' : 'Revisar el archivo' }}
                    </button>
                </form>
            </div>

            <!-- Columnas que se reconocen -->
            <div class="mt-4 rounded-2xl border border-iyem-200 bg-white p-5 shadow-soft">
                <h3 class="font-semibold text-gray-800">
                    Columnas que el hub reconoce solo
                </h3>
                <p class="mt-1 text-sm text-gray-500">
                    Si tus encabezados se llaman así, el mapeo se propone automáticamente. Si no,
                    lo eliges a mano en el siguiente paso.
                </p>
                <div class="scrollbar-fina mt-3 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <tbody class="divide-y divide-iyem-100">
                            <tr v-for="campo in campos" :key="campo.clave">
                                <td class="py-2 pr-4 font-medium text-gray-700">
                                    {{ campo.etiqueta }}
                                    <span v-if="campo.obligatorio" class="text-iyem-error">*</span>
                                </td>
                                <td class="py-2 text-gray-400">
                                    {{ campo.alias.join(', ') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- ============================================================
             Paso 2: mapear columnas y confirmar
             ============================================================ -->
        <section v-else class="mt-4">
            <div class="grid gap-3 sm:grid-cols-3">
                <div class="rounded-2xl border border-iyem-200 bg-white p-4 shadow-soft">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-400">
                        Filas en el archivo
                    </p>
                    <p class="mt-1 text-2xl font-bold tabular-nums text-gray-800">
                        {{ vistaPrevia.total_filas.toLocaleString('es-MX') }}
                    </p>
                </div>
                <div class="rounded-2xl border border-iyem-exito/30 bg-iyem-exito/5 p-4 shadow-soft">
                    <p class="text-xs font-medium uppercase tracking-wide text-iyem-exito">
                        Pasan la validación
                    </p>
                    <p class="mt-1 text-2xl font-bold tabular-nums text-iyem-exito">
                        {{ vistaPrevia.validas.toLocaleString('es-MX') }}
                    </p>
                </div>
                <div
                    class="rounded-2xl border p-4 shadow-soft"
                    :class="vistaPrevia.rechazadas ? 'border-iyem-error/30 bg-iyem-error/5' : 'border-iyem-200 bg-white'"
                >
                    <p class="text-xs font-medium uppercase tracking-wide" :class="vistaPrevia.rechazadas ? 'text-iyem-error' : 'text-gray-400'">
                        Se rechazarán
                    </p>
                    <p class="mt-1 text-2xl font-bold tabular-nums" :class="vistaPrevia.rechazadas ? 'text-iyem-error' : 'text-gray-800'">
                        {{ vistaPrevia.rechazadas.toLocaleString('es-MX') }}
                    </p>
                </div>
            </div>

            <div
                v-if="vistaPrevia.errores_frecuentes.length"
                class="mt-4 rounded-2xl border border-iyem-alerta/30 bg-iyem-alerta/5 p-4"
            >
                <h3 class="text-sm font-semibold text-iyem-alerta">
                    Lo que más falla en este archivo
                </h3>
                <ul class="mt-2 space-y-1 text-sm text-gray-700">
                    <li v-for="error in vistaPrevia.errores_frecuentes" :key="error.mensaje" class="flex gap-2">
                        <span class="tabular-nums font-semibold text-iyem-alerta">{{ error.total }}×</span>
                        {{ error.mensaje }}
                    </li>
                </ul>
            </div>

            <!-- Mapeo -->
            <div class="mt-4 rounded-2xl border border-iyem-200 bg-white p-5 shadow-soft">
                <h2 class="font-semibold text-gray-800">
                    2. Confirma a qué campo va cada columna
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    {{ camposSinMapear }} campos quedarán sin llenar. Las columnas del archivo que no
                    asignes a ningún campo simplemente se ignoran.
                </p>

                <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                    <div v-for="campo in campos" :key="campo.clave">
                        <label :for="`mapeo-${campo.clave}`" class="block text-xs font-medium uppercase tracking-wide text-gray-500">
                            {{ campo.etiqueta }}
                            <span v-if="campo.obligatorio" class="text-iyem-error">*</span>
                        </label>
                        <select
                            :id="`mapeo-${campo.clave}`"
                            v-model="mapeo[campo.clave]"
                            class="mt-1 h-11 w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-iyem-secundario focus:ring-iyem-secundario"
                            :class="campo.obligatorio && !mapeo[campo.clave] ? 'border-iyem-error' : ''"
                        >
                            <option :value="null">
                                — No importar —
                            </option>
                            <option v-for="encabezado in vistaPrevia.encabezados" :key="encabezado" :value="encabezado">
                                {{ encabezado }}
                            </option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Muestra -->
            <div class="mt-4 rounded-2xl border border-iyem-200 bg-white p-5 shadow-soft">
                <h2 class="font-semibold text-gray-800">
                    3. Revisa las primeras filas
                </h2>

                <ul class="mt-3 space-y-2">
                    <li
                        v-for="fila in vistaPrevia.muestra"
                        :key="fila.fila"
                        class="rounded-xl border p-3 text-sm"
                        :class="fila.valida ? 'border-iyem-100' : 'border-iyem-error/30 bg-iyem-error/5'"
                    >
                        <div class="flex items-start gap-2">
                            <IconoNav
                                :icono="fila.valida ? 'exito' : 'alerta'"
                                class="mt-0.5 h-4 w-4 shrink-0"
                                :class="fila.valida ? 'text-iyem-exito' : 'text-iyem-error'"
                            />
                            <div class="min-w-0 flex-1">
                                <p class="font-medium text-gray-800">
                                    Fila {{ fila.fila }}:
                                    {{ fila.datos.nombre_completo ?? '(sin nombre)' }}
                                </p>
                                <p v-if="fila.valida" class="mt-0.5 truncate text-xs text-gray-400">
                                    {{ Object.entries(fila.datos).filter(([k]) => k !== 'nombre_completo').map(([k, v]) => `${k}: ${v}`).join(' · ') || 'Sin datos adicionales' }}
                                </p>
                                <ul v-else class="mt-1 space-y-0.5 text-xs text-iyem-error">
                                    <li v-for="error in fila.errores" :key="error">
                                        {{ error }}
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </li>
                </ul>
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                <button
                    type="button"
                    :disabled="faltaObligatorio || formConfirmar.processing"
                    class="toque-minimo inline-flex items-center justify-center gap-2 rounded-lg bg-iyem-gradient px-5 text-sm font-semibold text-white shadow-soft transition hover:shadow-glow disabled:opacity-50"
                    @click="confirmar"
                >
                    <IconoNav icono="subir" class="h-4 w-4" />
                    {{ formConfirmar.processing
                        ? 'Importando…'
                        : `Importar ${vistaPrevia.validas.toLocaleString('es-MX')} filas` }}
                </button>

                <Link
                    :href="route('padron.importar.index')"
                    class="toque-minimo inline-flex items-center justify-center rounded-lg border border-iyem-200 bg-white px-4 text-sm font-medium text-gray-600 transition hover:bg-iyem-50"
                >
                    Cancelar
                </Link>
            </div>

            <p v-if="faltaObligatorio" class="mt-2 text-sm text-iyem-error">
                Hay que indicar qué columna trae el nombre completo.
            </p>
        </section>

        <!-- ============================================================
             Historial de lotes
             ============================================================ -->
        <section v-if="historial.length" class="mt-8">
            <h2 class="text-lg font-semibold text-gray-800">
                Importaciones anteriores
            </h2>

            <ul class="mt-3 space-y-2">
                <li
                    v-for="lote in historial"
                    :key="lote.id"
                    class="flex flex-wrap items-center gap-3 rounded-2xl border border-iyem-200 bg-white p-4 shadow-soft"
                >
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-medium text-gray-800">
                            {{ lote.archivo }}
                        </p>
                        <p class="mt-0.5 text-xs text-gray-400">
                            {{ lote.usuario }} · {{ fecha(lote.fecha) }} ·
                            <span class="capitalize">{{ lote.estado }}</span>
                        </p>
                        <p v-if="lote.mensaje" class="mt-1 text-sm tabular-nums text-gray-600">
                            {{ lote.mensaje }}
                        </p>
                    </div>

                    <a
                        v-if="lote.tiene_rechazos"
                        :href="route('padron.importar.rechazos', lote.id)"
                        class="toque-minimo inline-flex shrink-0 items-center gap-1.5 rounded-lg border border-iyem-error/30 px-3 text-xs font-semibold text-iyem-error transition hover:bg-iyem-error/5"
                    >
                        <IconoNav icono="descargar" class="h-3.5 w-3.5" />
                        {{ lote.rechazadas }} rechazos
                    </a>
                </li>
            </ul>
        </section>
    </AppLayout>
</template>
