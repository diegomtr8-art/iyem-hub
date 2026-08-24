<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import Modal from '@/Components/Modal.vue';
import IconoModulo from '@/Components/IconoModulo.vue';
import IconoNav from '@/Components/IconoNav.vue';
import { usePermisos } from '@/Composables/usePermisos';

/**
 * Buscador global 360° (⌘K / Ctrl+K).
 *
 * Busca personas del padrón por nombre, CURP, RFC, correo o teléfono, y
 * muestra en el mismo renglón en qué módulos ya aparecen. La idea es que
 * quien atiende en ventanilla no tenga que abrir cinco sistemas para saber
 * si esa persona ya pasó por el instituto.
 */
const { puede } = usePermisos();

const disponible = computed(() => puede('ver-padron'));

const abierto = ref(false);
const termino = ref('');
const resultados = ref([]);
const total = ref(0);
const truncado = ref(false);
const buscando = ref(false);
const indiceActivo = ref(0);
const campo = ref(null);

const MINIMO = 3;
const ESPERA_MS = 300;

let temporizador = null;
let peticionEnCurso = null;

/** Etiqueta del atajo según el sistema operativo del visitante. */
const atajo = computed(() => {
    if (typeof navigator === 'undefined') return 'Ctrl K';
    return /Mac|iPhone|iPad/i.test(navigator.platform || navigator.userAgent) ? '⌘ K' : 'Ctrl K';
});

const abrir = async () => {
    if (!disponible.value) return;

    abierto.value = true;
    await nextTick();
    campo.value?.focus();
};

const cerrar = () => {
    abierto.value = false;
    termino.value = '';
    resultados.value = [];
    total.value = 0;
    truncado.value = false;
    indiceActivo.value = 0;
};

const atenderTeclado = (evento) => {
    // ⌘K en Mac, Ctrl+K en el resto.
    if ((evento.metaKey || evento.ctrlKey) && evento.key.toLowerCase() === 'k') {
        evento.preventDefault();
        abierto.value ? cerrar() : abrir();
    }
};

onMounted(() => document.addEventListener('keydown', atenderTeclado));
onUnmounted(() => {
    document.removeEventListener('keydown', atenderTeclado);
    clearTimeout(temporizador);
});

/**
 * Debounce de 300 ms. Sin él, escribir "Candelaria" dispararía diez
 * consultas al padrón y la última en llegar no sería necesariamente la
 * de la última tecla.
 */
watch(termino, (valor) => {
    clearTimeout(temporizador);
    indiceActivo.value = 0;

    if (valor.trim().length < MINIMO) {
        resultados.value = [];
        total.value = 0;
        truncado.value = false;
        buscando.value = false;
        return;
    }

    buscando.value = true;
    temporizador = setTimeout(consultar, ESPERA_MS);
});

async function consultar() {
    // Cancela la consulta anterior: si el usuario siguió escribiendo, su
    // respuesta ya no interesa y podría llegar después de la buena.
    peticionEnCurso?.abort();
    peticionEnCurso = new AbortController();

    try {
        const { data } = await axios.get(route('buscar'), {
            params: { q: termino.value.trim() },
            signal: peticionEnCurso.signal,
        });

        resultados.value = data.resultados ?? [];
        total.value = data.total ?? 0;
        truncado.value = data.truncado ?? false;
        buscando.value = false;
    } catch (error) {
        if (!axios.isCancel(error)) {
            resultados.value = [];
            buscando.value = false;
        }
    }
}

const mover = (delta) => {
    if (!resultados.value.length) return;

    const total = resultados.value.length;
    indiceActivo.value = (indiceActivo.value + delta + total) % total;
};

const abrirResultado = (persona) => {
    cerrar();
    router.visit(persona.url);
};

const abrirActivo = () => {
    const persona = resultados.value[indiceActivo.value];
    if (persona) abrirResultado(persona);
};

defineExpose({ abrir });
</script>

<template>
    <div v-if="disponible">
        <!-- Disparador visible en el encabezado -->
        <button
            type="button"
            class="toque-minimo flex items-center gap-2 rounded-lg border border-iyem-200 bg-white px-3 text-sm text-gray-400 transition hover:border-iyem-300 hover:text-gray-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-iyem-secundario"
            aria-label="Buscar en el padrón"
            @click="abrir"
        >
            <IconoNav icono="buscar" class="h-4 w-4" />
            <span class="hidden sm:inline">Buscar…</span>
            <kbd class="hidden rounded border border-iyem-200 bg-iyem-50 px-1.5 py-0.5 font-sans text-[0.65rem] font-semibold text-gray-500 md:inline">
                {{ atajo }}
            </kbd>
        </button>

        <Modal :show="abierto" max-width="2xl" @close="cerrar">
            <div class="flex flex-col">
                <!-- Campo de búsqueda -->
                <div class="flex items-center gap-3 border-b border-iyem-100 px-4 py-3">
                    <IconoNav icono="buscar" class="h-5 w-5 shrink-0 text-gray-400" />
                    <input
                        ref="campo"
                        v-model="termino"
                        type="search"
                        inputmode="search"
                        autocomplete="off"
                        autocapitalize="none"
                        spellcheck="false"
                        placeholder="Nombre, CURP, RFC, correo o teléfono…"
                        aria-label="Buscar personas en el padrón"
                        class="w-full border-0 p-0 text-base text-gray-800 placeholder-gray-400 focus:ring-0"
                        @keydown.down.prevent="mover(1)"
                        @keydown.up.prevent="mover(-1)"
                        @keydown.enter.prevent="abrirActivo"
                        @keydown.esc.prevent="cerrar"
                    >
                    <kbd class="hidden shrink-0 rounded border border-iyem-200 bg-iyem-50 px-1.5 py-0.5 font-sans text-[0.65rem] font-semibold text-gray-400 sm:inline">
                        esc
                    </kbd>
                </div>

                <!-- Resultados -->
                <div class="scrollbar-fina scroll-suave-ios max-h-[55dvh] overflow-y-auto overscroll-contain">
                    <!-- Esqueletos mientras responde -->
                    <div v-if="buscando" class="space-y-2 p-4" aria-live="polite">
                        <div v-for="n in 3" :key="n" class="flex animate-pulse items-center gap-3">
                            <div class="h-9 w-9 shrink-0 rounded-lg bg-iyem-100" />
                            <div class="flex-1 space-y-1.5">
                                <div class="h-3 w-2/5 rounded bg-iyem-100" />
                                <div class="h-2.5 w-3/5 rounded bg-iyem-50" />
                            </div>
                        </div>
                    </div>

                    <ul v-else-if="resultados.length" class="divide-y divide-iyem-100">
                        <li v-for="(persona, i) in resultados" :key="persona.id">
                            <button
                                type="button"
                                class="flex w-full items-start gap-3 px-4 py-3 text-left transition-colors duration-150"
                                :class="i === indiceActivo ? 'bg-iyem-50' : 'hover:bg-iyem-50/60'"
                                @click="abrirResultado(persona)"
                                @mouseenter="indiceActivo = i"
                            >
                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-iyem-claro text-iyem-primario">
                                    <IconoNav icono="user" class="h-4 w-4" />
                                </div>

                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="font-medium text-gray-800">
                                            {{ persona.nombre_completo }}
                                        </p>
                                        <span
                                            v-if="persona.demo"
                                            class="rounded-full bg-iyem-dorado/15 px-1.5 py-0.5 text-[0.6rem] font-bold uppercase tracking-wide text-iyem-alerta"
                                        >demo</span>
                                        <span
                                            v-if="persona.estado_persona !== 'activa'"
                                            class="rounded-full bg-gray-100 px-1.5 py-0.5 text-[0.6rem] font-semibold capitalize text-gray-500"
                                        >{{ persona.estado_persona }}</span>
                                    </div>

                                    <p class="mt-0.5 truncate text-xs text-gray-400">
                                        <span v-if="persona.curp" class="tabular-nums">{{ persona.curp }}</span>
                                        <span v-if="persona.curp && persona.municipio"> · </span>
                                        <span v-if="persona.municipio">{{ persona.municipio }}</span>
                                        <span v-if="persona.telefono"> · <span class="tabular-nums">{{ persona.telefono }}</span></span>
                                    </p>

                                    <!-- Vínculos entre módulos: lo que hace 360° a este buscador -->
                                    <div v-if="persona.modulos.length" class="mt-1.5 flex flex-wrap gap-1">
                                        <span
                                            v-for="modulo in persona.modulos"
                                            :key="modulo.slug"
                                            class="inline-flex items-center gap-1 rounded-full bg-iyem-claro px-1.5 py-0.5 text-[0.65rem] font-medium text-iyem-700"
                                            :title="`${modulo.total} registro(s) en ${modulo.nombre}`"
                                        >
                                            <IconoModulo :icono="modulo.icono" class="h-3 w-3" />
                                            {{ modulo.nombre }}
                                            <span class="tabular-nums opacity-70">{{ modulo.total }}</span>
                                        </span>
                                    </div>
                                    <p v-else class="mt-1.5 text-[0.65rem] text-gray-300">
                                        Sin registros en otros módulos
                                    </p>
                                </div>

                                <IconoNav icono="arrow" class="mt-2 h-4 w-4 shrink-0 text-gray-300" />
                            </button>
                        </li>
                    </ul>

                    <p v-else-if="termino.trim().length >= MINIMO" class="px-4 py-10 text-center text-sm text-gray-400">
                        Nadie coincide con «{{ termino.trim() }}».
                    </p>

                    <div v-else class="px-4 py-10 text-center">
                        <IconoNav icono="buscar" class="mx-auto h-8 w-8 text-iyem-100" />
                        <p class="mt-2 text-sm text-gray-400">
                            Escribe al menos {{ MINIMO }} caracteres.
                        </p>
                        <p class="mt-1 text-xs text-gray-300">
                            Busca por nombre, CURP, RFC, correo o teléfono.
                        </p>
                    </div>
                </div>

                <!-- Pie con ayuda de teclado -->
                <div class="flex items-center justify-between gap-3 border-t border-iyem-100 bg-iyem-50/60 px-4 py-2 text-[0.7rem] text-gray-400">
                    <span class="hidden items-center gap-3 sm:flex">
                        <span><kbd class="font-sans font-semibold">↑ ↓</kbd> moverse</span>
                        <span><kbd class="font-sans font-semibold">↵</kbd> abrir ficha</span>
                        <span><kbd class="font-sans font-semibold">esc</kbd> cerrar</span>
                    </span>
                    <span v-if="truncado" class="tabular-nums">
                        Mostrando {{ resultados.length }} de {{ total.toLocaleString('es-MX') }} coincidencias
                    </span>
                    <span v-else-if="total" class="tabular-nums">
                        {{ total }} coincidencia{{ total === 1 ? '' : 's' }}
                    </span>
                </div>
            </div>
        </Modal>
    </div>
</template>
