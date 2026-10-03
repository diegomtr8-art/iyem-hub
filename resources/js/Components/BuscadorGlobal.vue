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
            class="toque-minimo flex items-center gap-2 rounded-md border border-line-strong bg-surface px-3 text-small text-ink-600 transition-colors hover:bg-surface-100 hover:text-ink focus:outline-none focus-visible:ring-2 focus-visible:ring-focus focus-visible:ring-offset-2 lg:min-w-[220px]"
            aria-label="Buscar en el padrón"
            @click="abrir"
        >
            <IconoNav icono="buscar" class="h-4 w-4" />
            <span class="hidden sm:inline">Buscar personas…</span>
            <kbd class="ml-auto hidden rounded-sm border border-line bg-surface-50 px-1.5 py-0.5 font-mono text-caption text-ink-600 md:inline">
                {{ atajo }}
            </kbd>
        </button>

        <Modal :show="abierto" max-width="2xl" @close="cerrar">
            <div class="flex flex-col">
                <!-- Campo de búsqueda -->
                <!-- El anillo de foco va en el contenedor: el campo ocupa toda la barra. -->
                <div class="flex items-center gap-3 border-b border-line px-4 py-3 focus-within:ring-2 focus-within:ring-inset focus-within:ring-focus">
                    <IconoNav icono="buscar" class="h-5 w-5 shrink-0 text-ink-600" />
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
                        class="h-10 w-full border-0 bg-transparent p-0 text-body text-ink placeholder:text-ink-400 focus:ring-0"
                        @keydown.down.prevent="mover(1)"
                        @keydown.up.prevent="mover(-1)"
                        @keydown.enter.prevent="abrirActivo"
                        @keydown.esc.prevent="cerrar"
                    >
                    <kbd class="hidden shrink-0 rounded-sm border border-line bg-surface-50 px-1.5 py-0.5 font-mono text-caption text-ink-600 sm:inline">
                        esc
                    </kbd>
                </div>

                <!-- Resultados -->
                <div class="scrollbar-fina scroll-suave-ios max-h-[55dvh] overflow-y-auto overscroll-contain">
                    <!-- Esqueletos mientras responde -->
                    <div v-if="buscando" class="space-y-2 p-4" aria-live="polite">
                        <div v-for="n in 3" :key="n" class="flex animate-pulse items-center gap-3">
                            <div class="h-9 w-9 shrink-0 rounded-md bg-surface-100" />
                            <div class="flex-1 space-y-1.5">
                                <div class="h-3 w-2/5 rounded-sm bg-surface-100" />
                                <div class="h-2.5 w-3/5 rounded-sm bg-surface-50" />
                            </div>
                        </div>
                    </div>

                    <ul v-else-if="resultados.length" class="divide-y divide-line">
                        <li v-for="(persona, i) in resultados" :key="persona.id">
                            <button
                                type="button"
                                class="flex w-full items-start gap-3 px-4 py-3 text-left transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-focus"
                                :class="i === indiceActivo ? 'bg-surface-brand' : 'hover:bg-surface-100'"
                                @click="abrirResultado(persona)"
                                @mouseenter="indiceActivo = i"
                            >
                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-surface-brand text-action">
                                    <IconoNav icono="user" class="h-4 w-4" />
                                </div>

                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="text-body-strong text-ink">
                                            {{ persona.nombre_completo }}
                                        </p>
                                        <span
                                            v-if="persona.demo"
                                            class="inline-flex h-5 items-center rounded-sm bg-warning-surface px-1.5 text-caption text-ink"
                                        >Demo</span>
                                        <span
                                            v-if="persona.estado_persona !== 'activa'"
                                            class="inline-flex h-5 items-center rounded-sm bg-surface-100 px-1.5 text-caption capitalize text-ink-600"
                                        >{{ persona.estado_persona }}</span>
                                    </div>

                                    <p class="mt-0.5 truncate text-small text-ink-600">
                                        <span v-if="persona.curp" class="font-mono">{{ persona.curp }}</span>
                                        <span v-if="persona.curp && persona.municipio"> · </span>
                                        <span v-if="persona.municipio">{{ persona.municipio }}</span>
                                        <span v-if="persona.telefono"> · <span class="font-mono">{{ persona.telefono }}</span></span>
                                    </p>

                                    <!-- Vínculos entre módulos: lo que hace 360° a este buscador -->
                                    <div v-if="persona.modulos.length" class="mt-1.5 flex flex-wrap gap-1">
                                        <span
                                            v-for="modulo in persona.modulos"
                                            :key="modulo.slug"
                                            class="inline-flex h-6 items-center gap-1 rounded-sm bg-surface-100 px-1.5 text-caption text-ink"
                                            :title="`${modulo.total} registro(s) en ${modulo.nombre}`"
                                        >
                                            <IconoModulo :icono="modulo.icono" class="h-3.5 w-3.5 text-ink-600" />
                                            {{ modulo.nombre }}
                                            <span class="font-mono text-ink-600">{{ modulo.total }}</span>
                                        </span>
                                    </div>
                                    <p v-else class="mt-1.5 text-caption text-ink-600">
                                        Sin registros en otros módulos
                                    </p>
                                </div>

                                <IconoNav icono="arrow" class="mt-2 h-4 w-4 shrink-0 text-ink-400" />
                            </button>
                        </li>
                    </ul>

                    <p v-else-if="termino.trim().length >= MINIMO" class="px-4 py-10 text-center text-body text-ink-600">
                        Nadie coincide con «{{ termino.trim() }}».
                    </p>

                    <div v-else class="px-4 py-10 text-center">
                        <IconoNav icono="buscar" class="mx-auto h-8 w-8 text-ink-400" />
                        <p class="mt-2 text-body text-ink-600">
                            Escribe al menos {{ MINIMO }} caracteres.
                        </p>
                        <p class="mt-1 text-small text-ink-600">
                            Busca por nombre, CURP, RFC, correo o teléfono.
                        </p>
                    </div>
                </div>

                <!-- Pie con ayuda de teclado -->
                <div class="mt-auto flex items-center justify-between gap-3 border-t border-line bg-surface-50 px-4 py-2 text-caption text-ink-600">
                    <span class="hidden items-center gap-3 sm:flex">
                        <span><kbd class="font-mono">↑ ↓</kbd> moverse</span>
                        <span><kbd class="font-mono">↵</kbd> abrir ficha</span>
                        <span><kbd class="font-mono">esc</kbd> cerrar</span>
                    </span>
                    <span v-if="truncado">
                        Mostrando {{ resultados.length }} de {{ total.toLocaleString('es-MX') }} coincidencias
                    </span>
                    <span v-else-if="total">
                        {{ total }} coincidencia{{ total === 1 ? '' : 's' }}
                    </span>
                </div>
            </div>
        </Modal>
    </div>
</template>
