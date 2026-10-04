<script setup>
import { Link } from '@inertiajs/vue3';
import InterruptorTema from '@/Components/InterruptorTema.vue';
import MarcaErp from '@/Components/MarcaErp.vue';

/**
 * Marco de las seis pantallas de autenticación.
 *
 * Escritorio: panel de identidad brand-900 a la izquierda, formulario sobre
 * el lienzo a la derecha. Móvil: el panel se reduce a un encabezado corto y
 * el formulario ocupa el resto.
 *
 * El panel lleva data-theme="dark" para que, sobre brand-900, rijan los
 * tokens del tema oscuro en los dos temas (texto claro, foco índigo claro).
 *
 * El slot `logo` se conserva por compatibilidad con Jetstream, pero ya no se
 * pinta: la marca vive en el panel.
 */
defineProps({
    titulo: {
        type: String,
        default: null,
    },
    descripcion: {
        type: String,
        default: null,
    },
});

const anio = new Date().getFullYear();
</script>

<template>
    <div class="flex min-h-screen flex-col bg-surface lg:flex-row">
        <!-- Panel de identidad -->
        <aside
            data-theme="dark"
            class="pad-seguro-arriba pad-seguro-lados flex shrink-0 flex-col bg-brand-900 text-ink lg:w-[42%] lg:max-w-xl"
        >
            <div class="flex h-14 items-center justify-between gap-4 px-4 sm:px-6 lg:h-auto lg:px-12 lg:pt-12">
                <Link
                    :href="route('inicio')"
                    class="rounded-sm focus:outline-none focus-visible:ring-2 focus-visible:ring-focus focus-visible:ring-offset-2 focus-visible:ring-offset-brand-900"
                    aria-label="IYEM ERP, volver a la pantalla de inicio"
                >
                    <MarcaErp tamano="sm" sobre-oscuro class="lg:hidden" />
                    <MarcaErp tamano="md" sobre-oscuro class="hidden lg:inline-flex" />
                </Link>
                <InterruptorTema />
            </div>

            <div class="hidden flex-1 flex-col justify-center px-12 lg:flex">
                <p class="text-display-lg text-ink">Plataforma de gestión del Instituto</p>
                <p class="mt-4 max-w-sm text-body text-ink-600">
                    Padrón, consultas y los módulos del ecosistema IYEM en un mismo acceso.
                </p>
            </div>

            <p class="hidden px-12 pb-12 text-caption text-ink-600 lg:block">
                © <span class="font-mono">{{ anio }}</span> Instituto Yucateco de Emprendedores
            </p>
        </aside>

        <!-- Formulario -->
        <!-- El área segura va en el contenedor y el gutter en el hijo: las dos
             reglas escriben padding lateral y en el mismo elemento se pisan. -->
        <main class="pad-seguro-abajo pad-seguro-lados flex flex-1 flex-col">
            <div class="mx-auto flex w-full max-w-sm flex-1 flex-col px-4 py-10 sm:px-0 lg:justify-center lg:py-16">
                <h1 v-if="titulo" class="text-display-md text-ink">{{ titulo }}</h1>
                <p v-if="descripcion" class="mt-2 text-body text-ink-600">{{ descripcion }}</p>

                <div :class="{ 'mt-8': titulo || descripcion }">
                    <slot />
                </div>

                <p class="mt-10 text-small text-ink-600">
                    Las cuentas las crea el administrador de la plataforma.
                </p>
            </div>
        </main>
    </div>
</template>
