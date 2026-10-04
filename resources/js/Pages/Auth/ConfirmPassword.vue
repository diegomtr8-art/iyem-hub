<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticationCard from '@/Components/AuthenticationCard.vue';
import Campo from '@/Components/Campo.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const form = useForm({
    password: '',
});

const passwordInput = ref(null);

const submit = () => {
    form.post(route('password.confirm'), {
        onFinish: () => {
            form.reset();

            passwordInput.value.focus();
        },
    });
};
</script>

<template>
    <Head title="Confirmar contraseña" />

    <AuthenticationCard
        titulo="Confirma tu contraseña"
        descripcion="Estás por entrar a una sección protegida. Escribe tu contraseña para continuar."
    >
        <form class="space-y-5" novalidate @submit.prevent="submit">
            <Campo id="password" v-slot="campo" etiqueta="Contraseña" :error="form.errors.password">
                <TextInput
                    :id="campo.id"
                    ref="passwordInput"
                    v-model="form.password"
                    type="password"
                    class="block w-full"
                    :invalido="campo.invalido"
                    :aria-describedby="campo.describedby"
                    required
                    autocomplete="current-password"
                    autofocus
                />
            </Campo>

            <PrimaryButton class="w-full" :procesando="form.processing">
                {{ form.processing ? 'Confirmando…' : 'Confirmar y continuar' }}
            </PrimaryButton>
        </form>
    </AuthenticationCard>
</template>
