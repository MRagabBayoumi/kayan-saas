export function initShifts() {
    initShiftsIndex();
}

function initShiftsIndex() {
    const pageContainer = document.querySelector('[data-page="shifts-index"], [data-page="shift-show"]');
    if (!pageContainer) return;

    // 1. Open Shift Modal
    const openShiftModalBtn = document.getElementById('openShiftModalBtn');
    const openShiftModal = document.getElementById('openShiftModal');

    if (openShiftModalBtn && openShiftModal) {
        openShiftModalBtn.addEventListener('click', () => {
            openShiftModal.classList.remove('hidden');
            openShiftModal.classList.add('open');
            const openingInput = document.getElementById('shiftOpeningBalance');
            if (openingInput) setTimeout(() => openingInput.focus(), 100);
        });
    }

    // 2. Close Shift Modal & Live Diff Calculation
    const closeShiftModal = document.getElementById('closeShiftModal');
    const closeShiftForm = document.getElementById('closeShiftForm');
    const closeShiftNumberSpan = document.getElementById('closeShiftNumberSpan');
    const closeShiftExpectedSpan = document.getElementById('closeShiftExpectedSpan');
    const closeShiftActualInput = document.getElementById('closeShiftActualBalance');
    const shiftDiffResultBox = document.getElementById('shiftDiffResultBox');
    const shiftDiffResultText = document.getElementById('shiftDiffResultText');

    let currentExpectedBalance = 0;

    document.querySelectorAll('.close-shift-trigger-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const shiftId = btn.dataset.shiftId;
            const shiftNum = btn.dataset.shiftNumber || '';
            currentExpectedBalance = parseFloat(btn.dataset.expected) || 0;

            const cashierSpan = document.getElementById('closeShiftCashierSpan');
            const warehouseSpan = document.getElementById('closeShiftWarehouseSpan');
            const openingSpan = document.getElementById('closeShiftOpeningSpan');
            const salesSpan = document.getElementById('closeShiftSalesSpan');
            const additionsSpan = document.getElementById('closeShiftAdditionsSpan');
            const expensesSpan = document.getElementById('closeShiftExpensesSpan');

            if (cashierSpan && btn.dataset.cashier) cashierSpan.textContent = btn.dataset.cashier;
            if (warehouseSpan && btn.dataset.warehouse) warehouseSpan.textContent = btn.dataset.warehouse;
            if (openingSpan) openingSpan.textContent = `${parseFloat(btn.dataset.opening || 0).toFixed(2)} ج.م`;
            if (salesSpan) salesSpan.textContent = `${parseFloat(btn.dataset.sales || 0).toFixed(2)} ج.م`;
            if (additionsSpan) additionsSpan.textContent = `${parseFloat(btn.dataset.additions || 0).toFixed(2)} ج.م`;
            if (expensesSpan) expensesSpan.textContent = `${parseFloat(btn.dataset.expenses || 0).toFixed(2)} ج.م`;

            if (closeShiftForm) {
                closeShiftForm.action = `/shifts/${shiftId}/close`;
            }
            if (closeShiftNumberSpan) closeShiftNumberSpan.textContent = shiftNum;
            if (closeShiftExpectedSpan) closeShiftExpectedSpan.textContent = `${currentExpectedBalance.toFixed(2)} ج.م`;
            if (closeShiftActualInput) {
                closeShiftActualInput.value = '';
                setTimeout(() => closeShiftActualInput.focus(), 100);
            }
            if (shiftDiffResultBox) shiftDiffResultBox.classList.add('hidden');

            if (closeShiftModal) {
                closeShiftModal.classList.remove('hidden');
                closeShiftModal.classList.add('open');
            }
        });
    });

    if (closeShiftActualInput) {
        closeShiftActualInput.addEventListener('input', () => {
            const actual = parseFloat(closeShiftActualInput.value);
            if (isNaN(actual)) {
                if (shiftDiffResultBox) shiftDiffResultBox.classList.add('hidden');
                return;
            }

            const diff = actual - currentExpectedBalance;
            if (shiftDiffResultBox && shiftDiffResultText) {
                shiftDiffResultBox.classList.remove('hidden', 'erp-alert-success', 'erp-alert-danger', 'erp-alert-info');
                if (Math.abs(diff) < 0.005) {
                    shiftDiffResultBox.classList.add('erp-alert-success');
                    shiftDiffResultText.textContent = 'الدرج متطابق تماماً بدون عجز أو زيادة (الفارق: 0.00 ج.م)';
                } else if (diff > 0) {
                    shiftDiffResultBox.classList.add('erp-alert-info');
                    shiftDiffResultText.textContent = `يوجد فائض نقدي بالدرج بقيمة +${diff.toFixed(2)} ج.م`;
                } else {
                    shiftDiffResultBox.classList.add('erp-alert-danger');
                    shiftDiffResultText.textContent = `يوجد عجز نقدي بالدرج بقيمة ${diff.toFixed(2)} ج.م`;
                }
            }
        });
    }

    // 3. Record Movement Modal (Cash In / Out)
    const recordMovementModal = document.getElementById('recordMovementModal');
    const recordMovementForm = document.getElementById('recordMovementForm');
    const recordMovementModalBtn = document.getElementById('recordMovementModalBtn');

    function openMovementForShift(shiftId) {
        if (recordMovementForm) {
            recordMovementForm.action = `/shifts/${shiftId}/movement`;
        }
        if (recordMovementModal) {
            recordMovementModal.classList.remove('hidden');
            recordMovementModal.classList.add('open');
            const amountInput = document.getElementById('movementAmount');
            if (amountInput) setTimeout(() => amountInput.focus(), 100);
        }
    }

    if (recordMovementModalBtn) {
        recordMovementModalBtn.addEventListener('click', () => {
            const activeBanner = document.querySelector('.record-movement-trigger-btn');
            const shiftId = activeBanner ? activeBanner.dataset.shiftId : null;
            if (shiftId) openMovementForShift(shiftId);
        });
    }

    document.querySelectorAll('.record-movement-trigger-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            openMovementForShift(btn.dataset.shiftId);
        });
    });

    // 4. Record Shift Daily Expense Modal
    const shiftExpenseModal = document.getElementById('shiftExpenseModal');
    const recordShiftExpenseBtn = document.getElementById('recordShiftExpenseBtn');

    function openShiftExpenseModal() {
        if (!shiftExpenseModal) return;
        shiftExpenseModal.classList.remove('hidden');
        shiftExpenseModal.classList.add('open');
        const amountInput = document.getElementById('shiftExpenseAmount');
        if (amountInput) setTimeout(() => amountInput.focus(), 100);
    }

    if (recordShiftExpenseBtn) {
        recordShiftExpenseBtn.addEventListener('click', () => {
            const hasShift = recordShiftExpenseBtn.dataset.hasActiveShift === 'true';
            if (!hasShift) {
                const proceed = confirm('تنبيه: لا توجد وردية كاشير مفتوحة لك حالياً لخصم المصروف من درجها.\n\nهل ترغب في فتح وردية جديدة أولاً؟\n(اضغط "موافق" لفتح وردية، أو "إلغاء" للمتابعة وتسجيل مصروف عام)');
                if (proceed) {
                    const openShiftModalBtn = document.getElementById('openShiftModalBtn');
                    if (openShiftModalBtn) {
                        openShiftModalBtn.click();
                        return;
                    }
                }
            }
            openShiftExpenseModal();
        });
    }

    document.querySelectorAll('.trigger-shift-expense-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            openShiftExpenseModal();
        });
    });

    // Close any modal
    document.querySelectorAll('.close-modal-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.erp-modal-overlay').forEach(modal => {
                modal.classList.remove('open');
                modal.classList.add('hidden');
            });
        });
    });

    // Close on overlay click
    document.querySelectorAll('.erp-modal-overlay').forEach(modal => {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.classList.remove('open');
                modal.classList.add('hidden');
            }
        });
    });
}
