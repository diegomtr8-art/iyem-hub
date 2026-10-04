<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import ActionMessage from '@/Components/ActionMessage.vue';
import Campo from '@/Components/Campo.vue';
import FormSection from '@/Components/FormSection.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const passwordInput = ref(null);
const currentPasswordInput = ref(null);

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updatePassword = () => {
    form.put(route('user-password.update'), {
        errorBag: 'updatePassword',
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: () => {
            if (form.errors.password) {
                form.reset('password', 'password_confirmation');
                passwordInput.value.focus();
            }

            if (form.errors.current_password) {
                form.reset('current_password');
                currentPasswordInput.value.focus();
            }
        },
    });
};
</script>

<template>
    <FormSection @submitted="updatePassword">
        <template #title>
            Contraseña
        </template>

        <template #description>
            Usa una contraseña larga que no uses en ningún otro sistema.
        </template>

        <template #form>
            <div class="col-span-6 sm:col-span-4">
                <Campo id="current_password" v-slot="campo" etiqueta="Contraseña actual" :error="form.errors.current_password">
                    <TextInput
                        :id="campo.id"
                        ref="currentPasswordInput"
                        v-model="form.current_password"
                        type="password"
                        class="block w-full"
                        :invalido="campo.invalido"
                        :aria-describedby="campo.describedby"
                        autocomplete="current-password"
                    />
                </Campo>
            </div>

            <div class="col-span-6 sm:col-span-4">
                <Campo id="password" v-slot="campo" etiqueta="Contraseña nueva" :error="form.errors.password">
                    <TextInput
                        :id="campo.id"
                        ref="passwordInput"
                        v-model="form.password"
                        type="password"
                        class="block w-full"
                        :invalido="campo.invalido"
                        :aria-describedby="campo.describedby"
                        autocomplete="new-password"
                    />
                </Campo>
            </div>

            <div class="col-span-6 sm:col-span-4">
                <Campo id="password_confirmation" v-slot="campo" etiqueta="Confirma la contraseña nueva" :error="form.errors.password_confirmation">
                    <TextInput
                        :id="campo.id"
                        v-model="form.password_confirmation"
                        type="password"
                        class="block w-full"
                        :invalido="campo.invalido"
                        :aria-describedby="campo.describedby"
                        autocomplete="new-password"
                    />
                </Campo>
            </div>
        </template>

        <template #actions>
            <ActionMessage :on="form.recentlySuccessful">
                Contraseña actualizada.
            </ActionMessage>

            <PrimaryButton :procesando="form.processing">
                {{ form.processing ? 'Guardando…' : 'Cambiar contraseña' }}
            </PrimaryButton>
        </template>
    </FormSection>
</template>
