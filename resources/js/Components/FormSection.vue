<script setup>
import { computed, useSlots } from 'vue';
import SectionTitle from './SectionTitle.vue';

defineEmits(['submitted']);

const hasActions = computed(() => !! useSlots().actions);
</script>

<template>
    <div class="md:grid md:grid-cols-3 md:gap-6">
        <SectionTitle>
            <template #title>
                <slot name="title" />
            </template>
            <template #description>
                <slot name="description" />
            </template>
        </SectionTitle>

        <div class="mt-5 md:mt-0 md:col-span-2">
            <form @submit.prevent="$emit('submitted')">
                <div
                    class="border border-iyem-claro bg-white px-4 py-5 shadow-soft sm:p-6"
                    :class="hasActions ? 'sm:rounded-t-2xl' : 'sm:rounded-2xl'"
                >
                    <div class="grid grid-cols-6 gap-6">
                        <slot name="form" />
                    </div>
                </div>

                <div v-if="hasActions" class="flex items-center justify-end border border-t-0 border-iyem-claro bg-iyem-50 px-4 py-3 text-end shadow-soft sm:rounded-b-2xl sm:px-6">
                    <slot name="actions" />
                </div>
            </form>
        </div>
    </div>
</template>
