<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import IconoModulo from '@/Components/IconoModulo.vue';
import IconoNav from '@/Components/IconoNav.vue';
import BadgeEstado from '@/Components/BadgeEstado.vue';
import PuntoSalud from '@/Components/PuntoSalud.vue';

const props = defineProps({
    modulo: {
        type: Object,
        required: true,
    },
    salud: {
        type: Object,
        default: null,
    },
    compacto: {
        type: Boolean,
        default: false,
    },
});

/**
 * Tailwind no puede resolver clases armadas en tiempo de ejecución, así que
 * el token que declara cada módulo en `config/modulos.php` se traduce aquí
 * a clases literales que el compilador sí ve.
 */
const fondos = {
    'iyem-primario': 'bg-iyem-gradient',
    'iyem-secundario': 'bg-gradient-to-br from-iyem-secundario to-iyem-400',
    'iyem-dorado': 'bg-gradient-to-br from-iyem-dorado to-amber-600',
};

const fondoIcono = computed(() => fondos[props.modulo.color] ?? fondos['iyem-primario']);

/*
 * Tres formas de renderizar la misma tarjeta:
 *
 *   - No navegable  -> <div>. Sin href, sin foco, sin la falsa promesa de
 *                      que va a llevar a alguna parte.
 *   - Externo       -> <a> normal. El módulo vive en otro dominio y se abre
 *                      en pestaña nueva; un <Link> de Inertia intentaría
 *                      resolverlo como visita del lado del cliente e
 *                      ignoraría el target.
 *   - Interno       -> <Link> de Inertia, para no recargar la aplicación.
 */
const etiquetaComponente = computed(() => {
    if (!props.modulo.navegable) return 'div';
    return props.modulo.externo ? 'a' : Link;
});

const atributos = computed(() => {
    if (!props.modulo.navegable) return { 'aria-disabled': 'true' };

    return props.modulo.externo
        ? { href: props.modulo.url, target: '_blank', rel: 'noopener noreferrer' }
        : { href: props.modulo.url };
});
</script>

<template>
    <component
        :is="etiquetaComponente"
        v-bind="atributos"
        class="group relative flex rounded-2xl border bg-white/90 backdrop-blur-sm transition-all duration-200"
        :class="[
            compacto ? 'items-center gap-4 p-4' : 'flex-col p-5',
            modulo.navegable
                ? 'border-iyem-200 shadow-soft hover:-translate-y-0.5 hover:border-iyem-300 hover:shadow-soft-lg focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-iyem-secundario focus-visible:ring-offset-2'
                : 'border-dashed border-gray-300 bg-white/60 shadow-none',
        ]"
    >
        <div
            class="flex items-center justify-center rounded-xl text-white shadow-glow"
            :class="[
                fondoIcono,
                compacto ? 'h-11 w-11 shrink-0' : 'h-12 w-12',
                modulo.navegable ? '' : 'opacity-40 grayscale',
            ]"
        >
            <IconoModulo :icono="modulo.icono" />
        </div>

        <div :class="compacto ? 'min-w-0 flex-1' : 'mt-4 flex flex-1 flex-col'">
            <div class="flex items-center gap-2">
                <h3
                    class="truncate font-semibold"
                    :class="modulo.navegable ? 'text-gray-800' : 'text-gray-500'"
                >
                    {{ modulo.nombre }}
                </h3>
                <PuntoSalud :estado="salud?.estado ?? null" :ms="salud?.ms ?? null" />
            </div>

            <p
                class="mt-1 text-sm text-gray-500"
                :class="compacto ? 'truncate' : 'flex-1'"
            >
                {{ modulo.descripcion }}
            </p>

            <p
                v-if="modulo.dato && !compacto"
                class="mt-3 text-sm text-gray-600"
            >
                <span class="text-lg font-bold tabular-nums text-iyem-700">
                    {{ modulo.dato.valor.toLocaleString('es-MX') }}
                </span>
                <span class="ml-1.5">{{ modulo.dato.etiqueta }}</span>
            </p>

            <div
                class="flex items-center gap-2"
                :class="compacto ? 'mt-1.5' : 'mt-4 flex-wrap'"
            >
                <BadgeEstado :estado="modulo.estado" />
                <span
                    v-if="!compacto"
                    class="text-[0.7rem] uppercase tracking-wide text-gray-400"
                >
                    {{ modulo.categoria }}
                </span>

                <span
                    v-if="modulo.navegable"
                    class="ml-auto inline-flex items-center gap-1.5 text-sm font-semibold text-iyem-700 transition-all group-hover:gap-2.5"
                >
                    <span :class="compacto ? 'sr-only' : ''">Acceder</span>
                    <IconoNav :icono="modulo.externo ? 'externo' : 'arrow'" class="h-4 w-4" />
                </span>
                <span
                    v-else
                    class="ml-auto text-xs text-gray-400"
                >
                    No disponible
                </span>
            </div>
        </div>
    </component>
</template>
