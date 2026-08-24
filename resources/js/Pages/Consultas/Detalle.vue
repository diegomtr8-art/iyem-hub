<script setup>
import { computed, ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import IconoNav from '@/Components/IconoNav.vue';
import Grafica from '@/Components/Grafica.vue';
import Paginacion from '@/Components/Paginacion.vue';

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

const esNumero = (valor) => typeof valor === 'number';

const claseSituacion = (valor) => ({
    'Sin presencia': 'text-iyem-error font-medium',
    'Cobertura débil': 'text-iyem-alerta font-medium',
    'Crítico': 'text-iyem-error font-medium',
}[valor] ?? '');
</script>

<template>
    <AppLayout :title="consulta.titulo">
        <template #header>
            <div class="flex min-w-0 items-center gap-2">
                <Link
                    :href="route('consultas.index')"
                    class="shrink-0 text-gray-400 transition hover:text-iyem-primario"
                    aria-label="Volver a las consultas"
                >
                    <IconoNav icono="arrow" class="h-5 w-5 rotate-180" />
                </Link>
                <span class="truncate">{{ consulta.titulo }}</span>
            </div>
        </template>

        <!-- Selector rápido de consulta -->
        <div class="scrollbar-fina -mx-1 flex gap-2 overflow-x-auto px-1 pb-1">
            <Link
                v-for="opcion in catalogo"
                :key="opcion.clave"
                :href="route('consultas.index', { consulta: opcion.clave })"
                class="inline-flex h-9 shrink-0 items-center gap-1.5 rounded-full border px-3.5 text-sm font-medium transition-all duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-iyem-secundario"
                :class="opcion.clave === consulta.clave
                    ? 'border-transparent bg-iyem-gradient text-white shadow-glow'
                    : 'border-iyem-200 bg-white text-gray-600 hover:border-iyem-300 hover:text-iyem-700'"
            >
                <IconoNav :icono="opcion.icono" class="h-4 w-4" />
                {{ opcion.titulo }}
            </Link>
        </div>

        <p class="mt-4 text-sm text-gray-500">
            {{ consulta.descripcion }}
        </p>

        <!-- ============================================================
             Filtros
             ============================================================ -->
        <form class="mt-4 rounded-2xl border border-iyem-200 bg-white p-4 shadow-soft" @submit.prevent="aplicar">
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <label for="desde" class="block text-xs font-medium uppercase tracking-wide text-gray-500">
                        Desde
                    </label>
                    <input
                        id="desde"
                        v-model="valores.desde"
                        type="date"
                        class="mt-1 h-11 w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-iyem-secundario focus:ring-iyem-secundario"
                    >
                </div>

                <div>
                    <label for="hasta" class="block text-xs font-medium uppercase tracking-wide text-gray-500">
                        Hasta
                    </label>
                    <input
                        id="hasta"
                        v-model="valores.hasta"
                        type="date"
                        class="mt-1 h-11 w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-iyem-secundario focus:ring-iyem-secundario"
                    >
                </div>

                <div>
                    <label for="municipio" class="block text-xs font-medium uppercase tracking-wide text-gray-500">
                        Municipio
                    </label>
                    <select
                        id="municipio"
                        v-model="valores.municipio"
                        class="mt-1 h-11 w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-iyem-secundario focus:ring-iyem-secundario"
                    >
                        <option value="">
                            Todos
                        </option>
                        <option v-for="m in municipios" :key="m" :value="m">
                            {{ m }}
                        </option>
                    </select>
                </div>

                <!-- Controles propios de cada consulta -->
                <template v-for="control in consulta.controles" :key="control.nombre">
                    <div v-if="control.tipo === 'select'">
                        <label :for="control.nombre" class="block text-xs font-medium uppercase tracking-wide text-gray-500">
                            {{ control.etiqueta }}
                        </label>
                        <select
                            :id="control.nombre"
                            v-model="valores[control.nombre]"
                            class="mt-1 h-11 w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-iyem-secundario focus:ring-iyem-secundario"
                        >
                            <option v-for="(texto, clave) in control.opciones" :key="clave" :value="clave">
                                {{ texto }}
                            </option>
                        </select>
                    </div>

                    <div v-else-if="control.tipo === 'numero'">
                        <label :for="control.nombre" class="block text-xs font-medium uppercase tracking-wide text-gray-500">
                            {{ control.etiqueta }}
                        </label>
                        <input
                            :id="control.nombre"
                            v-model="valores[control.nombre]"
                            type="number"
                            inputmode="numeric"
                            min="1"
                            class="mt-1 h-11 w-full rounded-lg border-gray-300 text-sm tabular-nums shadow-sm focus:border-iyem-secundario focus:ring-iyem-secundario"
                        >
                        <p v-if="control.ayuda" class="mt-1 text-xs text-gray-400">
                            {{ control.ayuda }}
                        </p>
                    </div>

                    <fieldset v-else-if="control.tipo === 'checkbox-multiple'" class="sm:col-span-2 lg:col-span-4">
                        <legend class="text-xs font-medium uppercase tracking-wide text-gray-500">
                            {{ control.etiqueta }}
                        </legend>
                        <p v-if="control.ayuda" class="mt-0.5 text-xs text-gray-400">
                            {{ control.ayuda }}
                        </p>
                        <div class="mt-2 flex flex-wrap gap-2">
                            <label
                                v-for="(texto, clave) in control.opciones"
                                :key="clave"
                                class="toque-minimo inline-flex cursor-pointer items-center gap-2 rounded-full border px-3.5 text-sm transition"
                                :class="valores[control.nombre].includes(clave)
                                    ? 'border-transparent bg-iyem-gradient text-white shadow-glow'
                                    : 'border-iyem-200 bg-white text-gray-600 hover:border-iyem-300'"
                            >
                                <input
                                    v-model="valores[control.nombre]"
                                    type="checkbox"
                                    :value="clave"
                                    class="sr-only"
                                >
                                {{ texto }}
                            </label>
                        </div>
                    </fieldset>
                </template>
            </div>

            <div class="mt-4 flex flex-wrap items-center gap-2">
                <button
                    type="submit"
                    class="toque-minimo inline-flex items-center justify-center gap-2 rounded-lg bg-iyem-gradient px-4 text-sm font-semibold text-white shadow-soft transition hover:shadow-glow focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-iyem-secundario focus-visible:ring-offset-2"
                >
                    <IconoNav icono="filtro" class="h-4 w-4" />
                    Aplicar
                </button>

                <button
                    v-if="hayFiltros"
                    type="button"
                    class="toque-minimo inline-flex items-center justify-center rounded-lg px-3 text-sm font-medium text-gray-500 transition hover:text-gray-700"
                    @click="limpiar"
                >
                    Limpiar
                </button>

                <div class="ml-auto flex flex-wrap gap-2">
                    <button
                        type="button"
                        class="toque-minimo inline-flex items-center justify-center gap-2 rounded-lg border border-iyem-200 bg-white px-3 text-sm font-medium text-gray-600 transition hover:bg-iyem-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-iyem-secundario"
                        @click="copiarEnlace"
                    >
                        <IconoNav :icono="enlaceCopiado ? 'exito' : 'externo'" class="h-4 w-4" />
                        {{ enlaceCopiado ? '¡Copiado!' : 'Copiar enlace' }}
                    </button>

                    <template v-if="puedeExportar">
                        <a
                            :href="urlExportar('xlsx')"
                            class="toque-minimo inline-flex items-center justify-center gap-2 rounded-lg border border-iyem-200 bg-white px-3 text-sm font-medium text-iyem-700 transition hover:bg-iyem-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-iyem-secundario"
                        >
                            <IconoNav icono="descargar" class="h-4 w-4" />
                            XLSX
                        </a>
                        <a
                            :href="urlExportar('csv')"
                            class="toque-minimo inline-flex items-center justify-center gap-2 rounded-lg border border-iyem-200 bg-white px-3 text-sm font-medium text-gray-600 transition hover:bg-iyem-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-iyem-secundario"
                        >
                            <IconoNav icono="descargar" class="h-4 w-4" />
                            CSV
                        </a>
                    </template>
                </div>
            </div>
        </form>

        <!-- ============================================================
             Resumen
             ============================================================ -->
        <div v-if="resumen.length" class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <div
                v-for="(dato, i) in resumen"
                :key="i"
                class="rounded-2xl border border-iyem-200 bg-white p-4 shadow-soft"
            >
                <p class="text-xs font-medium uppercase tracking-wide text-gray-400">
                    {{ dato.etiqueta }}
                </p>
                <p class="mt-1 text-2xl font-bold tabular-nums text-iyem-700">
                    {{ typeof dato.valor === 'number' ? dato.valor.toLocaleString('es-MX') : dato.valor }}
                </p>
                <p v-if="dato.detalle" class="mt-0.5 text-xs text-gray-400">
                    {{ dato.detalle }}
                </p>
            </div>
        </div>

        <!-- ============================================================
             Gráfica
             ============================================================ -->
        <div class="mt-5">
            <Grafica v-if="grafica" :datos="grafica" />
        </div>

        <!-- ============================================================
             Tabla
             ============================================================ -->
        <template v-if="tabla.data.length">
            <!-- Teléfono: tarjetas apiladas -->
            <ul class="mt-5 space-y-3 md:hidden">
                <li
                    v-for="(fila, i) in tabla.data"
                    :key="i"
                    class="rounded-2xl border border-iyem-200 bg-white p-4 shadow-soft"
                >
                    <dl class="space-y-1.5 text-sm">
                        <div v-for="clave in clavesDeColumna" :key="clave" class="flex gap-3">
                            <dt class="w-1/3 shrink-0 text-gray-400">
                                {{ consulta.columnas[clave] }}
                            </dt>
                            <dd
                                class="min-w-0 flex-1 break-words text-gray-700"
                                :class="[esNumero(fila[clave]) ? 'tabular-nums font-medium' : '', claseSituacion(fila[clave])]"
                            >
                                {{ esNumero(fila[clave]) ? fila[clave].toLocaleString('es-MX') : (fila[clave] ?? '—') }}
                            </dd>
                        </div>
                    </dl>
                </li>
            </ul>

            <!-- Tablet y escritorio: tabla -->
            <div class="scrollbar-fina scroll-suave-ios mt-5 hidden overflow-x-auto rounded-2xl border border-iyem-200 bg-white shadow-soft md:block">
                <table class="min-w-full divide-y divide-iyem-100 text-sm">
                    <thead class="bg-iyem-50 text-left text-xs uppercase tracking-wide text-iyem-700">
                        <tr>
                            <th v-for="clave in clavesDeColumna" :key="clave" scope="col" class="whitespace-nowrap px-4 py-3">
                                {{ consulta.columnas[clave] }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-iyem-100">
                        <tr
                            v-for="(fila, i) in tabla.data"
                            :key="i"
                            class="transition-colors duration-150 hover:bg-iyem-50/60"
                        >
                            <td
                                v-for="clave in clavesDeColumna"
                                :key="clave"
                                class="px-4 py-3 text-gray-700"
                                :class="[esNumero(fila[clave]) ? 'tabular-nums font-medium' : '', claseSituacion(fila[clave])]"
                            >
                                <Link
                                    v-if="clave === 'nombre_completo' && fila.id"
                                    :href="route('padron.show', fila.id)"
                                    class="text-gray-800 underline-offset-2 transition hover:text-iyem-700 hover:underline"
                                >
                                    {{ fila[clave] }}
                                </Link>
                                <template v-else>
                                    {{ esNumero(fila[clave]) ? fila[clave].toLocaleString('es-MX') : (fila[clave] ?? '—') }}
                                </template>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </template>

        <p
            v-else
            class="mt-5 rounded-2xl border border-dashed border-iyem-200 px-4 py-10 text-center text-sm text-gray-500"
        >
            Esta consulta no devolvió resultados con los filtros aplicados.
        </p>

        <Paginacion :paginador="tabla" />
    </AppLayout>
</template>
