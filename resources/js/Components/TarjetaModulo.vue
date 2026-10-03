<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import IconoModulo from '@/Components/IconoModulo.vue';
import IconoNav from '@/Components/IconoNav.vue';
import BadgeEstado from '@/Components/BadgeEstado.vue';
import PuntoSalud from '@/Components/PuntoSalud.vue';
import { numero } from '@/formato';

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
 * El acento del ícono sale del `color` de `config/modulos.php`, traducido a
 * clases literales para que Tailwind las vea. El guinda existe solo para
 * CREA: así se reconoce el sistema al que se sale.
 */
const acentos = {
    'guinda-700': 'bg-guinda-700 text-white',
    'brand-500': 'bg-surface-brand text-brand-500 dark:text-brand-300',
};

const acento = computed(() => acentos[props.modulo.color] ?? acentos['brand-500']);

// Un módulo que no se puede abrir dice por qué, en lugar de parecer
// clickeable y no responder. El badge ya dice el estado; aquí va quién lo
// lleva. El área responsable está pendiente de confirmar con el IYEM.
const motivoNoDisponible = computed(() =>
    ['Aún no se puede abrir', props.modulo.responsable].filter(Boolean).join(' · '),
);

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
    if (!props.modulo.navegable) return {};

    return props.modulo.externo
        ? {
            href: props.modulo.url,
            target: '_blank',
            rel: 'noopener noreferrer',
            'aria-label': `Abrir ${props.modulo.nombre} (sitio externo, se abre en una pestaña nueva)`,
        }
        : { href: props.modulo.url, 'aria-label': `Abrir ${props.modulo.nombre}` };
});
</script>

<template>
    <component
        :is="etiquetaComponente"
        v-bind="atributos"
        class="group flex rounded-lg border p-5 transition-colors"
        :class="[
            compacto ? 'items-center gap-4' : 'flex-col',
            modulo.navegable
                ? 'border-line bg-surface shadow-sm hover:border-line-strong hover:bg-surface-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-focus focus-visible:ring-offset-2'
                : 'border-line bg-surface-50',
        ]"
    >
        <div class="flex items-start gap-3" :class="compacto ? 'min-w-0 flex-1' : ''">
            <span
                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md"
                :class="modulo.navegable ? acento : 'bg-surface-100 text-ink-400'"
            >
                <IconoModulo :icono="modulo.icono" />
            </span>

            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-start justify-between gap-x-2 gap-y-1">
                    <h4 class="text-subtitle" :class="modulo.navegable ? 'text-ink' : 'text-ink-600'">
                        {{ modulo.nombre }}
                    </h4>
                    <BadgeEstado :estado="modulo.estado" />
                </div>
                <p class="mt-1 text-small text-ink-600" :class="compacto ? 'truncate' : ''">
                    {{ modulo.descripcion }}
                </p>
            </div>
        </div>

        <p v-if="modulo.dato && !compacto" class="mt-4 flex items-baseline gap-2">
            <span class="text-number-lg text-ink">{{ numero(modulo.dato.valor) }}</span>
            <span class="text-small text-ink-600">{{ modulo.dato.etiqueta }}</span>
        </p>

        <!-- Empuja el pie al fondo para que las tarjetas de una fila alineen. -->
        <div v-if="!compacto" class="flex-1" aria-hidden="true" />

        <div
            class="flex items-center gap-3"
            :class="compacto ? 'shrink-0' : 'mt-4 border-t border-line pt-4'"
        >
            <template v-if="modulo.navegable">
                <PuntoSalud
                    :estado="salud?.estado ?? null"
                    :ms="salud?.ms ?? null"
                    :con-texto="!compacto"
                />
                <span class="ml-auto inline-flex items-center gap-1.5 text-body-strong text-action group-hover:underline">
                    <span :class="compacto ? 'sr-only' : ''">Abrir</span>
                    <IconoNav :icono="modulo.externo ? 'externo' : 'arrow'" class="h-4 w-4" />
                </span>
            </template>
            <p v-else class="text-small text-ink-600">
                {{ motivoNoDisponible }}
            </p>
        </div>
    </component>
</template>
