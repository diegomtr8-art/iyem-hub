<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import ActionMessage from '@/Components/ActionMessage.vue';
import ActionSection from '@/Components/ActionSection.vue';
import Checkbox from '@/Components/Checkbox.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import DangerButton from '@/Components/DangerButton.vue';
import DialogModal from '@/Components/DialogModal.vue';
import FormSection from '@/Components/FormSection.vue';
import Campo from '@/Components/Campo.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import SectionBorder from '@/Components/SectionBorder.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    tokens: Array,
    availablePermissions: Array,
    defaultPermissions: Array,
});

const createApiTokenForm = useForm({
    name: '',
    permissions: props.defaultPermissions,
});

const updateApiTokenForm = useForm({
    permissions: [],
});

const deleteApiTokenForm = useForm({});

const displayingToken = ref(false);
const managingPermissionsFor = ref(null);
const apiTokenBeingDeleted = ref(null);

const createApiToken = () => {
    createApiTokenForm.post(route('api-tokens.store'), {
        preserveScroll: true,
        onSuccess: () => {
            displayingToken.value = true;
            createApiTokenForm.reset();
        },
    });
};

const manageApiTokenPermissions = (token) => {
    updateApiTokenForm.permissions = token.abilities;
    managingPermissionsFor.value = token;
};

const updateApiToken = () => {
    updateApiTokenForm.put(route('api-tokens.update', managingPermissionsFor.value), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => (managingPermissionsFor.value = null),
    });
};

const confirmApiTokenDeletion = (token) => {
    apiTokenBeingDeleted.value = token;
};

const deleteApiToken = () => {
    deleteApiTokenForm.delete(route('api-tokens.destroy', apiTokenBeingDeleted.value), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => (apiTokenBeingDeleted.value = null),
    });
};
</script>

<template>
    <div>
        <!-- Crear token -->
        <FormSection @submitted="createApiToken">
            <template #title>
                Crear token de API
            </template>

            <template #description>
                Un token permite que un servicio externo se identifique ante la plataforma en tu nombre.
            </template>

            <template #form>
                <div class="col-span-6 sm:col-span-4">
                    <Campo id="name" v-slot="campo" etiqueta="Nombre del token" :error="createApiTokenForm.errors.name">
                        <TextInput
                            :id="campo.id"
                            v-model="createApiTokenForm.name"
                            type="text"
                            class="block w-full"
                            :invalido="campo.invalido"
                            :aria-describedby="campo.describedby"
                            autofocus
                        />
                    </Campo>
                </div>

                <fieldset v-if="availablePermissions.length > 0" class="col-span-6">
                    <legend class="text-body-strong text-ink">Permisos</legend>

                    <div class="mt-2 grid grid-cols-1 gap-2 md:grid-cols-2">
                        <label v-for="permission in availablePermissions" :key="permission" class="flex min-h-[44px] items-center gap-2">
                            <Checkbox v-model:checked="createApiTokenForm.permissions" :value="permission" />
                            <span class="font-mono text-small text-ink">{{ permission }}</span>
                        </label>
                    </div>
                </fieldset>
            </template>

            <template #actions>
                <ActionMessage :on="createApiTokenForm.recentlySuccessful">
                    Token creado.
                </ActionMessage>

                <PrimaryButton :procesando="createApiTokenForm.processing">
                    {{ createApiTokenForm.processing ? 'Creando…' : 'Crear token' }}
                </PrimaryButton>
            </template>
        </FormSection>

        <div v-if="tokens.length > 0">
            <SectionBorder />

            <div class="mt-10 sm:mt-0">
                <ActionSection>
                    <template #title>
                        Tokens existentes
                    </template>

                    <template #description>
                        Elimina los tokens que ya no uses.
                    </template>

                    <template #content>
                        <ul class="divide-y divide-line">
                            <li v-for="token in tokens" :key="token.id" class="flex flex-wrap items-center justify-between gap-3 py-3 first:pt-0 last:pb-0">
                                <p class="min-w-0 break-all text-body-strong text-ink">
                                    {{ token.name }}
                                </p>

                                <div class="flex items-center gap-2">
                                    <span v-if="token.last_used_ago" class="text-small text-ink-600">
                                        Último uso {{ token.last_used_ago }}
                                    </span>

                                    <button
                                        v-if="availablePermissions.length > 0"
                                        type="button"
                                        class="inline-flex h-8 items-center rounded-md px-3 text-small font-medium text-action hover:bg-surface-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-focus"
                                        @click="manageApiTokenPermissions(token)"
                                    >
                                        Permisos
                                    </button>

                                    <button
                                        type="button"
                                        class="inline-flex h-8 items-center rounded-md px-3 text-small font-medium text-danger hover:bg-surface-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-focus"
                                        @click="confirmApiTokenDeletion(token)"
                                    >
                                        Eliminar
                                    </button>
                                </div>
                            </li>
                        </ul>
                    </template>
                </ActionSection>
            </div>
        </div>

        <!-- Valor del token recién creado -->
        <DialogModal :show="displayingToken" @close="displayingToken = false">
            <template #title>
                Token de API
            </template>

            <template #content>
                <p>Copia tu token nuevo ahora. Por seguridad, no volverá a mostrarse.</p>

                <p v-if="$page.props.jetstream.flash.token" class="mt-4 break-all rounded-md border border-line bg-surface-50 px-4 py-2 text-code text-ink">
                    {{ $page.props.jetstream.flash.token }}
                </p>
            </template>

            <template #footer>
                <SecondaryButton @click="displayingToken = false">
                    Cerrar
                </SecondaryButton>
            </template>
        </DialogModal>

        <!-- Permisos de un token -->
        <DialogModal :show="managingPermissionsFor != null" @close="managingPermissionsFor = null">
            <template #title>
                Permisos de «{{ managingPermissionsFor?.name }}»
            </template>

            <template #content>
                <div class="grid grid-cols-1 gap-2 md:grid-cols-2">
                    <label v-for="permission in availablePermissions" :key="permission" class="flex min-h-[44px] items-center gap-2">
                        <Checkbox v-model:checked="updateApiTokenForm.permissions" :value="permission" />
                        <span class="font-mono text-small text-ink">{{ permission }}</span>
                    </label>
                </div>
            </template>

            <template #footer>
                <SecondaryButton @click="managingPermissionsFor = null">
                    Cancelar
                </SecondaryButton>

                <PrimaryButton type="button" :procesando="updateApiTokenForm.processing" @click="updateApiToken">
                    {{ updateApiTokenForm.processing ? 'Guardando…' : 'Guardar permisos' }}
                </PrimaryButton>
            </template>
        </DialogModal>

        <!-- Confirmar eliminación -->
        <ConfirmationModal :show="apiTokenBeingDeleted != null" @close="apiTokenBeingDeleted = null">
            <template #title>
                ¿Eliminar el token «{{ apiTokenBeingDeleted?.name }}»?
            </template>

            <template #content>
                Los servicios que lo usen dejarán de poder conectarse. Esto no se puede deshacer.
            </template>

            <template #footer>
                <SecondaryButton @click="apiTokenBeingDeleted = null">
                    Cancelar
                </SecondaryButton>

                <DangerButton :procesando="deleteApiTokenForm.processing" @click="deleteApiToken">
                    {{ deleteApiTokenForm.processing ? 'Eliminando…' : 'Eliminar token' }}
                </DangerButton>
            </template>
        </ConfirmationModal>
    </div>
</template>
