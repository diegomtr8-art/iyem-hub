<script setup>
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
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
</script>

<template>
    <AppLayout title="Crear usuario">
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Crear usuario
            </h2>
        </template>

        <div class="max-w-xl rounded-2xl border border-iyem-claro bg-white p-6 shadow-soft sm:p-8">
            <form class="space-y-4" @submit.prevent="submit">
                <div>
                    <InputLabel for="name" value="Nombre" />
                    <TextInput id="name" v-model="form.name" class="mt-1 block w-full" required autofocus />
                    <InputError class="mt-2" :message="form.errors.name" />
                </div>

                <div>
                    <InputLabel for="apellido" value="Apellido" />
                    <TextInput id="apellido" v-model="form.apellido" class="mt-1 block w-full" />
                    <InputError class="mt-2" :message="form.errors.apellido" />
                </div>

                <div>
                    <InputLabel for="email" value="Correo electrónico" />
                    <TextInput id="email" v-model="form.email" type="email" class="mt-1 block w-full" required />
                    <InputError class="mt-2" :message="form.errors.email" />
                </div>

                <div>
                    <InputLabel for="role" value="Rol" />
                    <select
                        id="role"
                        v-model="form.role"
                        required
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-iyem-secundario focus:ring-iyem-secundario"
                    >
                        <option value="" disabled>Selecciona un rol</option>
                        <option v-for="rol in roles" :key="rol.id" :value="rol.name">
                            {{ rol.name }} — {{ rol.descripcion }}
                        </option>
                    </select>
                    <InputError class="mt-2" :message="form.errors.role" />
                </div>

                <p class="text-xs text-gray-500">
                    Se generará una contraseña temporal que se mostrará una sola vez tras crear el usuario.
                </p>

                <PrimaryButton :disabled="form.processing">
                    Crear usuario
                </PrimaryButton>
            </form>
        </div>
    </AppLayout>
</template>
