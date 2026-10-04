<script setup>
import { Link } from '@inertiajs/vue3';
import IconoNav from '@/Components/IconoNav.vue';
import { formatear, SIN_DATO } from '@/Components/Modulo/formato.js';

/**
 * Una cifra de dirección con el periodo al que corresponde.
 *
 * Sin el rango debajo, "Ingresos $124,800.00 MXN" no sería un dato sino una
 * afirmación. `enlacePersonal` lleva a la lista detrás de la cifra, para
 * quien tiene permiso de ver datos personales.
 */
defineProps({
    indicador: { type: Object, required: true },
    rango: { type: String, default: null },
    enlacePersonal: { type: String, default: null },
});
</script>

<template>
    <article class="flex flex-col rounded-md border border-line bg-surface p-4 shadow-sm">
        <h3 class="text-overline text-ink-600">{{ indicador.etiqueta }}</h3>

        <p
            class="mt-2 break-words text-display-md !font-mono tabular-nums"
            :class="indicador.valor === null ? 'text-ink-400' : 'text-ink'"
        >
            {{ formatear(indicador.valor, indicador.unidad, indicador.moneda) }}
        </p>

        <dl v-if="indicador.desglose.length" class="mt-3 space-y-1 border-t border-line pt-3">
            <div
                v-for="parte in indicador.desglose"
                :key="parte.clave"
                class="flex items-baseline justify-between gap-3"
            >
                <dt class="text-small text-ink-600">{{ parte.etiqueta }}</dt>
                <dd class="whitespace-nowrap text-number text-ink" :class="{ 'text-ink-400': parte.valor === null }">
                    {{ formatear(parte.valor, parte.unidad, parte.moneda) }}
                </dd>
            </div>
        </dl>

        <div class="mt-auto flex flex-wrap items-center justify-between gap-2 pt-3">
            <p class="text-caption text-ink-400">
                <span class="sr-only">Periodo: </span>{{ rango ?? SIN_DATO }}
            </p>

            <Link
                v-if="enlacePersonal && indicador.detalle_personal"
                :href="enlacePersonal"
                class="inline-flex items-center gap-1 rounded-sm text-small font-semibold text-action hover:text-action-hover focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-focus focus-visible:ring-offset-2"
            >
                Ver la lista
                <IconoNav icono="arrow" class="h-4 w-4" />
            </Link>
        </div>
    </article>
</template>
