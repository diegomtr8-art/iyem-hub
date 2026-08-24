<script setup>
import { computed, ref } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import IconoNav from '@/Components/IconoNav.vue';

const props = defineProps({
    grupos: { type: Array, default: () => [] },
    resumen: { type: Object, default: () => ({}) },
    truncado: { type: Boolean, default: false },
    incluyeSimilitud: { type: Boolean, default: true },
    umbralSimilitud: { type: Number, default: 85 },
    diasParaRevertir: { type: Number, default: 30 },
    fusionesRecientes: { type: Array, default: () => [] },
});

const pestana = ref('pendientes');

const alternarSimilitud = () => {
    router.get(
        route('padron.duplicados.index'),
        props.incluyeSimilitud ? { sin_similitud: 1 } : {},
        { preserveScroll: true },
    );
};

/* ------------------------------------------------------------------ *
 * Fusión
 * ------------------------------------------------------------------ */

const grupoAbierto = ref(null);
const principalElegida = ref(null);

const formFusion = useForm({
    principal_id: null,
    duplicada_id: null,
    criterio: null,
    motivo: '',
});

const abrirGrupo = (indice, grupo) => {
    if (grupoAbierto.value === indice) {
        grupoAbierto.value = null;
        return;
    }

    grupoAbierto.value = indice;
    // Por omisión sobrevive la ficha más antigua: suele ser la que más
    // trámites acumula y la que otros módulos ya referencian.
    principalElegida.value = grupo.personas[0].id;
    formFusion.reset();
    formFusion.criterio = grupo.criterio;
};

const fusionar = (grupo, duplicadaId) => {
    formFusion.principal_id = principalElegida.value;
    formFusion.duplicada_id = duplicadaId;
    formFusion.criterio = grupo.criterio;

    formFusion.post(route('padron.duplicados.fusionar'), {
        preserveScroll: true,
        onSuccess: () => {
            grupoAbierto.value = null;
            formFusion.reset();
        },
    });
};

const revertir = (fusion) => {
    router.post(route('padron.duplicados.revertir', fusion.id), {}, { preserveScroll: true });
};

/* ------------------------------------------------------------------ *
 * Presentación
 * ------------------------------------------------------------------ */

const colorConfianza = (confianza) => ({
    certeza: 'bg-iyem-error/10 text-iyem-error ring-iyem-error/25',
    alta: 'bg-iyem-alerta/10 text-iyem-alerta ring-iyem-alerta/25',
    media: 'bg-iyem-dorado/15 text-iyem-dorado ring-iyem-dorado/30',
    sospecha: 'bg-sky-500/10 text-sky-700 ring-sky-500/25',
}[confianza] ?? 'bg-gray-100 text-gray-600 ring-gray-300');

const fecha = (valor) =>
    valor ? new Date(valor).toLocaleDateString('es-MX', { day: 'numeric', month: 'short', year: 'numeric' }) : '—';

const bloqueados = computed(() => props.resumen.criterios_bloqueados_por_esquema ?? []);
</script>

<template>
    <AppLayout title="Duplicados del padrón">
        <template #header>
            <div class="flex min-w-0 items-center gap-2">
                <Link
                    :href="route('padron.index')"
                    class="shrink-0 text-gray-400 transition hover:text-iyem-primario"
                    aria-label="Volver al padrón"
                >
                    <IconoNav icono="arrow" class="h-5 w-5 rotate-180" />
                </Link>
                <span class="truncate">Duplicados</span>
            </div>
        </template>

        <!-- Advertencia sobre el alcance real de la detección -->
        <div
            v-if="bloqueados.length"
            class="flex items-start gap-3 rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-900"
        >
            <IconoNav icono="info" class="mt-0.5 h-5 w-5 shrink-0 text-sky-600" />
            <p>
                La base impide por diseño que se repitan
                <strong>{{ bloqueados.join(' y ') }}</strong>: esas columnas tienen restricción de unicidad.
                Que no aparezcan duplicados por ahí no es un logro de calidad del padrón, es la
                restricción haciendo su trabajo. Los duplicados reales entran por RFC, por teléfono
                y por nombre mal capturado.
            </p>
        </div>

        <!-- Resumen -->
        <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl border border-iyem-200 bg-white p-4 shadow-soft">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-400">
                    Grupos por revisar
                </p>
                <p class="mt-1 text-2xl font-bold tabular-nums text-iyem-700">
                    {{ (resumen.grupos ?? 0).toLocaleString('es-MX') }}
                </p>
            </div>
            <div class="rounded-2xl border border-iyem-200 bg-white p-4 shadow-soft">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-400">
                    Personas involucradas
                </p>
                <p class="mt-1 text-2xl font-bold tabular-nums text-iyem-700">
                    {{ (resumen.personas_involucradas ?? 0).toLocaleString('es-MX') }}
                </p>
            </div>
            <div class="rounded-2xl border border-iyem-200 bg-white p-4 shadow-soft sm:col-span-2">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-400">
                    Sospechas por nombre parecido
                </p>
                <label class="mt-2 inline-flex cursor-pointer items-center gap-2 text-sm text-gray-600">
                    <input
                        type="checkbox"
                        :checked="incluyeSimilitud"
                        class="rounded border-gray-300 text-iyem-primario focus:ring-iyem-secundario"
                        @change="alternarSimilitud"
                    >
                    Incluirlas (umbral {{ umbralSimilitud }} %)
                </label>
            </div>
        </div>

        <!-- Pestañas -->
        <div class="mt-6 flex gap-1 border-b border-iyem-200" role="tablist">
            <button
                v-for="opcion in [
                    { clave: 'pendientes', texto: `Por revisar (${grupos.length})` },
                    { clave: 'historial', texto: `Fusiones hechas (${fusionesRecientes.length})` },
                ]"
                :key="opcion.clave"
                type="button"
                role="tab"
                class="min-h-[44px] border-b-2 px-3.5 text-sm font-medium transition-colors duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-iyem-secundario"
                :class="pestana === opcion.clave
                    ? 'border-iyem-secundario text-iyem-700'
                    : 'border-transparent text-gray-500 hover:text-gray-700'"
                :aria-selected="pestana === opcion.clave"
                @click="pestana = opcion.clave"
            >
                {{ opcion.texto }}
            </button>
        </div>

        <!-- ============================================================
             Grupos por revisar
             ============================================================ -->
        <section v-show="pestana === 'pendientes'" class="mt-5 space-y-3">
            <article
                v-for="(grupo, i) in grupos"
                :key="i"
                class="overflow-hidden rounded-2xl border border-iyem-200 bg-white shadow-soft"
            >
                <button
                    type="button"
                    class="flex w-full items-center gap-3 px-4 py-3 text-left transition-colors duration-200 hover:bg-iyem-50/60 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-iyem-secundario"
                    :aria-expanded="grupoAbierto === i"
                    @click="abrirGrupo(i, grupo)"
                >
                    <IconoNav icono="duplicados" class="h-5 w-5 shrink-0 text-iyem-primario" />

                    <div class="min-w-0 flex-1">
                        <p class="truncate font-medium text-gray-800">
                            {{ grupo.personas.map((p) => p.nombre_completo).join('  ·  ') }}
                        </p>
                        <p class="mt-0.5 truncate text-xs text-gray-400">
                            {{ grupo.etiqueta }}<span v-if="grupo.valor">: {{ grupo.valor }}</span>
                        </p>
                    </div>

                    <span
                        class="shrink-0 rounded-full px-2 py-0.5 text-[0.7rem] font-semibold capitalize ring-1 ring-inset"
                        :class="colorConfianza(grupo.confianza)"
                    >
                        {{ grupo.confianza }}
                    </span>

                    <IconoNav
                        icono="chevron"
                        class="h-4 w-4 shrink-0 text-gray-400 transition-transform duration-200"
                        :class="grupoAbierto === i ? 'rotate-180' : ''"
                    />
                </button>

                <div v-show="grupoAbierto === i" class="border-t border-iyem-100 p-4">
                    <p class="text-sm text-gray-500">
                        Elige qué ficha sobrevive. La otra se archiva y sus trámites, etiquetas e
                        historial pasan a la ficha elegida.
                        <strong class="text-gray-700">Se puede deshacer durante {{ diasParaRevertir }} días.</strong>
                    </p>

                    <div class="mt-4 grid gap-3 lg:grid-cols-2">
                        <div
                            v-for="persona in grupo.personas"
                            :key="persona.id"
                            class="rounded-xl border p-3 transition-colors duration-200"
                            :class="principalElegida === persona.id
                                ? 'border-iyem-400 bg-iyem-50'
                                : 'border-iyem-200'"
                        >
                            <label class="flex cursor-pointer items-start gap-2">
                                <input
                                    v-model="principalElegida"
                                    type="radio"
                                    :value="persona.id"
                                    :name="`principal-${i}`"
                                    class="mt-1 border-gray-300 text-iyem-primario focus:ring-iyem-secundario"
                                >
                                <span class="min-w-0 flex-1">
                                    <span class="block font-medium text-gray-800">{{ persona.nombre_completo }}</span>
                                    <span class="mt-0.5 block text-xs text-gray-400">
                                        ID {{ persona.id }} · alta {{ fecha(persona.alta) }} ·
                                        origen {{ persona.creado_por_modulo || '—' }}
                                    </span>
                                </span>
                            </label>

                            <dl class="mt-3 space-y-1 text-xs">
                                <div v-for="campo in ['curp', 'rfc', 'email', 'telefono', 'municipio']" :key="campo" class="flex gap-2">
                                    <dt class="w-20 shrink-0 uppercase text-gray-400">
                                        {{ campo }}
                                    </dt>
                                    <dd class="min-w-0 break-all text-gray-600">
                                        {{ persona[campo] || '—' }}
                                    </dd>
                                </div>
                            </dl>

                            <div class="mt-3 flex flex-wrap gap-2">
                                <Link
                                    :href="persona.url"
                                    class="inline-flex items-center gap-1 text-xs font-medium text-iyem-700 hover:underline"
                                >
                                    Ver ficha
                                    <IconoNav icono="arrow" class="h-3 w-3" />
                                </Link>

                                <button
                                    v-if="principalElegida !== persona.id"
                                    type="button"
                                    :disabled="formFusion.processing"
                                    class="ml-auto inline-flex items-center gap-1.5 rounded-lg bg-iyem-error/10 px-2.5 py-1.5 text-xs font-semibold text-iyem-error transition hover:bg-iyem-error/15 disabled:opacity-50"
                                    @click="fusionar(grupo, persona.id)"
                                >
                                    <IconoNav icono="duplicados" class="h-3.5 w-3.5" />
                                    Fusionar dentro de la elegida
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <label :for="`motivo-${i}`" class="block text-xs font-medium uppercase tracking-wide text-gray-500">
                            Motivo (queda en la bitácora)
                        </label>
                        <input
                            :id="`motivo-${i}`"
                            v-model="formFusion.motivo"
                            type="text"
                            maxlength="500"
                            placeholder="Ej.: se confirmó por teléfono que es la misma persona"
                            class="mt-1 h-11 w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-iyem-secundario focus:ring-iyem-secundario"
                        >
                    </div>
                </div>
            </article>

            <p v-if="truncado" class="rounded-2xl border border-dashed border-iyem-200 px-4 py-3 text-center text-sm text-gray-500">
                Se muestran los primeros 100 grupos. Corre
                <code class="rounded bg-iyem-50 px-1.5 py-0.5 text-xs">php artisan padron:duplicados --csv=reporte.csv</code>
                para el listado completo.
            </p>

            <p v-if="!grupos.length" class="rounded-2xl border border-dashed border-iyem-200 px-4 py-10 text-center text-sm text-gray-500">
                No hay duplicados por revisar.
            </p>
        </section>

        <!-- ============================================================
             Historial de fusiones
             ============================================================ -->
        <section v-show="pestana === 'historial'" class="mt-5 space-y-3">
            <article
                v-for="fusion in fusionesRecientes"
                :key="fusion.id"
                class="rounded-2xl border border-iyem-200 bg-white p-4 shadow-soft"
                :class="fusion.revertida_at ? 'opacity-60' : ''"
            >
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="font-medium text-gray-800">
                            <span class="text-gray-400 line-through">{{ fusion.duplicada }}</span>
                            <span class="mx-1.5 text-gray-300">→</span>
                            <Link :href="route('padron.show', fusion.principal_id)" class="hover:underline">
                                {{ fusion.principal }}
                            </Link>
                        </p>
                        <p class="mt-1 text-xs text-gray-400">
                            {{ fusion.usuario }} · {{ fecha(fusion.fecha) }}
                            <span v-if="fusion.criterio"> · criterio: {{ fusion.criterio }}</span>
                        </p>
                        <p v-if="fusion.motivo" class="mt-1 text-sm text-gray-600">
                            «{{ fusion.motivo }}»
                        </p>
                        <p v-if="Object.keys(fusion.vinculos_movidos).length" class="mt-1.5 text-xs text-gray-400">
                            Movidos:
                            <span v-for="(total, tabla) in fusion.vinculos_movidos" :key="tabla" class="mr-2">
                                {{ tabla.replace(/_/g, ' ') }} ({{ total }})
                            </span>
                        </p>
                    </div>

                    <div class="shrink-0 text-right">
                        <p v-if="fusion.revertida_at" class="text-xs font-semibold text-gray-500">
                            Revertida el {{ fecha(fusion.revertida_at) }}
                        </p>
                        <template v-else>
                            <button
                                v-if="fusion.es_revertible"
                                type="button"
                                class="toque-minimo inline-flex items-center gap-1.5 rounded-lg border border-iyem-200 px-3 text-xs font-semibold text-gray-600 transition hover:bg-iyem-50"
                                @click="revertir(fusion)"
                            >
                                <IconoNav icono="historial" class="h-3.5 w-3.5" />
                                Deshacer
                            </button>
                            <p class="mt-1 text-[0.7rem] text-gray-400">
                                {{ fusion.es_revertible
                                    ? `Se puede deshacer hasta el ${fecha(fusion.revertible_hasta)}`
                                    : 'La ventana para deshacer ya cerró' }}
                            </p>
                        </template>
                    </div>
                </div>
            </article>

            <p v-if="!fusionesRecientes.length" class="rounded-2xl border border-dashed border-iyem-200 px-4 py-10 text-center text-sm text-gray-500">
                Todavía no se ha fusionado ninguna ficha.
            </p>
        </section>
    </AppLayout>
</template>
