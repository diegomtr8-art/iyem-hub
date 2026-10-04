<script setup>
import { computed, ref } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import IconoModulo from '@/Components/IconoModulo.vue';
import IconoNav from '@/Components/IconoNav.vue';
import InputError from '@/Components/InputError.vue';
import Paginacion from '@/Components/Paginacion.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { usePermisos } from '@/Composables/usePermisos';
import { fecha, fechaHora, numero } from '@/formato';

const props = defineProps({
    persona: { type: Object, required: true },
    secciones: { type: Array, default: () => [] },
    lineaDeTiempo: { type: Array, default: () => [] },
    vinculos: { type: Array, default: () => [] },
    etiquetas: { type: Array, default: () => [] },
    etiquetasSugeridas: { type: Array, default: () => [] },
    auditorias: { type: Object, required: true },
    filtrosAuditoria: { type: Object, default: () => ({}) },
    camposAuditados: { type: Array, default: () => [] },
    modulosAuditados: { type: Array, default: () => [] },
});

const { puede } = usePermisos();

/* ------------------------------------------------------------------ *
 * Pestañas
 * ------------------------------------------------------------------ */

const pestanas = [
    { clave: 'datos', texto: 'Datos generales', icono: 'lista' },
    { clave: 'tiempo', texto: 'Línea de tiempo', icono: 'historial' },
    { clave: 'vinculos', texto: 'Vínculos', icono: 'stack' },
    { clave: 'etiquetas', texto: 'Etiquetas', icono: 'etiqueta' },
    { clave: 'auditoria', texto: 'Auditoría', icono: 'shield' },
];

const pestanaActiva = ref('datos');

/* ------------------------------------------------------------------ *
 * Datos generales
 * ------------------------------------------------------------------ */

const abiertas = ref(
    Object.fromEntries(props.secciones.map((s) => [s.clave, s.abierta])),
);

const mostrarVacios = ref(false);

const camposVisibles = (seccion) =>
    mostrarVacios.value ? seccion.campos : seccion.campos.filter((c) => !c.vacio);

const totalVacios = computed(() =>
    props.secciones.reduce((n, s) => n + s.campos.filter((c) => c.vacio).length, 0),
);

// Claves e identificadores que se leen carácter por carácter: van en mono
// aunque en config/padron.php sean de tipo "texto".
const CAMPOS_MONO = ['curp', 'rfc', 'ine_clave', 'codigo_postal'];

const esMono = (campo) => ['telefono', 'numero', 'fecha'].includes(campo.tipo) || CAMPOS_MONO.includes(campo.nombre);

/* ------------------------------------------------------------------ *
 * Etiquetas
 * ------------------------------------------------------------------ */

const formEtiqueta = useForm({ etiqueta: '' });

const sugerenciasLibres = computed(() =>
    props.etiquetasSugeridas.filter((e) => !props.etiquetas.includes(e)),
);

const agregarEtiqueta = (valor = null) => {
    if (valor) formEtiqueta.etiqueta = valor;
    if (!formEtiqueta.etiqueta.trim()) return;

    formEtiqueta.post(route('padron.etiquetas.store', props.persona.id), {
        preserveScroll: true,
        onSuccess: () => formEtiqueta.reset(),
    });
};

const quitarEtiqueta = (etiqueta) => {
    router.delete(route('padron.etiquetas.destroy', [props.persona.id, etiqueta]), {
        preserveScroll: true,
    });
};

/* ------------------------------------------------------------------ *
 * Auditoría
 * ------------------------------------------------------------------ */

const filtroCampo = ref(props.filtrosAuditoria.campo ?? '');
const filtroModulo = ref(props.filtrosAuditoria.modulo ?? '');

const filtrarAuditoria = () => {
    router.get(
        route('padron.show', props.persona.id),
        { campo: filtroCampo.value, modulo: filtroModulo.value },
        { preserveState: true, preserveScroll: true, replace: true, only: ['auditorias', 'filtrosAuditoria'] },
    );
};

/* ------------------------------------------------------------------ *
 * Presentación
 * ------------------------------------------------------------------ */

// Acento por módulo, igual que en TarjetaModulo.vue: el guinda existe solo
// para CREA.
const acentos = {
    'guinda-700': 'bg-guinda-700 text-white',
    'brand-500': 'bg-surface-brand text-brand-500 dark:text-brand-300',
};

const acento = (color) => acentos[color] ?? acentos['brand-500'];

// Estado de la persona: fondo del tinte, texto ink, ícono del color.
const estadosPersona = {
    activa: { fondo: 'bg-success-surface', icono: 'text-success', trazo: 'exito' },
    inactiva: { fondo: 'bg-surface-100', icono: 'text-ink-400', trazo: 'reloj' },
    bloqueada: { fondo: 'bg-danger-surface', icono: 'text-danger', trazo: 'alerta' },
};

const estadoPersona = computed(() => estadosPersona[props.persona.estado_persona] ?? estadosPersona.inactiva);

const iniciales = computed(() =>
    props.persona.nombre_completo
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((p) => p[0])
        .join('')
        .toUpperCase(),
);

const claseEnlace = 'rounded-sm text-action underline-offset-2 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-focus focus-visible:ring-offset-2';
const claseSelect = 'h-10 rounded-md border-line-strong bg-surface text-body text-ink focus:border-focus focus:ring-2 focus:ring-focus focus:ring-offset-2';
const claseVacio = 'rounded-lg border border-line bg-surface p-6';
</script>

<template>
    <AppLayout :title="persona.nombre_completo">
        <template #header>
            <div class="flex min-w-0 items-center gap-2">
                <Link
                    :href="route('padron.index')"
                    class="toque-minimo -ml-2 flex shrink-0 items-center justify-center rounded-md text-ink-600 transition-colors hover:text-ink focus:outline-none focus-visible:ring-2 focus-visible:ring-focus"
                    aria-label="Volver al padrón"
                >
                    <IconoNav icono="arrow" class="h-5 w-5 rotate-180" />
                </Link>
                <span>Ficha de la persona</span>
            </div>
        </template>

        <div class="mx-auto max-w-7xl">
            <!-- ============================================================
                 Encabezado de la persona: zona teñida plana
                 ============================================================ -->
            <section class="rounded-lg border border-line bg-surface-brand p-5 sm:p-6" aria-labelledby="nombre-persona">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                    <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-brand-600 text-title text-white" aria-hidden="true">
                        {{ iniciales }}
                    </span>

                    <div class="min-w-0 flex-1">
                        <h1 id="nombre-persona" class="break-words text-display-lg text-ink">
                            {{ persona.nombre_completo }}
                        </h1>
                        <div class="mt-2 flex flex-wrap items-center gap-2">
                            <span class="inline-flex h-6 items-center gap-1 rounded-sm px-2 text-caption capitalize text-ink" :class="estadoPersona.fondo">
                                <IconoNav :icono="estadoPersona.trazo" class="h-3.5 w-3.5" :class="estadoPersona.icono" />
                                {{ persona.estado_persona }}
                            </span>
                            <span class="inline-flex h-6 items-center rounded-sm bg-surface px-2 text-caption text-ink">
                                Persona {{ persona.tipo_persona }}
                            </span>
                            <span v-if="persona.demo" class="inline-flex h-6 items-center gap-1 rounded-sm bg-warning-surface px-2 text-caption text-ink">
                                <IconoNav icono="alerta" class="h-3.5 w-3.5 text-warning" />
                                Demostración
                            </span>
                            <span v-if="persona.municipio" class="text-small text-ink-600">
                                {{ persona.municipio }}
                            </span>
                        </div>
                    </div>

                    <p class="shrink-0 text-small text-ink-600">
                        ID <span class="font-mono text-ink">{{ persona.id }}</span>
                    </p>
                </div>
            </section>

            <!-- ============================================================
                 Pestañas
                 ============================================================ -->
            <div class="scrollbar-fina mt-6 flex gap-1 overflow-x-auto border-b border-line" role="tablist" aria-label="Secciones de la ficha">
                <button
                    v-for="pestana in pestanas"
                    :id="`pestana-${pestana.clave}`"
                    :key="pestana.clave"
                    type="button"
                    role="tab"
                    :aria-selected="pestanaActiva === pestana.clave ? 'true' : 'false'"
                    :aria-controls="`panel-${pestana.clave}`"
                    class="-mb-px flex min-h-[44px] shrink-0 items-center gap-2 border-b-2 px-3 text-body font-medium transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-focus"
                    :class="pestanaActiva === pestana.clave
                        ? 'border-action text-action'
                        : 'border-transparent text-ink-600 hover:text-ink'"
                    @click="pestanaActiva = pestana.clave"
                >
                    <IconoNav :icono="pestana.icono" class="h-4 w-4" />
                    {{ pestana.texto }}
                </button>
            </div>

            <!-- ============================================================
                 Datos generales
                 En iPad horizontal y escritorio, dos columnas: las secciones
                 son cortas y en una sola columna desperdician el ancho.
                 ============================================================ -->
            <section
                v-show="pestanaActiva === 'datos'"
                id="panel-datos"
                role="tabpanel"
                aria-labelledby="pestana-datos"
                class="mt-6"
            >
                <div v-if="totalVacios" class="mb-4 flex items-center justify-end">
                    <label class="inline-flex min-h-[44px] cursor-pointer items-center gap-2 text-small text-ink-600">
                        <input
                            v-model="mostrarVacios"
                            type="checkbox"
                            class="h-4 w-4 rounded-sm border-line-strong bg-surface text-action focus:ring-2 focus:ring-focus focus:ring-offset-2"
                        >
                        Mostrar los <span class="font-mono">{{ totalVacios }}</span> campos sin capturar
                    </label>
                </div>

                <div class="grid gap-6 xl:grid-cols-2">
                    <div
                        v-for="seccion in secciones"
                        :key="seccion.clave"
                        class="h-fit overflow-hidden rounded-lg border border-line bg-surface shadow-sm"
                    >
                        <h3>
                            <button
                                type="button"
                                class="flex min-h-[52px] w-full items-center gap-3 px-5 text-left transition-colors hover:bg-surface-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-focus"
                                :aria-expanded="abiertas[seccion.clave] ? 'true' : 'false'"
                                @click="abiertas[seccion.clave] = !abiertas[seccion.clave]"
                            >
                                <IconoNav :icono="seccion.icono" class="h-5 w-5 text-action" />
                                <span class="flex-1 text-subtitle text-ink">{{ seccion.titulo }}</span>
                                <IconoNav
                                    icono="chevron"
                                    class="h-4 w-4 text-ink-600 transition-transform"
                                    :class="abiertas[seccion.clave] ? 'rotate-180' : ''"
                                />
                            </button>
                        </h3>

                        <dl v-show="abiertas[seccion.clave]" class="divide-y divide-line border-t border-line">
                            <div
                                v-for="campo in camposVisibles(seccion)"
                                :key="campo.nombre"
                                class="flex flex-col gap-0.5 px-5 py-3 sm:flex-row sm:gap-4"
                            >
                                <dt class="text-small text-ink-600 sm:w-44 sm:shrink-0">
                                    {{ campo.etiqueta }}
                                </dt>
                                <dd class="min-w-0 break-words text-body text-ink" :class="esMono(campo) ? 'font-mono' : ''">
                                    <span v-if="campo.valor === null" class="font-sans text-ink-400">Sin capturar</span>

                                    <a
                                        v-else-if="campo.tipo === 'correo'"
                                        :href="`mailto:${campo.valor}`"
                                        :class="claseEnlace"
                                    >{{ campo.valor }}</a>

                                    <a
                                        v-else-if="campo.tipo === 'telefono'"
                                        :href="`tel:${campo.valor}`"
                                        :class="claseEnlace"
                                    >{{ campo.valor }}</a>

                                    <a
                                        v-else-if="campo.tipo === 'url'"
                                        :href="campo.valor"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        :class="[claseEnlace, 'inline-flex items-center gap-1 break-all']"
                                    >
                                        {{ campo.valor }}
                                        <IconoNav icono="externo" class="h-3.5 w-3.5 shrink-0" />
                                        <span class="sr-only">(se abre en una pestaña nueva)</span>
                                    </a>

                                    <span v-else-if="campo.tipo === 'fecha'">{{ fecha(campo.valor) }}</span>

                                    <span v-else>{{ campo.valor }}</span>
                                </dd>
                            </div>

                            <p v-if="!camposVisibles(seccion).length" class="px-5 py-4 text-small text-ink-600">
                                Esta sección no tiene datos capturados.
                                <template v-if="!mostrarVacios && seccion.campos.length">Activa «Mostrar los campos sin capturar» para verlos.</template>
                            </p>
                        </dl>
                    </div>
                </div>
            </section>

            <!-- ============================================================
                 Línea de tiempo unificada
                 ============================================================ -->
            <section
                v-show="pestanaActiva === 'tiempo'"
                id="panel-tiempo"
                role="tabpanel"
                aria-labelledby="pestana-tiempo"
                class="mt-6"
            >
                <ol v-if="lineaDeTiempo.length" class="relative space-y-4 border-l-2 border-line pl-6 sm:pl-8">
                    <li v-for="(evento, i) in lineaDeTiempo" :key="i" class="relative">
                        <span
                            class="absolute -left-[calc(1.5rem+1px)] top-4 flex h-7 w-7 -translate-x-1/2 items-center justify-center rounded-full ring-4 ring-[color:var(--surface-050)] sm:-left-[calc(2rem+1px)]"
                            :class="acento(evento.modulo_color)"
                            aria-hidden="true"
                        >
                            <IconoModulo :icono="evento.modulo_icono" class="h-4 w-4" />
                        </span>

                        <div class="rounded-lg border border-line bg-surface p-5 shadow-sm">
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <h3 class="text-body-strong text-ink">{{ evento.titulo }}</h3>
                                <time class="shrink-0 font-mono text-small text-ink-600" :datetime="evento.fecha">
                                    {{ fecha(evento.fecha) }}
                                </time>
                            </div>

                            <p class="mt-1 text-small text-ink-600">{{ evento.detalle }}</p>

                            <div class="mt-3 flex flex-wrap items-center gap-2">
                                <span class="inline-flex h-6 items-center rounded-sm bg-surface-brand px-2 text-caption text-ink">
                                    {{ evento.modulo_nombre }}
                                </span>
                                <span v-if="evento.estado" class="inline-flex h-6 items-center rounded-sm bg-surface-100 px-2 text-caption capitalize text-ink">
                                    {{ evento.estado.replace('_', ' ') }}
                                </span>
                            </div>
                        </div>
                    </li>
                </ol>

                <div v-else :class="claseVacio">
                    <p class="text-body-strong text-ink">Sin movimientos registrados.</p>
                    <p class="mt-1 text-small text-ink-600">
                        Cuando esta persona tenga un trámite en algún módulo del ecosistema, aparecerá aquí en orden cronológico.
                    </p>
                </div>
            </section>

            <!-- ============================================================
                 Vínculos por módulo
                 ============================================================ -->
            <section
                v-show="pestanaActiva === 'vinculos'"
                id="panel-vinculos"
                role="tabpanel"
                aria-labelledby="pestana-vinculos"
                class="mt-6"
            >
                <div v-if="vinculos.length" class="grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
                    <component
                        :is="vinculo.url ? 'a' : 'div'"
                        v-for="vinculo in vinculos"
                        :key="vinculo.slug"
                        :href="vinculo.url"
                        :target="vinculo.url ? '_blank' : undefined"
                        :rel="vinculo.url ? 'noopener noreferrer' : undefined"
                        :aria-label="vinculo.url ? `${vinculo.nombre}: ${vinculo.total} registros (sitio externo, se abre en una pestaña nueva)` : undefined"
                        class="group flex items-center gap-4 rounded-lg border border-line bg-surface p-5 shadow-sm transition-colors"
                        :class="vinculo.url ? 'hover:border-line-strong hover:bg-surface-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-focus focus-visible:ring-offset-2' : ''"
                    >
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md" :class="acento(vinculo.color)">
                            <IconoModulo :icono="vinculo.icono" />
                        </span>

                        <div class="min-w-0 flex-1">
                            <h3 class="flex items-center gap-1.5 text-subtitle text-ink">
                                {{ vinculo.nombre }}
                                <IconoNav v-if="vinculo.url" icono="externo" class="h-4 w-4 text-action" />
                            </h3>
                            <p class="mt-0.5 text-small text-ink-600">{{ vinculo.descripcion }}</p>
                        </div>

                        <span class="shrink-0 text-number-lg text-ink">{{ numero(vinculo.total) }}</span>
                    </component>
                </div>

                <div v-else :class="claseVacio">
                    <p class="text-body-strong text-ink">Sin registros en otros módulos.</p>
                    <p class="mt-1 text-small text-ink-600">
                        Esta persona solo existe en el padrón. Cuando un módulo la registre, el vínculo aparecerá aquí.
                    </p>
                </div>
            </section>

            <!-- ============================================================
                 Etiquetas
                 ============================================================ -->
            <section
                v-show="pestanaActiva === 'etiquetas'"
                id="panel-etiquetas"
                role="tabpanel"
                aria-labelledby="pestana-etiquetas"
                class="mt-6"
            >
                <div class="rounded-lg border border-line bg-surface p-5 shadow-sm">
                    <h3 class="text-title text-ink">Etiquetas de la persona</h3>

                    <ul v-if="etiquetas.length" class="mt-4 flex flex-wrap gap-2" role="list">
                        <li
                            v-for="etiqueta in etiquetas"
                            :key="etiqueta"
                            class="inline-flex h-8 items-center gap-1 rounded-full bg-surface-brand pl-3 text-small text-ink"
                            :class="puede('editar-padron') ? 'pr-0.5' : 'pr-3'"
                        >
                            {{ etiqueta }}
                            <button
                                v-if="puede('editar-padron')"
                                type="button"
                                class="flex h-7 w-7 items-center justify-center rounded-full text-ink-600 transition-colors hover:bg-surface-100 hover:text-ink focus:outline-none focus-visible:ring-2 focus-visible:ring-focus"
                                :aria-label="`Quitar la etiqueta ${etiqueta}`"
                                @click="quitarEtiqueta(etiqueta)"
                            >
                                <IconoNav icono="close" class="h-3.5 w-3.5" />
                            </button>
                        </li>
                    </ul>

                    <p v-else class="mt-2 text-small text-ink-600">
                        Sin etiquetas todavía.<template v-if="puede('editar-padron')"> Agrega la primera con el campo de abajo.</template>
                    </p>

                    <template v-if="puede('editar-padron')">
                        <form class="mt-5 border-t border-line pt-5" @submit.prevent="agregarEtiqueta()">
                            <label for="nueva-etiqueta" class="block text-body-strong text-ink">Nueva etiqueta</label>
                            <div class="mt-1.5 flex flex-col gap-2 sm:flex-row">
                                <TextInput
                                    id="nueva-etiqueta"
                                    v-model="formEtiqueta.etiqueta"
                                    list="etiquetas-sugeridas"
                                    type="text"
                                    maxlength="60"
                                    autocapitalize="none"
                                    placeholder="Escribe o elige una etiqueta…"
                                    class="block w-full sm:flex-1"
                                    :invalido="Boolean(formEtiqueta.errors.etiqueta)"
                                    :aria-describedby="formEtiqueta.errors.etiqueta ? 'nueva-etiqueta-error' : undefined"
                                />
                                <datalist id="etiquetas-sugeridas">
                                    <option v-for="sugerencia in sugerenciasLibres" :key="sugerencia" :value="sugerencia" />
                                </datalist>

                                <PrimaryButton
                                    :procesando="formEtiqueta.processing"
                                    :disabled="formEtiqueta.processing || !formEtiqueta.etiqueta.trim()"
                                    :title="!formEtiqueta.etiqueta.trim() ? 'Escribe una etiqueta para agregarla' : undefined"
                                >
                                    <IconoNav icono="mas" class="h-4 w-4" />
                                    {{ formEtiqueta.processing ? 'Agregando…' : 'Agregar etiqueta' }}
                                </PrimaryButton>
                            </div>
                            <InputError id="nueva-etiqueta-error" class="mt-1.5" :message="formEtiqueta.errors.etiqueta" />
                        </form>

                        <div v-if="sugerenciasLibres.length" class="mt-5">
                            <h4 class="text-overline text-ink-600">Sugerencias</h4>
                            <div class="mt-2 flex flex-wrap gap-2">
                                <button
                                    v-for="sugerencia in sugerenciasLibres.slice(0, 12)"
                                    :key="sugerencia"
                                    type="button"
                                    class="inline-flex min-h-[36px] items-center gap-1 rounded-full border border-line-strong px-3 text-small text-ink transition-colors hover:bg-surface-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-focus focus-visible:ring-offset-2"
                                    :aria-label="`Agregar la etiqueta ${sugerencia}`"
                                    @click="agregarEtiqueta(sugerencia)"
                                >
                                    <IconoNav icono="mas" class="h-3.5 w-3.5 text-action" />
                                    {{ sugerencia }}
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </section>

            <!-- ============================================================
                 Auditoría
                 ============================================================ -->
            <section
                v-show="pestanaActiva === 'auditoria'"
                id="panel-auditoria"
                role="tabpanel"
                aria-labelledby="pestana-auditoria"
                class="mt-6"
            >
                <div class="flex flex-col gap-4 rounded-lg border border-line bg-surface-50 p-4 sm:flex-row sm:items-end">
                    <div>
                        <label for="filtro-campo" class="block text-body-strong text-ink">Campo</label>
                        <select id="filtro-campo" v-model="filtroCampo" :class="[claseSelect, 'mt-1.5 w-full sm:w-56']" @change="filtrarAuditoria">
                            <option value="">Todos los campos</option>
                            <option v-for="campo in camposAuditados" :key="campo" :value="campo">{{ campo }}</option>
                        </select>
                    </div>

                    <div>
                        <label for="filtro-modulo" class="block text-body-strong text-ink">Módulo de origen</label>
                        <select id="filtro-modulo" v-model="filtroModulo" :class="[claseSelect, 'mt-1.5 w-full sm:w-56']" @change="filtrarAuditoria">
                            <option value="">Todos los módulos</option>
                            <option v-for="modulo in modulosAuditados" :key="modulo" :value="modulo">{{ modulo }}</option>
                        </select>
                    </div>
                </div>

                <template v-if="auditorias.data.length">
                    <!-- Teléfono: lista de tarjetas -->
                    <ul class="mt-4 divide-y divide-line overflow-hidden rounded-lg border border-line bg-surface md:hidden">
                        <li v-for="registro in auditorias.data" :key="registro.id" class="p-4">
                            <div class="flex items-start justify-between gap-2">
                                <p class="text-body-strong text-ink">{{ registro.campo }}</p>
                                <time class="shrink-0 font-mono text-small text-ink-600" :datetime="registro.fecha">{{ fechaHora(registro.fecha) }}</time>
                            </div>
                            <dl class="mt-2 grid grid-cols-[auto,1fr] gap-x-3 gap-y-1 text-small">
                                <dt class="text-ink-600">Antes</dt>
                                <dd class="break-words text-ink-600">{{ registro.valor_anterior || '—' }}</dd>
                                <dt class="text-ink-600">Después</dt>
                                <dd class="break-words text-ink">{{ registro.valor_nuevo || '—' }}</dd>
                            </dl>
                            <p class="mt-2 text-caption text-ink-600">
                                {{ registro.usuario }} · {{ registro.modulo || 'Sin módulo' }}
                            </p>
                        </li>
                    </ul>

                    <!-- Tablet y escritorio: tabla a sangre en su contenedor -->
                    <div class="scrollbar-fina mt-4 hidden max-h-[70vh] overflow-auto border border-line bg-surface md:block">
                        <table class="min-w-full text-left">
                            <thead class="sticky top-0 z-10 bg-surface-50">
                                <tr>
                                    <th scope="col" class="px-3 py-2 text-overline text-ink-600">Campo</th>
                                    <th scope="col" class="px-3 py-2 text-overline text-ink-600">Antes</th>
                                    <th scope="col" class="px-3 py-2 text-overline text-ink-600">Después</th>
                                    <th scope="col" class="px-3 py-2 text-overline text-ink-600">Módulo</th>
                                    <th scope="col" class="px-3 py-2 text-overline text-ink-600">Usuario</th>
                                    <th scope="col" class="px-3 py-2 text-overline text-ink-600">Fecha</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="registro in auditorias.data" :key="registro.id" class="border-t border-line hover:bg-surface-100">
                                    <td class="h-11 px-3 text-body-strong text-ink">{{ registro.campo }}</td>
                                    <td class="max-w-[16rem] truncate px-3 text-body text-ink-600" :title="registro.valor_anterior">{{ registro.valor_anterior || '—' }}</td>
                                    <td class="max-w-[16rem] truncate px-3 text-body text-ink" :title="registro.valor_nuevo">{{ registro.valor_nuevo || '—' }}</td>
                                    <td class="px-3 text-body text-ink-600">{{ registro.modulo || '—' }}</td>
                                    <td class="px-3 text-body text-ink-600">{{ registro.usuario }}</td>
                                    <td class="whitespace-nowrap px-3 text-number text-ink">
                                        <time :datetime="registro.fecha">{{ fechaHora(registro.fecha) }}</time>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </template>

                <div v-else :class="[claseVacio, 'mt-4']">
                    <p class="text-body-strong text-ink">No hay cambios registrados con esos filtros.</p>
                    <p class="mt-1 text-small text-ink-600">
                        <template v-if="filtroCampo || filtroModulo">Prueba con «Todos los campos» y «Todos los módulos».</template>
                        <template v-else>Cuando alguien modifique un dato de esta persona, el cambio quedará anotado aquí.</template>
                    </p>
                </div>

                <Paginacion :paginador="auditorias" />
            </section>
        </div>
    </AppLayout>
</template>
