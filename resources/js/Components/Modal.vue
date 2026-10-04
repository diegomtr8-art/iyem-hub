<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

const props = defineProps({
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

const emit = defineEmits(['close']);
const dialog = ref();
const showSlot = ref(props.show);

watch(() => props.show, () => {
    if (props.show) {
        document.body.style.overflow = 'hidden';
        showSlot.value = true;
        dialog.value?.showModal();
    } else {
        document.body.style.overflow = null;
        setTimeout(() => {
            dialog.value?.close();
            showSlot.value = false;
        }, 200);
    }
});

const close = () => {
    if (props.closeable) {
        emit('close');
    }
};

const closeOnEscape = (e) => {
    if (e.key === 'Escape') {
        e.preventDefault();

        if (props.show) {
            close();
        }
    }
};

onMounted(() => document.addEventListener('keydown', closeOnEscape));

onUnmounted(() => {
    document.removeEventListener('keydown', closeOnEscape);
    document.body.style.overflow = null;
});

const maxWidthClass = computed(() => {
    return {
        'sm': 'sm:max-w-sm',
        'md': 'sm:max-w-md',
        'lg': 'sm:max-w-lg',
        'xl': 'sm:max-w-xl',
        '2xl': 'sm:max-w-2xl',
    }[props.maxWidth];
});
</script>

<template>
    <!--
        <dialog> nativo con showModal(): el navegador atrapa el foco, Escape
        cierra y al cerrar el foco vuelve al control que lo abrió.
        En teléfono el panel ocupa la pantalla completa; en escritorio va
        centrado con su ancho máximo.
    -->
    <dialog class="z-50 m-0 min-h-full min-w-full overflow-y-auto bg-transparent backdrop:bg-transparent" ref="dialog">
        <div class="fixed inset-0 z-50 overflow-y-auto" scroll-region>
            <transition
                enter-active-class="ease-out duration-200"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="ease-in duration-150"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-show="show" class="fixed inset-0 transform transition-all" @click="close">
                    <div class="absolute inset-0 bg-brand-900/60" />
                </div>
            </transition>

            <div class="pointer-events-none relative flex min-h-full items-stretch sm:items-start sm:justify-center sm:px-4 sm:py-10">
                <transition
                    enter-active-class="ease-out duration-200"
                    enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    enter-to-class="opacity-100 translate-y-0 sm:scale-100"
                    leave-active-class="ease-in duration-150"
                    leave-from-class="opacity-100 translate-y-0 sm:scale-100"
                    leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                >
                    <!--
                        max-h en `dvh` y no en `vh`: Safari en iOS mide `vh`
                        contra la ventana con la barra de direcciones oculta, y
                        el modal termina más alto que la pantalla, con el botón
                        de guardar fuera de vista.
                    -->
                    <div
                        v-show="show"
                        role="document"
                        class="pad-seguro-arriba pad-seguro-abajo pad-seguro-lados scrollbar-fina scroll-suave-ios pointer-events-auto flex h-[100dvh] w-full transform flex-col overflow-y-auto overscroll-contain bg-surface shadow-lg transition-all sm:h-auto sm:max-h-[85dvh] sm:rounded-xl sm:border sm:border-line"
                        :class="maxWidthClass"
                    >
                        <slot v-if="showSlot" />
                    </div>
                </transition>
            </div>
        </div>
    </dialog>
</template>
