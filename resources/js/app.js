import Sortable from 'sortablejs';
import Chart from 'chart.js/auto';

window.Sortable = Sortable;
window.Chart = Chart;

const chartPalette = [
    '#0f766e',
    '#2563eb',
    '#7c3aed',
    '#ca8a04',
    '#dc2626',
    '#0891b2',
    '#65a30d',
    '#c2410c',
];

function reportChartOptions(type) {
    const baseOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: type === 'doughnut' ? 'bottom' : 'top',
            },
        },
    };

    if (type === 'doughnut') {
        return baseOptions;
    }

    return {
        ...baseOptions,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: (value) => Number(value).toLocaleString(),
                },
            },
        },
    };
}

function readReportChartData(canvas) {
    const source = document.getElementById(`${canvas.id}Data`);

    if (!source) {
        return {};
    }

    try {
        return JSON.parse(source.textContent || '{}');
    } catch (error) {
        console.warn(`Could not parse chart data for ${canvas.id}`, error);

        return {};
    }
}

function buildReportDataset(canvas, values, type) {
    const colors = type === 'doughnut'
        ? chartPalette.slice(0, Math.max(Object.keys(values).length, 1))
        : canvas.dataset.chartColor || chartPalette[0];

    return {
        label: canvas.dataset.chartLabel || '',
        data: Object.values(values).map((value) => Number(value) || 0),
        backgroundColor: colors,
        borderColor: type === 'line' ? canvas.dataset.chartColor || chartPalette[0] : undefined,
        tension: type === 'line' ? 0.3 : undefined,
        fill: type === 'line' ? false : undefined,
    };
}

function initializeReportCharts() {
    document.querySelectorAll('[data-report-chart]').forEach((canvas) => {
        if (!(canvas instanceof HTMLCanvasElement)) {
            return;
        }

        const type = canvas.dataset.chartType || 'bar';
        const values = readReportChartData(canvas);

        Chart.getChart(canvas)?.destroy();

        new Chart(canvas, {
            type,
            data: {
                labels: Object.keys(values),
                datasets: [buildReportDataset(canvas, values, type)],
            },
            options: reportChartOptions(type),
        });
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeReportCharts);
} else {
    queueMicrotask(initializeReportCharts);
}

document.addEventListener('livewire:navigated', () => queueMicrotask(initializeReportCharts));
