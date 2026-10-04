<script setup>
import { computed } from 'vue';
import InputError from '@/Components/InputError.vue';

/**
 * Campo de formulario completo: etiqueta visible asociada con for/id,
 * control, y debajo la ayuda o —si lo hay— el error, que la reemplaza.
 *
 * El control va en el slot y recibe lo que necesita para quedar enlazado:
 *
 *   <Campo id="email" etiqueta="Correo" :error="form.errors.email" v-slot="campo">
 *       <TextInput :id="campo.id" :invalido="campo.invalido" :aria-describedby="campo.describedby" ... />
 *   </Campo>
 */
const props = defineProps({
    id: {
        type: String,
        required: true,
    },
    etiqueta: {
        type: String,
        required: true,
    },
    ayuda: {
        type: String,
        default: null,
    },
    error: {
        type: String,
        default: null,
    },
});

const idError = computed(() => `${props.id}-error`);
const idAyuda = computed(() => `${props.id}-ayuda`);
const describedby = computed(() => {
    if (props.error) return idError.value;
    if (props.ayuda) return idAyuda.value;
    return undefined;
});
</script>

<template>
    <div>
        <label :for="id" class="block text-body-strong text-ink">
            {{ etiqueta }}
            <slot name="etiqueta-extra" />
        </label>
        <div class="mt-1.5">
            <slot :id="id" :invalido="Boolean(error)" :describedby="describedby" />
        </div>
        <InputError v-if="error" :id="idError" class="mt-1.5" :message="error" />
        <p v-else-if="ayuda" :id="idAyuda" class="mt-1.5 text-small text-ink-600">{{ ayuda }}</p>
    </div>
</template>
