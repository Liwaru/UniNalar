import './form-feedback';
const passwordInput = document.getElementById('password');
const passwordToggle = document.querySelector('.password-toggle');

if (passwordInput && passwordToggle) {
    passwordToggle.addEventListener('click', () => {
        const willReveal = passwordInput.type === 'password';

        passwordInput.type = willReveal ? 'text' : 'password';
        passwordToggle.setAttribute('aria-pressed', String(willReveal));
        passwordToggle.setAttribute('aria-label', willReveal ? 'Sembunyikan password' : 'Tampilkan password');
    });
}

const helpDialog = document.getElementById('accountHelpDialog');

if (helpDialog instanceof HTMLDialogElement) {
    document.querySelector('[data-open-help]')?.addEventListener('click', () => helpDialog.showModal());

    document.querySelectorAll('[data-close-help]').forEach((button) => {
        button.addEventListener('click', () => helpDialog.close());
    });

    helpDialog.addEventListener('click', (event) => {
        if (event.target === helpDialog) helpDialog.close();
    });
}
