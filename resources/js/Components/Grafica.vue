<script setup>
import { onMounted, onUnmounted, ref, watch } from 'vue';
import {
    ArcElement,
    BarController,
    BarElement,
    CategoryScale,
    Chart,
    DoughnutController,
    Legend,
    LinearScale,
    LineController,
    LineElement,
    PointElement,
    Tooltip,
} from 'chart.js';
import { coloresGrafica, seriesDatos } from '@/paletaDatos';
import { observarTema } from '@/Composables/useTema';

/*
 * Registro selectivo de Chart.js.
 *
 * Se importan solo los controladores que estas consultas usan. El registro
 * completo (`chart.js/auto`) mete todo el catálogo de gráficas al bundle,
 * y aquí nadie dibuja radares ni burbujas.
 */
Chart.register(
    BarController, BarElement,
    LineController, LineElement, PointElement,
    DoughnutController, ArcElement,
    CategoryScale, LinearScale,
    Tooltip, Legend,
);

const props = defineProps({
    /** { tipo, etiquetas, series: [{ etiqueta, datos }] } */
    datos: { type: Object, default: null },
    alto: { type: String, default: 'h-64 sm:h-80' },
    // Descripcion para lectores de pantalla: el canvas no tiene texto.
    etiqueta: { type: String, default: 'Grafica de resultados' },
});

const lienzo = ref(null);
let grafica = null;
let dejarDeObservar = null;

/*
 * Colores: salen de las variables CSS del tema (paletaDatos.js), porque
 * Chart.js pinta en un canvas y no hereda CSS. Al cambiar de tema se vuelve
 * a construir la grafica con los colores nuevos.
 */
const FUENTE = 'Inter, ui-sans-serif, system-ui, sans-serif';
const FUENTE_MONO = '"JetBrains Mono", ui-monospace, monospace';

function construir() {
    destruir();

    if (!props.datos || !lienzo.value) return;

    const esCircular = props.datos.tipo === 'doughnut' || props.datos.tipo === 'pie';
    const PALETA = seriesDatos();
    const color = coloresGrafica();

    grafica = new Chart(lienzo.value, {
        type: props.datos.tipo || 'bar',
        data: {
            labels: props.datos.etiquetas,
            datasets: props.datos.series.map((serie, i) => ({
                label: serie.etiqueta,
                data: serie.datos,
                backgroundColor: esCircular
                    ? props.datos.etiquetas.map((_, j) => PALETA[j % PALETA.length])
                    : PALETA[i % PALETA.length],
                // En las circulares el borde es el lienzo: separa las rebanadas.
                borderColor: esCircular ? color.lienzo : PALETA[i % PALETA.length],
                borderWidth: esCircular ? 2 : 0,
                borderRadius: esCircular ? 0 : 4,
                maxBarThickness: 44,
            })),
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            color: color.texto,
            font: { family: FUENTE },
            plugins: {
                // Con una sola serie la leyenda solo repite el título.
                legend: {
                    display: esCircular || props.datos.series.length > 1,
                    position: esCircular ? 'right' : 'top',
                    labels: { usePointStyle: true, boxWidth: 8, padding: 16, color: color.texto, font: { family: FUENTE } },
                },
                tooltip: {
                    backgroundColor: color.tooltipFondo,
                    titleColor: color.tooltipTexto,
                    bodyColor: color.tooltipTexto,
                    padding: 10,
                    cornerRadius: 8,
                    titleFont: { family: FUENTE },
                    bodyFont: { family: FUENTE_MONO },
                    callbacks: {
                        label: (contexto) => {
                            const valor = contexto.parsed.y ?? contexto.parsed;
                            const etiqueta = contexto.dataset.label ?? '';
                            return ` ${etiqueta}: ${Number(valor).toLocaleString('es-MX')}`;
                        },
                    },
                },
            },
            scales: esCircular ? {} : {
                x: {
                    grid: { display: false },
                    ticks: { autoSkip: false, maxRotation: 60, minRotation: 0, color: color.texto, font: { family: FUENTE } },
                },
                y: {
                    beginAtZero: true,
                    grid: { color: color.rejilla },
                    border: { display: false },
                    ticks: { precision: 0, color: color.texto, font: { family: FUENTE_MONO }, callback: (v) => Number(v).toLocaleString('es-MX') },
                },
            },
        },
    });
}

function destruir() {
    grafica?.destroy();
    grafica = null;
}

onMounted(() => {
    construir();
    dejarDeObservar = observarTema(construir);
});
onUnmounted(() => {
    dejarDeObservar?.();
    destruir();
});
watch(() => props.datos, construir, { deep: true });
</script>

<template>
    <div v-if="datos" class="rounded-lg border border-line bg-surface p-4 shadow-sm sm:p-5">
        <div class="relative" :class="alto">
            <canvas ref="lienzo" role="img" :aria-label="etiqueta" />
        </div>
    </div>
</template>
