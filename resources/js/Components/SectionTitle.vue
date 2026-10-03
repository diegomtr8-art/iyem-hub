<template>
    <div class="flex justify-between md:col-span-1">
        <div>
            <h3 class="text-title text-ink">
                <slot name="title" />
            </h3>

            <p class="mt-1 text-small text-ink-600">
                <slot name="description" />
            </p>
        </div>

        <div>
            <slot name="aside" />
        </div>
    </div>
</template>
