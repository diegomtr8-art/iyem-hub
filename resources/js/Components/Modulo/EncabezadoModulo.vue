<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';
import BadgeEstado from '@/Components/BadgeEstado.vue';
import IconoModulo from '@/Components/IconoModulo.vue';
import IconoNav from '@/Components/IconoNav.vue';
import PuntoSalud from '@/Components/PuntoSalud.vue';

/**
 * Encabezado del tablero de un módulo.
 *
 * Dos acciones, y las dos secundarias: el control principal de la pantalla
 * es el periodo, que es lo que de verdad se usa. "Ir al sitio" sale a otro
 * dominio y lo dice; "Exportar CSV" pide al padre que baje el archivo a
 * través del ERP (así una descarga fallida se avisa en la página, no como
 * una pantalla de error).
 */
const props = defineProps({
    modulo: { type: Object, required: true },
    salud: { type: Object, default: null },
    /** [{ clave, etiqueta, personal }] */
    informes: { type: Array, default: () => [] },
    exportando: { type: String, default: null },
});

const emit = defineEmits(['exportar']);

const menuAbierto = ref(false);
const contenedor = ref(null);

const cerrarAlSalir = (evento) => {
    if (menuAbierto.value && contenedor.value && !contenedor.value.contains(evento.target)) {
        menuAbierto.value = false;
    }
};

onMounted(() => document.addEventListener('click', cerrarAlSalir));
onBeforeUnmount(() => document.removeEventListener('click', cerrarAlSalir));
</script>

<template>
    <header class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div class="flex min-w-0 items-start gap-3">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-md bg-surface-brand text-action">
                <IconoModulo :icono="modulo.icono" />
            </div>

            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <h1 class="text-display-md text-ink">{{ modulo.nombre }}</h1>
                    <PuntoSalud :estado="salud?.estado ?? null" :ms="salud?.ms ?? null" />
                </div>
                <p class="mt-0.5 text-body text-ink-600">{{ modulo.descripcion }}</p>
                <div class="mt-2 flex flex-wrap items-center gap-2">
                    <BadgeEstado :estado="modulo.estado" />
                    <span
                        v-if="modulo.entorno_de_prueba"
                        class="inline-flex min-h-6 items-center gap-1 rounded-sm bg-warning-surface px-2 py-0.5 text-caption text-ink"
                    >
                        <IconoNav icono="alerta" class="h-3.5 w-3.5 text-warning" />
                        Entorno de prueba: las cifras son ficticias
                    </span>
                </div>
            </div>
        </div>

        <div class="flex shrink-0 flex-wrap gap-2">
            <a
                v-if="modulo.url_sitio"
                :href="modulo.url_sitio"
                target="_blank"
                rel="noopener noreferrer"
                class="inline-flex h-10 items-center gap-1.5 rounded border border-line-strong bg-surface px-3.5 text-small font-semibold text-ink transition-colors hover:bg-surface-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-focus focus-visible:ring-offset-2"
                :aria-label="`Ir al sitio de ${modulo.nombre}. Abre otro sitio en una pestaña nueva.`"
            >
                Ir al sitio
                <IconoNav icono="externo" class="h-4 w-4" />
            </a>

            <div v-if="informes.length" ref="contenedor" class="relative" @keydown.escape="menuAbierto = false">
                <button
                    type="button"
                    class="inline-flex h-10 items-center gap-1.5 rounded border border-line-strong bg-surface px-3.5 text-small font-semibold text-ink transition-colors hover:bg-surface-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-focus focus-visible:ring-offset-2"
                    :aria-expanded="menuAbierto"
                    aria-controls="menu-informes"
                    @click="menuAbierto = !menuAbierto"
                >
                    <IconoNav icono="descargar" class="h-4 w-4" :class="{ 'animate-bounce': exportando }" />
                    {{ exportando ? 'Descargando…' : 'Exportar CSV' }}
                    <IconoNav icono="chevron" class="h-4 w-4 transition-transform" :class="{ 'rotate-180': menuAbierto }" />
                </button>

                <ul
                    v-show="menuAbierto"
                    id="menu-informes"
                    class="absolute right-0 z-30 mt-1 w-64 rounded-md border border-line bg-surface py-1 shadow-md"
                >
                    <li v-for="informe in informes" :key="informe.clave">
                        <button
                            type="button"
                            class="flex w-full items-center justify-between gap-2 px-3 py-2 text-left text-small text-ink hover:bg-surface-50 focus-visible:bg-surface-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-focus"
                            @click="menuAbierto = false; emit('exportar', informe)"
                        >
                            {{ informe.etiqueta }}
                            <span v-if="informe.personal" class="text-caption text-ink-400">Datos personales</span>
                        </button>
                    </li>
                </ul>
            </div>
        </div>
    </header>
</template>
