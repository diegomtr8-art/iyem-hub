<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import BadgeEstado from '@/Components/BadgeEstado.vue';
import IconoModulo from '@/Components/IconoModulo.vue';
import InterruptorTema from '@/Components/InterruptorTema.vue';
import MarcaErp from '@/Components/MarcaErp.vue';

/**
 * Pantalla de inicio del IYEM ERP.
 *
 * Es la puerta privada del personal del Instituto, no una página de
 * difusión: identidad, los módulos que existen y una sola acción, entrar.
 * Los módulos llegan sin URL ni conteos (ver routes/web.php).
 */
const props = defineProps({
    modulos: {
        type: Array,
        default: () => [],
    },
});

const anio = new Date().getFullYear();

// Los avisos legales solo se enlazan si la ruta existe: hoy la función de
// Jetstream está apagada y un enlace roto en el pie es peor que no tenerlo.
const tieneRuta = (nombre) => {
    try {
        return route().has(nombre);
    } catch {
        return false;
    }
};
const legales = computed(() => [
    { ruta: 'policy.show', texto: 'Aviso de privacidad' },
    { ruta: 'terms.show', texto: 'Términos de uso' },
].filter((enlace) => tieneRuta(enlace.ruta)));

const enOperacion = computed(() => props.modulos.filter((m) => m.estado === 'produccion').length);
</script>

<template>
    <Head title="Inicio" />

    <div class="flex min-h-screen flex-col bg-surface-50">
        <!--
            Identidad y acceso. El bloque lleva data-theme="dark" a propósito:
            sobre brand-900 rigen los tokens del tema oscuro (texto claro,
            primario índigo claro con tinta oscura), en los dos temas.
        -->
        <header data-theme="dark" class="pad-seguro-arriba pad-seguro-lados bg-brand-900 text-ink" aria-labelledby="titulo-inicio">
            <div class="mx-auto flex h-14 max-w-6xl items-center justify-between gap-4 px-4 sm:px-6">
                <p class="truncate text-small text-ink-600">Instituto Yucateco de Emprendedores</p>
                <InterruptorTema />
            </div>

            <div class="mx-auto max-w-6xl px-4 pb-12 pt-8 sm:px-6 sm:pb-16 sm:pt-12">
                <h1 id="titulo-inicio" class="text-display-xl">
                    <MarcaErp tamano="lg" sobre-oscuro />
                </h1>
                <p class="mt-4 max-w-xl text-body text-ink-600">
                    Plataforma de gestión del Instituto Yucateco de Emprendedores.
                </p>

                <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:items-center sm:gap-4">
                    <Link
                        :href="route('login')"
                        class="inline-flex h-11 items-center justify-center gap-2 rounded-md bg-action px-6 text-body-strong text-action-ink transition-colors hover:bg-action-hover focus:outline-none focus-visible:ring-2 focus-visible:ring-focus focus-visible:ring-offset-2 focus-visible:ring-offset-brand-900"
                    >
                        Entrar al sistema
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                        </svg>
                    </Link>
                    <p class="text-small text-ink-600">
                        Acceso exclusivo para el personal del Instituto.
                    </p>
                </div>
            </div>
        </header>

        <main class="pad-seguro-lados flex-1">
            <!-- Vitrina de módulos -->
            <section class="mx-auto max-w-6xl px-4 py-10 sm:px-6" aria-labelledby="titulo-modulos">
                <div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1">
                    <h2 id="titulo-modulos" class="text-title text-ink">Módulos del ecosistema</h2>
                    <p class="text-small text-ink-600">
                        <span class="font-mono">{{ enOperacion }}</span> de
                        <span class="font-mono">{{ modulos.length }}</span> en operación
                    </p>
                </div>

                <ul v-if="modulos.length" class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3" role="list">
                    <li
                        v-for="modulo in modulos"
                        :key="modulo.slug"
                        class="flex gap-4 rounded-lg border border-line bg-surface p-5 shadow-sm"
                    >
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-surface-brand text-brand-500 dark:text-brand-300">
                            <IconoModulo :icono="modulo.icono" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <h3 class="text-subtitle text-ink">{{ modulo.nombre }}</h3>
                                <BadgeEstado :estado="modulo.estado" />
                            </div>
                            <p class="mt-1 text-small text-ink-600">{{ modulo.descripcion }}</p>
                        </div>
                    </li>
                </ul>
                <p v-else class="mt-6 rounded-lg border border-line bg-surface p-5 text-body text-ink-600">
                    El catálogo de módulos no está disponible en este momento. Puedes entrar al sistema con normalidad.
                </p>
            </section>
        </main>

        <footer class="pad-seguro-abajo pad-seguro-lados border-t border-line bg-surface">
            <div class="mx-auto flex max-w-6xl flex-col gap-3 px-4 py-6 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <p class="text-small text-ink-600">
                    Las cuentas las crea el administrador de la plataforma. Si necesitas acceso, solicítalo a tu área.
                </p>
                <nav v-if="legales.length" aria-label="Avisos legales" class="flex gap-4">
                    <a
                        v-for="enlace in legales"
                        :key="enlace.ruta"
                        :href="route(enlace.ruta)"
                        class="rounded-sm text-small text-action underline-offset-2 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-focus focus-visible:ring-offset-2"
                    >
                        {{ enlace.texto }}
                    </a>
                </nav>
            </div>
            <p class="mx-auto max-w-6xl px-4 pb-6 text-caption text-ink-600 sm:px-6">
                © <span class="font-mono">{{ anio }}</span> Instituto Yucateco de Emprendedores
            </p>
        </footer>
    </div>
</template>
