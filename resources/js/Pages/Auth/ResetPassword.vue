<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticationCard from '@/Components/AuthenticationCard.vue';
import Campo from '@/Components/Campo.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    email: String,
    token: String,
});

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('password.update'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Restablecer contraseña" />

    <AuthenticationCard titulo="Crear contraseña nueva" descripcion="Elige una contraseña que no uses en otro sistema.">
        <form class="space-y-5" novalidate @submit.prevent="submit">
            <Campo id="email" v-slot="campo" etiqueta="Correo electrónico" :error="form.errors.email">
                <TextInput
                    :id="campo.id"
                    v-model="form.email"
                    type="email"
                    inputmode="email"
                    class="block w-full"
                    :invalido="campo.invalido"
                    :aria-describedby="campo.describedby"
                    required
                    autocomplete="username"
                />
            </Campo>

            <Campo id="password" v-slot="campo" etiqueta="Contraseña nueva" :error="form.errors.password">
                <TextInput
                    :id="campo.id"
                    v-model="form.password"
                    type="password"
                    class="block w-full"
                    :invalido="campo.invalido"
                    :aria-describedby="campo.describedby"
                    required
                    autofocus
                    autocomplete="new-password"
                />
            </Campo>

            <Campo id="password_confirmation" v-slot="campo" etiqueta="Confirma la contraseña" :error="form.errors.password_confirmation">
                <TextInput
                    :id="campo.id"
                    v-model="form.password_confirmation"
                    type="password"
                    class="block w-full"
                    :invalido="campo.invalido"
                    :aria-describedby="campo.describedby"
                    required
                    autocomplete="new-password"
                />
            </Campo>

            <PrimaryButton class="w-full" :procesando="form.processing">
                {{ form.processing ? 'Guardando…' : 'Guardar contraseña' }}
            </PrimaryButton>
        </form>
    </AuthenticationCard>
</template>
