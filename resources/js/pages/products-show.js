/**
 * Kayan ERP — Product Show Details Page Interactions
 * Direct thermal printing, quantity adjusting, barcode modal preview
 */

import { showPrintToast } from './products-index.js';

export function initProductShow() {
    const page = document.querySelector('[data-page="products-show"]');
    if (!page || page.dataset.initialized === 'true') return;
    page.dataset.initialized = 'true';

    const productId = page.dataset.productId;
    const modalQtyInput = document.getElementById('labelQuantity');
    const printModal = document.getElementById('printBarcodeModal');

    function getShowQty() {
        const input = document.getElementById('showLabelQty') || document.getElementById('cardLabelQty');
        const val = parseInt(input ? input.value : 1, 10);
        return Math.max(1, Math.min(500, isNaN(val) ? 1 : val));
    }

    function setShowQty(val) {
        const clamped = Math.max(1, Math.min(500, parseInt(val, 10) || 1));
        const showInput = document.getElementById('showLabelQty');
        const cardInput = document.getElementById('cardLabelQty');
        if (showInput) showInput.value = clamped;
        if (cardInput) cardInput.value = clamped;
        if (modalQtyInput) modalQtyInput.value = clamped;
        return clamped;
    }

    // 1. Direct Print from Show via Centralized Python Engine
    document.querySelectorAll('[data-action="direct-print-show"]').forEach(btn => {
        btn.addEventListener('click', async (e) => {
            e.preventDefault();
            if (btn.disabled || btn.dataset.printing === 'true') return;
            btn.dataset.printing = 'true';
            const qty = getShowQty();
            showPrintToast(`جاري إرسال ${qty} ملصق مباشرة إلى طابعة الباركود...`);

            const originalDisabled = btn.disabled;
            btn.disabled = true;

            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                const response = await fetch(`/products/${productId}/direct-print`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ quantity: qty }),
                });

                const data = await response.json();
                if (response.ok && data.success) {
                    showPrintToast(`تم إرسال ${qty} ملصق إلى (${data.printer || 'طابعة الباركود'}) بنجاح.`);
                } else {
                    showPrintToast(`تعذر إرسال أمر الطباعة: ${data.message || 'خطأ غير محدد'}`);
                }
            } catch (err) {
                showPrintToast('حدث خطأ في الاتصال بالخادم أثناء إرسال أمر الطباعة.');
            } finally {
                btn.disabled = originalDisabled;
                btn.dataset.printing = 'false';
            }
        });
    });

    // 2. Adjust Show Qty buttons (+ and - in control panel and card)
    document.querySelectorAll('[data-action="adjust-show-qty"]').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            const delta = parseInt(btn.dataset.delta, 10) || 0;
            const current = getShowQty();
            setShowQty(current + delta);
        });
    });

    // Listen to direct manual typing into quantity fields
    ['showLabelQty', 'cardLabelQty'].forEach(id => {
        const inp = document.getElementById(id);
        if (inp) {
            inp.addEventListener('input', () => {
                setShowQty(inp.value);
            });
            inp.addEventListener('change', () => {
                setShowQty(inp.value);
            });
        }
    });

    // 3. Print Modal Handlers
    function openPrintModal() {
        if (printModal) {
            const currentQty = getShowQty();
            if (modalQtyInput) {
                modalQtyInput.value = currentQty;
            }
            printModal.classList.remove('hidden');
            printModal.classList.add('open');
            printModal.style.display = 'flex';
        }
    }

    function closePrintModal() {
        if (printModal) {
            printModal.classList.remove('open');
            printModal.classList.add('hidden');
            printModal.style.display = 'none';
        }
    }

    document.querySelectorAll('[data-action="open-print-modal"]').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            openPrintModal();
        });
    });

    document.querySelectorAll('[data-action="close-print-modal"]').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            closePrintModal();
        });
    });

    if (printModal) {
        printModal.addEventListener('click', (e) => {
            if (e.target === printModal) closePrintModal();
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && (printModal.classList.contains('open') || printModal.style.display === 'flex')) {
                closePrintModal();
            }
        });
    }

    // Modal Qty Adjust
    document.querySelectorAll('[data-action="adjust-modal-qty"]').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const delta = parseInt(btn.dataset.delta, 10) || 0;
            if (modalQtyInput) {
                const current = parseInt(modalQtyInput.value, 10) || 1;
                const next = Math.max(1, Math.min(500, current + delta));
                modalQtyInput.value = next;
                setShowQty(next);
            }
        });
    });

    // Modal Preset Qty (1, 5, 10, 20, 50)
    document.querySelectorAll('[data-action="set-modal-qty"]').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const val = parseInt(btn.dataset.qty, 10) || 1;
            if (modalQtyInput) {
                modalQtyInput.value = val;
                setShowQty(val);
            }
        });
    });

    if (modalQtyInput) {
        modalQtyInput.addEventListener('input', () => {
            setShowQty(modalQtyInput.value);
        });
    }

    // Open Print Preview or Direct Print from Modal
    document.querySelectorAll('[data-action="open-print-preview"], [data-action="submit-print-preview"]').forEach(btn => {
        btn.addEventListener('click', async (e) => {
            e.preventDefault();
            if (btn.disabled || btn.dataset.printing === 'true') return;
            btn.dataset.printing = 'true';
            const autoPrint = btn.dataset.autoPrint === 'true' || btn.dataset.autoprint === 'true';
            const qty = parseInt(modalQtyInput ? modalQtyInput.value : 1, 10) || 1;

            if (autoPrint) {
                // Direct Print via Centralized Python Engine (Zero browser dialogs)
                closePrintModal();
                showPrintToast(`جاري إرسال ${qty} ملصق مباشرة إلى طابعة الباركود...`);

                try {
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                    const response = await fetch(`/products/${productId}/direct-print`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ quantity: qty }),
                    });

                    const data = await response.json();
                    if (response.ok && data.success) {
                        showPrintToast(`تم إرسال ${qty} ملصق إلى (${data.printer || 'طابعة الباركود'}) بنجاح.`);
                    } else {
                        showPrintToast(`تعذر إرسال أمر الطباعة: ${data.message || 'خطأ غير محدد'}`);
                    }
                } catch (err) {
                    showPrintToast('حدث خطأ في الاتصال بالخادم أثناء إرسال أمر الطباعة.');
                } finally {
                    btn.dataset.printing = 'false';
                }
            } else {
                // Pure screen preview in new tab
                window.open(`/products/${productId}/print-label?quantity=${qty}`, '_blank');
                closePrintModal();
                btn.dataset.printing = 'false';
            }
        });
    });
}
