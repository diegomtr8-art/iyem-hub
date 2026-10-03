<script setup>
import { computed, ref } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Aviso from '@/Components/Aviso.vue';
import Checkbox from '@/Components/Checkbox.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import DangerButton from '@/Components/DangerButton.vue';
import IconoNav from '@/Components/IconoNav.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { fecha, numero } from '@/formato';

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
        onFinish: () => {
            confirmacion.value = null;
        },
    });
};

const revirtiendo = ref(false);

const revertir = (fusion) => {
    revirtiendo.value = true;
    router.post(route('padron.duplicados.revertir', fusion.id), {}, {
        preserveScroll: true,
        onFinish: () => {
            revirtiendo.value = false;
            confirmacion.value = null;
        },
    });
};

/* ------------------------------------------------------------------ *
 * Confirmación
 *
 * Fusionar y deshacer mueven trámites entre identidades: la confirmación
 * nombra a las dos personas, no pregunta "¿estás seguro?".
 * ------------------------------------------------------------------ */

const confirmacion = ref(null);

const pedirFusion = (grupo, duplicada) => {
    const principal = grupo.personas.find((p) => p.id === principalElegida.value);
    confirmacion.value = { tipo: 'fusion', grupo, duplicada, principal };
};

const pedirReversion = (fusion) => {
    confirmacion.value = { tipo: 'revertir', fusion };
};

const confirmar = () => {
    const c = confirmacion.value;
    if (!c) return;
    if (c.tipo === 'fusion') fusionar(c.grupo, c.duplicada.id);
    else revertir(c.fusion);
};

const procesandoConfirmacion = computed(() => formFusion.processing || revirtiendo.value);

/* ------------------------------------------------------------------ *
 * Presentación
 * ------------------------------------------------------------------ */

const CONFIANZA = {
    certeza: { texto: 'Certeza', fondo: 'bg-danger-surface', icono: 'text-danger' },
    alta: { texto: 'Confianza alta', fondo: 'bg-warning-surface', icono: 'text-warning' },
    media: { texto: 'Confianza media', fondo: 'bg-warning-surface', icono: 'text-warning' },
    sospecha: { texto: 'Sospecha', fondo: 'bg-surface-brand', icono: 'text-action' },
};

const confianza = (clave) => CONFIANZA[clave] ?? { texto: clave, fondo: 'bg-surface-100', icono: 'text-ink-400' };

const CAMPOS = [
    { clave: 'curp', etiqueta: 'CURP', mono: true },
    { clave: 'rfc', etiqueta: 'RFC', mono: true },
    { clave: 'email', etiqueta: 'Correo', mono: false },
    { clave: 'telefono', etiqueta: 'Teléfono', mono: true },
    { clave: 'municipio', etiqueta: 'Municipio', mono: false },
];

const bloqueados = computed(() => props.resumen.criterios_bloqueados_por_esquema ?? []);

const claseFoco = 'focus:outline-none focus-visible:ring-2 focus-visible:ring-focus focus-visible:ring-offset-2';
</script>

<template>
    <AppLayout title="Duplicados del padrón">
        <template #header>
            <div class="flex min-w-0 items-center gap-2">
                <Link
                    :href="route('padron.index')"
                    :class="['toque-minimo -ml-2 flex shrink-0 items-center justify-center rounded-md text-ink-600 transition-colors hover:text-ink', claseFoco]"
                    aria-label="Volver al padrón"
                >
                    <IconoNav icono="arrow" class="h-5 w-5 rotate-180" />
                </Link>
                <span>Duplicados</span>
            </div>
        </template>

        <div class="mx-auto max-w-6xl space-y-6">
            <div>
                <h1 class="text-display-lg text-ink">Duplicados del padrón</h1>
                <p class="mt-1 text-body text-ink-600">
                    Fichas que probablemente son la misma persona. Al fusionarlas, sus trámites quedan bajo una sola identidad.
                </p>
            </div>

            <!-- Advertencia sobre el alcance real de la detección -->
            <Aviso v-if="bloqueados.length" tipo="info">
                La base impide por diseño que se repitan
                <strong>{{ bloqueados.join(' y ') }}</strong>: esas columnas tienen restricción de unicidad.
                Que no aparezcan duplicados por ahí no es un logro de calidad del padrón, es la
                restricción haciendo su trabajo. Los duplicados reales entran por RFC, por teléfono
                y por nombre mal capturado.
            </Aviso>

            <!-- Resumen -->
            <section aria-label="Resumen" class="grid grid-cols-2 gap-4 xl:grid-cols-4">
                <div class="rounded-lg border border-line bg-surface p-5 shadow-sm">
                    <p class="text-overline text-ink-600">Grupos por revisar</p>
                    <p class="mt-2 text-number-display text-ink">{{ numero(resumen.grupos ?? 0) }}</p>
                </div>
                <div class="rounded-lg border border-line bg-surface p-5 shadow-sm">
                    <p class="text-overline text-ink-600">Personas involucradas</p>
                    <p class="mt-2 text-number-display text-ink">{{ numero(resumen.personas_involucradas ?? 0) }}</p>
                </div>
                <div class="col-span-2 rounded-lg border border-line bg-surface p-5 shadow-sm">
                    <p class="text-overline text-ink-600">Sospechas por nombre parecido</p>
                    <label class="mt-3 inline-flex min-h-[44px] cursor-pointer items-center gap-3 text-body text-ink">
                        <Checkbox :checked="incluyeSimilitud" @change="alternarSimilitud" />
                        Incluirlas (umbral <span class="font-mono">{{ umbralSimilitud }} %</span>)
                    </label>
                </div>
            </section>

            <!-- Pestañas -->
            <div>
                <div class="flex gap-1 overflow-x-auto border-b border-line" role="tablist" aria-label="Duplicados">
                    <button
                        v-for="opcion in [
                            { clave: 'pendientes', texto: 'Por revisar', total: grupos.length },
                            { clave: 'historial', texto: 'Fusiones hechas', total: fusionesRecientes.length },
                        ]"
                        :id="`pestana-${opcion.clave}`"
                        :key="opcion.clave"
                        type="button"
                        role="tab"
                        :aria-controls="`panel-${opcion.clave}`"
                        class="min-h-[44px] shrink-0 border-b-2 px-4 text-body font-medium transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-focus"
                        :class="pestana === opcion.clave ? 'border-action text-ink' : 'border-transparent text-ink-600 hover:text-ink'"
                        :aria-selected="pestana === opcion.clave ? 'true' : 'false'"
                        @click="pestana = opcion.clave"
                    >
                        {{ opcion.texto }} <span class="font-mono text-ink-600">{{ opcion.total }}</span>
                    </button>
                </div>

                <!-- ============================================================
                     Grupos por revisar
                     ============================================================ -->
                <section
                    v-show="pestana === 'pendientes'"
                    id="panel-pendientes"
                    role="tabpanel"
                    aria-labelledby="pestana-pendientes"
                    class="mt-5 space-y-4"
                >
                    <article
                        v-for="(grupo, i) in grupos"
                        :key="i"
                        class="overflow-hidden rounded-lg border border-line bg-surface shadow-sm"
                    >
                        <button
                            type="button"
                            class="flex min-h-[44px] w-full items-center gap-3 px-4 py-3 text-left transition-colors hover:bg-surface-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-focus"
                            :aria-expanded="grupoAbierto === i ? 'true' : 'false'"
                            :aria-controls="`grupo-${i}`"
                            @click="abrirGrupo(i, grupo)"
                        >
                            <IconoNav icono="duplicados" class="h-5 w-5 shrink-0 text-action" />

                            <div class="min-w-0 flex-1">
                                <p class="truncate text-body-strong text-ink">
                                    {{ grupo.personas.map((p) => p.nombre_completo).join(' · ') }}
                                </p>
                                <p class="mt-0.5 truncate text-small text-ink-600">
                                    {{ grupo.etiqueta }}<span v-if="grupo.valor">: <span class="font-mono">{{ grupo.valor }}</span></span>
                                </p>
                            </div>

                            <span class="inline-flex h-6 shrink-0 items-center gap-1 rounded-sm px-2 text-caption text-ink" :class="confianza(grupo.confianza).fondo">
                                <IconoNav icono="alerta" class="h-3.5 w-3.5" :class="confianza(grupo.confianza).icono" />
                                {{ confianza(grupo.confianza).texto }}
                            </span>

                            <IconoNav
                                icono="chevron"
                                class="h-4 w-4 shrink-0 text-ink-600 transition-transform"
                                :class="grupoAbierto === i ? 'rotate-180' : ''"
                            />
                        </button>

                        <div v-show="grupoAbierto === i" :id="`grupo-${i}`" class="border-t border-line p-4 sm:p-5">
                            <p class="text-small text-ink-600">
                                Elige qué ficha sobrevive. La otra se archiva y sus trámites, etiquetas e
                                historial pasan a la ficha elegida.
                                <strong class="text-ink">Se puede deshacer durante {{ diasParaRevertir }} días.</strong>
                            </p>

                            <fieldset class="mt-4">
                                <legend class="sr-only">Ficha que sobrevive</legend>
                                <div class="grid gap-4 lg:grid-cols-2">
                                    <div
                                        v-for="persona in grupo.personas"
                                        :key="persona.id"
                                        class="rounded-md border p-4 transition-colors"
                                        :class="principalElegida === persona.id ? 'border-action bg-surface-brand' : 'border-line'"
                                    >
                                        <label class="flex cursor-pointer items-start gap-3">
                                            <input
                                                v-model="principalElegida"
                                                type="radio"
                                                :value="persona.id"
                                                :name="`principal-${i}`"
                                                class="mt-1 h-4 w-4 border-line-strong bg-surface text-action focus:ring-2 focus:ring-focus focus:ring-offset-2"
                                            >
                                            <span class="min-w-0 flex-1">
                                                <span class="block text-body-strong text-ink">{{ persona.nombre_completo }}</span>
                                                <span class="mt-0.5 block text-small text-ink-600">
                                                    ID <span class="font-mono">{{ persona.id }}</span> · alta <span class="font-mono">{{ fecha(persona.alta) }}</span> ·
                                                    origen {{ persona.creado_por_modulo || '—' }}
                                                </span>
                                                <span v-if="principalElegida === persona.id" class="mt-1 block text-caption text-action">Esta ficha sobrevive</span>
                                            </span>
                                        </label>

                                        <dl class="mt-3 space-y-1 text-small">
                                            <div v-for="campo in CAMPOS" :key="campo.clave" class="flex gap-2">
                                                <dt class="w-20 shrink-0 text-ink-600">{{ campo.etiqueta }}</dt>
                                                <dd class="min-w-0 break-all text-ink" :class="campo.mono ? 'font-mono' : ''">
                                                    {{ persona[campo.clave] || '—' }}
                                                </dd>
                                            </div>
                                        </dl>

                                        <div class="mt-4 flex flex-wrap items-center gap-3">
                                            <Link
                                                :href="persona.url"
                                                :class="['inline-flex min-h-[44px] items-center gap-1 rounded-sm text-small font-medium text-action hover:underline', claseFoco]"
                                            >
                                                Ver ficha
                                                <IconoNav icono="arrow" class="h-4 w-4" />
                                            </Link>

                                            <DangerButton
                                                v-if="principalElegida !== persona.id"
                                                class="ml-auto"
                                                :procesando="formFusion.processing"
                                                @click="pedirFusion(grupo, persona)"
                                            >
                                                <IconoNav icono="duplicados" class="h-4 w-4" />
                                                Fusionar en la elegida
                                            </DangerButton>
                                        </div>
                                    </div>
                                </div>
                            </fieldset>

                            <div class="mt-5">
                                <label :for="`motivo-${i}`" class="block text-body-strong text-ink">Motivo</label>
                                <input
                                    :id="`motivo-${i}`"
                                    v-model="formFusion.motivo"
                                    type="text"
                                    maxlength="500"
                                    :aria-describedby="`motivo-${i}-ayuda`"
                                    class="mt-1.5 h-10 w-full rounded-md border-line-strong bg-surface px-3 text-body text-ink focus:border-focus focus:ring-2 focus:ring-focus focus:ring-offset-2"
                                >
                                <p :id="`motivo-${i}-ayuda`" class="mt-1.5 text-small text-ink-600">
                                    Queda en la bitácora. Por ejemplo: se confirmó por teléfono que es la misma persona.
                                </p>
                            </div>
                        </div>
                    </article>

                    <Aviso v-if="truncado" tipo="info">
                        Se muestran los primeros 100 grupos. Para el listado completo corre
                        <code class="rounded-sm bg-surface px-1.5 py-0.5 text-code">php artisan padron:duplicados --csv=reporte.csv</code>.
                    </Aviso>

                    <div v-if="!grupos.length" class="rounded-lg border border-line bg-surface p-6">
                        <p class="text-body-strong text-ink">No hay duplicados por revisar.</p>
                        <p class="mt-1 text-small text-ink-600">
                            {{ incluyeSimilitud
                                ? 'Ni por datos repetidos ni por nombres parecidos.'
                                : 'Activa las sospechas por nombre parecido para buscar con un criterio más amplio.' }}
                        </p>
                    </div>
                </section>

                <!-- ============================================================
                     Historial de fusiones
                     ============================================================ -->
                <section
                    v-show="pestana === 'historial'"
                    id="panel-historial"
                    role="tabpanel"
                    aria-labelledby="pestana-historial"
                    class="mt-5"
                >
                    <ul v-if="fusionesRecientes.length" class="divide-y divide-line overflow-hidden rounded-lg border border-line bg-surface shadow-sm">
                        <li
                            v-for="fusion in fusionesRecientes"
                            :key="fusion.id"
                            class="flex flex-wrap items-start justify-between gap-3 p-4 sm:p-5"
                            :class="fusion.revertida_at ? 'bg-surface-50' : ''"
                        >
                            <div class="min-w-0">
                                <p class="text-body-strong text-ink">
                                    <span class="text-ink-600 line-through">{{ fusion.duplicada }}</span>
                                    <span class="mx-1.5 text-ink-600" aria-hidden="true">→</span>
                                    <span class="sr-only">se fusionó en</span>
                                    <Link :href="route('padron.show', fusion.principal_id)" :class="['rounded-sm text-action hover:underline', claseFoco]">
                                        {{ fusion.principal }}
                                    </Link>
                                </p>
                                <p class="mt-1 text-small text-ink-600">
                                    {{ fusion.usuario }} · <span class="font-mono">{{ fecha(fusion.fecha) }}</span>
                                    <span v-if="fusion.criterio"> · criterio: {{ fusion.criterio }}</span>
                                </p>
                                <p v-if="fusion.motivo" class="mt-1 text-small text-ink">«{{ fusion.motivo }}»</p>
                                <p v-if="Object.keys(fusion.vinculos_movidos).length" class="mt-1.5 text-small text-ink-600">
                                    Movidos:
                                    <span v-for="(total, tabla) in fusion.vinculos_movidos" :key="tabla" class="mr-2">
                                        {{ tabla.replace(/_/g, ' ') }} (<span class="font-mono">{{ total }}</span>)
                                    </span>
                                </p>
                            </div>

                            <div class="shrink-0 text-left sm:text-right">
                                <p v-if="fusion.revertida_at" class="inline-flex h-6 items-center rounded-sm bg-surface-100 px-2 text-caption text-ink">
                                    Revertida el <span class="ml-1 font-mono">{{ fecha(fusion.revertida_at) }}</span>
                                </p>
                                <template v-else>
                                    <SecondaryButton v-if="fusion.es_revertible" @click="pedirReversion(fusion)">
                                        <IconoNav icono="historial" class="h-4 w-4" />
                                        Deshacer fusión
                                    </SecondaryButton>
                                    <p class="mt-1 text-caption text-ink-600">
                                        <template v-if="fusion.es_revertible">
                                            Se puede deshacer hasta el <span class="font-mono">{{ fecha(fusion.revertible_hasta) }}</span>
                                        </template>
                                        <template v-else>La ventana para deshacer ya cerró</template>
                                    </p>
                                </template>
                            </div>
                        </li>
                    </ul>

                    <div v-else class="rounded-lg border border-line bg-surface p-6">
                        <p class="text-body-strong text-ink">Todavía no se ha fusionado ninguna ficha.</p>
                        <p class="mt-1 text-small text-ink-600">Las fusiones que hagas desde «Por revisar» aparecerán aquí y se podrán deshacer durante {{ diasParaRevertir }} días.</p>
                    </div>
                </section>
            </div>
        </div>

        <!-- Confirmación: nombra a las personas involucradas -->
        <ConfirmationModal :show="confirmacion !== null" @close="confirmacion = null">
            <template #title>
                <template v-if="confirmacion?.tipo === 'fusion'">Fusionar a {{ confirmacion.duplicada.nombre_completo }}</template>
                <template v-else-if="confirmacion?.tipo === 'revertir'">Deshacer la fusión de {{ confirmacion.fusion.duplicada }}</template>
            </template>

            <template #content>
                <template v-if="confirmacion?.tipo === 'fusion'">
                    <p>
                        La ficha de <strong class="text-ink">{{ confirmacion.duplicada.nombre_completo }}</strong>
                        (ID <span class="font-mono">{{ confirmacion.duplicada.id }}</span>) se archivará y sus trámites, etiquetas e
                        historial pasarán a <strong class="text-ink">{{ confirmacion.principal?.nombre_completo }}</strong>
                        (ID <span class="font-mono">{{ confirmacion.principal?.id }}</span>).
                    </p>
                    <p class="mt-2">Podrás deshacerlo durante {{ diasParaRevertir }} días.</p>
                    <p v-if="!formFusion.motivo" class="mt-2 text-small">No escribiste motivo; la fusión quedará en la bitácora sin explicación.</p>
                </template>
                <template v-else-if="confirmacion?.tipo === 'revertir'">
                    <p>
                        <strong class="text-ink">{{ confirmacion.fusion.duplicada }}</strong> volverá a ser una ficha independiente y los
                        vínculos que se movieron a <strong class="text-ink">{{ confirmacion.fusion.principal }}</strong> regresarán a ella.
                    </p>
                </template>
            </template>

            <template #footer>
                <SecondaryButton @click="confirmacion = null">Cancelar</SecondaryButton>
                <DangerButton :procesando="procesandoConfirmacion" @click="confirmar">
                    <template v-if="confirmacion?.tipo === 'fusion'">{{ formFusion.processing ? 'Fusionando…' : 'Fusionar fichas' }}</template>
                    <template v-else>{{ revirtiendo ? 'Deshaciendo…' : 'Deshacer fusión' }}</template>
                </DangerButton>
            </template>
        </ConfirmationModal>
    </AppLayout>
</template>
