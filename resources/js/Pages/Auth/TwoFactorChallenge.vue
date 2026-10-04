<script setup>
import { nextTick, ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticationCard from '@/Components/AuthenticationCard.vue';
import Campo from '@/Components/Campo.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const recovery = ref(false);

const form = useForm({
    code: '',
    recovery_code: '',
});

const recoveryCodeInput = ref(null);
const codeInput = ref(null);

const toggleRecovery = async () => {
    recovery.value ^= true;

    await nextTick();

    if (recovery.value) {
        recoveryCodeInput.value.focus();
        form.code = '';
    } else {
        codeInput.value.focus();
        form.recovery_code = '';
    }
};

const submit = () => {
    form.post(route('two-factor.login'));
};
</script>

<template>
    <Head title="Verificación en dos pasos" />

    <AuthenticationCard
        titulo="Verificación en dos pasos"
        :descripcion="recovery
            ? 'Escribe uno de tus códigos de recuperación de emergencia.'
            : 'Escribe el código de 6 dígitos que muestra tu aplicación de autenticación.'"
    >
        <form class="space-y-5" novalidate @submit.prevent="submit">
            <Campo v-if="!recovery" id="code" v-slot="campo" etiqueta="Código de autenticación" :error="form.errors.code">
                <TextInput
                    :id="campo.id"
                    ref="codeInput"
                    v-model="form.code"
                    type="text"
                    inputmode="numeric"
                    class="block w-full font-mono tracking-[0.3em]"
                    :invalido="campo.invalido"
                    :aria-describedby="campo.describedby"
                    autofocus
                    autocomplete="one-time-code"
                />
            </Campo>

            <Campo v-else id="recovery_code" v-slot="campo" etiqueta="Código de recuperación" :error="form.errors.recovery_code">
                <TextInput
                    :id="campo.id"
                    ref="recoveryCodeInput"
                    v-model="form.recovery_code"
                    type="text"
                    class="block w-full font-mono"
                    :invalido="campo.invalido"
                    :aria-describedby="campo.describedby"
                    autocomplete="one-time-code"
                />
            </Campo>

            <PrimaryButton class="w-full" :procesando="form.processing">
                {{ form.processing ? 'Verificando…' : 'Verificar y entrar' }}
            </PrimaryButton>

            <button
                type="button"
                class="inline-flex min-h-[44px] items-center rounded-sm text-small text-action underline-offset-2 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-focus focus-visible:ring-offset-2"
                @click.prevent="toggleRecovery"
            >
                {{ recovery ? 'Usar el código de la aplicación' : 'Usar un código de recuperación' }}
            </button>
        </form>
    </AuthenticationCard>
</template>
