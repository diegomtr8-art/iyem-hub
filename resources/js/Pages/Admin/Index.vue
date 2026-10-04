<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import TarjetaStat from '@/Components/TarjetaStat.vue';
import { fechaHora } from '@/formato';

defineProps({
    usuarios: Array,
    totales: Object,
});

const formatearFecha = (fecha) => (fecha ? fechaHora(fecha) : 'Nunca');
</script>

<template>
    <AppLayout title="Administración">
        <template #header>
            <span>Administración</span>
        </template>

        <div class="mx-auto max-w-7xl space-y-8">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h1 class="text-display-lg text-ink">Administración</h1>
                    <p class="mt-1 text-body text-ink-600">Cuentas, roles y últimos accesos a la plataforma.</p>
                </div>
                <Link
                    :href="route('admin.usuarios.index')"
                    class="inline-flex h-10 items-center justify-center gap-2 rounded-md bg-action px-4 text-body-strong text-action-ink transition-colors hover:bg-action-hover focus:outline-none focus-visible:ring-2 focus-visible:ring-focus focus-visible:ring-offset-2"
                >
                    Gestionar usuarios
                </Link>
            </div>

            <section aria-label="Totales" class="grid grid-cols-1 gap-4 sm:grid-cols-3 sm:gap-6">
                <TarjetaStat etiqueta="Usuarios totales" :valor="totales.usuarios" icono="user" />
                <TarjetaStat etiqueta="Usuarios activos" :valor="totales.activos" icono="shield" />
                <TarjetaStat etiqueta="Roles configurados" :valor="totales.roles" icono="stack" />
            </section>

            <section aria-labelledby="titulo-accesos">
                <h2 id="titulo-accesos" class="text-title text-ink">Último acceso por usuario</h2>

                <div class="mt-4 overflow-hidden rounded-lg border border-line bg-surface shadow-sm">
                    <template v-if="usuarios.length">
                        <!-- Teléfono: lista -->
                        <ul class="divide-y divide-line sm:hidden">
                            <li v-for="usuario in usuarios" :key="usuario.id" class="px-4 py-3">
                                <div class="flex items-start justify-between gap-3">
                                    <p class="min-w-0 truncate text-body-strong text-ink">{{ usuario.name }} {{ usuario.apellido }}</p>
                                    <span
                                        class="inline-flex h-6 shrink-0 items-center gap-1 rounded-sm px-2 text-caption text-ink"
                                        :class="usuario.estado ? 'bg-success-surface' : 'bg-surface-100'"
                                    >
                                        <span class="h-1.5 w-1.5 rounded-full" :class="usuario.estado ? 'bg-success' : 'bg-ink-400'" aria-hidden="true" />
                                        {{ usuario.estado ? 'Activo' : 'Deshabilitado' }}
                                    </span>
                                </div>
                                <p class="mt-0.5 truncate text-small text-ink-600">{{ usuario.email }}</p>
                                <p class="mt-0.5 text-small text-ink-600">
                                    {{ usuario.roles.map(r => r.name).join(', ') || 'Sin rol' }} ·
                                    <span class="font-mono">{{ formatearFecha(usuario.last_login) }}</span>
                                </p>
                            </li>
                        </ul>

                        <!-- Escritorio: tabla -->
                        <div class="scrollbar-fina hidden overflow-x-auto sm:block">
                            <table class="w-full text-left">
                                <thead class="bg-surface-50">
                                    <tr>
                                        <th scope="col" class="px-3 py-2 text-overline text-ink-600">Usuario</th>
                                        <th scope="col" class="px-3 py-2 text-overline text-ink-600">Correo</th>
                                        <th scope="col" class="px-3 py-2 text-overline text-ink-600">Rol</th>
                                        <th scope="col" class="px-3 py-2 text-overline text-ink-600">Estado</th>
                                        <th scope="col" class="px-3 py-2 text-overline text-ink-600">Último acceso</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="usuario in usuarios" :key="usuario.id" class="border-t border-line hover:bg-surface-100">
                                        <td class="h-11 px-3 text-body-strong text-ink">{{ usuario.name }} {{ usuario.apellido }}</td>
                                        <td class="px-3 text-body text-ink-600">{{ usuario.email }}</td>
                                        <td class="px-3 text-body text-ink-600">{{ usuario.roles.map(r => r.name).join(', ') || '—' }}</td>
                                        <td class="px-3">
                                            <span
                                                class="inline-flex h-6 items-center gap-1 rounded-sm px-2 text-caption text-ink"
                                                :class="usuario.estado ? 'bg-success-surface' : 'bg-surface-100'"
                                            >
                                                <span class="h-1.5 w-1.5 rounded-full" :class="usuario.estado ? 'bg-success' : 'bg-ink-400'" aria-hidden="true" />
                                                {{ usuario.estado ? 'Activo' : 'Deshabilitado' }}
                                            </span>
                                        </td>
                                        <td class="px-3 text-number text-ink">{{ formatearFecha(usuario.last_login) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </template>
                    <div v-else class="p-6">
                        <p class="text-body-strong text-ink">Aún no hay usuarios.</p>
                        <p class="mt-1 text-small text-ink-600">Crea la primera cuenta desde Gestionar usuarios.</p>
                    </div>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
