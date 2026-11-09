// Progressive enhancement: without Popover support, existing inline actions remain.
if ('showPopover' in HTMLElement.prototype) {
    let dialogReturnTarget = null;
    for (const panel of document.querySelectorAll('[data-actions-panel]')) {
        const button = panel.closest('[data-record-actions]').querySelector('[data-actions-toggle]');
        if (!button) continue;
        panel.setAttribute('popover', 'auto');
        button.setAttribute('popovertarget', panel.id);
        button.hidden = false;
        const position = () => {
            const rect = button.getBoundingClientRect();
            panel.style.left = `${Math.max(8, Math.min(rect.right - 160, innerWidth - 168))}px`;
            panel.style.top = `${rect.bottom + 8 + 112 > innerHeight ? Math.max(8, rect.top - 120) : rect.bottom + 8}px`;
        };
        panel.addEventListener('beforetoggle', (event) => {
            if (event.newState === 'open') position();
        });
        panel.addEventListener('toggle', () => button.setAttribute('aria-expanded', String(panel.matches(':popover-open'))));
        panel.addEventListener('click', (event) => {
            if (event.target instanceof Element && event.target.closest('[data-confirm-trigger]')) {
                dialogReturnTarget = button;
                panel.hidePopover();
            }
        }, true);
        const reposition = () => { if (panel.matches(':popover-open')) position(); };
        window.addEventListener('resize', reposition);
        window.addEventListener('scroll', reposition, { passive: true });
        panel.closest('.admin-table-wrap')?.addEventListener('scroll', reposition, { passive: true });
    }
    document.getElementById('uninalar-confirmation')?.addEventListener('close', () => {
        if (dialogReturnTarget) {
            const target = dialogReturnTarget;
            dialogReturnTarget = null;
            queueMicrotask(() => target.focus());
        }
    });
}
