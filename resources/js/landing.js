import { Collapse } from 'bootstrap';

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
