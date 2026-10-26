/**
 * Kayan ERP — High-Speed Sales & Point of Sale (POS) Client
 * Ergonomic Barcode Scanning, Instant Item Merging, Steppers, and Dynamic Tier Pricing.
 */

export function initSales() {
    const posPage = document.querySelector('[data-page="sales-create"], [data-page="sales-edit"]');
    if (posPage) {
        initSalesPOS(posPage);
    }

    const showPage = document.querySelector('[data-page="sales-show"]');
    if (showPage) {
        initSalesShow();
    }
}

function initSalesPOS(posPage = null) {
    const createPage = posPage || document.querySelector('[data-page="sales-create"], [data-page="sales-edit"]');
    if (!createPage) return;

    // Elements
    const salesForm = document.getElementById('salesForm');
    const tableBody = document.getElementById('salesItemsBody');
    const manualAddBtn = document.getElementById('addSalesItemBtn');
    const clearCartBtn = document.getElementById('clearCartBtn');
    const posSubmitBtn = document.getElementById('posSubmitBtn');

    // Meta Elements
    const customerSelect = document.getElementById('customerSelect');
    const customerTierHint = document.getElementById('customerTierHint');
    const customerBalanceHint = document.getElementById('customerBalanceHint');
    const warehouseSelect = document.getElementById('warehouseSelect');
    const priceTierSelect = document.getElementById('priceTierSelect');
    const shippingInput = document.getElementById('shippingCost');

    // Quick Entry Bar
    const searchModeSelect = document.getElementById('searchModeSelect');
    const fastScanToggle = document.getElementById('fastScanToggle');
    const fastScanBadge = document.getElementById('fastScanBadge');
    const quickProductInput = document.getElementById('quickProductInput');
    const quickSearchInputLabel = document.getElementById('quickSearchInputLabel');
    const quickSearchResults = document.getElementById('quickSearchResults');
    const quickProductId = document.getElementById('quickProductId');
    const quickStockBadge = document.getElementById('quickStockBadge');
    const quickUnitSelect = document.getElementById('quickUnitSelect');
    const quickUnitPieceBtn = document.getElementById('quickUnitPieceBtn');
    const quickUnitDozenBtn = document.getElementById('quickUnitDozenBtn');
    const quickUnitDozenLabel = document.getElementById('quickUnitDozenLabel');
    const quickQtyInput = document.getElementById('quickQtyInput');
    const quickQtyMinus = document.getElementById('quickQtyMinus');
    const quickQtyPlus = document.getElementById('quickQtyPlus');
    const quickPriceInput = document.getElementById('quickPriceInput');
    const quickLineDiscountInput = document.getElementById('quickLineDiscountInput');
    const quickAddBtn = document.getElementById('quickAddBtn');

    // Discount Elements
    const discountTypeSelect = document.getElementById('invoiceDiscountType');
    const discountValueInput = document.getElementById('invoiceDiscountValue');
    const discountUnitLabel = document.getElementById('discountUnitLabel');

    // Summary Elements
    const summaryItemsCount = document.getElementById('summaryItemsCount');
    const summaryTotalUnits = document.getElementById('summaryTotalUnits');
    const summaryItemsSubtotal = document.getElementById('summaryItemsSubtotal');
    const summaryDiscountDisplay = document.getElementById('summaryDiscountDisplay');
    const summaryDiscountHint = document.getElementById('summaryDiscountHint');
    const summaryGrandTotal = document.getElementById('summaryGrandTotal');

    // Payment & Deferred Credit Elements
    const posPaymentToggleGroup = document.getElementById('posPaymentToggleGroup');
    const posPaidAmountInput = document.getElementById('posPaidAmountInput');
    const posRemainingAmountInput = document.getElementById('posRemainingAmountInput');
    const posPaidHint = document.getElementById('posPaidHint');
    const posRemainingHint = document.getElementById('posRemainingHint');
    const posDueDateGroup = document.getElementById('posDueDateGroup');
    const customerCreditStatusBadge = document.getElementById('customerCreditStatusBadge');
    const creditLimitAlertBox = document.getElementById('creditLimitAlertBox');

    // Parse Data attributes
    let productsData = [];
    let unitsData = [];
    let customersData = [];
    const canEditPrice = createPage.dataset.canEditPrice === 'true';

    try {
        productsData = JSON.parse(createPage.dataset.products || '[]');
    } catch (e) {
        productsData = [];
    }

    try {
        unitsData = JSON.parse(createPage.dataset.units || '[]');
    } catch (e) {
        unitsData = [];
    }

    try {
        customersData = JSON.parse(createPage.dataset.customers || '[]');
    } catch (e) {
        customersData = [];
    }

    let activeSuggestionIndex = -1;
    let isSubmitting = false;
    let lastProductSelectTime = 0;

    // Helper: Get product price based on selected tier and target unit factor
    function getProductPriceForTier(product, tier = null, targetUnitFactor = null) {
        if (!product) return 0.00;
        const currentTier = tier || (priceTierSelect ? priceTierSelect.value : 'retail');
        let registeredPrice = 0.00;
        if (currentTier === 'distributor') {
            registeredPrice = parseFloat(product.distributor_price ?? product.wholesale_price ?? product.retail_price ?? 0);
        } else if (currentTier === 'wholesale') {
            registeredPrice = parseFloat(product.wholesale_price ?? product.retail_price ?? 0);
        } else {
            registeredPrice = parseFloat(product.retail_price ?? 0);
        }

        const prodFactor = parseFloat(product.unit_factor || 1.0) || 1.0;
        const basePiecePrice = registeredPrice / prodFactor;

        if (targetUnitFactor !== null && targetUnitFactor !== undefined && targetUnitFactor > 0) {
            return basePiecePrice * targetUnitFactor;
        }

        return registeredPrice;
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

    // Helper: Get available warehouse stock
    function getProductWarehouseStock(product, warehouseId = null) {
        if (!product) return 0;
        const whId = warehouseId || (warehouseSelect ? parseInt(warehouseSelect.value, 10) : null);
        if (whId && product.stocks) {
            if (product.stocks[whId] !== undefined) {
                return parseFloat(product.stocks[whId]) || 0;
            }
            if (product.stocks[String(whId)] !== undefined) {
                return parseFloat(product.stocks[String(whId)]) || 0;
            }
        }
        return parseFloat(product.total_stock) || 0;
    }

    // Update Quick Stock Badge
    function updateStockBadge(product) {
        if (!quickStockBadge) return;
        if (!product) {
            quickStockBadge.classList.add('hidden');
            return;
        }

        const stock = getProductWarehouseStock(product);
        const formatted = formatStockBreakdown(product, stock);

        quickStockBadge.classList.remove('hidden', 'in-stock', 'low-stock', 'out-of-stock');

        if (stock <= 0) {
            quickStockBadge.classList.add('out-of-stock');
            quickStockBadge.textContent = `رصيد الصنف: ${formatted} (نفد من المخزن)`;
        } else if (stock <= 5) {
            quickStockBadge.classList.add('low-stock');
            quickStockBadge.textContent = `رصيد الصنف بالمخزن: ${formatted} (منخفض)`;
        } else {
            quickStockBadge.classList.add('in-stock');
            quickStockBadge.textContent = `رصيد الصنف بالمخزن: ${formatted}`;
        }
    }

    // Helper: Update price in quick bar based on currently selected product, unit and tier
    function updateQuickPrice() {
        const pId = quickProductId ? parseInt(quickProductId.value, 10) : null;
        if (!pId) return;
        const prod = productsData.find(p => p.id === pId);
        if (!prod || !quickPriceInput) return;

        const opt = quickUnitSelect ? quickUnitSelect.options[quickUnitSelect.selectedIndex] : null;
        const selectedFactor = parseFloat(opt?.dataset.factor || 1.0) || 1.0;
        const price = getProductPriceForTier(prod, null, selectedFactor);
        quickPriceInput.value = price.toFixed(2);
        syncUnitPillState(selectedFactor);
    }

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
        updateQuickPrice();
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
        updateQuickPrice();
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

    // Customer Change Handler: Auto-sets price tier
    if (customerSelect) {
        customerSelect.addEventListener('change', () => {
            const custId = parseInt(customerSelect.value, 10);
            const customer = customersData.find(c => c.id === custId);
            if (customer && priceTierSelect) {
                const tier = customer.pricing_tier || 'retail';
                priceTierSelect.value = tier;
                if (customerTierHint) {
                    const label = tier === 'distributor' ? 'فئة: موزع' : (tier === 'wholesale' ? 'فئة: جملة' : 'فئة: تجزئة');
                    customerTierHint.textContent = label;
                }
                updateQuickPrice();
            }
            updatePaymentAndCreditStatus();
        });
    }

    // Warehouse Change Handler: Updates stock badge
    if (warehouseSelect) {
        warehouseSelect.addEventListener('change', () => {
            const pId = quickProductId ? parseInt(quickProductId.value, 10) : null;
            if (pId) {
                const prod = productsData.find(p => p.id === pId);
                if (prod) updateStockBadge(prod);
            }
        });
    }

    // Fast-Scan Mode Toggle
    if (fastScanToggle && fastScanBadge) {
        const savedFastScan = localStorage.getItem('pos_fast_scan');
        const isFastScanEnabled = savedFastScan !== null ? (savedFastScan === 'true') : false;
        fastScanToggle.checked = isFastScanEnabled;
        fastScanBadge.classList.toggle('active', isFastScanEnabled);
        const scanLabel = fastScanBadge.querySelector('span');
        if (scanLabel) {
            scanLabel.textContent = isFastScanEnabled ? 'المسح المباشر السريع (مفعل)' : 'المسح المباشر السريع (معطل)';
        }

        fastScanToggle.addEventListener('change', () => {
            fastScanBadge.classList.toggle('active', fastScanToggle.checked);
            localStorage.setItem('pos_fast_scan', fastScanToggle.checked ? 'true' : 'false');
            if (scanLabel) {
                scanLabel.textContent = fastScanToggle.checked ? 'المسح المباشر السريع (مفعل)' : 'المسح المباشر السريع (معطل)';
            }
            if (quickProductInput) quickProductInput.focus({ preventScroll: true });
        });
    }

    // Steppers in Quick Bar
    if (quickQtyMinus && quickQtyInput) {
        quickQtyMinus.addEventListener('click', (e) => {
            e.preventDefault();
            let v = parseFloat(quickQtyInput.value) || 1;
            if (v > 1) {
                quickQtyInput.value = (v - 1).toFixed(0);
            }
        });
    }
    if (quickQtyPlus && quickQtyInput) {
        quickQtyPlus.addEventListener('click', (e) => {
            e.preventDefault();
            let v = parseFloat(quickQtyInput.value) || 1;
            quickQtyInput.value = (v + 1).toFixed(0);
        });
    }

    // Tier Change Handler
    if (priceTierSelect) {
        priceTierSelect.addEventListener('change', () => {
            updateQuickPrice();
        });
    }

    // Search Mode Change Handler
    if (searchModeSelect) {
        searchModeSelect.addEventListener('change', () => {
            const mode = searchModeSelect.value;
            if (mode === 'barcode') {
                if (quickSearchInputLabel) quickSearchInputLabel.textContent = 'مربع الباركود (مسدس الباركود)';
                if (quickProductInput) quickProductInput.placeholder = 'اضرب الباركود بمسدس الباركود (F2)...';
            } else if (mode === 'sku') {
                if (quickSearchInputLabel) quickSearchInputLabel.textContent = 'كود الصنف (SKU)';
                if (quickProductInput) quickProductInput.placeholder = 'اكتب كود الصنف واضغط Enter...';
            } else {
                if (quickSearchInputLabel) quickSearchInputLabel.textContent = 'اسم الصنف';
                if (quickProductInput) quickProductInput.placeholder = 'اكتب اسم الصنف للبحث...';
            }
            if (quickProductInput) quickProductInput.focus();
        });
    }

    // Discounts & Shipping handlers
    function updateDiscountLabel() {
        if (!discountTypeSelect || !discountUnitLabel) return;
        discountUnitLabel.textContent = discountTypeSelect.value === 'percentage' ? '%' : 'ج.م';
        recalculateTotals();
    }

    if (discountTypeSelect) discountTypeSelect.addEventListener('change', updateDiscountLabel);
    if (discountValueInput) discountValueInput.addEventListener('input', recalculateTotals);
    if (shippingInput) shippingInput.addEventListener('input', recalculateTotals);

    function cleanSearchTerm(term) {
        if (!term) return '';
        let str = term.toString().trim();
        // 1. Convert Arabic-Indic digits (٠-٩) and Persian digits to standard Western digits (0-9)
        const arabicIndic = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩', '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        const standardDigits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        for (let i = 0; i < arabicIndic.length; i++) {
            str = str.replaceAll(arabicIndic[i], standardDigits[i]);
        }
        // 2. Remove scanner prefix (AIM symbols like ]C1, ]e0, ]E0, etc.)
        str = str.replace(/^\][A-Za-z0-9]{2}/, '');
        // 3. Remove non-printable control characters, CR, LF, TAB
        str = str.replace(/[\r\n\t\x00-\x1F\x7F-\x9F]/g, '');
        return str.trim();
    }

    function normalizeArabic(text) {
        if (!text) return '';
        return cleanSearchTerm(text).toLowerCase()
            .replace(/[أإآ]/g, 'ا')
            .replace(/ة/g, 'ه')
            .replace(/ى/g, 'ي')
            .replace(/[\u064B-\u065F]/g, '');
    }

    // Product Lookup logic
    function findProductByTerm(term, mode = 'barcode') {
        const clean = cleanSearchTerm(term).toLowerCase();
        if (!clean) return null;

        // 0. If quickProductId has already selected an item, verify and return it
        if (quickProductId && quickProductId.value) {
            const currentId = parseInt(quickProductId.value, 10);
            if (!isNaN(currentId) && currentId > 0) {
                const found = productsData.find(p => p.id === currentId);
                if (found) return found;
            }
        }

        // 0b. If term contains formatted pattern "Name (SKU)", extract SKU inside parenthesis
        const bracketMatch = clean.match(/\(([^)]+)\)$/);
        if (bracketMatch && bracketMatch[1]) {
            const extractedSku = bracketMatch[1].trim().toLowerCase();
            const foundByBracket = productsData.find(p => (p.sku || '').trim().toLowerCase() === extractedSku);
            if (foundByBracket) return foundByBracket;
        }

        const norm = normalizeArabic(clean);
        const cleanAlpha = clean.replace(/[^a-z0-9\u0600-\u06FF]/g, '');

        // 1. Exact barcode match
        const exactBarcode = productsData.find(p => cleanSearchTerm(p.barcode || '').toLowerCase() === clean);
        if (exactBarcode) return exactBarcode;

        // 2. Exact SKU match
        const exactSku = productsData.find(p => (p.sku || '').trim().toLowerCase() === clean);
        if (exactSku) return exactSku;

        // 2b. Alphanumeric SKU match (ignoring dashes, spaces, e.g. "hb89" or "hb 89" matches "HB-89")
        if (cleanAlpha.length >= 2) {
            const alphaSkuMatch = productsData.find(p => (p.sku || '').replace(/[^a-z0-9\u0600-\u06FF]/gi, '').toLowerCase() === cleanAlpha);
            if (alphaSkuMatch) return alphaSkuMatch;
        }

        // 3. Exact Name match (normalized)
        const exactName = productsData.find(p => normalizeArabic(p.name) === norm);
        if (exactName) return exactName;

        // 4. Starts with name or contains name
        const nameMatch = productsData.find(p => normalizeArabic(p.name).includes(norm));
        if (nameMatch) return nameMatch;

        // 5. Universal fallback: contains barcode, sku, or name
        return productsData.find(p => 
            cleanSearchTerm(p.barcode || '').toLowerCase().includes(clean) ||
            (p.sku || '').toLowerCase().includes(clean) ||
            normalizeArabic(p.name).includes(norm)
        ) || null;
    }

    function renderSuggestions(query) {
        if (!quickSearchResults) return;
        const q = cleanSearchTerm(query);
        if (!q || q.length < 1) {
            quickSearchResults.classList.add('hidden');
            quickSearchResults.innerHTML = '';
            activeSuggestionIndex = -1;
            return;
        }

        const cleanQ = q.toLowerCase();
        const normQ = normalizeArabic(q);
        const cleanAlphaQ = cleanQ.replace(/[^a-z0-9\u0600-\u06FF]/g, '');
        const mode = searchModeSelect ? searchModeSelect.value : 'barcode';

        const matches = productsData.filter(p => {
            const pBarcode = cleanSearchTerm(p.barcode || '').toLowerCase();
            const pSku = (p.sku || '').toLowerCase();
            const pSkuAlpha = pSku.replace(/[^a-z0-9\u0600-\u06FF]/g, '');
            const pNameNorm = normalizeArabic(p.name);

            const matchesBarcode = pBarcode.includes(cleanQ);
            const matchesSku = pSku.includes(cleanQ) || (cleanAlphaQ.length >= 2 && pSkuAlpha.includes(cleanAlphaQ));
            const matchesName = pNameNorm.includes(normQ);

            if (mode === 'sku') {
                return matchesSku || matchesName || matchesBarcode;
            }
            if (mode === 'name') {
                return matchesName || matchesSku || matchesBarcode;
            }
            // Universal match: works for barcode, SKU, and name seamlessly
            return matchesBarcode || matchesSku || matchesName;
        }).slice(0, 10);

        if (matches.length === 0) {
            quickSearchResults.innerHTML = `
                <div class="px-3 py-2 text-xs text-[var(--text-muted)] text-center">
                    لا يوجد صنف مطابق لـ "${query}"
                </div>
            `;
            quickSearchResults.classList.remove('hidden');
            activeSuggestionIndex = -1;
            return;
        }

        quickSearchResults.innerHTML = matches.map((p, idx) => {
            const price = getProductPriceForTier(p);
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
                <div class="suggestion-item pos-suggestion-item" data-index="${idx}" data-id="${p.id}">
                    <div class="flex flex-col flex-1 min-w-0 pr-1">
                        <span class="item-title truncate font-bold">${p.name}</span>
                        <div class="item-meta flex items-center gap-2 mt-1">
                            <span class="font-numeric">كود: ${p.sku}</span>
                            ${p.barcode ? '<span class="font-numeric">· باركود: ' + p.barcode + '</span>' : ''}
                            <span class="pos-suggestion-badge ${badgeClass}">${badgeText}</span>
                        </div>
                    </div>
                    <div class="text-left font-numeric flex flex-col items-end pl-1 shrink-0">
                        <span class="pos-suggestion-price">${price.toFixed(2)} <span class="text-[11px] font-normal text-[var(--text-muted)]">ج.م</span></span>
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
                    selectProduct(found, false);
                }
            });
        });

        quickSearchResults.classList.remove('hidden');
        activeSuggestionIndex = -1;
    }

    function selectProduct(product, autoCommitIfFast = false) {
        if (!product) return;

        if (quickProductId) quickProductId.value = product.id;
        if (quickProductInput) quickProductInput.value = `${product.name} (${product.sku})`;

        const pkgFactor = parseInt(product.package_factor || product.pieces_per_package || product.unit_factor || 12, 10);
        if (quickUnitDozenLabel) {
            quickUnitDozenLabel.textContent = `دستة (* ${pkgFactor})`;
        }

        // 1. Select matching unit
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
        }

        // 2. Set price for that unit and update stock badge
        updateQuickPrice();
        updateStockBadge(product);

        // Hide suggestions
        if (quickSearchResults) {
            quickSearchResults.classList.add('hidden');
            quickSearchResults.innerHTML = '';
        }

        // If Fast-Scan Mode is explicitly enabled: IMMEDIATELY ADD/INCREMENT TO TABLE!
        if (autoCommitIfFast && fastScanToggle && fastScanToggle.checked) {
            commitQuickAdd();
            return;
        }

        // Otherwise follow POS keyboard flow: focus Quantity & select for instant editing without scrolling
        lastProductSelectTime = Date.now();
        if (quickQtyInput) {
            setTimeout(() => {
                quickQtyInput.focus({ preventScroll: true });
                quickQtyInput.select();
            }, 30);
        }
    }

    // Search Input Keydown & Input
    if (quickProductInput) {
        quickProductInput.addEventListener('input', (e) => {
            const term = e.target.value.trim();
            if (quickProductId && quickProductId.value) {
                quickProductId.value = '';
                if (quickStockBadge) quickStockBadge.classList.add('hidden');
            }
            renderSuggestions(term);
        });

        quickProductInput.addEventListener('keydown', async (e) => {
            const mode = searchModeSelect ? searchModeSelect.value : 'barcode';

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
                }
                return;
            }

            if (e.key === 'Enter') {
                e.preventDefault(); // Stop main form submission!

                // 1. If quickProductId is ALREADY populated with a selected product:
                // hitting Enter commits it immediately to the invoice!
                if (quickProductId && quickProductId.value) {
                    const pId = parseInt(quickProductId.value, 10);
                    const found = productsData.find(item => item.id === pId);
                    if (found) {
                        commitQuickAdd();
                        return;
                    }
                }

                // 2. If an item in dropdown is actively selected with arrow keys:
                const items = quickSearchResults ? quickSearchResults.querySelectorAll('.suggestion-item') : [];
                if (items.length > 0 && activeSuggestionIndex >= 0 && items[activeSuggestionIndex]) {
                    const pId = parseInt(items[activeSuggestionIndex].dataset.id, 10);
                    const found = productsData.find(item => item.id === pId);
                    if (found) {
                        selectProduct(found, false);
                        commitQuickAdd();
                        return;
                    }
                }

                // 3. Search by entered term / scanned barcode
                const rawTerm = quickProductInput.value;
                const cleanTerm = cleanSearchTerm(rawTerm);
                if (!cleanTerm) {
                    return;
                }

                let matched = findProductByTerm(cleanTerm, mode);

                // Fallback to top suggestion if not exact matched but dropdown has results
                if (!matched && items.length > 0) {
                    const firstId = parseInt(items[0].dataset.id, 10);
                    matched = productsData.find(item => item.id === firstId);
                }

                // Fallback to Server AJAX lookup if not found in client-side memory
                if (!matched) {
                    try {
                        const res = await fetch(`/products/lookup-barcode?barcode=${encodeURIComponent(cleanTerm)}`, {
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                        });
                        if (res.ok) {
                            const data = await res.json();
                            if (data && data.found && data.product) {
                                const newProd = {
                                    id: data.product.id,
                                    name: data.product.name,
                                    sku: data.product.sku,
                                    barcode: (data.product.barcode || ''),
                                    retail_price: parseFloat(data.product.retail_price || 0),
                                    wholesale_price: parseFloat(data.product.wholesale_price || 0),
                                    distributor_price: parseFloat(data.product.distributor_price || data.product.wholesale_price || 0),
                                    cost_price: parseFloat(data.product.cost_price || 0),
                                    unit: data.product.unit || 'قطعة',
                                    unit_factor: parseFloat(data.product.unit_factor || 1.0),
                                    package_factor: parseFloat(data.product.package_factor || 12.0),
                                    pieces_per_package: parseInt(data.product.pieces_per_package || 12, 10),
                                    stocks: data.product.stocks || {},
                                    total_stock: parseFloat(data.product.total_stock || 0),
                                    formatted_stock: data.product.formatted_stock || 'متوفر'
                                };
                                productsData.push(newProd);
                                matched = newProd;
                            }
                        }
                    } catch (err) {
                        console.warn('Fallback server lookup error:', err);
                    }
                }

                if (matched) {
                    const isFastScan = !!(fastScanToggle && fastScanToggle.checked);
                    if (isFastScan) {
                        // High-speed POS scan (only if explicitly enabled by user):
                        selectProduct(matched, true);
                    } else {
                        // Default POS workflow: select product and jump directly to Quantity field without committing/dropping!
                        selectProduct(matched, false);
                    }
                } else {
                    alert(`لم يتم العثور على صنف مطابق لهذا الباركود أو الكود: "${cleanTerm}"`);
                    quickProductInput.select();
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

    // Keyboard Flow: Unit -> Tab -> Qty
    if (quickUnitSelect) {
        quickUnitSelect.addEventListener('change', () => {
            updateQuickPrice();
        });

        quickUnitSelect.addEventListener('keydown', (e) => {
            if (e.key === 'Tab' && !e.shiftKey) {
                e.preventDefault();
                if (quickQtyInput) {
                    quickQtyInput.focus({ preventScroll: true });
                    quickQtyInput.select();
                }
            } else if (e.key === 'Enter') {
                e.preventDefault();
                commitQuickAdd();
            }
        });
    }

    // Keyboard Flow: Quantity -> Tab -> (Price if editable, else Discount)
    if (quickQtyInput) {
        quickQtyInput.addEventListener('keydown', (e) => {
            // Direct shortcut from quantity field: '*' or 'د' or 'd' switches unit to dozen (دستة)
            if (e.key === '*' || e.key === 'd' || e.key === 'D' || e.key === 'د') {
                e.preventDefault();
                switchToDozen();
                return;
            }
            // Direct shortcut from quantity field: '/' or 'ق' or 'p' switches unit to piece (قطعة)
            if (e.key === '/' || e.key === 'p' || e.key === 'P' || e.key === 'ق') {
                e.preventDefault();
                switchToPiece();
                return;
            }

            if (e.key === 'Tab' && !e.shiftKey) {
                e.preventDefault();
                if (canEditPrice && quickPriceInput) {
                    quickPriceInput.focus({ preventScroll: true });
                    quickPriceInput.select();
                } else if (quickLineDiscountInput) {
                    quickLineDiscountInput.focus({ preventScroll: true });
                    quickLineDiscountInput.select();
                }
            } else if (e.key === 'Tab' && e.shiftKey) {
                e.preventDefault();
                if (quickUnitSelect) {
                    quickUnitSelect.focus({ preventScroll: true });
                }
            } else if (e.key === 'Enter') {
                e.preventDefault();
                commitQuickAdd();
            }
        });
    }

    // Keyboard Flow: Price -> Tab -> Line Discount
    if (quickPriceInput) {
        quickPriceInput.addEventListener('keydown', (e) => {
            if (e.key === 'Tab' && !e.shiftKey) {
                e.preventDefault();
                if (quickLineDiscountInput) {
                    quickLineDiscountInput.focus({ preventScroll: true });
                    quickLineDiscountInput.select();
                }
            } else if (e.key === 'Enter') {
                e.preventDefault();
                commitQuickAdd();
            }
        });
    }

    // Keyboard Flow: Line Discount -> Enter -> Commit & Refocus
    if (quickLineDiscountInput) {
        quickLineDiscountInput.addEventListener('keydown', (e) => {
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

    // COMMIT FUNCTION: Adds item or increments existing row
    function commitQuickAdd() {
        let pId = quickProductId ? parseInt(quickProductId.value, 10) : null;
        let product = productsData.find(p => p.id === pId);

        if (!product) {
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

        const price = parseFloat(quickPriceInput ? quickPriceInput.value : 0) || 0;
        if (price < 0) {
            alert('سعر البيع لا يمكن أن يكون سالباً.');
            if (quickPriceInput) quickPriceInput.focus();
            return;
        }

        const lineDisc = parseFloat(quickLineDiscountInput ? quickLineDiscountInput.value : 0) || 0;
        if (lineDisc < 0) {
            alert('خصم البند لا يمكن أن يكون سالباً.');
            if (quickLineDiscountInput) quickLineDiscountInput.focus();
            return;
        }

        const selectedUnitId = quickUnitSelect ? parseInt(quickUnitSelect.value, 10) : null;
        const selectedUnit = unitsData.find(u => u.id === selectedUnitId);
        const conversionFactor = selectedUnit ? (parseFloat(selectedUnit.factor) || 1.0) : 1.0;

        // CHECK DUPLICATE MERGING IN EXISTING TABLE
        // If an existing row has identical product_id, unit_id, unit_price, and line_discount:
        // INCREMENT QUANTITY!
        const existingRow = findMatchingRow(product.id, selectedUnitId, price, lineDisc);

        if (existingRow) {
            const qtyInput = existingRow.querySelector('.row-qty');
            if (qtyInput) {
                const currentQty = parseFloat(qtyInput.value) || 0;
                const newQty = currentQty + qty;
                qtyInput.value = (newQty % 1 === 0 ? newQty.toFixed(0) : newQty.toFixed(2));

                // Trigger row subtotal update
                const subtotalText = existingRow.querySelector('.row-subtotal-text');
                const lineSub = Math.max(0, (newQty * price) - lineDisc);
                if (subtotalText) subtotalText.textContent = lineSub.toFixed(2);

                // Flash animation feedback
                existingRow.classList.remove('item-updated');
                void existingRow.offsetWidth; // trigger reflow
                existingRow.classList.add('item-updated');
            }
        } else {
            // Insert New Row at TOP
            insertRowAtTop({
                productId: product.id,
                productName: product.name,
                productSku: product.sku,
                productBarcode: product.barcode,
                unitId: selectedUnitId,
                conversionFactor: conversionFactor,
                quantity: qty,
                unitPrice: price,
                lineDiscount: lineDisc,
                notes: ''
            });
        }

        // Reset quick input state
        if (quickProductInput) quickProductInput.value = '';
        if (quickProductId) quickProductId.value = '';
        if (quickStockBadge) quickStockBadge.classList.add('hidden');
        if (quickQtyInput) quickQtyInput.value = '1';
        if (quickPriceInput) quickPriceInput.value = '0.00';
        if (quickLineDiscountInput) quickLineDiscountInput.value = '0.00';
        if (quickSearchResults) quickSearchResults.classList.add('hidden');

        // ALWAYS RETURN FOCUS TO SEARCH INPUT FOR NEXT ITEM
        if (quickProductInput) {
            quickProductInput.focus({ preventScroll: true });
        }

        recalculateTotals();
    }

    // Match existing row for merging
    function findMatchingRow(productId, unitId, price, lineDisc) {
        if (!tableBody) return null;
        const rows = tableBody.querySelectorAll('.sales-item-row');
        for (const row of rows) {
            const rowPid = parseInt(row.getAttribute('data-product-id') || row.querySelector('.row-product-id')?.value, 10);
            const rowUnitId = parseInt(row.querySelector('.row-unit-select')?.value, 10);
            const rowPrice = parseFloat(row.querySelector('.row-price')?.value || 0);
            const rowDisc = parseFloat(row.querySelector('.row-discount')?.value || 0);

            if (rowPid === productId && rowUnitId === unitId && Math.abs(rowPrice - price) < 0.001 && Math.abs(rowDisc - lineDisc) < 0.001) {
                return row;
            }
        }
        return null;
    }

    function insertRowAtTop(data) {
        if (!tableBody) return;

        const rowIdx = Date.now() + Math.floor(Math.random() * 1000);
        const tr = document.createElement('tr');
        tr.className = 'sales-item-row item-updated';
        tr.dataset.rowIdx = rowIdx;
        tr.dataset.productId = data.productId;

        // Build Unit Options HTML
        let unitOptionsHtml = '';
        if (unitsData.length > 0) {
            unitsData.forEach(u => {
                const isSel = (u.id === data.unitId) ? 'selected' : '';
                unitOptionsHtml += `<option value="${u.id}" data-factor="${u.factor}" ${isSel}>${u.name}</option>`;
            });
        } else {
            unitOptionsHtml = `<option value="1" data-factor="1.0" selected>قطعة</option>`;
        }

        const lineSubtotal = Math.max(0, (data.quantity * data.unitPrice) - data.lineDiscount);
        const priceLockAttrs = canEditPrice ? '' : 'readonly tabindex="-1"';
        const priceLockClass = canEditPrice ? '' : 'bg-[var(--surface-muted)] cursor-not-allowed';

        const prodObj = productsData.find(p => p.id === data.productId);
        const rowStock = prodObj ? getProductWarehouseStock(prodObj) : 0;
        const rowStockFormatted = prodObj ? formatStockBreakdown(prodObj, rowStock) : '0 قطعة';

        tr.innerHTML = `
            <td class="text-center font-numeric row-counter">1</td>
            <td>
                <input type="hidden" name="items[${rowIdx}][product_id]" value="${data.productId}" class="row-product-id">
                <span class="font-bold text-[var(--text-main)]">${data.productName}</span>
                <div class="text-[11px] text-[var(--text-muted)] font-numeric flex flex-wrap items-center gap-1.5 mt-0.5">
                    <span>كود: ${data.productSku || '—'}</span>
                    ${data.productBarcode ? '<span>· باركود: ' + data.productBarcode + '</span>' : ''}
                    <span class="erp-badge ${rowStock <= 0 ? 'erp-badge-danger' : 'erp-badge-info'} text-[10px] py-0 px-1.5 font-bold">
                        رصيد الصنف: ${rowStockFormatted}
                    </span>
                </div>
            </td>
            <td>
                <select name="items[${rowIdx}][unit_id]" class="erp-input text-xs row-unit-select">
                    ${unitOptionsHtml}
                </select>
                <input type="hidden" name="items[${rowIdx}][conversion_factor]" value="${data.conversionFactor}" class="row-conversion-factor">
                <div class="row-factor-badge text-[11px] text-[var(--primary)] font-bold font-numeric mt-1 ${data.conversionFactor > 1 ? '' : 'hidden'}">
                    (×${data.conversionFactor} قطعة)
                </div>
            </td>
            <td>
                <div class="pos-qty-stepper mx-auto">
                    <button type="button" class="pos-qty-btn row-qty-minus" tabindex="-1">−</button>
                    <input type="number" step="0.01" min="0.01" name="items[${rowIdx}][quantity]" value="${data.quantity}" class="pos-qty-input font-numeric row-qty">
                    <button type="button" class="pos-qty-btn row-qty-plus" tabindex="-1">+</button>
                </div>
            </td>
            <td>
                <input type="number" step="0.01" min="0" name="items[${rowIdx}][unit_price]" value="${data.unitPrice.toFixed(2)}" class="erp-input font-numeric text-center text-xs row-price ${priceLockClass}" ${priceLockAttrs}>
            </td>
            <td>
                <input type="number" step="0.01" min="0" name="items[${rowIdx}][line_discount]" value="${data.lineDiscount.toFixed(2)}" class="erp-input font-numeric text-center text-xs row-discount">
            </td>
            <td class="text-center font-numeric font-bold text-xs row-subtotal-text">
                ${lineSubtotal.toFixed(2)}
            </td>
            <td>
                <input type="text" name="items[${rowIdx}][notes]" value="${data.notes || ''}" class="erp-input text-xs" placeholder="ملاحظة...">
            </td>
            <td class="text-center">
                <button type="button" class="erp-btn erp-btn-ghost erp-btn-sm text-[var(--danger)] remove-row-btn" title="حذف البند">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="3 6 5 6 21 6"></polyline>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                    </svg>
                </button>
            </td>
        `;

        // PREPEND TO TOP OF TABLE BODY
        tableBody.prepend(tr);
        bindRowEvents(tr);
        updateRowCounters();
    }

    function bindRowEvents(row) {
        const qtyInput = row.querySelector('.row-qty');
        const minusBtn = row.querySelector('.row-qty-minus');
        const plusBtn = row.querySelector('.row-qty-plus');
        const priceInput = row.querySelector('.row-price');
        const discountInput = row.querySelector('.row-discount');
        const unitSelect = row.querySelector('.row-unit-select');
        const factorHidden = row.querySelector('.row-conversion-factor');
        const subtotalText = row.querySelector('.row-subtotal-text');
        const removeBtn = row.querySelector('.remove-row-btn');

        function updateRowSubtotal() {
            const q = parseFloat(qtyInput?.value || 0);
            const p = parseFloat(priceInput?.value || 0);
            const d = parseFloat(discountInput?.value || 0);
            const sub = Math.max(0, (q * p) - d);
            if (subtotalText) {
                subtotalText.textContent = sub.toFixed(2);
            }
            recalculateTotals();
        }

        if (minusBtn && qtyInput) {
            minusBtn.addEventListener('click', (e) => {
                e.preventDefault();
                let q = parseFloat(qtyInput.value) || 1;
                if (q > 1) {
                    qtyInput.value = (q - 1).toFixed(0);
                    updateRowSubtotal();
                }
            });
        }

        if (plusBtn && qtyInput) {
            plusBtn.addEventListener('click', (e) => {
                e.preventDefault();
                let q = parseFloat(qtyInput.value) || 1;
                qtyInput.value = (q + 1).toFixed(0);
                updateRowSubtotal();
            });
        }

        if (qtyInput) qtyInput.addEventListener('input', updateRowSubtotal);
        if (priceInput) priceInput.addEventListener('input', updateRowSubtotal);
        if (discountInput) discountInput.addEventListener('input', updateRowSubtotal);

        const factorBadge = row.querySelector('.row-factor-badge');
        if (unitSelect && factorHidden) {
            unitSelect.addEventListener('change', () => {
                const opt = unitSelect.options[unitSelect.selectedIndex];
                const newFactor = parseFloat(opt?.dataset.factor || 1.0) || 1.0;
                factorHidden.value = newFactor;

                if (factorBadge) {
                    if (newFactor > 1.0) {
                        factorBadge.textContent = `(×${newFactor} قطعة)`;
                        factorBadge.classList.remove('hidden');
                    } else {
                        factorBadge.classList.add('hidden');
                    }
                }

                const rowPid = parseInt(row.getAttribute('data-product-id') || row.querySelector('.row-product-id')?.value, 10);
                const prod = productsData.find(p => p.id === rowPid);
                if (prod && priceInput) {
                    const price = getProductPriceForTier(prod, null, newFactor);
                    priceInput.value = price.toFixed(2);
                }

                updateRowSubtotal();
            });
        }

        if (removeBtn) {
            removeBtn.addEventListener('click', (e) => {
                e.preventDefault();
                row.remove();
                updateRowCounters();
                recalculateTotals();
                if (quickProductInput) quickProductInput.focus();
            });
        }
    }

    function updateRowCounters() {
        if (!tableBody) return;
        const rows = tableBody.querySelectorAll('.sales-item-row');
        rows.forEach((r, idx) => {
            const counter = r.querySelector('.row-counter');
            if (counter) counter.textContent = idx + 1;
        });
    }

    // Clear Cart Button
    if (clearCartBtn) {
        clearCartBtn.addEventListener('click', (e) => {
            e.preventDefault();
            if (tableBody && tableBody.children.length > 0) {
                if (confirm('هل أنت متأكد من تفريغ كافة الأصناف في سلة المبيعات؟')) {
                    tableBody.innerHTML = '';
                    recalculateTotals();
                    if (quickProductInput) quickProductInput.focus();
                }
            }
        });
    }

    // Manual Add Button
    if (manualAddBtn) {
        manualAddBtn.addEventListener('click', (e) => {
            e.preventDefault();
            const firstProd = productsData[0];
            if (!firstProd) {
                alert('لا توجد منتجات مسجلة في النظام.');
                return;
            }
            const prodUnitName = (firstProd.unit || 'قطعة').trim();
            const matchingUnit = unitsData.find(u => u.name === prodUnitName) || unitsData[0];
            const unitFactor = matchingUnit ? parseFloat(matchingUnit.factor || 1.0) : 1.0;
            insertRowAtTop({
                productId: firstProd.id,
                productName: firstProd.name,
                productSku: firstProd.sku,
                productBarcode: firstProd.barcode,
                unitId: matchingUnit?.id || 1,
                conversionFactor: unitFactor,
                quantity: 1,
                unitPrice: getProductPriceForTier(firstProd, null, unitFactor),
                lineDiscount: 0,
                notes: ''
            });
            recalculateTotals();
        });
    }

    // Bind existing rows (e.g. from validation error back)
    if (tableBody) {
        tableBody.querySelectorAll('.sales-item-row').forEach(row => {
            bindRowEvents(row);
        });
    }

    function recalculateTotals() {
        if (!tableBody) return;

        let itemsCount = 0;
        let totalUnits = 0;
        let itemsSubtotal = 0;
        let totalLineDiscounts = 0;

        const rows = tableBody.querySelectorAll('.sales-item-row');
        rows.forEach(r => {
            const q = parseFloat(r.querySelector('.row-qty')?.value || 0);
            const p = parseFloat(r.querySelector('.row-price')?.value || 0);
            const d = parseFloat(r.querySelector('.row-discount')?.value || 0);

            if (q > 0) {
                itemsCount++;
                totalUnits += q;
                itemsSubtotal += (q * p);
                totalLineDiscounts += d;
            }
        });

        // Invoice Level Discount
        const discType = discountTypeSelect ? discountTypeSelect.value : 'fixed';
        const discVal = parseFloat(discountValueInput ? discountValueInput.value : 0) || 0;
        let invoiceDiscount = 0;

        const subtotalAfterLines = Math.max(0, itemsSubtotal - totalLineDiscounts);

        if (discVal > 0) {
            if (discType === 'percentage') {
                invoiceDiscount = (subtotalAfterLines * Math.min(100, discVal)) / 100;
            } else {
                invoiceDiscount = Math.min(subtotalAfterLines, discVal);
            }
        }

        const totalDiscount = totalLineDiscounts + invoiceDiscount;
        const shipping = parseFloat(shippingInput ? shippingInput.value : 0) || 0;
        const grandTotal = Math.max(0, itemsSubtotal - totalDiscount + shipping);

        // Update Summary Cards
        if (summaryItemsCount) summaryItemsCount.textContent = itemsCount;
        if (summaryTotalUnits) summaryTotalUnits.textContent = totalUnits.toFixed(fmod(totalUnits, 1) === 0 ? 0 : 2);
        if (summaryItemsSubtotal) summaryItemsSubtotal.textContent = `${itemsSubtotal.toFixed(2)} ج.م`;
        if (summaryDiscountDisplay) summaryDiscountDisplay.textContent = `${totalDiscount.toFixed(2)} ج.م`;
        if (summaryDiscountHint) {
            if (totalDiscount > 0) {
                summaryDiscountHint.textContent = `خصم بنود: ${totalLineDiscounts.toFixed(2)} + خصم فاتورة: ${invoiceDiscount.toFixed(2)}`;
            } else {
                summaryDiscountHint.textContent = 'لا يوجد خصم مطبق';
            }
        }
        if (summaryGrandTotal) summaryGrandTotal.textContent = `${grandTotal.toFixed(2)} ج.م`;
        updatePaymentAndCreditStatus(grandTotal);
    }

    function getCurrentGrandTotal() {
        if (summaryGrandTotal) {
            const raw = summaryGrandTotal.textContent.replace(/[^\d.-]/g, '');
            const parsed = parseFloat(raw);
            if (!isNaN(parsed)) return parsed;
        }
        return 0;
    }

    function getCurrentPaymentType() {
        const checked = document.querySelector('input[name="payment_type"]:checked');
        return checked ? checked.value : 'cash';
    }

    function getSelectedCustomer() {
        if (!customerSelect) return null;
        const custId = parseInt(customerSelect.value, 10);
        return customersData.find(c => c.id === custId) || null;
    }

    function updatePaymentAndCreditStatus(grandTotal = null) {
        if (grandTotal === null) {
            grandTotal = getCurrentGrandTotal();
        }

        const paymentType = getCurrentPaymentType();
        const customer = getSelectedCustomer();
        const posDueDateHint = document.getElementById('posDueDateHint');

        // 1. Sync inputs based on payment type
        if (paymentType === 'cash') {
            if (posPaidAmountInput) {
                posPaidAmountInput.value = grandTotal.toFixed(2);
                posPaidAmountInput.readOnly = true;
                posPaidAmountInput.classList.remove('bg-[var(--bg-surface)]');
                posPaidAmountInput.classList.add('bg-[var(--surface-muted)]');
            }
            if (posRemainingAmountInput) {
                posRemainingAmountInput.value = '0.00';
            }
            if (posPaidHint) posPaidHint.textContent = 'كامل الفاتورة مسدد نقداً بالدرج';
            if (posRemainingHint) posRemainingHint.textContent = 'لا يوجد دين آجل على العميل';
            if (posDueDateInput) {
                posDueDateInput.disabled = true;
                posDueDateInput.value = '';
                posDueDateInput.classList.add('bg-[var(--surface-muted)]', 'cursor-not-allowed', 'opacity-60');
            }
            if (posDueDateHint) posDueDateHint.textContent = 'غير مطلوب في البيع النقدي';
        } else if (paymentType === 'credit') {
            if (posPaidAmountInput) {
                posPaidAmountInput.value = '0.00';
                posPaidAmountInput.readOnly = true;
                posPaidAmountInput.classList.remove('bg-[var(--bg-surface)]');
                posPaidAmountInput.classList.add('bg-[var(--surface-muted)]');
            }
            if (posRemainingAmountInput) {
                posRemainingAmountInput.value = grandTotal.toFixed(2);
            }
            if (posPaidHint) posPaidHint.textContent = 'لا توجد دفعات نقدية مسددة بالدرج';
            if (posRemainingHint) posRemainingHint.textContent = 'كامل الفاتورة آجل على حساب العميل';
            if (posDueDateInput) {
                posDueDateInput.disabled = false;
                posDueDateInput.classList.remove('bg-[var(--surface-muted)]', 'cursor-not-allowed', 'opacity-60');
            }
            if (posDueDateHint) posDueDateHint.textContent = 'حدد تاريخ المطالبة بسداد المديونية';
        } else { // partial
            if (posPaidAmountInput) {
                posPaidAmountInput.readOnly = false;
                posPaidAmountInput.classList.remove('bg-[var(--surface-muted)]');
                posPaidAmountInput.classList.add('bg-[var(--bg-surface)]');
                let paid = parseFloat(posPaidAmountInput.value) || 0;
                if (paid > grandTotal) {
                    paid = grandTotal;
                    posPaidAmountInput.value = paid.toFixed(2);
                }
                const remaining = Math.max(0, grandTotal - paid);
                if (posRemainingAmountInput) {
                    posRemainingAmountInput.value = remaining.toFixed(2);
                }
            }
            if (posPaidHint) posPaidHint.textContent = 'المبلغ المحصل نقداً الآن بالدرج';
            if (posRemainingHint) posRemainingHint.textContent = 'المتبقي الآجل المسجل على العميل';
            if (posDueDateInput) {
                posDueDateInput.disabled = false;
                posDueDateInput.classList.remove('bg-[var(--surface-muted)]', 'cursor-not-allowed', 'opacity-60');
            }
            if (posDueDateHint) posDueDateHint.textContent = 'تاريخ استحقاق سداد المبلغ المتبقي';
        }


        // 2. Customer Credit Limit Badge and Alerts
        const remainingDebt = parseFloat(posRemainingAmountInput ? posRemainingAmountInput.value : 0) || 0;

        if (customer) {
            const currentBal = parseFloat(customer.current_balance) || 0;
            const creditLim = parseFloat(customer.credit_limit) || 0;

            if (customerBalanceHint) {
                if (currentBal < 0) {
                    customerBalanceHint.textContent = `· دائن له: ${Math.abs(currentBal).toFixed(2)} ج.م`;
                    customerBalanceHint.className = 'text-[10px] font-bold text-emerald-600 dark:text-emerald-400';
                } else if (currentBal > 0) {
                    customerBalanceHint.textContent = `· مدين عليه: ${currentBal.toFixed(2)} ج.م`;
                    customerBalanceHint.className = 'text-[10px] font-bold text-rose-600 dark:text-rose-400';
                } else {
                    customerBalanceHint.textContent = '· متزن (0.00)';
                    customerBalanceHint.className = 'text-[10px] font-normal text-[var(--text-muted)]';
                }
            }

            if (customerCreditStatusBadge) {
                customerCreditStatusBadge.classList.remove('hidden');
                if (creditLim > 0) {
                    const availableCredit = creditLim - currentBal;
                    customerCreditStatusBadge.textContent = `حد الائتمان: ${creditLim.toFixed(2)} ج.م (المتاح: ${availableCredit.toFixed(2)} ج.م)`;
                    if (availableCredit <= 0) {
                        customerCreditStatusBadge.className = 'text-xs px-2.5 py-1 rounded font-semibold border bg-red-50 text-red-700 border-red-200 dark:bg-red-950/40 dark:text-red-300 dark:border-red-900';
                    } else {
                        customerCreditStatusBadge.className = 'text-xs px-2.5 py-1 rounded font-semibold border bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-900';
                    }
                } else {
                    if (currentBal > 0) {
                        customerCreditStatusBadge.textContent = `الرصيد السابق: ${currentBal.toFixed(2)} ج.م (مدين عليه)`;
                        customerCreditStatusBadge.className = 'text-xs px-2.5 py-1 rounded font-semibold border bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-900';
                    } else if (currentBal < 0) {
                        customerCreditStatusBadge.textContent = `رصيد دائن لصالح العميل: ${Math.abs(currentBal).toFixed(2)} ج.م (له)`;
                        customerCreditStatusBadge.className = 'text-xs px-2.5 py-1 rounded font-semibold border bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-900';
                    } else {
                        customerCreditStatusBadge.textContent = 'رصيد العميل: 0.00 ج.م (متزن)';
                        customerCreditStatusBadge.className = 'text-xs px-2.5 py-1 rounded font-semibold border bg-slate-50 text-slate-700 border-slate-200 dark:bg-slate-900 dark:text-slate-300 dark:border-slate-800';
                    }
                }
            }

            if (posRemainingHint && customer.name !== 'عميل نقدي') {
                const finalBal = currentBal + remainingDebt;
                let balanceNote = '';
                if (finalBal > 0) {
                    balanceNote = ` · الرصيد بعد الفاتورة: ${finalBal.toFixed(2)} ج.م (عليه)`;
                } else if (finalBal < 0) {
                    balanceNote = ` · الرصيد بعد الفاتورة: ${Math.abs(finalBal).toFixed(2)} ج.م (له)`;
                } else {
                    balanceNote = ` · الرصيد بعد الفاتورة: متزن (0.00)`;
                }

                if (paymentType === 'cash') {
                    posRemainingHint.textContent = `كامل الفاتورة مسدد نقداً${balanceNote}`;
                } else if (paymentType === 'credit') {
                    posRemainingHint.textContent = `متبقي الفاتورة: ${remainingDebt.toFixed(2)} ج.م آجل${balanceNote}`;
                } else {
                    posRemainingHint.textContent = `متبقي الفاتورة: ${remainingDebt.toFixed(2)} ج.م${balanceNote}`;
                }
            }

            if (creditLimitAlertBox) {
                if (remainingDebt > 0) {
                    if (customer.name === 'عميل نقدي') {
                        creditLimitAlertBox.classList.remove('hidden');
                        creditLimitAlertBox.className = 'mt-3 p-3 rounded text-xs border flex items-center gap-2 danger bg-red-50 text-red-700 border-red-200 dark:bg-red-950/40 dark:text-red-300 dark:border-red-900';
                        creditLimitAlertBox.innerHTML = `<strong>تنبيه مالي:</strong> لا يمكن تسجيل بيع آجل على "العميل النقدي العام". يرجى اختيار عميل مسجل أو إضافة عميل جديد.`;
                    } else if (creditLim > 0 && (currentBal + remainingDebt) > creditLim) {
                        const exceededBy = (currentBal + remainingDebt) - creditLim;
                        creditLimitAlertBox.classList.remove('hidden');
                        creditLimitAlertBox.className = 'mt-3 p-3 rounded text-xs border flex items-center gap-2 danger bg-red-50 text-red-700 border-red-200 dark:bg-red-950/40 dark:text-red-300 dark:border-red-900';
                        creditLimitAlertBox.innerHTML = `<strong>تحذير تجاوز سقف الائتمان:</strong> سيتم تجاوز الحد الائتماني للعميل بمقدار ${exceededBy.toFixed(2)} ج.م (الحد المسموح: ${creditLim.toFixed(2)} ج.م، الرصيد بعد الفاتورة: ${(currentBal + remainingDebt).toFixed(2)} ج.م).`;
                    } else {
                        creditLimitAlertBox.classList.add('hidden');
                    }
                } else {
                    creditLimitAlertBox.classList.add('hidden');
                }
            }
        } else {
            if (customerBalanceHint) customerBalanceHint.textContent = '';
            if (customerCreditStatusBadge) customerCreditStatusBadge.classList.add('hidden');
            if (creditLimitAlertBox) creditLimitAlertBox.classList.add('hidden');
        }
    }

    // Attach Payment Method Events
    if (posPaymentToggleGroup) {
        const toggleButtons = posPaymentToggleGroup.querySelectorAll('.payment-toggle-btn');
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
                updatePaymentAndCreditStatus();
            });
        });

        const radios = posPaymentToggleGroup.querySelectorAll('input[type="radio"]');
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
                updatePaymentAndCreditStatus();
            });
        });
    }

    if (posPaidAmountInput) {
        posPaidAmountInput.addEventListener('input', () => {
            const grandTotal = getCurrentGrandTotal();
            let paid = parseFloat(posPaidAmountInput.value) || 0;
            if (paid < 0) {
                paid = 0;
                posPaidAmountInput.value = '0.00';
            }
            if (paid > grandTotal) {
                paid = grandTotal;
                posPaidAmountInput.value = paid.toFixed(2);
            }
            const remaining = Math.max(0, grandTotal - paid);
            if (posRemainingAmountInput) {
                posRemainingAmountInput.value = remaining.toFixed(2);
            }
            updatePaymentAndCreditStatus(grandTotal);
        });
    }

    function fmod(a, b) {
        return Number((a - (Math.floor(a / b) * b)).toPrecision(8));
    }

    // POS Keyboard Shortcuts (F2: Search, F9: Submit/Post, Esc: Close)
    window.addEventListener('keydown', (e) => {
        // F2: Focus Search Bar immediately
        if (e.key === 'F2') {
            e.preventDefault();
            if (quickProductInput) {
                quickProductInput.focus();
                quickProductInput.select();
            }
            return;
        }

        // F4: Quick toggle Piece / Dozen
        if (e.key === 'F4') {
            e.preventDefault();
            const opt = quickUnitSelect ? quickUnitSelect.options[quickUnitSelect.selectedIndex] : null;
            const currentFactor = parseFloat(opt?.dataset.factor || 1.0) || 1.0;
            if (currentFactor > 1.0) {
                switchToPiece();
            } else {
                switchToDozen();
            }
            return;
        }

        // F9: Fast Checkout & Post
        if (e.key === 'F9') {
            e.preventDefault();
            if (posSubmitBtn && !isSubmitting) {
                posSubmitBtn.click();
            }
            return;
        }
    });

    // Guard Against Double Submissions
    if (salesForm) {
        salesForm.addEventListener('submit', (e) => {
            const rows = tableBody ? tableBody.querySelectorAll('.sales-item-row') : [];
            if (rows.length === 0) {
                e.preventDefault();
                alert('سلة المبيعات فارغة! يرجى إضافة صنف واحد على الأقل قبل حفظ الفاتورة.');
                if (quickProductInput) quickProductInput.focus();
                return;
            }

            if (isSubmitting) {
                e.preventDefault();
                return;
            }
            isSubmitting = true;
        });
    }

    // Quick Add Customer Modal Logic
    const quickAddCustomerBtn = document.getElementById('quickAddCustomerBtn');
    const quickCustomerModal = document.getElementById('quickCustomerModal');
    const closeQuickCustomerModalBtn = document.getElementById('closeQuickCustomerModalBtn');
    const cancelQuickCustomerBtn = document.getElementById('cancelQuickCustomerBtn');
    const quickCustomerForm = document.getElementById('quickCustomerForm');
    const quickCustomerAlert = document.getElementById('quickCustomerAlert');
    const quickCustomerAlertMsg = document.getElementById('quickCustomerAlertMsg');
    const saveQuickCustomerBtn = document.getElementById('saveQuickCustomerBtn');

    let previousCustomerValue = customerSelect ? customerSelect.value : '';

    function openCustomerModal() {
        if (!quickCustomerModal) return;
        if (quickCustomerForm) quickCustomerForm.reset();
        if (quickCustomerAlert) quickCustomerAlert.classList.add('hidden');
        quickCustomerModal.classList.add('open');
        const nameInput = document.getElementById('quickCustName');
        if (nameInput) setTimeout(() => nameInput.focus(), 120);
    }

    function closeCustomerModal() {
        if (!quickCustomerModal) return;
        quickCustomerModal.classList.remove('open');
        if (customerSelect && customerSelect.value === '__add_new__') {
            customerSelect.value = previousCustomerValue;
        }
    }

    if (quickAddCustomerBtn) {
        quickAddCustomerBtn.addEventListener('click', (e) => {
            e.preventDefault();
            openCustomerModal();
        });
    }

    if (closeQuickCustomerModalBtn) {
        closeQuickCustomerModalBtn.addEventListener('click', (e) => {
            e.preventDefault();
            closeCustomerModal();
        });
    }

    if (cancelQuickCustomerBtn) {
        cancelQuickCustomerBtn.addEventListener('click', (e) => {
            e.preventDefault();
            closeCustomerModal();
        });
    }

    if (quickCustomerModal) {
        quickCustomerModal.addEventListener('click', (e) => {
            if (e.target === quickCustomerModal) {
                closeCustomerModal();
            }
        });
    }

    if (customerSelect) {
        customerSelect.addEventListener('focus', () => {
            if (customerSelect.value !== '__add_new__') {
                previousCustomerValue = customerSelect.value;
            }
        });

        customerSelect.addEventListener('change', () => {
            if (customerSelect.value === '__add_new__') {
                openCustomerModal();
            } else {
                previousCustomerValue = customerSelect.value;
                const selectedOpt = customerSelect.options[customerSelect.selectedIndex];
                const tier = selectedOpt ? selectedOpt.dataset.tier : 'retail';
                if (customerTierHint) {
                    const label = tier === 'wholesale' ? 'جملة' : (tier === 'distributor' ? 'موزع' : 'تجزئة');
                    customerTierHint.textContent = `فئة: ${label}`;
                }
                if (priceTierSelect && tier) {
                    priceTierSelect.value = tier;
                }
            }
        });
    }

    if (quickCustomerForm) {
        quickCustomerForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            if (quickCustomerAlert) quickCustomerAlert.classList.add('hidden');
            if (saveQuickCustomerBtn) saveQuickCustomerBtn.disabled = true;

            const formData = new FormData(quickCustomerForm);
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            try {
                const response = await fetch(quickCustomerForm.action, {
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
                    let errMsg = result.message || 'حدث خطأ أثناء حفظ العميل.';
                    if (result.errors) {
                        const firstErr = Object.values(result.errors)[0];
                        if (Array.isArray(firstErr) && firstErr.length > 0) errMsg = firstErr[0];
                    }
                    if (quickCustomerAlert && quickCustomerAlertMsg) {
                        quickCustomerAlertMsg.textContent = errMsg;
                        quickCustomerAlert.classList.remove('hidden');
                    } else {
                        alert(errMsg);
                    }
                    if (saveQuickCustomerBtn) saveQuickCustomerBtn.disabled = false;
                    return;
                }

                const newCust = result.customer;
                if (customerSelect && newCust) {
                    const opt = document.createElement('option');
                    opt.value = newCust.id;
                    opt.dataset.tier = newCust.pricing_tier || 'retail';
                    opt.textContent = `${newCust.name} (${newCust.phone})`;
                    customerSelect.appendChild(opt);

                    customerSelect.value = newCust.id;
                    previousCustomerValue = newCust.id;

                    if (customerTierHint) {
                        const label = newCust.pricing_tier === 'wholesale' ? 'جملة' : (newCust.pricing_tier === 'distributor' ? 'موزع' : 'تجزئة');
                        customerTierHint.textContent = `فئة: ${label}`;
                    }
                    if (priceTierSelect && newCust.pricing_tier) {
                        priceTierSelect.value = newCust.pricing_tier;
                    }
                }

                closeCustomerModal();
            } catch (err) {
                if (quickCustomerAlert && quickCustomerAlertMsg) {
                    quickCustomerAlertMsg.textContent = 'تعذر الاتصال بالخادم. يرجى المحاولة مرة أخرى.';
                    quickCustomerAlert.classList.remove('hidden');
                } else {
                    alert('تعذر الاتصال بالخادم.');
                }
            } finally {
                if (saveQuickCustomerBtn) saveQuickCustomerBtn.disabled = false;
            }
        });
    }

    // POS Quick Open Shift Modal
    const posOpenShiftTriggerBtn = document.getElementById('posOpenShiftTriggerBtn');
    const posOpenShiftModal = document.getElementById('posOpenShiftModal');
    const closePosOpenShiftModalBtn = document.getElementById('closePosOpenShiftModalBtn');
    const cancelPosOpenShiftBtn = document.getElementById('cancelPosOpenShiftBtn');

    if (posOpenShiftTriggerBtn && posOpenShiftModal) {
        posOpenShiftTriggerBtn.addEventListener('click', (e) => {
            e.preventDefault();
            posOpenShiftModal.classList.remove('hidden');
            posOpenShiftModal.classList.add('open');
            const obInput = document.getElementById('posShiftOpeningBalance');
            if (obInput) setTimeout(() => obInput.focus(), 100);
        });
    }

    const closeOpenShift = () => {
        if (posOpenShiftModal) {
            posOpenShiftModal.classList.remove('open');
            posOpenShiftModal.classList.add('hidden');
        }
    };
    if (closePosOpenShiftModalBtn) closePosOpenShiftModalBtn.addEventListener('click', closeOpenShift);
    if (cancelPosOpenShiftBtn) cancelPosOpenShiftBtn.addEventListener('click', closeOpenShift);

    // POS Quick Close Shift Modal
    const posCloseShiftTriggerBtn = document.getElementById('posCloseShiftTriggerBtn');
    const posCloseShiftModal = document.getElementById('posCloseShiftModal');
    const closePosCloseShiftModalBtn = document.getElementById('closePosCloseShiftModalBtn');
    const cancelPosCloseShiftBtn = document.getElementById('cancelPosCloseShiftBtn');

    if (posCloseShiftTriggerBtn && posCloseShiftModal) {
        posCloseShiftTriggerBtn.addEventListener('click', (e) => {
            e.preventDefault();
            posCloseShiftModal.classList.remove('hidden');
            posCloseShiftModal.classList.add('open');
            const abInput = document.getElementById('posShiftActualBalance');
            if (abInput) setTimeout(() => abInput.focus(), 100);
        });
    }

    const posShiftActualBalance = document.getElementById('posShiftActualBalance');
    const posExpectedBalanceDisplay = document.getElementById('posExpectedBalanceDisplay');
    const posLiveDiffRow = document.getElementById('posLiveDiffRow');
    const posLiveDiffBadge = document.getElementById('posLiveDiffBadge');

    if (posShiftActualBalance && posExpectedBalanceDisplay && posLiveDiffRow && posLiveDiffBadge) {
        posShiftActualBalance.addEventListener('input', () => {
            const val = posShiftActualBalance.value.trim();
            if (val === '') {
                posLiveDiffRow.classList.add('hidden');
                return;
            }
            const actual = parseFloat(val) || 0;
            const expected = parseFloat(posExpectedBalanceDisplay.dataset.expected || 0) || 0;
            const diff = actual - expected;
            posLiveDiffRow.classList.remove('hidden');

            if (Math.abs(diff) < 0.01) {
                posLiveDiffBadge.className = 'erp-badge erp-badge-success font-numeric font-bold text-xs py-0.5 px-2';
                posLiveDiffBadge.textContent = 'مطابق تماماً (بدون عجز أو زيادة)';
            } else if (diff < 0) {
                posLiveDiffBadge.className = 'erp-badge erp-badge-danger font-numeric font-bold text-xs py-0.5 px-2';
                posLiveDiffBadge.textContent = `عجز في الدرج: ${Math.abs(diff).toFixed(2)} ج.م`;
            } else {
                posLiveDiffBadge.className = 'erp-badge erp-badge-info font-numeric font-bold text-xs py-0.5 px-2';
                posLiveDiffBadge.textContent = `زيادة في الدرج: +${diff.toFixed(2)} ج.م`;
            }
        });
    }

    const closeCloseShift = () => {
        if (posCloseShiftModal) {
            posCloseShiftModal.classList.remove('open');
            posCloseShiftModal.classList.add('hidden');
        }
    };
    if (closePosCloseShiftModalBtn) closePosCloseShiftModalBtn.addEventListener('click', closeCloseShift);
    if (cancelPosCloseShiftBtn) cancelPosCloseShiftBtn.addEventListener('click', closeCloseShift);

    // Initial calculation
    recalculateTotals();
    updateDiscountLabel();

    // Auto-focus search input on page load
    if (quickProductInput) {
        setTimeout(() => {
            quickProductInput.focus();
        }, 100);
    }
}

function initSalesShow() {
    // Show view confirmation handlers handled via data-confirm in common.js
}
