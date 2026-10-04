<script setup>
import { ref } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import ActionSection from '@/Components/ActionSection.vue';
import DangerButton from '@/Components/DangerButton.vue';
import DialogModal from '@/Components/DialogModal.vue';
import InputError from '@/Components/InputError.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const page = usePage();

const confirmingUserDeletion = ref(false);
const passwordInput = ref(null);

const form = useForm({
    password: '',
});

const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true;

    setTimeout(() => passwordInput.value.focus(), 250);
};

const deleteUser = () => {
    form.delete(route('current-user.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value.focus(),
        onFinish: () => form.reset(),
    });
};

const closeModal = () => {
    confirmingUserDeletion.value = false;

    form.reset();
};
</script>

<template>
    <ActionSection>
        <template #title>
            Eliminar cuenta
        </template>

        <template #description>
            Borra tu cuenta de forma permanente.
        </template>

        <template #content>
            <p class="max-w-xl text-body text-ink-600">
                Al eliminar tu cuenta se borran para siempre sus datos. Antes de hacerlo, guarda cualquier información que quieras conservar.
            </p>

            <div class="mt-5">
                <DangerButton @click="confirmUserDeletion">
                    Eliminar mi cuenta
                </DangerButton>
            </div>

            <DialogModal :show="confirmingUserDeletion" @close="closeModal">
                <template #title>
                    ¿Eliminar la cuenta {{ page.props.auth.user.email }}?
                </template>

                <template #content>
                    <p>
                        La cuenta y todos sus datos se borrarán de forma permanente; esto no se puede deshacer. Escribe tu contraseña para confirmar.
                    </p>

                    <div class="mt-4">
                        <label for="eliminar-cuenta-contrasena" class="block text-body-strong text-ink">Contraseña</label>
                        <TextInput
                            id="eliminar-cuenta-contrasena"
                            ref="passwordInput"
                            v-model="form.password"
                            type="password"
                            class="mt-1.5 block w-full sm:w-3/4"
                            :invalido="Boolean(form.errors.password)"
                            :aria-describedby="form.errors.password ? 'eliminar-cuenta-contrasena-error' : undefined"
                            autocomplete="current-password"
                            @keyup.enter="deleteUser"
                        />

                        <InputError id="eliminar-cuenta-contrasena-error" :message="form.errors.password" class="mt-1.5" />
                    </div>
                </template>

                <template #footer>
                    <SecondaryButton @click="closeModal">
                        Cancelar
                    </SecondaryButton>

                    <DangerButton :procesando="form.processing" @click="deleteUser">
                        {{ form.processing ? 'Eliminando…' : 'Eliminar mi cuenta' }}
                    </DangerButton>
                </template>
            </DialogModal>
        </template>
    </ActionSection>
</template>
