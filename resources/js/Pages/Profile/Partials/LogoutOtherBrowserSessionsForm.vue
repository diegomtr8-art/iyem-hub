<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import ActionMessage from '@/Components/ActionMessage.vue';
import ActionSection from '@/Components/ActionSection.vue';
import DialogModal from '@/Components/DialogModal.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

defineProps({
    sessions: Array,
});

const confirmingLogout = ref(false);
const passwordInput = ref(null);

const form = useForm({
    password: '',
});

const confirmLogout = () => {
    confirmingLogout.value = true;

    setTimeout(() => passwordInput.value.focus(), 250);
};

const logoutOtherBrowserSessions = () => {
    form.delete(route('other-browser-sessions.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value.focus(),
        onFinish: () => form.reset(),
    });
};

const closeModal = () => {
    confirmingLogout.value = false;

    form.reset();
};
</script>

<template>
    <ActionSection>
        <template #title>
            Sesiones abiertas
        </template>

        <template #description>
            Revisa dónde tienes la sesión iniciada y cierra las de otros navegadores y dispositivos.
        </template>

        <template #content>
            <p class="max-w-xl text-body text-ink-600">
                Puedes cerrar la sesión en todos tus otros navegadores y dispositivos. La lista muestra tus sesiones recientes y puede no estar completa. Si crees que alguien más entró a tu cuenta, cambia también tu contraseña.
            </p>

            <ul v-if="sessions.length > 0" class="mt-5 divide-y divide-line rounded-lg border border-line">
                <li v-for="(session, i) in sessions" :key="i" class="flex items-center gap-3 px-4 py-3">
                    <svg v-if="session.agent.is_desktop" class="h-7 w-7 shrink-0 text-ink-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0V12a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 12V5.25" />
                    </svg>
                    <svg v-else class="h-7 w-7 shrink-0 text-ink-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3" />
                    </svg>

                    <div class="min-w-0">
                        <p class="text-body text-ink">
                            {{ session.agent.platform ? session.agent.platform : 'Sistema desconocido' }} · {{ session.agent.browser ? session.agent.browser : 'Navegador desconocido' }}
                        </p>
                        <p class="text-small text-ink-600">
                            <span class="font-mono">{{ session.ip_address }}</span> ·
                            <span v-if="session.is_current_device" class="inline-flex items-center gap-1 font-semibold text-success">
                                <span class="h-1.5 w-1.5 rounded-full bg-success" aria-hidden="true" />
                                Este dispositivo
                            </span>
                            <span v-else>Última actividad {{ session.last_active }}</span>
                        </p>
                    </div>
                </li>
            </ul>

            <div class="mt-5 flex flex-wrap items-center gap-3">
                <SecondaryButton @click="confirmLogout">
                    Cerrar las otras sesiones
                </SecondaryButton>

                <ActionMessage :on="form.recentlySuccessful">
                    Sesiones cerradas.
                </ActionMessage>
            </div>

            <DialogModal :show="confirmingLogout" @close="closeModal">
                <template #title>
                    Cerrar las otras sesiones
                </template>

                <template #content>
                    <p>Escribe tu contraseña para cerrar la sesión en todos tus otros navegadores y dispositivos.</p>

                    <div class="mt-4">
                        <label for="sesiones-contrasena" class="block text-body-strong text-ink">Contraseña</label>
                        <TextInput
                            id="sesiones-contrasena"
                            ref="passwordInput"
                            v-model="form.password"
                            type="password"
                            class="mt-1.5 block w-full sm:w-3/4"
                            :invalido="Boolean(form.errors.password)"
                            :aria-describedby="form.errors.password ? 'sesiones-contrasena-error' : undefined"
                            autocomplete="current-password"
                            @keyup.enter="logoutOtherBrowserSessions"
                        />

                        <InputError id="sesiones-contrasena-error" :message="form.errors.password" class="mt-1.5" />
                    </div>
                </template>

                <template #footer>
                    <SecondaryButton @click="closeModal">
                        Cancelar
                    </SecondaryButton>

                    <PrimaryButton type="button" :procesando="form.processing" @click="logoutOtherBrowserSessions">
                        {{ form.processing ? 'Cerrando…' : 'Cerrar las otras sesiones' }}
                    </PrimaryButton>
                </template>
            </DialogModal>
        </template>
    </ActionSection>
</template>
