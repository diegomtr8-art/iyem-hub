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
});

const lienzo = ref(null);
let grafica = null;

/**
 * Paleta institucional.
 *
 * El guinda encabeza y el dorado acompaña; el resto son variaciones de la
 * misma escala. Nada de arcoíris: en un tablero de gobierno, el color debe
 * distinguir series, no llamar la atención.
 */
const PALETA = ['#691C32', '#BC955C', '#9F2241', '#C0728A', '#1F7A5C', '#B45309', '#4D1526'];

Chart.defaults.font.family = 'Arial, Helvetica, sans-serif';
Chart.defaults.color = '#6B7280';

function construir() {
    destruir();

    if (!props.datos || !lienzo.value) return;

    const esCircular = props.datos.tipo === 'doughnut' || props.datos.tipo === 'pie';

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
                borderColor: PALETA[i % PALETA.length],
                borderWidth: esCircular ? 2 : 0,
                borderRadius: esCircular ? 0 : 6,
                maxBarThickness: 44,
            })),
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                // Con una sola serie la leyenda solo repite el título.
                legend: {
                    display: esCircular || props.datos.series.length > 1,
                    position: esCircular ? 'right' : 'top',
                    labels: { usePointStyle: true, boxWidth: 8, padding: 16 },
                },
                tooltip: {
                    backgroundColor: '#271217',
                    padding: 10,
                    cornerRadius: 8,
                    titleFont: { family: 'Arial' },
                    bodyFont: { family: 'Arial' },
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
                    ticks: { autoSkip: false, maxRotation: 60, minRotation: 0 },
                },
                y: {
                    beginAtZero: true,
                    grid: { color: '#F1F1F3' },
                    border: { display: false },
                    ticks: { precision: 0, callback: (v) => Number(v).toLocaleString('es-MX') },
                },
            },
        },
    });
}

function destruir() {
    grafica?.destroy();
    grafica = null;
}

onMounted(construir);
onUnmounted(destruir);
watch(() => props.datos, construir, { deep: true });
</script>

<template>
    <div v-if="datos" class="rounded-2xl border border-iyem-200 bg-white p-4 shadow-soft sm:p-5">
        <div class="relative" :class="alto">
            <canvas ref="lienzo" />
        </div>
    </div>
</template>
