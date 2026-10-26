/**
 * Kayan ERP — Customers Client Logic
 * Add/Edit customer modal management, customer profile actions, payment voucher modal,
 * duplicate submit protection, and keyboard dismiss.
 */

export function initCustomersPage() {
    initCustomersIndex();
    initCustomerShow();
}

function initCustomersIndex() {
    const page = document.querySelector('[data-page="customers"]');
    if (!page) return;

    const addModal = page.querySelector('#addCustomerModal');
    const editModal = page.querySelector('#editCustomerModal');

    function openAddModal() {
        if (addModal) {
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

    function openEditModal(customer) {
        const form = page.querySelector('#editCustomerForm');
        if (!form) return;

        form.action = `/customers/${customer.id}`;

        const setVal = (id, val) => {
            const el = page.querySelector(id);
            if (el) el.value = val ?? '';
        };

        setVal('#edit_cust_name', customer.name);
        setVal('#edit_cust_company_name', customer.company_name);
        setVal('#edit_cust_phone', customer.phone);
        setVal('#edit_cust_phone_alt', customer.phone_alt);
        setVal('#edit_cust_customer_type', customer.customer_type || 'wholesale');
        setVal('#edit_cust_pricing_tier', customer.pricing_tier || 'wholesale');
        setVal('#edit_cust_city', customer.city);
        setVal('#edit_cust_address', customer.address);
        setVal('#edit_cust_credit_limit', customer.credit_limit || 0);
        setVal('#edit_cust_opening_balance', customer.opening_balance ?? customer.current_balance ?? 0);
        setVal('#edit_cust_current_balance', customer.current_balance || 0);
        setVal('#edit_cust_sales_rep_id', customer.sales_rep_id);
        setVal('#edit_cust_notes', customer.notes);

        const activeCb = page.querySelector('#edit_cust_is_active');
        if (activeCb) {
            activeCb.checked = Boolean(customer.is_active);
        }

        if (editModal) {
            editModal.classList.add('open');
            editModal.style.display = 'flex';
            const firstInput = editModal.querySelector('#edit_cust_name');
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
    document.querySelectorAll('[data-action="open-add-customer"]').forEach(btn => {
        btn.addEventListener('click', openAddModal);
    });

    document.querySelectorAll('[data-action="close-add-customer"]').forEach(btn => {
        btn.addEventListener('click', closeAddModal);
    });

    document.querySelectorAll('[data-action="open-edit-customer"]').forEach(btn => {
        btn.addEventListener('click', () => {
            const raw = btn.dataset.customer;
            if (raw) {
                try {
                    const data = JSON.parse(raw);
                    openEditModal(data);
                } catch (e) {
                    console.error('Failed to parse customer data JSON', e);
                }
            }
        });
    });

    document.querySelectorAll('[data-action="close-edit-customer"]').forEach(btn => {
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
                submitBtn.innerHTML = '<span>جاري الحفظ...</span>';

                // Safety re-enable after 6 seconds in case of network/server lag
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

    bindFormSubmitProtection('#addCustomerModal form');
    bindFormSubmitProtection('#editCustomerForm');
}

function initCustomerShow() {
    const page = document.querySelector('[data-page="customer-show"]');
    if (!page) return;

    const paymentModal = document.getElementById('recordCustomerPaymentModal');
    if (!paymentModal) return;

    const amountInput = document.getElementById('custPaymentAmount');
    const invoiceSelect = document.getElementById('custPaymentInvoiceSelect');
    const form = document.getElementById('recordCustomerPaymentForm');

    function openPaymentModal() {
        paymentModal.classList.remove('hidden');
        paymentModal.classList.add('open');
        paymentModal.style.display = 'flex';
        if (amountInput) {
            amountInput.focus();
        }
    }

    function closePaymentModal() {
        paymentModal.classList.remove('open');
        paymentModal.classList.add('hidden');
        paymentModal.style.display = 'none';
    }

    // Open button (control panel or elsewhere on page)
    document.querySelectorAll('[data-action="open-customer-payment-modal"]').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            openPaymentModal();
        });
    });

    // Close buttons (modal close icon, cancel button)
    document.querySelectorAll('[data-action="close-customer-payment-modal"]').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            closePaymentModal();
        });
    });

    // Close on backdrop click
    paymentModal.addEventListener('click', (e) => {
        if (e.target === paymentModal) {
            closePaymentModal();
        }
    });

    // Close on Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !paymentModal.classList.contains('hidden') && paymentModal.style.display !== 'none') {
            closePaymentModal();
        }
    });

    // Auto-fill max amount when specific invoice is selected
    if (invoiceSelect && amountInput) {
        invoiceSelect.addEventListener('change', function () {
            const selected = this.options[this.selectedIndex];
            const max = selected?.dataset?.max;
            if (max && parseFloat(max) > 0) {
                amountInput.value = parseFloat(max).toFixed(2);
            }
        });
    }

    // Protection against duplicate form submissions
    if (form) {
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
                submitBtn.innerHTML = '<span>جاري تسجيل السند...</span>';

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
    }
}
