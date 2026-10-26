/**
 * Kayan ERP — Inventory Page Interactions
 * Stock movement modal, quick movement row actions, dynamic warehouse inputs
 */

export function initInventoryPage() {
    const page = document.querySelector('[data-page="inventory"]');
    if (!page) return;

    const modal = document.querySelector('#stockMovementModal');
    const openModalBtns = document.querySelectorAll('[data-action="open-movement-modal"]');
    const closeModalBtns = document.querySelectorAll('[data-action="close-movement-modal"]');
    const quickMovementBtns = document.querySelectorAll('[data-action="quick-movement"]');

    function openStockMovementModal() {
        if (!modal) return;
        const subtitle = document.querySelector('#modal_product_subtitle');
        if (subtitle) subtitle.textContent = '';
        modal.classList.add('open');
        modal.style.display = 'flex';
        updateMovementFields();
    }

    function closeStockMovementModal() {
        if (!modal) return;
        modal.classList.remove('open');
        modal.style.display = 'none';
    }

    function openQuickMovementModal(productId, productName, sku, cost) {
        const select = page.querySelector('#modal_product_id');
        const costInput = page.querySelector('#modal_unit_cost');
        const subtitle = page.querySelector('#modal_product_subtitle');

        if (select) select.value = productId;
        if (costInput) costInput.value = cost || '';
        if (subtitle) subtitle.textContent = `الصنف المحدد: [${sku}] ${productName}`;

        if (modal) {
            modal.classList.add('open');
            modal.style.display = 'flex';
            updateMovementFields();
        }
    }

    function updateMovementFields() {
        const typeInput = page.querySelector('input[name="movement_type"]:checked');
        if (!typeInput) return;
        const type = typeInput.value;

        const singleWh = page.querySelector('#single_warehouse_box');
        const transferWh = page.querySelector('#transfer_warehouses_box');
        const costBox = page.querySelector('#cost_box');
        const qtyLabel = page.querySelector('#qty_label');

        ['in', 'out', 'transfer', 'adjustment'].forEach(t => {
            const el = page.querySelector(`#type_label_${t}`);
            if (el) {
                if (t === type) {
                    el.classList.add('active');
                } else {
                    el.classList.remove('active');
                }
            }
        });

        if (type === 'transfer') {
            if (singleWh) singleWh.style.display = 'none';
            if (transferWh) transferWh.style.display = 'grid';
            if (costBox) costBox.style.display = 'none';
            if (qtyLabel) qtyLabel.textContent = 'الكمية المحولة *';
        } else if (type === 'adjustment') {
            if (singleWh) singleWh.style.display = 'block';
            if (transferWh) transferWh.style.display = 'none';
            if (costBox) costBox.style.display = 'none';
            if (qtyLabel) qtyLabel.textContent = 'الرصيد الفعلي بعد الجرد *';
        } else if (type === 'out') {
            if (singleWh) singleWh.style.display = 'block';
            if (transferWh) transferWh.style.display = 'none';
            if (costBox) costBox.style.display = 'none';
            if (qtyLabel) qtyLabel.textContent = 'الكمية المنصرفة (FIFO) *';
        } else {
            if (singleWh) singleWh.style.display = 'block';
            if (transferWh) transferWh.style.display = 'none';
            if (costBox) costBox.style.display = 'block';
            if (qtyLabel) qtyLabel.textContent = 'الكمية المضافة *';
        }
    }

    openModalBtns.forEach(btn => btn.addEventListener('click', openStockMovementModal));
    closeModalBtns.forEach(btn => btn.addEventListener('click', closeStockMovementModal));

    quickMovementBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.dataset.productId;
            const name = btn.dataset.productName;
            const sku = btn.dataset.productSku;
            const cost = btn.dataset.productCost;
            openQuickMovementModal(id, name, sku, cost);
        });
    });

    page.querySelectorAll('input[name="movement_type"]').forEach(input => {
        input.addEventListener('change', updateMovementFields);
    });

    if (modal) {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) closeStockMovementModal();
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && modal.classList.contains('open')) {
                closeStockMovementModal();
            }
        });
    }

    // Product selector change inside modal -> sync cost price
    const modalProductSelect = page.querySelector('#modal_product_id');
    if (modalProductSelect) {
        modalProductSelect.addEventListener('change', () => {
            const selectedOpt = modalProductSelect.options[modalProductSelect.selectedIndex];
            if (selectedOpt) {
                const cost = selectedOpt.dataset.cost;
                const costInput = page.querySelector('#modal_unit_cost');
                if (costInput && cost) {
                    costInput.value = cost;
                }
            }
        });
    }
}
