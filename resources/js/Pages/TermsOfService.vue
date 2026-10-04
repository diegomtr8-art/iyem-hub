<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticationCardLogo from '@/Components/AuthenticationCardLogo.vue';

defineProps({
    terms: String,
});
</script>

<template>
    <Head title="Términos de uso" />

    <div class="pad-seguro-arriba pad-seguro-abajo pad-seguro-lados min-h-screen bg-surface-50">
        <div class="mx-auto max-w-3xl px-4 py-10 sm:px-6">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <AuthenticationCardLogo />
                <Link
                    :href="route('inicio')"
                    class="inline-flex min-h-[44px] items-center rounded-sm text-small text-action underline-offset-2 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-focus focus-visible:ring-offset-2"
                >
                    ← Volver al inicio
                </Link>
            </div>

            <!-- El texto trae su propio título (h1) desde resources/markdown. -->
            <article
                class="prose mt-8 max-w-none rounded-lg border border-line bg-surface p-5 shadow-sm sm:p-8 prose-headings:font-display prose-headings:text-ink prose-p:text-ink-600 prose-li:text-ink-600 prose-strong:text-ink prose-a:text-action prose-code:text-ink prose-hr:border-line"
                v-html="terms"
            />
        </div>
    </div>
</template>
