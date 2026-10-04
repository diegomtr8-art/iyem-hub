<script setup>
import { computed, reactive, ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Aviso from '@/Components/Aviso.vue';
import Campo from '@/Components/Campo.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import DangerButton from '@/Components/DangerButton.vue';
import DialogModal from '@/Components/DialogModal.vue';
import Dropdown from '@/Components/Dropdown.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    usuarios: Array,
    roles: Array,
});

const page = usePage();
const errores = computed(() => page.props.errors ?? {});

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

/*
 * Confirmaciones. Restablecer la contraseña deja sin acceso a la persona
 * hasta que reciba la temporal, y deshabilitar le corta la sesión: las dos
 * nombran a quién afectan. Habilitar no necesita confirmación.
 */
const confirmando = ref(null); // { tipo: 'reset' | 'estado', usuario }

const nombre = (usuario) => `${usuario.name} ${usuario.apellido ?? ''}`.trim();

const pedirConfirmacion = (tipo, usuario) => {
    if (tipo === 'estado' && !usuario.estado) {
        toggleEstado(usuario);
        return;
    }
    confirmando.value = { tipo, usuario };
};

const confirmar = () => {
    const { tipo, usuario } = confirmando.value;
    confirmando.value = null;
    if (tipo === 'reset') resetearPassword(usuario);
    else toggleEstado(usuario);
};

const claseSelect = 'block h-10 w-full rounded-md border-line-strong bg-surface px-3 text-body text-ink focus:border-focus focus:outline-none focus:ring-2 focus:ring-focus focus:ring-offset-2';
const claseOpcion = 'flex min-h-[44px] w-full items-center px-4 text-start text-body transition-colors hover:bg-surface-100 focus:outline-none focus-visible:bg-surface-100 focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-focus';
</script>

<template>
    <AppLayout title="Usuarios">
        <template #header>
            <span>Usuarios</span>
        </template>

        <div class="mx-auto max-w-7xl space-y-6">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h1 class="text-display-lg text-ink">Usuarios</h1>
                    <p class="mt-1 text-body text-ink-600">Cuentas de la plataforma, su rol y su estado.</p>
                </div>
                <Link
                    :href="route('admin.usuarios.create')"
                    class="inline-flex h-10 items-center justify-center gap-2 rounded-md bg-action px-4 text-body-strong text-action-ink transition-colors hover:bg-action-hover focus:outline-none focus-visible:ring-2 focus-visible:ring-focus focus-visible:ring-offset-2"
                >
                    Crear usuario
                </Link>
            </div>

            <Aviso v-if="page.props.jetstream.flash?.password_temporal" tipo="exito">
                Contraseña temporal para <strong>{{ page.props.jetstream.flash.usuario_creado }}</strong>:
                <code class="rounded-sm bg-surface px-2 py-0.5 text-code text-ink">{{ page.props.jetstream.flash.password_temporal }}</code>
                — compártela de forma segura; no volverá a mostrarse.
            </Aviso>

            <!-- Sin overflow-hidden: los menús de cada fila se saldrían recortados. -->
            <div class="rounded-lg border border-line bg-surface shadow-sm">
                <template v-if="usuarios.length">
                    <!-- Teléfono: lista de tarjetas -->
                    <ul class="divide-y divide-line sm:hidden">
                        <li v-for="usuario in usuarios" :key="usuario.id" class="flex items-start gap-3 px-4 py-3">
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-body-strong text-ink">{{ nombre(usuario) }}</p>
                                <p class="truncate text-small text-ink-600">{{ usuario.email }}</p>
                                <div class="mt-1.5 flex flex-wrap items-center gap-2">
                                    <span class="text-small text-ink-600">{{ usuario.roles.map(r => r.name).join(', ') || 'Sin rol' }}</span>
                                    <span
                                        class="inline-flex h-6 items-center gap-1 rounded-sm px-2 text-caption text-ink"
                                        :class="usuario.estado ? 'bg-success-surface' : 'bg-surface-100'"
                                    >
                                        <span class="h-1.5 w-1.5 rounded-full" :class="usuario.estado ? 'bg-success' : 'bg-ink-400'" aria-hidden="true" />
                                        {{ usuario.estado ? 'Activo' : 'Deshabilitado' }}
                                    </span>
                                </div>
                            </div>
                            <Dropdown align="right" width="48">
                                <template #trigger>
                                    <button
                                        type="button"
                                        class="toque-minimo -mr-2 flex items-center justify-center rounded-md text-ink-600 hover:bg-surface-100 hover:text-ink focus:outline-none focus-visible:ring-2 focus-visible:ring-focus"
                                        :aria-label="`Acciones para ${nombre(usuario)}`"
                                    >
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.75a.75.75 0 110-1.5.75.75 0 010 1.5zM12 12.75a.75.75 0 110-1.5.75.75 0 010 1.5zM12 18.75a.75.75 0 110-1.5.75.75 0 010 1.5z" />
                                        </svg>
                                    </button>
                                </template>
                                <template #content>
                                    <button type="button" :class="[claseOpcion, 'text-ink']" @click="abrirEdicion(usuario)">Editar</button>
                                    <button type="button" :class="[claseOpcion, 'text-ink']" @click="pedirConfirmacion('reset', usuario)">Restablecer contraseña</button>
                                    <button
                                        v-if="!usuario.es_super_admin"
                                        type="button"
                                        :class="[claseOpcion, usuario.estado ? 'text-danger' : 'text-ink']"
                                        @click="pedirConfirmacion('estado', usuario)"
                                    >
                                        {{ usuario.estado ? 'Deshabilitar' : 'Habilitar' }}
                                    </button>
                                </template>
                            </Dropdown>
                        </li>
                    </ul>

                    <!-- Escritorio: tabla -->
                    <div class="hidden sm:block">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="[&>th:first-child]:rounded-tl-lg [&>th:last-child]:rounded-tr-lg">
                                    <th scope="col" class="bg-surface-50 px-3 py-2 text-overline text-ink-600">Usuario</th>
                                    <th scope="col" class="px-3 py-2 text-overline text-ink-600">Correo</th>
                                    <th scope="col" class="px-3 py-2 text-overline text-ink-600">Rol</th>
                                    <th scope="col" class="px-3 py-2 text-overline text-ink-600">Estado</th>
                                    <th scope="col" class="bg-surface-50 px-3 py-2 text-right text-overline text-ink-600">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="usuario in usuarios" :key="usuario.id" class="border-t border-line hover:bg-surface-100">
                                    <td class="h-11 px-3 text-body-strong text-ink">{{ nombre(usuario) }}</td>
                                    <td class="px-3 text-body text-ink-600">{{ usuario.email }}</td>
                                    <td class="px-3 text-body text-ink-600">{{ usuario.roles.map(r => r.name).join(', ') || '—' }}</td>
                                    <td class="px-3">
                                        <span
                                            class="inline-flex h-6 items-center gap-1 rounded-sm px-2 text-caption text-ink"
                                            :class="usuario.estado ? 'bg-success-surface' : 'bg-surface-100'"
                                        >
                                            <span class="h-1.5 w-1.5 rounded-full" :class="usuario.estado ? 'bg-success' : 'bg-ink-400'" aria-hidden="true" />
                                            {{ usuario.estado ? 'Activo' : 'Deshabilitado' }}
                                        </span>
                                    </td>
                                    <td class="px-3 text-right">
                                        <div class="inline-flex justify-end">
                                            <Dropdown align="right" width="48">
                                                <template #trigger>
                                                    <button
                                                        type="button"
                                                        class="flex h-8 w-8 items-center justify-center rounded-md text-ink-600 hover:bg-surface-100 hover:text-ink focus:outline-none focus-visible:ring-2 focus-visible:ring-focus"
                                                        :aria-label="`Acciones para ${nombre(usuario)}`"
                                                    >
                                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.75a.75.75 0 110-1.5.75.75 0 010 1.5zM12 12.75a.75.75 0 110-1.5.75.75 0 010 1.5zM12 18.75a.75.75 0 110-1.5.75.75 0 010 1.5z" />
                                                        </svg>
                                                    </button>
                                                </template>
                                                <template #content>
                                                    <button type="button" :class="[claseOpcion, 'text-ink']" @click="abrirEdicion(usuario)">Editar</button>
                                                    <button type="button" :class="[claseOpcion, 'text-ink']" @click="pedirConfirmacion('reset', usuario)">Restablecer contraseña</button>
                                                    <button
                                                        v-if="!usuario.es_super_admin"
                                                        type="button"
                                                        :class="[claseOpcion, usuario.estado ? 'text-danger' : 'text-ink']"
                                                        @click="pedirConfirmacion('estado', usuario)"
                                                    >
                                                        {{ usuario.estado ? 'Deshabilitar' : 'Habilitar' }}
                                                    </button>
                                                </template>
                                            </Dropdown>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </template>
                <div v-else class="p-6">
                    <p class="text-body-strong text-ink">Aún no hay usuarios.</p>
                    <p class="mt-1 text-small text-ink-600">Crea la primera cuenta con el botón Crear usuario.</p>
                </div>
            </div>
        </div>

        <!-- Edición -->
        <DialogModal :show="editando !== null" max-width="lg" @close="editando = null">
            <template #title>Editar usuario</template>

            <template #content>
                <form id="form-edicion" class="space-y-4" novalidate @submit.prevent="guardarEdicion">
                    <Campo id="edicion-nombre" v-slot="campo" etiqueta="Nombre" :error="errores.name">
                        <TextInput :id="campo.id" v-model="formEdicion.name" class="block w-full" :invalido="campo.invalido" :aria-describedby="campo.describedby" autocomplete="off" />
                    </Campo>
                    <Campo id="edicion-apellido" v-slot="campo" etiqueta="Apellido" :error="errores.apellido">
                        <TextInput :id="campo.id" v-model="formEdicion.apellido" class="block w-full" :invalido="campo.invalido" :aria-describedby="campo.describedby" autocomplete="off" />
                    </Campo>
                    <Campo id="edicion-correo" v-slot="campo" etiqueta="Correo" :error="errores.email">
                        <TextInput :id="campo.id" v-model="formEdicion.email" type="email" inputmode="email" class="block w-full" :invalido="campo.invalido" :aria-describedby="campo.describedby" autocomplete="off" />
                    </Campo>
                    <Campo id="edicion-rol" v-slot="campo" etiqueta="Rol" :error="errores.role">
                        <select
                            :id="campo.id"
                            v-model="formEdicion.role"
                            :class="[claseSelect, campo.invalido ? 'border-danger' : '']"
                            :aria-invalid="campo.invalido ? 'true' : undefined"
                            :aria-describedby="campo.describedby"
                        >
                            <option v-for="rol in roles" :key="rol.id" :value="rol.name">
                                {{ rol.name }}
                            </option>
                        </select>
                    </Campo>
                </form>
            </template>

            <template #footer>
                <SecondaryButton @click="editando = null">Cancelar</SecondaryButton>
                <PrimaryButton form="form-edicion">Guardar cambios</PrimaryButton>
            </template>
        </DialogModal>

        <!-- Confirmación de acciones con consecuencia -->
        <ConfirmationModal :show="confirmando !== null" @close="confirmando = null">
            <template #title>
                <template v-if="confirmando?.tipo === 'reset'">¿Restablecer la contraseña de {{ nombre(confirmando.usuario) }}?</template>
                <template v-else-if="confirmando">¿Deshabilitar la cuenta de {{ nombre(confirmando.usuario) }}?</template>
            </template>

            <template #content>
                <template v-if="confirmando?.tipo === 'reset'">
                    Su contraseña actual dejará de funcionar y se generará una temporal que verás una sola vez.
                </template>
                <template v-else-if="confirmando">
                    {{ confirmando.usuario.email }} no podrá entrar a la plataforma hasta que vuelvas a habilitar la cuenta.
                </template>
            </template>

            <template #footer>
                <SecondaryButton @click="confirmando = null">Cancelar</SecondaryButton>
                <DangerButton @click="confirmar">
                    {{ confirmando?.tipo === 'reset' ? 'Restablecer contraseña' : 'Deshabilitar cuenta' }}
                </DangerButton>
            </template>
        </ConfirmationModal>
    </AppLayout>
</template>
