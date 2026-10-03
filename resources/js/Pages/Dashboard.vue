<script setup>
import { computed, onMounted, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';
import IconoModulo from '@/Components/IconoModulo.vue';
import IconoNav from '@/Components/IconoNav.vue';
import TarjetaKpi from '@/Components/TarjetaKpi.vue';
import TarjetaModulo from '@/Components/TarjetaModulo.vue';
import { fechaHora, fechaLarga, hace, numero } from '@/formato';

const props = defineProps({
    modulos: { type: Array, default: () => [] },
    categorias: { type: Array, default: () => [] },
    indicadores: { type: Object, default: () => ({}) },
    actividades: { type: Array, default: () => [] },
    // null para quien no es Super Admin: entonces la sección no existe.
    actividadesPlataforma: { type: Array, default: null },
});

const page = usePage();
const usuario = computed(() => page.props.auth.user);

/* ------------------------------------------------------------------ *
 * Encabezado
 * ------------------------------------------------------------------ */

const saludo = computed(() => {
    const hora = new Date().getHours();
    if (hora < 12) return 'Buenos días';
    if (hora < 19) return 'Buenas tardes';
    return 'Buenas noches';
});

const hoy = fechaLarga();

/* ------------------------------------------------------------------ *
 * Semáforo de disponibilidad
 *
 * Se pide después de montar la página: sondear los subdominios durante el
 * render dejaría el tablero en blanco cada vez que uno no contestara. Hasta
 * que llega, cada tarjeta dice "Consultando…", no "En línea".
 * ------------------------------------------------------------------ */

const salud = ref({});
const saludCargada = ref(false);
const saludFallo = ref(false);

onMounted(async () => {
    try {
        const { data } = await axios.get(route('dashboard.salud'));
        salud.value = data.salud ?? {};
    } catch {
        // Si el sondeo falla, las tarjetas dicen "Sin datos". Es preferible a
        // afirmar que todo está caído por un error nuestro.
        saludFallo.value = true;
    } finally {
        saludCargada.value = true;
    }
});

const resumenSalud = computed(() => {
    if (!saludCargada.value || saludFallo.value) return null;

    const navegables = props.modulos.filter((m) => m.navegable);
    const enLinea = navegables.filter((m) => salud.value[m.slug]?.estado === 'en_linea').length;
    const caidos = navegables.filter((m) => salud.value[m.slug]?.estado === 'caido').length;

    return { enLinea, caidos, total: navegables.length };
});

/* ------------------------------------------------------------------ *
 * Módulos agrupados por categoría (las que manda el backend, en su orden)
 * ------------------------------------------------------------------ */

const vista = ref('tarjetas');

const grupos = computed(() =>
    props.categorias
        .map((categoria) => ({
            ...categoria,
            modulos: props.modulos.filter((m) => m.categoria === categoria.clave),
        }))
        .filter((grupo) => grupo.modulos.length),
);

const hayResponsables = computed(() => props.modulos.some((m) => !m.navegable && m.responsable));

/* ------------------------------------------------------------------ *
 * Actividad
 * ------------------------------------------------------------------ */

const modulosPorSlug = computed(() => Object.fromEntries(props.modulos.map((m) => [m.slug, m])));

const nombreModulo = (slug) => modulosPorSlug.value[slug]?.nombre ?? slug;
const iconoModulo = (slug) => modulosPorSlug.value[slug]?.icono ?? 'squares-2x2';
</script>

<template>
    <AppLayout title="Tablero">
        <template #header>
            <h2>Tablero</h2>
        </template>

        <div class="mx-auto max-w-7xl space-y-8">
            <!-- ============================================================
                 Saludo
                 ============================================================ -->
            <section aria-labelledby="titulo-saludo">
                <h1 id="titulo-saludo" class="text-title text-ink">
                    {{ saludo }}, {{ usuario.name }}
                </h1>
                <p class="mt-1 text-small text-ink-600">Hoy es {{ hoy }}.</p>
            </section>

            <!-- ============================================================
                 Indicadores
                 Cada cifra lleva su referencia: no hay periodo anterior en el
                 backend, así que la referencia es la fecha del corte.
                 ============================================================ -->
            <section aria-label="Indicadores">
                <div class="grid grid-cols-2 gap-4 sm:gap-6 xl:grid-cols-4">
                    <TarjetaKpi
                        etiqueta="Personas en el padrón"
                        :valor="indicadores.personas ?? null"
                        :referencia="`Al ${hoy}`"
                    />
                    <TarjetaKpi
                        etiqueta="Trámites activos"
                        :valor="indicadores.tramites_activos ?? null"
                        :referencia="`En todos los módulos, al ${hoy}`"
                    />
                    <TarjetaKpi
                        etiqueta="Módulos visibles"
                        :valor="indicadores.modulos_visibles ?? null"
                        referencia="Con permiso para tu cuenta"
                    />
                    <TarjetaKpi
                        etiqueta="Módulos navegables"
                        :valor="indicadores.modulos_navegables ?? null"
                        :referencia="`De ${numero(indicadores.modulos_visibles)} visibles; el resto aún no abre`"
                    />
                </div>
            </section>

            <!-- ============================================================
                 Módulos por categoría
                 ============================================================ -->
            <section aria-labelledby="titulo-modulos">
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <h2 id="titulo-modulos" class="text-title text-ink">Módulos</h2>
                        <p class="mt-1 text-small text-ink-600" aria-live="polite">
                            <template v-if="resumenSalud">
                                <span class="font-mono">{{ resumenSalud.enLinea }}</span> de
                                <span class="font-mono">{{ resumenSalud.total }}</span> en línea
                                <template v-if="resumenSalud.caidos">
                                    · <span class="font-mono">{{ resumenSalud.caidos }}</span> sin respuesta
                                </template>
                            </template>
                            <template v-else-if="saludFallo">No se pudo consultar la disponibilidad; recarga la página para intentarlo de nuevo.</template>
                            <template v-else>Consultando disponibilidad…</template>
                        </p>
                    </div>

                    <div class="inline-flex rounded-md border border-line-strong bg-surface p-0.5" role="group" aria-label="Forma de ver los módulos">
                        <button
                            v-for="opcion in [
                                { clave: 'tarjetas', icono: 'tarjetas', texto: 'Tarjetas' },
                                { clave: 'lista', icono: 'lista', texto: 'Lista' },
                            ]"
                            :key="opcion.clave"
                            type="button"
                            class="toque-minimo flex items-center justify-center gap-1.5 rounded-sm px-3 text-small font-medium transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-focus"
                            :class="vista === opcion.clave ? 'bg-surface-brand text-action' : 'text-ink-600 hover:text-ink'"
                            :aria-pressed="vista === opcion.clave ? 'true' : 'false'"
                            @click="vista = opcion.clave"
                        >
                            <IconoNav :icono="opcion.icono" class="h-4 w-4" />
                            <span class="sr-only sm:not-sr-only">{{ opcion.texto }}</span>
                        </button>
                    </div>
                </div>

                <div v-if="grupos.length" class="mt-6 space-y-8">
                    <section v-for="grupo in grupos" :key="grupo.clave" :aria-labelledby="`grupo-${grupo.clave}`">
                        <h3 :id="`grupo-${grupo.clave}`" class="flex items-baseline gap-2 border-b border-line pb-2 text-overline text-ink-600">
                            {{ grupo.nombre }}
                            <span class="font-mono text-caption">{{ grupo.total }}</span>
                        </h3>
                        <div
                            class="mt-4 grid gap-4 sm:gap-6"
                            :class="vista === 'lista' ? 'grid-cols-1 lg:grid-cols-2' : 'grid-cols-1 sm:grid-cols-2 xl:grid-cols-3'"
                        >
                            <TarjetaModulo
                                v-for="modulo in grupo.modulos"
                                :key="modulo.slug"
                                :modulo="modulo"
                                :salud="salud[modulo.slug] ?? (saludFallo ? { estado: 'sin_datos' } : null)"
                                :compacto="vista === 'lista'"
                            />
                        </div>
                    </section>
                </div>

                <div v-else class="mt-6 rounded-lg border border-line bg-surface p-6">
                    <p class="text-body-strong text-ink">No tienes módulos asignados.</p>
                    <p class="mt-1 text-small text-ink-600">
                        Pide al administrador de la plataforma que te dé permiso sobre los módulos con los que trabajas.
                    </p>
                </div>

                <p v-if="hayResponsables" class="mt-4 text-caption text-ink-600">
                    Las áreas responsables que aparecen en los módulos no disponibles son preliminares y están pendientes de confirmar con el Instituto.
                </p>
            </section>

            <!-- ============================================================
                 Actividad reciente del usuario
                 ============================================================ -->
            <section aria-labelledby="titulo-actividad">
                <h2 id="titulo-actividad" class="text-title text-ink">Tu actividad reciente</h2>
                <p class="mt-1 text-small text-ink-600">Los últimos accesos que hiciste a módulos desde este tablero.</p>

                <div class="mt-4 overflow-hidden rounded-lg border border-line bg-surface shadow-sm">
                    <template v-if="actividades.length">
                        <!-- Teléfono: lista -->
                        <ul class="divide-y divide-line sm:hidden">
                            <li v-for="actividad in actividades" :key="actividad.id" class="flex items-center gap-3 px-4 py-3">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-surface-brand text-action">
                                    <IconoModulo :icono="iconoModulo(actividad.modulo)" class="h-5 w-5" />
                                </span>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-body-strong text-ink">{{ nombreModulo(actividad.modulo) }}</p>
                                    <p class="truncate font-mono text-small text-ink-600">{{ actividad.ip_address ?? 'IP no registrada' }}</p>
                                </div>
                                <time class="shrink-0 text-right" :datetime="actividad.accedido_at">
                                    <span class="block font-mono text-small text-ink">{{ fechaHora(actividad.accedido_at) }}</span>
                                    <span class="block text-caption text-ink-600">{{ hace(actividad.accedido_at) }}</span>
                                </time>
                            </li>
                        </ul>

                        <!-- Escritorio: tabla -->
                        <table class="hidden w-full text-left sm:table">
                            <thead class="bg-surface-50">
                                <tr>
                                    <th scope="col" class="px-3 py-2 text-overline text-ink-600">Módulo</th>
                                    <th scope="col" class="px-3 py-2 text-overline text-ink-600">IP</th>
                                    <th scope="col" class="px-3 py-2 text-overline text-ink-600">Fecha</th>
                                    <th scope="col" class="px-3 py-2 text-overline text-ink-600"><span class="sr-only">Hace</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="actividad in actividades" :key="actividad.id" class="border-t border-line hover:bg-surface-100">
                                    <td class="h-11 px-3 text-body text-ink">
                                        <span class="flex items-center gap-2">
                                            <IconoModulo :icono="iconoModulo(actividad.modulo)" class="h-4 w-4 text-ink-600" />
                                            {{ nombreModulo(actividad.modulo) }}
                                        </span>
                                    </td>
                                    <td class="px-3 font-mono text-small text-ink-600">{{ actividad.ip_address ?? 'No registrada' }}</td>
                                    <td class="px-3 text-number text-ink">
                                        <time :datetime="actividad.accedido_at">{{ fechaHora(actividad.accedido_at) }}</time>
                                    </td>
                                    <td class="px-3 text-small text-ink-600">{{ hace(actividad.accedido_at) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </template>

                    <div v-else class="p-6">
                        <p class="text-body-strong text-ink">Sin accesos registrados todavía.</p>
                        <p class="mt-1 text-small text-ink-600">
                            Cuando abras un módulo desde este tablero, el acceso quedará anotado aquí con su fecha e IP.
                        </p>
                    </div>
                </div>
            </section>

            <!-- ============================================================
                 Bitácora de la plataforma — solo Super Admin.
                 Para cualquier otro rol el backend manda null y la sección
                 no se pinta: ni tabla vacía ni hueco.
                 ============================================================ -->
            <section v-if="actividadesPlataforma" aria-labelledby="titulo-bitacora">
                <h2 id="titulo-bitacora" class="text-title text-ink">Bitácora de la plataforma</h2>
                <p class="mt-1 text-small text-ink-600">Los últimos 30 accesos de todas las cuentas. Solo la ve la administración.</p>

                <div class="mt-4 overflow-hidden rounded-lg border border-line bg-surface shadow-sm">
                    <template v-if="actividadesPlataforma.length">
                        <ul class="divide-y divide-line sm:hidden">
                            <li v-for="actividad in actividadesPlataforma" :key="actividad.id" class="px-4 py-3">
                                <div class="flex items-baseline justify-between gap-3">
                                    <p class="truncate text-body-strong text-ink">{{ actividad.usuario }}</p>
                                    <time class="shrink-0 font-mono text-small text-ink" :datetime="actividad.accedido_at">{{ fechaHora(actividad.accedido_at) }}</time>
                                </div>
                                <p class="mt-0.5 truncate text-small text-ink-600">
                                    {{ nombreModulo(actividad.modulo) }} · <span class="font-mono">{{ actividad.ip_address ?? 'IP no registrada' }}</span>
                                </p>
                            </li>
                        </ul>

                        <div class="scrollbar-fina hidden max-h-[480px] overflow-y-auto sm:block">
                            <table class="w-full text-left">
                                <thead class="sticky top-0 bg-surface-50">
                                    <tr>
                                        <th scope="col" class="px-3 py-2 text-overline text-ink-600">Usuario</th>
                                        <th scope="col" class="px-3 py-2 text-overline text-ink-600">Módulo</th>
                                        <th scope="col" class="px-3 py-2 text-overline text-ink-600">IP</th>
                                        <th scope="col" class="px-3 py-2 text-overline text-ink-600">Fecha</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="actividad in actividadesPlataforma" :key="actividad.id" class="border-t border-line hover:bg-surface-100">
                                        <td class="h-11 px-3 text-body text-ink">{{ actividad.usuario }}</td>
                                        <td class="px-3 text-body text-ink-600">{{ nombreModulo(actividad.modulo) }}</td>
                                        <td class="px-3 font-mono text-small text-ink-600">{{ actividad.ip_address ?? 'No registrada' }}</td>
                                        <td class="px-3 text-number text-ink">
                                            <time :datetime="actividad.accedido_at">{{ fechaHora(actividad.accedido_at) }}</time>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </template>

                    <div v-else class="p-6">
                        <p class="text-body-strong text-ink">La plataforma no tiene accesos registrados todavía.</p>
                        <p class="mt-1 text-small text-ink-600">Los accesos aparecen aquí en cuanto alguien abre un módulo desde el tablero.</p>
                    </div>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
