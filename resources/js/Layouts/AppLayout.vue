<script setup>
import { computed, ref, watch } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import IconoNav from '@/Components/IconoNav.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import BuscadorGlobal from '@/Components/BuscadorGlobal.vue';
import InterruptorTema from '@/Components/InterruptorTema.vue';
import MarcaErp from '@/Components/MarcaErp.vue';

defineProps({
    title: String,
});

const page = usePage();
const user = () => page.props.auth.user;

const sidebarOpen = ref(false);
const modulosAbiertos = ref(true);
const buscador = ref(null);

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
    }

    // route().has() ademas del permiso: el modulo de consultas puede estar
    // habilitado para el rol antes de que su ruta exista en el despliegue.
    if (puede('ver-consultas') && route().has('consultas.index')) {
        destinos.push({
            clave: 'consultas',
            texto: 'Consultas',
            icono: 'chart',
            href: route('consultas.index'),
            activo: esActivo('consultas'),
        });
    }

    if (puede('ver-padron')) {
        destinos.push({
            clave: 'buscar',
            texto: 'Buscar',
            icono: 'buscar',
            accion: () => buscador.value?.abrir(),
            activo: false,
        });
    }

    destinos.push({
        clave: 'perfil',
        texto: 'Perfil',
        icono: 'user',
        href: route('perfil'),
        activo: esActivo('profile'),
    });

    return destinos.slice(0, 5);
});

// Ítems del sidebar. El activo lleva fondo plano brand-600: sin degradado
// ni resplandor.
const claseItem = (activo) => [
    'flex min-h-[44px] items-center gap-3 rounded-md px-3 text-body transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-focus focus-visible:ring-offset-2 focus-visible:ring-offset-brand-900',
    activo ? 'bg-brand-600 font-semibold text-white' : 'text-ink-600 hover:bg-white/5 hover:text-ink',
];
</script>

<template>
    <div class="min-h-screen bg-surface-50">
        <Head :title="title" />

        <!-- Telón del cajón en móvil -->
        <div
            v-show="sidebarOpen"
            class="fixed inset-0 z-30 bg-brand-900/60 lg:hidden"
            aria-hidden="true"
            @click="sidebarOpen = false"
        />

        <!--
            Sidebar. Lleva data-theme="dark": sobre brand-900 rigen los tokens
            del tema oscuro (tinta clara, foco índigo claro) en los dos temas.
        -->
        <aside
            id="menu-principal"
            data-theme="dark"
            class="pad-seguro-abajo fixed inset-y-0 left-0 z-40 flex w-72 max-w-[85vw] flex-col border-r border-line bg-brand-900 text-ink transition-transform duration-200 lg:translate-x-0"
            :class="sidebarOpen ? 'translate-x-0 shadow-lg' : '-translate-x-full'"
            aria-label="Menú principal"
        >
            <div class="pad-seguro-arriba shrink-0 border-b border-white/10">
                <div class="flex h-16 items-center justify-between gap-3 px-6">
                    <Link
                        :href="route('dashboard')"
                        class="rounded-sm focus:outline-none focus-visible:ring-2 focus-visible:ring-focus focus-visible:ring-offset-2 focus-visible:ring-offset-brand-900"
                        aria-label="IYEM ERP, ir al tablero"
                    >
                        <MarcaErp tamano="sm" sobre-oscuro />
                    </Link>
                    <button
                        type="button"
                        class="toque-minimo -mr-3 flex items-center justify-center rounded-md text-ink-600 transition-colors hover:text-ink focus:outline-none focus-visible:ring-2 focus-visible:ring-focus lg:hidden"
                        aria-label="Cerrar el menú"
                        @click="sidebarOpen = false"
                    >
                        <IconoNav icono="close" class="h-5 w-5" />
                    </button>
                </div>
            </div>

            <div class="flex items-center gap-3 border-b border-white/10 px-6 py-4">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-brand-600 text-small font-semibold text-white" aria-hidden="true">
                    {{ iniciales() }}
                </div>
                <div class="min-w-0">
                    <p class="truncate text-body-strong text-ink">
                        {{ user().nombre_completo ?? user().name }}
                    </p>
                    <p class="truncate text-caption text-ink-600">
                        {{ user().rol_actual ?? 'Sin rol asignado' }}
                    </p>
                </div>
            </div>

            <nav class="scrollbar-fina scroll-suave-ios flex-1 overflow-y-auto px-4 py-5" aria-label="Secciones">
                <p class="px-3 pb-2 text-overline text-ink-600">General</p>
                <div class="space-y-1">
                    <Link :href="route('dashboard')" :class="claseItem(esActivo('dashboard'))" :aria-current="esActivo('dashboard') ? 'page' : undefined">
                        <IconoNav icono="grid" />
                        Tablero
                    </Link>
                    <Link
                        v-if="user().es_super_admin"
                        :href="route('admin.index')"
                        :class="claseItem(esActivo('admin'))"
                        :aria-current="esActivo('admin') ? 'page' : undefined"
                    >
                        <IconoNav icono="shield" />
                        Administración
                    </Link>
                </div>

                <div v-if="page.props.modulosSidebar.length" class="mt-6">
                    <button
                        type="button"
                        class="flex min-h-[44px] w-full items-center justify-between rounded-md px-3 text-overline text-ink-600 transition-colors hover:text-ink focus:outline-none focus-visible:ring-2 focus-visible:ring-focus focus-visible:ring-offset-2 focus-visible:ring-offset-brand-900"
                        :aria-expanded="modulosAbiertos ? 'true' : 'false'"
                        aria-controls="lista-modulos"
                        @click="modulosAbiertos = !modulosAbiertos"
                    >
                        Módulos
                        <IconoNav icono="chevron" class="h-4 w-4 transition-transform" :class="modulosAbiertos ? 'rotate-180' : ''" />
                    </button>
                    <div v-show="modulosAbiertos" id="lista-modulos" class="mt-1 space-y-1">
                        <Link
                            v-for="modulo in page.props.modulosSidebar"
                            :key="modulo.slug"
                            :href="modulo.url"
                            :class="claseItem(false)"
                        >
                            <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-brand-300" aria-hidden="true" />
                            {{ modulo.nombre }}
                        </Link>
                    </div>
                </div>

                <p class="mt-6 px-3 pb-2 text-overline text-ink-600">Cuenta</p>
                <Link :href="route('perfil')" :class="claseItem(esActivo('profile'))" :aria-current="esActivo('profile') ? 'page' : undefined">
                    <IconoNav icono="user" />
                    Mi perfil
                </Link>
            </nav>

            <div class="border-t border-white/10 p-4">
                <form @submit.prevent="logout">
                    <button type="submit" :class="[claseItem(false), 'w-full text-left']">
                        <IconoNav icono="logout" />
                        Cerrar sesión
                    </button>
                </form>
            </div>
        </aside>

        <div class="lg:pl-72">
            <!-- Barra superior: fondo opaco para que el contenido no se lea
                 por debajo al hacer scroll (sobre todo en oscuro). -->
            <header class="pad-seguro-arriba pad-seguro-lados sticky top-0 z-20 border-b border-line bg-surface">
                <div class="flex h-16 items-center justify-between gap-3 px-4 sm:px-6">
                    <div class="flex min-w-0 items-center gap-2">
                        <button
                            type="button"
                            class="toque-minimo -ml-2 flex items-center justify-center rounded-md text-ink-600 transition-colors hover:text-ink focus:outline-none focus-visible:ring-2 focus-visible:ring-focus lg:hidden"
                            aria-label="Abrir el menú"
                            aria-controls="menu-principal"
                            :aria-expanded="sidebarOpen ? 'true' : 'false'"
                            @click="sidebarOpen = true"
                        >
                            <IconoNav icono="menu" class="h-6 w-6" />
                        </button>

                        <div v-if="$slots.header" class="encabezado-pagina min-w-0 truncate text-subtitle text-ink">
                            <slot name="header" />
                        </div>
                    </div>

                    <div class="flex shrink-0 items-center gap-1 sm:gap-2">
                        <BuscadorGlobal ref="buscador" />

                        <InterruptorTema />

                        <Dropdown align="right" width="48">
                            <template #trigger>
                                <button
                                    type="button"
                                    class="toque-minimo flex items-center justify-end gap-2 rounded-md px-1 text-body text-ink transition-colors hover:bg-surface-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-focus focus-visible:ring-offset-2"
                                    aria-label="Menú de la cuenta"
                                >
                                    <img
                                        v-if="page.props.jetstream.managesProfilePhotos"
                                        class="h-9 w-9 rounded-full object-cover"
                                        :src="user().profile_photo_url"
                                        :alt="user().name"
                                    >
                                    <span v-else class="flex h-8 w-8 items-center justify-center rounded-full bg-brand-50 text-caption font-semibold text-brand-700 dark:bg-surface-brand dark:text-brand-300" aria-hidden="true">
                                        {{ iniciales() }}
                                    </span>
                                    <span class="hidden font-medium md:block">{{ user().name }}</span>
                                </button>
                            </template>

                            <template #content>
                                <DropdownLink :href="route('perfil')">
                                    Mi perfil
                                </DropdownLink>
                                <form @submit.prevent="logout">
                                    <DropdownLink as="button">
                                        Cerrar sesión
                                    </DropdownLink>
                                </form>
                            </template>
                        </Dropdown>
                    </div>
                </div>
            </header>

            <!--
                Aviso permanente del modo de pruebas. No se puede cerrar: la
                idea es que quien demuestra la plataforma nunca confunda un
                dato ficticio con uno real.
            -->
            <div
                v-if="user().es_tester"
                role="status"
                class="pad-seguro-lados border-b border-line bg-warning-surface"
            >
                <div class="flex items-start gap-3 px-4 py-3 text-small text-ink sm:px-6">
                    <IconoNav icono="alerta" class="h-5 w-5 shrink-0 text-warning" />
                    <p>
                        <span class="font-semibold">Estás en modo de pruebas.</span>
                        Los datos que ves son de demostración y algunos campos están enmascarados.
                    </p>
                </div>
            </div>

            <!-- El padding inferior extra en móvil reserva el alto de la barra
                 de navegación fija más el área segura del iPhone. El área
                 segura lateral va aquí y el gutter en el hijo, para que las dos
                 reglas de padding no se pisen. -->
            <main class="pad-seguro-lados espacio-barra-inferior sm:pb-0">
                <div class="p-4 sm:p-6 lg:p-8">
                    <slot />
                </div>
            </main>
        </div>

        <!-- ============================================================
             Barra de navegación inferior (solo teléfono)
             ============================================================ -->
        <nav
            class="pad-seguro-abajo pad-seguro-lados fixed inset-x-0 bottom-0 z-20 border-t border-line bg-surface sm:hidden"
            aria-label="Navegación principal"
        >
            <div class="flex items-stretch justify-around">
                <component
                    :is="destino.href ? Link : 'button'"
                    v-for="destino in destinosInferiores"
                    :key="destino.clave"
                    :href="destino.href"
                    :type="destino.href ? undefined : 'button'"
                    class="toque-minimo flex flex-1 flex-col items-center justify-center gap-0.5 py-2 text-caption transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-focus"
                    :class="destino.activo ? 'font-semibold text-action' : 'text-ink-600'"
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
