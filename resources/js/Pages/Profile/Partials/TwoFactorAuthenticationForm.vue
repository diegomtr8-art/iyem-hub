<script setup>
import { ref, computed, watch } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import ActionSection from '@/Components/ActionSection.vue';
import ConfirmsPassword from '@/Components/ConfirmsPassword.vue';
import DangerButton from '@/Components/DangerButton.vue';
import Campo from '@/Components/Campo.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    requiresConfirmation: Boolean,
});

const page = usePage();
const enabling = ref(false);
const confirming = ref(false);
const disabling = ref(false);
const qrCode = ref(null);
const setupKey = ref(null);
const recoveryCodes = ref([]);

const confirmationForm = useForm({
    code: '',
});

const twoFactorEnabled = computed(
    () => ! enabling.value && page.props.auth.user?.two_factor_enabled,
);

watch(twoFactorEnabled, () => {
    if (! twoFactorEnabled.value) {
        confirmationForm.reset();
        confirmationForm.clearErrors();
    }
});

const enableTwoFactorAuthentication = () => {
    enabling.value = true;

    router.post(route('two-factor.enable'), {}, {
        preserveScroll: true,
        onSuccess: () => Promise.all([
            showQrCode(),
            showSetupKey(),
            showRecoveryCodes(),
        ]),
        onFinish: () => {
            enabling.value = false;
            confirming.value = props.requiresConfirmation;
        },
    });
};

const showQrCode = () => {
    return axios.get(route('two-factor.qr-code')).then(response => {
        qrCode.value = response.data.svg;
    });
};

const showSetupKey = () => {
    return axios.get(route('two-factor.secret-key')).then(response => {
        setupKey.value = response.data.secretKey;
    });
}

const showRecoveryCodes = () => {
    return axios.get(route('two-factor.recovery-codes')).then(response => {
        recoveryCodes.value = response.data;
    });
};

const confirmTwoFactorAuthentication = () => {
    confirmationForm.post(route('two-factor.confirm'), {
        errorBag: "confirmTwoFactorAuthentication",
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            confirming.value = false;
            qrCode.value = null;
            setupKey.value = null;
        },
    });
};

const regenerateRecoveryCodes = () => {
    axios
        .post(route('two-factor.recovery-codes'))
        .then(() => showRecoveryCodes());
};

const disableTwoFactorAuthentication = () => {
    disabling.value = true;

    router.delete(route('two-factor.disable'), {
        preserveScroll: true,
        onSuccess: () => {
            disabling.value = false;
            confirming.value = false;
        },
    });
};
</script>

<template>
    <ActionSection>
        <template #title>
            Verificación en dos pasos
        </template>

        <template #description>
            Protege tu cuenta pidiendo un código de tu teléfono además de la contraseña.
        </template>

        <template #content>
            <h4 class="flex items-center gap-2 text-subtitle text-ink">
                <span
                    class="inline-flex h-6 items-center gap-1 rounded-sm px-2 text-caption text-ink"
                    :class="twoFactorEnabled && ! confirming ? 'bg-success-surface' : (twoFactorEnabled ? 'bg-warning-surface' : 'bg-surface-100')"
                >
                    <span
                        class="h-1.5 w-1.5 rounded-full"
                        :class="twoFactorEnabled && ! confirming ? 'bg-success' : (twoFactorEnabled ? 'bg-warning' : 'bg-ink-400')"
                        aria-hidden="true"
                    />
                    {{ twoFactorEnabled && ! confirming ? 'Activa' : (twoFactorEnabled ? 'Por confirmar' : 'Inactiva') }}
                </span>
                <template v-if="twoFactorEnabled && ! confirming">La verificación en dos pasos está activa.</template>
                <template v-else-if="twoFactorEnabled && confirming">Termina de activar la verificación en dos pasos.</template>
                <template v-else>La verificación en dos pasos no está activa.</template>
            </h4>

            <p class="mt-3 max-w-xl text-body text-ink-600">
                Con la verificación activa, al iniciar sesión se te pedirá un código temporal que genera una aplicación de autenticación en tu teléfono (por ejemplo, Google Authenticator).
            </p>

            <div v-if="twoFactorEnabled">
                <div v-if="qrCode">
                    <p class="mt-4 max-w-xl text-body" :class="confirming ? 'font-semibold text-ink' : 'text-ink-600'">
                        <template v-if="confirming">
                            Para terminar, escanea este código QR con tu aplicación de autenticación o escribe la clave de configuración, y luego ingresa el código que te muestre.
                        </template>
                        <template v-else>
                            La verificación en dos pasos quedó activa. Escanea este código QR con tu aplicación de autenticación o escribe la clave de configuración.
                        </template>
                    </p>

                    <!-- Fondo blanco fijo a propósito: el QR necesita contraste
                         máximo para que la cámara lo lea, también en tema oscuro. -->
                    <div class="mt-4 inline-block rounded-md border border-line bg-white p-2" v-html="qrCode" />

                    <p v-if="setupKey" class="mt-4 max-w-xl text-body text-ink-600">
                        Clave de configuración:
                        <span class="break-all rounded-sm bg-surface-100 px-1.5 py-0.5 text-code text-ink" v-html="setupKey"></span>
                    </p>

                    <div v-if="confirming" class="mt-4 max-w-xs">
                        <Campo id="code" v-slot="campo" etiqueta="Código" :error="confirmationForm.errors.code">
                            <TextInput
                                :id="campo.id"
                                v-model="confirmationForm.code"
                                type="text"
                                name="code"
                                class="block w-full font-mono tracking-[0.3em]"
                                :invalido="campo.invalido"
                                :aria-describedby="campo.describedby"
                                inputmode="numeric"
                                autofocus
                                autocomplete="one-time-code"
                                @keyup.enter="confirmTwoFactorAuthentication"
                            />
                        </Campo>
                    </div>
                </div>

                <div v-if="recoveryCodes.length > 0 && ! confirming">
                    <p class="mt-4 max-w-xl text-body-strong text-ink">
                        Guarda estos códigos de recuperación en un gestor de contraseñas. Te sirven para entrar si pierdes el teléfono con la aplicación.
                    </p>

                    <ul class="mt-4 grid max-w-xl gap-1 rounded-md border border-line bg-surface-50 p-4 text-code text-ink sm:grid-cols-2">
                        <li v-for="code in recoveryCodes" :key="code">
                            {{ code }}
                        </li>
                    </ul>
                </div>
            </div>

            <div class="mt-5">
                <div v-if="! twoFactorEnabled">
                    <ConfirmsPassword @confirmed="enableTwoFactorAuthentication">
                        <PrimaryButton type="button" :procesando="enabling">
                            {{ enabling ? 'Activando…' : 'Activar verificación' }}
                        </PrimaryButton>
                    </ConfirmsPassword>
                </div>

                <div v-else class="flex flex-wrap gap-3">
                    <ConfirmsPassword @confirmed="confirmTwoFactorAuthentication">
                        <PrimaryButton
                            v-if="confirming"
                            type="button"
                            :procesando="enabling || confirmationForm.processing"
                        >
                            {{ confirmationForm.processing ? 'Confirmando…' : 'Confirmar código' }}
                        </PrimaryButton>
                    </ConfirmsPassword>

                    <ConfirmsPassword @confirmed="regenerateRecoveryCodes">
                        <SecondaryButton v-if="recoveryCodes.length > 0 && ! confirming">
                            Generar códigos nuevos
                        </SecondaryButton>
                    </ConfirmsPassword>

                    <ConfirmsPassword @confirmed="showRecoveryCodes">
                        <SecondaryButton v-if="recoveryCodes.length === 0 && ! confirming">
                            Ver códigos de recuperación
                        </SecondaryButton>
                    </ConfirmsPassword>

                    <ConfirmsPassword @confirmed="disableTwoFactorAuthentication">
                        <SecondaryButton v-if="confirming" :disabled="disabling">
                            Cancelar
                        </SecondaryButton>
                    </ConfirmsPassword>

                    <ConfirmsPassword
                        title="¿Desactivar la verificación en dos pasos?"
                        content="Tu cuenta quedará protegida solo con la contraseña. Escríbela para confirmar."
                        button="Desactivar verificación"
                        @confirmed="disableTwoFactorAuthentication"
                    >
                        <DangerButton v-if="! confirming" :procesando="disabling">
                            {{ disabling ? 'Desactivando…' : 'Desactivar verificación' }}
                        </DangerButton>
                    </ConfirmsPassword>
                </div>
            </div>
        </template>
    </ActionSection>
</template>
