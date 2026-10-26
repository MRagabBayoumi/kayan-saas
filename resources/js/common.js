/**
 * Kayan ERP — Common Global UI Helpers
 * Auto-submit selects, confirmation dialogs, modal escape listener
 */

export function initCommonUI() {
    if (window.__commonUiInitialized) return;
    window.__commonUiInitialized = true;

    // 1. Auto-submit on change
    document.addEventListener('change', (e) => {
        if (e.target.matches('[data-action="auto-submit"]')) {
            e.target.form?.submit();
        }
    });

    // 2. Data confirmation dialog
    document.addEventListener('submit', (e) => {
        const form = e.target;
        const confirmMsg = form.getAttribute('data-confirm');
        if (confirmMsg) {
            if (!window.confirm(confirmMsg)) {
                e.preventDefault();
            }
        }
    });

    document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-confirm]');
        if (btn && btn.type !== 'submit') {
            const confirmMsg = btn.getAttribute('data-confirm');
            if (confirmMsg) {
                if (!window.confirm(confirmMsg)) {
                    e.preventDefault();
                }
            }
        }
    });

    // 3. Password visibility toggle
    document.addEventListener('click', (e) => {
        const toggleBtn = e.target.closest('[data-action="toggle-password"]');
        if (!toggleBtn) return;

        const targetId = toggleBtn.getAttribute('data-target');
        const input = targetId ? document.getElementById(targetId) : null;
        if (input) {
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            toggleBtn.setAttribute('aria-pressed', isPassword ? 'true' : 'false');
            toggleBtn.classList.toggle('is-visible', isPassword);
        }
    });

    // 4. Toggle Module Permissions in Roles Matrix
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-action="toggle-module-permissions"]');
        if (!btn) return;

        const moduleCard = btn.closest('.role-matrix-module');
        if (!moduleCard) return;

        const checkboxes = moduleCard.querySelectorAll('input[type="checkbox"][name="permissions[]"]');
        if (!checkboxes.length) return;

        const allChecked = Array.from(checkboxes).every(cb => cb.checked);
        checkboxes.forEach(cb => {
            cb.checked = !allChecked;
            cb.dispatchEvent(new Event('change', { bubbles: true }));
        });

        const span = btn.querySelector('span');
        if (span) {
            span.textContent = allChecked ? 'تحديد الكل' : 'إلغاء التحديد';
        }
    });

    // 5. Initialize Universal Print Modal
    initUniversalPrintModal();
}

/**
 * Universal Print Modal Controller
 * Binds [data-action="open-print-modal"] triggers to the global modal
 */
export function initUniversalPrintModal() {
    if (window.__universalPrintModalInitialized) return;
    window.__universalPrintModalInitialized = true;

    document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-action="open-print-modal"]');
        if (!btn) return;
        e.preventDefault();

        const modal = document.getElementById('universalPrintModal');
        if (!modal) return;

        const title = btn.dataset.printTitle || 'خيارات طباعة المستند';
        const urlA4 = btn.dataset.urlA4 || '#';
        const urlA5 = btn.dataset.urlA5 || '#';
        const urlThermal = btn.dataset.urlThermal || '#';
        const directA4 = btn.dataset.directUrlA4 || '';
        const directA5 = btn.dataset.directUrlA5 || '';
        const directThermal = btn.dataset.directUrlThermal || '';

        const titleEl = modal.querySelector('#printModalDocTitle');
        if (titleEl) titleEl.textContent = title;

        const linkA4 = modal.querySelector('#printModalLinkA4');
        if (linkA4) linkA4.href = urlA4;

        const linkA5 = modal.querySelector('#printModalLinkA5');
        if (linkA5) linkA5.href = urlA5;

        const linkThermal = modal.querySelector('#printModalLinkThermal');
        if (linkThermal) linkThermal.href = urlThermal;

        const btnDirectA4 = modal.querySelector('#printModalDirectA4');
        if (btnDirectA4) {
            btnDirectA4.dataset.url = directA4;
            btnDirectA4.style.display = directA4 ? 'inline-flex' : 'none';
        }

        const btnDirectA5 = modal.querySelector('#printModalDirectA5');
        if (btnDirectA5) {
            btnDirectA5.dataset.url = directA5;
            btnDirectA5.style.display = directA5 ? 'inline-flex' : 'none';
        }

        const btnDirectThermal = modal.querySelector('#printModalDirectThermal');
        if (btnDirectThermal) {
            btnDirectThermal.dataset.url = directThermal;
            btnDirectThermal.style.display = directThermal ? 'inline-flex' : 'none';
        }

        modal.classList.add('open');
        modal.setAttribute('aria-hidden', 'false');
    });

    // Close Modal triggers
    document.addEventListener('click', (e) => {
        if (e.target.closest('[data-action="close-print-modal"]') || e.target.id === 'universalPrintModal') {
            const modal = document.getElementById('universalPrintModal');
            if (modal) {
                modal.classList.remove('open');
                modal.setAttribute('aria-hidden', 'true');
            }
        }
    });

    // Close on Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            const modal = document.getElementById('universalPrintModal');
            if (modal && modal.classList.contains('open')) {
                modal.classList.remove('open');
                modal.setAttribute('aria-hidden', 'true');
            }
        }
    });
}

