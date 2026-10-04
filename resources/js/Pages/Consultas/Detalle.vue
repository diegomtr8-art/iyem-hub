<script setup>
import { computed, ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import IconoNav from '@/Components/IconoNav.vue';
import Grafica from '@/Components/Grafica.vue';
import Paginacion from '@/Components/Paginacion.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { numero } from '@/formato';

const props = defineProps({
    catalogo: { type: Array, default: () => [] },
    municipios: { type: Array, default: () => [] },
    consulta: { type: Object, required: true },
    filtros: { type: Object, default: () => ({}) },
    resumen: { type: Array, default: () => [] },
    tabla: { type: Object, required: true },
    grafica: { type: Object, default: null },
    puedeExportar: { type: Boolean, default: false },
});

/* ------------------------------------------------------------------ *
 * Filtros
 *
 * Todo vive en la query string: recargar, compartir o marcar la página
 * reproduce exactamente el mismo resultado.
 * ------------------------------------------------------------------ */

const valores = ref({
    desde: props.filtros.desde ?? '',
    hasta: props.filtros.hasta ?? '',
    municipio: props.filtros.municipio ?? '',
    ...Object.fromEntries(
        props.consulta.controles.map((control) => [
            control.nombre,
            props.filtros[control.nombre] ?? (control.tipo === 'checkbox-multiple' ? [] : ''),
        ]),
    ),
});

const aplicar = () => {
    const parametros = { consulta: props.consulta.clave };

    for (const [clave, valor] of Object.entries(valores.value)) {
        if (Array.isArray(valor) ? valor.length : valor !== '') {
            parametros[clave] = valor;
        }
    }

    router.get(route('consultas.index'), parametros, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

// Los selectores y las casillas aplican solos; las fechas y los números
// esperan al botón, para no consultar con una fecha a medio escribir.
watch(
    () => props.consulta.controles
        .filter((c) => c.tipo === 'select' || c.tipo === 'checkbox-multiple')
        .map((c) => valores.value[c.nombre]),
    aplicar,
    { deep: true },
);

watch(() => valores.value.municipio, aplicar);

const limpiar = () => {
    for (const clave of Object.keys(valores.value)) {
        valores.value[clave] = Array.isArray(valores.value[clave]) ? [] : '';
    }
    aplicar();
};

const hayFiltros = computed(() =>
    Object.values(valores.value).some((v) => (Array.isArray(v) ? v.length > 0 : v !== '')),
);

/* ------------------------------------------------------------------ *
 * Enlace permanente y exportación
 * ------------------------------------------------------------------ */

const enlaceCopiado = ref(false);

const copiarEnlace = async () => {
    try {
        await navigator.clipboard.writeText(window.location.href);
        enlaceCopiado.value = true;
        setTimeout(() => { enlaceCopiado.value = false; }, 2000);
    } catch {
        // Navegadores sin permiso de portapapeles: la URL ya está en la
        // barra de direcciones y se puede copiar a mano.
    }
};

const urlExportar = (formato) =>
    route('consultas.exportar', {
        clave: props.consulta.clave,
        ...props.filtros,
        formato,
    });

/* ------------------------------------------------------------------ *
 * Presentación
 * ------------------------------------------------------------------ */

const clavesDeColumna = computed(() => Object.keys(props.consulta.columnas));

// Cifra = número, o texto que es un porcentaje ya formateado por el backend
// ("5.9 %"). Las dos se alinean a la derecha en mono; solo los números se
// reformatean con separador de miles.
const esNumero = (valor) => typeof valor === 'number' || (typeof valor === 'string' && /^-?[\d.,]+\s*%$/.test(valor));

// Una columna es de cifras si alguna fila trae una: entonces se alinea a la
// derecha en mono, cabecera incluida.
const columnaNumerica = (clave) => props.tabla.data.some((fila) => esNumero(fila[clave]));

// La primera columna identifica el renglón; en móvil es el título de la tarjeta.
const claveTitulo = computed(() => clavesDeColumna.value[0]);
const clavesDetalle = computed(() => clavesDeColumna.value.slice(1));

const valorCelda = (valor) => (typeof valor === 'number' ? numero(valor) : (valor ?? '—'));

// Situaciones que piden atención. El texto ya dice qué pasa; el color y el
// ícono solo lo refuerzan.
const SITUACIONES = {
    'Sin presencia': 'text-danger',
    'Cobertura débil': 'text-warning',
    'Crítico': 'text-danger',
};

const claseSituacion = (valor) => SITUACIONES[valor] ?? '';

const claseCampo = 'h-10 w-full rounded-md border-line-strong bg-surface px-3 text-body text-ink focus:border-focus focus:outline-none focus:ring-2 focus:ring-focus focus:ring-offset-2';
const claseEtiqueta = 'block text-body-strong text-ink';
const claseSecundario = 'toque-minimo inline-flex items-center justify-center gap-2 rounded-md border border-line-strong bg-surface px-4 text-body-strong text-ink transition-colors hover:bg-surface-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-focus focus-visible:ring-offset-2';
</script>

<template>
    <AppLayout :title="consulta.titulo">
        <template #header>
            <div class="flex min-w-0 items-center gap-2">
                <Link
                    :href="route('consultas.index')"
                    class="toque-minimo -ml-2 flex shrink-0 items-center justify-center rounded-md text-ink-600 transition-colors hover:text-ink focus:outline-none focus-visible:ring-2 focus-visible:ring-focus"
                    aria-label="Volver a las consultas"
                >
                    <IconoNav icono="arrow" class="h-5 w-5 rotate-180" />
                </Link>
                <span class="truncate">{{ consulta.titulo }}</span>
            </div>
        </template>

        <div class="mx-auto max-w-7xl">
            <!-- Selector rápido de consulta. Scroll horizontal propio en
                 móvil para que nunca desplace el body. -->
            <nav aria-label="Otras consultas" class="scrollbar-fina -mx-1 flex gap-2 overflow-x-auto px-1 pb-2">
                <Link
                    v-for="opcion in catalogo"
                    :key="opcion.clave"
                    :href="route('consultas.index', { consulta: opcion.clave })"
                    class="toque-minimo inline-flex shrink-0 items-center gap-1.5 rounded-full border px-4 text-small font-medium transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-focus focus-visible:ring-offset-2"
                    :class="opcion.clave === consulta.clave
                        ? 'border-transparent bg-action text-action-ink'
                        : 'border-line-strong bg-surface text-ink hover:bg-surface-100'"
                    :aria-current="opcion.clave === consulta.clave ? 'page' : undefined"
                >
                    <IconoNav :icono="opcion.icono" class="h-4 w-4" />
                    {{ opcion.titulo }}
                </Link>
            </nav>

            <h1 class="mt-6 text-display-lg text-ink">{{ consulta.titulo }}</h1>
            <p class="mt-2 max-w-3xl text-body text-ink-600">
                {{ consulta.descripcion }}
            </p>

            <!-- ============================================================
                 Filtros
                 ============================================================ -->
            <form
                class="mt-6 rounded-lg border border-line bg-surface shadow-sm"
                aria-labelledby="titulo-filtros"
                @submit.prevent="aplicar"
            >
                <div class="border-b border-line bg-surface-50 px-5 py-3">
                    <h2 id="titulo-filtros" class="text-subtitle text-ink">Filtros</h2>
                </div>

                <div class="grid gap-4 p-5 sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <label for="desde" :class="claseEtiqueta">Desde</label>
                        <input id="desde" v-model="valores.desde" type="date" :class="[claseCampo, 'mt-1.5 font-mono']">
                    </div>

                    <div>
                        <label for="hasta" :class="claseEtiqueta">Hasta</label>
                        <input id="hasta" v-model="valores.hasta" type="date" :class="[claseCampo, 'mt-1.5 font-mono']">
                    </div>

                    <div>
                        <label for="municipio" :class="claseEtiqueta">Municipio</label>
                        <select id="municipio" v-model="valores.municipio" :class="[claseCampo, 'mt-1.5 pr-9']">
                            <option value="">Todos los municipios</option>
                            <option v-for="m in municipios" :key="m" :value="m">
                                {{ m }}
                            </option>
                        </select>
                    </div>

                    <!-- Controles propios de cada consulta -->
                    <template v-for="control in consulta.controles" :key="control.nombre">
                        <div v-if="control.tipo === 'select'">
                            <label :for="control.nombre" :class="claseEtiqueta">{{ control.etiqueta }}</label>
                            <select :id="control.nombre" v-model="valores[control.nombre]" :class="[claseCampo, 'mt-1.5 pr-9']">
                                <option v-for="(texto, clave) in control.opciones" :key="clave" :value="clave">
                                    {{ texto }}
                                </option>
                            </select>
                        </div>

                        <div v-else-if="control.tipo === 'numero'">
                            <label :for="control.nombre" :class="claseEtiqueta">{{ control.etiqueta }}</label>
                            <input
                                :id="control.nombre"
                                v-model="valores[control.nombre]"
                                type="number"
                                inputmode="numeric"
                                min="1"
                                :class="[claseCampo, 'mt-1.5 font-mono']"
                                :aria-describedby="control.ayuda ? `${control.nombre}-ayuda` : undefined"
                            >
                            <p v-if="control.ayuda" :id="`${control.nombre}-ayuda`" class="mt-1.5 text-small text-ink-600">
                                {{ control.ayuda }}
                            </p>
                        </div>

                        <fieldset v-else-if="control.tipo === 'checkbox-multiple'" class="sm:col-span-2 lg:col-span-4">
                            <legend :class="claseEtiqueta">{{ control.etiqueta }}</legend>
                            <p v-if="control.ayuda" class="mt-0.5 text-small text-ink-600">
                                {{ control.ayuda }}
                            </p>
                            <div class="mt-2 flex flex-wrap gap-2">
                                <!-- Píldora de filtro: la casilla real queda oculta y
                                     el foco se dibuja en la píldora completa. -->
                                <label
                                    v-for="(texto, clave) in control.opciones"
                                    :key="clave"
                                    class="toque-minimo inline-flex cursor-pointer items-center gap-2 rounded-full border px-4 text-small font-medium transition-colors focus-within:ring-2 focus-within:ring-focus focus-within:ring-offset-2"
                                    :class="valores[control.nombre].includes(clave)
                                        ? 'border-transparent bg-action text-action-ink'
                                        : 'border-line-strong bg-surface text-ink hover:bg-surface-100'"
                                >
                                    <input
                                        v-model="valores[control.nombre]"
                                        type="checkbox"
                                        :value="clave"
                                        class="sr-only"
                                    >
                                    <svg
                                        v-if="valores[control.nombre].includes(clave)"
                                        class="h-4 w-4"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke-width="2"
                                        stroke="currentColor"
                                        aria-hidden="true"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                    </svg>
                                    {{ texto }}
                                </label>
                            </div>
                        </fieldset>
                    </template>
                </div>

                <div class="flex flex-wrap items-center gap-3 border-t border-line bg-surface-50 px-5 py-3">
                    <PrimaryButton class="toque-minimo">
                        <IconoNav icono="filtro" class="h-4 w-4" />
                        Aplicar filtros
                    </PrimaryButton>

                    <button
                        v-if="hayFiltros"
                        type="button"
                        class="toque-minimo inline-flex items-center justify-center rounded-md px-3 text-body-strong text-action transition-colors hover:bg-surface-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-focus focus-visible:ring-offset-2"
                        @click="limpiar"
                    >
                        Limpiar filtros
                    </button>

                    <div class="flex w-full flex-wrap gap-3 sm:ml-auto sm:w-auto">
                        <button type="button" :class="claseSecundario" @click="copiarEnlace">
                            <IconoNav :icono="enlaceCopiado ? 'exito' : 'externo'" class="h-4 w-4" />
                            <span aria-live="polite">{{ enlaceCopiado ? 'Enlace copiado' : 'Copiar enlace' }}</span>
                        </button>

                        <template v-if="puedeExportar">
                            <a :href="urlExportar('xlsx')" :class="claseSecundario">
                                <IconoNav icono="descargar" class="h-4 w-4" />
                                Exportar XLSX
                            </a>
                            <a :href="urlExportar('csv')" :class="claseSecundario">
                                <IconoNav icono="descargar" class="h-4 w-4" />
                                Exportar CSV
                            </a>
                        </template>
                    </div>
                </div>
            </form>

            <!-- ============================================================
                 Resumen
                 ============================================================ -->
            <section v-if="resumen.length" class="mt-6" aria-label="Resumen de la consulta">
                <div class="grid grid-cols-2 gap-4 sm:gap-6 xl:grid-cols-4">
                    <div
                        v-for="(dato, i) in resumen"
                        :key="i"
                        class="rounded-lg border border-line bg-surface p-4 shadow-sm sm:p-5"
                    >
                        <p class="text-overline text-ink-600">{{ dato.etiqueta }}</p>
                        <p class="mt-2 text-number-display text-ink">
                            {{ valorCelda(dato.valor) }}
                        </p>
                        <p v-if="dato.detalle" class="mt-1 text-small text-ink-600">
                            {{ dato.detalle }}
                        </p>
                    </div>
                </div>
            </section>

            <!-- ============================================================
                 Gráfica
                 ============================================================ -->
            <section v-if="grafica" class="mt-6" aria-label="Gráfica de la consulta">
                <Grafica :datos="grafica" :etiqueta="`Gráfica de ${consulta.titulo}. Los mismos datos están en la tabla de resultados.`" />
            </section>

            <!-- ============================================================
                 Resultados
                 ============================================================ -->
            <section class="mt-8" aria-labelledby="titulo-resultados">
                <div class="flex flex-wrap items-baseline justify-between gap-2">
                    <h2 id="titulo-resultados" class="text-title text-ink">Resultados</h2>
                    <p v-if="tabla.total" class="text-small text-ink-600">
                        <span class="font-mono">{{ numero(tabla.total) }}</span>
                        {{ tabla.total === 1 ? 'renglón' : 'renglones' }}
                    </p>
                </div>

                <template v-if="tabla.data.length">
                    <!-- Teléfono: lista de tarjetas. Una tabla de varias columnas
                         con scroll horizontal no se lee en 375 px. -->
                    <ul class="mt-4 space-y-4 md:hidden" role="list">
                        <li
                            v-for="(fila, i) in tabla.data"
                            :key="i"
                            class="rounded-lg border border-line bg-surface p-4 shadow-sm"
                        >
                            <Link
                                v-if="claveTitulo === 'nombre_completo' && fila.id"
                                :href="route('padron.show', fila.id)"
                                class="rounded-sm text-body-strong text-action underline-offset-2 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-focus focus-visible:ring-offset-2"
                            >
                                {{ fila[claveTitulo] }}
                            </Link>
                            <p
                                v-else
                                class="text-body-strong"
                                :class="[esNumero(fila[claveTitulo]) ? 'font-mono' : '', claseSituacion(fila[claveTitulo]) || 'text-ink']"
                            >
                                {{ valorCelda(fila[claveTitulo]) }}
                            </p>

                            <dl v-if="clavesDetalle.length" class="mt-3 space-y-2 border-t border-line pt-3">
                                <div v-for="clave in clavesDetalle" :key="clave" class="flex items-baseline justify-between gap-4">
                                    <dt class="text-small text-ink-600">{{ consulta.columnas[clave] }}</dt>
                                    <dd
                                        class="min-w-0 break-words text-right"
                                        :class="[
                                            esNumero(fila[clave]) ? 'text-number' : 'text-body',
                                            claseSituacion(fila[clave]) ? `${claseSituacion(fila[clave])} font-semibold` : 'text-ink',
                                        ]"
                                    >
                                        <Link
                                            v-if="clave === 'nombre_completo' && fila.id"
                                            :href="route('padron.show', fila.id)"
                                            class="rounded-sm text-action underline-offset-2 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-focus"
                                        >
                                            {{ fila[clave] }}
                                        </Link>
                                        <template v-else>{{ valorCelda(fila[clave]) }}</template>
                                    </dd>
                                </div>
                            </dl>
                        </li>
                    </ul>

                    <!-- Tablet y escritorio: tabla a sangre dentro de su contenedor -->
                    <div class="scrollbar-fina scroll-suave-ios mt-4 hidden max-h-[70vh] overflow-auto border border-line bg-surface md:block">
                        <table class="min-w-full text-left">
                            <thead class="sticky top-0 z-10 bg-surface-50">
                                <tr>
                                    <th
                                        v-for="clave in clavesDeColumna"
                                        :key="clave"
                                        scope="col"
                                        class="whitespace-nowrap border-b border-line px-3 py-2 text-overline text-ink-600"
                                        :class="columnaNumerica(clave) ? 'text-right' : 'text-left'"
                                    >
                                        {{ consulta.columnas[clave] }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="(fila, i) in tabla.data"
                                    :key="i"
                                    class="border-b border-line transition-colors hover:bg-surface-100"
                                >
                                    <td
                                        v-for="clave in clavesDeColumna"
                                        :key="clave"
                                        class="h-11 px-3"
                                        :class="[
                                            esNumero(fila[clave]) ? 'text-right text-number' : 'text-body',
                                            claseSituacion(fila[clave]) ? `${claseSituacion(fila[clave])} font-semibold` : 'text-ink',
                                        ]"
                                    >
                                        <Link
                                            v-if="clave === 'nombre_completo' && fila.id"
                                            :href="route('padron.show', fila.id)"
                                            class="rounded-sm text-action underline-offset-2 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-focus"
                                        >
                                            {{ fila[clave] }}
                                        </Link>
                                        <template v-else>
                                            {{ valorCelda(fila[clave]) }}
                                        </template>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </template>

                <div v-else class="mt-4 rounded-lg border border-line bg-surface p-6">
                    <p class="text-body-strong text-ink">No hay resultados con estos filtros.</p>
                    <p class="mt-1 text-small text-ink-600">
                        Amplía el rango de fechas o quita algún filtro para ver más renglones.
                    </p>
                    <button
                        v-if="hayFiltros"
                        type="button"
                        :class="[claseSecundario, 'mt-4']"
                        @click="limpiar"
                    >
                        Limpiar filtros
                    </button>
                </div>

                <Paginacion :paginador="tabla" />
            </section>
        </div>
    </AppLayout>
</template>
