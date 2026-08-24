<script setup>
import { reactive, ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';

const props = defineProps({
    usuarios: Array,
    roles: Array,
});

const page = usePage();

const editando = ref(null);
const formEdicion = reactive({ name: '', apellido: '', email: '', role: '' });

const abrirEdicion = (usuario) => {
    editando.value = usuario;
    formEdicion.name = usuario.name;
    formEdicion.apellido = usuario.apellido ?? '';
    formEdicion.email = usuario.email;
    formEdicion.role = usuario.roles[0]?.name ?? '';
};

const guardarEdicion = () => {
    router.put(route('admin.usuarios.update', editando.value.id), formEdicion, {
        onSuccess: () => { editando.value = null; },
    });
};

const toggleEstado = (usuario) => {
    router.patch(route('admin.usuarios.estado', usuario.id));
};

const resetearPassword = (usuario) => {
    router.post(route('admin.usuarios.reset-password', usuario.id));
};
</script>

<template>
    <AppLayout title="Usuarios">
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Usuarios
            </h2>
        </template>

        <div
            v-if="page.props.jetstream.flash?.password_temporal"
            class="mb-4 rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 shadow-soft"
        >
            Contraseña temporal para <strong>{{ page.props.jetstream.flash.usuario_creado }}</strong>:
            <code class="rounded bg-white px-2 py-0.5 font-mono">{{ page.props.jetstream.flash.password_temporal }}</code>
            — compártela de forma segura, no volverá a mostrarse.
        </div>

        <div class="flex justify-end">
            <Link
                :href="route('admin.usuarios.create')"
                class="inline-flex items-center gap-2 rounded-lg bg-iyem-gradient px-4 py-2 text-sm font-semibold text-white shadow-soft transition hover:shadow-glow"
            >
                Nuevo usuario
            </Link>
        </div>

        <div class="scrollbar-fina mt-4 overflow-x-auto rounded-2xl border border-iyem-claro bg-white shadow-soft">
            <table class="min-w-full divide-y divide-iyem-claro text-sm">
                <thead class="bg-iyem-50 text-left text-xs uppercase tracking-wide text-iyem-700">
                    <tr>
                        <th class="px-4 py-3">Usuario</th>
                        <th class="px-4 py-3">Correo</th>
                        <th class="px-4 py-3">Rol</th>
                        <th class="px-4 py-3">Estado</th>
                        <th class="px-4 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-iyem-claro">
                    <tr v-for="usuario in usuarios" :key="usuario.id" class="transition hover:bg-iyem-50/60">
                        <td class="px-4 py-3 font-medium text-gray-800">
                            {{ usuario.name }} {{ usuario.apellido }}
                        </td>
                        <td class="px-4 py-3 text-gray-600">
                            {{ usuario.email }}
                        </td>
                        <td class="px-4 py-3 text-gray-600">
                            {{ usuario.roles.map(r => r.name).join(', ') || '—' }}
                        </td>
                        <td class="px-4 py-3">
                            <span
                                class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold"
                                :class="usuario.estado ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-600'"
                            >
                                {{ usuario.estado ? 'Activo' : 'Deshabilitado' }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-3">
                                <button class="text-xs font-semibold text-iyem-700 hover:underline" @click="abrirEdicion(usuario)">
                                    Editar
                                </button>
                                <button class="text-xs font-semibold text-gray-500 hover:underline" @click="resetearPassword(usuario)">
                                    Resetear contraseña
                                </button>
                                <button
                                    v-if="!usuario.es_super_admin"
                                    class="text-xs font-semibold text-red-600 hover:underline"
                                    @click="toggleEstado(usuario)"
                                >
                                    {{ usuario.estado ? 'Deshabilitar' : 'Habilitar' }}
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <Modal :show="editando !== null" @close="editando = null">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-800">
                    Editar usuario
                </h3>

                <div class="mt-4 space-y-4">
                    <div>
                        <InputLabel value="Nombre" />
                        <input v-model="formEdicion.name" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-iyem-secundario focus:ring-iyem-secundario">
                    </div>
                    <div>
                        <InputLabel value="Apellido" />
                        <input v-model="formEdicion.apellido" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-iyem-secundario focus:ring-iyem-secundario">
                    </div>
                    <div>
                        <InputLabel value="Correo" />
                        <input v-model="formEdicion.email" type="email" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-iyem-secundario focus:ring-iyem-secundario">
                    </div>
                    <div>
                        <InputLabel value="Rol" />
                        <select v-model="formEdicion.role" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-iyem-secundario focus:ring-iyem-secundario">
                            <option v-for="rol in roles" :key="rol.id" :value="rol.name">
                                {{ rol.name }}
                            </option>
                        </select>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="editando = null">
                        Cancelar
                    </SecondaryButton>
                    <PrimaryButton @click="guardarEdicion">
                        Guardar cambios
                    </PrimaryButton>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>
