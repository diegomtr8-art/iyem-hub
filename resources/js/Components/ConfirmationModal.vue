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
            <div class="sm:flex sm:items-start sm:gap-4">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-danger-surface">
                    <svg class="h-5 w-5 text-danger" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                    </svg>
                </div>

                <div class="mt-4 sm:mt-0">
                    <h2 class="text-display-md text-ink">
                        <slot name="title" />
                    </h2>

                    <div class="mt-3 text-body text-ink-600">
                        <slot name="content" />
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-auto flex flex-wrap justify-end gap-3 border-t border-line bg-surface-50 px-6 py-4">
            <slot name="footer" />
        </div>
    </Modal>
</template>
