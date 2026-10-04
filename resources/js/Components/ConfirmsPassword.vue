<script setup>
import { ref, reactive, nextTick } from 'vue';
import DialogModal from './DialogModal.vue';
import InputError from './InputError.vue';
import PrimaryButton from './PrimaryButton.vue';
import SecondaryButton from './SecondaryButton.vue';
import TextInput from './TextInput.vue';

const emit = defineEmits(['confirmed']);

defineProps({
    title: {
        type: String,
        default: 'Confirma tu contraseña',
    },
    content: {
        type: String,
        default: 'Por seguridad, escribe tu contraseña para continuar.',
    },
    button: {
        type: String,
        default: 'Confirmar',
    },
});

const confirmingPassword = ref(false);

const form = reactive({
    password: '',
    error: '',
    processing: false,
});

const passwordInput = ref(null);

const startConfirmingPassword = () => {
    axios.get(route('password.confirmation')).then(response => {
        if (response.data.confirmed) {
            emit('confirmed');
        } else {
            confirmingPassword.value = true;

            setTimeout(() => passwordInput.value.focus(), 250);
        }
    });
};

const confirmPassword = () => {
    form.processing = true;

    axios.post(route('password.confirm'), {
        password: form.password,
    }).then(() => {
        form.processing = false;

        closeModal();
        nextTick().then(() => emit('confirmed'));

    }).catch(error => {
        form.processing = false;
        form.error = error.response.data.errors.password[0];
        passwordInput.value.focus();
    });
};

const closeModal = () => {
    confirmingPassword.value = false;
    form.password = '';
    form.error = '';
};
</script>

<template>
    <span>
        <span @click="startConfirmingPassword">
            <slot />
        </span>

        <DialogModal :show="confirmingPassword" @close="closeModal">
            <template #title>
                {{ title }}
            </template>

            <template #content>
                <p>{{ content }}</p>

                <div class="mt-4">
                    <label for="confirmar-contrasena" class="block text-body-strong text-ink">Contraseña</label>
                    <TextInput
                        id="confirmar-contrasena"
                        ref="passwordInput"
                        v-model="form.password"
                        type="password"
                        class="mt-1.5 block w-full sm:w-3/4"
                        :invalido="Boolean(form.error)"
                        :aria-describedby="form.error ? 'confirmar-contrasena-error' : undefined"
                        autocomplete="current-password"
                        @keyup.enter="confirmPassword"
                    />

                    <InputError id="confirmar-contrasena-error" :message="form.error" class="mt-1.5" />
                </div>
            </template>

            <template #footer>
                <SecondaryButton @click="closeModal">
                    Cancelar
                </SecondaryButton>

                <PrimaryButton type="button" :procesando="form.processing" @click="confirmPassword">
                    {{ form.processing ? 'Confirmando…' : button }}
                </PrimaryButton>
            </template>
        </DialogModal>
    </span>
</template>
