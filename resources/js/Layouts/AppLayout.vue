<script setup>
import { computed, ref, watch } from 'vue';
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

// Al navegar, el cajón debe cerrarse solo. Si no, en móvil se llega a la
// página nueva con el menú encima tapándola.
watch(() => page.url, () => {
    sidebarOpen.value = false;
});

const logout = () => {
    router.post(route('logout'));
};

const esActivo = (nombreRuta) => route().current(nombreRuta) || route().current(`${nombreRuta}.*`);

const iniciales = () => {
    const n = user().name?.[0] ?? '';
    const a = user().apellido?.[0] ?? '';
    return (n + a).toUpperCase() || 'IY';
};

/**
 * Barra inferior de navegación (solo en pantallas de teléfono).
 *
 * Cinco destinos como máximo: más de eso deja áreas táctiles por debajo de
 * los 44 px que exige Apple en un iPhone de 390 px de ancho.
 */
const puede = (permiso) => (page.props.permisos ?? []).includes(permiso);

const destinosInferiores = computed(() => {
    const destinos = [
        { clave: 'dashboard', texto: 'Inicio', icono: 'inicio', href: route('dashboard'), activo: esActivo('dashboard') },
    ];

    if (puede('ver-padron')) {
        destinos.push({
            clave: 'padron',
            texto: 'Padrón',
            icono: 'user',
            href: route('padron.index'),
            activo: esActivo('padron'),
        });
        destinos.push({
            clave: 'mapa',
            texto: 'Mapa',
            icono: 'mapa',
            href: route('padron.mapa'),
            activo: route().current('padron.mapa'),
        });
    }

    destinos.push({
        clave: 'menu',
        texto: 'Menú',
        icono: 'menu',
        accion: () => { sidebarOpen.value = true; },
        activo: false,
    });

    destinos.push({
        clave: 'perfil',
        texto: 'Perfil',
        icono: 'user',
        href: route('perfil'),
        activo: esActivo('profile'),
    });

    return destinos.slice(0, 5);
});
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
            class="pad-seguro-abajo fixed inset-y-0 left-0 z-40 flex w-72 max-w-[85vw] flex-col bg-tinta-gradient text-white shadow-soft-lg transition-transform duration-200 lg:translate-x-0"
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
        >
            <div class="flex h-16 shrink-0 items-center justify-between gap-3 border-b border-white/10 px-6">
                <Link :href="route('dashboard')">
                    <ApplicationLogo variant="blanco" />
                </Link>
                <button
                    type="button"
                    class="toque-minimo -mr-3 flex items-center justify-center text-white/60 transition hover:text-white lg:hidden"
                    aria-label="Cerrar el menú"
                    @click="sidebarOpen = false"
                >
                    <IconoNav icono="close" class="h-5 w-5" />
                </button>
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

            <nav class="scrollbar-fina scroll-suave-ios flex-1 space-y-1 overflow-y-auto px-4 py-5">
                <Link
                    :href="route('dashboard')"
                    class="group flex min-h-[44px] items-center gap-3 rounded-xl px-3.5 py-2.5 text-sm font-medium transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-iyem-secundario"
                    :class="esActivo('dashboard') ? 'bg-iyem-gradient text-white shadow-glow' : 'text-white/70 hover:bg-white/5 hover:text-white'"
                >
                    <IconoNav icono="grid" />
                    Dashboard
                </Link>

                <div v-if="page.props.modulosSidebar.length">
                    <button
                        type="button"
                        class="flex min-h-[44px] w-full items-center justify-between rounded-xl px-3.5 py-2.5 text-sm font-medium text-white/70 transition hover:bg-white/5 hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-iyem-secundario"
                        :aria-expanded="modulosAbiertos"
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
                            class="flex min-h-[40px] items-center rounded-lg px-3 py-2 text-sm text-white/60 transition hover:bg-white/5 hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-iyem-secundario"
                        >
                            {{ modulo.nombre }}
                        </Link>
                    </div>
                </div>

                <Link
                    v-if="user().es_super_admin"
                    :href="route('admin.index')"
                    class="flex min-h-[44px] items-center gap-3 rounded-xl px-3.5 py-2.5 text-sm font-medium transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-iyem-secundario"
                    :class="esActivo('admin') ? 'bg-iyem-gradient text-white shadow-glow' : 'text-white/70 hover:bg-white/5 hover:text-white'"
                >
                    <IconoNav icono="shield" />
                    Panel de Administración
                </Link>

                <Link
                    :href="route('perfil')"
                    class="flex min-h-[44px] items-center gap-3 rounded-xl px-3.5 py-2.5 text-sm font-medium text-white/70 transition hover:bg-white/5 hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-iyem-secundario"
                >
                    <IconoNav icono="user" />
                    Mi Perfil
                </Link>
            </nav>

            <div class="border-t border-white/10 p-4">
                <form @submit.prevent="logout">
                    <button type="submit" class="flex min-h-[44px] w-full items-center gap-3 rounded-xl px-3.5 py-2.5 text-left text-sm font-medium text-white/60 transition hover:bg-white/5 hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-iyem-secundario">
                        <IconoNav icono="logout" />
                        Cerrar Sesión
                    </button>
                </form>
            </div>
        </aside>

        <div class="lg:pl-72">
            <header class="pad-seguro-lados sticky top-0 z-20 flex h-16 items-center justify-between border-b border-iyem-claro/80 bg-white/85 px-4 backdrop-blur-md sm:px-6">
                <div class="flex min-w-0 items-center gap-2">
                    <button
                        type="button"
                        class="toque-minimo -ml-2 flex items-center justify-center text-gray-500 transition hover:text-gray-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-iyem-secundario lg:hidden"
                        aria-label="Abrir el menú"
                        @click="sidebarOpen = true"
                    >
                        <IconoNav icono="menu" class="h-6 w-6" />
                    </button>

                    <div v-if="$slots.header" class="min-w-0 truncate text-lg font-semibold text-gray-800">
                        <slot name="header" />
                    </div>
                </div>

                <Dropdown align="right" width="48">
                    <template #trigger>
                        <button type="button" class="toque-minimo flex items-center justify-end gap-2 rounded-full text-sm transition hover:opacity-80 focus:outline-none focus-visible:ring-2 focus-visible:ring-iyem-secundario">
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

            <!--
                Aviso permanente del modo de pruebas. No se puede cerrar: la
                idea es que quien demuestra la plataforma nunca confunda un
                dato ficticio con uno real.
            -->
            <div
                v-if="user().es_tester"
                role="status"
                class="flex items-start gap-3 border-b border-iyem-dorado/40 bg-iyem-dorado/10 px-4 py-3 text-sm text-iyem-800 sm:px-6"
            >
                <IconoNav icono="alerta" class="mt-0.5 h-5 w-5 shrink-0 text-iyem-alerta" />
                <p>
                    <span class="font-semibold">Estás en modo de pruebas.</span>
                    Los datos que ves son de demostración y algunos campos están enmascarados.
                </p>
            </div>

            <!-- El padding inferior extra en móvil reserva el alto de la barra
                 de navegación fija más el área segura del iPhone. -->
            <main class="pad-seguro-lados espacio-barra-inferior p-4 sm:p-6 sm:pb-6 lg:p-8">
                <slot />
            </main>
        </div>

        <!-- ============================================================
             Barra de navegación inferior (solo teléfono)
             ============================================================ -->
        <nav
            class="pad-seguro-abajo fixed inset-x-0 bottom-0 z-20 border-t border-iyem-200 bg-white/95 backdrop-blur-md sm:hidden"
            aria-label="Navegación principal"
        >
            <div class="flex items-stretch justify-around">
                <component
                    :is="destino.href ? Link : 'button'"
                    v-for="destino in destinosInferiores"
                    :key="destino.clave"
                    :href="destino.href"
                    :type="destino.href ? undefined : 'button'"
                    class="toque-minimo flex flex-1 flex-col items-center justify-center gap-0.5 py-2 text-[0.65rem] font-medium transition-colors duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-iyem-secundario"
                    :class="destino.activo ? 'text-iyem-primario' : 'text-gray-500'"
                    :aria-current="destino.activo ? 'page' : undefined"
                    @click="destino.accion?.()"
                >
                    <IconoNav :icono="destino.icono" class="h-5 w-5" />
                    {{ destino.texto }}
                </component>
            </div>
        </nav>
    </div>
</template>
