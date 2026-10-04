<script setup>
import Modal from './Modal.vue';

const emit = defineEmits(['close']);

defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    maxWidth: {
        type: String,
        default: '2xl',
    },
    closeable: {
        type: Boolean,
        default: true,
    },
});

const close = () => {
    emit('close');
};
</script>

<template>
    <Modal
        :show="show"
        :max-width="maxWidth"
        :closeable="closeable"
        @close="close"
    >
        <div class="px-6 py-5">
            <h2 class="text-display-md text-ink">
                <slot name="title" />
            </h2>

            <div class="mt-4 text-body text-ink-600">
                <slot name="content" />
            </div>
        </div>

        <div class="mt-auto flex flex-wrap justify-end gap-3 border-t border-line bg-surface-50 px-6 py-4">
            <slot name="footer" />
        </div>
    </Modal>
</template>
