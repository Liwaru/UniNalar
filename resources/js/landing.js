import { Collapse } from 'bootstrap';
import Chart from 'chart.js/auto';

const currentUrl = new URL(window.location.href);

if (currentUrl.searchParams.has('utm_source')) {
    const preservedParams = [...currentUrl.searchParams]
        .filter(([key]) => key !== 'utm_source');

    currentUrl.search = '';
    preservedParams.forEach(([key, value]) => currentUrl.searchParams.append(key, value));

    window.history.replaceState(
        window.history.state,
        '',
        `${currentUrl.pathname}${currentUrl.search}${currentUrl.hash}`,
    );
}

const palette = {
    navy: '#14213D',
    blue: '#2563EB',
    teal: '#14B8A6',
    amber: '#F0AD4E',
    muted: '#9AA6B6',
};

const trendCanvas = document.getElementById('riskTrendChart');

if (trendCanvas) {
    new Chart(trendCanvas, {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun'],
            datasets: [
                {
                    label: 'Perlu perhatian (simulasi)',
                    data: [21, 24, 20, 29, 25, 18],
                    borderColor: palette.blue,
                    backgroundColor: 'rgba(37, 99, 235, .08)',
                    fill: true,
                    tension: 0.38,
                    borderWidth: 2,
                    pointRadius: 2.5,
                    pointHoverRadius: 4,
                },
                {
                    label: 'Stabil (simulasi)',
                    data: [72, 69, 74, 65, 70, 78],
                    borderColor: palette.teal,
                    backgroundColor: 'transparent',
                    fill: false,
                    tension: 0.38,
                    borderWidth: 2,
                    pointRadius: 2.5,
                    pointHoverRadius: 4,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { intersect: false, mode: 'index' },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: palette.navy,
                    padding: 10,
                    titleFont: { family: 'Plus Jakarta Sans', size: 10 },
                    bodyFont: { family: 'Plus Jakarta Sans', size: 9 },
                },
            },
            scales: {
                x: {
                    grid: { display: false },
                    border: { display: false },
                    ticks: { color: palette.muted, font: { family: 'Plus Jakarta Sans', size: 8 } },
                },
                y: {
                    beginAtZero: true,
                    max: 100,
                    grid: { color: '#edf1f6', drawTicks: false },
                    border: { display: false },
                    ticks: {
                        stepSize: 25,
                        color: palette.muted,
                        padding: 8,
                        font: { family: 'Plus Jakarta Sans', size: 8 },
                        callback: (value) => `${value}%`,
                    },
                },
            },
        },
    });
}

const distributionCanvas = document.getElementById('riskDistributionChart');

if (distributionCanvas) {
    new Chart(distributionCanvas, {
        type: 'doughnut',
        data: {
            labels: ['Stabil (simulasi)', 'Pantau (simulasi)', 'Prioritas (simulasi)'],
            datasets: [{
                data: [64, 25, 11],
                backgroundColor: [palette.teal, palette.blue, palette.amber],
                borderWidth: 0,
                hoverOffset: 3,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '74%',
            plugins: { legend: { display: false }, tooltip: { enabled: true } },
        },
    });
}

const sectionNavigation = document.getElementById('sectionNavigation');

if (sectionNavigation) {
    const sectionLinks = [...sectionNavigation.querySelectorAll('a[href^="#"]')];
    const sectionHeadings = sectionLinks
        .map((link) => document.getElementById(decodeURIComponent(link.hash.slice(1))))
        .filter(Boolean);
    const siteHeader = document.querySelector('.site-header');
    const mobileMenu = document.getElementById('mainNavigation');
    let pendingNavigation = null;

    mobileMenu?.addEventListener('shown.bs.collapse', () => {
        if (pendingNavigation) Collapse.getOrCreateInstance(mobileMenu).hide();
    });
    mobileMenu?.addEventListener('hidden.bs.collapse', () => {
        if (!pendingNavigation) return;
        const { target, hash } = pendingNavigation;
        pendingNavigation = null;
        window.history.pushState(null, '', hash);
        target.scrollIntoView({ behavior: 'smooth' });
    });

    sectionLinks.forEach((link) => link.addEventListener('click', (event) => {
        if (!window.matchMedia('(max-width: 991.98px)').matches || !mobileMenu
            || !(mobileMenu.classList.contains('show') || mobileMenu.classList.contains('collapsing'))) return;
        const target = document.getElementById(link.hash.slice(1));
        if (!target) return;
        event.preventDefault();
        pendingNavigation = { target, hash: link.hash };
        if (mobileMenu.classList.contains('show')) Collapse.getOrCreateInstance(mobileMenu).hide();
    }));

    const setActiveSection = (sectionId) => {
        sectionLinks.forEach((link) => {
            const isActive = link.hash === `#${sectionId}`;

            link.classList.toggle('active', isActive);

            if (isActive) {
                link.setAttribute('aria-current', 'location');
            } else {
                link.removeAttribute('aria-current');
            }
        });
    };

    const updateActiveSection = () => {
        const anchorOffset = sectionHeadings[0] ? parseFloat(getComputedStyle(sectionHeadings[0]).scrollMarginTop) || 0 : 0;
        const activationLine = Math.max((siteHeader?.getBoundingClientRect().bottom ?? 0) + 12, anchorOffset + 1);
        const activeHeading = sectionHeadings
            .filter((heading) => heading.getBoundingClientRect().top <= activationLine)
            .at(-1);

        setActiveSection(activeHeading?.id ?? sectionHeadings[0]?.id ?? 'beranda');
    };

    let scrollFrame = null;
    const scheduleActiveSectionUpdate = () => {
        if (scrollFrame !== null) return;

        scrollFrame = window.requestAnimationFrame(() => {
            updateActiveSection();
            scrollFrame = null;
        });
    };

    window.addEventListener('scroll', scheduleActiveSectionUpdate, { passive: true });
    window.addEventListener('resize', scheduleActiveSectionUpdate);
    window.addEventListener('hashchange', scheduleActiveSectionUpdate);
    scheduleActiveSectionUpdate();
}
