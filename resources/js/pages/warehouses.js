/**
 * Kayan ERP — Warehouses Page Interactions
 * Add and edit warehouse modals, form pre-population, validation reset
 */

export function initWarehousesPage() {
    const page = document.querySelector('[data-page="warehouses"]');
    if (!page) return;

    const addModal = page.querySelector('#addWarehouseModal');
    const editModal = page.querySelector('#editWarehouseModal');

    function openAddWarehouseModal() {
        if (addModal) {
            addModal.classList.add('open');
            addModal.style.display = 'flex';
        }
    }

    function closeAddWarehouseModal() {
        if (addModal) {
            addModal.classList.remove('open');
            addModal.style.display = 'none';
        }
    }

    function openEditWarehouseModal(warehouse) {
        const form = page.querySelector('#editWarehouseForm');
        if (!form) return;

        form.action = `/warehouses/${warehouse.id}`;
        const codeInput = page.querySelector('#edit_code');
        const nameInput = page.querySelector('#edit_name');
        const locInput = page.querySelector('#edit_location');
        const notesInput = page.querySelector('#edit_notes');
        const activeCb = page.querySelector('#edit_is_active');
        const defaultCb = page.querySelector('#edit_is_default');

        if (codeInput) codeInput.value = warehouse.code || '';
        if (nameInput) nameInput.value = warehouse.name || '';
        if (locInput) locInput.value = warehouse.location || '';
        if (notesInput) notesInput.value = warehouse.notes || '';
        if (activeCb) activeCb.checked = Boolean(warehouse.is_active);
        if (defaultCb) defaultCb.checked = Boolean(warehouse.is_default);

        if (editModal) {
            editModal.classList.add('open');
            editModal.style.display = 'flex';
        }
    }

    function closeEditWarehouseModal() {
        if (editModal) {
            editModal.classList.remove('open');
            editModal.style.display = 'none';
        }
    }

    document.querySelectorAll('[data-action="open-add-warehouse"]').forEach(btn => {
        btn.addEventListener('click', openAddWarehouseModal);
    });

    page.querySelectorAll('[data-action="close-add-warehouse"]').forEach(btn => {
        btn.addEventListener('click', closeAddWarehouseModal);
    });

    page.querySelectorAll('[data-action="open-edit-warehouse"]').forEach(btn => {
        btn.addEventListener('click', () => {
            const rawData = btn.dataset.warehouse;
            if (rawData) {
                try {
                    const warehouse = JSON.parse(rawData);
                    openEditWarehouseModal(warehouse);
                } catch (e) {
                    console.error('Failed to parse warehouse JSON', e);
                }
            }
        });
    });

    page.querySelectorAll('[data-action="close-edit-warehouse"]').forEach(btn => {
        btn.addEventListener('click', closeEditWarehouseModal);
    });

    [addModal, editModal].forEach(modal => {
        if (modal) {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.classList.remove('open');
                    modal.style.display = 'none';
                }
            });
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeAddWarehouseModal();
            closeEditWarehouseModal();
        }
    });
}
