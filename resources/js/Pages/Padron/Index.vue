<script setup>
import { ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BadgePersona from '@/Components/BadgePersona.vue';
import IconoNav from '@/Components/IconoNav.vue';
import Paginacion from '@/Components/Paginacion.vue';
import { numero } from '@/formato';
import { usePermisos } from '@/Composables/usePermisos';

const props = defineProps({
    personas: { type: Object, required: true },
    filtros: { type: Object, default: () => ({}) },
});

const { puede } = usePermisos();

const busqueda = ref(props.filtros.busqueda ?? '');
const estadoPersona = ref(props.filtros.estado_persona ?? '');

const buscar = () => {
    router.get(
        route('padron.index'),
        { busqueda: busqueda.value, estado_persona: estadoPersona.value },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

// El selector filtra al instante; el texto espera al submit para no disparar
// una consulta por cada tecla.
watch(estadoPersona, buscar);

const limpiar = () => {
    busqueda.value = '';
    estadoPersona.value = '';
    buscar();
};

const hayFiltros = () => busqueda.value !== '' || estadoPersona.value !== '';

const claseFoco = 'focus:outline-none focus-visible:ring-2 focus-visible:ring-focus focus-visible:ring-offset-2';
const claseSecundario = `toque-minimo inline-flex items-center justify-center gap-2 rounded-md border border-line-strong bg-surface px-4 text-body-strong text-ink transition-colors hover:bg-surface-100 ${claseFoco}`;
</script>

<template>
    <AppLayout title="Padrón">
        <template #header>
            <span>Padrón Central</span>
        </template>

        <div class="mx-auto max-w-7xl">
            <h1 class="text-display-lg text-ink">Padrón Central</h1>
            <p class="mt-1 text-small text-ink-600">
                <span class="font-mono">{{ numero(personas.total) }}</span> personas registradas{{ hayFiltros() ? ' con estos filtros' : '' }}.
            </p>

            <!-- Filtros y acciones -->
            <div class="mt-6 flex flex-col gap-3 rounded-lg border border-line bg-surface-50 p-4 lg:flex-row lg:items-end lg:justify-between">
                <form class="flex flex-col gap-3 sm:flex-row sm:items-end" role="search" @submit.prevent="buscar">
                    <div>
                        <label for="padron-busqueda" class="block text-body-strong text-ink">Buscar</label>
                        <div class="relative mt-1.5">
                            <IconoNav
                                icono="buscar"
                                class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-ink-400"
                            />
                            <input
                                id="padron-busqueda"
                                v-model="busqueda"
                                type="search"
                                inputmode="search"
                                enterkeyhint="search"
                                placeholder="Nombre, correo o municipio"
                                class="h-10 w-full rounded-md border-line-strong bg-surface pl-9 text-body text-ink placeholder:text-ink-400 focus:border-focus focus:ring-2 focus:ring-focus focus:ring-offset-2 sm:w-72"
                            >
                        </div>
                    </div>

                    <div>
                        <label for="padron-estado" class="block text-body-strong text-ink">Estado</label>
                        <select
                            id="padron-estado"
                            v-model="estadoPersona"
                            class="mt-1.5 h-10 w-full rounded-md border-line-strong bg-surface text-body text-ink focus:border-focus focus:ring-2 focus:ring-focus focus:ring-offset-2 sm:w-48"
                        >
                            <option value="">Todos los estados</option>
                            <option value="activa">Activa</option>
                            <option value="inactiva">Inactiva</option>
                            <option value="bloqueada">Bloqueada</option>
                        </select>
                    </div>

                    <div class="flex gap-2">
                        <button type="submit" :class="[claseSecundario, 'flex-1 sm:flex-none']">
                            Filtrar
                        </button>
                        <button
                            v-if="hayFiltros()"
                            type="button"
                            :class="['toque-minimo inline-flex items-center justify-center rounded-md px-3 text-body-strong text-action transition-colors hover:bg-surface-100', claseFoco]"
                            @click="limpiar"
                        >
                            Limpiar filtros
                        </button>
                    </div>
                </form>

                <div class="flex gap-2">
                    <Link :href="route('padron.mapa')" :class="[claseSecundario, 'flex-1 lg:flex-none']">
                        <IconoNav icono="mapa" class="h-4 w-4" />
                        Ver mapa
                    </Link>
                    <Link
                        v-if="puede('crear-padron')"
                        :href="route('padron.create')"
                        :class="['toque-minimo inline-flex flex-1 items-center justify-center gap-2 rounded-md bg-action px-4 text-body-strong text-action-ink transition-colors hover:bg-action-hover lg:flex-none', claseFoco]"
                    >
                        <IconoNav icono="mas" class="h-4 w-4" />
                        Nuevo contacto
                    </Link>
                </div>
            </div>

            <!-- ============================================================
                 Teléfono: lista de tarjetas.
                 Una tabla de cinco columnas en 375 px obliga a scroll
                 horizontal; apilada se lee de un vistazo.
                 ============================================================ -->
            <ul v-if="personas.data.length" class="mt-4 space-y-3 md:hidden" role="list">
                <li v-for="persona in personas.data" :key="persona.id">
                    <Link
                        :href="route('padron.show', persona.id)"
                        :class="['block rounded-lg border border-line bg-surface p-4 shadow-sm transition-colors hover:bg-surface-50', claseFoco]"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <p class="min-w-0 flex-1 text-body-strong text-ink">
                                {{ persona.nombre_completo }}
                            </p>
                            <BadgePersona :estado="persona.estado_persona" />
                        </div>

                        <dl class="mt-3 space-y-1 text-small">
                            <div v-if="persona.email" class="flex gap-2">
                                <dt class="w-20 shrink-0 text-ink-600">Correo</dt>
                                <dd class="min-w-0 truncate text-ink">{{ persona.email }}</dd>
                            </div>
                            <div v-if="persona.telefono" class="flex gap-2">
                                <dt class="w-20 shrink-0 text-ink-600">Teléfono</dt>
                                <dd class="font-mono text-ink">{{ persona.telefono }}</dd>
                            </div>
                            <div v-if="persona.municipio" class="flex gap-2">
                                <dt class="w-20 shrink-0 text-ink-600">Municipio</dt>
                                <dd class="text-ink">{{ persona.municipio }}</dd>
                            </div>
                        </dl>
                    </Link>
                </li>
            </ul>

            <!-- ============================================================
                 Tablet y escritorio: tabla a sangre, cabecera fija.
                 El scroll, si hace falta, ocurre dentro del contenedor y
                 nunca en el body.
                 ============================================================ -->
            <div
                v-if="personas.data.length"
                class="scrollbar-fina scroll-suave-ios mt-4 hidden max-h-[70dvh] overflow-auto border border-line bg-surface md:block"
            >
                <table class="min-w-full text-left">
                    <thead class="sticky top-0 z-10 bg-surface-50">
                        <tr>
                            <th scope="col" class="px-3 py-2 text-overline text-ink-600">Nombre</th>
                            <th scope="col" class="px-3 py-2 text-overline text-ink-600">Correo</th>
                            <th scope="col" class="px-3 py-2 text-right text-overline text-ink-600">Teléfono</th>
                            <th scope="col" class="px-3 py-2 text-overline text-ink-600">Municipio</th>
                            <th scope="col" class="px-3 py-2 text-overline text-ink-600">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="persona in personas.data"
                            :key="persona.id"
                            class="border-t border-line transition-colors hover:bg-surface-100"
                        >
                            <td class="h-11 px-3">
                                <Link
                                    :href="route('padron.show', persona.id)"
                                    :class="['rounded-sm text-body-strong text-action underline-offset-2 hover:underline', claseFoco]"
                                >
                                    {{ persona.nombre_completo }}
                                </Link>
                            </td>
                            <td class="px-3 text-body text-ink-600">{{ persona.email || '—' }}</td>
                            <td class="px-3 text-right text-number text-ink">{{ persona.telefono || '—' }}</td>
                            <td class="px-3 text-body text-ink-600">{{ persona.municipio || '—' }}</td>
                            <td class="px-3"><BadgePersona :estado="persona.estado_persona" /></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="!personas.data.length" class="mt-4 rounded-lg border border-line bg-surface p-6">
                <template v-if="hayFiltros()">
                    <p class="text-body-strong text-ink">No hay personas con estos filtros.</p>
                    <p class="mt-1 text-small text-ink-600">Prueba con otro término o quita el filtro de estado.</p>
                    <button type="button" :class="[claseSecundario, 'mt-4']" @click="limpiar">
                        Limpiar filtros
                    </button>
                </template>
                <template v-else>
                    <p class="text-body-strong text-ink">El padrón todavía no tiene contactos.</p>
                    <p class="mt-1 text-small text-ink-600">
                        Las personas llegan desde los módulos del ecosistema, por importación o capturándolas a mano.
                    </p>
                </template>
            </div>

            <Paginacion :paginador="personas" />
        </div>
    </AppLayout>
</template>
