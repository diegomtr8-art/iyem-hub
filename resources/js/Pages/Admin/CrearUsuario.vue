<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Campo from '@/Components/Campo.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

defineProps({
    roles: Array,
});

const form = useForm({
    name: '',
    apellido: '',
    email: '',
    role: '',
});

const submit = () => {
    form.post(route('admin.usuarios.store'));
};

const claseSelect = 'block h-10 w-full rounded-md bg-surface px-3 text-body text-ink focus:outline-none focus:ring-2 focus:ring-focus focus:ring-offset-2';
</script>

<template>
    <AppLayout title="Crear usuario">
        <template #header>
            <span>Crear usuario</span>
        </template>

        <div class="mx-auto max-w-xl">
            <Link
                :href="route('admin.usuarios.index')"
                class="inline-flex min-h-[44px] items-center gap-1 rounded-sm text-small text-action underline-offset-2 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-focus focus-visible:ring-offset-2"
            >
                ← Volver a usuarios
            </Link>

            <h1 class="mt-2 text-display-lg text-ink">Crear usuario</h1>
            <p class="mt-1 text-body text-ink-600">
                Se generará una contraseña temporal que verás una sola vez al guardar.
            </p>

            <form class="mt-6 overflow-hidden rounded-lg border border-line bg-surface shadow-sm" novalidate @submit.prevent="submit">
                <div class="space-y-5 p-5 sm:p-6">
                    <Campo id="name" v-slot="campo" etiqueta="Nombre" :error="form.errors.name">
                        <TextInput :id="campo.id" v-model="form.name" class="block w-full" :invalido="campo.invalido" :aria-describedby="campo.describedby" required autofocus autocomplete="off" />
                    </Campo>

                    <Campo id="apellido" v-slot="campo" etiqueta="Apellido" :error="form.errors.apellido">
                        <TextInput :id="campo.id" v-model="form.apellido" class="block w-full" :invalido="campo.invalido" :aria-describedby="campo.describedby" autocomplete="off" />
                    </Campo>

                    <Campo
                        id="email"
                        v-slot="campo"
                        etiqueta="Correo electrónico"
                        ayuda="Usa el correo institucional de la persona."
                        :error="form.errors.email"
                    >
                        <TextInput :id="campo.id" v-model="form.email" type="email" inputmode="email" class="block w-full" :invalido="campo.invalido" :aria-describedby="campo.describedby" required autocomplete="off" />
                    </Campo>

                    <Campo id="role" v-slot="campo" etiqueta="Rol" :error="form.errors.role">
                        <select
                            :id="campo.id"
                            v-model="form.role"
                            required
                            :class="[claseSelect, campo.invalido ? 'border-danger' : 'border-line-strong']"
                            :aria-invalid="campo.invalido ? 'true' : undefined"
                            :aria-describedby="campo.describedby"
                        >
                            <option value="" disabled>Selecciona un rol</option>
                            <option v-for="rol in roles" :key="rol.id" :value="rol.name">
                                {{ rol.name }} — {{ rol.descripcion }}
                            </option>
                        </select>
                    </Campo>
                </div>

                <div class="flex justify-end border-t border-line bg-surface-50 px-5 py-3 sm:px-6">
                    <PrimaryButton :procesando="form.processing">
                        {{ form.processing ? 'Creando…' : 'Crear usuario' }}
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
