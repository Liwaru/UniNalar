// All confirmed actions keep their original form, CSRF and method override.
const dialog = document.getElementById('uninalar-confirmation');
if (dialog && typeof dialog.showModal === 'function') {
    const cancel = dialog.querySelector('[data-confirm-cancel]');
    const accept = dialog.querySelector('[data-confirm-accept]');
    const approved = new WeakSet();
    const pending = new Set();
    let current = null;

    const close = () => {
        const trigger = current?.trigger;
        current = null;
        dialog.close();
        if (trigger?.isConnected) trigger.focus();
    };
    const open = (form, trigger) => {
        if (dialog.open || pending.has(form) || !form.reportValidity()) return;
        current = { form, trigger };
        dialog.querySelector('#confirmation-title').textContent = trigger.dataset.confirmTitle;
        dialog.querySelector('#confirmation-description').textContent = trigger.dataset.confirmDescription;
        dialog.querySelector('#confirmation-record').textContent = trigger.dataset.confirmRecord;
        accept.textContent = trigger.textContent;
        accept.classList.toggle('is-danger', trigger.dataset.confirmTone === 'danger');
        accept.disabled = false;
        dialog.showModal();
        cancel.focus();
    };
    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || !form.matches('[data-confirm-form]')) return;
        if (pending.has(form)) { event.preventDefault(); return; }
        if (approved.has(form)) {
            approved.delete(form);
            pending.add(form);
            return;
        }
        event.preventDefault();
        const trigger = form.querySelector('[data-confirm-trigger]');
        if (trigger && !trigger.disabled) open(form, trigger);
    }, true);
    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-confirm-trigger]');
        if (trigger && !trigger.disabled && trigger.form) open(trigger.form, trigger);
    });
    cancel.addEventListener('click', close);
    dialog.addEventListener('cancel', (event) => { event.preventDefault(); close(); });
    dialog.addEventListener('click', (event) => {
        const bounds = dialog.getBoundingClientRect();
        if (event.target === dialog && (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom)) close();
    });
    dialog.addEventListener('keydown', (event) => {
        if (event.key !== 'Tab') return;
        const first = cancel, last = accept.disabled ? cancel : accept;
        if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
        else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
    });
    accept.addEventListener('click', () => {
        if (!current || accept.disabled) return;
        const { form, trigger } = current;
        accept.disabled = true;
        close();
        approved.add(form);
        trigger.disabled = true;
        form.requestSubmit();
        queueMicrotask(() => {
            // Validation or another application guard may have cancelled submission.
            if (!form.hasAttribute('aria-busy')) {
                approved.delete(form);
                pending.delete(form);
                trigger.disabled = false;
            }
        });
    });
    const initialize = () => {
        for (const form of document.querySelectorAll('[data-confirm-form]')) {
            form.inert = false;
            form.querySelectorAll('[data-confirm-trigger]').forEach(button => { button.disabled = false; });
        }
    };
    window.addEventListener('pageshow', () => { pending.clear(); initialize(); });
    // Controls stay inert/disabled if this module or native dialog support fails.
    initialize();
}
