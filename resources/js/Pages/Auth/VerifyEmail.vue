<script setup>
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticationCard from '@/Components/AuthenticationCard.vue';
import Aviso from '@/Components/Aviso.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    status: String,
});

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};

const verificationLinkSent = computed(() => props.status === 'verification-link-sent');

const claseEnlace = 'inline-flex min-h-[44px] items-center rounded-sm text-small text-action underline-offset-2 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-focus focus-visible:ring-offset-2';
</script>

<template>
    <Head title="Verificar correo" />

    <AuthenticationCard
        titulo="Verifica tu correo"
        descripcion="Te enviamos un enlace de verificación. Ábrelo desde tu correo institucional para continuar; si no te llegó, te mandamos otro."
    >
        <Aviso v-if="verificationLinkSent" tipo="exito" class="mb-6">
            Enviamos un enlace nuevo al correo registrado en tu perfil.
        </Aviso>

        <form class="space-y-4" @submit.prevent="submit">
            <PrimaryButton class="w-full" :procesando="form.processing">
                {{ form.processing ? 'Enviando…' : 'Reenviar correo de verificación' }}
            </PrimaryButton>

            <div class="flex flex-wrap items-center justify-between gap-x-4">
                <Link :href="route('profile.show')" :class="claseEnlace">Editar perfil</Link>
                <Link :href="route('logout')" method="post" as="button" :class="claseEnlace">Cerrar sesión</Link>
            </div>
        </form>
    </AuthenticationCard>
</template>
