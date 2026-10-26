/**
 * Kayan ERP — Sales Representatives Page Client Logic
 * Add/Edit sales rep modal management, pre-population, and keyboard dismiss
 */

export function initSalesRepsPage() {
    const page = document.querySelector('[data-page="sales-reps"]');
    if (!page) return;

    const addModal = document.getElementById('addRepModal');
    const editModal = document.getElementById('editRepModal');

    window.openAddRepModal = function () {
        if (addModal) {
            addModal.classList.add('open');
            addModal.classList.remove('hidden');
            addModal.style.display = 'flex';
            const firstInput = addModal.querySelector('input[name="name"]');
            if (firstInput) firstInput.focus();
        }
    };

    window.closeRepModal = function (modalId) {
        const modal = typeof modalId === 'string' ? document.getElementById(modalId) : modalId;
        if (modal) {
            modal.classList.remove('open');
            modal.classList.add('hidden');
            modal.style.display = 'none';
        }
    };

    window.openEditRepModal = function (btn) {
        if (!editModal) return;

        const repId = btn.getAttribute('data-rep-id');
        const form = document.getElementById('editRepForm');
        if (form) {
            form.action = `/sales-reps/${repId}`;
        }

        const setVal = (id, val) => {
            const el = document.getElementById(id);
            if (el) el.value = val ?? '';
        };

        setVal('editRepName', btn.getAttribute('data-rep-name') || '');
        setVal('editRepUsername', btn.getAttribute('data-rep-username') || '');
        setVal('editRepEmail', btn.getAttribute('data-rep-email') || '');
        setVal('editRepPhone', btn.getAttribute('data-rep-phone') || '');
        setVal('editRepCommissionRate', btn.getAttribute('data-rep-commission-rate') || 0);
        setVal('editRepCommissionType', btn.getAttribute('data-rep-commission-type') || 'percentage');
        setVal('editRepWarehouseId', btn.getAttribute('data-rep-warehouse-id') || '');

        const activeCb = document.getElementById('editRepActive');
        if (activeCb) {
            activeCb.checked = btn.getAttribute('data-rep-active') === '1';
        }

        editModal.classList.add('open');
        editModal.classList.remove('hidden');
        editModal.style.display = 'flex';

        const nameInput = document.getElementById('editRepName');
        if (nameInput) nameInput.focus();
    };

    // Attach listeners to buttons
    document.querySelectorAll('[data-action="open-add-rep-modal"]').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            window.openAddRepModal();
        });
    });

    document.querySelectorAll('[data-action="open-edit-rep-modal"]').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            window.openEditRepModal(btn);
        });
    });

    document.querySelectorAll('[data-action="close-modal"]').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const target = btn.getAttribute('data-target');
            window.closeRepModal(target);
        });
    });

    // Close on backdrop click
    [addModal, editModal].forEach(modal => {
        if (modal) {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    window.closeRepModal(modal);
                }
            });
        }
    });

    // Close on Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            window.closeRepModal('addRepModal');
            window.closeRepModal('editRepModal');
        }
    });
}
