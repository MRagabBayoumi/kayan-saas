/**
 * Kayan ERP — Purchases Module Interactions
 * Ergonomic POS/ERP barcode scanning, keyboard-driven navigation, and dynamic table entry.
 */

export function initPurchases() {
    const createPage = document.querySelector('[data-page="purchases-create"]');
    if (createPage) {
        initPurchaseCreate();
    }

    const showPage = document.querySelector('[data-page="purchases-show"]');
    if (showPage) {
        initPurchaseShow();
    }

    const ledgerPage = document.querySelector('[data-page="product-ledger"]');
    if (ledgerPage) {
        initProductLedger();
    }
}

function initPurchaseCreate() {
    const tableBody = document.getElementById('purchaseItemsBody');
    const template = document.getElementById('purchaseItemRowTemplate');
    const manualAddBtn = document.getElementById('addPurchaseItemBtn');
    const shippingInput = document.getElementById('shippingCost');

    // Upper Input Panel Elements
    const searchModeSelect = document.getElementById('searchModeSelect');
    const searchModeHint = document.getElementById('searchModeHint');
    const quickProductInput = document.getElementById('quickProductInput');
    const quickSearchInputLabel = document.getElementById('quickSearchInputLabel');
    const quickSearchResults = document.getElementById('quickSearchResults');
    const quickProductId = document.getElementById('quickProductId');
    const selectedProductBadge = document.getElementById('selectedProductBadge');
    const quickStockBadge = document.getElementById('quickStockBadge');
    const warehouseSelect = document.getElementById('warehouseSelect') || document.getElementById('warehouseId');
    const quickUnitSelect = document.getElementById('quickUnitSelect');
    const quickUnitPieceBtn = document.getElementById('quickUnitPieceBtn');
    const quickUnitDozenBtn = document.getElementById('quickUnitDozenBtn');
    const quickUnitDozenLabel = document.getElementById('quickUnitDozenLabel');
    const quickQtyInput = document.getElementById('quickQtyInput');
    const quickCostInput = document.getElementById('quickCostInput');
    const quickAddBtn = document.getElementById('quickAddBtn');

    // Quick Product Modal Elements
    const quickProductModal = document.getElementById('quickProductModal');
    const openQuickProductModalBtn = document.getElementById('openQuickProductModalBtn');
    const closeQuickProductModalBtn = document.getElementById('closeQuickProductModalBtn');
    const cancelQuickProductModalBtn = document.getElementById('cancelQuickProductModalBtn');
    const quickProductForm = document.getElementById('quickProductForm');
    const quickProductAlert = document.getElementById('quickProductAlert');
    const quickProductAlertMsg = document.getElementById('quickProductAlertMsg');
    const saveQuickProductBtn = document.getElementById('saveQuickProductBtn');
    const generateInternalBarcodeBtn = document.getElementById('generateInternalBarcodeBtn');
    const regenerateQuickSkuBtn = document.getElementById('regenerateQuickSkuBtn');
    const quickProdBarcode = document.getElementById('quickProdBarcode');
    const quickProdSku = document.getElementById('quickProdSku');
    const quickProdName = document.getElementById('quickProdName');
    const quickProdCategory = document.getElementById('quickProdCategory');
    const quickProdUnit = document.getElementById('quickProdUnit');
    const quickProdPkgFactor = document.getElementById('quickProdPkgFactor');
    const quickProdFactorPreview = document.getElementById('quickProdFactorPreview');
    const quickProdFactorBadge = document.getElementById('quickProdFactorBadge');
    const quickProdFactorHeader = document.getElementById('quickProdFactorHeader');
    const quickProdCostPiece = document.getElementById('quickProdCostPiece');
    const quickProdCostDozen = document.getElementById('quickProdCostDozen');
    const quickProdRetailPiece = document.getElementById('quickProdRetailPiece');
    const quickProdRetailDozen = document.getElementById('quickProdRetailDozen');
    const quickProdWholesalePiece = document.getElementById('quickProdWholesalePiece');
    const quickProdWholesaleDozen = document.getElementById('quickProdWholesaleDozen');
    const quickProdDistributorPiece = document.getElementById('quickProdDistributorPiece');
    const quickProdDistributorDozen = document.getElementById('quickProdDistributorDozen');
    const quickProdMinStock = document.getElementById('quickProdMinStock');
    const quickProdInsertDirectly = document.getElementById('quickProdInsertDirectly');
    const quickProdDirectPurchaseFields = document.getElementById('quickProdDirectPurchaseFields');
    const quickProdBuyPieceBtn = document.getElementById('quickProdBuyPieceBtn');
    const quickProdBuyDozenBtn = document.getElementById('quickProdBuyDozenBtn');
    const quickProdBuyDozenLabel = document.getElementById('quickProdBuyDozenLabel');
    const quickProdPurchaseUnitVal = document.getElementById('quickProdPurchaseUnitVal');
    const quickProdPurchaseQty = document.getElementById('quickProdPurchaseQty');
    const quickProdLineSubtotalPreview = document.getElementById('quickProdLineSubtotalPreview');

    // Discount Elements
    const discountTypeSelect = document.getElementById('invoiceDiscountType');
    const discountValueInput = document.getElementById('invoiceDiscountValue');
    const discountUnitLabel = document.getElementById('discountUnitLabel');

    // Summary Elements
    const itemsCountText = document.getElementById('purchaseItemsCountText');
    const totalQtyText = document.getElementById('purchaseTotalQtyText');
    const subtotalText = document.getElementById('purchaseSubtotalText');
    const discountText = document.getElementById('purchaseDiscountText');
    const shippingText = document.getElementById('purchaseShippingText');
    const grandTotalText = document.getElementById('purchaseGrandTotalText');

    // Payment & Deferred Elements
    const purchasePaymentToggleGroup = document.getElementById('purchasePaymentToggleGroup');
    const purchasePaidAmountInput = document.getElementById('purchasePaidAmountInput');
    const purchaseRemainingAmountInput = document.getElementById('purchaseRemainingAmountInput');
    const purchasePaidHint = document.getElementById('purchasePaidHint');
    const purchaseRemainingHint = document.getElementById('purchaseRemainingHint');
    const purchaseDueDateGroup = document.getElementById('purchaseDueDateGroup');

    const createPage = document.querySelector('[data-page="purchases-create"]');
    let productsData = [];
    let unitsData = [];

    if (createPage && createPage.dataset.products) {
        try {
            productsData = JSON.parse(createPage.dataset.products);
        } catch (e) {
            productsData = [];
        }
    }
    if (!productsData.length && Array.isArray(window.kayanProductsData)) {
        productsData = window.kayanProductsData;
    }

    if (createPage && createPage.dataset.units) {
        try {
            unitsData = JSON.parse(createPage.dataset.units);
        } catch (e) {
            unitsData = [];
        }
    }
    if (!unitsData.length && Array.isArray(window.kayanUnitsData)) {
        unitsData = window.kayanUnitsData;
    }

    // Fallback if data attribute was empty: read from select options
    if (!productsData.length) {
        const dummySelect = document.querySelector('.purchase-product-select');
        if (dummySelect) {
            Array.from(dummySelect.options).forEach(opt => {
                if (opt.value) {
                    productsData.push({
                        id: parseInt(opt.value, 10),
                        name: opt.text.split('(')[0].trim(),
                        sku: (opt.text.match(/\(([^)]+)\)/) || [])[1] || '',
                        barcode: '',
                        cost_price: parseFloat(opt.dataset.cost || 0),
                        unit: opt.dataset.unit || 'قطعة'
                    });
                }
            });
        }
    }

    let activeSuggestionIndex = -1;

    // Search Mode Change Handler
    if (searchModeSelect) {
        searchModeSelect.addEventListener('change', () => {
            const mode = searchModeSelect.value;
            if (mode === 'barcode') {
                if (quickSearchInputLabel) quickSearchInputLabel.textContent = 'مربع الباركود (مسدس الباركود)';
                if (quickProductInput) quickProductInput.placeholder = 'اضرب الباركود بمسدس الباركود...';
                if (searchModeHint) searchModeHint.textContent = 'اضرب الباركود بالمسدس (ينتقل للوحدة تلقائياً)';
            } else if (mode === 'sku') {
                if (quickSearchInputLabel) quickSearchInputLabel.textContent = 'كود الصنف (SKU)';
                if (quickProductInput) quickProductInput.placeholder = 'اكتب كود الصنف واضغط Enter...';
                if (searchModeHint) searchModeHint.textContent = 'اكتب كود الصنف أو اختر من القائمة';
            } else {
                if (quickSearchInputLabel) quickSearchInputLabel.textContent = 'اسم الصنف';
                if (quickProductInput) quickProductInput.placeholder = 'اكتب اسم الصنف للبحث...';
                if (searchModeHint) searchModeHint.textContent = 'ابحث بالاسم واختر الصنف المطلوب';
            }
            if (quickProductInput) quickProductInput.focus();
        });
    }

    // Discount Type & Value Change Handlers
    function updateDiscountLabel() {
        if (!discountTypeSelect || !discountUnitLabel) return;
        discountUnitLabel.textContent = discountTypeSelect.value === 'percentage' ? '%' : 'ج.م';
        recalculateTotals();
    }

    if (discountTypeSelect) {
        discountTypeSelect.addEventListener('change', updateDiscountLabel);
    }
    if (discountValueInput) {
        discountValueInput.addEventListener('input', recalculateTotals);
    }

    function normalizeArabic(text) {
        if (!text) return '';
        return text.toString().trim().toLowerCase()
            .replace(/[أإآ]/g, 'ا')
            .replace(/ة/g, 'ه')
            .replace(/ى/g, 'ي')
            .replace(/[\u064B-\u065F]/g, '');
    }

    // Product Autocomplete and Barcode Lookup
    function findProductByTerm(term, mode = 'barcode') {
        const clean = (term || '').trim().toLowerCase();
        if (!clean) return null;
        const norm = normalizeArabic(clean);

        // First check exact barcode match
        const exactBarcode = productsData.find(p => (p.barcode || '').trim().toLowerCase() === clean);
        if (exactBarcode) return exactBarcode;

        // Fallback to SKU
        const exactSku = productsData.find(p => (p.sku || '').trim().toLowerCase() === clean);
        if (exactSku) return exactSku;

        // Exact Name match (normalized)
        const exactName = productsData.find(p => normalizeArabic(p.name) === norm);
        if (exactName) return exactName;

        // Partial match
        return productsData.find(p => 
            (p.barcode || '').toLowerCase().includes(clean) ||
            (p.sku || '').toLowerCase().includes(clean) ||
            normalizeArabic(p.name).includes(norm)
        ) || null;
    }

    // Helper: Get available warehouse stock
    function getProductWarehouseStock(product) {
        if (!product) return 0;
        const whId = warehouseSelect ? warehouseSelect.value : null;
        if (whId && product.stocks && product.stocks[whId] !== undefined) {
            return parseFloat(product.stocks[whId]) || 0;
        }
        if (product.total_stock !== undefined) {
            return parseFloat(product.total_stock) || 0;
        }
        return 0;
    }

    // Helper: Format stock quantity into compound breakdown (e.g. 54 دستة و 11 قطعة)
    function formatStockBreakdown(product, stockQty = null) {
        if (!product) return '0 قطعة';
        const qty = stockQty !== null ? stockQty : getProductWarehouseStock(product);
        const factor = Math.round(parseFloat(product.package_factor || product.pieces_per_package || 12) || 12);
        const unitName = (product.unit || 'قطعة').trim();

        if (qty <= 0) {
            return `0 ${unitName}`;
        }

        if (factor <= 1) {
            return `${Number(qty.toFixed(2))} ${unitName}`;
        }

        const totalPieces = Math.round(qty);
        const packages = Math.floor(totalPieces / factor);
        const remainderPieces = totalPieces % factor;
        const packLabel = (factor === 12 || unitName === 'دستة') ? 'دستة' : 'طرد';

        if (packages === 0) {
            return `${remainderPieces} قطعة`;
        }
        if (remainderPieces === 0) {
            return `${packages} ${packLabel} (${totalPieces} قطعة)`;
        }
        return `${packages} ${packLabel} و ${remainderPieces} قطعة (${totalPieces} قطعة)`;
    }

    function renderSuggestions(query) {
        if (!quickSearchResults) return;
        const q = (query || '').trim();
        if (!q || q.length < 1) {
            quickSearchResults.classList.add('hidden');
            quickSearchResults.innerHTML = '';
            activeSuggestionIndex = -1;
            return;
        }

        const cleanQ = q.toLowerCase();
        const normQ = normalizeArabic(q);
        const mode = searchModeSelect ? searchModeSelect.value : 'barcode';

        const matches = productsData.filter(p => {
            const pBarcode = (p.barcode || '').toLowerCase();
            const pSku = (p.sku || '').toLowerCase();
            const pNameNorm = normalizeArabic(p.name);

            if (mode === 'sku') {
                return pSku.includes(cleanQ) || pNameNorm.includes(normQ);
            }
            if (mode === 'name') {
                return pNameNorm.includes(normQ) || pSku.includes(cleanQ) || pBarcode.includes(cleanQ);
            }
            return pBarcode.includes(cleanQ) || pSku.includes(cleanQ) || pNameNorm.includes(normQ);
        }).slice(0, 10);

        if (matches.length === 0) {
            quickSearchResults.innerHTML = `
                <div class="px-4 py-3 text-xs text-[var(--text-muted)] text-center flex flex-col items-center justify-center gap-2">
                    <span>لا يوجد صنف مطابق لـ "${query}"</span>
                    <button type="button" id="quickAddProductFromSearchBtn" class="erp-btn erp-btn-xs erp-btn-primary flex items-center gap-1.5 font-bold cursor-pointer py-1.5 px-3">
                        <x-icon name="plus" size="13" />
                        <span>+ إضافة "${query}" كصنف جديد فوراً (F3)</span>
                    </button>
                </div>
            `;
            quickSearchResults.classList.remove('hidden');
            activeSuggestionIndex = -1;

            const addBtn = document.getElementById('quickAddProductFromSearchBtn');
            if (addBtn) {
                addBtn.addEventListener('mousedown', (e) => {
                    e.preventDefault();
                    quickSearchResults.classList.add('hidden');
                    const isNum = /^[0-9]+$/.test(query);
                    openQuickProductModal({
                        name: !isNum ? query : '',
                        barcode: isNum ? query : ''
                    });
                });
            }
            return;
        }

        quickSearchResults.innerHTML = matches.map((p, idx) => {
            const costPrice = parseFloat(p.cost_price) || 0;
            const stock = getProductWarehouseStock(p);
            const formattedStock = formatStockBreakdown(p, stock);
            let badgeClass = 'stock-in';
            let badgeText = `رصيد: ${formattedStock}`;
            if (stock <= 0) {
                badgeClass = 'stock-out';
                badgeText = 'نفد (0)';
            } else if (stock <= 5) {
                badgeClass = 'stock-low';
                badgeText = `منخفض: ${formattedStock}`;
            }

            return `
                <div class="suggestion-item pos-suggestion-item purchases-suggestion-item" data-index="${idx}" data-id="${p.id}">
                    <div class="flex flex-col flex-1 min-w-0 pr-1">
                        <span class="item-title truncate font-bold">${p.name}</span>
                        <div class="item-meta flex items-center gap-2 mt-1">
                            <span class="font-numeric">كود: ${p.sku}</span>
                            ${p.barcode ? '<span class="font-numeric">· باركود: ' + p.barcode + '</span>' : ''}
                            <span class="pos-suggestion-badge ${badgeClass}">${badgeText}</span>
                        </div>
                    </div>
                    <div class="text-left font-numeric flex flex-col items-end pl-1 shrink-0">
                        <span class="pos-suggestion-price">${costPrice.toFixed(2)} <span class="text-[11px] font-normal text-[var(--text-muted)]">ج.م</span></span>
                        <span class="text-[10px] text-[var(--text-muted)] font-normal">${p.unit || 'قطعة'}</span>
                    </div>
                </div>
            `;
        }).join('');

        quickSearchResults.querySelectorAll('.suggestion-item').forEach(el => {
            el.addEventListener('mousedown', (e) => {
                e.preventDefault();
                const pId = parseInt(el.dataset.id, 10);
                const found = productsData.find(item => item.id === pId);
                if (found) {
                    selectProduct(found);
                }
            });
        });

        quickSearchResults.classList.remove('hidden');
        activeSuggestionIndex = -1;
    }

    // Fast Unit Switcher Support (Piece vs Dozen)
    function syncUnitPillState(selectedFactor = 1.0) {
        if (!quickUnitPieceBtn || !quickUnitDozenBtn) return;
        if (selectedFactor > 1.0) {
            quickUnitPieceBtn.classList.remove('active');
            quickUnitDozenBtn.classList.add('active', 'dozen');
        } else {
            quickUnitPieceBtn.classList.add('active');
            quickUnitDozenBtn.classList.remove('active', 'dozen');
        }
    }

    function switchToDozen() {
        if (!quickUnitSelect) return;
        const pId = quickProductId ? parseInt(quickProductId.value, 10) : null;
        const prod = productsData.find(p => p.id === pId);
        const pkgFactor = prod ? (parseFloat(prod.package_factor || prod.pieces_per_package || prod.unit_factor || 12.0) || 12.0) : 12.0;

        let matched = false;
        for (let i = 0; i < quickUnitSelect.options.length; i++) {
            const opt = quickUnitSelect.options[i];
            const optFactor = parseFloat(opt.dataset.factor || 1.0);
            if (optFactor === pkgFactor || opt.text.includes('دست') || opt.dataset.name?.includes('دست')) {
                quickUnitSelect.selectedIndex = i;
                matched = true;
                break;
            }
        }
        if (!matched) {
            for (let i = 0; i < quickUnitSelect.options.length; i++) {
                if (parseFloat(quickUnitSelect.options[i].dataset.factor || 1.0) > 1.0) {
                    quickUnitSelect.selectedIndex = i;
                    break;
                }
            }
        }
        const prodFound = productsData.find(p => p.id === (quickProductId ? parseInt(quickProductId.value, 10) : null));
        updateQuickCostByUnit(prodFound);
        syncUnitPillState(pkgFactor);
        if (quickQtyInput) {
            quickQtyInput.focus();
            quickQtyInput.select();
        }
    }

    function switchToPiece() {
        if (!quickUnitSelect) return;
        for (let i = 0; i < quickUnitSelect.options.length; i++) {
            const opt = quickUnitSelect.options[i];
            const optFactor = parseFloat(opt.dataset.factor || 1.0);
            if (optFactor === 1.0 || opt.text.includes('قطع') || opt.dataset.name?.includes('قطع')) {
                quickUnitSelect.selectedIndex = i;
                break;
            }
        }
        const prodFound = productsData.find(p => p.id === (quickProductId ? parseInt(quickProductId.value, 10) : null));
        updateQuickCostByUnit(prodFound);
        syncUnitPillState(1.0);
        if (quickQtyInput) {
            quickQtyInput.focus();
            quickQtyInput.select();
        }
    }

    if (quickUnitDozenBtn) {
        quickUnitDozenBtn.addEventListener('click', (e) => {
            e.preventDefault();
            switchToDozen();
        });
    }

    if (quickUnitPieceBtn) {
        quickUnitPieceBtn.addEventListener('click', (e) => {
            e.preventDefault();
            switchToPiece();
        });
    }

    function updateQuickCostByUnit(product) {
        if (!product || !quickUnitSelect || !quickCostInput) return;
        const prodFactor = parseFloat(product.unit_factor) || 1.0;
        const baseCost = (parseFloat(product.cost_price) || 0) / prodFactor;
        const selectedOpt = quickUnitSelect.options[quickUnitSelect.selectedIndex];
        const targetFactor = parseFloat(selectedOpt?.dataset?.factor) || 1.0;
        quickCostInput.value = (baseCost * targetFactor).toFixed(2);
    }

    function selectProduct(product) {
        if (!product) return;

        if (quickProductId) quickProductId.value = product.id;
        if (quickProductInput) quickProductInput.value = `${product.name} (${product.sku})`;
        if (selectedProductBadge) selectedProductBadge.classList.remove('hidden');

        // Update dozen label with actual package factor
        const pkgFactor = parseInt(product.package_factor || product.pieces_per_package || product.unit_factor || 12, 10);
        if (quickUnitDozenLabel) {
            quickUnitDozenLabel.textContent = `دستة (* ${pkgFactor})`;
        }

        // Update live stock badge if present
        if (quickStockBadge) {
            const stock = getProductWarehouseStock(product);
            const formatted = formatStockBreakdown(product, stock);
            quickStockBadge.textContent = `رصيد الصنف بالمخزن: ${formatted}`;
            quickStockBadge.className = 'pos-stock-badge font-bold ' + (stock > 5 ? 'in-stock' : (stock > 0 ? 'low-stock' : 'out-of-stock'));
            quickStockBadge.classList.remove('hidden');
        }

        // Select unit matching product's unit
        if (quickUnitSelect) {
            const productUnitName = (product.unit || 'قطعة').trim();
            let matchedOption = false;
            for (let i = 0; i < quickUnitSelect.options.length; i++) {
                const opt = quickUnitSelect.options[i];
                if (opt.dataset.name === productUnitName || opt.text.includes(productUnitName)) {
                    quickUnitSelect.selectedIndex = i;
                    matchedOption = true;
                    break;
                }
            }
            if (!matchedOption && quickUnitSelect.options.length > 0) {
                quickUnitSelect.selectedIndex = 0;
            }
            updateQuickCostByUnit(product);
            const selectedOpt = quickUnitSelect.options[quickUnitSelect.selectedIndex];
            syncUnitPillState(parseFloat(selectedOpt?.dataset?.factor || 1.0));
        } else if (quickCostInput) {
            quickCostInput.value = (product.cost_price || 0).toFixed(2);
        }

        // Hide suggestions
        if (quickSearchResults) {
            quickSearchResults.classList.add('hidden');
            quickSearchResults.innerHTML = '';
        }

        // KEYBOARD WORKFLOW: Immediately transfer focus to Unit of Measurement
        if (quickUnitSelect) {
            quickUnitSelect.focus();
        } else if (quickQtyInput) {
            quickQtyInput.focus();
            quickQtyInput.select();
        }
    }

    // Search Input Events
    if (quickProductInput) {
        quickProductInput.addEventListener('input', (e) => {
            const term = e.target.value.trim();
            if (quickProductId && quickProductId.value) {
                // If user changes text after selecting, clear the ID
                quickProductId.value = '';
                if (selectedProductBadge) selectedProductBadge.classList.add('hidden');
                if (quickStockBadge) quickStockBadge.classList.add('hidden');
            }
            renderSuggestions(term);
        });

        quickProductInput.addEventListener('keydown', (e) => {
            const mode = searchModeSelect ? searchModeSelect.value : 'barcode';

            // Fast Unit Keyboard Shortcuts in Search Box
            if ((e.key === 'p' || e.key === 'P' || e.key === 'ق') && !quickProductInput.value.trim()) {
                e.preventDefault();
                switchToPiece();
                return;
            }
            if ((e.key === '*' || e.key === 'd' || e.key === 'D' || e.key === 'د') && !quickProductInput.value.trim()) {
                e.preventDefault();
                switchToDozen();
                return;
            }

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                const items = quickSearchResults.querySelectorAll('.suggestion-item');
                if (items.length > 0) {
                    activeSuggestionIndex = (activeSuggestionIndex + 1) % items.length;
                    items.forEach((it, i) => {
                        const isCurrent = (i === activeSuggestionIndex);
                        it.classList.toggle('active', isCurrent);
                        it.classList.toggle('bg-selected', isCurrent);
                    });
                    items[activeSuggestionIndex]?.scrollIntoView({ block: 'nearest' });
                }
                return;
            }

            if (e.key === 'ArrowUp') {
                e.preventDefault();
                const items = quickSearchResults.querySelectorAll('.suggestion-item');
                if (items.length > 0) {
                    activeSuggestionIndex = (activeSuggestionIndex - 1 + items.length) % items.length;
                    items.forEach((it, i) => {
                        const isCurrent = (i === activeSuggestionIndex);
                        it.classList.toggle('active', isCurrent);
                        it.classList.toggle('bg-selected', isCurrent);
                    });
                    items[activeSuggestionIndex]?.scrollIntoView({ block: 'nearest' });
                }
                return;
            }

            if (e.key === 'Enter') {
                e.preventDefault();
                // 1. If an item in dropdown is active
                const items = quickSearchResults.querySelectorAll('.suggestion-item');
                if (items.length > 0 && activeSuggestionIndex >= 0 && items[activeSuggestionIndex]) {
                    const pId = parseInt(items[activeSuggestionIndex].dataset.id, 10);
                    const found = productsData.find(item => item.id === pId);
                    if (found) {
                        selectProduct(found);
                        return;
                    }
                }

                // 2. Exact match check (Barcode scanner sends Enter!)
                const term = quickProductInput.value.trim();
                const matched = findProductByTerm(term, mode);
                if (matched) {
                    selectProduct(matched);
                } else if (items.length > 0) {
                    // Pick first suggestion if available
                    const firstId = parseInt(items[0].dataset.id, 10);
                    const found = productsData.find(item => item.id === firstId);
                    if (found) selectProduct(found);
                } else if (term) {
                    // Open quick product modal with term prefilled
                    const isNum = /^[0-9]+$/.test(term);
                    openQuickProductModal({
                        name: !isNum ? term : '',
                        barcode: isNum ? term : ''
                    });
                }
            }

            if (e.key === 'Escape') {
                if (quickSearchResults) quickSearchResults.classList.add('hidden');
            }
        });

        // Hide suggestions on outside click
        document.addEventListener('click', (e) => {
            if (quickSearchResults && !quickSearchResults.contains(e.target) && e.target !== quickProductInput) {
                quickSearchResults.classList.add('hidden');
            }
        });
    }

    // Keyboard Flow: Unit -> Tab -> Quantity
    if (quickUnitSelect) {
        quickUnitSelect.addEventListener('change', () => {
            let pId = quickProductId ? parseInt(quickProductId.value, 10) : null;
            let product = productsData.find(p => p.id === pId);
            if (!product) {
                const term = (quickProductInput ? quickProductInput.value : '').trim();
                product = findProductByTerm(term, searchModeSelect ? searchModeSelect.value : 'barcode');
            }
            if (product) {
                updateQuickCostByUnit(product);
            }
            const selectedOpt = quickUnitSelect.options[quickUnitSelect.selectedIndex];
            const targetFactor = parseFloat(selectedOpt?.dataset?.factor) || 1.0;
            syncUnitPillState(targetFactor);
        });

        quickUnitSelect.addEventListener('keydown', (e) => {
            if (e.key === 'Tab' && !e.shiftKey) {
                e.preventDefault();
                if (quickQtyInput) {
                    quickQtyInput.focus();
                    quickQtyInput.select();
                }
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (quickQtyInput) {
                    quickQtyInput.focus();
                    quickQtyInput.select();
                }
            }
        });
    }

    // Keyboard Flow: Quantity -> Tab -> Cost
    if (quickQtyInput) {
        quickQtyInput.addEventListener('keydown', (e) => {
            if (e.key === 'Tab' && !e.shiftKey) {
                e.preventDefault();
                if (quickCostInput) {
                    quickCostInput.focus();
                    quickCostInput.select();
                }
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (quickCostInput) {
                    quickCostInput.focus();
                    quickCostInput.select();
                }
            }
        });
    }

    // Keyboard Flow: Cost -> Enter -> Commit to Table & Return to Search
    if (quickCostInput) {
        quickCostInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                commitQuickAdd();
            }
        });
    }

    if (quickAddBtn) {
        quickAddBtn.addEventListener('click', (e) => {
            e.preventDefault();
            commitQuickAdd();
        });
    }

    // COMMIT QUICK ADD FUNCTION: Inserts item into the TOP ROW of the table
    function commitQuickAdd() {
        let pId = quickProductId ? parseInt(quickProductId.value, 10) : null;
        let product = productsData.find(p => p.id === pId);

        if (!product) {
            // Try matching whatever is in quickProductInput
            const term = (quickProductInput ? quickProductInput.value : '').trim();
            product = findProductByTerm(term, searchModeSelect ? searchModeSelect.value : 'barcode');
        }

        if (!product) {
            alert('يرجى اختيار صنف صحيح من القائمة أو ضرب باركود مسجل أولاً.');
            if (quickProductInput) quickProductInput.focus();
            return;
        }

        const qty = parseFloat(quickQtyInput ? quickQtyInput.value : 1) || 1;
        if (qty <= 0) {
            alert('الكمية يجب أن تكون أكبر من الصفر.');
            if (quickQtyInput) quickQtyInput.focus();
            return;
        }

        const cost = parseFloat(quickCostInput ? quickCostInput.value : 0) || 0;
        if (cost < 0) {
            alert('سعر التكلفة لا يمكن أن يكون سالباً.');
            if (quickCostInput) quickCostInput.focus();
            return;
        }

        const selectedOpt = quickUnitSelect ? quickUnitSelect.options[quickUnitSelect.selectedIndex] : null;
        const selectedUnitId = selectedOpt ? selectedOpt.value : null;
        const selectedUnitName = selectedOpt ? (selectedOpt.dataset.name || selectedOpt.text) : 'قطعة';
        const selectedFactor = selectedOpt ? (parseFloat(selectedOpt.dataset.factor) || 1.0) : 1.0;

        // Add to TOP of Table
        insertRowAtTop({
            productId: product.id,
            productName: product.name,
            productSku: product.sku,
            quantity: qty,
            unitCost: cost,
            unitId: selectedUnitId,
            unitName: selectedUnitName,
            conversionFactor: selectedFactor,
            notes: ''
        });

        // Reset Input Fields
        if (quickProductInput) quickProductInput.value = '';
        if (quickProductId) quickProductId.value = '';
        if (selectedProductBadge) selectedProductBadge.classList.add('hidden');
        if (quickStockBadge) quickStockBadge.classList.add('hidden');
        if (quickQtyInput) quickQtyInput.value = '1';
        if (quickCostInput) quickCostInput.value = '0.00';
        if (quickSearchResults) quickSearchResults.classList.add('hidden');

        // RETURN FOCUS TO SEARCH INPUT FOR NEXT ITEM
        if (quickProductInput) {
            quickProductInput.focus();
        }

        recalculateTotals();
    }

    function insertRowAtTop(data) {
        if (!tableBody || !template) return;

        // Check if there is an empty initial placeholder row with no product selected
        const existingRows = tableBody.querySelectorAll('.purchase-item-row');
        if (existingRows.length === 1) {
            const firstSelect = existingRows[0].querySelector('.purchase-product-select');
            if (firstSelect && !firstSelect.value) {
                existingRows[0].remove();
            }
        }

        const tempIndex = Date.now();
        let html = template.innerHTML
            .replace(/__INDEX__/g, tempIndex.toString())
            .replace(/__NUM__/g, '1')
            .replace(/__UNIT_ID__/g, (data.unitId || '').toString())
            .replace(/__UNIT__/g, data.unitName || 'قطعة')
            .replace(/__FACTOR__/g, (data.conversionFactor || 1.0).toString())
            .replace(/__QTY__/g, data.quantity.toString())
            .replace(/__COST__/g, data.unitCost.toFixed(2))
            .replace(/__SUBTOTAL__/g, (data.quantity * data.unitCost).toFixed(2));

        const tempContainer = document.createElement('tbody');
        tempContainer.innerHTML = html.trim();
        const newRow = tempContainer.firstElementChild;

        // Set selected product in the row select
        const prodSelect = newRow.querySelector('.purchase-product-select');
        if (prodSelect) {
            let opt = prodSelect.querySelector(`option[value="${data.productId}"]`);
            if (!opt) {
                const pInfo = productsData.find(p => p.id === data.productId) || {
                    id: data.productId,
                    name: data.productName || 'صنف جديد',
                    sku: data.productSku || '',
                    cost_price: data.unitCost,
                    unit: data.unitName
                };
                opt = document.createElement('option');
                opt.value = pInfo.id;
                opt.textContent = `${pInfo.name} (${pInfo.sku || ''})`;
                opt.dataset.cost = pInfo.cost_price;
                opt.dataset.unit = pInfo.unit;
                prodSelect.appendChild(opt);

                const tplSelect = template.content ? template.content.querySelector('.purchase-product-select') : template.querySelector?.('.purchase-product-select');
                if (tplSelect && !tplSelect.querySelector(`option[value="${data.productId}"]`)) {
                    tplSelect.appendChild(opt.cloneNode(true));
                }
            }
            prodSelect.value = data.productId.toString();
        }

        // PREPEND TO TABLE: Newly scanned/entered item is ALWAYS AT THE TOP ROW
        if (tableBody.firstChild) {
            tableBody.insertBefore(newRow, tableBody.firstChild);
        } else {
            tableBody.appendChild(newRow);
        }

        // Visual flash effect on new top row
        newRow.classList.add('bg-emerald-50', 'dark:bg-emerald-950/40', 'transition-colors', 'duration-700');
        setTimeout(() => {
            newRow.classList.remove('bg-emerald-50', 'dark:bg-emerald-950/40');
        }, 800);

        attachRowEvents(newRow);
        reindexRows();
    }

    function reindexRows() {
        const rows = tableBody.querySelectorAll('.purchase-item-row');
        rows.forEach((row, idx) => {
            row.setAttribute('data-row-index', idx.toString());

            const numCell = row.querySelector('.row-num-cell');
            if (numCell) numCell.textContent = (idx + 1).toString();

            const select = row.querySelector('.purchase-product-select');
            if (select) select.name = `items[${idx}][product_id]`;

            const unitIdInput = row.querySelector('.purchase-unit-id-input');
            if (unitIdInput) unitIdInput.name = `items[${idx}][unit_id]`;

            const unitInput = row.querySelector('.purchase-unit-input');
            if (unitInput) unitInput.name = `items[${idx}][unit_name]`;

            const factorInput = row.querySelector('.purchase-factor-input');
            if (factorInput) factorInput.name = `items[${idx}][conversion_factor]`;

            const qty = row.querySelector('.purchase-qty-input');
            if (qty) qty.name = `items[${idx}][quantity]`;

            const cost = row.querySelector('.purchase-cost-input');
            if (cost) cost.name = `items[${idx}][unit_cost]`;

            const notes = row.querySelector('input[type="text"][name*="notes"]');
            if (notes) notes.name = `items[${idx}][notes]`;
        });
    }

    function recalculateTotals() {
        let subtotal = 0;
        let totalQty = 0;
        const rows = tableBody ? tableBody.querySelectorAll('.purchase-item-row') : [];

        rows.forEach(row => {
            const qtyInput = row.querySelector('.purchase-qty-input');
            const costInput = row.querySelector('.purchase-cost-input');
            const subtotalCell = row.querySelector('.purchase-line-subtotal');

            const qty = parseFloat(qtyInput ? qtyInput.value : 0) || 0;
            const cost = parseFloat(costInput ? costInput.value : 0) || 0;
            const lineTotal = qty * cost;

            if (subtotalCell) {
                subtotalCell.textContent = lineTotal.toFixed(2);
            }
            subtotal += lineTotal;
            totalQty += qty;
        });

        // Discount calculation
        const discType = discountTypeSelect ? discountTypeSelect.value : 'fixed';
        const discVal = parseFloat(discountValueInput ? discountValueInput.value : 0) || 0;
        let discAmount = 0;
        if (discVal > 0) {
            if (discType === 'percentage') {
                discAmount = (subtotal * Math.min(100, discVal)) / 100;
            } else {
                discAmount = Math.min(subtotal, discVal);
            }
        }

        const shipping = parseFloat(shippingInput ? shippingInput.value : 0) || 0;
        const grandTotal = Math.max(0, subtotal - discAmount + shipping);

        // Update Summary Cards
        if (itemsCountText) itemsCountText.textContent = rows.length.toString();
        if (totalQtyText) totalQtyText.textContent = totalQty.toFixed(2);
        if (subtotalText) subtotalText.textContent = subtotal.toFixed(2);
        if (discountText) discountText.textContent = discAmount.toFixed(2);
        if (shippingText) shippingText.textContent = shipping.toFixed(2);
        if (grandTotalText) grandTotalText.textContent = grandTotal.toFixed(2);
        updatePurchasePaymentStatus(grandTotal);
    }

    function getCurrentPurchasePaymentType() {
        const checked = document.querySelector('input[name="payment_type"]:checked');
        return checked ? checked.value : 'cash';
    }

    function updatePurchasePaymentStatus(grandTotal = null) {
        if (grandTotal === null) {
            grandTotal = parseFloat(grandTotalText ? grandTotalText.textContent : 0) || 0;
        }

        const paymentType = getCurrentPurchasePaymentType();

        const purchaseDueDateHint = document.getElementById('purchaseDueDateHint');

        if (paymentType === 'cash') {
            if (purchasePaidAmountInput) {
                purchasePaidAmountInput.value = grandTotal.toFixed(2);
                purchasePaidAmountInput.readOnly = true;
                purchasePaidAmountInput.classList.remove('bg-[var(--bg-surface)]');
                purchasePaidAmountInput.classList.add('bg-[var(--surface-muted)]');
            }
            if (purchaseRemainingAmountInput) {
                purchaseRemainingAmountInput.value = '0.00';
            }
            if (purchasePaidHint) purchasePaidHint.textContent = 'كامل الفاتورة مسدد للمورد';
            if (purchaseRemainingHint) purchaseRemainingHint.textContent = 'لا توجد مستحقات معلقة للمورد';
            if (purchaseDueDateInput) {
                purchaseDueDateInput.disabled = true;
                purchaseDueDateInput.value = '';
                purchaseDueDateInput.classList.add('bg-[var(--surface-muted)]', 'cursor-not-allowed', 'opacity-60');
            }
            if (purchaseDueDateHint) purchaseDueDateHint.textContent = 'غير مطلوب في السداد النقدي';
        } else if (paymentType === 'credit') {
            if (purchasePaidAmountInput) {
                purchasePaidAmountInput.value = '0.00';
                purchasePaidAmountInput.readOnly = true;
                purchasePaidAmountInput.classList.remove('bg-[var(--bg-surface)]');
                purchasePaidAmountInput.classList.add('bg-[var(--surface-muted)]');
            }
            if (purchaseRemainingAmountInput) {
                purchaseRemainingAmountInput.value = grandTotal.toFixed(2);
            }
            if (purchasePaidHint) purchasePaidHint.textContent = 'لا يوجد سداد نقدي حالي';
            if (purchaseRemainingHint) purchaseRemainingHint.textContent = 'كامل الفاتورة آجل على حساب المورد';
            if (purchaseDueDateInput) {
                purchaseDueDateInput.disabled = false;
                purchaseDueDateInput.classList.remove('bg-[var(--surface-muted)]', 'cursor-not-allowed', 'opacity-60');
            }
            if (purchaseDueDateHint) purchaseDueDateHint.textContent = 'تاريخ استحقاق سداد فاتورة المورد';
        } else { // partial
            if (purchasePaidAmountInput) {
                purchasePaidAmountInput.readOnly = false;
                purchasePaidAmountInput.classList.remove('bg-[var(--surface-muted)]');
                purchasePaidAmountInput.classList.add('bg-[var(--bg-surface)]');
                let paid = parseFloat(purchasePaidAmountInput.value) || 0;
                if (paid > grandTotal) {
                    paid = grandTotal;
                    purchasePaidAmountInput.value = paid.toFixed(2);
                }
                const remaining = Math.max(0, grandTotal - paid);
                if (purchaseRemainingAmountInput) {
                    purchaseRemainingAmountInput.value = remaining.toFixed(2);
                }
            }
            if (purchasePaidHint) purchasePaidHint.textContent = 'المبلغ المسدد الآن للمورد';
            if (purchaseRemainingHint) purchaseRemainingHint.textContent = 'المتبقي الآجل المستحق للمورد';
            if (purchaseDueDateInput) {
                purchaseDueDateInput.disabled = false;
                purchaseDueDateInput.classList.remove('bg-[var(--surface-muted)]', 'cursor-not-allowed', 'opacity-60');
            }
            if (purchaseDueDateHint) purchaseDueDateHint.textContent = 'تاريخ استحقاق سداد المبلغ المتبقي';
        }

    }

    // Attach Payment Method Events
    if (purchasePaymentToggleGroup) {
        const toggleButtons = purchasePaymentToggleGroup.querySelectorAll('.payment-toggle-btn');
        toggleButtons.forEach(btn => {
            btn.addEventListener('click', (e) => {
                if (e.target && e.target.tagName && e.target.tagName.toLowerCase() === 'input') {
                    return;
                }
                toggleButtons.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                const radio = btn.querySelector('input[type="radio"]');
                if (radio) {
                    radio.checked = true;
                }
                updatePurchasePaymentStatus();
            });
        });

        const radios = purchasePaymentToggleGroup.querySelectorAll('input[type="radio"]');
        radios.forEach(radio => {
            radio.addEventListener('change', () => {
                toggleButtons.forEach(b => {
                    const r = b.querySelector('input[type="radio"]');
                    if (r && r.checked) {
                        b.classList.add('active');
                    } else {
                        b.classList.remove('active');
                    }
                });
                updatePurchasePaymentStatus();
            });
        });
    }

    if (purchasePaidAmountInput) {
        purchasePaidAmountInput.addEventListener('input', () => {
            const grandTotal = parseFloat(grandTotalText ? grandTotalText.textContent : 0) || 0;
            let paid = parseFloat(purchasePaidAmountInput.value) || 0;
            if (paid < 0) {
                paid = 0;
                purchasePaidAmountInput.value = '0.00';
            }
            if (paid > grandTotal) {
                paid = grandTotal;
                purchasePaidAmountInput.value = paid.toFixed(2);
            }
            const remaining = Math.max(0, grandTotal - paid);
            if (purchaseRemainingAmountInput) {
                purchaseRemainingAmountInput.value = remaining.toFixed(2);
            }
            updatePurchasePaymentStatus(grandTotal);
        });
    }

    function attachRowEvents(row) {
        const productSelect = row.querySelector('.purchase-product-select');
        const qtyInput = row.querySelector('.purchase-qty-input');
        const costInput = row.querySelector('.purchase-cost-input');
        const removeBtn = row.querySelector('.remove-row-btn');
        const unitBadge = row.querySelector('.purchase-unit-badge');
        const unitInput = row.querySelector('.purchase-unit-input');

        if (productSelect) {
            productSelect.addEventListener('change', () => {
                const selectedOpt = productSelect.options[productSelect.selectedIndex];
                if (selectedOpt && selectedOpt.dataset.cost) {
                    const costVal = parseFloat(selectedOpt.dataset.cost) || 0;
                    if (costInput && (costInput.value === '0.00' || costInput.value === '0' || !costInput.value)) {
                        costInput.value = costVal.toFixed(2);
                    }
                }
                if (selectedOpt && selectedOpt.dataset.unit) {
                    if (unitBadge) unitBadge.textContent = selectedOpt.dataset.unit;
                    if (unitInput) unitInput.value = selectedOpt.dataset.unit;
                }
                recalculateTotals();
            });
        }

        if (qtyInput) {
            qtyInput.addEventListener('input', recalculateTotals);
        }

        if (costInput) {
            costInput.addEventListener('input', recalculateTotals);
        }

        if (removeBtn) {
            removeBtn.addEventListener('click', () => {
                const totalRows = tableBody.querySelectorAll('.purchase-item-row').length;
                if (totalRows <= 1) {
                    alert('يجب الإبقاء على صنف واحد على الأقل في فاتورة الشراء.');
                    return;
                }
                row.remove();
                reindexRows();
                recalculateTotals();
            });
        }
    }

    // Attach to existing initial rows
    if (tableBody) {
        tableBody.querySelectorAll('.purchase-item-row').forEach(row => attachRowEvents(row));
    }

    // Manual add button (adds to bottom if clicked)
    if (manualAddBtn && template && tableBody) {
        manualAddBtn.addEventListener('click', () => {
            const tempIndex = Date.now();
            const html = template.innerHTML
                .replace(/__INDEX__/g, tempIndex.toString())
                .replace(/__NUM__/g, (tableBody.querySelectorAll('.purchase-item-row').length + 1).toString())
                .replace(/__UNIT_ID__/g, '')
                .replace(/__UNIT__/g, 'قطعة')
                .replace(/__FACTOR__/g, '1.0')
                .replace(/__QTY__/g, '1')
                .replace(/__COST__/g, '0.00')
                .replace(/__SUBTOTAL__/g, '0.00');

            const tempContainer = document.createElement('tbody');
            tempContainer.innerHTML = html.trim();
            const newRow = tempContainer.firstElementChild;

            tableBody.appendChild(newRow);
            attachRowEvents(newRow);
            reindexRows();
            recalculateTotals();
        });
    }

    if (shippingInput) {
        shippingInput.addEventListener('input', recalculateTotals);
    }

    // ==========================================================================
    // Quick Add Product Modal Handling (Piece & Dozen Integrated)
    // ==========================================================================
    function getQuickFactor() {
        return Math.max(1, parseFloat(quickProdPkgFactor?.value) || 12);
    }

    function syncFactorLabels(factor) {
        if (quickProdFactorPreview) quickProdFactorPreview.textContent = factor.toString();
        if (quickProdFactorBadge) quickProdFactorBadge.textContent = factor.toString();
        if (quickProdFactorHeader) quickProdFactorHeader.textContent = `×${factor}`;
        if (quickProdBuyDozenLabel) quickProdBuyDozenLabel.textContent = `دستة (×${factor})`;
    }

    function autoSuggestSellingPrices() {
        const costVal = parseFloat(quickProdCostPiece?.value);
        if (isNaN(costVal) || costVal <= 0) return;
        const factor = getQuickFactor();

        // 1. Retail (25% markup)
        if (quickProdRetailPiece && (!quickProdRetailPiece.value || parseFloat(quickProdRetailPiece.value) === 0)) {
            quickProdRetailPiece.value = (costVal * 1.25).toFixed(2);
            if (quickProdRetailDozen) quickProdRetailDozen.value = (costVal * 1.25 * factor).toFixed(2);
        }
        // 2. Wholesale (15% markup)
        if (quickProdWholesalePiece && (!quickProdWholesalePiece.value || parseFloat(quickProdWholesalePiece.value) === 0)) {
            quickProdWholesalePiece.value = (costVal * 1.15).toFixed(2);
            if (quickProdWholesaleDozen) quickProdWholesaleDozen.value = (costVal * 1.15 * factor).toFixed(2);
        }
        // 3. Distributor (10% markup)
        if (quickProdDistributorPiece && (!quickProdDistributorPiece.value || parseFloat(quickProdDistributorPiece.value) === 0)) {
            quickProdDistributorPiece.value = (costVal * 1.10).toFixed(2);
            if (quickProdDistributorDozen) quickProdDistributorDozen.value = (costVal * 1.10 * factor).toFixed(2);
        }
    }

    function syncModalPurchaseSubtotal() {
        if (!quickProdLineSubtotalPreview) return;
        const factor = getQuickFactor();
        const unitType = quickProdPurchaseUnitVal?.value || 'piece';
        const qty = parseFloat(quickProdPurchaseQty?.value) || 0;
        let unitCost = 0;

        if (unitType === 'piece') {
            unitCost = parseFloat(quickProdCostPiece?.value) || 0;
        } else {
            const dozenCost = parseFloat(quickProdCostDozen?.value);
            if (!isNaN(dozenCost) && dozenCost > 0) {
                unitCost = dozenCost;
            } else {
                unitCost = (parseFloat(quickProdCostPiece?.value) || 0) * factor;
            }
        }

        const subtotal = Math.max(0, qty * unitCost);
        quickProdLineSubtotalPreview.textContent = subtotal.toFixed(2);
    }

    function setModalPurchaseUnit(unitType) {
        if (quickProdPurchaseUnitVal) quickProdPurchaseUnitVal.value = unitType;
        if (unitType === 'dozen') {
            quickProdBuyDozenBtn?.classList.add('active', 'dozen');
            quickProdBuyPieceBtn?.classList.remove('active');
        } else {
            quickProdBuyPieceBtn?.classList.add('active');
            quickProdBuyDozenBtn?.classList.remove('active', 'dozen');
        }
        syncModalPurchaseSubtotal();
    }

    function openQuickProductModal(prefill = {}) {
        if (!quickProductModal) return;
        if (quickProductAlert) quickProductAlert.classList.add('hidden');
        if (quickProductForm) quickProductForm.reset();

        if (prefill.name && quickProdName) quickProdName.value = prefill.name;
        if (prefill.barcode && quickProdBarcode) quickProdBarcode.value = prefill.barcode;

        if (quickProdPkgFactor) quickProdPkgFactor.value = 12;
        syncFactorLabels(12);

        if (quickProdInsertDirectly) {
            quickProdInsertDirectly.checked = true;
            if (quickProdDirectPurchaseFields) {
                quickProdDirectPurchaseFields.classList.remove('opacity-40', 'pointer-events-none');
            }
        }
        if (saveQuickProductBtn) {
            const span = saveQuickProductBtn.querySelector('span');
            if (span) span.textContent = 'حفظ وإدراج الصنف في الفاتورة فوراً';
        }

        if (quickProdPurchaseQty) quickProdPurchaseQty.value = '1';
        setModalPurchaseUnit('piece');

        quickProductModal.style.display = 'flex';
        setTimeout(() => {
            if (prefill.barcode && !prefill.name && quickProdName) {
                quickProdName.focus();
            } else if (quickProdName && !prefill.name) {
                quickProdName.focus();
            } else if (quickProdCostPiece) {
                quickProdCostPiece.focus();
            }
        }, 100);
    }

    function closeQuickProductModal() {
        if (!quickProductModal) return;
        quickProductModal.style.display = 'none';
        if (quickProductForm) quickProductForm.reset();
        if (quickProductAlert) quickProductAlert.classList.add('hidden');
    }

    if (openQuickProductModalBtn) {
        openQuickProductModalBtn.addEventListener('click', (e) => {
            e.preventDefault();
            const term = (quickProductInput?.value || '').trim();
            const isNum = /^[0-9]+$/.test(term);
            openQuickProductModal({
                name: !isNum ? term : '',
                barcode: isNum ? term : ''
            });
        });
    }

    if (closeQuickProductModalBtn) closeQuickProductModalBtn.addEventListener('click', closeQuickProductModal);
    if (cancelQuickProductModalBtn) cancelQuickProductModalBtn.addEventListener('click', closeQuickProductModal);

    // Unit toggle buttons inside modal
    if (quickProdBuyPieceBtn) {
        quickProdBuyPieceBtn.addEventListener('click', (e) => {
            e.preventDefault();
            setModalPurchaseUnit('piece');
        });
    }

    if (quickProdBuyDozenBtn) {
        quickProdBuyDozenBtn.addEventListener('click', (e) => {
            e.preventDefault();
            setModalPurchaseUnit('dozen');
        });
    }

    if (quickProdInsertDirectly) {
        quickProdInsertDirectly.addEventListener('change', () => {
            const isDirect = quickProdInsertDirectly.checked;
            if (quickProdDirectPurchaseFields) {
                if (isDirect) {
                    quickProdDirectPurchaseFields.classList.remove('opacity-40', 'pointer-events-none');
                } else {
                    quickProdDirectPurchaseFields.classList.add('opacity-40', 'pointer-events-none');
                }
            }
            if (saveQuickProductBtn) {
                const textSpan = saveQuickProductBtn.querySelector('span');
                if (textSpan) {
                    textSpan.textContent = isDirect ? 'حفظ وإدراج الصنف في الفاتورة فوراً' : 'حفظ الصنف في الدليل فقط';
                }
            }
        });
    }

    if (quickProdPurchaseQty) {
        quickProdPurchaseQty.addEventListener('input', syncModalPurchaseSubtotal);
    }

    // Helper to bind bidirectional syncing between piece and dozen inputs
    function bindTwoWayPriceSync(pieceInput, dozenInput, onUpdate) {
        if (!pieceInput || !dozenInput) return;
        pieceInput.addEventListener('input', () => {
            const factor = getQuickFactor();
            const pVal = parseFloat(pieceInput.value);
            if (!isNaN(pVal)) {
                dozenInput.value = (pVal * factor).toFixed(2);
            } else {
                dozenInput.value = '';
            }
            if (onUpdate) onUpdate();
        });
        dozenInput.addEventListener('input', () => {
            const factor = getQuickFactor();
            const dVal = parseFloat(dozenInput.value);
            if (!isNaN(dVal)) {
                pieceInput.value = (dVal / factor).toFixed(2);
            } else {
                pieceInput.value = '';
            }
            if (onUpdate) onUpdate();
        });
    }

    // 1. Cost Price (Purchase)
    bindTwoWayPriceSync(quickProdCostPiece, quickProdCostDozen, () => {
        autoSuggestSellingPrices();
        syncModalPurchaseSubtotal();
    });

    // 2. Retail Price (Selling)
    bindTwoWayPriceSync(quickProdRetailPiece, quickProdRetailDozen, null);

    // 3. Wholesale Price (Selling)
    bindTwoWayPriceSync(quickProdWholesalePiece, quickProdWholesaleDozen, null);

    // 4. Distributor Price (Selling)
    bindTwoWayPriceSync(quickProdDistributorPiece, quickProdDistributorDozen, null);

    // Package factor changes (recalculate all dozen prices live)
    if (quickProdPkgFactor) {
        quickProdPkgFactor.addEventListener('input', () => {
            const factor = getQuickFactor();
            syncFactorLabels(factor);

            if (quickProdCostPiece && quickProdCostPiece.value) {
                quickProdCostDozen.value = (parseFloat(quickProdCostPiece.value) * factor).toFixed(2);
            }
            if (quickProdRetailPiece && quickProdRetailPiece.value) {
                quickProdRetailDozen.value = (parseFloat(quickProdRetailPiece.value) * factor).toFixed(2);
            }
            if (quickProdWholesalePiece && quickProdWholesalePiece.value) {
                quickProdWholesaleDozen.value = (parseFloat(quickProdWholesalePiece.value) * factor).toFixed(2);
            }
            if (quickProdDistributorPiece && quickProdDistributorPiece.value) {
                quickProdDistributorDozen.value = (parseFloat(quickProdDistributorPiece.value) * factor).toFixed(2);
            }
            syncModalPurchaseSubtotal();
        });
    }

    // Barcode Generator
    if (generateInternalBarcodeBtn) {
        generateInternalBarcodeBtn.addEventListener('click', async (e) => {
            e.preventDefault();
            generateInternalBarcodeBtn.disabled = true;
            try {
                const res = await fetch('/products/generate-barcode', {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (data.barcode && quickProdBarcode) {
                    quickProdBarcode.value = data.barcode;
                }
            } catch (err) {
                console.error(err);
            } finally {
                generateInternalBarcodeBtn.disabled = false;
            }
        });
    }

    // SKU Generator
    if (regenerateQuickSkuBtn) {
        regenerateQuickSkuBtn.addEventListener('click', () => {
            const random = Math.floor(1000 + Math.random() * 9000);
            const yr = new Date().getFullYear().toString().slice(-2);
            if (quickProdSku) quickProdSku.value = `KY-${yr}-${random}`;
        });
    }

    // Quick Product Form Submission
    if (quickProductForm) {
        quickProductForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            if (quickProductAlert) quickProductAlert.classList.add('hidden');
            if (saveQuickProductBtn) saveQuickProductBtn.disabled = true;

            const shouldInsertDirectly = quickProdInsertDirectly ? quickProdInsertDirectly.checked : true;
            const purchaseUnitType = quickProdPurchaseUnitVal ? quickProdPurchaseUnitVal.value : 'piece';
            const purchaseQty = parseFloat(quickProdPurchaseQty ? quickProdPurchaseQty.value : 1) || 1;
            const pkgFactor = getQuickFactor();
            const pieceCostVal = parseFloat(quickProdCostPiece ? quickProdCostPiece.value : 0) || 0;
            const dozenCostVal = parseFloat(quickProdCostDozen ? quickProdCostDozen.value : 0) || (pieceCostVal * pkgFactor);

            const formData = new FormData(quickProductForm);
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            try {
                const response = await fetch(quickProductForm.action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: formData,
                });

                const result = await response.json();

                if (!response.ok || !result.success) {
                    let errMsg = result.message || 'حدث خطأ أثناء حفظ الصنف.';
                    if (result.errors) {
                        const firstErr = Object.values(result.errors)[0];
                        if (Array.isArray(firstErr) && firstErr.length > 0) errMsg = firstErr[0];
                    }
                    if (quickProductAlert && quickProductAlertMsg) {
                        quickProductAlertMsg.textContent = errMsg;
                        quickProductAlert.classList.remove('hidden');
                    } else {
                        alert(errMsg);
                    }
                    if (saveQuickProductBtn) saveQuickProductBtn.disabled = false;
                    return;
                }

                const newProd = result.product;
                // Add to local products data
                productsData.unshift(newProd);

                closeQuickProductModal();

                if (shouldInsertDirectly) {
                    // Resolve unit details based on piece vs dozen
                    let unitName = newProd.unit || 'قطعة';
                    let unitId = null;
                    let conversionFactor = 1.0;
                    let unitCost = pieceCostVal > 0 ? pieceCostVal : (parseFloat(newProd.cost_price) || 0);

                    if (purchaseUnitType === 'dozen') {
                        conversionFactor = pkgFactor;
                        unitCost = dozenCostVal > 0 ? dozenCostVal : (unitCost * pkgFactor);
                        // Search for a matching unit with this conversion factor or name in unitsData
                        const matchedUnit = unitsData.find(u => Math.abs(parseFloat(u.factor || 1.0) - pkgFactor) < 0.001 || (u.name && u.name.includes('دست')));
                        if (matchedUnit) {
                            unitId = matchedUnit.id;
                            unitName = matchedUnit.name;
                        } else {
                            unitName = `دستة (×${pkgFactor})`;
                        }
                    } else {
                        // Single piece / base unit
                        const baseUnit = unitsData.find(u => u.name === newProd.unit || (u.factor && parseFloat(u.factor) === 1.0) || u.is_default);
                        if (baseUnit) {
                            unitId = baseUnit.id;
                            unitName = baseUnit.name;
                        }
                        conversionFactor = 1.0;
                    }

                    // Directly insert at top of table
                    insertRowAtTop({
                        productId: newProd.id,
                        productName: newProd.name,
                        productSku: newProd.sku,
                        quantity: purchaseQty,
                        unitCost: unitCost,
                        unitId: unitId,
                        unitName: unitName,
                        conversionFactor: conversionFactor,
                        notes: ''
                    });

                    recalculateTotals();

                    // Select in top search bar too for ease of continued work
                    selectProduct(newProd);
                } else {
                    // Just select in search bar and focus quantity
                    selectProduct(newProd);
                    if (quickQtyInput) {
                        quickQtyInput.focus();
                        quickQtyInput.select();
                    }
                }
            } catch (err) {
                console.error(err);
                if (quickProductAlert && quickProductAlertMsg) {
                    quickProductAlertMsg.textContent = 'تعذر الاتصال بالخادم. يرجى المحاولة لاحقاً.';
                    quickProductAlert.classList.remove('hidden');
                }
            } finally {
                if (saveQuickProductBtn) saveQuickProductBtn.disabled = false;
            }
        });
    }

    // Quick Supplier Modal Integration (Same as POS Customer Modal)
    const supplierSelect = document.getElementById('supplierSelect') || document.getElementById('supplierId');
    const quickAddSupplierBtn = document.getElementById('quickAddSupplierBtn');
    const quickSupplierModal = document.getElementById('quickSupplierModal');
    const closeQuickSupplierModalBtn = document.getElementById('closeQuickSupplierModalBtn');
    const cancelQuickSupplierBtn = document.getElementById('cancelQuickSupplierBtn');
    const quickSupplierForm = document.getElementById('quickSupplierForm');
    const quickSupplierAlert = document.getElementById('quickSupplierAlert');
    const quickSupplierAlertMsg = document.getElementById('quickSupplierAlertMsg');
    const saveQuickSupplierBtn = document.getElementById('saveQuickSupplierBtn');
    const quickSupType = document.getElementById('quick_sup_type');
    const quickSupChinaFields = document.getElementById('quick_sup_china_fields');
    const quickSupCountry = document.getElementById('quick_sup_country');
    const quickSupCurrency = document.getElementById('quick_sup_currency');
    const quickSupName = document.getElementById('quick_sup_name');

    let previousSupplierValue = supplierSelect ? supplierSelect.value : '';

    function openSupplierModal() {
        if (!quickSupplierModal) return;
        if (quickSupplierForm) quickSupplierForm.reset();
        if (quickSupplierAlert) quickSupplierAlert.classList.add('hidden');
        if (quickSupChinaFields) quickSupChinaFields.classList.add('hidden');
        if (quickSupCountry) quickSupCountry.value = 'مصر';
        if (quickSupCurrency) quickSupCurrency.value = 'EGP';
        quickSupplierModal.classList.add('open');
        if (quickSupName) setTimeout(() => quickSupName.focus(), 120);
    }

    function closeSupplierModal() {
        if (!quickSupplierModal) return;
        quickSupplierModal.classList.remove('open');
        if (supplierSelect && supplierSelect.value === '__add_new__') {
            supplierSelect.value = previousSupplierValue;
        }
    }

    if (quickAddSupplierBtn) {
        quickAddSupplierBtn.addEventListener('click', (e) => {
            e.preventDefault();
            openSupplierModal();
        });
    }

    if (closeQuickSupplierModalBtn) {
        closeQuickSupplierModalBtn.addEventListener('click', (e) => {
            e.preventDefault();
            closeSupplierModal();
        });
    }

    if (cancelQuickSupplierBtn) {
        cancelQuickSupplierBtn.addEventListener('click', (e) => {
            e.preventDefault();
            closeSupplierModal();
        });
    }

    if (quickSupplierModal) {
        quickSupplierModal.addEventListener('click', (e) => {
            if (e.target === quickSupplierModal) {
                closeSupplierModal();
            }
        });
    }

    if (quickSupType) {
        quickSupType.addEventListener('change', () => {
            const isChina = quickSupType.value === 'china_factory';
            if (quickSupChinaFields) {
                if (isChina) {
                    quickSupChinaFields.classList.remove('hidden');
                } else {
                    quickSupChinaFields.classList.add('hidden');
                }
            }
            if (isChina) {
                if (quickSupCountry && (!quickSupCountry.value || quickSupCountry.value === 'مصر')) {
                    quickSupCountry.value = 'China';
                }
                if (quickSupCurrency) {
                    quickSupCurrency.value = 'CNY';
                }
            } else {
                if (quickSupCountry && quickSupCountry.value === 'China') {
                    quickSupCountry.value = 'مصر';
                }
                if (quickSupCurrency) {
                    quickSupCurrency.value = 'EGP';
                }
            }
        });
    }

    if (supplierSelect) {
        supplierSelect.addEventListener('focus', () => {
            if (supplierSelect.value !== '__add_new__') {
                previousSupplierValue = supplierSelect.value;
            }
        });

        supplierSelect.addEventListener('change', () => {
            if (supplierSelect.value === '__add_new__') {
                openSupplierModal();
            } else {
                previousSupplierValue = supplierSelect.value;
            }
        });
    }

    if (quickSupplierForm) {
        quickSupplierForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            if (quickSupplierAlert) quickSupplierAlert.classList.add('hidden');
            if (saveQuickSupplierBtn) saveQuickSupplierBtn.disabled = true;

            const formData = new FormData(quickSupplierForm);
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            try {
                const response = await fetch(quickSupplierForm.action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: formData,
                });

                const result = await response.json();

                if (!response.ok || !result.success) {
                    let errMsg = result.message || 'حدث خطأ أثناء حفظ المورد.';
                    if (result.errors) {
                        const firstErr = Object.values(result.errors)[0];
                        if (Array.isArray(firstErr) && firstErr.length > 0) errMsg = firstErr[0];
                    }
                    if (quickSupplierAlert && quickSupplierAlertMsg) {
                        quickSupplierAlertMsg.textContent = errMsg;
                        quickSupplierAlert.classList.remove('hidden');
                    } else {
                        alert(errMsg);
                    }
                    if (saveQuickSupplierBtn) saveQuickSupplierBtn.disabled = false;
                    return;
                }

                const newSup = result.supplier;
                if (supplierSelect && newSup) {
                    const opt = document.createElement('option');
                    opt.value = newSup.id;
                    opt.dataset.type = newSup.supplier_type || 'local_supplier';
                    opt.dataset.currency = newSup.currency || 'EGP';
                    opt.textContent = `${newSup.name} (${newSup.country || 'مورد'})`;
                    supplierSelect.appendChild(opt);

                    supplierSelect.value = newSup.id;
                    previousSupplierValue = newSup.id;
                    supplierSelect.dispatchEvent(new Event('change'));
                }

                closeSupplierModal();
            } catch (err) {
                console.error(err);
                if (quickSupplierAlert && quickSupplierAlertMsg) {
                    quickSupplierAlertMsg.textContent = 'تعذر الاتصال بالخادم. يرجى المحاولة مرة أخرى.';
                    quickSupplierAlert.classList.remove('hidden');
                } else {
                    alert('تعذر الاتصال بالخادم.');
                }
            } finally {
                if (saveQuickSupplierBtn) saveQuickSupplierBtn.disabled = false;
            }
        });
    }

    // F3 Global Shortcut to open quick product modal
    document.addEventListener('keydown', (e) => {
        if (e.key === 'F3') {
            e.preventDefault();
            const term = (quickProductInput?.value || '').trim();
            const isNum = /^[0-9]+$/.test(term);
            openQuickProductModal({
                name: !isNum ? term : '',
                barcode: isNum ? term : ''
            });
        }
    });

    reindexRows();
    recalculateTotals();

    // Initial focus on search input
    if (quickProductInput) {
        setTimeout(() => quickProductInput.focus(), 100);
    }
}

function initPurchaseShow() {
    const printBtn = document.querySelector('[data-action="print-window"]');
    if (printBtn) {
        printBtn.addEventListener('click', () => {
            window.print();
        });
    }
}

function initProductLedger() {
    const printBtn = document.querySelector('[data-action="print-window"]');
    if (printBtn) {
        printBtn.addEventListener('click', () => {
            window.print();
        });
    }
}
