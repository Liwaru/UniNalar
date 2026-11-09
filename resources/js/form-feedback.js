import './confirmation-dialog';

// Enhance normal POST forms without changing their action, fields or CSRF handling.
const pendingForms = new Map();

function restoreForms() {
    for (const [form, controls] of pendingForms) {
        form.removeAttribute('aria-busy');
        for (const [button, label] of controls) {
            button.disabled = false;
            button.innerHTML = label;
        }
        const status = form.querySelector('[data-submit-status], [data-ai-status]');
        if (status) status.textContent = '';
    }
    pendingForms.clear();
}

document.addEventListener('submit', (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || form.method.toLowerCase() !== 'post' || event.defaultPrevented) return;
    if (pendingForms.has(form)) {
        event.preventDefault();
        return;
    }

    const controls = [...form.querySelectorAll('button[type="submit"], button:not([type])')]
        .filter((button) => !button.disabled)
        .map((button) => [button, button.innerHTML]);
    pendingForms.set(form, controls);
    form.setAttribute('aria-busy', 'true');
    for (const [button] of controls) {
        button.disabled = true;
        if (form.matches('[data-ai-analysis]')) {
            const spinner = document.createElement('span');
            spinner.className = 'analysis-submit-spinner';
            spinner.setAttribute('aria-hidden', 'true');
            button.replaceChildren(spinner, document.createTextNode('Sedang menganalisis...'));
        } else {
            button.textContent = 'Memproses…';
        }
    }
    const status = form.querySelector('[data-submit-status], [data-ai-status]');
    if (status) status.textContent = form.matches('[data-ai-analysis]')
        ? 'Sedang memeriksa hasil tersimpan atau menyusun penjelasan. Anda tetap dapat membuka halaman lain.'
        : (form.dataset.submitMessage ?? 'Permintaan sedang diproses. Tunggu hingga halaman selesai dimuat.');
});

// A restored history entry must allow resubmission after a validation error/back.
window.addEventListener('pageshow', restoreForms);
document.querySelector('[data-validation-summary]')?.focus();
