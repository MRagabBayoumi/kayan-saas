@extends('layouts.app')

@section('control_panel')
    <x-control-panel title="قائمة التعبئة والتجهيز (Packing List)" :breadcrumbs="['المخزون والمنتجات' => route('products.index'), 'سجل قوائم التعبئة' => route('packing-list.index'), ($loadedPackingList ? 'تعديل قائمة: ' . $loadedPackingList->packing_number : 'إنشاء قائمة جديدة') => '#']">
        <x-slot:actions>
            <!-- Archive / History Link -->
            <a href="{{ route('packing-list.index') }}" class="erp-btn erp-btn-secondary" title="عرض كافة قوائم التعبئة السابقة والبحث بالعميل">
                <x-icon name="list" size="16" />
                <span>سجل القوائم السابقة</span>
            </a>

            @if($loadedPackingList)
                <a href="{{ route('packing-list.create') }}" class="erp-btn erp-btn-secondary" title="إنشاء قائمة جديدة فارغة">
                    <x-icon name="plus" size="16" />
                    <span>قائمة جديدة</span>
                </a>
            @endif

            <!-- Upload Customer Order File -->
            <button type="button" id="uploadCustomerOrderFileBtn" class="erp-btn bg-indigo-600 hover:bg-indigo-700 text-white font-bold shadow-sm" title="رفع شيت إكسيل أو CSV لطلب العميل ومطابقته فوراً مع رصيد المخزن">
                <x-icon name="upload" size="16" />
                <span>رفع شيت طلب العميل (Excel)</span>
            </button>
            <input type="file" id="customerOrderFileInput" accept=".xlsx,.xls,.csv" class="hidden">

            <!-- Stock Inspector & Allocation Button -->
            <button type="button" id="openStockInspectorBtn" class="erp-btn bg-emerald-600 hover:bg-emerald-700 text-white font-bold shadow-sm" title="فحص ومطابقة رصيد المخزن وتخصيص كميات طلب العميل وتصدير شيت مقسم">
                <x-icon name="check-circle" size="16" />
                <span>فحص ومطابقة رصيد المخزن</span>
            </button>

            <!-- Refresh Prices Top Button -->
            <button type="button" id="refreshPricesTopBtn" class="erp-btn erp-btn-secondary font-bold text-indigo-700 dark:text-indigo-300" title="تحديث أسعار كافة أصناف القائمة طبقاً لأحدث أسعار مسجلة في قاعدة البيانات">
                <x-icon name="refresh-cw" size="15" />
                <span>تحديث الأسعار</span>
            </button>

            <!-- Save / Update To Archive Button -->
            <button type="button" id="saveToArchiveBtn" class="erp-btn erp-btn-primary" style="background: #059669; border-color: #059669;" title="حفظ وتعديل قائمة التعبئة الحالية في السجل الدائم">
                <x-icon name="save" size="16" />
                <span id="saveBtnText">{{ $loadedPackingList ? 'حفظ وتعديل في السجل' : 'حفظ في السجل' }}</span>
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
                        <span>تصدير إكسيل رسمي (Excel .xlsx)</span>
                    </button>
                    <button type="button" id="exportCsvBtn" class="erp-dropdown-item font-medium">
                        <x-icon name="file" size="15" class="text-blue-600" />
                        <span>تصدير ملف بيانات (CSV .csv)</span>
                    </button>
                    <div class="erp-dropdown-divider"></div>
                    <button type="button" id="exportCustomerStockExcelBtn" class="erp-dropdown-item font-bold text-emerald-700 dark:text-emerald-400">
                        <x-icon name="file-spreadsheet" size="15" class="text-emerald-600" />
                        <span>تصدير إكسيل العميل (مقسم: متاح + رصيد صفر)</span>
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
                <a href="{{ route('packing-list.create') }}" class="erp-btn erp-btn-sm erp-btn-secondary">
                    <x-icon name="plus" size="14" />
                    <span>إنشاء قائمة جديدة فارغة</span>
                </a>
                <a href="{{ route('packing-list.index') }}" class="erp-btn erp-btn-sm erp-btn-secondary">
                    <x-icon name="list" size="14" />
                    <span>العودة للسجل</span>
                </a>
            </div>
        </div>
    @endif

    <!-- Unsaved Local Draft Banner (if user had an uncommitted draft in browser) -->
    <div id="unconfirmedDraftAlert" style="display: none;" class="erp-card border-r-4 border-r-amber-500 bg-amber-50/90 dark:bg-amber-950/40 p-3.5 rounded-lg flex flex-wrap items-center justify-between gap-3 text-sm">
        <div class="flex items-center gap-2.5 text-amber-950 dark:text-amber-200">
            <x-icon name="alert-triangle" size="18" class="text-amber-600 flex-shrink-0" />
            <div>
                <span class="font-bold">توجد مسودة عمل غير محفوظة من جلسة سابقة:</span>
                <span class="text-xs text-amber-800 dark:text-amber-300 font-numeric mr-1" id="unconfirmedDraftDetails"></span>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" id="restoreDraftBtn" class="erp-btn erp-btn-sm erp-btn-primary font-bold">
                <x-icon name="rotate-ccw" size="14" />
                <span>استعادة المسودة</span>
            </button>
            <button type="button" id="discardDraftBtn" class="erp-btn erp-btn-sm erp-btn-secondary text-rose-600 dark:text-rose-400">
                <x-icon name="trash" size="14" />
                <span>تجاهل وبدء جديد</span>
            </button>
        </div>
    </div>

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

                        <!-- Instant Autocomplete Dropdown -->
                        <div 
                            id="searchResultsDropdown" 
                            class="purchases-autocomplete-dropdown hidden"
                            style="display: none; position: absolute; top: calc(100% + 4px); right: 0; left: 0; width: 100%; min-width: 450px; max-height: 280px; overflow-y: auto; z-index: 9999;"
                        ></div>
                    </div>
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
            <div class="flex items-center gap-3">
                <button type="button" id="refreshPricesBtn" class="erp-btn erp-btn-secondary erp-btn-sm font-bold text-indigo-700 dark:text-indigo-300" title="تحديث أسعار كافة أصناف القائمة طبقاً لأحدث أسعار مسجلة في قاعدة البيانات">
                    <x-icon name="refresh-cw" size="13" />
                    <span>تحديث الأسعار من النظام</span>
                </button>
                <div class="text-xs text-[var(--text-muted)] hidden md:block">
                    💡 يمكنك تعديل الكميات والطرود ونوع السعر مباشرة داخل خلايا الجدول.
                </div>
            </div>
        </div>

        <div class="erp-card-body p-0">
            <div class="erp-table-responsive">
                <table class="erp-table" id="packingListTable">
                    <thead>
                        <tr>
                            <th class="w-12 text-center">#</th>
                            <th class="w-24 text-center">كود الصنف (SKU)</th>
                            <th class="w-24 text-center">الباركود</th>
                            <th class="min-w-[220px]">اسم الصنف والتصنيف</th>
                            <th class="w-24 text-center">الوحدة</th>
                            <th class="w-32 text-center col-qty">الكمية</th>
                            <th class="w-24 text-center col-packages">عدد الطرود</th>
                            <th class="w-48 text-center col-unit-price">السعر المختار</th>
                            <th class="w-32 text-center col-line-total">الإجمالي</th>
                            <th class="w-36">ملاحظات التعبئة</th>
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
    <!-- Stock Inspector & Allocation Modal -->
    <div id="stockInspectorModal" class="erp-modal-overlay" style="display: none;">
        <div class="erp-modal-container flex flex-col shadow-2xl border border-[var(--border-color)] overflow-hidden" style="width: min(1200px, 96vw); max-width: 1200px; height: 90vh; max-height: 90vh; background: var(--bg-surface); color: var(--text-main);">
            <!-- Modal Header -->
            <div class="erp-modal-header flex items-center justify-between px-5 py-3 border-b border-[var(--border-color)] bg-[var(--bg-surface-alt)] flex-shrink-0">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-emerald-500/15 text-emerald-500 flex items-center justify-center font-bold">
                        <x-icon name="check-circle" size="18" />
                    </div>
                    <div>
                        <div class="font-bold text-sm text-[var(--text-main)] flex items-center gap-2">
                            <span>فحص ومطابقة رصيد المخزن وتخصيص كميات العميل</span>
                            <span id="inspectorCustomerNameBadge" class="erp-badge erp-badge-info text-xs"></span>
                        </div>
                        <div class="text-[11px] text-[var(--text-muted)] mt-0.5">
                            مطابقة أصناف الطلب مع الرصيد الفعلي بالمستودع وتحديد كميات التجهيز المعتمدة.
                        </div>
                    </div>
                </div>
                <button type="button" id="closeInspectorModalBtn" class="erp-modal-close" title="إغلاق النافذة">
                    <x-icon name="x" size="18" />
                </button>
            </div>

            <!-- KPI Summary Bar -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 p-3 border-b border-[var(--border-color)] bg-[var(--bg-surface-alt)] flex-shrink-0 text-center">
                <div class="p-2 rounded-lg border border-[var(--border-color)] bg-[var(--bg-surface)]">
                    <div class="text-[11px] text-[var(--text-muted)] font-semibold">إجمالي أصناف الطلب</div>
                    <div id="inspectorTotalItems" class="text-base font-bold font-numeric text-[var(--text-main)] mt-0.5">0</div>
                </div>
                <div class="p-2 rounded-lg border border-emerald-500/30 bg-emerald-500/10">
                    <div class="text-[11px] text-emerald-600 dark:text-emerald-400 font-bold">متوفر بالمخزن (&gt; 0)</div>
                    <div id="inspectorInStockCount" class="text-base font-bold font-numeric text-emerald-600 dark:text-emerald-400 mt-0.5">0</div>
                </div>
                <div class="p-2 rounded-lg border border-rose-500/30 bg-rose-500/10">
                    <div class="text-[11px] text-rose-600 dark:text-rose-400 font-bold">غير متوفر (رصيد صفر)</div>
                    <div id="inspectorZeroStockCount" class="text-base font-bold font-numeric text-rose-600 dark:text-rose-400 mt-0.5">0</div>
                </div>
                <div class="p-2 rounded-lg border border-indigo-500/30 bg-indigo-500/10">
                    <div class="text-[11px] text-indigo-600 dark:text-indigo-400 font-bold">نسبة توفر الأصناف</div>
                    <div id="inspectorAvailabilityRate" class="text-base font-bold font-numeric text-indigo-600 dark:text-indigo-400 mt-0.5">0%</div>
                </div>
            </div>

            <!-- Allocation Toolbar (The 3 Options) -->
            <div class="px-4 py-2.5 border-b border-[var(--border-color)] bg-[var(--bg-surface)] flex flex-wrap items-center justify-between gap-2.5 flex-shrink-0">
                <div class="flex items-center gap-1.5 text-xs font-bold text-[var(--text-main)]">
                    <x-icon name="sliders" size="14" class="text-emerald-500" />
                    <span>خيارات التخصيص السريع لكميات العميل:</span>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <!-- Option A: Full Available Stock -->
                    <button type="button" id="allocateAvailableBtn" class="erp-btn erp-btn-success erp-btn-sm font-bold text-xs" style="background-color: #10b981; color: #ffffff !important;" title="إعطاء العميل كامل الرصيد المتاح بالمخزن لكل صنف متوفر">
                        <x-icon name="check" size="13" />
                        <span>الكمية المتاحة بالكامل</span>
                    </button>

                    <!-- Option B: Minimum Stock Alert -->
                    <button type="button" id="allocateMinAlertBtn" class="erp-btn erp-btn-secondary erp-btn-sm font-bold text-xs" title="إعطاء العميل كمية الحد الأدنى (أو الرصيد المتاح إذا كان أقل)">
                        <x-icon name="shield" size="13" />
                        <span>كمية الحد الأدنى</span>
                    </button>

                    <!-- Option C: Custom Uniform Qty -->
                    <div class="flex items-center gap-1 bg-[var(--bg-surface-alt)] p-1 rounded-md border border-[var(--border-color)]">
                        <input type="number" id="customAllocationQtyInput" class="erp-input font-numeric font-bold text-xs text-center" style="width: 60px; height: 28px; padding: 2px; background: var(--bg-surface); color: var(--text-main);" placeholder="الكمية" value="1" min="0.01" step="any">
                        <button type="button" id="allocateCustomQtyBtn" class="erp-btn erp-btn-secondary erp-btn-sm text-xs font-bold" style="height: 28px;" title="تطبيق هذه الكمية على جميع الأصناف المتاحة بالمخزن">
                            <span>تطبيق كمية محددة</span>
                        </button>
                    </div>

                    <!-- Reset to zero for shortages -->
                    <button type="button" id="zeroShortagesBtn" class="erp-btn erp-btn-ghost erp-btn-sm text-rose-500 hover:text-rose-600 text-xs font-semibold" title="تصفير كمية أي صنف رصيده صفر">
                        <span>تصفير النواقص</span>
                    </button>
                </div>
            </div>

            <!-- Modal Body (Scrollable Table with Sticky Header) -->
            <div class="erp-modal-body p-0 flex-1 min-h-0 overflow-auto relative bg-[var(--bg-surface)]" id="inspectorScrollArea">
                <div id="inspectorLoadingState" class="py-12 text-center hidden">
                    <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-emerald-500 border-t-transparent"></div>
                    <div class="text-xs font-bold text-[var(--text-muted)] mt-2">جارٍ فحص ومطابقة أرصدة المخزن...</div>
                </div>

                <div class="w-full" id="inspectorTableWrapper">
                    <table class="erp-table text-xs w-full border-collapse" id="inspectorStockTable" style="margin: 0;">
                        <thead class="sticky top-0 z-20 shadow-sm" style="background-color: var(--bg-surface-alt); border-bottom: 2px solid var(--border-color);">
                            <tr>
                                <th class="w-8 text-center font-bold" style="background-color: var(--bg-surface-alt); padding: 8px 4px;">#</th>
                                <th class="w-20 text-center font-bold" style="background-color: var(--bg-surface-alt); padding: 8px 6px;">كود (SKU)</th>
                                <th class="w-24 text-center font-bold" style="background-color: var(--bg-surface-alt); padding: 8px 6px;">الباركود</th>
                                <th class="min-w-[220px] text-right font-bold" style="background-color: var(--bg-surface-alt); padding: 8px 8px;">اسم الصنف والتصنيف</th>
                                <th class="w-14 text-center font-bold" style="background-color: var(--bg-surface-alt); padding: 8px 6px;">الوحدة</th>
                                <th class="w-20 text-center font-bold" style="background-color: var(--bg-surface-alt); padding: 8px 6px;">الرصيد المتاح</th>
                                <th class="w-28 text-center font-bold" style="background-color: var(--bg-surface-alt); padding: 8px 6px;">تفصيل المخزن</th>
                                <th class="w-16 text-center font-bold" style="background-color: var(--bg-surface-alt); padding: 8px 6px;">حد الطلب</th>
                                <th class="w-24 text-center font-bold text-emerald-600 dark:text-emerald-400" style="background-color: rgba(16, 185, 129, 0.12); padding: 8px 6px;">الكمية للعميل</th>
                                <th class="w-24 text-center font-bold" style="background-color: var(--bg-surface-alt); padding: 8px 6px;">حالة الصنف</th>
                            </tr>
                        </thead>
                        <tbody id="inspectorTableBody" class="divide-y divide-[var(--border-color)]">
                            <!-- Injected dynamically via JS -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="erp-modal-footer px-4 py-3 border-t border-[var(--border-color)] bg-[var(--bg-surface-alt)] flex flex-wrap items-center justify-between gap-3 flex-shrink-0">
                <div class="text-xs text-[var(--text-muted)] flex items-center gap-1.5">
                    <span class="text-emerald-500 font-bold">💡</span>
                    <span>يمكنك تعديل كمية أي صنف مباشرة من خانة "الكمية للعميل"، ثم تطبيقها أو تصديرها.</span>
                </div>
                <div class="flex items-center gap-2.5">
                    <button type="button" id="applyAllocationToMainTableBtn" class="erp-btn erp-btn-secondary font-bold text-xs flex items-center gap-1.5">
                        <x-icon name="check-square" size="14" />
                        <span>تطبيق الكميات على قائمة التعبئة</span>
                    </button>

                    <button type="button" id="exportCustomerExcelDirectBtn" class="erp-btn erp-btn-success font-bold text-xs flex items-center gap-1.5 shadow-md" style="background-color: #10b981; color: #ffffff !important;">
                        <x-icon name="file-spreadsheet" size="15" />
                        <span>تصدير شيت إكسيل للعميل (مقسم شيتين)</span>
                    </button>
                </div>
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
(function () {
    function initPackingListPage() {
        const packingListBody = document.getElementById('packingListBody');
        if (!packingListBody) return;
        if (packingListBody.dataset.initialized === 'true') return;
        packingListBody.dataset.initialized = 'true';

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

    let currentSearchSeq = 0;

    function cleanSearchTerm(term) {
        if (!term) return '';
        let str = term.toString().trim();
        const arabicIndic = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩', '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        const standardDigits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        for (let i = 0; i < arabicIndic.length; i++) {
            str = str.replaceAll(arabicIndic[i], standardDigits[i]);
        }
        str = str.replace(/^\][A-Za-z0-9]{2}/, '');
        str = str.replace(/[\r\n\t\x00-\x1F\x7F-\x9F]/g, '');
        return str.trim();
    }

    // Fast-scan toggle text change
    fastScanModeCheckbox.addEventListener('change', function () {
        fastScanText.textContent = this.checked ? 'المسح المباشر السريع (مفعل)' : 'المسح المباشر السريع (معطل)';
        localStorage.setItem('packing_list_fast_scan', this.checked ? '1' : '0');
    });

    // Load saved fast-scan preference
    const savedFastScan = localStorage.getItem('packing_list_fast_scan');
    if (savedFastScan !== null) {
        fastScanModeCheckbox.checked = savedFastScan === '1';
        fastScanText.textContent = fastScanModeCheckbox.checked ? 'المسح المباشر السريع (مفعل)' : 'المسح المباشر السريع (معطل)';
    }

    // Global Barcode Scanner Listener & Keyboard Shortcuts
    let globalBarcodeBuffer = '';
    let globalBarcodeLastTime = 0;

    window.addEventListener('keydown', function (e) {
        if (e.key === 'F2') {
            e.preventDefault();
            productSearchInput.focus();
            productSearchInput.select();
            return;
        }

        const target = e.target;
        const isOtherInput = target && (target.tagName === 'INPUT' || target.tagName === 'TEXTAREA' || target.tagName === 'SELECT') && target !== productSearchInput;

        if (isOtherInput) {
            return;
        }

        const now = Date.now();
        const interval = now - globalBarcodeLastTime;
        globalBarcodeLastTime = now;

        if (e.key === 'Enter') {
            if (globalBarcodeBuffer.length >= 3 && interval < 70) {
                e.preventDefault();
                const scanned = cleanSearchTerm(globalBarcodeBuffer);
                globalBarcodeBuffer = '';
                productSearchInput.value = '';
                handleBarcodeOrInputCommit(scanned);
                return;
            }
            globalBarcodeBuffer = '';
        } else if (e.key.length === 1) {
            if (interval > 140) {
                globalBarcodeBuffer = '';
            }
            globalBarcodeBuffer += e.key;
        }
    });

    // Search Input Typing Listener
    productSearchInput.addEventListener('input', function () {
        const raw = this.value;
        const val = cleanSearchTerm(raw);
        clearSearchBtn.classList.toggle('hidden', raw.trim().length === 0);

        clearTimeout(searchDebounceTimer);
        currentSearchSeq++;

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
        }, 120);
    });

    // Search Input Keydown Listener (Barcode gun Enter / Tab & Keyboard navigation)
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

        if (e.key === 'Enter' || e.key === 'Tab') {
            e.preventDefault();
            const term = cleanSearchTerm(this.value);
            if (!term) return;

            clearTimeout(searchDebounceTimer);
            currentSearchSeq++;
            hideDropdown();

            await handleBarcodeOrInputCommit(term);
        } else if (e.key === 'Escape') {
            clearTimeout(searchDebounceTimer);
            currentSearchSeq++;
            hideDropdown();
        }
    });

    clearSearchBtn.addEventListener('click', function () {
        clearTimeout(searchDebounceTimer);
        currentSearchSeq++;
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
        if (currentSelectedProduct) {
            addProductDirectlyToTable(currentSelectedProduct);
        } else if (currentCandidateProduct) {
            addProductDirectlyToTable(currentCandidateProduct);
        } else {
            addItemFromForm();
        }
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
                    item.price_tier = mode;
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
        if (packingItems.length === 0 && !customerNameInput.value.trim() && !docNotesInput.value.trim()) return;
        if (confirm('هل أنت متأكد من تفريغ كافة أصناف وبيانات قائمة التعبئة؟')) {
            packingItems = [];
            currentListId = null;
            customerNameInput.value = '';
            docNotesInput.value = '';
            if (saveBtnText) saveBtnText.textContent = 'حفظ في السجل';
            localStorage.removeItem(STORAGE_KEY);
            pendingDraft = null;
            const draftAlert = document.getElementById('unconfirmedDraftAlert');
            if (draftAlert) draftAlert.style.display = 'none';
            renderTable();
            productSearchInput.focus();
            showToast('تم تفريغ القائمة بالكامل.', 'info');
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
        const seq = ++currentSearchSeq;
        const term = cleanSearchTerm(q);
        if (!term) {
            hideDropdown();
            return;
        }

        try {
            const resp = await fetch('{{ route('packing-list.search-products') }}?q=' + encodeURIComponent(term));
            const data = await resp.json();

            // Ignore obsolete responses or if input was cleared!
            if (seq !== currentSearchSeq || !productSearchInput.value.trim()) {
                hideDropdown();
                return;
            }

            if (data.success && data.products.length > 0) {
                currentSearchResults = data.products;
                activeDropdownIndex = 0;
                renderDropdown(data.products);

                // Auto-detect and show unit of measurement for closest matching product as user types
                const qNorm = term.toLowerCase();
                const matched = data.products.find(p => 
                    (p.barcode && cleanSearchTerm(p.barcode).toLowerCase() === qNorm) ||
                    (p.sku && cleanSearchTerm(p.sku).toLowerCase() === qNorm) ||
                    p.name.trim().toLowerCase() === qNorm
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

    async function handleBarcodeOrInputCommit(rawTerm) {
        const term = cleanSearchTerm(rawTerm);
        if (!term) return;

        clearTimeout(searchDebounceTimer);
        searchDebounceTimer = null;
        currentSearchSeq++;
        hideDropdown();

        const isFastScan = fastScanModeCheckbox.checked;
        const termLower = term.toLowerCase();

        // 1. If actively selected via Arrow keys
        let matched = null;
        if (activeDropdownIndex >= 0 && currentSearchResults[activeDropdownIndex]) {
            matched = currentSearchResults[activeDropdownIndex];
        }

        // 2. Exact match in cached current results
        if (!matched && currentSearchResults.length > 0) {
            matched = currentSearchResults.find(p => 
                (p.barcode && cleanSearchTerm(p.barcode).toLowerCase() === termLower) ||
                (p.sku && cleanSearchTerm(p.sku).toLowerCase() === termLower)
            );
        }

        // 3. Direct server lookup
        if (!matched) {
            try {
                const resp = await fetch('{{ route('packing-list.search-products') }}?q=' + encodeURIComponent(term));
                const data = await resp.json();
                if (data.success && data.products.length > 0) {
                    matched = data.products.find(p => 
                        (p.barcode && cleanSearchTerm(p.barcode).toLowerCase() === termLower) ||
                        (p.sku && cleanSearchTerm(p.sku).toLowerCase() === termLower)
                    ) || data.products[0];
                }
            } catch (err) {
                console.error('Scan lookup error:', err);
            }
        }

        if (matched) {
            if (isFastScan) {
                addProductDirectlyToTable(matched);
            } else {
                selectProduct(matched);
                hideDropdown();
                if (toggleShowQtyCheckbox.checked) {
                    itemQtyInput.focus();
                    itemQtyInput.select();
                } else {
                    addItemBtn.focus();
                }
            }
        } else {
            if (isFastScan) {
                showToast(`لم يتم العثور على صنف بالباركود: "${term}"`, 'error');
                productSearchInput.select();
            } else {
                addItemFromForm();
            }
        }
    }

    function addProductDirectlyToTable(p, quantityToAdd = 1) {
        clearTimeout(searchDebounceTimer);
        searchDebounceTimer = null;
        currentSearchSeq++;
        hideDropdown();

        let qty = 1;
        if (toggleShowQtyCheckbox.checked) {
            qty = Math.max(0.01, parseFloat(itemQtyInput.value) || quantityToAdd);
        }
        let packages = 0;
        if (toggleShowPackagesCheckbox.checked) {
            packages = parseFloat(itemPackagesInput.value) || 0;
            if (packages === 0 && p.package_factor && p.package_factor > 1) {
                packages = 1;
            }
        }

        const mode = defaultPriceModeSelect.value;
        const price = resolveItemPrice(p, mode);
        const unit = p.unit || itemUnitSelect.value || 'قطعة';
        const notes = itemNotesInput.value.trim();

        const pBarcode = cleanSearchTerm(p.barcode);
        const pSku = cleanSearchTerm(p.sku);

        let existingIndex = packingItems.findIndex(i => 
            (p.id && i.id === p.id) || 
            (pBarcode && cleanSearchTerm(i.barcode) === pBarcode) ||
            (pSku && cleanSearchTerm(i.sku) === pSku)
        );

        if (existingIndex >= 0) {
            packingItems[existingIndex].qty += qty;
            packingItems[existingIndex].packages_count += packages;
            if (price > 0 && packingItems[existingIndex].price === 0) {
                packingItems[existingIndex].price = price;
            }
            packingItems[existingIndex].total = packingItems[existingIndex].qty * packingItems[existingIndex].price;
            if (notes) {
                packingItems[existingIndex].notes = packingItems[existingIndex].notes ? (packingItems[existingIndex].notes + ' / ' + notes) : notes;
            }

            renderTable();
            saveState();
            highlightTableRow(existingIndex);

            if (toggleShowQtyCheckbox.checked) {
                showToast(`✓ تم زيادة كمية (${p.name}) إلى ${packingItems[existingIndex].qty}`, 'success');
            } else {
                showToast(`الصنف (${p.name}) موجود بالفعل في القائمة بالسطر #${existingIndex + 1}`, 'info');
            }
        } else {
            const wP = parseFloat(p.wholesale_price || 0) || price;
            const rP = parseFloat(p.retail_price || 0) || price;
            const dP = parseFloat(p.distributor_price || 0) || wP;
            const cP = parseFloat(p.cost_price || 0) || price;
            const newItem = {
                id: p.id,
                sku: p.sku || '',
                barcode: p.barcode || '',
                name: p.name,
                category_name: p.category_name || 'عام',
                unit: unit,
                qty: qty,
                packages_count: packages,
                price: price,
                price_tier: defaultPriceModeSelect.value,
                wholesale_price: wP,
                retail_price: rP,
                distributor_price: dP,
                cost_price: cP,
                total: qty * price,
                notes: notes,
            };
            packingItems.push(newItem);

            renderTable();
            saveState();

            const newIndex = packingItems.length - 1;
            highlightTableRow(newIndex);
            showToast(`✓ تم إدراج الصنف بنجاح: ${p.name}`, 'success');
        }

        // Clean inputs & refocus search box
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

        productSearchInput.focus();
    }

    function highlightTableRow(index) {
        setTimeout(() => {
            const row = packingListBody.querySelector(`tr[data-row-index="${index}"]`);
            if (row) {
                row.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                row.classList.add('pos-row-highlight');
                setTimeout(() => {
                    row.classList.remove('pos-row-highlight');
                }, 1400);
            }
        }, 50);
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
        if (!searchResultsDropdown) return;
        if (products.length === 0) {
            searchResultsDropdown.innerHTML = '<div class="p-3 text-center text-xs text-[var(--text-muted)]">لا توجد نتائج مطابقة</div>';
            searchResultsDropdown.classList.remove('hidden');
            searchResultsDropdown.style.display = 'block';
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
        searchResultsDropdown.style.display = 'block';

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
        if (!searchResultsDropdown) return;
        if (searchResultsDropdown.style.display === 'none' && searchResultsDropdown.classList.contains('hidden')) {
            return;
        }
        searchResultsDropdown.classList.add('hidden');
        searchResultsDropdown.style.display = 'none';
        searchResultsDropdown.innerHTML = '';
        currentSearchResults = [];
        activeDropdownIndex = -1;
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
        if (mode === 'none' || mode === 'zero') return 0;
        const curPrice = parseFloat(itemOrProd.price || 0);
        const w = parseFloat(itemOrProd.wholesale_price || 0);
        const r = parseFloat(itemOrProd.retail_price || 0);
        const d = parseFloat(itemOrProd.distributor_price || 0);
        const c = parseFloat(itemOrProd.cost_price || 0);

        const fallbackWholesale = w > 0 ? w : (curPrice > 0 ? curPrice : (r > 0 ? r : 0));
        const fallbackRetail = r > 0 ? r : (curPrice > 0 ? curPrice : fallbackWholesale);
        const fallbackDistributor = d > 0 ? d : fallbackWholesale;
        const fallbackCost = c > 0 ? c : fallbackWholesale;

        if (mode === 'retail') return fallbackRetail;
        if (mode === 'wholesale') return fallbackWholesale;
        if (mode === 'distributor') return fallbackDistributor;
        if (mode === 'cost') return fallbackCost;
        return fallbackWholesale;
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
            const wP = activeProduct ? (parseFloat(activeProduct.wholesale_price || 0) || price) : price;
            const rP = activeProduct ? (parseFloat(activeProduct.retail_price || 0) || price) : price;
            const dP = activeProduct ? (parseFloat(activeProduct.distributor_price || 0) || wP) : price;
            const cP = activeProduct ? (parseFloat(activeProduct.cost_price || 0) || price) : price;
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
                price_tier: defaultPriceModeSelect.value,
                wholesale_price: wP,
                retail_price: rP,
                distributor_price: dP,
                cost_price: cP,
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
            const effectiveTier = item.price_tier || defaultPriceModeSelect.value || 'wholesale';
            html += `
                <tr class="hover:bg-[var(--bg-hover)] transition-colors" data-row-index="${idx}">
                    <td class="text-center text-[var(--text-muted)] font-numeric font-semibold">${idx + 1}</td>
                    <td class="text-center font-mono font-semibold text-[var(--primary)] text-xs">${escapeHtml(item.sku || '—')}</td>
                    <td class="text-center font-mono text-[var(--text-muted)] text-xs">${escapeHtml(item.barcode || '—')}</td>
                    <td class="py-2">
                        <div class="font-bold text-sm text-[var(--text-main)] leading-snug">${escapeHtml(item.name)}</div>
                        <div class="text-[11px] text-[var(--text-muted)] font-semibold mt-0.5">${escapeHtml(item.category_name || 'عام')}</div>
                    </td>
                    <td class="text-center">
                        <span class="erp-badge erp-badge-neutral text-xs font-semibold">
                            ${escapeHtml(item.unit || 'قطعة')}
                        </span>
                    </td>
                    <td class="text-center col-qty" style="${showQty ? '' : 'display: none;'}">
                        <div class="pos-qty-stepper mx-auto" style="width: 105px; height: 32px;">
                            <button type="button" class="pos-qty-btn table-qty-minus text-sm font-bold" data-index="${idx}" tabindex="-1">−</button>
                            <input type="number" step="any" min="0.01" value="${item.qty}" class="pos-qty-input font-numeric font-bold text-xs table-qty-input" data-index="${idx}">
                            <button type="button" class="pos-qty-btn table-qty-plus text-sm font-bold" data-index="${idx}" tabindex="-1">+</button>
                        </div>
                    </td>
                    <td class="text-center col-packages" style="${showPackages ? '' : 'display: none;'}">
                        <input type="number" min="0" step="1" value="${item.packages_count}" class="erp-input font-numeric font-semibold text-center text-xs table-packages-input" style="width: 65px; height: 32px; margin: 0 auto;" data-index="${idx}">
                    </td>
                    <td class="text-center col-unit-price" style="${showPrice ? '' : 'display: none;'}">
                        <div style="display: inline-flex; align-items: center; border: 1px solid var(--border-color); border-radius: 6px; background-color: var(--bg-surface); overflow: hidden; height: 32px; width: 165px; margin: 0 auto; box-shadow: 0 1px 2px rgba(0,0,0,0.04);">
                            <input 
                                type="number" 
                                min="0" 
                                step="0.01" 
                                value="${item.price}" 
                                class="table-price-input font-numeric" 
                                style="width: 88px; height: 100%; border: none; background: transparent; text-align: center; font-size: 13px; font-weight: 700; color: var(--primary); outline: none; padding: 0 4px;" 
                                data-index="${idx}"
                                title="سعر الوحدة"
                            >
                            <span style="width: 1px; height: 18px; background-color: var(--border-color); flex-shrink: 0;"></span>
                            <select 
                                class="table-price-tier-select" 
                                style="width: 76px; height: 100%; border: none; background-color: var(--bg-surface-alt); text-align: center; font-size: 11px; font-weight: 600; color: var(--text-main); outline: none; cursor: pointer; padding: 0 4px; -webkit-appearance: menulist; appearance: menulist;" 
                                data-index="${idx}"
                                title="نوع السعر"
                            >
                                <option value="wholesale" ${effectiveTier === 'wholesale' ? 'selected' : ''}>جملة</option>
                                <option value="distributor" ${effectiveTier === 'distributor' ? 'selected' : ''}>موزع</option>
                                <option value="retail" ${effectiveTier === 'retail' ? 'selected' : ''}>قطاعي</option>
                                <option value="cost" ${effectiveTier === 'cost' ? 'selected' : ''}>تكلفة</option>
                                <option value="custom" ${effectiveTier === 'custom' ? 'selected' : ''}>يدوي</option>
                                <option value="zero" ${effectiveTier === 'zero' || item.price === 0 ? 'selected' : ''}>مجاني (0)</option>
                            </select>
                        </div>
                    </td>
                    <td class="text-center font-numeric font-extrabold text-emerald-600 dark:text-emerald-400 text-sm col-line-total" style="${showLineTotal ? '' : 'display: none;'}">
                        ${lineTotal > 0 ? lineTotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' <span class="text-[10px] font-normal text-[var(--text-muted)]">ج.م</span>' : '—'}
                    </td>
                    <td>
                        <input type="text" value="${escapeHtml(item.notes || '')}" placeholder="ملاحظة..." class="erp-input text-xs table-notes-input" style="height: 32px;" data-index="${idx}">
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
                packingItems[idx].price_tier = 'custom';
                packingItems[idx].total = packingItems[idx].qty * p;
                renderTable();
                saveState();
            });
        });

        packingListBody.querySelectorAll('.table-price-tier-select').forEach(sel => {
            sel.addEventListener('change', function () {
                const idx = parseInt(this.dataset.index);
                const mode = this.value;
                const newPrice = resolveItemPrice(packingItems[idx], mode);

                packingItems[idx].price_tier = mode;
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

    let saveStateDebounceTimer = null;
    function saveState() {
        clearTimeout(saveStateDebounceTimer);
        saveStateDebounceTimer = setTimeout(performSaveState, 200);
    }

    function performSaveState() {
        if (packingItems.length === 0 && !customerNameInput.value.trim() && !docNotesInput.value.trim()) {
            localStorage.removeItem(STORAGE_KEY);
            return;
        }
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
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
        } catch (e) {
            // QuotaExceeded or storage disabled
        }
    }

    let pendingDraft = null;

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
            }
            renderTable();
            return;
        }

        // Priority 2: Fallback to localStorage draft - with user-controlled restore banner
        try {
            const raw = localStorage.getItem(STORAGE_KEY);
            if (!raw) {
                renderTable();
                return;
            }
            const state = JSON.parse(raw);
            if (state && Array.isArray(state.items) && state.items.length > 0) {
                pendingDraft = state;
                const draftAlert = document.getElementById('unconfirmedDraftAlert');
                const draftDetails = document.getElementById('unconfirmedDraftDetails');
                if (draftAlert && draftDetails) {
                    draftDetails.textContent = `(${state.items.length} صنف${state.customer_name ? ' للعميل: ' + state.customer_name : ''})`;
                    draftAlert.style.display = 'flex';
                }
            }
        } catch (e) {
            console.error('Failed to parse draft state:', e);
            localStorage.removeItem(STORAGE_KEY);
        }
        renderTable();
    }

    // Draft restore & discard buttons
    const restoreDraftBtn = document.getElementById('restoreDraftBtn');
    const discardDraftBtn = document.getElementById('discardDraftBtn');
    if (restoreDraftBtn) {
        restoreDraftBtn.addEventListener('click', function () {
            if (!pendingDraft) return;
            if (pendingDraft.doc_number) docNumberInput.value = pendingDraft.doc_number;
            if (pendingDraft.doc_date) docDateInput.value = pendingDraft.doc_date;
            if (pendingDraft.customer_name) customerNameInput.value = pendingDraft.customer_name;
            if (pendingDraft.warehouse_name) warehouseSelect.value = pendingDraft.warehouse_name;
            if (pendingDraft.price_mode) defaultPriceModeSelect.value = pendingDraft.price_mode;
            if (pendingDraft.notes) docNotesInput.value = pendingDraft.notes;
            if (typeof pendingDraft.show_quantity === 'boolean') toggleShowQtyCheckbox.checked = pendingDraft.show_quantity;
            if (typeof pendingDraft.show_price === 'boolean') toggleShowPriceCheckbox.checked = pendingDraft.show_price;
            if (typeof pendingDraft.show_packages === 'boolean') toggleShowPackagesCheckbox.checked = pendingDraft.show_packages;

            packingItems = pendingDraft.items || [];
            applyColumnVisibility();
            renderTable();
            const draftAlert = document.getElementById('unconfirmedDraftAlert');
            if (draftAlert) draftAlert.style.display = 'none';
            showToast('تمت استعادة المسودة بنجاح.', 'success');
        });
    }

    if (discardDraftBtn) {
        discardDraftBtn.addEventListener('click', function () {
            localStorage.removeItem(STORAGE_KEY);
            pendingDraft = null;
            const draftAlert = document.getElementById('unconfirmedDraftAlert');
            if (draftAlert) draftAlert.style.display = 'none';
            showToast('تم تجاهل المسودة وبدء قائمة جديدة فارغة.', 'info');
        });
    }

    // Refresh Prices from System Database
    async function handleRefreshPrices() {
        if (packingItems.length === 0) {
            showToast('لا توجد أصناف في القائمة لتحديث أسعارها.', 'warning');
            return;
        }

        const btn1 = document.getElementById('refreshPricesBtn');
        const btn2 = document.getElementById('refreshPricesTopBtn');
        const orig1 = btn1 ? btn1.innerHTML : '';
        const orig2 = btn2 ? btn2.innerHTML : '';

        [btn1, btn2].forEach(b => {
            if (b) {
                b.disabled = true;
                b.innerHTML = `
                    <span class="inline-block animate-spin mr-1">⌛</span>
                    <span>جاري تحديث الأسعار...</span>
                `;
            }
        });

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const res = await fetch("{{ route('packing-list.refresh-prices') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken || ''
                },
                body: JSON.stringify({ items: packingItems })
            });

            const data = await res.json();
            if (data.success && Array.isArray(data.products) && data.products.length > 0) {
                const refreshedMap = new Map();
                data.products.forEach(p => {
                    if (p.product_id) refreshedMap.set('id_' + p.product_id, p);
                    if (p.barcode) refreshedMap.set('bc_' + p.barcode, p);
                    if (p.sku) refreshedMap.set('sku_' + p.sku, p);
                });

                let updatedCount = 0;
                const currentMode = defaultPriceModeSelect.value;

                packingItems.forEach(item => {
                    const match = (item.id && refreshedMap.get('id_' + item.id))
                        || (item.barcode && refreshedMap.get('bc_' + item.barcode))
                        || (item.sku && refreshedMap.get('sku_' + item.sku));

                    if (match) {
                        item.id = match.product_id;
                        item.name = match.product_name || item.name;
                        item.unit = match.unit || item.unit;
                        item.category_name = match.category_name || item.category_name;
                        item.wholesale_price = match.wholesale_price;
                        item.retail_price = match.retail_price;
                        item.distributor_price = match.distributor_price;
                        item.cost_price = match.cost_price;

                        const tier = item.price_tier || currentMode;
                        item.price = resolveItemPrice(item, tier);
                        item.total = item.qty * item.price;
                        updatedCount++;
                    }
                });

                renderTable();
                saveState();
                showToast(`تم تحديث أسعار ${updatedCount} صنف وفقاً لبيانات النظام. اضغط على 'حفظ وتعديل في السجل' لاعتماد التعديل.`, 'success');
            } else {
                showToast(data.message || 'لم يتم العثور على تحديثات جديدة للأسعار.', 'info');
            }
        } catch (err) {
            console.error('Error refreshing prices:', err);
            showToast('تعذر الاتصال بالخادم لتحديث الأسعار.', 'error');
        } finally {
            if (btn1) {
                btn1.disabled = false;
                btn1.innerHTML = orig1;
            }
            if (btn2) {
                btn2.disabled = false;
                btn2.innerHTML = orig2;
            }
        }
    }

    const refreshPricesBtn = document.getElementById('refreshPricesBtn');
    const refreshPricesTopBtn = document.getElementById('refreshPricesTopBtn');
    if (refreshPricesBtn) refreshPricesBtn.addEventListener('click', handleRefreshPrices);
    if (refreshPricesTopBtn) refreshPricesTopBtn.addEventListener('click', handleRefreshPrices);

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
                    localStorage.removeItem(STORAGE_KEY);
                    pendingDraft = null;

                    if (data.packing_number) {
                        docNumberInput.value = data.packing_number;
                    }
                    if (saveBtnText) {
                        saveBtnText.textContent = 'حفظ وتعديل في السجل';
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
                    showToast(data.message || 'تم حفظ قائمة التعبئة بنجاح في السجل. جاري الانتقال للسجل...', 'success');
                    setTimeout(() => {
                        window.location.href = "{{ route('packing-list.index') }}";
                    }, 1200);
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
                    <span>${currentListId ? 'حفظ وتعديل في السجل' : 'حفظ في السجل'}</span>
                `;
            }
        });
    }

    // ==========================================
    // Upload Customer Order File Handler
    // ==========================================
    const uploadCustomerOrderFileBtn = document.getElementById('uploadCustomerOrderFileBtn');
    const customerOrderFileInput = document.getElementById('customerOrderFileInput');

    if (uploadCustomerOrderFileBtn && customerOrderFileInput) {
        uploadCustomerOrderFileBtn.addEventListener('click', function () {
            customerOrderFileInput.click();
        });

        customerOrderFileInput.addEventListener('change', async function () {
            const file = this.files[0];
            if (!file) return;

            const origHtml = uploadCustomerOrderFileBtn.innerHTML;
            uploadCustomerOrderFileBtn.disabled = true;
            uploadCustomerOrderFileBtn.innerHTML = `
                <span class="inline-block animate-spin mr-1">⌛</span>
                <span>جارٍ فحص الشيت ومطابقة المخزن...</span>
            `;

            try {
                const formData = new FormData();
                formData.append('file', file);
                formData.append('warehouse_name', warehouseSelect.value);

                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const res = await fetch("{{ route('packing-list.import-customer-order') }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken || '',
                        'Accept': 'application/json'
                    },
                    body: formData
                });

                const data = await res.json();
                if (data.success && Array.isArray(data.items) && data.items.length > 0) {
                    // Populate packing list with items from the customer sheet
                    packingItems = data.items;
                    renderTable();
                    saveState();

                    // Suggest customer name from file if not entered
                    if (!customerNameInput.value.trim() && data.filename) {
                        const cleanName = data.filename.replace(/\.[^/.]+$/, '').replace(/[_\-]+/g, ' ').trim();
                        if (cleanName) customerNameInput.value = cleanName;
                    }

                    showToast(data.message, 'success');

                    // Immediately open the Stock Inspector modal with the 3 allocation options!
                    inspectorItems = JSON.parse(JSON.stringify(data.items));
                    const customer = customerNameInput.value.trim() || 'عميل عام';
                    inspectorCustomerNameBadge.textContent = 'العميل: ' + customer;
                    renderInspectorKPIs(data.summary);
                    renderInspectorTable();
                    stockInspectorModal.style.display = 'flex';
                    stockInspectorModal.classList.add('open');
                    stockInspectorModal.classList.remove('hidden');
                } else {
                    showToast(data.message || 'لم يتم العثور على أصناف مطابقة داخل الملف.', 'error');
                }
            } catch (err) {
                console.error('Error importing customer order file:', err);
                showToast('حدث خطأ أثناء رفع وتحليل ملف العميل.', 'error');
            } finally {
                uploadCustomerOrderFileBtn.disabled = false;
                uploadCustomerOrderFileBtn.innerHTML = origHtml;
                customerOrderFileInput.value = '';
            }
        });
    }

    // ==========================================
    // Stock Inspector & Customer Quantity Allocation
    // ==========================================
    const openStockInspectorBtn = document.getElementById('openStockInspectorBtn');
    const exportCustomerStockExcelBtn = document.getElementById('exportCustomerStockExcelBtn');
    const stockInspectorModal = document.getElementById('stockInspectorModal');
    const closeInspectorModalBtn = document.getElementById('closeInspectorModalBtn');
    const inspectorCustomerNameBadge = document.getElementById('inspectorCustomerNameBadge');
    const inspectorTotalItems = document.getElementById('inspectorTotalItems');
    const inspectorInStockCount = document.getElementById('inspectorInStockCount');
    const inspectorZeroStockCount = document.getElementById('inspectorZeroStockCount');
    const inspectorAvailabilityRate = document.getElementById('inspectorAvailabilityRate');
    const inspectorLoadingState = document.getElementById('inspectorLoadingState');
    const inspectorTableWrapper = document.getElementById('inspectorTableWrapper');
    const inspectorTableBody = document.getElementById('inspectorTableBody');
    const allocateAvailableBtn = document.getElementById('allocateAvailableBtn');
    const allocateMinAlertBtn = document.getElementById('allocateMinAlertBtn');
    const customAllocationQtyInput = document.getElementById('customAllocationQtyInput');
    const allocateCustomQtyBtn = document.getElementById('allocateCustomQtyBtn');
    const zeroShortagesBtn = document.getElementById('zeroShortagesBtn');
    const applyAllocationToMainTableBtn = document.getElementById('applyAllocationToMainTableBtn');
    const exportCustomerExcelDirectBtn = document.getElementById('exportCustomerExcelDirectBtn');

    let inspectorItems = [];

    function openStockInspector() {
        if (packingItems.length === 0) {
            showToast('يرجى إضافة أو رفع أصناف طلب العميل أولاً قبل الفحص.', 'warning');
            return;
        }

        const customer = customerNameInput.value.trim() || 'عميل عام';
        if (inspectorCustomerNameBadge) {
            inspectorCustomerNameBadge.textContent = 'العميل: ' + customer;
        }
        stockInspectorModal.style.display = 'flex';
        stockInspectorModal.classList.add('open');
        stockInspectorModal.classList.remove('hidden');

        fetchStockInspection();
    }

    function closeStockInspector() {
        stockInspectorModal.style.display = 'none';
        stockInspectorModal.classList.remove('open');
        stockInspectorModal.classList.add('hidden');
    }

    if (openStockInspectorBtn) {
        openStockInspectorBtn.addEventListener('click', openStockInspector);
    }

    if (closeInspectorModalBtn) {
        closeInspectorModalBtn.addEventListener('click', closeStockInspector);
    }

    stockInspectorModal?.addEventListener('click', function (e) {
        if (e.target === stockInspectorModal) closeStockInspector();
    });

    async function fetchStockInspection() {
        inspectorLoadingState.classList.remove('hidden');
        inspectorTableWrapper.classList.add('hidden');

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const res = await fetch("{{ route('packing-list.inspect-stock') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken || ''
                },
                body: JSON.stringify({
                    items: packingItems,
                    warehouse_name: warehouseSelect.value
                })
            });

            const data = await res.json();
            if (data.success) {
                inspectorItems = data.items || [];
                renderInspectorKPIs(data.summary);
                renderInspectorTable();
            } else {
                showToast(data.message || 'فشل فحص رصيد المخزن.', 'error');
            }
        } catch (err) {
            console.error('Error fetching stock inspection:', err);
            showToast('تعذر الاتصال بالخادم لمطابقة رصيد المخزن.', 'error');
        } finally {
            inspectorLoadingState.classList.add('hidden');
            inspectorTableWrapper.classList.remove('hidden');
        }
    }

    function renderInspectorKPIs(summary) {
        if (!summary) return;
        inspectorTotalItems.textContent = summary.total_items;
        inspectorInStockCount.textContent = summary.in_stock_count;
        inspectorZeroStockCount.textContent = summary.zero_stock_count;
        inspectorAvailabilityRate.textContent = summary.availability_rate + '%';
    }

    function renderInspectorTable() {
        if (!inspectorTableBody) return;
        inspectorTableBody.innerHTML = '';

        if (inspectorItems.length === 0) {
            inspectorTableBody.innerHTML = `
                <tr><td colspan="10" class="text-center py-8 text-[var(--text-muted)] font-semibold">لا توجد أصناف للفحص.</td></tr>
            `;
            return;
        }

        inspectorItems.forEach((item, idx) => {
            const tr = document.createElement('tr');
            tr.className = item.is_in_stock
                ? 'hover:bg-[var(--bg-hover)] transition-colors'
                : 'bg-rose-500/5 hover:bg-rose-500/10 transition-colors';

            const statusBadge = item.is_in_stock
                ? `<span class="erp-badge erp-badge-success text-[11px] font-bold">متوفر</span>`
                : `<span class="erp-badge erp-badge-danger text-[11px] font-bold">رصيد صفر</span>`;

            tr.innerHTML = `
                <td class="text-center font-numeric text-[var(--text-muted)] font-semibold text-xs py-2 px-1">${idx + 1}</td>
                <td class="text-center font-mono text-xs font-semibold text-[var(--primary)] py-2 px-1.5" dir="ltr">${escapeHtml(item.sku || '—')}</td>
                <td class="text-center font-mono text-xs text-[var(--text-muted)] py-2 px-1.5" dir="ltr">${escapeHtml(item.barcode || '—')}</td>
                <td class="py-2 px-2 text-right">
                    <div class="font-bold text-xs text-[var(--text-main)] truncate max-w-[280px]" title="${escapeHtml(item.name)}">${escapeHtml(item.name)}</div>
                    <div class="text-[10px] text-[var(--text-muted)] mt-0.5">${escapeHtml(item.category_name || 'عام')}</div>
                </td>
                <td class="text-center py-2 px-1">
                    <span class="erp-badge erp-badge-neutral text-[11px] font-semibold">${escapeHtml(item.unit || 'قطعة')}</span>
                </td>
                <td class="text-center font-numeric font-bold text-xs py-2 px-1.5 ${item.is_in_stock ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'}">
                    ${item.stock_in_unit}
                </td>
                <td class="text-center text-[11px] text-[var(--text-muted)] font-numeric py-2 px-1.5">
                    ${escapeHtml(item.stock_breakdown || '0')}
                </td>
                <td class="text-center font-numeric text-[var(--text-muted)] text-[11px] py-2 px-1">
                    ${item.min_stock_alert > 0 ? item.min_stock_alert : '—'}
                </td>
                <td class="text-center py-1.5 px-1 bg-emerald-500/5">
                    <input type="number" step="any" min="0" value="${item.qty}" data-inspector-idx="${idx}" class="inspector-qty-input erp-input font-numeric font-bold text-xs text-center border-emerald-500/50" style="width: 75px; height: 28px; margin: 0 auto; color: var(--primary); background: var(--bg-surface);">
                </td>
                <td class="text-center py-2 px-1.5">${statusBadge}</td>
            `;

            inspectorTableBody.appendChild(tr);
        });

        inspectorTableBody.querySelectorAll('.inspector-qty-input').forEach(input => {
            input.addEventListener('change', function () {
                const idx = parseInt(this.dataset.inspectorIdx);
                const val = parseFloat(this.value) || 0;
                if (inspectorItems[idx]) {
                    inspectorItems[idx].qty = Math.max(0, val);
                }
            });
            input.addEventListener('input', function () {
                const idx = parseInt(this.dataset.inspectorIdx);
                const val = parseFloat(this.value) || 0;
                if (inspectorItems[idx]) {
                    inspectorItems[idx].qty = Math.max(0, val);
                }
            });
        });
    }
                const idx = parseInt(this.dataset.inspectorIdx);
                const val = parseFloat(this.value) || 0;
                if (inspectorItems[idx]) {
                    inspectorItems[idx].qty = Math.max(0, val);
                }
            });
        });
    }

    // Option A: Allocate Full Available Stock
    allocateAvailableBtn?.addEventListener('click', function () {
        inspectorItems.forEach(item => {
            if (item.is_in_stock && item.stock_in_unit > 0) {
                item.qty = item.stock_in_unit;
            } else {
                item.qty = 0;
            }
        });
        renderInspectorTable();
        showToast('تم تخصيص الرصيد المتاح بالكامل لجميع الأصناف المتوفرة.', 'success');
    });

    // Option B: Allocate Minimum Stock Alert
    allocateMinAlertBtn?.addEventListener('click', function () {
        inspectorItems.forEach(item => {
            if (item.is_in_stock && item.stock_in_unit > 0) {
                const minTarget = item.min_stock_alert > 0 ? item.min_stock_alert : 1;
                item.qty = Math.min(minTarget, item.stock_in_unit);
            } else {
                item.qty = 0;
            }
        });
        renderInspectorTable();
        showToast('تم تخصيص كميات الحد الأدنى للأصناف المتاحة بالمخزن.', 'success');
    });

    // Option C: Allocate Custom Uniform Quantity
    allocateCustomQtyBtn?.addEventListener('click', function () {
        const val = parseFloat(customAllocationQtyInput.value) || 1;
        if (val <= 0) {
            alert('يرجى إدخال كمية صحيحة أكبر من الصفر.');
            return;
        }
        inspectorItems.forEach(item => {
            if (item.is_in_stock && item.stock_in_unit > 0) {
                item.qty = Math.min(val, item.stock_in_unit);
            } else {
                item.qty = 0;
            }
        });
        renderInspectorTable();
        showToast(`تم تطبيق كمية (${val}) على كافة الأصناف المتاحة بالمخزن.`, 'success');
    });

    // Reset shortages to 0
    zeroShortagesBtn?.addEventListener('click', function () {
        inspectorItems.forEach(item => {
            if (!item.is_in_stock || item.stock_in_unit <= 0) {
                item.qty = 0;
            }
        });
        renderInspectorTable();
        showToast('تم تصفير كميات الأصناف غير المتوفرة بالمخزن.', 'info');
    });

    // Apply allocated quantities to the main packing list table
    applyAllocationToMainTableBtn?.addEventListener('click', function () {
        if (inspectorItems.length === 0) return;

        // Map by SKU or Barcode or index to preserve references
        inspectorItems.forEach((inspItem, i) => {
            let target = null;
            if (inspItem.sku) {
                target = packingItems.find(it => it.sku === inspItem.sku);
            }
            if (!target && inspItem.barcode) {
                target = packingItems.find(it => it.barcode === inspItem.barcode);
            }
            if (!target && packingItems[i]) {
                target = packingItems[i];
            }

            if (target) {
                target.qty = inspItem.qty;
                target.total = target.qty * target.price;
                if (typeof inspItem.stock_in_unit !== 'undefined') {
                    target.stock_in_unit = inspItem.stock_in_unit;
                    target.stock_breakdown = inspItem.stock_breakdown;
                    target.is_in_stock = inspItem.is_in_stock;
                }
            }
        });

        renderTable();
        saveState();
        closeStockInspector();
        showToast('تم تطبيق الكميات المعتمدة بنجاح على جدول قائمة التعبئة!', 'success');
    });

    // Direct Customer Multi-sheet Excel Export Function
    function exportCustomerStockExcel(itemsToExport) {
        const items = (Array.isArray(itemsToExport) && itemsToExport.length > 0)
            ? itemsToExport
            : packingItems;

        if (!items || items.length === 0) {
            showToast('لا توجد أصناف لتصديرها للعميل.', 'warning');
            return;
        }

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route('packing-list.export-customer-stock-excel') }}';
        form.target = '_self';
        form.style.display = 'none';

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (csrfToken) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = '_token';
            input.value = csrfToken;
            form.appendChild(input);
        }

        const params = {
            doc_number: docNumberInput.value,
            doc_date: docDateInput.value,
            customer_name: customerNameInput.value,
            warehouse_name: warehouseSelect.value,
            notes: docNotesInput.value,
            items: items
        };

        for (const [key, val] of Object.entries(params)) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = key;
            input.value = typeof val === 'object' ? JSON.stringify(val) : val;
            form.appendChild(input);
        }

        document.body.appendChild(form);
        form.submit();
        setTimeout(() => form.remove(), 2500);
    }

    // Direct Customer Export from Modal
    exportCustomerExcelDirectBtn?.addEventListener('click', function () {
        exportCustomerStockExcel(inspectorItems.length > 0 ? inspectorItems : packingItems);
    });

    // Export from Top Dropdown
    exportCustomerStockExcelBtn?.addEventListener('click', function () {
        exportCustomerStockExcel(packingItems);
    });

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
        if (searchResultsDropdown && !searchResultsDropdown.contains(e.target) && e.target !== productSearchInput) {
            hideDropdown();
        }
    });

    window.addEventListener('scroll', function () {
        hideDropdown();
    }, { passive: true });

    // Ensure dropdown starts 100% hidden and empty on page load
    hideDropdown();
}

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initPackingListPage);
    } else {
        initPackingListPage();
    }
    document.addEventListener('turbo:load', initPackingListPage);
})();
</script>

<style>
#searchResultsDropdown.hidden,
.purchases-autocomplete-dropdown.hidden {
    display: none !important;
}

.purchases-autocomplete-dropdown {
    position: absolute !important;
    top: calc(100% + 4px) !important;
    right: 0 !important;
    left: 0 !important;
    width: 100% !important;
    min-width: 480px !important;
    max-height: 320px !important;
    overflow-y: auto !important;
    background: #ffffff !important;
    border: 1.5px solid #cbd5e1 !important;
    border-radius: 8px !important;
    box-shadow: 0 16px 36px rgba(0, 0, 0, 0.25) !important;
    z-index: 9999 !important;
}

.dark .purchases-autocomplete-dropdown {
    background: #1e293b !important;
    border-color: #334155 !important;
    box-shadow: 0 16px 36px rgba(0, 0, 0, 0.5) !important;
}

.purchases-suggestion-item {
    padding: 9px 12px !important;
    cursor: pointer !important;
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
    gap: 12px !important;
    border-bottom: 1px solid #f1f5f9 !important;
    color: #1e293b !important;
    background: #ffffff !important;
    transition: background-color 0.12s ease !important;
}

.dark .purchases-suggestion-item {
    border-bottom-color: #334155 !important;
    color: #f8fafc !important;
    background: #1e293b !important;
}

.purchases-suggestion-item:last-child {
    border-bottom: none !important;
}

.purchases-suggestion-item:hover,
.purchases-suggestion-item.active {
    background: #f8fafc !important;
}

.dark .purchases-suggestion-item:hover,
.dark .purchases-suggestion-item.active {
    background: #334155 !important;
}

.pos-row-highlight {
    animation: posRowFlash 1.4s ease-out forwards;
}
@keyframes posRowFlash {
    0% { background-color: rgba(16, 185, 129, 0.45) !important; }
    50% { background-color: rgba(16, 185, 129, 0.2) !important; }
    100% { background-color: transparent; }
}

/* Stock Inspector Modal Enhancements */
#stockInspectorModal.erp-modal-overlay {
    z-index: 1000 !important;
}

#stockInspectorModal .erp-modal-container {
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.45) !important;
}

#inspectorStockTable th {
    background-color: var(--bg-surface-alt) !important;
    color: var(--text-main) !important;
    font-weight: 700 !important;
    white-space: nowrap !important;
    position: sticky !important;
    top: 0 !important;
    z-index: 10 !important;
    border-bottom: 2px solid var(--border-color) !important;
}

#inspectorScrollArea {
    scrollbar-gutter: stable;
}
</style>
@endsection
