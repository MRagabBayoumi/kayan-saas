/**
 * Kayan ERP — Products Index Page Interactions
 * Bulk selection, direct thermal printing via iframe, batch print modal
 */

let toastTimeout = null;

export function initProductsIndex() {
    const page = document.querySelector('[data-page="products-index"]');
    if (!page) return;

    const selectAllCb = page.querySelector('#selectAllCheckbox');
    const rowCheckboxes = page.querySelectorAll('.product-select-cb');
    const actionBar = page.querySelector('#batchActionBar');
    const topBatchBtn = document.getElementById('topBatchPrintBtn');
    const selectedCountEl = page.querySelector('#selectedProductsCount');
    const topBatchCountEl = document.getElementById('topBatchCount');

    function getSelectedCheckboxes() {
        return Array.from(page.querySelectorAll('.product-select-cb:checked'));
    }

    function onRowSelectionChange() {
        const selected = getSelectedCheckboxes();
        const count = selected.length;

        if (selectedCountEl) selectedCountEl.textContent = count;
        if (topBatchCountEl) topBatchCountEl.textContent = count;

        if (count > 0) {
            if (actionBar) {
                actionBar.classList.remove('hidden');
                actionBar.style.display = 'flex';
            }
            if (topBatchBtn) {
                topBatchBtn.classList.remove('hidden');
                topBatchBtn.style.display = 'inline-flex';
            }
        } else {
            if (actionBar) {
                actionBar.classList.add('hidden');
                actionBar.style.display = 'none';
            }
            if (topBatchBtn) {
                topBatchBtn.classList.add('hidden');
                topBatchBtn.style.display = 'none';
            }
        }

        if (selectAllCb && rowCheckboxes.length > 0) {
            selectAllCb.checked = (selected.length === rowCheckboxes.length);
            selectAllCb.indeterminate = (selected.length > 0 && selected.length < rowCheckboxes.length);
        }
    }

    // Toggle select all
    if (selectAllCb) {
        selectAllCb.addEventListener('change', () => {
            rowCheckboxes.forEach(cb => {
                cb.checked = selectAllCb.checked;
            });
            onRowSelectionChange();
        });
    }

    // Row checkbox change
    rowCheckboxes.forEach(cb => {
        cb.addEventListener('change', onRowSelectionChange);
    });

    // Clear selections button
    page.querySelector('[data-action="clear-selections"]')?.addEventListener('click', () => {
        rowCheckboxes.forEach(cb => {
            cb.checked = false;
        });
        if (selectAllCb) {
            selectAllCb.checked = false;
            selectAllCb.indeterminate = false;
        }
        onRowSelectionChange();
    });

    // Batch Print Modal handling
    const batchModal = document.getElementById('batchPrintModal');
    const openBatchBtns = document.querySelectorAll('[data-action="open-batch-modal"]');
    const closeBatchBtns = document.querySelectorAll('[data-action="close-batch-modal"]');

    function openBatchPrintModal() {
        const selected = getSelectedCheckboxes();
        if (selected.length === 0) {
            alert('يرجى تحديد صنف واحد على الأقل للطباعة.');
            return;
        }

        const tbody = document.getElementById('batchModalTableBody');
        if (!tbody) return;
        tbody.innerHTML = '';

        selected.forEach((cb, index) => {
            const id = cb.dataset.id;
            const name = cb.dataset.name;
            const sku = cb.dataset.sku;

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="text-center font-muted font-sm">${index + 1}</td>
                <td><strong class="text-main font-sm">${name}</strong></td>
                <td><span class="font-numeric text-primary font-bold font-sm">${sku}</span></td>
                <td class="text-center">
                    <div class="erp-batch-qty-control inline-flex items-center gap-1">
                        <button type="button" class="erp-btn erp-btn-secondary font-numeric py-1 px-2.5 font-bold" data-action="adjust-item-qty" data-id="${id}" data-delta="-1" title="إنقاص الكمية">−</button>
                        <input type="number" id="batch_qty_${id}" class="erp-input font-numeric batch-qty-input erp-batch-qty-input text-center font-bold w-14" data-id="${id}" value="1" min="1" max="500">
                        <button type="button" class="erp-btn erp-btn-secondary font-numeric py-1 px-2.5 font-bold" data-action="adjust-item-qty" data-id="${id}" data-delta="1" title="زيادة الكمية">+</button>
                    </div>
                </td>
            `;
            tbody.appendChild(tr);
        });

        recalculateBatchTotal();

        if (batchModal) {
            batchModal.classList.remove('hidden');
            batchModal.classList.add('open');
            batchModal.style.display = 'flex';
        }
    }

    function closeBatchPrintModal() {
        if (batchModal) {
            batchModal.classList.remove('open');
            batchModal.classList.add('hidden');
            batchModal.style.display = 'none';
        }
    }

    openBatchBtns.forEach(btn => btn.addEventListener('click', (e) => {
        e.preventDefault();
        openBatchPrintModal();
    }));
    closeBatchBtns.forEach(btn => btn.addEventListener('click', (e) => {
        e.preventDefault();
        closeBatchPrintModal();
    }));

    if (batchModal) {
        batchModal.addEventListener('click', (e) => {
            if (e.target === batchModal) closeBatchPrintModal();
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && (batchModal.classList.contains('open') || batchModal.style.display === 'flex')) {
                closeBatchPrintModal();
            }
        });
    }

    // Batch quantity adjust handlers inside modal (bind globally to document)
    document.addEventListener('click', (e) => {
        const adjustBtn = e.target.closest('[data-action="adjust-item-qty"]');
        if (adjustBtn) {
            e.preventDefault();
            e.stopPropagation();
            const id = adjustBtn.dataset.id;
            const delta = parseInt(adjustBtn.dataset.delta, 10) || 0;
            const input = document.getElementById('batch_qty_' + id);
            if (input) {
                const current = parseInt(input.value, 10) || 1;
                input.value = Math.max(1, Math.min(500, current + delta));
                recalculateBatchTotal();
            }
        }
    });

    document.addEventListener('input', (e) => {
        if (e.target.classList && e.target.classList.contains('batch-qty-input')) {
            recalculateBatchTotal();
        }
    });

    // Preset quantity buttons (1, 2, 5, 10, 20)
    document.querySelectorAll('[data-preset-qty]').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const val = parseInt(btn.dataset.presetQty, 10) || 1;
            document.querySelectorAll('.batch-qty-input').forEach(inp => {
                inp.value = val;
            });
            recalculateBatchTotal();
        });
    });

    function recalculateBatchTotal() {
        const inputs = page.querySelectorAll('.batch-qty-input');
        let totalLabels = 0;
        inputs.forEach(inp => {
            totalLabels += (parseInt(inp.value, 10) || 1);
        });

        const countEl = page.querySelector('#batchSummaryProductsCount');
        const totalEl = page.querySelector('#batchSummaryTotalLabels');
        if (countEl) countEl.textContent = inputs.length;
        if (totalEl) totalEl.textContent = totalLabels;
    }

    // Submit batch print form
    page.querySelectorAll('[data-action="submit-batch-print"]').forEach(btn => {
        btn.addEventListener('click', async () => {
            const autoPrint = btn.dataset.autoprint === 'true';
            const form = page.querySelector('#batchPrintForm');
            const container = page.querySelector('#batchFormHiddenInputs');
            const autoprintInput = page.querySelector('#batchFormAutoprint');
            if (!form || !container) return;

            const inputs = page.querySelectorAll('.batch-qty-input');
            if (inputs.length === 0) {
                alert('لا توجد أصناف محددة.');
                return;
            }

            if (autoPrint) {
                // Direct Printing via Centralized Python Engine (Zero browser dialogs)
                const itemsPayload = [];
                inputs.forEach(inp => {
                    const id = inp.dataset.id;
                    const qty = parseInt(inp.value, 10) || 1;
                    itemsPayload.push({ id: parseInt(id, 10), qty: qty });
                });

                closeBatchPrintModal();
                showPrintToast(`جاري إرسال ${itemsPayload.length} أصناف للطباعة الفورية...`);

                try {
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                    const response = await fetch('/products/direct-print/batch', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ items: itemsPayload }),
                    });

                    const data = await response.json();
                    if (response.ok && data.success) {
                        showPrintToast(`تم إرسال ${data.total_copies || itemsPayload.length} ملصق إلى طابعة الباركود بنجاح.`);
                    } else {
                        showPrintToast(`تعذر إرسال أمر الطباعة: ${data.message || 'خطأ غير محدد'}`);
                    }
                } catch (err) {
                    showPrintToast('حدث خطأ في الاتصال بالخادم أثناء إرسال أمر الطباعة.');
                }
            } else {
                // Browser Preview
                if (autoprintInput) autoprintInput.value = '0';
                container.innerHTML = '';

                inputs.forEach(inp => {
                    const id = inp.dataset.id;
                    const qty = parseInt(inp.value, 10) || 1;
                    const hidden = document.createElement('input');
                    hidden.type = 'hidden';
                    hidden.name = `products[${id}]`;
                    hidden.value = qty;
                    container.appendChild(hidden);
                });

                form.submit();
                closeBatchPrintModal();
            }
        });
    });

    // Direct Printing from index row via Centralized Python Engine
    page.addEventListener('click', async (e) => {
        const printBtn = e.target.closest('[data-action="direct-print-row"]');
        if (printBtn) {
            e.preventDefault();
            e.stopPropagation();
            const productId = printBtn.dataset.productId;
            if (!productId) return;

            showPrintToast('جاري إرسال ملصق الصنف مباشرة إلى طابعة الباركود...');
            printBtn.disabled = true;

            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                const response = await fetch(`/products/${productId}/direct-print`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ quantity: 1 }),
                });

                const data = await response.json();
                if (response.ok && data.success) {
                    showPrintToast(`تم إرسال الملصق بنجاح إلى (${data.printer || 'طابعة الباركود'}).`);
                } else {
                    showPrintToast(`تعذر إرسال أمر الطباعة: ${data.message || 'خطأ غير محدد'}`);
                }
            } catch (err) {
                showPrintToast('حدث خطأ في الاتصال بالخادم أثناء إرسال أمر الطباعة.');
            } finally {
                printBtn.disabled = false;
            }
        }
    });

    // Direct Batch Print buttons (both in control panel and floating bar) via Centralized Python Engine
    document.querySelectorAll('[data-action="direct-print-batch"]').forEach(btn => {
        btn.addEventListener('click', async (e) => {
            e.preventDefault();
            const selected = getSelectedCheckboxes();
            if (selected.length === 0) {
                alert('يرجى تحديد صنف واحد على الأقل للطباعة.');
                return;
            }

            const ids = selected.map(cb => cb.dataset.id).filter(Boolean);
            showPrintToast(`جاري إرسال ${ids.length} أصناف للطباعة الفورية...`);
            btn.disabled = true;

            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                const itemsPayload = ids.map(id => ({ id: parseInt(id, 10), qty: 1 }));
                const response = await fetch('/products/direct-print/batch', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ items: itemsPayload }),
                });

                const data = await response.json();
                if (response.ok && data.success) {
                    showPrintToast(`تم إرسال ${data.total_copies || ids.length} ملصق إلى طابعة الباركود بنجاح.`);
                } else {
                    showPrintToast(`تعذر إرسال أمر الطباعة: ${data.message || 'خطأ غير محدد'}`);
                }
            } catch (err) {
                showPrintToast('حدث خطأ في الاتصال بالخادم أثناء إرسال أمر الطباعة.');
            } finally {
                btn.disabled = false;
            }
        });
    });

    // Row Click Navigation (Odoo List View Behavior: click anywhere on row to view product details)
    const tableBody = page.querySelector('.erp-table tbody');
    if (tableBody) {
        tableBody.addEventListener('click', (e) => {
            // Ignore if clicking on interactive controls (checkbox, button, modal trigger, delete form, direct link)
            if (e.target.closest('input, button, select, textarea, form, .erp-btn, label, a')) {
                return;
            }

            const row = e.target.closest('tr.product-row-clickable');
            if (row && row.dataset.url) {
                window.location.href = row.dataset.url;
            }
        });
    }

    // Per Page Selector Handling
    const perPageSelect = page.querySelector('[data-action="change-per-page"]');
    if (perPageSelect) {
        perPageSelect.addEventListener('change', () => {
            const url = new URL(window.location.href);
            url.searchParams.set('per_page', perPageSelect.value);
            url.searchParams.delete('page');
            window.location.href = url.toString();
        });
    }

    // Category Multi-Select Filter Label Handling
    const categoryDropdown = document.getElementById('productCategoryFilterDropdown');
    const categoryFilterLabel = document.getElementById('productCategoryFilterLabel');
    if (categoryDropdown && categoryFilterLabel) {
        const categoryCheckboxes = categoryDropdown.querySelectorAll('.category-filter-cb');

        function updateCategoryFilterLabel() {
            const checked = Array.from(categoryCheckboxes).filter(cb => cb.checked);
            if (checked.length === 0) {
                categoryFilterLabel.textContent = 'كل التصنيفات';
            } else if (checked.length === 1) {
                const labelText = checked[0].closest('label')?.querySelector('span')?.textContent?.trim() || 'تصنيف محدد';
                categoryFilterLabel.textContent = labelText;
            } else {
                categoryFilterLabel.textContent = `${checked.length} تصنيفات محددة`;
            }
        }

        categoryCheckboxes.forEach(cb => {
            cb.addEventListener('change', updateCategoryFilterLabel);
        });

        document.getElementById('selectAllCategoriesBtn')?.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            categoryCheckboxes.forEach(cb => { cb.checked = true; });
            updateCategoryFilterLabel();
        });

        document.getElementById('clearAllCategoriesBtn')?.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            categoryCheckboxes.forEach(cb => { cb.checked = false; });
            updateCategoryFilterLabel();
        });
    }
}

export function showPrintToast(msg) {
    const toast = document.getElementById('printNotificationToast');
    const msgEl = document.getElementById('printToastMessage');
    if (toast && msgEl) {
        msgEl.textContent = msg;
        toast.classList.remove('hidden');
        toast.style.display = 'flex';
        clearTimeout(toastTimeout);
        toastTimeout = setTimeout(() => {
            toast.classList.add('hidden');
            toast.style.display = 'none';
        }, 2800);
    }
}
