/**
 * Customer Installments & Collection Scheduling Page Handler
 */
export function initCustomerInstallments() {
    const page = document.querySelector('[data-page="customer-installments"]');
    if (!page) return;

    // Modals
    const addModal = document.getElementById('addCustomerInstallmentModal');
    const collectModal = document.getElementById('quickCollectModal');

    // Add Installment Elements
    const openAddBtn = document.querySelector('[data-action="open-add-installment"]');
    const closeAddBtns = document.querySelectorAll('[data-action="close-add-installment"]');
    const customerSelect = document.getElementById('ci_customer_id');
    const invoiceSelect = document.getElementById('ci_invoice_id');
    const singleSection = document.getElementById('ci_single_section');
    const multipleSection = document.getElementById('ci_multiple_section');
    const singleTitle = document.getElementById('ci_inst_title');
    const singleAmount = document.getElementById('ci_inst_amount');
    const planRadios = document.querySelectorAll('[data-action="toggle-ci-plan"]');

    // Collect Elements
    const collectBtns = document.querySelectorAll('[data-action="collect-installment"]');
    const closeCollectBtns = document.querySelectorAll('[data-action="close-quick-collect"]');
    const collectForm = document.getElementById('quickCollectForm');
    const collectCustName = document.getElementById('qc_customer_name');
    const collectTargetInfo = document.getElementById('qc_target_info');
    const collectAmount = document.getElementById('qc_amount');

    // Auto submit filter
    const autoFilter = document.querySelector('[data-action="auto-submit-filter"]');
    if (autoFilter) {
        autoFilter.addEventListener('change', () => {
            autoFilter.closest('form')?.submit();
        });
    }

    // Confirm delete
    const deleteForms = document.querySelectorAll('[data-action="confirm-delete-installment"]');
    deleteForms.forEach(form => {
        form.addEventListener('submit', (e) => {
            if (!confirm('هل أنت متأكد من رغبتك في حذف هذا القسط المجدول؟')) {
                e.preventDefault();
            }
        });
    });

    // Open/Close Add Modal
    if (openAddBtn && addModal) {
        openAddBtn.addEventListener('click', (e) => {
            e.preventDefault();
            addModal.classList.add('open');
            if (customerSelect && customerSelect.value) {
                loadCustomerInvoices(customerSelect.value);
            }
        });
    }

    closeAddBtns.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            addModal?.classList.remove('open');
        });
    });

    if (addModal) {
        addModal.addEventListener('click', (e) => {
            if (e.target === addModal) {
                addModal.classList.remove('open');
            }
        });
    }

    // Customer selection -> load invoices
    if (customerSelect) {
        customerSelect.addEventListener('change', () => {
            loadCustomerInvoices(customerSelect.value);
        });
    }

    function loadCustomerInvoices(customerId) {
        if (!invoiceSelect) return;
        invoiceSelect.innerHTML = '<option value="">-- تحصيل عام لحساب العميل (بدون فاتورة محددة) --</option>';

        if (!customerId) return;

        fetch(`/customers/${customerId}/unpaid-invoices`)
            .then(res => res.json())
            .then(data => {
                if (data.success && data.invoices) {
                    data.invoices.forEach(inv => {
                        const opt = document.createElement('option');
                        opt.value = inv.id;
                        const remFormatted = Number(inv.remaining_amount).toLocaleString('ar-EG', { minimumFractionDigits: 2 });
                        opt.textContent = `فاتورة #${inv.invoice_number} (تاريخ ${inv.invoice_date} - متبقي ${remFormatted} ج.م)`;
                        invoiceSelect.appendChild(opt);
                    });
                }
            })
            .catch(err => {
                console.error('Error loading customer invoices:', err);
            });
    }

    // Toggle Plan Type (Single vs Schedule)
    planRadios.forEach(radio => {
        radio.addEventListener('change', () => {
            if (radio.value === 'schedule') {
                singleSection?.classList.add('hidden');
                multipleSection?.classList.remove('hidden');
                singleTitle?.removeAttribute('required');
                singleAmount?.removeAttribute('required');
            } else {
                singleSection?.classList.remove('hidden');
                multipleSection?.classList.add('hidden');
                singleTitle?.setAttribute('required', 'required');
                singleAmount?.setAttribute('required', 'required');
            }
        });
    });

    // Open/Close Quick Collect Modal
    collectBtns.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const instId = btn.dataset.installmentId;
            const custName = btn.dataset.customerName || '';
            const instTitle = btn.dataset.installmentTitle || '';
            const remaining = btn.dataset.remaining || '0.00';

            if (collectForm) {
                collectForm.action = `/customer-installments/${instId}/collect`;
            }

            if (collectCustName) collectCustName.textContent = custName;
            if (collectTargetInfo) collectTargetInfo.textContent = `القسط: ${instTitle} — المتبقي للتحصيل: ${Number(remaining).toLocaleString('ar-EG', { minimumFractionDigits: 2 })} ج.م`;
            if (collectAmount) {
                collectAmount.value = parseFloat(remaining).toFixed(2);
                collectAmount.max = parseFloat(remaining).toFixed(2);
            }

            collectModal?.classList.add('open');
            setTimeout(() => collectAmount?.focus(), 100);
        });
    });

    closeCollectBtns.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            collectModal?.classList.remove('open');
        });
    });

    if (collectModal) {
        collectModal.addEventListener('click', (e) => {
            if (e.target === collectModal) {
                collectModal.classList.remove('open');
            }
        });
    }
}
