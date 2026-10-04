<script setup>
import AvisoDeFalla from '@/Components/Modulo/AvisoDeFalla.vue';
import { esNumerica, formatear, momento } from '@/Components/Modulo/formato.js';

/**
 * Una sección de reporte de un módulo: tarjeta con su tabla.
 *
 * Según la guía del sistema de diseño: cifras a la derecha en mono, sin
 * radio, cabecera fija al desplazar y totales en el pie. El contenedor con
 * desplazamiento recibe foco para que la tabla se pueda recorrer con el
 * teclado aunque no tenga controles.
 */
const props = defineProps({
    seccion: { type: Object, required: true },
    reintentando: { type: Boolean, default: false },
});

defineEmits(['reintentar']);

const idTitulo = `tabla-${props.seccion.clave}`;
</script>

<template>
    <section class="rounded-md border border-line bg-surface shadow-sm" :aria-labelledby="idTitulo">
        <header class="border-b border-line px-4 py-3">
            <h3 :id="idTitulo" class="text-subtitle text-ink">{{ seccion.titulo }}</h3>
            <p v-if="seccion.descripcion" class="mt-0.5 text-small text-ink-600">{{ seccion.descripcion }}</p>
            <p v-if="seccion.rango" class="mt-1 text-caption text-ink-400">
                <span class="sr-only">Periodo: </span>{{ seccion.rango.descripcion }}
            </p>
        </header>

        <div v-if="seccion.falla" class="p-4">
            <AvisoDeFalla :falla="seccion.falla" compacto :reintentando="reintentando" @reintentar="$emit('reintentar')" />
        </div>

        <p v-else-if="!seccion.filas.length" class="px-4 py-6 text-center text-small text-ink-600">
            No hubo movimientos en este periodo.
        </p>

        <div
            v-else
            class="max-h-[28rem] overflow-auto focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-focus"
            tabindex="0"
            role="region"
            :aria-labelledby="idTitulo"
        >
            <table class="w-full min-w-max border-collapse text-left">
                <thead class="sticky top-0 z-10 bg-surface-50">
                    <tr>
                        <th
                            v-for="columna in seccion.columnas"
                            :key="columna.clave"
                            scope="col"
                            class="whitespace-nowrap border-b border-line px-4 py-2 text-overline text-ink-600"
                            :class="esNumerica(columna.tipo) ? 'text-right' : 'text-left'"
                        >
                            {{ columna.etiqueta }}
                        </th>
                    </tr>
                </thead>

                <tbody>
                    <tr
                        v-for="(fila, indice) in seccion.filas"
                        :key="indice"
                        class="border-b border-line last:border-b-0 hover:bg-surface-50"
                    >
                        <td
                            v-for="columna in seccion.columnas"
                            :key="columna.clave"
                            class="whitespace-nowrap px-4 py-2"
                            :class="esNumerica(columna.tipo) ? 'text-right text-number text-ink' : 'text-body text-ink'"
                        >
                            {{ formatear(fila[columna.clave], columna.tipo) }}
                        </td>
                    </tr>
                </tbody>

                <tfoot v-if="seccion.totales" class="sticky bottom-0 bg-surface-100">
                    <tr>
                        <td
                            v-for="columna in seccion.columnas"
                            :key="columna.clave"
                            class="whitespace-nowrap border-t border-line-strong px-4 py-2"
                            :class="esNumerica(columna.tipo) ? 'text-right text-number font-semibold text-ink' : 'text-body-strong text-ink'"
                        >
                            <template v-if="columna.clave in seccion.totales">
                                {{ formatear(seccion.totales[columna.clave], columna.tipo) }}
                            </template>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <p v-if="seccion.obtenido_en && !seccion.falla" class="border-t border-line px-4 py-2 text-caption text-ink-400">
            Consultado {{ momento(seccion.obtenido_en) }}
        </p>
    </section>
</template>
