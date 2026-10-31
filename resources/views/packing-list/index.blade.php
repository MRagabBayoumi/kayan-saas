@extends('layouts.app')

@section('control_panel')
    <x-control-panel title="قائمة التعبئة والتجهيز (Packing List)" :breadcrumbs="['المخزون والمنتجات' => route('products.index'), 'قائمة التعبئة (Packing List)' => route('packing-list.index')]">
        <x-slot:actions>
            <!-- Archive / History Link -->
            <a href="{{ route('packing-list.archive') }}" class="erp-btn erp-btn-secondary" title="عرض كافة قوائم التعبئة السابقة والبحث بالعميل">
                <x-icon name="archive" size="16" />
                <span>سجل القوائم السابقة</span>
            </a>

            <!-- Save To Archive Button -->
            <button type="button" id="saveToArchiveBtn" class="erp-btn erp-btn-primary" style="background: #059669; border-color: #059669;" title="حفظ قائمة التعبئة الحالية في السجل الدائم">
                <x-icon name="save" size="16" />
                <span id="saveBtnText">{{ $loadedPackingList ? 'تحديث في السجل' : 'حفظ في السجل' }}</span>
            </button>

            <!-- Reset / Clear Button -->
            <button type="button" id="clearListBtn" class="erp-btn erp-btn-danger erp-btn-sm" title="تفريغ كافة الأصناف">
                <x-icon name="trash-2" size="14" />
                <span>تفريغ القائمة</span>
            </button>

            <!-- Export Actions Dropdown -->
            <div class="erp-dropdown">
                <button type="button" class="erp-btn erp-btn-success" data-dropdown-toggle="packingListExportMenu">
                    <x-icon name="download" size="16" />
                    <span>تصدير البيانات</span>
                    <x-icon name="chevron-down" size="14" />
                </button>
                <div id="packingListExportMenu" class="erp-dropdown-menu">
                    <button type="button" id="exportExcelBtn" class="erp-dropdown-item font-medium">
                        <x-icon name="file-text" size="15" class="text-emerald-600" />
                        <span>تصدير إكسيل منسق (Excel .xls)</span>
                    </button>
                    <button type="button" id="exportCsvBtn" class="erp-dropdown-item font-medium">
                        <x-icon name="file" size="15" class="text-blue-600" />
                        <span>تصدير ملف بيانات (CSV .csv)</span>
                    </button>
                </div>
            </div>

            <!-- Print Actions Dropdown (3 Sizes) -->
            <div class="erp-dropdown">
                <button type="button" class="erp-btn erp-btn-primary" data-dropdown-toggle="packingListPrintMenu">
                    <x-icon name="printer" size="16" />
                    <span>طباعة القائمة (3 مقاسات)</span>
                    <x-icon name="chevron-down" size="14" />
                </button>
                <div id="packingListPrintMenu" class="erp-dropdown-menu">
                    <button type="button" data-print-format="a4" class="erp-dropdown-item">
                        <x-icon name="file-text" size="15" />
                        <span>طباعة A4 قياسي (ورقة شحن رسمية)</span>
                    </button>
                    <button type="button" data-print-format="a5" class="erp-dropdown-item">
                        <x-icon name="file" size="15" />
                        <span>طباعة A5 وسط (مدمج لتوفير الورق)</span>
                    </button>
                    <div class="erp-dropdown-divider"></div>
                    <button type="button" data-print-format="thermal_80mm" class="erp-dropdown-item">
                        <x-icon name="printer" size="15" />
                        <span>طباعة 80 مم حراري (طابعة كاشير/بون)</span>
                    </button>
                </div>
            </div>
        </x-slot:actions>

        <x-slot:extra>
            <div class="flex items-center gap-2">
                <span class="erp-badge erp-badge-neutral text-xs font-numeric" id="topItemsCountBadge">
                    0 أصناف
                </span>
                <span class="erp-badge erp-badge-info text-xs font-numeric" id="topQtyCountBadge">
                    0 إجمالي الكميات
                </span>
            </div>
        </x-slot:extra>
    </x-control-panel>
@endsection

@section('content')
<div id="packingListApp" data-page="packing-list" class="space-y-4">

    @if($loadedPackingList)
        <!-- Loaded Packing List Banner -->
        <div class="erp-card border-r-4 border-r-indigo-500 bg-indigo-50/70 dark:bg-indigo-950/40 p-3.5 rounded-lg flex flex-wrap items-center justify-between gap-3 text-sm">
            <div class="flex items-center gap-2.5 text-indigo-950 dark:text-indigo-200">
                <x-icon name="info" size="18" class="text-indigo-600 flex-shrink-0" />
                <div>
                    <span class="font-bold">أنت تقوم باستعراض وتعديل قائمة محفوظة:</span>
                    <span class="font-numeric font-extrabold text-indigo-700 dark:text-indigo-400 mx-1">{{ $loadedPackingList->packing_number }}</span>
                    <span>للعميل:</span>
                    <strong class="mx-1 text-[var(--text-main)]">{{ $loadedPackingList->customer_name }}</strong>
                    <span class="text-xs text-indigo-700/80 dark:text-indigo-400/80">({{ $loadedPackingList->items->count() }} أصناف - تم الحفظ {{ $loadedPackingList->created_at->diffForHumans() }})</span>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('packing-list.index') }}" class="erp-btn erp-btn-sm erp-btn-secondary">
                    <x-icon name="plus" size="14" />
                    <span>إنشاء قائمة جديدة فارغة</span>
                </a>
                <a href="{{ route('packing-list.archive') }}" class="erp-btn erp-btn-sm erp-btn-secondary">
                    <x-icon name="archive" size="14" />
                    <span>العودة للأرشيف</span>
                </a>
            </div>
        </div>
    @endif

    <!-- 1. Document Metadata Header Card -->
    <div class="erp-card">
        <div class="erp-card-header">
            <div class="erp-card-title">
                <x-icon name="file-text" size="16" class="text-[var(--primary)]" />
                <span>بيانات قائمة التعبئة والشحن</span>
            </div>
            <div class="text-xs text-[var(--text-muted)] font-numeric flex items-center gap-1.5" id="autoSaveIndicator">
                <x-icon name="check-circle" size="14" class="text-emerald-500" />
                <span>محفوظ تلقائياً في المتصفح</span>
            </div>
        </div>

        <div class="erp-card-body">
            <div class="erp-form-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px;">
                <!-- Doc Number -->
                <div class="erp-form-group mb-0">
                    <label class="erp-form-label text-xs font-semibold">رقم قائمة التعبئة</label>
                    <input type="text" id="docNumberInput" value="{{ $docNumber }}" class="erp-input font-numeric font-semibold" placeholder="PL-20261031-XXXX">
                </div>

                <!-- Doc Date -->
                <div class="erp-form-group mb-0">
                    <label class="erp-form-label text-xs font-semibold">تاريخ التجهيز</label>
                    <input type="date" id="docDateInput" value="{{ date('Y-m-d') }}" class="erp-input font-numeric">
                </div>

                <!-- Customer / Recipient -->
                <div class="erp-form-group mb-0">
                    <label class="erp-form-label text-xs font-semibold">العميل / المستلم (اختياري)</label>
                    <input type="text" id="customerNameInput" class="erp-input" placeholder="اسم العميل أو الجهة المستلمة...">
                </div>

                <!-- Warehouse -->
                <div class="erp-form-group mb-0">
                    <label class="erp-form-label text-xs font-semibold">المستودع المصدّر</label>
                    <select id="warehouseSelect" class="erp-input erp-select">
                        <option value="">كافة المخازن / بدون تحديد</option>
                        @foreach($warehouses as $wh)
                            <option value="{{ $wh->name }}">{{ $wh->name }} ({{ $wh->code }})</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Default Price Mode & Shipping Notes -->
            <div class="erp-form-grid mt-3 pt-3" style="grid-template-columns: minmax(220px, 1fr) minmax(300px, 2.5fr); gap: 14px; border-top: 1px dashed var(--border-light);">
                <div class="erp-form-group mb-0">
                    <label class="erp-form-label text-xs font-semibold">السعر الافتراضي للأصناف</label>
                    <select id="defaultPriceModeSelect" class="erp-input erp-select font-semibold text-[var(--primary)]">
                        <option value="wholesale" selected>سعر الجملة (Wholesale)</option>
                        <option value="retail">سعر القطاعي (Retail)</option>
                        <option value="distributor">سعر الموزع (Distributor)</option>
                        <option value="cost">سعر التكلفة (Cost)</option>
                        <option value="none">بدون سعر (بيان تعبئة فقط)</option>
                    </select>
                </div>

                <div class="erp-form-group mb-0">
                    <label class="erp-form-label text-xs font-semibold">ملاحظات التعبئة والشحن العامة</label>
                    <input type="text" id="docNotesInput" class="erp-input text-xs" placeholder="مثال: تسليم شركة شحن النورس / عدد 4 كراتين مغلفة بنايلون...">
                </div>
            </div>

            <!-- Column Visibility Toggles (Show / Hide Quantity & Price) -->
            <div class="mt-3 pt-3 flex flex-wrap items-center gap-6 text-xs font-semibold text-[var(--text-main)]" style="border-top: 1px dashed var(--border-light); background: var(--bg-surface-alt, #f8fafc); padding: 8px 12px; border-radius: 6px;">
                <span class="text-[var(--text-muted)] font-bold flex items-center gap-1.5">
                    <x-icon name="sliders" size="14" class="text-[var(--primary)]" />
                    <span>تخصيص أعمدة العرض والتصدير والطباعة:</span>
                </span>
                
                <label class="flex items-center gap-2 cursor-pointer select-none">
                    <input type="checkbox" id="toggleShowQtyCheckbox" class="rounded border-gray-300 text-[var(--primary)] focus:ring-[var(--primary)]" checked>
                    <span>تضمين الكمية (Quantity)</span>
                </label>

                <label class="flex items-center gap-2 cursor-pointer select-none">
                    <input type="checkbox" id="toggleShowPriceCheckbox" class="rounded border-gray-300 text-[var(--primary)] focus:ring-[var(--primary)]" checked>
                    <span>تضمين السعر والإجمالي (Price & Total)</span>
                </label>

                <label class="flex items-center gap-2 cursor-pointer select-none">
                    <input type="checkbox" id="toggleShowPackagesCheckbox" class="rounded border-gray-300 text-[var(--primary)] focus:ring-[var(--primary)]" checked>
                    <span>تضمين عدد الطرود/الكراتين (Packages)</span>
                </label>
            </div>
        </div>
    </div>

    <!-- 2. High-Speed POS Quick Entry Bar -->
    <div class="purchases-quick-entry">
        <div class="purchases-quick-entry-header">
            <div class="purchases-quick-entry-title">
                <x-icon name="zap" size="18" class="text-amber-500" />
                <span>شريط البحث والإدراج السريع للأصناف (الباركود أو الاسم أو SKU)</span>
            </div>

            <div class="flex items-center gap-2">
                <!-- Fast-Scan Mode Toggle Pill -->
                <label for="fastScanModeCheckbox" id="fastScanBadge" class="pos-fast-scan-badge" title="عند التفعيل: مسح الباركود يدرج الصنف فوراً">
                    <input type="checkbox" id="fastScanModeCheckbox" class="hidden" checked>
                    <x-icon name="barcode" size="14" />
                    <span id="fastScanText">المسح المباشر السريع (مفعل)</span>
                </label>

                <div class="purchases-quick-entry-shortcuts hidden md:flex">
                    <span class="purchases-shortcut-pill">
                        <kbd>F2</kbd> للبحث
                    </span>
                    <span class="purchases-shortcut-pill">
                        <kbd>Enter</kbd> للإدراج
                    </span>
                </div>
            </div>
        </div>

        <div class="purchases-quick-entry-body">
            <!-- Row 1: Search hero input + UOM + Qty Stepper + Packages + Price + Add Button -->
            <div class="flex flex-wrap items-end gap-3 w-full">
                <!-- Product Search & Barcode -->
                <div class="erp-form-group mb-0 relative flex-1 min-w-[280px]">
                    <label for="productSearchInput" class="erp-form-label text-xs font-semibold flex items-center justify-between">
                        <span class="flex items-center gap-1">
                            <x-icon name="barcode" size="13" class="text-[var(--primary)]" />
                            <span>مربع البحث / قراءة الباركود (F2)</span>
                        </span>
                        <span id="quickStockBadge" class="hidden pos-stock-badge in-stock font-bold">
                            المتاح: 0
                        </span>
                    </label>
                    <div class="relative w-full">
                        <input 
                            type="text" 
                            id="productSearchInput" 
                            class="erp-input erp-hero-search-input text-xs w-full font-semibold" 
                            placeholder="اضرب الباركود بمسدس الباركود أو اكتب الاسم / SKU..." 
                            autocomplete="off"
                        >
                        <button 
                            type="button" 
                            id="clearSearchBtn" 
                            class="absolute left-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 hidden"
                        >
                            <x-icon name="x" size="14" />
                        </button>
                    </div>

                    <!-- Instant Autocomplete Dropdown -->
                    <div id="searchResultsDropdown" class="purchases-autocomplete-dropdown hidden"></div>
                </div>

                <!-- Unit of Measurement -->
                <div class="erp-form-group mb-0 w-28 flex-shrink-0">
                    <label for="itemUnitSelect" class="erp-form-label text-xs font-semibold flex items-center justify-between">
                        <span>الوحدة</span>
                        <span id="detectedUnitBadge" class="text-[10px] text-emerald-600 font-bold hidden font-sans">تلقائي ✓</span>
                    </label>
                    <select id="itemUnitSelect" class="erp-input erp-select text-xs font-semibold text-[var(--text-main)] w-full">
                        @php
                            $defaultUnits = ['قطعة', 'علبة', 'كرتونة', 'دستة', 'طقم', 'باكت', 'كيلو', 'متر', 'لتر'];
                            $allUnitNames = collect($units->pluck('name'))->merge($defaultUnits)->unique()->filter()->values();
                        @endphp
                        @foreach($allUnitNames as $uName)
                            <option value="{{ $uName }}" {{ $uName === 'قطعة' ? 'selected' : '' }}>{{ $uName }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Quantity Stepper -->
                <div class="erp-form-group mb-0 w-32 flex-shrink-0" id="quickQtyWrapper">
                    <label for="itemQtyInput" class="erp-form-label text-xs font-semibold">الكمية</label>
                    <div class="pos-qty-stepper w-full">
                        <button type="button" class="pos-qty-btn" id="qtyMinusBtn" tabindex="-1">−</button>
                        <input 
                            type="number" 
                            step="any" 
                            min="0.01" 
                            id="itemQtyInput" 
                            class="pos-qty-input font-numeric" 
                            value="1"
                        >
                        <button type="button" class="pos-qty-btn" id="qtyPlusBtn" tabindex="-1">+</button>
                    </div>
                </div>

                <!-- Packages / Cartons Count -->
                <div class="erp-form-group mb-0 w-24 flex-shrink-0" id="quickPackagesWrapper">
                    <label for="itemPackagesInput" class="erp-form-label text-xs font-semibold">عدد الطرود</label>
                    <input 
                        type="number" 
                        id="itemPackagesInput" 
                        min="0" 
                        step="1" 
                        value="0" 
                        class="erp-input font-numeric text-center text-xs w-full" 
                        placeholder="0"
                    >
                </div>

                <!-- Price Input with Affix -->
                <div class="erp-form-group mb-0 w-36 flex-shrink-0" id="quickPriceWrapper">
                    <label for="itemPriceInput" class="erp-form-label text-xs font-semibold flex items-center justify-between">
                        <span>السعر</span>
                        <span id="selectedPriceTierBadge" class="text-[10px] text-[var(--primary)] font-bold">(جملة)</span>
                    </label>
                    <div class="purchases-input-affix">
                        <input 
                            type="number" 
                            step="0.01" 
                            min="0" 
                            id="itemPriceInput" 
                            class="erp-input font-numeric text-xs font-bold text-[var(--primary)]" 
                            value="0.00"
                        >
                        <span class="purchases-input-affix-label">ج.م</span>
                    </div>
                </div>

                <!-- Add Button -->
                <div class="erp-form-group mb-0 flex-shrink-0">
                    <button type="button" id="addItemBtn" class="erp-btn erp-btn-primary font-bold flex items-center gap-1.5" style="height: 40px; padding: 0 18px;">
                        <x-icon name="plus" size="16" />
                        <span>إدراج للصنف</span>
                    </button>
                </div>
            </div>

            <!-- Row 2: Item Packing Notes -->
            <div class="mt-2 pt-2" style="border-top: 1px dashed var(--border-light);">
                <div class="erp-form-group mb-0">
                    <label for="itemNotesInput" class="erp-form-label text-xs text-[var(--text-muted)]">ملاحظات التعبئة للصنف (اختياري)</label>
                    <input 
                        type="text" 
                        id="itemNotesInput" 
                        class="erp-input text-xs" 
                        placeholder="مثال: كرتونة رقم 1 / لون عشوائي / تغليف بلاستيك ممتاز..."
                    >
                </div>
            </div>
        </div>
    </div>

    <!-- 3. Packing List Data Table (Standard Kayan ERP Table) -->
    <div class="erp-card">
        <div class="erp-card-header">
            <div class="erp-card-title">
                <x-icon name="list" size="16" class="text-[var(--primary)]" />
                <span>أصناف قائمة التعبئة والتجهيز</span>
                <span id="tableCountBadge" class="erp-badge erp-badge-neutral text-xs font-numeric">0 أصناف</span>
            </div>
            <div class="text-xs text-[var(--text-muted)]">
                💡 يمكنك تعديل الكميات والطرود ونوع السعر مباشرة داخل خلايا الجدول.
            </div>
        </div>

        <div class="erp-card-body p-0">
            <div class="erp-table-responsive">
                <table class="erp-table" id="packingListTable">
                    <thead>
                        <tr>
                            <th class="w-10 text-center">#</th>
                            <th class="w-28 text-center">كود الصنف (SKU)</th>
                            <th class="w-28 text-center">الباركود</th>
                            <th class="min-w-[220px]">اسم الصنف والتصنيف</th>
                            <th class="w-24 text-center">الوحدة</th>
                            <th class="w-28 text-center col-qty">الكمية</th>
                            <th class="w-24 text-center col-packages">عدد الطرود</th>
                            <th class="w-36 text-center col-unit-price">السعر المختار</th>
                            <th class="w-32 text-center col-line-total">الإجمالي</th>
                            <th class="w-40">ملاحظات التعبئة</th>
                            <th class="w-12 text-center">حذف</th>
                        </tr>
                    </thead>
                    <tbody id="packingListBody">
                        <tr id="emptyRowPlaceholder">
                            <td colspan="11" class="py-10 text-center text-[var(--text-muted)]">
                                <div class="flex flex-col items-center justify-center gap-1.5">
                                    <x-icon name="clipboard" size="32" class="text-gray-300 dark:text-gray-600" />
                                    <span class="text-sm font-semibold">قائمة التعبئة فارغة حالياً</span>
                                    <span class="text-xs text-[var(--text-muted)]">استخدم شريط البحث بالأعلى أو مسدس الباركود لإدراج الأصناف</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                    <tfoot class="bg-[var(--surface-muted)] font-bold border-t-2 border-[var(--border-color)]" id="tableFooter">
                        <tr>
                            <td colspan="5" class="py-2.5 px-4 text-left font-bold text-xs">الإجمالي الكلي:</td>
                            <td class="py-2.5 px-2 text-center font-numeric font-bold text-sm text-[var(--primary)] col-qty" id="footTotalQty">0</td>
                            <td class="py-2.5 px-2 text-center font-numeric font-bold text-sm col-packages" id="footTotalPackages">0</td>
                            <td class="py-2.5 px-2 text-center text-xs text-[var(--text-muted)] col-unit-price">-</td>
                            <td class="py-2.5 px-2 text-center font-numeric font-bold text-sm text-emerald-600 col-line-total" id="footTotalAmount">0.00 ج.م</td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- 4. Reusable Kayan ERP KPI Metrics Cards -->
    <div class="inventory-kpi-grid">
        <!-- Distinct Items -->
        <div class="erp-card inventory-kpi-card mb-0">
            <div class="inventory-kpi-icon primary">
                <x-icon name="layers" size="22" />
            </div>
            <div>
                <div class="text-xs text-[var(--text-muted)] font-semibold">أصناف بالقائمة</div>
                <div class="font-numeric text-xl font-extrabold text-[var(--text-main)] mt-0.5" id="cardItemsCount">0</div>
            </div>
        </div>

        <!-- Total Units -->
        <div class="erp-card inventory-kpi-card mb-0" id="kpiQtyCard">
            <div class="inventory-kpi-icon info">
                <x-icon name="package" size="22" />
            </div>
            <div>
                <div class="text-xs text-[var(--text-muted)] font-semibold">إجمالي الكميات والقطع</div>
                <div class="font-numeric text-xl font-extrabold text-[#0284c7] mt-0.5" id="cardTotalQty">0</div>
            </div>
        </div>

        <!-- Total Cartons / Packages -->
        <div class="erp-card inventory-kpi-card mb-0" id="kpiPackagesCard">
            <div class="inventory-kpi-icon warning">
                <x-icon name="box" size="22" />
            </div>
            <div>
                <div class="text-xs text-[var(--text-muted)] font-semibold">إجمالي الكراتين والطرود</div>
                <div class="font-numeric text-xl font-extrabold text-[#f59e0b] mt-0.5" id="cardTotalPackages">0</div>
            </div>
        </div>

        <!-- Total Estimated Amount -->
        <div class="erp-card inventory-kpi-card mb-0" id="kpiAmountCard">
            <div class="inventory-kpi-icon" style="background: rgba(16, 185, 129, 0.1); color: #10b981;">
                <x-icon name="dollar-sign" size="22" />
            </div>
            <div>
                <div class="text-xs text-[var(--text-muted)] font-semibold">القيمة التقديرية</div>
                <div class="font-numeric text-xl font-extrabold text-emerald-600 mt-0.5" id="cardTotalAmount">0.00 ج.م</div>
            </div>
        </div>
    </div>
    <!-- Action Feedback Toast -->
    <div id="packingNotificationToast" class="fixed bottom-6 left-1/2 -translate-x-1/2 z-50 flex items-center gap-2 px-5 py-3 rounded-lg shadow-xl border text-sm font-semibold transition-all duration-300 hidden" style="background: #f0fdf4; border-color: #10b981; color: #166534;">
        <span id="packingToastIcon">
            <x-icon name="check-circle" size="18" />
        </span>
        <span id="packingToastMessage">تم الحفظ بنجاح</span>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const STORAGE_KEY = 'kayan_packing_list_v1';
    let packingItems = [];
    let currentSelectedProduct = null;
    let searchDebounceTimer = null;

    // Check if a saved list was passed from controller for viewing/editing
    const serverLoadedList = {!! json_encode($loadedData ?? null) !!};
    let currentListId = serverLoadedList ? serverLoadedList.id : null;

    // Elements
    const docNumberInput = document.getElementById('docNumberInput');
    const docDateInput = document.getElementById('docDateInput');
    const customerNameInput = document.getElementById('customerNameInput');
    const warehouseSelect = document.getElementById('warehouseSelect');
    const defaultPriceModeSelect = document.getElementById('defaultPriceModeSelect');
    const docNotesInput = document.getElementById('docNotesInput');
    const fastScanModeCheckbox = document.getElementById('fastScanModeCheckbox');
    const fastScanText = document.getElementById('fastScanText');

    // Column Toggles
    const toggleShowQtyCheckbox = document.getElementById('toggleShowQtyCheckbox');
    const toggleShowPriceCheckbox = document.getElementById('toggleShowPriceCheckbox');
    const toggleShowPackagesCheckbox = document.getElementById('toggleShowPackagesCheckbox');

    // Quick entry wrappers
    const quickQtyWrapper = document.getElementById('quickQtyWrapper');
    const quickPriceWrapper = document.getElementById('quickPriceWrapper');
    const quickPackagesWrapper = document.getElementById('quickPackagesWrapper');
    const kpiQtyCard = document.getElementById('kpiQtyCard');
    const kpiAmountCard = document.getElementById('kpiAmountCard');
    const kpiPackagesCard = document.getElementById('kpiPackagesCard');

    // Search & Add elements
    const productSearchInput = document.getElementById('productSearchInput');
    const clearSearchBtn = document.getElementById('clearSearchBtn');
    const searchResultsDropdown = document.getElementById('searchResultsDropdown');
    const quickStockBadge = document.getElementById('quickStockBadge');
    const itemUnitSelect = document.getElementById('itemUnitSelect');
    const detectedUnitBadge = document.getElementById('detectedUnitBadge');
    let currentCandidateProduct = null;
    let currentSearchResults = [];
    let activeDropdownIndex = -1;
    const itemQtyInput = document.getElementById('itemQtyInput');
    const qtyMinusBtn = document.getElementById('qtyMinusBtn');
    const qtyPlusBtn = document.getElementById('qtyPlusBtn');
    const itemPackagesInput = document.getElementById('itemPackagesInput');
    const itemPriceInput = document.getElementById('itemPriceInput');
    const selectedPriceTierBadge = document.getElementById('selectedPriceTierBadge');
    const itemNotesInput = document.getElementById('itemNotesInput');
    const addItemBtn = document.getElementById('addItemBtn');

    // Table elements
    const packingListBody = document.getElementById('packingListBody');
    const footTotalQty = document.getElementById('footTotalQty');
    const footTotalPackages = document.getElementById('footTotalPackages');
    const footTotalAmount = document.getElementById('footTotalAmount');

    // Badges & Counters
    const topItemsCountBadge = document.getElementById('topItemsCountBadge');
    const topQtyCountBadge = document.getElementById('topQtyCountBadge');
    const tableCountBadge = document.getElementById('tableCountBadge');
    const cardItemsCount = document.getElementById('cardItemsCount');
    const cardTotalQty = document.getElementById('cardTotalQty');
    const cardTotalPackages = document.getElementById('cardTotalPackages');
    const cardTotalAmount = document.getElementById('cardTotalAmount');

    // Action buttons
    const saveToArchiveBtn = document.getElementById('saveToArchiveBtn');
    const saveBtnText = document.getElementById('saveBtnText');
    const clearListBtn = document.getElementById('clearListBtn');
    const exportExcelBtn = document.getElementById('exportExcelBtn');
    const exportCsvBtn = document.getElementById('exportCsvBtn');

    // Load persisted state
    loadSavedState();
    applyColumnVisibility();

    // Toggle Column Visibility Listeners
    [toggleShowQtyCheckbox, toggleShowPriceCheckbox, toggleShowPackagesCheckbox].forEach(chk => {
        chk.addEventListener('change', function () {
            applyColumnVisibility();
            renderTable();
            saveState();
        });
    });

    function applyColumnVisibility() {
        const showQty = toggleShowQtyCheckbox.checked;
        const showPrice = toggleShowPriceCheckbox.checked;
        const showPackages = toggleShowPackagesCheckbox.checked;
        const showLineTotal = showQty && showPrice;

        // Table headers and footer cells
        document.querySelectorAll('.col-qty').forEach(el => el.style.display = showQty ? '' : 'none');
        document.querySelectorAll('.col-packages').forEach(el => el.style.display = showPackages ? '' : 'none');
        document.querySelectorAll('.col-unit-price').forEach(el => el.style.display = showPrice ? '' : 'none');
        document.querySelectorAll('.col-line-total').forEach(el => el.style.display = showLineTotal ? '' : 'none');

        // Quick Entry Wrappers
        if (quickQtyWrapper) quickQtyWrapper.style.display = showQty ? '' : 'none';
        if (quickPackagesWrapper) quickPackagesWrapper.style.display = showPackages ? '' : 'none';
        if (quickPriceWrapper) quickPriceWrapper.style.display = showPrice ? '' : 'none';

        // Table Footer visibility: hide whole footer if neither Qty, Packages nor LineTotal is active
        const tableFooter = document.getElementById('tableFooter');
        if (tableFooter) {
            tableFooter.style.display = (showQty || showPackages || showLineTotal) ? '' : 'none';
        }

        // KPI Cards
        if (kpiQtyCard) kpiQtyCard.style.display = showQty ? '' : 'none';
        if (kpiPackagesCard) kpiPackagesCard.style.display = showPackages ? '' : 'none';
        if (kpiAmountCard) kpiAmountCard.style.display = showLineTotal ? '' : 'none';

        // Top Header Badges
        if (topQtyCountBadge) topQtyCountBadge.style.display = showQty ? '' : 'none';
    }

    // Fast-scan toggle text change
    fastScanModeCheckbox.addEventListener('change', function () {
        fastScanText.textContent = this.checked ? 'المسح المباشر السريع (مفعل)' : 'المسح المباشر السريع (معطل)';
    });

    // Keyboard shortcut F2 to focus search
    window.addEventListener('keydown', function (e) {
        if (e.key === 'F2') {
            e.preventDefault();
            productSearchInput.focus();
            productSearchInput.select();
        }
    });

    // Search Input
    productSearchInput.addEventListener('input', function () {
        const val = this.value.trim();
        clearSearchBtn.classList.toggle('hidden', val.length === 0);

        clearTimeout(searchDebounceTimer);
        if (val.length === 0) {
            hideDropdown();
            currentSelectedProduct = null;
            currentCandidateProduct = null;
            currentSearchResults = [];
            activeDropdownIndex = -1;
            quickStockBadge.classList.add('hidden');
            if (detectedUnitBadge) detectedUnitBadge.classList.add('hidden');
            setUnit('قطعة', false);
            return;
        }

        searchDebounceTimer = setTimeout(() => {
            fetchProducts(val);
        }, 150);
    });

    // Search Keydown
    productSearchInput.addEventListener('keydown', async function (e) {
        if (e.key === 'ArrowDown') {
            if (currentSearchResults.length > 0) {
                e.preventDefault();
                activeDropdownIndex = (activeDropdownIndex + 1) % currentSearchResults.length;
                updateDropdownHighlight();
            }
            return;
        }

        if (e.key === 'ArrowUp') {
            if (currentSearchResults.length > 0) {
                e.preventDefault();
                activeDropdownIndex = (activeDropdownIndex - 1 + currentSearchResults.length) % currentSearchResults.length;
                updateDropdownHighlight();
            }
            return;
        }

        if (e.key === 'Enter') {
            e.preventDefault();
            const term = this.value.trim();
            if (!term) return;

            // If an item in dropdown is highlighted or candidate exists
            if (activeDropdownIndex >= 0 && currentSearchResults[activeDropdownIndex]) {
                selectProduct(currentSearchResults[activeDropdownIndex]);
            } else if (currentCandidateProduct && !currentSelectedProduct) {
                selectProduct(currentCandidateProduct);
            }

            if (fastScanModeCheckbox.checked) {
                if (currentSelectedProduct) {
                    addItemFromForm();
                } else {
                    await directBarcodeLookup(term);
                }
            } else {
                if (currentSelectedProduct) {
                    hideDropdown();
                    itemQtyInput.focus();
                    itemQtyInput.select();
                } else {
                    addItemFromForm();
                }
            }
        } else if (e.key === 'Escape') {
            hideDropdown();
        }
    });

    clearSearchBtn.addEventListener('click', function () {
        productSearchInput.value = '';
        clearSearchBtn.classList.add('hidden');
        quickStockBadge.classList.add('hidden');
        if (detectedUnitBadge) detectedUnitBadge.classList.add('hidden');
        setUnit('قطعة', false);
        hideDropdown();
        currentSelectedProduct = null;
        currentCandidateProduct = null;
        currentSearchResults = [];
        activeDropdownIndex = -1;
        productSearchInput.focus();
    });

    // Steppers
    qtyMinusBtn.addEventListener('click', function () {
        let q = parseFloat(itemQtyInput.value) || 1;
        if (q > 1) {
            itemQtyInput.value = Math.max(1, q - 1);
        }
    });

    qtyPlusBtn.addEventListener('click', function () {
        let q = parseFloat(itemQtyInput.value) || 1;
        itemQtyInput.value = q + 1;
    });

    // Add Item Click
    addItemBtn.addEventListener('click', function () {
        addItemFromForm();
    });

    // Enter in inputs adds item
    [itemQtyInput, itemPackagesInput, itemPriceInput, itemNotesInput].forEach(el => {
        el.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                addItemFromForm();
            }
        });
    });

    // Change default price mode
    defaultPriceModeSelect.addEventListener('change', function () {
        const mode = this.value;
        if (packingItems.length > 0) {
            if (confirm('هل ترغب في تحديث أسعار كافة الأصناف الموجودة بالقائمة لتتوافق مع النوع المختار (' + getPriceModeLabel(mode) + ')؟')) {
                packingItems.forEach(item => {
                    item.price = resolveItemPrice(item, mode);
                    item.total = item.qty * item.price;
                });
                renderTable();
                saveState();
            }
        }
        if (currentSelectedProduct) {
            itemPriceInput.value = resolveItemPrice(currentSelectedProduct, mode);
        }
    });

    // Clear List Button
    clearListBtn.addEventListener('click', function () {
        if (packingItems.length === 0) return;
        if (confirm('هل أنت متأكد من تفريغ كافة أصناف قائمة التعبئة؟')) {
            packingItems = [];
            currentListId = null;
            if (saveBtnText) saveBtnText.textContent = 'حفظ في السجل';
            renderTable();
            localStorage.removeItem(STORAGE_KEY);
            productSearchInput.focus();
        }
    });

    // Unified Robust Form Action Submitter (Exports & Prints)
    function submitAction(url, target = '_self', extraParams = {}) {
        if (packingItems.length === 0) {
            alert('يرجى إضافة أصناف إلى قائمة التعبئة أولاً قبل المتابعة.');
            return;
        }

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = url;
        form.target = target;
        form.style.display = 'none';

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        const data = {
            _token: csrfToken || '',
            doc_number: docNumberInput.value.trim(),
            doc_date: docDateInput.value,
            customer_name: customerNameInput.value.trim(),
            warehouse_name: warehouseSelect.value,
            price_type: defaultPriceModeSelect.value,
            notes: docNotesInput.value.trim(),
            show_quantity: toggleShowQtyCheckbox.checked ? '1' : '0',
            show_price: toggleShowPriceCheckbox.checked ? '1' : '0',
            show_packages: toggleShowPackagesCheckbox.checked ? '1' : '0',
            items: JSON.stringify(packingItems),
            ...extraParams
        };

        for (const [key, val] of Object.entries(data)) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = key;
            input.value = val;
            form.appendChild(input);
        }

        document.body.appendChild(form);
        form.submit();
        setTimeout(() => form.remove(), 2500);
    }

    // Export Excel via Dropdown
    exportExcelBtn.addEventListener('click', function () {
        submitAction('{{ route('packing-list.export-excel') }}', '_self');
    });

    // Export CSV via Dropdown
    exportCsvBtn.addEventListener('click', function () {
        submitAction('{{ route('packing-list.export-csv') }}', '_self');
    });

    // Print Formats (3 sizes)
    document.querySelectorAll('[data-print-format]').forEach(btn => {
        btn.addEventListener('click', function () {
            const format = this.dataset.printFormat;
            submitAction('{{ route('packing-list.print') }}', '_blank', { format: format });
        });
    });

    // Auto-save on metadata change
    [docNumberInput, docDateInput, customerNameInput, warehouseSelect, docNotesInput].forEach(el => {
        el.addEventListener('change', saveState);
        el.addEventListener('input', saveState);
    });

    // Functions

    function setUnit(unitName, showBadge = true) {
        if (!unitName) unitName = 'قطعة';
        unitName = unitName.trim();

        let found = false;
        for (let i = 0; i < itemUnitSelect.options.length; i++) {
            if (itemUnitSelect.options[i].value.trim().toLowerCase() === unitName.toLowerCase()) {
                itemUnitSelect.selectedIndex = i;
                found = true;
                break;
            }
        }

        if (!found) {
            const opt = document.createElement('option');
            opt.value = unitName;
            opt.textContent = unitName;
            opt.selected = true;
            itemUnitSelect.appendChild(opt);
            itemUnitSelect.value = unitName;
        }

        if (detectedUnitBadge) {
            if (showBadge && unitName) {
                detectedUnitBadge.textContent = 'وحدة الصنف: ' + unitName;
                detectedUnitBadge.classList.remove('hidden');
            } else {
                detectedUnitBadge.classList.add('hidden');
            }
        }
    }

    async function fetchProducts(q) {
        try {
            const resp = await fetch('{{ route('packing-list.search-products') }}?q=' + encodeURIComponent(q));
            const data = await resp.json();
            if (data.success && data.products.length > 0) {
                currentSearchResults = data.products;
                activeDropdownIndex = 0;
                renderDropdown(data.products);

                // Auto-detect and show unit of measurement for closest matching product as user types
                const qNorm = q.trim().toLowerCase();
                const matched = data.products.find(p => 
                    p.name.trim().toLowerCase() === qNorm ||
                    (p.sku && p.sku.trim().toLowerCase() === qNorm) ||
                    (p.barcode && p.barcode.trim() === q)
                ) || data.products.find(p => 
                    p.name.trim().toLowerCase().startsWith(qNorm)
                ) || data.products[0];

                if (matched) {
                    currentCandidateProduct = matched;
                    setUnit(matched.unit, true);

                    quickStockBadge.textContent = 'المتاح: ' + matched.total_stock + ' ' + (matched.unit || 'قطعة');
                    quickStockBadge.classList.remove('hidden');

                    const mode = defaultPriceModeSelect.value;
                    const price = resolveItemPrice(matched, mode);
                    itemPriceInput.value = price.toFixed(2);
                    selectedPriceTierBadge.textContent = '(' + getPriceModeLabel(mode) + ')';
                }
            } else {
                currentSearchResults = [];
                currentCandidateProduct = null;
                activeDropdownIndex = -1;
                renderDropdown([]);
                if (detectedUnitBadge) detectedUnitBadge.classList.add('hidden');
                quickStockBadge.classList.add('hidden');
            }
        } catch (e) {
            console.error('Failed to search products:', e);
        }
    }

    async function directBarcodeLookup(barcode) {
        try {
            const resp = await fetch('{{ route('packing-list.search-products') }}?barcode=' + encodeURIComponent(barcode));
            const data = await resp.json();
            if (data.success && data.products.length > 0) {
                const prod = data.products[0];
                selectProduct(prod);
                addItemFromForm();
            } else {
                alert('لم يتم العثور على صنف بهذا الباركود: ' + barcode);
            }
        } catch (e) {
            console.error('Barcode lookup failed:', e);
        }
    }

    function updateDropdownHighlight() {
        const items = searchResultsDropdown.querySelectorAll('.purchases-suggestion-item');
        items.forEach((el, idx) => {
            if (idx === activeDropdownIndex) {
                el.classList.add('bg-[var(--bg-hover)]', 'border-r-4', 'border-r-[var(--primary)]');
                const prod = currentSearchResults[idx];
                if (prod) {
                    currentCandidateProduct = prod;
                    setUnit(prod.unit, true);
                    quickStockBadge.textContent = 'المتاح: ' + prod.total_stock + ' ' + (prod.unit || 'قطعة');
                    quickStockBadge.classList.remove('hidden');
                    const mode = defaultPriceModeSelect.value;
                    const price = resolveItemPrice(prod, mode);
                    itemPriceInput.value = price.toFixed(2);
                    selectedPriceTierBadge.textContent = '(' + getPriceModeLabel(mode) + ')';
                }
            } else {
                el.classList.remove('bg-[var(--bg-hover)]', 'border-r-4', 'border-r-[var(--primary)]');
            }
        });
    }

    function renderDropdown(products) {
        if (products.length === 0) {
            searchResultsDropdown.innerHTML = '<div class="p-3 text-center text-xs text-[var(--text-muted)]">لا توجد نتائج مطابقة</div>';
            searchResultsDropdown.classList.remove('hidden');
            return;
        }

        let html = '';
        products.forEach((p, idx) => {
            const mode = defaultPriceModeSelect.value;
            const price = resolveItemPrice(p, mode);
            const isFirst = idx === 0 ? 'bg-[var(--bg-hover)] border-r-4 border-r-[var(--primary)]' : '';
            html += `
                <div class="purchases-suggestion-item p-2.5 hover:bg-[var(--bg-hover)] cursor-pointer border-b border-[var(--border-light)] last:border-b-0 flex items-center justify-between ${isFirst}" data-index="${idx}">
                    <div>
                        <div class="font-bold text-xs text-[var(--text-main)]">${escapeHtml(p.name)}</div>
                        <div class="text-[11px] text-[var(--text-muted)] flex items-center gap-2 mt-0.5 font-numeric">
                            <span class="font-mono text-[var(--primary)] font-semibold">${p.sku || '-'}</span>
                            <span>·</span>
                            <span>باركود: <span class="font-mono">${p.barcode || '-'}</span></span>
                            <span>·</span>
                            <span>${p.category_name || 'عام'}</span>
                            <span>·</span>
                            <span class="text-emerald-700 font-bold bg-emerald-50 px-1 rounded">الوحدة: ${escapeHtml(p.unit || 'قطعة')}</span>
                        </div>
                    </div>
                    <div class="text-left font-numeric">
                        <div class="font-bold text-xs text-[var(--primary)]">${price > 0 ? price.toFixed(2) + ' ج.م' : 'بدون سعر'}</div>
                        <div class="text-[10px] text-[var(--text-muted)]">المتاح: ${p.total_stock} ${p.unit}</div>
                    </div>
                </div>
            `;
        });

        searchResultsDropdown.innerHTML = html;
        searchResultsDropdown.classList.remove('hidden');

        searchResultsDropdown.querySelectorAll('.purchases-suggestion-item').forEach((el) => {
            const idx = parseInt(el.dataset.index);
            el.addEventListener('mouseenter', function () {
                activeDropdownIndex = idx;
                updateDropdownHighlight();
            });

            el.addEventListener('click', function () {
                const prod = products[idx];
                selectProduct(prod);
                hideDropdown();
                itemQtyInput.focus();
                itemQtyInput.select();
            });
        });
    }

    function hideDropdown() {
        searchResultsDropdown.classList.add('hidden');
    }

    function selectProduct(p) {
        currentSelectedProduct = p;
        currentCandidateProduct = p;
        productSearchInput.value = p.name;
        clearSearchBtn.classList.remove('hidden');

        quickStockBadge.textContent = 'المتاح: ' + p.total_stock + ' ' + (p.unit || 'قطعة');
        quickStockBadge.classList.remove('hidden');

        setUnit(p.unit, true);

        const mode = defaultPriceModeSelect.value;
        const price = resolveItemPrice(p, mode);
        itemPriceInput.value = price.toFixed(2);
        selectedPriceTierBadge.textContent = '(' + getPriceModeLabel(mode) + ')';

        if (p.package_factor && p.package_factor > 1) {
            itemPackagesInput.value = '1';
        }
    }

    function resolveItemPrice(itemOrProd, mode) {
        if (mode === 'none') return 0;
        if (mode === 'retail') return parseFloat(itemOrProd.retail_price || 0);
        if (mode === 'wholesale') return parseFloat(itemOrProd.wholesale_price || 0);
        if (mode === 'distributor') return parseFloat(itemOrProd.distributor_price || itemOrProd.wholesale_price || 0);
        if (mode === 'cost') return parseFloat(itemOrProd.cost_price || 0);
        return parseFloat(itemOrProd.wholesale_price || 0);
    }

    function getPriceModeLabel(mode) {
        switch (mode) {
            case 'wholesale': return 'جملة';
            case 'retail': return 'قطاعي';
            case 'distributor': return 'موزع';
            case 'cost': return 'تكلفة';
            case 'none': return 'بدون سعر';
            default: return 'سعر مخصص';
        }
    }

    function addItemFromForm() {
        let name = productSearchInput.value.trim();
        if (!name) {
            productSearchInput.focus();
            return;
        }

        const activeProduct = currentSelectedProduct || (currentCandidateProduct && (currentCandidateProduct.name.trim().toLowerCase() === name.toLowerCase() || !name) ? currentCandidateProduct : null);

        let qty = parseFloat(itemQtyInput.value) || 1;
        let packages = parseFloat(itemPackagesInput.value) || 0;
        let price = parseFloat(itemPriceInput.value) || 0;
        let unit = itemUnitSelect.value;
        let notes = itemNotesInput.value.trim();

        let existingIndex = -1;
        if (activeProduct) {
            existingIndex = packingItems.findIndex(i => i.id === activeProduct.id);
        } else {
            existingIndex = packingItems.findIndex(i => i.name.toLowerCase() === name.toLowerCase());
        }

        if (existingIndex >= 0) {
            packingItems[existingIndex].qty += qty;
            packingItems[existingIndex].packages_count += packages;
            if (price > 0) packingItems[existingIndex].price = price;
            packingItems[existingIndex].total = packingItems[existingIndex].qty * packingItems[existingIndex].price;
            if (notes) {
                packingItems[existingIndex].notes = packingItems[existingIndex].notes ? (packingItems[existingIndex].notes + ' / ' + notes) : notes;
            }
        } else {
            const newItem = {
                id: activeProduct ? activeProduct.id : null,
                sku: activeProduct ? activeProduct.sku : '',
                barcode: activeProduct ? activeProduct.barcode : '',
                name: activeProduct ? activeProduct.name : name,
                category_name: activeProduct ? activeProduct.category_name : 'عام',
                unit: unit,
                qty: qty,
                packages_count: packages,
                price: price,
                wholesale_price: activeProduct ? activeProduct.wholesale_price : price,
                retail_price: activeProduct ? activeProduct.retail_price : price,
                distributor_price: activeProduct ? activeProduct.distributor_price : price,
                cost_price: activeProduct ? activeProduct.cost_price : price,
                total: qty * price,
                notes: notes,
            };
            packingItems.push(newItem);
        }

        productSearchInput.value = '';
        clearSearchBtn.classList.add('hidden');
        quickStockBadge.classList.add('hidden');
        if (detectedUnitBadge) detectedUnitBadge.classList.add('hidden');
        setUnit('قطعة', false);
        itemQtyInput.value = '1';
        itemPackagesInput.value = '0';
        itemNotesInput.value = '';
        currentSelectedProduct = null;
        currentCandidateProduct = null;
        currentSearchResults = [];
        activeDropdownIndex = -1;
        hideDropdown();

        renderTable();
        saveState();

        productSearchInput.focus();
    }

    function renderTable() {
        const showQty = toggleShowQtyCheckbox.checked;
        const showPrice = toggleShowPriceCheckbox.checked;
        const showPackages = toggleShowPackagesCheckbox.checked;
        const showLineTotal = showQty && showPrice;

        if (packingItems.length === 0) {
            packingListBody.innerHTML = `
                <tr id="emptyRowPlaceholder">
                    <td colspan="11" class="py-10 text-center text-[var(--text-muted)]">
                        <div class="flex flex-col items-center justify-center gap-1.5">
                            <x-icon name="clipboard" size="32" class="text-gray-300 dark:text-gray-600" />
                            <span class="text-sm font-semibold">قائمة التعبئة فارغة حالياً</span>
                            <span class="text-xs text-[var(--text-muted)]">استخدم شريط البحث بالأعلى أو مسدس الباركود لإدراج الأصناف</span>
                        </div>
                    </td>
                </tr>
            `;
            updateTotals();
            return;
        }

        let html = '';
        packingItems.forEach((item, idx) => {
            const lineTotal = item.qty * item.price;
            html += `
                <tr class="hover:bg-[var(--bg-hover)] transition-colors" data-row-index="${idx}">
                    <td class="text-center text-[var(--text-muted)] font-numeric font-semibold">${idx + 1}</td>
                    <td class="text-center font-mono font-semibold text-[var(--primary)]">${escapeHtml(item.sku || '—')}</td>
                    <td class="text-center font-mono text-[var(--text-muted)] text-xs">${escapeHtml(item.barcode || '—')}</td>
                    <td>
                        <div class="font-bold text-[var(--text-main)]">${escapeHtml(item.name)}</div>
                        <div class="text-[11px] text-[var(--text-muted)]">${escapeHtml(item.category_name || 'عام')}</div>
                    </td>
                    <td class="text-center">
                        <span class="erp-badge erp-badge-neutral text-xs">${escapeHtml(item.unit || 'قطعة')}</span>
                    </td>
                    <td class="text-center col-qty" style="${showQty ? '' : 'display: none;'}">
                        <div class="pos-qty-stepper mx-auto" style="width: 100px; height: 30px;">
                            <button type="button" class="pos-qty-btn table-qty-minus" data-index="${idx}" tabindex="-1">−</button>
                            <input type="number" step="any" min="0.01" value="${item.qty}" class="pos-qty-input font-numeric table-qty-input" data-index="${idx}">
                            <button type="button" class="pos-qty-btn table-qty-plus" data-index="${idx}" tabindex="-1">+</button>
                        </div>
                    </td>
                    <td class="text-center col-packages" style="${showPackages ? '' : 'display: none;'}">
                        <input type="number" min="0" step="1" value="${item.packages_count}" class="erp-input font-numeric text-center text-xs table-packages-input" style="width: 70px; height: 30px; margin: 0 auto;" data-index="${idx}">
                    </td>
                    <td class="text-center col-unit-price" style="${showPrice ? '' : 'display: none;'}">
                        <div class="flex items-center gap-1 justify-center">
                            <input type="number" min="0" step="0.01" value="${item.price}" class="erp-input font-numeric text-center text-xs font-bold text-[var(--primary)] table-price-input" style="width: 75px; height: 30px;" data-index="${idx}">
                            <select class="erp-input erp-select text-[10px] p-0 table-price-tier-select" style="width: 70px; height: 30px;" data-index="${idx}">
                                <option value="custom">مخصص</option>
                                <option value="wholesale">جملة</option>
                                <option value="retail">قطاعي</option>
                                <option value="distributor">موزع</option>
                                <option value="cost">تكلفة</option>
                                <option value="zero">بدون (0)</option>
                            </select>
                        </div>
                    </td>
                    <td class="text-center font-numeric font-bold text-emerald-600 col-line-total" style="${showLineTotal ? '' : 'display: none;'}">
                        ${lineTotal > 0 ? lineTotal.toFixed(2) + ' ج.م' : '—'}
                    </td>
                    <td>
                        <input type="text" value="${escapeHtml(item.notes || '')}" placeholder="ملاحظة..." class="erp-input text-xs table-notes-input" style="height: 30px;" data-index="${idx}">
                    </td>
                    <td class="text-center">
                        <button type="button" class="erp-btn erp-btn-ghost erp-btn-sm text-rose-500 hover:text-rose-700 remove-row-btn" data-index="${idx}" title="حذف">
                            <x-icon name="trash-2" size="14" />
                        </button>
                    </td>
                </tr>
            `;
        });

        packingListBody.innerHTML = html;

        // Wire up table events
        packingListBody.querySelectorAll('.table-qty-input').forEach(input => {
            input.addEventListener('change', function () {
                const idx = parseInt(this.dataset.index);
                const q = parseFloat(this.value) || 1;
                packingItems[idx].qty = q;
                packingItems[idx].total = q * packingItems[idx].price;
                renderTable();
                saveState();
            });
        });

        packingListBody.querySelectorAll('.table-qty-minus').forEach(btn => {
            btn.addEventListener('click', function () {
                const idx = parseInt(this.dataset.index);
                if (packingItems[idx].qty > 1) {
                    packingItems[idx].qty = Math.max(1, packingItems[idx].qty - 1);
                    packingItems[idx].total = packingItems[idx].qty * packingItems[idx].price;
                    renderTable();
                    saveState();
                }
            });
        });

        packingListBody.querySelectorAll('.table-qty-plus').forEach(btn => {
            btn.addEventListener('click', function () {
                const idx = parseInt(this.dataset.index);
                packingItems[idx].qty += 1;
                packingItems[idx].total = packingItems[idx].qty * packingItems[idx].price;
                renderTable();
                saveState();
            });
        });

        packingListBody.querySelectorAll('.table-packages-input').forEach(input => {
            input.addEventListener('change', function () {
                const idx = parseInt(this.dataset.index);
                packingItems[idx].packages_count = parseFloat(this.value) || 0;
                saveState();
                updateTotals();
            });
        });

        packingListBody.querySelectorAll('.table-price-input').forEach(input => {
            input.addEventListener('change', function () {
                const idx = parseInt(this.dataset.index);
                const p = parseFloat(this.value) || 0;
                packingItems[idx].price = p;
                packingItems[idx].total = packingItems[idx].qty * p;
                renderTable();
                saveState();
            });
        });

        packingListBody.querySelectorAll('.table-price-tier-select').forEach(sel => {
            sel.addEventListener('change', function () {
                const idx = parseInt(this.dataset.index);
                const mode = this.value;
                let newPrice = packingItems[idx].price;
                if (mode === 'zero') newPrice = 0;
                else if (mode === 'wholesale') newPrice = packingItems[idx].wholesale_price || 0;
                else if (mode === 'retail') newPrice = packingItems[idx].retail_price || 0;
                else if (mode === 'distributor') newPrice = packingItems[idx].distributor_price || packingItems[idx].wholesale_price || 0;
                else if (mode === 'cost') newPrice = packingItems[idx].cost_price || 0;

                packingItems[idx].price = newPrice;
                packingItems[idx].total = packingItems[idx].qty * newPrice;
                renderTable();
                saveState();
            });
        });

        packingListBody.querySelectorAll('.table-notes-input').forEach(input => {
            input.addEventListener('change', function () {
                const idx = parseInt(this.dataset.index);
                packingItems[idx].notes = this.value.trim();
                saveState();
            });
        });

        packingListBody.querySelectorAll('.remove-row-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const idx = parseInt(this.dataset.index);
                packingItems.splice(idx, 1);
                renderTable();
                saveState();
            });
        });

        updateTotals();
    }

    function updateTotals() {
        const showQty = toggleShowQtyCheckbox.checked;
        const showPrice = toggleShowPriceCheckbox.checked;
        const showPackages = toggleShowPackagesCheckbox.checked;
        const showLineTotal = showQty && showPrice;

        let totQty = 0;
        let totPackages = 0;
        let totAmount = 0;

        packingItems.forEach(item => {
            if (showQty) totQty += item.qty;
            if (showPackages) totPackages += item.packages_count;
            if (showLineTotal) totAmount += (item.qty * item.price);
        });

        footTotalQty.textContent = totQty.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
        footTotalPackages.textContent = totPackages > 0 ? totPackages.toLocaleString('en-US') : '0';
        footTotalAmount.textContent = showLineTotal ? (totAmount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ج.م') : '—';

        topItemsCountBadge.textContent = packingItems.length + ' أصناف';
        topQtyCountBadge.textContent = totQty.toLocaleString('en-US') + ' إجمالي الكميات';
        tableCountBadge.textContent = packingItems.length + ' أصناف';

        cardItemsCount.textContent = packingItems.length;
        cardTotalQty.textContent = totQty.toLocaleString('en-US');
        cardTotalPackages.textContent = totPackages > 0 ? totPackages.toLocaleString('en-US') : '0';
        cardTotalAmount.textContent = showLineTotal ? (totAmount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ج.م') : '—';
    }

    function saveState() {
        const state = {
            doc_number: docNumberInput.value.trim(),
            doc_date: docDateInput.value,
            customer_name: customerNameInput.value.trim(),
            warehouse_name: warehouseSelect.value,
            price_mode: defaultPriceModeSelect.value,
            notes: docNotesInput.value.trim(),
            show_quantity: toggleShowQtyCheckbox.checked,
            show_price: toggleShowPriceCheckbox.checked,
            show_packages: toggleShowPackagesCheckbox.checked,
            items: packingItems,
        };
        localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
    }

    function loadSavedState() {
        if (serverLoadedList) {
            // Priority 1: Load directly from database record passed by controller
            if (serverLoadedList.doc_number) docNumberInput.value = serverLoadedList.doc_number;
            if (serverLoadedList.doc_date) docDateInput.value = serverLoadedList.doc_date;
            if (serverLoadedList.customer_name) customerNameInput.value = serverLoadedList.customer_name;
            if (serverLoadedList.warehouse_name) warehouseSelect.value = serverLoadedList.warehouse_name;
            if (serverLoadedList.price_type) defaultPriceModeSelect.value = serverLoadedList.price_type;
            if (serverLoadedList.notes) docNotesInput.value = serverLoadedList.notes;
            if (typeof serverLoadedList.show_quantity === 'boolean') toggleShowQtyCheckbox.checked = serverLoadedList.show_quantity;
            if (typeof serverLoadedList.show_price === 'boolean') toggleShowPriceCheckbox.checked = serverLoadedList.show_price;
            if (typeof serverLoadedList.show_packages === 'boolean') toggleShowPackagesCheckbox.checked = serverLoadedList.show_packages;

            if (Array.isArray(serverLoadedList.items) && serverLoadedList.items.length > 0) {
                packingItems = serverLoadedList.items;
                renderTable();
            }
            return;
        }

        // Priority 2: Fallback to localStorage draft
        try {
            const raw = localStorage.getItem(STORAGE_KEY);
            if (!raw) return;
            const state = JSON.parse(raw);
            if (state) {
                if (state.doc_number) docNumberInput.value = state.doc_number;
                if (state.doc_date) docDateInput.value = state.doc_date;
                if (state.customer_name) customerNameInput.value = state.customer_name;
                if (state.warehouse_name) warehouseSelect.value = state.warehouse_name;
                if (state.price_mode) defaultPriceModeSelect.value = state.price_mode;
                if (state.notes) docNotesInput.value = state.notes;
                if (typeof state.show_quantity === 'boolean') toggleShowQtyCheckbox.checked = state.show_quantity;
                if (typeof state.show_price === 'boolean') toggleShowPriceCheckbox.checked = state.show_price;
                if (typeof state.show_packages === 'boolean') toggleShowPackagesCheckbox.checked = state.show_packages;

                if (Array.isArray(state.items) && state.items.length > 0) {
                    packingItems = state.items;
                    renderTable();
                }
            }
        } catch (e) {
            console.error('Failed to load saved state:', e);
        }
    }

    // Save To Database Archive Listener
    if (saveToArchiveBtn) {
        saveToArchiveBtn.addEventListener('click', async function () {
            if (packingItems.length === 0) {
                showToast('يرجى إضافة صنف واحد على الأقل قبل الحفظ في السجل.', 'warning');
                return;
            }

            const origHtml = saveToArchiveBtn.innerHTML;
            saveToArchiveBtn.disabled = true;
            saveToArchiveBtn.innerHTML = `
                <span class="inline-block animate-spin mr-1">⌛</span>
                <span>جاري الحفظ في السجل...</span>
            `;

            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const payload = {
                    packing_list_id: currentListId,
                    doc_number: docNumberInput.value.trim(),
                    doc_date: docDateInput.value,
                    customer_name: customerNameInput.value.trim(),
                    warehouse_name: warehouseSelect.value,
                    price_type: defaultPriceModeSelect.value,
                    notes: docNotesInput.value.trim(),
                    show_quantity: toggleShowQtyCheckbox.checked ? 1 : 0,
                    show_price: toggleShowPriceCheckbox.checked ? 1 : 0,
                    show_packages: toggleShowPackagesCheckbox.checked ? 1 : 0,
                    items: packingItems,
                };

                const res = await fetch("{{ route('packing-list.save') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken || ''
                    },
                    body: JSON.stringify(payload)
                });

                const data = await res.json();

                if (data.success) {
                    currentListId = data.id;
                    if (data.packing_number) {
                        docNumberInput.value = data.packing_number;
                    }
                    if (saveBtnText) {
                        saveBtnText.textContent = 'تحديث في السجل';
                    }
                    const indicator = document.getElementById('autoSaveIndicator');
                    if (indicator) {
                        indicator.innerHTML = `
                            <svg class="w-3.5 h-3.5 text-emerald-500 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span class="text-emerald-700 font-bold">محفوظ في السجل برقم ${escapeHtml(data.packing_number)}</span>
                        `;
                    }
                    showToast(data.message || 'تم حفظ قائمة التعبئة بنجاح في السجل.', 'success');
                    saveState();
                } else {
                    showToast(data.message || 'حدث خطأ أثناء حفظ القائمة في السجل.', 'error');
                }
            } catch (err) {
                console.error('Error saving packing list:', err);
                showToast('تعذر الاتصال بالخادم لحفظ القائمة.', 'error');
            } finally {
                saveToArchiveBtn.disabled = false;
                saveToArchiveBtn.innerHTML = `
                    <svg class="w-4 h-4 inline-block ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path>
                    </svg>
                    <span>${currentListId ? 'تحديث في السجل' : 'حفظ في السجل'}</span>
                `;
            }
        });
    }

    function showToast(message, type = 'success') {
        const toast = document.getElementById('packingNotificationToast');
        const toastMsg = document.getElementById('packingToastMessage');
        if (!toast || !toastMsg) return;

        toastMsg.textContent = message;
        if (type === 'error') {
            toast.style.borderColor = '#ef4444';
            toast.style.background = '#fef2f2';
            toast.style.color = '#991b1b';
        } else if (type === 'warning') {
            toast.style.borderColor = '#f59e0b';
            toast.style.background = '#fffbeb';
            toast.style.color = '#92400e';
        } else {
            toast.style.borderColor = '#10b981';
            toast.style.background = '#f0fdf4';
            toast.style.color = '#166534';
        }

        toast.classList.remove('hidden');
        toast.style.opacity = '1';

        setTimeout(() => {
            toast.style.opacity = '0';
            setTimeout(() => toast.classList.add('hidden'), 300);
        }, 3500);
    }

    function escapeHtml(text) {
        if (!text) return '';
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return String(text).replace(/[&<>"']/g, m => map[m]);
    }

    document.addEventListener('click', function (e) {
        if (!searchResultsDropdown.contains(e.target) && e.target !== productSearchInput) {
            hideDropdown();
        }
    });
});
</script>
@endsection
