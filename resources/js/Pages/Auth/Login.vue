<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticationCard from '@/Components/AuthenticationCard.vue';
import Aviso from '@/Components/Aviso.vue';
import Campo from '@/Components/Campo.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

defineProps({
    canResetPassword: Boolean,
    status: String,
});

const form = useForm({
    email: '',
    password: '',
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Iniciar sesión" />

    <AuthenticationCard
        titulo="Iniciar sesión"
        descripcion="Entra con tu correo institucional y tu contraseña."
    >
        <Aviso v-if="status" tipo="exito" class="mb-6">{{ status }}</Aviso>

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
                    autofocus
                    autocomplete="username"
                    placeholder="nombre@iyemyucatan.com"
                />
            </Campo>

            <Campo id="password" v-slot="campo" etiqueta="Contraseña" :error="form.errors.password">
                <TextInput
                    :id="campo.id"
                    v-model="form.password"
                    type="password"
                    class="block w-full"
                    :invalido="campo.invalido"
                    :aria-describedby="campo.describedby"
                    required
                    autocomplete="current-password"
                />
            </Campo>

            <div v-if="canResetPassword" class="flex justify-end">
                <Link
                    :href="route('password.request')"
                    class="rounded-sm text-small text-action underline-offset-2 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-focus focus-visible:ring-offset-2"
                >
                    ¿Olvidaste tu contraseña?
                </Link>
            </div>

            <PrimaryButton class="w-full" :procesando="form.processing">
                {{ form.processing ? 'Entrando…' : 'Entrar' }}
            </PrimaryButton>
        </form>
    </AuthenticationCard>
</template>
