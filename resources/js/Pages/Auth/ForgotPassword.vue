<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticationCard from '@/Components/AuthenticationCard.vue';
import Aviso from '@/Components/Aviso.vue';
import Campo from '@/Components/Campo.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

defineProps({
    status: String,
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <Head title="Recuperar contraseña" />

    <AuthenticationCard
        titulo="Recuperar contraseña"
        descripcion="Escribe tu correo institucional y te enviaremos un enlace para crear una contraseña nueva."
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
                />
            </Campo>

            <PrimaryButton class="w-full" :procesando="form.processing">
                {{ form.processing ? 'Enviando…' : 'Enviar enlace de recuperación' }}
            </PrimaryButton>

            <Link
                :href="route('login')"
                class="inline-flex min-h-[44px] items-center rounded-sm text-small text-action underline-offset-2 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-focus focus-visible:ring-offset-2"
            >
                Volver a iniciar sesión
            </Link>
        </form>
    </AuthenticationCard>
</template>
