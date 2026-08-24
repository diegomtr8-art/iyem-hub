<script setup>
import { computed, ref } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import IconoModulo from '@/Components/IconoModulo.vue';
import IconoNav from '@/Components/IconoNav.vue';
import Paginacion from '@/Components/Paginacion.vue';
import { usePermisos } from '@/Composables/usePermisos';

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

const fechaCorta = (valor) =>
    new Date(valor).toLocaleDateString('es-MX', { day: 'numeric', month: 'long', year: 'numeric' });

const fechaHora = (valor) =>
    new Date(valor).toLocaleString('es-MX', { dateStyle: 'medium', timeStyle: 'short' });

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

const coloresModulo = {
    'iyem-primario': 'bg-iyem-gradient',
    'iyem-secundario': 'bg-gradient-to-br from-iyem-secundario to-iyem-400',
    'iyem-dorado': 'bg-gradient-to-br from-iyem-dorado to-amber-600',
};

const fondo = (color) => coloresModulo[color] ?? coloresModulo['iyem-primario'];

const colorEstado = (estado) => ({
    activa: 'bg-iyem-exito/10 text-iyem-exito ring-iyem-exito/25',
    inactiva: 'bg-gray-100 text-gray-600 ring-gray-300',
    bloqueada: 'bg-iyem-error/10 text-iyem-error ring-iyem-error/25',
}[estado] ?? 'bg-gray-100 text-gray-600 ring-gray-300');

const iniciales = computed(() =>
    props.persona.nombre_completo
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((p) => p[0])
        .join('')
        .toUpperCase(),
);
</script>

<template>
    <AppLayout :title="persona.nombre_completo">
        <template #header>
            <div class="flex min-w-0 items-center gap-2">
                <Link
                    :href="route('padron.index')"
                    class="shrink-0 text-gray-400 transition hover:text-iyem-primario focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-iyem-secundario"
                    aria-label="Volver al padrón"
                >
                    <IconoNav icono="arrow" class="h-5 w-5 rotate-180" />
                </Link>
                <span class="truncate">Ficha de la persona</span>
            </div>
        </template>

        <!-- ============================================================
             Encabezado de la persona
             ============================================================ -->
        <section class="relative overflow-hidden rounded-2xl bg-iyem-gradient shadow-soft-lg">
            <div class="pointer-events-none absolute inset-0 bg-iyem-mesh" aria-hidden="true" />

            <div class="relative flex flex-col gap-4 px-5 py-6 sm:flex-row sm:items-center sm:px-8">
                <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl border border-white/20 bg-white/10 text-xl font-bold text-white backdrop-blur-sm">
                    {{ iniciales }}
                </div>

                <div class="min-w-0 flex-1">
                    <h1 class="text-xl font-bold text-white sm:text-2xl">
                        {{ persona.nombre_completo }}
                    </h1>
                    <div class="mt-2 flex flex-wrap items-center gap-2">
                        <span
                            class="rounded-full bg-white/15 px-2.5 py-0.5 text-xs font-semibold capitalize text-white ring-1 ring-inset ring-white/20"
                        >
                            {{ persona.estado_persona }}
                        </span>
                        <span class="rounded-full bg-white/15 px-2.5 py-0.5 text-xs font-semibold capitalize text-white ring-1 ring-inset ring-white/20">
                            Persona {{ persona.tipo_persona }}
                        </span>
                        <span v-if="persona.municipio" class="text-sm text-white/70">
                            {{ persona.municipio }}
                        </span>
                        <span
                            v-if="persona.demo"
                            class="rounded-full bg-iyem-dorado/90 px-2.5 py-0.5 text-xs font-bold uppercase tracking-wide text-white"
                        >
                            Demostración
                        </span>
                    </div>
                </div>

                <p class="shrink-0 text-sm tabular-nums text-white/60">
                    ID {{ persona.id }}
                </p>
            </div>
        </section>

        <!-- ============================================================
             Pestañas
             ============================================================ -->
        <div class="scrollbar-fina mt-6 flex gap-1 overflow-x-auto border-b border-iyem-200" role="tablist">
            <button
                v-for="pestana in pestanas"
                :key="pestana.clave"
                type="button"
                role="tab"
                :aria-selected="pestanaActiva === pestana.clave"
                class="flex min-h-[44px] shrink-0 items-center gap-2 border-b-2 px-3.5 text-sm font-medium transition-colors duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-iyem-secundario"
                :class="pestanaActiva === pestana.clave
                    ? 'border-iyem-secundario text-iyem-700'
                    : 'border-transparent text-gray-500 hover:text-gray-700'"
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
        <section v-show="pestanaActiva === 'datos'" class="mt-5">
            <div v-if="totalVacios" class="mb-4 flex items-center justify-end">
                <label class="inline-flex cursor-pointer items-center gap-2 text-sm text-gray-500">
                    <input
                        v-model="mostrarVacios"
                        type="checkbox"
                        class="rounded border-gray-300 text-iyem-primario focus:ring-iyem-secundario"
                    >
                    Mostrar los {{ totalVacios }} campos sin capturar
                </label>
            </div>

            <div class="grid gap-4 xl:grid-cols-2">
                <div
                    v-for="seccion in secciones"
                    :key="seccion.clave"
                    class="h-fit overflow-hidden rounded-2xl border border-iyem-200 bg-white shadow-soft"
                >
                    <button
                        type="button"
                        class="flex min-h-[52px] w-full items-center gap-3 px-4 text-left transition-colors duration-200 hover:bg-iyem-50/60 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-iyem-secundario"
                        :aria-expanded="abiertas[seccion.clave]"
                        @click="abiertas[seccion.clave] = !abiertas[seccion.clave]"
                    >
                        <IconoNav :icono="seccion.icono" class="h-5 w-5 text-iyem-primario" />
                        <span class="flex-1 font-semibold text-gray-800">{{ seccion.titulo }}</span>
                        <IconoNav
                            icono="chevron"
                            class="h-4 w-4 text-gray-400 transition-transform duration-200"
                            :class="abiertas[seccion.clave] ? 'rotate-180' : ''"
                        />
                    </button>

                    <dl v-show="abiertas[seccion.clave]" class="divide-y divide-iyem-100 border-t border-iyem-100">
                        <div
                            v-for="campo in camposVisibles(seccion)"
                            :key="campo.nombre"
                            class="flex flex-col gap-0.5 px-4 py-2.5 sm:flex-row sm:gap-4"
                        >
                            <dt class="text-sm text-gray-400 sm:w-44 sm:shrink-0">
                                {{ campo.etiqueta }}
                            </dt>
                            <dd class="min-w-0 break-words text-sm text-gray-800">
                                <span v-if="campo.valor === null" class="text-gray-300">Sin capturar</span>

                                <a
                                    v-else-if="campo.tipo === 'correo'"
                                    :href="`mailto:${campo.valor}`"
                                    class="text-iyem-700 underline-offset-2 hover:underline"
                                >{{ campo.valor }}</a>

                                <a
                                    v-else-if="campo.tipo === 'telefono'"
                                    :href="`tel:${campo.valor}`"
                                    class="tabular-nums text-iyem-700 underline-offset-2 hover:underline"
                                >{{ campo.valor }}</a>

                                <a
                                    v-else-if="campo.tipo === 'url'"
                                    :href="campo.valor"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex items-center gap-1 break-all text-iyem-700 underline-offset-2 hover:underline"
                                >
                                    {{ campo.valor }}
                                    <IconoNav icono="externo" class="h-3.5 w-3.5 shrink-0" />
                                </a>

                                <span v-else-if="campo.tipo === 'fecha'">{{ fechaCorta(campo.valor) }}</span>

                                <span v-else-if="campo.tipo === 'numero'" class="tabular-nums">{{ campo.valor }}</span>

                                <span v-else>{{ campo.valor }}</span>
                            </dd>
                        </div>

                        <p
                            v-if="!camposVisibles(seccion).length"
                            class="px-4 py-4 text-sm text-gray-400"
                        >
                            Esta sección no tiene datos capturados.
                        </p>
                    </dl>
                </div>
            </div>
        </section>

        <!-- ============================================================
             Línea de tiempo unificada
             ============================================================ -->
        <section v-show="pestanaActiva === 'tiempo'" class="mt-5">
            <ol v-if="lineaDeTiempo.length" class="relative space-y-4 border-l-2 border-iyem-100 pl-6 sm:pl-8">
                <li
                    v-for="(evento, i) in lineaDeTiempo"
                    :key="i"
                    class="relative"
                >
                    <span
                        class="absolute -left-[calc(1.5rem+1px)] top-3 flex h-6 w-6 -translate-x-1/2 items-center justify-center rounded-full text-white ring-4 ring-iyem-neutro sm:-left-[calc(2rem+1px)]"
                        :class="fondo(evento.modulo_color)"
                    >
                        <IconoModulo :icono="evento.modulo_icono" class="h-3.5 w-3.5" />
                    </span>

                    <div class="rounded-2xl border border-iyem-200 bg-white p-4 shadow-soft">
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <p class="font-medium text-gray-800">
                                {{ evento.titulo }}
                            </p>
                            <time
                                class="shrink-0 text-xs tabular-nums text-gray-400"
                                :datetime="evento.fecha"
                            >
                                {{ fechaCorta(evento.fecha) }}
                            </time>
                        </div>

                        <p class="mt-1 text-sm text-gray-500">
                            {{ evento.detalle }}
                        </p>

                        <div class="mt-2.5 flex flex-wrap items-center gap-2">
                            <span class="rounded-full bg-iyem-claro px-2 py-0.5 text-[0.7rem] font-semibold text-iyem-700">
                                {{ evento.modulo_nombre }}
                            </span>
                            <span
                                v-if="evento.estado"
                                class="rounded-full bg-gray-100 px-2 py-0.5 text-[0.7rem] font-medium capitalize text-gray-600"
                            >
                                {{ evento.estado.replace('_', ' ') }}
                            </span>
                        </div>
                    </div>
                </li>
            </ol>

            <p v-else class="rounded-2xl border border-dashed border-iyem-200 px-4 py-10 text-center text-sm text-gray-500">
                Esta persona todavía no tiene movimientos registrados en ningún módulo.
            </p>
        </section>

        <!-- ============================================================
             Vínculos por módulo
             ============================================================ -->
        <section v-show="pestanaActiva === 'vinculos'" class="mt-5">
            <div v-if="vinculos.length" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <component
                    :is="vinculo.url ? 'a' : 'div'"
                    v-for="vinculo in vinculos"
                    :key="vinculo.slug"
                    :href="vinculo.url"
                    :target="vinculo.url ? '_blank' : undefined"
                    :rel="vinculo.url ? 'noopener noreferrer' : undefined"
                    class="group flex items-center gap-4 rounded-2xl border border-iyem-200 bg-white p-4 shadow-soft transition-all duration-200"
                    :class="vinculo.url ? 'hover:-translate-y-0.5 hover:shadow-soft-lg focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-iyem-secundario' : ''"
                >
                    <div
                        class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl text-white shadow-glow"
                        :class="fondo(vinculo.color)"
                    >
                        <IconoModulo :icono="vinculo.icono" />
                    </div>

                    <div class="min-w-0 flex-1">
                        <p class="font-semibold text-gray-800">
                            {{ vinculo.nombre }}
                        </p>
                        <p class="mt-0.5 text-sm text-gray-500">
                            {{ vinculo.descripcion }}
                        </p>
                    </div>

                    <span class="shrink-0 text-2xl font-bold tabular-nums text-iyem-700">
                        {{ vinculo.total }}
                    </span>
                </component>
            </div>

            <p v-else class="rounded-2xl border border-dashed border-iyem-200 px-4 py-10 text-center text-sm text-gray-500">
                Esta persona no tiene registros en ningún módulo del ecosistema.
            </p>
        </section>

        <!-- ============================================================
             Etiquetas
             ============================================================ -->
        <section v-show="pestanaActiva === 'etiquetas'" class="mt-5">
            <div class="rounded-2xl border border-iyem-200 bg-white p-5 shadow-soft">
                <h3 class="font-semibold text-gray-800">
                    Etiquetas de la persona
                </h3>

                <div v-if="etiquetas.length" class="mt-3 flex flex-wrap gap-2">
                    <span
                        v-for="etiqueta in etiquetas"
                        :key="etiqueta"
                        class="inline-flex items-center gap-1.5 rounded-full bg-iyem-claro py-1 pl-3 pr-1.5 text-sm font-medium text-iyem-700"
                    >
                        {{ etiqueta }}
                        <button
                            v-if="puede('editar-padron')"
                            type="button"
                            class="flex h-6 w-6 items-center justify-center rounded-full text-iyem-500 transition hover:bg-iyem-200 hover:text-iyem-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-iyem-secundario"
                            :aria-label="`Quitar la etiqueta ${etiqueta}`"
                            @click="quitarEtiqueta(etiqueta)"
                        >
                            <IconoNav icono="close" class="h-3.5 w-3.5" />
                        </button>
                    </span>
                </div>

                <p v-else class="mt-2 text-sm text-gray-400">
                    Sin etiquetas.
                </p>

                <template v-if="puede('editar-padron')">
                    <form class="mt-5 flex flex-col gap-2 sm:flex-row" @submit.prevent="agregarEtiqueta()">
                        <input
                            v-model="formEtiqueta.etiqueta"
                            list="etiquetas-sugeridas"
                            type="text"
                            maxlength="60"
                            autocapitalize="none"
                            placeholder="Escribe o elige una etiqueta…"
                            aria-label="Nueva etiqueta"
                            class="h-11 flex-1 rounded-lg border-gray-300 text-sm shadow-sm focus:border-iyem-secundario focus:ring-iyem-secundario"
                        >
                        <datalist id="etiquetas-sugeridas">
                            <option v-for="sugerencia in sugerenciasLibres" :key="sugerencia" :value="sugerencia" />
                        </datalist>

                        <button
                            type="submit"
                            :disabled="formEtiqueta.processing || !formEtiqueta.etiqueta.trim()"
                            class="toque-minimo inline-flex items-center justify-center gap-2 rounded-lg bg-iyem-gradient px-4 text-sm font-semibold text-white shadow-soft transition hover:shadow-glow disabled:opacity-50 disabled:hover:shadow-soft"
                        >
                            <IconoNav icono="mas" class="h-4 w-4" />
                            Agregar
                        </button>
                    </form>

                    <p v-if="formEtiqueta.errors.etiqueta" class="mt-2 text-sm text-iyem-error">
                        {{ formEtiqueta.errors.etiqueta }}
                    </p>

                    <div v-if="sugerenciasLibres.length" class="mt-4">
                        <p class="text-xs uppercase tracking-wide text-gray-400">
                            Sugerencias
                        </p>
                        <div class="mt-2 flex flex-wrap gap-1.5">
                            <button
                                v-for="sugerencia in sugerenciasLibres.slice(0, 12)"
                                :key="sugerencia"
                                type="button"
                                class="rounded-full border border-iyem-200 px-2.5 py-1 text-xs text-gray-600 transition hover:border-iyem-400 hover:text-iyem-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-iyem-secundario"
                                @click="agregarEtiqueta(sugerencia)"
                            >
                                + {{ sugerencia }}
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </section>

        <!-- ============================================================
             Auditoría
             ============================================================ -->
        <section v-show="pestanaActiva === 'auditoria'" class="mt-5">
            <div class="flex flex-col gap-2 sm:flex-row">
                <select
                    v-model="filtroCampo"
                    aria-label="Filtrar por campo"
                    class="h-11 rounded-lg border-gray-300 text-sm shadow-sm focus:border-iyem-secundario focus:ring-iyem-secundario"
                    @change="filtrarAuditoria"
                >
                    <option value="">
                        Todos los campos
                    </option>
                    <option v-for="campo in camposAuditados" :key="campo" :value="campo">
                        {{ campo }}
                    </option>
                </select>

                <select
                    v-model="filtroModulo"
                    aria-label="Filtrar por módulo de origen"
                    class="h-11 rounded-lg border-gray-300 text-sm shadow-sm focus:border-iyem-secundario focus:ring-iyem-secundario"
                    @change="filtrarAuditoria"
                >
                    <option value="">
                        Todos los módulos
                    </option>
                    <option v-for="modulo in modulosAuditados" :key="modulo" :value="modulo">
                        {{ modulo }}
                    </option>
                </select>
            </div>

            <!-- Teléfono: tarjetas -->
            <ul v-if="auditorias.data.length" class="mt-4 space-y-3 md:hidden">
                <li
                    v-for="registro in auditorias.data"
                    :key="registro.id"
                    class="rounded-2xl border border-iyem-200 bg-white p-4 text-sm shadow-soft"
                >
                    <div class="flex items-start justify-between gap-2">
                        <p class="font-medium text-gray-800">
                            {{ registro.campo }}
                        </p>
                        <time class="shrink-0 text-xs text-gray-400">{{ fechaHora(registro.fecha) }}</time>
                    </div>
                    <p class="mt-2 break-words text-gray-600">
                        <span class="text-gray-400 line-through">{{ registro.valor_anterior || '—' }}</span>
                        <span class="mx-1.5 text-gray-300">→</span>
                        <span>{{ registro.valor_nuevo || '—' }}</span>
                    </p>
                    <p class="mt-2 text-xs text-gray-400">
                        {{ registro.usuario }} · {{ registro.modulo || 'sin módulo' }}
                    </p>
                </li>
            </ul>

            <!-- Tablet y escritorio: tabla -->
            <div
                v-if="auditorias.data.length"
                class="scrollbar-fina mt-4 hidden overflow-x-auto rounded-2xl border border-iyem-200 bg-white shadow-soft md:block"
            >
                <table class="min-w-full divide-y divide-iyem-100 text-sm">
                    <thead class="bg-iyem-50 text-left text-xs uppercase tracking-wide text-iyem-700">
                        <tr>
                            <th scope="col" class="px-4 py-3">
                                Campo
                            </th>
                            <th scope="col" class="px-4 py-3">
                                Antes
                            </th>
                            <th scope="col" class="px-4 py-3">
                                Después
                            </th>
                            <th scope="col" class="px-4 py-3">
                                Módulo
                            </th>
                            <th scope="col" class="px-4 py-3">
                                Usuario
                            </th>
                            <th scope="col" class="px-4 py-3">
                                Fecha
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-iyem-100">
                        <tr v-for="registro in auditorias.data" :key="registro.id">
                            <td class="px-4 py-3 font-medium text-gray-800">
                                {{ registro.campo }}
                            </td>
                            <td class="max-w-[16rem] truncate px-4 py-3 text-gray-400">
                                {{ registro.valor_anterior || '—' }}
                            </td>
                            <td class="max-w-[16rem] truncate px-4 py-3 text-gray-700">
                                {{ registro.valor_nuevo || '—' }}
                            </td>
                            <td class="px-4 py-3 text-gray-500">
                                {{ registro.modulo || '—' }}
                            </td>
                            <td class="px-4 py-3 text-gray-500">
                                {{ registro.usuario }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 tabular-nums text-gray-500">
                                {{ fechaHora(registro.fecha) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <p
                v-if="!auditorias.data.length"
                class="mt-4 rounded-2xl border border-dashed border-iyem-200 px-4 py-10 text-center text-sm text-gray-500"
            >
                No hay cambios registrados con esos filtros.
            </p>

            <Paginacion :paginador="auditorias" />
        </section>
    </AppLayout>
</template>
