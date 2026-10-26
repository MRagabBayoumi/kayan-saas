/**
 * Kayan ERP — Sales Representative Mobile Portal Interactions
 * Geolocation GPS, Field Order Cart & Pricing Engine
 */

export function initPortalPage() {
    initPortalCustomers();
    initPortalCatalogFilters();
    initPortalOrderForm();
}

/**
 * Field Customer GPS & Registration
 */
function initPortalCustomers() {
    const gpsBtn = document.getElementById('gpsBtn');
    const statusText = document.getElementById('gpsStatusText');
    const latInput = document.getElementById('latitudeInput');
    const lngInput = document.getElementById('longitudeInput');
    const customerTypeInput = document.getElementById('customerTypeInput');

    // Synchronize customer_type with pricing_tier radios
    document.querySelectorAll('input[name="pricing_tier"]').forEach(radio => {
        radio.addEventListener('change', function () {
            if (customerTypeInput) {
                customerTypeInput.value = this.value;
            }
        });
    });

    if (gpsBtn) {
        gpsBtn.addEventListener('click', function () {
            if (!navigator.geolocation) {
                alert('خاصية تحديد الموقع غير مدعومة في متصفحك.');
                return;
            }

            gpsBtn.disabled = true;
            gpsBtn.innerHTML = '<span class="inline-block animate-spin mr-1">⏳</span> جاري التقاط الإحداثيات عبر الأقمار الصناعية...';
            if (statusText) statusText.innerText = 'جاري البحث...';

            navigator.geolocation.getCurrentPosition(
                function (position) {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    if (latInput) latInput.value = lat;
                    if (lngInput) lngInput.value = lng;

                    if (statusText) {
                        statusText.innerText = 'تم التقاط الإحداثيات بنجاح ✅';
                        statusText.className = 'text-[10px] text-emerald-600 font-bold';
                    }

                    gpsBtn.disabled = false;
                    gpsBtn.className = 'w-full py-2.5 px-3 bg-emerald-50 border border-emerald-300 rounded-xl text-xs font-bold text-emerald-700 flex items-center justify-center gap-2';
                    gpsBtn.innerHTML = '✅ تم تحديد الموقع: ' + lat.toFixed(5) + ', ' + lng.toFixed(5);
                },
                function (error) {
                    gpsBtn.disabled = false;
                    gpsBtn.className = 'w-full py-2.5 px-3 bg-rose-50 border border-rose-300 rounded-xl text-xs font-bold text-rose-700 flex items-center justify-center gap-2';
                    gpsBtn.innerHTML = 'تعذر التقاط الموقع: ' + (error.message || 'يرجى السماح بالوصول للـ GPS');
                    if (statusText) statusText.innerText = 'فشل التحديد';
                },
                { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
            );
        });
    }
}

/**
 * Auto-submit on change for catalog filters
 */
function initPortalCatalogFilters() {
    document.querySelectorAll('.portal-auto-submit').forEach(el => {
        el.addEventListener('change', function () {
            if (this.form) {
                this.form.submit();
            }
        });
    });

    const catalogSearch = document.getElementById('catalogSearchInput');
    if (catalogSearch) {
        catalogSearch.addEventListener('change', function () {
            if (this.value && this.form) {
                this.form.submit();
            }
        });
    }
}

/**
 * Mobile Order Form Engine (Alpine component factory)
 */
function initPortalOrderForm() {
    const barcodeInput = document.getElementById('portalOrderBarcodeInput');
    if (barcodeInput) {
        barcodeInput.addEventListener('change', () => {
            const raw = barcodeInput.value ? barcodeInput.value.trim().toLowerCase() : '';
            if (!raw) return;

            const root = document.getElementById('portalOrderApp');
            if (!root) return;

            let allProducts = [];
            try {
                allProducts = JSON.parse(root.dataset.products || '[]');
            } catch (e) {
                allProducts = [];
            }

            const matched = allProducts.find(p =>
                (p.barcode && p.barcode.toLowerCase() === raw) ||
                (p.sku && p.sku.toLowerCase() === raw)
            );

            if (matched) {
                window.dispatchEvent(new CustomEvent('portal-barcode-scanned', { detail: matched }));
                if (navigator.vibrate) navigator.vibrate([100]);
            } else {
                alert('لم يتم العثور على صنف بالباركود: ' + raw);
            }

            barcodeInput.value = '';
        });
    }

    window.portalOrderForm = function portalOrderForm() {
        const root = document.getElementById('portalOrderApp');
        let allProducts = [];
        let repCommissionRate = 0;
        let repCommissionType = 'percentage';
        let initialCustomerId = '';
        let initialProductId = '';
        let isAdmin = false;

        if (root) {
            try {
                allProducts = JSON.parse(root.dataset.products || '[]');
            } catch (e) {
                allProducts = [];
            }
            repCommissionRate = Number(root.dataset.commissionRate) || 0;
            repCommissionType = root.dataset.commissionType || 'percentage';
            initialCustomerId = root.dataset.selectedCustomer || '';
            initialProductId = root.dataset.selectedProduct || '';
            isAdmin = root.dataset.isAdmin === '1';
        }

        return {
            customerId: initialCustomerId,
            priceTier: isAdmin ? 'retail' : 'wholesale',
            paymentType: 'cash',
            paidAmount: 0,
            items: [],
            showProductPicker: false,
            productSearch: '',
            subtotal: 0,
            estimatedCommission: 0,

            init() {
                if (this.customerId) {
                    this.onCustomerChange();
                }

                if (initialProductId) {
                    const preselected = allProducts.find(p => String(p.id) === String(initialProductId));
                    if (preselected) {
                        this.selectProduct(preselected);
                    }
                }

                window.addEventListener('portal-barcode-scanned', (e) => {
                    if (e.detail) {
                        this.selectProduct(e.detail);
                    }
                });
            },

            get filteredProducts() {
                if (!this.productSearch) return allProducts.slice(0, 30);
                const s = this.productSearch.toLowerCase();
                return allProducts.filter(p =>
                    (p.name && p.name.toLowerCase().includes(s)) ||
                    (p.sku && p.sku.toLowerCase().includes(s)) ||
                    (p.barcode && p.barcode.toLowerCase().includes(s))
                ).slice(0, 30);
            },

            onCustomerChange() {
                const select = document.querySelector('select[name="customer_id"]');
                if (!select) return;
                const opt = select.options[select.selectedIndex];
                if (opt && opt.dataset.tier) {
                    if (isAdmin) {
                        this.priceTier = opt.dataset.tier;
                    } else {
                        this.priceTier = 'wholesale';
                    }
                    this.recalculateItemPrices();
                }
            },

            getProductPrice(product) {
                if (!isAdmin) {
                    return product.wholesale_price;
                }
                if (this.priceTier === 'wholesale') return product.wholesale_price || product.retail_price;
                if (this.priceTier === 'distributor') return product.distributor_price || product.retail_price;
                return product.retail_price;
            },

            recalculateItemPrices() {
                this.items.forEach(item => {
                    const prod = allProducts.find(p => p.id === item.product_id);
                    if (prod) {
                        item.unit_price = this.getProductPrice(prod);
                    }
                });
                this.calculateTotals();
            },

            selectProduct(product) {
                const existing = this.items.find(i => i.product_id === product.id);
                if (existing) {
                    existing.quantity += 1;
                } else {
                    this.items.push({
                        product_id: product.id,
                        name: product.name,
                        available_stock: product.available_stock,
                        stock_display: product.stock_display,
                        is_available: product.is_available,
                        unit: product.unit,
                        pieces_per_package: product.pieces_per_package || 1,
                        quantity: 1,
                        unit_price: this.getProductPrice(product),
                        line_discount: 0
                    });
                }
                this.calculateTotals();
                this.showProductPicker = false;
                this.productSearch = '';
            },

            incrementQty(index) {
                this.items[index].quantity += 1;
                this.calculateTotals();
            },

            decrementQty(index) {
                if (this.items[index].quantity > 1) {
                    this.items[index].quantity -= 1;
                    this.calculateTotals();
                }
            },

            removeItem(index) {
                this.items.splice(index, 1);
                this.calculateTotals();
            },

            calculateTotals() {
                let total = 0;
                this.items.forEach(i => {
                    const line = (Number(i.quantity) * Number(i.unit_price)) - (Number(i.line_discount) || 0);
                    total += Math.max(0, line);
                });
                this.subtotal = Math.round(total * 100) / 100;

                // Commission estimation
                if (repCommissionType === 'percentage') {
                    this.estimatedCommission = Math.round((this.subtotal * (repCommissionRate / 100)) * 100) / 100;
                } else {
                    this.estimatedCommission = repCommissionRate;
                }
            },

            formatCurrency(val) {
                return (Number(val) || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            },

            validateAndSubmit(e) {
                if (this.items.length === 0) {
                    e.preventDefault();
                    alert('يرجى إضافة صنف واحد على الأقل للطلب قبل المتابعة.');
                    return false;
                }
            }
        };
    };
}
