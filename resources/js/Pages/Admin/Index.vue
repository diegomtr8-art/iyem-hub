<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import TarjetaStat from '@/Components/TarjetaStat.vue';

defineProps({
    usuarios: Array,
    totales: Object,
});

const formatearFecha = (fecha) => fecha
    ? new Date(fecha).toLocaleString('es-MX', { dateStyle: 'medium', timeStyle: 'short' })
    : 'Nunca';
</script>

<template>
    <AppLayout title="Panel de Administración">
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Panel de Administración
            </h2>
        </template>

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
            <TarjetaStat etiqueta="Usuarios totales" :valor="totales.usuarios" icono="user" />
            <TarjetaStat etiqueta="Usuarios activos" :valor="totales.activos" icono="shield" />
            <TarjetaStat etiqueta="Roles configurados" :valor="totales.roles" icono="stack" />
        </div>

        <div class="mt-8 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-800">
                Log de accesos
            </h3>
            <Link
                :href="route('admin.usuarios.index')"
                class="inline-flex items-center gap-2 rounded-lg bg-iyem-gradient px-4 py-2 text-sm font-semibold text-white shadow-soft transition hover:shadow-glow"
            >
                Gestionar usuarios
            </Link>
        </div>

        <div class="scrollbar-fina mt-3 overflow-x-auto rounded-2xl border border-iyem-claro bg-white shadow-soft">
            <table class="min-w-full divide-y divide-iyem-claro text-sm">
                <thead class="bg-iyem-50 text-left text-xs uppercase tracking-wide text-iyem-700">
                    <tr>
                        <th class="px-4 py-3">Usuario</th>
                        <th class="px-4 py-3">Correo</th>
                        <th class="px-4 py-3">Rol</th>
                        <th class="px-4 py-3">Estado</th>
                        <th class="px-4 py-3">Último acceso</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-iyem-claro">
                    <tr v-for="usuario in usuarios" :key="usuario.id" class="transition hover:bg-iyem-50/60">
                        <td class="px-4 py-3 font-medium text-gray-800">
                            {{ usuario.name }} {{ usuario.apellido }}
                        </td>
                        <td class="px-4 py-3 text-gray-600">
                            {{ usuario.email }}
                        </td>
                        <td class="px-4 py-3 text-gray-600">
                            {{ usuario.roles.map(r => r.name).join(', ') || '—' }}
                        </td>
                        <td class="px-4 py-3">
                            <span
                                class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold"
                                :class="usuario.estado ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-600'"
                            >
                                {{ usuario.estado ? 'Activo' : 'Deshabilitado' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-500">
                            {{ formatearFecha(usuario.last_login) }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AppLayout>
</template>
