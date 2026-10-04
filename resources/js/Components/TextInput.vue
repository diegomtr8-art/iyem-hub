<script setup>
import { onMounted, ref } from 'vue';

/**
 * Campo de texto del sistema: 40px de alto, borde border-strong (el borde
 * de un control necesita 3:1) y anillo de foco que nunca se quita.
 *
 * `invalido` pinta el borde en danger y marca aria-invalid. El texto del
 * error lo pone InputError o Campo: el borde rojo solo no basta.
 */
const props = defineProps({
    modelValue: [String, Number],
    invalido: {
        type: Boolean,
        default: false,
    },
});

defineEmits(['update:modelValue']);

const input = ref(null);

onMounted(() => {
    if (input.value.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value.focus() });
</script>

<template>
    <input
        ref="input"
        class="h-10 rounded-md bg-surface px-3 text-body text-ink shadow-none placeholder:text-ink-400 focus:outline-none focus:ring-2 focus:ring-focus focus:ring-offset-2 disabled:cursor-not-allowed disabled:bg-surface-100 disabled:text-ink-600"
        :class="props.invalido ? 'border-danger focus:border-danger' : 'border-line-strong focus:border-focus'"
        :aria-invalid="props.invalido ? 'true' : undefined"
        :value="modelValue"
        @input="$emit('update:modelValue', $event.target.value)"
    >
</template>
