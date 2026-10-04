<script setup>
import ApiTokenManager from '@/Pages/API/Partials/ApiTokenManager.vue';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
    tokens: Array,
    availablePermissions: Array,
    defaultPermissions: Array,
});
</script>

<template>
    <AppLayout title="Tokens de API">
        <template #header>
            <span>Tokens de API</span>
        </template>

        <div class="mx-auto max-w-7xl">
            <h1 class="text-display-lg text-ink">Tokens de API</h1>
            <p class="mt-1 text-body text-ink-600">Credenciales para que otros sistemas consulten la plataforma en tu nombre.</p>

            <div class="mt-8">
                <ApiTokenManager
                    :tokens="tokens"
                    :available-permissions="availablePermissions"
                    :default-permissions="defaultPermissions"
                />
            </div>
        </div>
    </AppLayout>
</template>
