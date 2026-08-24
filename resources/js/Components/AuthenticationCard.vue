<script setup>
import iconoIyem from '../../images/iyem-icono.png';

const modulos = ['CREA', 'Impúlsate', 'Nodico', 'Herencia Viva', 'Jurídico', 'Padrón'];
</script>

<template>
    <div class="flex min-h-screen bg-white">
        <!-- Panel de marca -->
        <div class="relative hidden w-[46%] max-w-2xl flex-col justify-between overflow-hidden bg-tinta-gradient p-12 text-white lg:flex">
            <div
                class="pointer-events-none absolute inset-0 opacity-[0.07]"
                :style="{ backgroundImage: `url(${iconoIyem})`, backgroundSize: '130px', backgroundRepeat: 'repeat' }"
            />
            <div class="pointer-events-none absolute inset-0 bg-iyem-mesh" />
            <div class="pointer-events-none absolute -right-32 -top-32 h-96 w-96 rounded-full bg-iyem-secundario/30 blur-3xl" />
            <div class="pointer-events-none absolute -bottom-40 -left-20 h-96 w-96 rounded-full bg-iyem-700/40 blur-3xl" />

            <div class="relative flex items-center gap-2 text-sm font-semibold uppercase tracking-[0.2em] text-white/60">
                <span class="h-1.5 w-1.5 rounded-full bg-iyem-400" />
                Plataforma Centralizada
            </div>

            <div class="relative">
                <h1 class="max-w-md text-4xl font-bold leading-tight">
                    Un solo acceso para todo el ecosistema IYEM
                </h1>
                <p class="mt-5 max-w-sm text-white/60">
                    Gestiona créditos, citas, coworking, comercio y trámites jurídicos desde una misma plataforma.
                </p>

                <div class="mt-8 flex flex-wrap gap-2">
                    <span
                        v-for="modulo in modulos"
                        :key="modulo"
                        class="rounded-full border border-white/15 bg-white/5 px-3.5 py-1.5 text-xs font-medium text-white/80 backdrop-blur-sm"
                    >
                        {{ modulo }}
                    </span>
                </div>
            </div>

            <p class="relative text-xs text-white/35">
                &copy; {{ new Date().getFullYear() }} Instituto Yucateco de Emprendedores (IYEM). Todos los derechos reservados.
            </p>
        </div>

        <!-- Panel de formulario -->
        <div class="patron-puntos relative flex flex-1 flex-col items-center justify-center px-6 py-12 text-iyem-100 sm:px-12">
            <div class="w-full max-w-sm">
                <div class="mb-10">
                    <slot name="logo" />
                </div>

                <slot />

                <p class="mt-10 text-center text-xs text-gray-400 lg:hidden">
                    &copy; {{ new Date().getFullYear() }} Instituto Yucateco de Emprendedores (IYEM).
                </p>
            </div>
        </div>
    </div>
</template>
