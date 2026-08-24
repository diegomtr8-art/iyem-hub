<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticationCard from '@/Components/AuthenticationCard.vue';
import AuthenticationCardLogo from '@/Components/AuthenticationCardLogo.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
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

    <AuthenticationCard>
        <template #logo>
            <AuthenticationCardLogo />
        </template>

        <h1 class="text-2xl font-bold text-gray-800">
            Bienvenido de vuelta
        </h1>
        <p class="mt-2 text-sm text-gray-500">
            Ingresa tus credenciales para acceder a la plataforma centralizada del IYEM.
        </p>

        <div v-if="status" class="mt-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-700">
            {{ status }}
        </div>

        <form class="mt-8" @submit.prevent="submit">
            <div>
                <InputLabel for="email" value="Correo electrónico" />
                <TextInput
                    id="email"
                    v-model="form.email"
                    type="email"
                    class="mt-1.5 block w-full"
                    required
                    autofocus
                    autocomplete="username"
                    placeholder="tu.correo@iyemyucatan.com"
                />
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="mt-5">
                <InputLabel for="password" value="Contraseña" />
                <TextInput
                    id="password"
                    v-model="form.password"
                    type="password"
                    class="mt-1.5 block w-full"
                    required
                    autocomplete="current-password"
                    placeholder="••••••••"
                />
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="mt-4 flex items-center justify-end">
                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="rounded-md text-sm text-gray-500 underline decoration-gray-300 underline-offset-2 transition hover:text-iyem-primario hover:decoration-iyem-secundario focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-iyem-secundario"
                >
                    ¿Olvidaste tu contraseña?
                </Link>
            </div>

            <PrimaryButton
                class="mt-7 w-full justify-center py-3 text-sm"
                :class="{ 'opacity-25': form.processing }"
                :disabled="form.processing"
            >
                Ingresar
            </PrimaryButton>
        </form>

        <p class="mt-8 text-center text-xs text-gray-400">
            Acceso restringido · las cuentas son creadas únicamente por el administrador de la plataforma.
        </p>
    </AuthenticationCard>
</template>
