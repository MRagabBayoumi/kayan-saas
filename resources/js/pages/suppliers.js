/**
 * Kayan ERP — Suppliers Page Client Logic
 * Add/Edit supplier modal management, pre-population, duplicate submit protection, and keyboard dismiss
 */

export function initSuppliersPage() {
    const page = document.querySelector('[data-page="suppliers"]');
    if (!page) return;

    const addModal = page.querySelector('#addSupplierModal');
    const editModal = page.querySelector('#editSupplierModal');

    // Toggle China specific fields for Add Modal
    function updateAddModalChinaFields(type) {
        const chinaBox = page.querySelector('#add_china_fields');
        const currencySelect = page.querySelector('#add_sup_currency');
        const countryInput = page.querySelector('#add_sup_country');

        if (type === 'china_factory') {
            if (chinaBox) chinaBox.style.display = 'block';
            if (currencySelect && (currencySelect.value === 'EGP' || !currencySelect.value)) {
                currencySelect.value = 'CNY';
            }
            if (countryInput && (!countryInput.value || countryInput.value === 'مصر')) {
                countryInput.value = 'China';
            }
        } else {
            if (chinaBox) chinaBox.style.display = 'none';
            if (currencySelect && (currencySelect.value === 'CNY' || !currencySelect.value)) {
                currencySelect.value = 'EGP';
            }
            if (countryInput && (!countryInput.value || countryInput.value === 'China')) {
                countryInput.value = 'مصر';
            }
        }
    }

    // Toggle China specific fields for Edit Modal
    function updateEditModalChinaFields(type) {
        const chinaBox = page.querySelector('#edit_china_fields');
        const currencySelect = page.querySelector('#edit_sup_currency');
        const countryInput = page.querySelector('#edit_sup_country');

        if (type === 'china_factory') {
            if (chinaBox) chinaBox.style.display = 'block';
            if (currencySelect && (currencySelect.value === 'EGP' || !currencySelect.value)) {
                currencySelect.value = 'CNY';
            }
            if (countryInput && (!countryInput.value || countryInput.value === 'مصر')) {
                countryInput.value = 'China';
            }
        } else {
            if (chinaBox) chinaBox.style.display = 'none';
            if (currencySelect && (currencySelect.value === 'CNY' || !currencySelect.value)) {
                currencySelect.value = 'EGP';
            }
            if (countryInput && (!countryInput.value || countryInput.value === 'China')) {
                countryInput.value = 'مصر';
            }
        }
    }

    const addTypeSelect = page.querySelector('#add_sup_supplier_type');
    if (addTypeSelect) {
        addTypeSelect.addEventListener('change', () => {
            updateAddModalChinaFields(addTypeSelect.value);
        });
    }

    const editTypeSelect = page.querySelector('#edit_sup_supplier_type');
    if (editTypeSelect) {
        editTypeSelect.addEventListener('change', () => {
            updateEditModalChinaFields(editTypeSelect.value);
        });
    }

    function openAddModal() {
        if (addModal) {
            const addForm = addModal.querySelector('form');
            if (addForm) {
                addForm.reset();
            }
            if (addTypeSelect) {
                addTypeSelect.value = 'local_supplier';
            }
            updateAddModalChinaFields('local_supplier');

            addModal.classList.add('open');
            addModal.style.display = 'flex';
            const firstInput = addModal.querySelector('input[name="name"]');
            if (firstInput) firstInput.focus();
        }
    }

    function closeAddModal() {
        if (addModal) {
            addModal.classList.remove('open');
            addModal.style.display = 'none';
        }
    }

    function openEditModal(supplier) {
        const form = page.querySelector('#editSupplierForm');
        if (!form) return;

        form.action = `/suppliers/${supplier.id}`;

        const setVal = (id, val) => {
            const el = page.querySelector(id);
            if (el) el.value = val ?? '';
        };

        const supplierType = supplier.supplier_type || 'local_supplier';
        setVal('#edit_sup_name', supplier.name);
        setVal('#edit_sup_supplier_type', supplierType);
        setVal('#edit_sup_contact_person', supplier.contact_person);
        setVal('#edit_sup_wechat_id', supplier.wechat_id);
        setVal('#edit_sup_phone', supplier.phone);
        setVal('#edit_sup_email', supplier.email);
        setVal('#edit_sup_country', supplier.country || (supplierType === 'china_factory' ? 'China' : 'مصر'));
        setVal('#edit_sup_city', supplier.city);
        setVal('#edit_sup_currency', supplier.currency || (supplierType === 'china_factory' ? 'CNY' : 'EGP'));
        setVal('#edit_sup_current_balance', supplier.current_balance || 0);
        setVal('#edit_sup_address', supplier.address);
        setVal('#edit_sup_notes', supplier.notes);

        const activeCb = page.querySelector('#edit_sup_is_active');
        if (activeCb) {
            activeCb.checked = Boolean(supplier.is_active);
        }

        const chinaBox = page.querySelector('#edit_china_fields');
        if (chinaBox) {
            chinaBox.style.display = (supplierType === 'china_factory') ? 'block' : 'none';
        }

        if (editModal) {
            editModal.classList.add('open');
            editModal.style.display = 'flex';
            const firstInput = editModal.querySelector('#edit_sup_name');
            if (firstInput) firstInput.focus();
        }
    }

    function closeEditModal() {
        if (editModal) {
            editModal.classList.remove('open');
            editModal.style.display = 'none';
        }
    }

    // Attach listeners on document to catch control-panel action buttons
    document.querySelectorAll('[data-action="open-add-supplier"]').forEach(btn => {
        btn.addEventListener('click', openAddModal);
    });

    document.querySelectorAll('[data-action="close-add-supplier"]').forEach(btn => {
        btn.addEventListener('click', closeAddModal);
    });

    document.querySelectorAll('[data-action="open-edit-supplier"]').forEach(btn => {
        btn.addEventListener('click', () => {
            const raw = btn.dataset.supplier;
            if (raw) {
                try {
                    const data = JSON.parse(raw);
                    openEditModal(data);
                } catch (e) {
                    console.error('Failed to parse supplier data JSON', e);
                }
            }
        });
    });

    document.querySelectorAll('[data-action="close-edit-supplier"]').forEach(btn => {
        btn.addEventListener('click', closeEditModal);
    });

    // Close on backdrop click
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

    // Close on Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeAddModal();
            closeEditModal();
        }
    });

    // Protection against duplicate form submissions
    const bindFormSubmitProtection = (formSelector) => {
        const form = page.querySelector(formSelector);
        if (!form) return;

        form.addEventListener('submit', function (e) {
            if (!form.checkValidity()) {
                return;
            }
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                if (submitBtn.disabled) {
                    e.preventDefault();
                    return false;
                }
                submitBtn.disabled = true;
                submitBtn.dataset.originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<span>جاري التسجيل...</span>';

                // Safety re-enable after 6 seconds
                setTimeout(() => {
                    if (submitBtn && submitBtn.disabled) {
                        submitBtn.disabled = false;
                        if (submitBtn.dataset.originalText) {
                            submitBtn.innerHTML = submitBtn.dataset.originalText;
                        }
                    }
                }, 6000);
            }
        });
    };

    bindFormSubmitProtection('#addSupplierModal form');
    bindFormSubmitProtection('#editSupplierForm');
}
