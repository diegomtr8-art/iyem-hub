<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import DeleteUserForm from '@/Pages/Profile/Partials/DeleteUserForm.vue';
import LogoutOtherBrowserSessionsForm from '@/Pages/Profile/Partials/LogoutOtherBrowserSessionsForm.vue';
import SectionBorder from '@/Components/SectionBorder.vue';
import TwoFactorAuthenticationForm from '@/Pages/Profile/Partials/TwoFactorAuthenticationForm.vue';
import UpdatePasswordForm from '@/Pages/Profile/Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from '@/Pages/Profile/Partials/UpdateProfileInformationForm.vue';

defineProps({
    confirmsTwoFactorAuthentication: Boolean,
    sessions: Array,
});
</script>

<template>
    <AppLayout title="Mi perfil">
        <template #header>
            <span>Mi perfil</span>
        </template>

        <div class="mx-auto max-w-7xl">
            <h1 class="text-display-lg text-ink">Mi perfil</h1>
            <p class="mt-1 text-body text-ink-600">Tus datos, tu contraseña y la seguridad de tu cuenta.</p>

            <div class="mt-8 space-y-10 sm:space-y-0">
                <div v-if="$page.props.jetstream.canUpdateProfileInformation">
                    <UpdateProfileInformationForm :user="$page.props.auth.user" />

                    <SectionBorder />
                </div>

                <div v-if="$page.props.jetstream.canUpdatePassword">
                    <UpdatePasswordForm />

                    <SectionBorder />
                </div>

                <div v-if="$page.props.jetstream.canManageTwoFactorAuthentication">
                    <TwoFactorAuthenticationForm :requires-confirmation="confirmsTwoFactorAuthentication" />

                    <SectionBorder />
                </div>

                <LogoutOtherBrowserSessionsForm :sessions="sessions" />

                <template v-if="$page.props.jetstream.hasAccountDeletionFeatures">
                    <SectionBorder />

                    <DeleteUserForm />
                </template>
            </div>
        </div>
    </AppLayout>
</template>
