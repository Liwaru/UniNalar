import 'bootstrap';
import Chart from 'chart.js/auto';

const canvas = document.getElementById('academicTrendChart');

if (canvas) {
    const dataElement = document.getElementById('academicTrendData');
    const trend = JSON.parse(dataElement?.textContent ?? '{}');

    new Chart(canvas, {
        type: 'line',
        data: {
            labels: trend.labels ?? [],
            datasets: [
                {
                    label: 'Rata-rata nilai',
                    data: trend.nilai ?? [],
                    yAxisID: 'nilai',
                    borderColor: '#2563eb',
                    backgroundColor: 'rgba(37, 99, 235, .1)',
                    pointBackgroundColor: '#2563eb',
                    pointRadius: 3,
                    borderWidth: 2,
                    tension: .32,
                    spanGaps: true,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { usePointStyle: true, pointStyle: 'circle', boxWidth: 7, boxHeight: 7, padding: 18, color: '#738198', font: { family: 'Plus Jakarta Sans', size: 10 } },
                },
                tooltip: { backgroundColor: '#14213d', padding: 10, titleFont: { family: 'Plus Jakarta Sans' }, bodyFont: { family: 'Plus Jakarta Sans' } },
            },
            scales: {
                x: { grid: { display: false }, ticks: { color: '#8b97a8', font: { family: 'Plus Jakarta Sans', size: 9 } }, border: { display: false } },
                nilai: { type: 'linear', position: 'left', grid: { color: '#eef1f5' }, ticks: { color: '#8b97a8', font: { family: 'Plus Jakarta Sans', size: 9 } }, border: { display: false } },
            },
        },
    });
}

