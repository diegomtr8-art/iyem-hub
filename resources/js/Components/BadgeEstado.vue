<script setup>
import { computed } from 'vue';

/**
 * Badge del estado de un módulo.
 *
 * Los cuatro estados de `config/modulos.php` se pintan distinto a propósito:
 * el dashboard tiene que poder decir "esto todavía no existe" sin ambigüedad.
 */
const props = defineProps({
    estado: {
        type: String,
        default: 'produccion',
    },
});

const estilos = {
    produccion: {
        texto: 'Producción',
        clases: 'bg-iyem-exito/10 text-iyem-exito ring-iyem-exito/25',
        titulo: 'En operación',
    },
    beta: {
        texto: 'Beta',
        clases: 'bg-iyem-alerta/10 text-iyem-alerta ring-iyem-alerta/25',
        titulo: 'En pruebas con usuarios',
    },
    desarrollo: {
        texto: 'Desarrollo',
        clases: 'bg-sky-500/10 text-sky-700 ring-sky-500/25',
        titulo: 'En construcción, todavía no se puede abrir',
    },
    planeado: {
        texto: 'Planeado',
        clases: 'bg-gray-400/10 text-gray-600 ring-gray-400/30',
        titulo: 'Aprobado pero sin arrancar',
    },
};

const estilo = computed(() => estilos[props.estado] ?? estilos.planeado);
</script>

<template>
    <span
        class="inline-flex items-center rounded-full px-2 py-0.5 text-[0.7rem] font-semibold ring-1 ring-inset"
        :class="estilo.clases"
        :title="estilo.titulo"
    >
        {{ estilo.texto }}
    </span>
</template>
