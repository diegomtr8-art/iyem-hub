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

        <div class="mt-4 md:col-span-2 md:mt-0">
            <form class="overflow-hidden rounded-lg border border-line bg-surface shadow-sm" @submit.prevent="$emit('submitted')">
                <div class="p-5 sm:p-6">
                    <div class="grid grid-cols-6 gap-5">
                        <slot name="form" />
                    </div>
                </div>

                <div v-if="hasActions" class="flex flex-wrap items-center justify-end gap-3 border-t border-line bg-surface-50 px-5 py-3 sm:px-6">
                    <slot name="actions" />
                </div>
            </form>
        </div>
    </div>
</template>
