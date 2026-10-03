<script setup>
import { ref, watchEffect } from 'vue';
import { usePage } from '@inertiajs/vue3';
import Aviso from '@/Components/Aviso.vue';

const page = usePage();
const show = ref(true);
const style = ref('success');
const message = ref('');

watchEffect(async () => {
    style.value = page.props.jetstream.flash?.bannerStyle || 'success';
    message.value = page.props.jetstream.flash?.banner || '';
    show.value = true;
});
</script>

<template>
    <div v-if="show && message" class="px-4 py-2 sm:px-6">
        <Aviso :tipo="style === 'danger' ? 'error' : 'exito'">
            <div class="flex items-start justify-between gap-3">
                <p>{{ message }}</p>
                <button
                    type="button"
                    class="toque-minimo -my-2 -mr-2 flex shrink-0 items-center justify-center rounded-md text-ink-600 hover:text-ink focus:outline-none focus-visible:ring-2 focus-visible:ring-focus"
                    aria-label="Cerrar el aviso"
                    @click.prevent="show = false"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </Aviso>
    </div>
</template>
