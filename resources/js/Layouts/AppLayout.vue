<script setup>
import { ref } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import IconoNav from '@/Components/IconoNav.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';

defineProps({
    title: String,
});

const page = usePage();
const user = () => page.props.auth.user;

const sidebarOpen = ref(false);
const modulosAbiertos = ref(true);

const logout = () => {
    router.post(route('logout'));
};

const esActivo = (nombreRuta) => route().current(nombreRuta) || route().current(`${nombreRuta}.*`);

const iniciales = () => {
    const n = user().name?.[0] ?? '';
    const a = user().apellido?.[0] ?? '';
    return (n + a).toUpperCase() || 'IY';
};
</script>

<template>
    <div class="min-h-screen bg-iyem-neutro">
        <Head :title="title" />

        <!-- Overlay móvil -->
        <div
            v-show="sidebarOpen"
            class="fixed inset-0 z-30 bg-tinta-950/60 backdrop-blur-sm lg:hidden"
            @click="sidebarOpen = false"
        />

        <!-- Sidebar -->
        <aside
            class="fixed inset-y-0 left-0 z-40 flex w-72 flex-col bg-tinta-gradient text-white shadow-soft-lg transition-transform lg:translate-x-0"
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
        >
            <div class="flex h-16 shrink-0 items-center gap-3 border-b border-white/10 px-6">
                <Link :href="route('dashboard')">
                    <ApplicationLogo variant="blanco" />
                </Link>
            </div>

            <div class="flex items-center gap-3 border-b border-white/10 px-6 py-5">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-iyem-gradient text-sm font-bold shadow-glow">
                    {{ iniciales() }}
                </div>
                <div class="min-w-0">
                    <p class="truncate text-sm font-semibold text-white">
                        {{ user().nombre_completo ?? user().name }}
                    </p>
                    <p class="mt-0.5 inline-flex items-center rounded-full bg-white/10 px-2 py-0.5 text-[0.65rem] font-semibold uppercase tracking-wide text-iyem-300">
                        {{ user().rol_actual ?? 'Sin rol asignado' }}
                    </p>
                </div>
            </div>

            <nav class="scrollbar-fina flex-1 space-y-1 overflow-y-auto px-4 py-5">
                <Link
                    :href="route('dashboard')"
                    class="group flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-sm font-medium transition"
                    :class="esActivo('dashboard') ? 'bg-iyem-gradient text-white shadow-glow' : 'text-white/70 hover:bg-white/5 hover:text-white'"
                >
                    <IconoNav icono="grid" />
                    Dashboard
                </Link>

                <div v-if="page.props.modulosSidebar.length">
                    <button
                        type="button"
                        class="flex w-full items-center justify-between rounded-xl px-3.5 py-2.5 text-sm font-medium text-white/70 transition hover:bg-white/5 hover:text-white"
                        @click="modulosAbiertos = !modulosAbiertos"
                    >
                        <span class="flex items-center gap-3">
                            <IconoNav icono="stack" />
                            Módulos
                        </span>
                        <IconoNav icono="chevron" class="h-4 w-4 transition-transform" :class="modulosAbiertos ? 'rotate-180' : ''" />
                    </button>
                    <div v-show="modulosAbiertos" class="mt-1 space-y-0.5 border-l border-white/10 pl-5">
                        <Link
                            v-for="modulo in page.props.modulosSidebar"
                            :key="modulo.slug"
                            :href="modulo.url"
                            class="block rounded-lg px-3 py-2 text-sm text-white/60 transition hover:bg-white/5 hover:text-white"
                        >
                            {{ modulo.nombre }}
                        </Link>
                    </div>
                </div>

                <Link
                    v-if="user().es_super_admin"
                    :href="route('admin.index')"
                    class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-sm font-medium transition"
                    :class="esActivo('admin') ? 'bg-iyem-gradient text-white shadow-glow' : 'text-white/70 hover:bg-white/5 hover:text-white'"
                >
                    <IconoNav icono="shield" />
                    Panel de Administración
                </Link>

                <Link
                    :href="route('perfil')"
                    class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-sm font-medium text-white/70 transition hover:bg-white/5 hover:text-white"
                >
                    <IconoNav icono="user" />
                    Mi Perfil
                </Link>
            </nav>

            <div class="border-t border-white/10 p-4">
                <form @submit.prevent="logout">
                    <button type="submit" class="flex w-full items-center gap-3 rounded-xl px-3.5 py-2.5 text-left text-sm font-medium text-white/60 transition hover:bg-white/5 hover:text-white">
                        <IconoNav icono="logout" />
                        Cerrar Sesión
                    </button>
                </form>
            </div>
        </aside>

        <div class="lg:pl-72">
            <header class="sticky top-0 z-20 flex h-16 items-center justify-between border-b border-iyem-claro/80 bg-white/80 px-4 backdrop-blur-md sm:px-6">
                <div class="flex items-center gap-3">
                    <button type="button" class="text-gray-500 lg:hidden" @click="sidebarOpen = true">
                        <IconoNav icono="menu" class="h-6 w-6" />
                    </button>

                    <div v-if="$slots.header" class="text-lg font-semibold text-gray-800">
                        <slot name="header" />
                    </div>
                </div>

                <Dropdown align="right" width="48">
                    <template #trigger>
                        <button type="button" class="flex items-center gap-2 rounded-full text-sm transition hover:opacity-80 focus:outline-none">
                            <img
                                v-if="page.props.jetstream.managesProfilePhotos"
                                class="h-9 w-9 rounded-full object-cover ring-2 ring-iyem-100"
                                :src="user().profile_photo_url"
                                :alt="user().name"
                            >
                            <span class="hidden font-medium text-gray-700 sm:block">{{ user().name }}</span>
                        </button>
                    </template>

                    <template #content>
                        <DropdownLink :href="route('perfil')">
                            Mi Perfil
                        </DropdownLink>
                        <form @submit.prevent="logout">
                            <DropdownLink as="button">
                                Cerrar Sesión
                            </DropdownLink>
                        </form>
                    </template>
                </Dropdown>
            </header>

            <main class="p-4 sm:p-6 lg:p-8">
                <slot />
            </main>
        </div>
    </div>
</template>
