<script setup>
import { computed, ref, watch } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Aviso from '@/Components/Aviso.vue';
import IconoNav from '@/Components/IconoNav.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { fechaHora, numero } from '@/formato';

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

const claseFoco = 'focus:outline-none focus-visible:ring-2 focus-visible:ring-focus focus-visible:ring-offset-2';
</script>

<template>
    <AppLayout title="Importar al padrón">
        <template #header>
            <div class="flex min-w-0 items-center gap-2">
                <Link
                    :href="route('padron.index')"
                    :class="['toque-minimo -ml-2 flex shrink-0 items-center justify-center rounded-md text-ink-600 transition-colors hover:text-ink', claseFoco]"
                    aria-label="Volver al padrón"
                >
                    <IconoNav icono="arrow" class="h-5 w-5 rotate-180" />
                </Link>
                <span>Importar al padrón</span>
            </div>
        </template>

        <div class="mx-auto max-w-5xl space-y-6">
            <div>
                <h1 class="text-display-lg text-ink">Importar al padrón</h1>
                <p class="mt-1 text-body text-ink-600">
                    Carga un lote de personas desde una hoja de cálculo. Nada se escribe hasta que confirmes la vista previa.
                </p>
            </div>

            <Aviso v-if="page.props.flash?.success" tipo="exito">{{ page.props.flash.success }}</Aviso>

            <!-- ============================================================
                 Paso 1: subir el archivo
                 ============================================================ -->
            <template v-if="!vistaPrevia">
                <section class="rounded-lg border border-line bg-surface p-5 shadow-sm sm:p-6" aria-labelledby="paso-subir">
                    <h2 id="paso-subir" class="text-title text-ink">1. Sube el archivo</h2>
                    <p class="mt-1 text-small text-ink-600">
                        CSV o XLSX, hasta 10 MB. La primera fila debe traer los nombres de las columnas.
                    </p>

                    <form class="mt-4" @submit.prevent="subir">
                        <label
                            class="flex cursor-pointer flex-col items-center justify-center rounded-lg border-2 border-dashed px-4 py-10 text-center transition-colors focus-within:ring-2 focus-within:ring-focus focus-within:ring-offset-2"
                            :class="[
                                arrastrando ? 'border-action bg-surface-brand' : 'border-line-strong hover:bg-surface-50',
                                formArchivo.errors.archivo ? 'border-danger' : '',
                            ]"
                            @dragover.prevent="arrastrando = true"
                            @dragleave.prevent="arrastrando = false"
                            @drop.prevent="soltarArchivo"
                        >
                            <IconoNav icono="subir" class="h-8 w-8 text-action" />
                            <span class="mt-2 text-body-strong text-ink">
                                {{ formArchivo.archivo?.name ?? 'Arrastra el archivo o haz clic para elegirlo' }}
                            </span>
                            <span class="mt-0.5 text-small text-ink-600">CSV, XLSX o XLS</span>
                            <input
                                type="file"
                                accept=".csv,.xlsx,.xls,text/csv"
                                class="sr-only"
                                :aria-invalid="formArchivo.errors.archivo ? 'true' : undefined"
                                :aria-describedby="formArchivo.errors.archivo ? 'archivo-error' : undefined"
                                @change="elegirArchivo"
                            >
                        </label>

                        <InputError id="archivo-error" class="mt-1.5" :message="formArchivo.errors.archivo" />

                        <div class="mt-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-4">
                            <PrimaryButton class="w-full sm:w-auto" :procesando="formArchivo.processing" :disabled="!formArchivo.archivo || formArchivo.processing">
                                <IconoNav icono="buscar" class="h-4 w-4" />
                                {{ formArchivo.processing ? 'Revisando…' : 'Revisar el archivo' }}
                            </PrimaryButton>
                            <p v-if="!formArchivo.archivo" class="text-small text-ink-600">Elige un archivo para poder revisarlo.</p>
                        </div>
                    </form>
                </section>

                <!-- Columnas que se reconocen -->
                <section class="rounded-lg border border-line bg-surface shadow-sm" aria-labelledby="columnas-reconocidas">
                    <div class="p-5 sm:p-6">
                        <h2 id="columnas-reconocidas" class="text-title text-ink">Columnas que el hub reconoce solo</h2>
                        <p class="mt-1 text-small text-ink-600">
                            Si tus encabezados se llaman así, el mapeo se propone automáticamente. Si no, lo eliges a mano en el siguiente paso.
                            Los campos marcados como obligatorios deben venir en el archivo.
                        </p>
                    </div>
                    <div class="scrollbar-fina overflow-x-auto border-t border-line">
                        <table class="min-w-full text-left">
                            <thead class="bg-surface-50">
                                <tr>
                                    <th scope="col" class="px-3 py-2 text-overline text-ink-600">Campo</th>
                                    <th scope="col" class="px-3 py-2 text-overline text-ink-600">Encabezados que reconoce</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="campo in campos" :key="campo.clave" class="border-t border-line">
                                    <td class="h-11 whitespace-nowrap px-3 text-body-strong text-ink">
                                        {{ campo.etiqueta }}
                                        <span v-if="campo.obligatorio" class="ml-1 text-caption text-danger">(obligatorio)</span>
                                    </td>
                                    <td class="px-3 py-2 font-mono text-small text-ink-600">{{ campo.alias.join(', ') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>
            </template>

            <!-- ============================================================
                 Paso 2: mapear columnas y confirmar
                 ============================================================ -->
            <template v-else>
                <section aria-label="Resumen del archivo" class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div class="rounded-lg border border-line bg-surface p-5 shadow-sm">
                        <p class="text-overline text-ink-600">Filas en el archivo</p>
                        <p class="mt-2 text-number-display text-ink">{{ numero(vistaPrevia.total_filas) }}</p>
                    </div>
                    <div class="rounded-lg border border-line bg-surface p-5 shadow-sm">
                        <p class="flex items-center gap-1.5 text-overline text-ink-600">
                            <IconoNav icono="exito" class="h-4 w-4 text-success" />
                            Pasan la validación
                        </p>
                        <p class="mt-2 text-number-display text-ink">{{ numero(vistaPrevia.validas) }}</p>
                    </div>
                    <div class="rounded-lg border border-line bg-surface p-5 shadow-sm">
                        <p class="flex items-center gap-1.5 text-overline text-ink-600">
                            <IconoNav icono="alerta" class="h-4 w-4" :class="vistaPrevia.rechazadas ? 'text-danger' : 'text-ink-400'" />
                            Se rechazarán
                        </p>
                        <p class="mt-2 text-number-display" :class="vistaPrevia.rechazadas ? 'text-danger' : 'text-ink'">{{ numero(vistaPrevia.rechazadas) }}</p>
                    </div>
                </section>

                <Aviso v-if="vistaPrevia.errores_frecuentes.length" tipo="advertencia">
                    <p class="text-body-strong">Lo que más falla en este archivo</p>
                    <ul class="mt-1 space-y-0.5 text-small">
                        <li v-for="error in vistaPrevia.errores_frecuentes" :key="error.mensaje" class="flex gap-2">
                            <span class="font-mono font-semibold">{{ numero(error.total) }}×</span>
                            {{ error.mensaje }}
                        </li>
                    </ul>
                </Aviso>

                <!-- Mapeo -->
                <section class="rounded-lg border border-line bg-surface p-5 shadow-sm sm:p-6" aria-labelledby="paso-mapeo">
                    <h2 id="paso-mapeo" class="text-title text-ink">2. Confirma a qué campo va cada columna</h2>
                    <p class="mt-1 text-small text-ink-600">
                        <span class="font-mono">{{ camposSinMapear }}</span> campos quedarán sin llenar. Las columnas del archivo que no asignes a ningún campo se ignoran.
                    </p>

                    <div class="mt-5 grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3">
                        <div v-for="campo in campos" :key="campo.clave">
                            <label :for="`mapeo-${campo.clave}`" class="block text-body-strong text-ink">
                                {{ campo.etiqueta }}
                                <span v-if="campo.obligatorio" class="text-caption text-ink-600">(obligatorio)</span>
                            </label>
                            <select
                                :id="`mapeo-${campo.clave}`"
                                v-model="mapeo[campo.clave]"
                                class="mt-1.5 h-10 w-full rounded-md bg-surface text-body text-ink focus:ring-2 focus:ring-focus focus:ring-offset-2"
                                :class="campo.obligatorio && !mapeo[campo.clave] ? 'border-danger focus:border-danger' : 'border-line-strong focus:border-focus'"
                                :aria-invalid="campo.obligatorio && !mapeo[campo.clave] ? 'true' : undefined"
                                :aria-describedby="campo.obligatorio && !mapeo[campo.clave] ? `mapeo-${campo.clave}-error` : undefined"
                            >
                                <option :value="null">No importar</option>
                                <option v-for="encabezado in vistaPrevia.encabezados" :key="encabezado" :value="encabezado">
                                    {{ encabezado }}
                                </option>
                            </select>
                            <InputError
                                v-if="campo.obligatorio && !mapeo[campo.clave]"
                                :id="`mapeo-${campo.clave}-error`"
                                class="mt-1.5"
                                :message="`Elige la columna que trae ${campo.etiqueta.toLowerCase()}.`"
                            />
                        </div>
                    </div>
                </section>

                <!-- Muestra -->
                <section class="rounded-lg border border-line bg-surface shadow-sm" aria-labelledby="paso-muestra">
                    <h2 id="paso-muestra" class="p-5 text-title text-ink sm:px-6">3. Revisa las primeras filas</h2>

                    <ul class="divide-y divide-line border-t border-line">
                        <li
                            v-for="fila in vistaPrevia.muestra"
                            :key="fila.fila"
                            class="flex items-start gap-3 px-5 py-3 sm:px-6"
                            :class="fila.valida ? '' : 'bg-danger-surface'"
                        >
                            <IconoNav
                                :icono="fila.valida ? 'exito' : 'alerta'"
                                class="mt-0.5 h-4 w-4 shrink-0"
                                :class="fila.valida ? 'text-success' : 'text-danger'"
                            />
                            <div class="min-w-0 flex-1">
                                <p class="text-body-strong text-ink">
                                    <span class="font-mono">Fila {{ fila.fila }}</span> ·
                                    {{ fila.datos.nombre_completo ?? '(sin nombre)' }}
                                    <span class="sr-only">{{ fila.valida ? ', válida' : ', se rechazará' }}</span>
                                </p>
                                <p v-if="fila.valida" class="mt-0.5 truncate text-small text-ink-600">
                                    {{ Object.entries(fila.datos).filter(([k]) => k !== 'nombre_completo').map(([k, v]) => `${k}: ${v}`).join(' · ') || 'Sin datos adicionales' }}
                                </p>
                                <ul v-else class="mt-0.5 space-y-0.5 text-small text-ink">
                                    <li v-for="error in fila.errores" :key="error">{{ error }}</li>
                                </ul>
                            </div>
                        </li>
                    </ul>
                </section>

                <div>
                    <div class="flex flex-col-reverse gap-3 sm:flex-row">
                        <Link
                            :href="route('padron.importar.index')"
                            :class="['inline-flex h-10 items-center justify-center rounded-md border border-line-strong bg-surface px-4 text-body-strong text-ink transition-colors hover:bg-surface-100', claseFoco]"
                        >
                            Cancelar
                        </Link>
                        <PrimaryButton
                            type="button"
                            :procesando="formConfirmar.processing"
                            :disabled="faltaObligatorio || formConfirmar.processing"
                            :aria-describedby="faltaObligatorio ? 'motivo-bloqueo' : undefined"
                            @click="confirmar"
                        >
                            <IconoNav icono="subir" class="h-4 w-4" />
                            {{ formConfirmar.processing ? 'Importando…' : `Importar ${numero(vistaPrevia.validas)} filas` }}
                        </PrimaryButton>
                    </div>

                    <p v-if="faltaObligatorio" id="motivo-bloqueo" class="mt-2 text-small text-ink-600">
                        Para importar, indica qué columna trae el nombre completo.
                    </p>
                </div>
            </template>

            <!-- ============================================================
                 Historial de lotes
                 ============================================================ -->
            <section v-if="historial.length" aria-labelledby="titulo-historial">
                <h2 id="titulo-historial" class="text-title text-ink">Importaciones anteriores</h2>

                <ul class="mt-4 divide-y divide-line overflow-hidden rounded-lg border border-line bg-surface shadow-sm">
                    <li v-for="lote in historial" :key="lote.id" class="flex flex-wrap items-center gap-3 px-5 py-4">
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-mono text-body text-ink">{{ lote.archivo }}</p>
                            <p class="mt-0.5 text-small text-ink-600">
                                {{ lote.usuario }} · <span class="font-mono">{{ fechaHora(lote.fecha) }}</span> ·
                                <span class="capitalize">{{ lote.estado }}</span>
                            </p>
                            <p v-if="lote.mensaje" class="mt-1 text-small text-ink">{{ lote.mensaje }}</p>
                        </div>

                        <a
                            v-if="lote.tiene_rechazos"
                            :href="route('padron.importar.rechazos', lote.id)"
                            :class="['toque-minimo inline-flex shrink-0 items-center gap-1.5 rounded-md border border-line-strong bg-surface px-3 text-small font-semibold text-ink transition-colors hover:bg-surface-100', claseFoco]"
                        >
                            <IconoNav icono="descargar" class="h-4 w-4 text-danger" />
                            Descargar <span class="font-mono">{{ numero(lote.rechazadas) }}</span> rechazos
                        </a>
                    </li>
                </ul>
            </section>
        </div>
    </AppLayout>
</template>
