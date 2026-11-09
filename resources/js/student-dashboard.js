import './form-feedback';
import 'bootstrap';
import Chart from 'chart.js/auto';

const analysisForm = document.querySelector('[data-ai-analysis]');

if (analysisForm) {
    const semester = analysisForm.querySelector('[name="semester_id"]');
    const consent = analysisForm.querySelector('[name="persetujuan"]');
    const submit = analysisForm.querySelector('button[type="submit"]');
    const isReady = () => analysisForm.dataset.aiEnabled === 'true'
        && semester.validity.valid && !semester.selectedOptions[0]?.disabled
        && consent.checked && !analysisForm.hasAttribute('aria-busy');
    const updateAnalysisButton = () => { submit.disabled = !isReady(); };

    analysisForm.addEventListener('input', updateAnalysisButton);
    analysisForm.addEventListener('change', updateAnalysisButton);
    analysisForm.addEventListener('submit', (event) => {
        if (!isReady()) event.preventDefault();
    });
    window.addEventListener('pageshow', updateAnalysisButton);
    updateAnalysisButton();
}

const canvas = document.getElementById('academicTrendChart');

const analysisChart = document.getElementById('analysisAcademicChart');
if (analysisChart) {
    const history = JSON.parse(document.getElementById('analysisAcademicData').textContent);
    const tokens = getComputedStyle(document.body);
    const primary = tokens.getPropertyValue('--un-color-primary').trim();
    const muted = tokens.getPropertyValue('--un-color-muted').trim();
    const divider = tokens.getPropertyValue('--un-color-divider').trim();
    new Chart(analysisChart, {
        type: 'line',
        data: {
            labels: history.map(row => row.semester),
            datasets: [
                { label: 'IPS semester', data: history.map(row => row.ips), borderColor: primary, backgroundColor: primary, pointStyle: 'circle' },
                { label: 'IPK hingga semester', data: history.map(row => row.ipk), borderColor: muted, backgroundColor: muted, borderDash: [5, 4], pointStyle: 'rectRot' },
            ].map(dataset => ({ ...dataset, borderWidth: 2, pointRadius: 4, pointHoverRadius: 6, tension: 0, spanGaps: false })),
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: false,
            interaction: { mode: 'index', intersect: false },
            plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, padding: 18, color: muted, font: { family: tokens.fontFamily, size: 12 } } } },
            scales: {
                x: { grid: { display: false }, ticks: { color: muted, font: { family: tokens.fontFamily, size: 11 } } },
                y: { min: 0, max: 4, title: { display: true, text: 'Indeks prestasi (0–4)', color: muted }, ticks: { stepSize: 1, color: muted }, grid: { color: divider } },
            },
        },
    });
}

if (canvas) {
    const style = getComputedStyle(canvas);
    const palette = { primary: style.getPropertyValue('--un-color-primary').trim(), muted: style.getPropertyValue('--un-color-muted').trim(), text: style.getPropertyValue('--un-color-text').trim(), divider: style.getPropertyValue('--un-color-divider').trim(), fill: style.getPropertyValue('--un-chart-fill').trim() };
    const fontFamily = getComputedStyle(document.body).fontFamily;
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
                    borderColor: palette.primary,
                    backgroundColor: palette.fill,
                    pointBackgroundColor: palette.primary,
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
            animation: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? false : undefined,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { usePointStyle: true, pointStyle: 'circle', boxWidth: 7, boxHeight: 7, padding: 18, color: palette.muted, font: { family: fontFamily, size: 12 } },
                },
                tooltip: { backgroundColor: palette.text, padding: 10, titleFont: { family: fontFamily }, bodyFont: { family: fontFamily } },
            },
            scales: {
                x: { grid: { display: false }, ticks: { color: palette.muted, font: { family: fontFamily, size: 12 } }, border: { display: false } },
                nilai: { type: 'linear', position: 'left', grid: { color: palette.divider }, ticks: { color: palette.muted, font: { family: fontFamily, size: 12 } }, border: { display: false } },
            },
        },
    });
}
