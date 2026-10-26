@props([
    'categories' => [],
    'units' => [],
    'suggestedSku' => null,
])

<!-- Quick Add Product Modal Dialog (Piece & Dozen Integrated) -->
<div id="quickProductModal" class="erp-modal-overlay hidden" role="dialog" aria-modal="true" aria-labelledby="quickProductModalTitle">
    <div class="erp-modal-container max-w-3xl">
        <!-- Header -->
        <div class="erp-modal-header flex items-center justify-between border-b border-[var(--border-color)] pb-3">
            <div class="erp-modal-title flex items-center gap-2 text-base font-bold text-[var(--text-main)]" id="quickProductModalTitle">
                <div class="w-8 h-8 rounded-lg bg-[var(--primary-soft)] text-[var(--primary)] flex items-center justify-center">
                    <x-icon name="plus-circle" size="18" />
                </div>
                <div>
                    <div class="text-sm font-bold text-[var(--text-main)]">إضافة صنف جديد سريع وشامل</div>
                    <div class="text-[11px] text-[var(--text-muted)] font-normal">تسجيل الصنف وتسعيره (قطعة / دستة) وإدراجه في جدول الفاتورة فوراً</div>
                </div>
            </div>
            <button type="button" id="closeQuickProductModalBtn" class="erp-modal-close text-[var(--text-muted)] hover:text-[var(--text-main)] cursor-pointer text-base leading-none p-1" aria-label="إغلاق">
                <x-icon name="x" size="18" />
            </button>
        </div>

        <!-- Form -->
        <form id="quickProductForm" method="POST" action="{{ route('products.quick-store') }}">
            @csrf
            <div class="erp-modal-body p-4 sm:p-5 space-y-4 max-h-[78vh] overflow-y-auto">
                <!-- Alert Box -->
                <div id="quickProductAlert" class="erp-alert erp-alert-danger hidden flex items-center gap-2 text-xs">
                    <x-icon name="alert-triangle" size="16" class="shrink-0" />
                    <span id="quickProductAlertMsg"></span>
                </div>

                <!-- Section 1: Basic Info -->
                <div class="grid grid-cols-1 sm:grid-cols-12 gap-3">
                    <div class="erp-form-group mb-0 sm:col-span-6">
                        <label for="quickProdName" class="erp-form-label text-xs font-semibold">
                            اسم الصنف / المنتج <span class="text-[var(--danger)]">*</span>
                        </label>
                        <input 
                            type="text" 
                            id="quickProdName" 
                            name="name" 
                            class="erp-input text-xs font-semibold" 
                            placeholder="مثال: قلم جاف أزرق لوكس 0.7 مم..." 
                            required
                        >
                    </div>

                    <div class="erp-form-group mb-0 sm:col-span-3">
                        <label for="quickProdCategory" class="erp-form-label text-xs font-semibold">
                            التصنيف <span class="text-[var(--danger)]">*</span>
                        </label>
                        <select id="quickProdCategory" name="category_id" class="erp-input text-xs" required>
                            @forelse($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @empty
                                <option value="1">عام</option>
                            @endforelse
                        </select>
                    </div>

                    <div class="erp-form-group mb-0 sm:col-span-3">
                        <label for="quickProdSku" class="erp-form-label text-xs font-semibold flex items-center justify-between">
                            <span>كود الصنف (SKU)</span>
                            <button type="button" id="regenerateQuickSkuBtn" class="text-[10px] text-[var(--primary)] hover:underline cursor-pointer">
                                توليد كود
                            </button>
                        </label>
                        <input 
                            type="text" 
                            id="quickProdSku" 
                            name="sku" 
                            value="{{ $suggestedSku }}" 
                            class="erp-input text-xs font-numeric font-semibold" 
                            placeholder="مثال: KY-26-0001"
                        >
                    </div>
                </div>

                <!-- Section 2: Barcode & Quick Generation -->
                <div class="erp-form-group mb-0">
                    <label for="quickProdBarcode" class="erp-form-label text-xs font-semibold flex items-center justify-between">
                        <span>الباركود الدولي أو الداخلي</span>
                        <span class="text-[11px] text-[var(--text-muted)] font-normal">اضرب الباركود بالمسدس أو اضغط زر التوليد التلقائي</span>
                    </label>
                    <div class="relative w-full flex items-center gap-1.5">
                        <div class="relative flex-1">
                            <input 
                                type="text" 
                                id="quickProdBarcode" 
                                name="barcode" 
                                class="erp-input text-xs font-numeric w-full pl-8" 
                                placeholder="اضرب الباركود بمسدس الباركود أو اتركه للتوليد التلقائي..."
                            >
                            <button 
                                type="button" 
                                data-action="open-barcode-scanner" 
                                data-target-input="quickProdBarcode" 
                                title="مسح الباركود بكاميرا الموبايل"
                                class="absolute left-1.5 top-1/2 -translate-y-1/2 h-6 w-6 border-0 bg-[var(--primary-soft)] text-[var(--primary)] hover:bg-[var(--primary)] hover:text-white rounded inline-flex items-center justify-center cursor-pointer transition-colors"
                            >
                                <x-icon name="camera" size="13" />
                            </button>
                        </div>
                        <button 
                            type="button" 
                            id="generateInternalBarcodeBtn" 
                            class="erp-btn erp-btn-sm erp-btn-secondary text-xs shrink-0 flex items-center gap-1 px-2.5 py-1.5 cursor-pointer"
                            title="توليد باركود رقمي داخلي تلقائياً"
                        >
                            <x-icon name="refresh-cw" size="13" />
                            <span>توليد باركود</span>
                        </button>
                    </div>
                </div>

                <!-- Section 3: Packaging & Unit System (نظام وحدة القياس والتعبئة) -->
                <div class="bg-[var(--surface-muted)] border border-[var(--border-color)] rounded-xl p-3.5 space-y-3">
                    <div class="flex items-center justify-between border-b border-[var(--border-color)] pb-2">
                        <div class="flex items-center gap-1.5 text-xs font-bold text-[var(--text-main)]">
                            <x-icon name="layers" size="15" class="text-[var(--primary)]" />
                            <span>نظام التعبئة ووحدات القياس (الأساس هو القطعة)</span>
                        </div>
                        <span class="text-[11px] text-[var(--text-muted)] font-mono">
                            1 دستة / عبوة = <strong id="quickProdFactorPreview" class="text-[var(--primary)] font-bold text-xs">12</strong> قطعة
                        </span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="erp-form-group mb-0">
                            <label for="quickProdUnit" class="erp-form-label text-xs font-semibold">
                                الوحدة الأساسية الصغرى (المخزن والسيستم) <span class="text-[var(--danger)]">*</span>
                            </label>
                            <select id="quickProdUnit" name="unit" class="erp-input text-xs" required>
                                @forelse($units as $u)
                                    <option value="{{ $u->name }}" data-id="{{ $u->id }}" data-factor="{{ $u->conversion_factor }}" {{ ($u->name === 'قطعة' || $u->is_default) ? 'selected' : '' }}>
                                        {{ $u->name }} ({{ $u->code }})
                                    </option>
                                @empty
                                    <option value="قطعة" data-id="" data-factor="1" selected>قطعة (PCS)</option>
                                    <option value="علبة" data-id="" data-factor="1">علبة (BOX)</option>
                                    <option value="طرد" data-id="" data-factor="1">طرد (PKG)</option>
                                @endforelse
                            </select>
                        </div>

                        <div class="erp-form-group mb-0">
                            <label for="quickProdPkgFactor" class="erp-form-label text-xs font-semibold">
                                كمية وحدة القياس (عدد القطع في الدستة / العبوة) <span class="text-[var(--danger)]">*</span>
                            </label>
                            <input 
                                type="number" 
                                id="quickProdPkgFactor" 
                                name="pieces_per_package" 
                                value="12" 
                                min="1" 
                                step="1" 
                                class="erp-input text-xs font-numeric font-bold" 
                                placeholder="12"
                                required
                            >
                        </div>
                    </div>
                </div>

                <!-- Section 4: Comprehensive 4-Tier Dual Pricing Matrix (منظومة التسعير الرباعية) -->
                <div class="border border-[var(--border-color)] rounded-xl overflow-hidden bg-[var(--bg-surface)]">
                    <!-- Matrix Header -->
                    <div class="bg-[var(--surface-muted)] px-3.5 py-2.5 border-b border-[var(--border-color)] flex items-center justify-between">
                        <div class="flex items-center gap-2 text-xs font-bold text-[var(--text-main)]">
                            <x-icon name="dollar-sign" size="15" class="text-[var(--primary)]" />
                            <span>منظومة التسعير المزدوج الذكي (حساب متبادل لحظي)</span>
                        </div>
                        <span class="text-[11px] text-[var(--primary)] font-bold bg-[var(--primary-soft)] px-2.5 py-0.5 rounded-full">
                            الأساس في الخلفية: القطعة (الدستة = <span id="quickProdFactorBadge">12</span> قطعة)
                        </span>
                    </div>

                    <!-- Matrix Rows -->
                    <div class="p-3 space-y-2.5">
                        <!-- Column Titles (Desktop) -->
                        <div class="grid grid-cols-1 sm:grid-cols-12 gap-2 text-[11px] font-bold text-[var(--text-muted)] pb-1 border-b border-[var(--border-color)] hidden sm:grid">
                            <div class="sm:col-span-4">مستوى السعر</div>
                            <div class="sm:col-span-4 text-center">سعر القطعة (أساس السيستم)</div>
                            <div class="sm:col-span-4 text-center">
                                سعر الدستة كاملة (<span id="quickProdFactorHeader">×12</span>)
                            </div>
                        </div>

                        <!-- 1. سعر الشراء (التكلفة من المورد) -->
                        <div class="grid grid-cols-1 sm:grid-cols-12 gap-2 items-center bg-amber-500/5 p-2.5 rounded-lg border border-amber-500/20">
                            <div class="sm:col-span-4 flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-amber-500 shrink-0"></span>
                                <div>
                                    <div class="text-xs font-bold text-amber-700 dark:text-amber-400">سعر الشراء والتكلفة <span class="text-[var(--danger)]">*</span></div>
                                    <div class="text-[10px] text-amber-600/80">تكلفة الصنف من المورد</div>
                                </div>
                            </div>
                            <div class="sm:col-span-4">
                                <label class="sm:hidden text-[10px] text-[var(--text-muted)] font-semibold block mb-0.5">سعر شراء القطعة:</label>
                                <div class="relative">
                                    <input type="number" id="quickProdCostPiece" name="cost_price" step="0.01" min="0" required
                                           class="erp-input text-xs font-numeric font-bold text-center text-amber-700 w-full pl-8" placeholder="0.00">
                                    <span class="absolute left-2 top-1/2 -translate-y-1/2 text-[10px] text-[var(--text-muted)] font-bold">ج.م</span>
                                </div>
                            </div>
                            <div class="sm:col-span-4">
                                <label class="sm:hidden text-[10px] text-[var(--text-muted)] font-semibold block mb-0.5">سعر شراء الدستة:</label>
                                <div class="relative">
                                    <input type="number" id="quickProdCostDozen" name="cost_price_dozen" step="0.01" min="0"
                                           class="erp-input text-xs font-numeric font-bold text-center text-amber-800 w-full pl-8" placeholder="0.00">
                                    <span class="absolute left-2 top-1/2 -translate-y-1/2 text-[10px] text-[var(--text-muted)] font-bold">ج.م</span>
                                </div>
                            </div>
                        </div>

                        <!-- 2. سعر بيع القطاعي (تجزئة) -->
                        <div class="grid grid-cols-1 sm:grid-cols-12 gap-2 items-center bg-emerald-500/5 p-2.5 rounded-lg border border-emerald-500/20">
                            <div class="sm:col-span-4 flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 shrink-0"></span>
                                <div>
                                    <div class="text-xs font-bold text-emerald-700 dark:text-emerald-400">سعر بيع القطاعي (تجزئة)</div>
                                    <div class="text-[10px] text-emerald-600/80">بيع المحل للزبون العادي</div>
                                </div>
                            </div>
                            <div class="sm:col-span-4">
                                <label class="sm:hidden text-[10px] text-[var(--text-muted)] font-semibold block mb-0.5">قطاعي القطعة:</label>
                                <div class="relative">
                                    <input type="number" id="quickProdRetailPiece" name="retail_price" step="0.01" min="0"
                                           class="erp-input text-xs font-numeric font-bold text-center text-emerald-700 w-full pl-8" placeholder="0.00">
                                    <span class="absolute left-2 top-1/2 -translate-y-1/2 text-[10px] text-[var(--text-muted)] font-bold">ج.م</span>
                                </div>
                            </div>
                            <div class="sm:col-span-4">
                                <label class="sm:hidden text-[10px] text-[var(--text-muted)] font-semibold block mb-0.5">قطاعي الدستة:</label>
                                <div class="relative">
                                    <input type="number" id="quickProdRetailDozen" name="retail_price_dozen" step="0.01" min="0"
                                           class="erp-input text-xs font-numeric font-bold text-center text-emerald-800 w-full pl-8" placeholder="0.00">
                                    <span class="absolute left-2 top-1/2 -translate-y-1/2 text-[10px] text-[var(--text-muted)] font-bold">ج.م</span>
                                </div>
                            </div>
                        </div>

                        <!-- 3. سعر بيع الجملة -->
                        <div class="grid grid-cols-1 sm:grid-cols-12 gap-2 items-center bg-blue-500/5 p-2.5 rounded-lg border border-blue-500/20">
                            <div class="sm:col-span-4 flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-blue-500 shrink-0"></span>
                                <div>
                                    <div class="text-xs font-bold text-blue-700 dark:text-blue-400">سعر بيع الجملة</div>
                                    <div class="text-[10px] text-blue-600/80">لتجار التجزئة والكميات</div>
                                </div>
                            </div>
                            <div class="sm:col-span-4">
                                <label class="sm:hidden text-[10px] text-[var(--text-muted)] font-semibold block mb-0.5">جملة القطعة:</label>
                                <div class="relative">
                                    <input type="number" id="quickProdWholesalePiece" name="wholesale_price" step="0.01" min="0"
                                           class="erp-input text-xs font-numeric font-bold text-center text-blue-700 w-full pl-8" placeholder="0.00">
                                    <span class="absolute left-2 top-1/2 -translate-y-1/2 text-[10px] text-[var(--text-muted)] font-bold">ج.م</span>
                                </div>
                            </div>
                            <div class="sm:col-span-4">
                                <label class="sm:hidden text-[10px] text-[var(--text-muted)] font-semibold block mb-0.5">جملة الدستة:</label>
                                <div class="relative">
                                    <input type="number" id="quickProdWholesaleDozen" name="wholesale_price_dozen" step="0.01" min="0"
                                           class="erp-input text-xs font-numeric font-bold text-center text-blue-800 w-full pl-8" placeholder="0.00">
                                    <span class="absolute left-2 top-1/2 -translate-y-1/2 text-[10px] text-[var(--text-muted)] font-bold">ج.م</span>
                                </div>
                            </div>
                        </div>

                        <!-- 4. سعر بيع الموزع -->
                        <div class="grid grid-cols-1 sm:grid-cols-12 gap-2 items-center bg-purple-500/5 p-2.5 rounded-lg border border-purple-500/20">
                            <div class="sm:col-span-4 flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-purple-500 shrink-0"></span>
                                <div>
                                    <div class="text-xs font-bold text-purple-700 dark:text-purple-400">سعر بيع الموزع</div>
                                    <div class="text-[10px] text-purple-600/80">لكبار الموزعين والوكلاء</div>
                                </div>
                            </div>
                            <div class="sm:col-span-4">
                                <label class="sm:hidden text-[10px] text-[var(--text-muted)] font-semibold block mb-0.5">موزع القطعة:</label>
                                <div class="relative">
                                    <input type="number" id="quickProdDistributorPiece" name="distributor_price" step="0.01" min="0"
                                           class="erp-input text-xs font-numeric font-bold text-center text-purple-700 w-full pl-8" placeholder="0.00">
                                    <span class="absolute left-2 top-1/2 -translate-y-1/2 text-[10px] text-[var(--text-muted)] font-bold">ج.م</span>
                                </div>
                            </div>
                            <div class="sm:col-span-4">
                                <label class="sm:hidden text-[10px] text-[var(--text-muted)] font-semibold block mb-0.5">موزع الدستة:</label>
                                <div class="relative">
                                    <input type="number" id="quickProdDistributorDozen" name="distributor_price_dozen" step="0.01" min="0"
                                           class="erp-input text-xs font-numeric font-bold text-center text-purple-800 w-full pl-8" placeholder="0.00">
                                    <span class="absolute left-2 top-1/2 -translate-y-1/2 text-[10px] text-[var(--text-muted)] font-bold">ج.م</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 5: Direct Invoice Purchase Entry (بيانات الشراء المباشرة بالفاتورة) -->
                <div class="bg-[var(--primary-soft)] border-2 border-[var(--primary)] rounded-xl p-3.5 space-y-3">
                    <div class="flex items-center justify-between border-b border-[var(--primary)]/30 pb-2.5">
                        <div class="flex items-center gap-2">
                            <div class="w-6 h-6 rounded-md bg-[var(--primary)] text-white flex items-center justify-center">
                                <x-icon name="shopping-cart" size="13" />
                            </div>
                            <div>
                                <div class="text-xs font-bold text-[var(--text-main)]">بيانات الشراء في الفاتورة الحالية</div>
                                <div class="text-[10px] text-[var(--text-muted)]">إدراج الصنف مباشرة كسطر شراء في جدول الفاتورة فور حفظه</div>
                            </div>
                        </div>
                        <label class="flex items-center gap-1.5 text-xs font-bold text-[var(--primary)] cursor-pointer select-none">
                            <input type="checkbox" id="quickProdInsertDirectly" checked class="w-4 h-4 rounded text-[var(--primary)] cursor-pointer">
                            <span>إدراج في الفاتورة فوراً</span>
                        </label>
                    </div>

                    <div id="quickProdDirectPurchaseFields" class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-end">
                        <!-- وحدة الشراء بالفاتورة -->
                        <div class="erp-form-group mb-0">
                            <label class="erp-form-label text-xs font-semibold mb-1">وحدة الشراء بالفاتورة</label>
                            <div class="grid grid-cols-2 gap-1.5 p-1 bg-[var(--bg-surface)] border border-[var(--border-color)] rounded-lg">
                                <button type="button" id="quickProdBuyPieceBtn" class="pos-unit-pill active py-1.5 text-xs font-bold justify-center" data-unit="piece">
                                    <span>قطعة (×1)</span>
                                </button>
                                <button type="button" id="quickProdBuyDozenBtn" class="pos-unit-pill py-1.5 text-xs font-bold justify-center" data-unit="dozen">
                                    <span id="quickProdBuyDozenLabel">دستة (×12)</span>
                                </button>
                            </div>
                            <input type="hidden" id="quickProdPurchaseUnitVal" value="piece">
                        </div>

                        <!-- الكمية المشتراة -->
                        <div class="erp-form-group mb-0">
                            <label for="quickProdPurchaseQty" class="erp-form-label text-xs font-semibold">
                                الكمية المشتراة بالفاتورة <span class="text-[var(--danger)]">*</span>
                            </label>
                            <input 
                                type="number" 
                                id="quickProdPurchaseQty" 
                                value="1" 
                                min="0.01" 
                                step="0.01" 
                                class="erp-input text-xs font-numeric font-bold text-center text-sm"
                            >
                        </div>

                        <!-- إجمالي سطر الشراء -->
                        <div class="erp-form-group mb-0 bg-[var(--bg-surface)] p-2.5 rounded-lg border border-[var(--border-color)] flex flex-col justify-center">
                            <span class="text-[10px] text-[var(--text-muted)] font-semibold">إجمالي سطر الشراء المتوقع:</span>
                            <div class="font-numeric font-bold text-base text-[var(--primary)] flex items-baseline gap-1" id="quickProdSubtotalBox">
                                <span id="quickProdLineSubtotalPreview">0.00</span>
                                <span class="text-xs font-normal text-[var(--text-muted)]">ج.م</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 6: Stock Alert -->
                <div class="erp-form-group mb-0">
                    <label for="quickProdMinStock" class="erp-form-label text-xs font-semibold">
                        حد الطلب الأدنى للتنبيه عند النواقص (قطع)
                    </label>
                    <input 
                        type="number" 
                        id="quickProdMinStock" 
                        name="min_stock_alert" 
                        value="5" 
                        min="0" 
                        class="erp-input text-xs font-numeric"
                    >
                </div>
            </div>

            <!-- Footer Actions -->
            <div class="erp-modal-footer flex items-center justify-between border-t border-[var(--border-color)] pt-3 p-4 sm:p-5 bg-[var(--surface-muted)]">
                <span class="text-[11px] text-[var(--text-muted)] hidden sm:inline">
                    اختصار: <kbd class="px-1.5 py-0.5 rounded bg-[var(--bg-surface)] border border-[var(--border-color)] font-mono text-[10px]">Enter</kbd> للحفظ السريع
                </span>
                <div class="flex items-center gap-2">
                    <button type="button" id="cancelQuickProductModalBtn" class="erp-btn erp-btn-secondary text-xs px-4 py-2 cursor-pointer">
                        إلغاء
                    </button>
                    <button type="submit" id="saveQuickProductBtn" class="erp-btn erp-btn-primary text-xs flex items-center gap-2 px-5 py-2 font-bold cursor-pointer shadow-sm">
                        <x-icon name="check" size="15" />
                        <span>حفظ وإدراج الصنف في الفاتورة فوراً</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
