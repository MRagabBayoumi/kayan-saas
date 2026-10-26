@extends('layouts.app')

@section('control_panel')
    <x-control-panel title="تعديل فاتورة مبيعات: {{ $invoice->invoice_number }}" :breadcrumbs="['المبيعات والعملاء' => route('sales.index'), $invoice->invoice_number => route('sales.show', $invoice), 'تعديل الفاتورة' => '#']">
        <x-slot:actions>
            <button type="submit" form="salesForm" name="action" value="draft" class="erp-btn erp-btn-secondary">
                <x-icon name="file-text" size="16" />
                <span>حفظ التعديلات كمسودة</span>
            </button>
            <button type="submit" form="salesForm" name="action" value="post" class="erp-btn erp-btn-primary">
                <x-icon name="check-circle" size="16" />
                <span>تحديث وترحيل وصرف FIFO (F9)</span>
            </button>
            <a href="{{ route('sales.show', $invoice) }}" class="erp-btn erp-btn-ghost">
                <x-icon name="x" size="16" />
                <span>إلغاء وعودة</span>
            </a>
        </x-slot:actions>

        <x-slot:extra>
            <span class="erp-badge erp-badge-neutral font-numeric text-xs">
                فاتورة: {{ $invoice->invoice_number }}
            </span>
        </x-slot:extra>
    </x-control-panel>
@endsection

@section('content')
    <div data-page="sales-edit"
         data-products="{{ json_encode($productsJson ?? []) }}"
         data-units="{{ json_encode($unitsJson ?? []) }}"
         data-customers="{{ json_encode($customersJson ?? []) }}"
         data-can-edit-price="{{ $canEditPrice ? 'true' : 'false' }}">
        
        <!-- Validation Errors Alert -->
        @if(session('error'))
            <div class="erp-alert erp-alert-danger mb-4" role="alert">
                <x-icon name="alert-triangle" size="18" />
                <div>
                    <strong>تعذر إتمام العملية:</strong>
                    <div class="text-xs mt-1">{{ session('error') }}</div>
                </div>
            </div>
        @endif

        @if(session('message'))
            <div class="erp-alert erp-alert-success mb-4" role="alert">
                <x-icon name="check-circle" size="18" />
                <div>
                    <span>{{ session('message') }}</span>
                </div>
            </div>
        @endif

        @if($errors->any())
            <div class="erp-alert erp-alert-danger mb-4" role="alert">
                <x-icon name="alert-triangle" size="16" />
                <div>
                    <strong>يرجى تصحيح الأخطاء التالية قبل حفظ التعديلات:</strong>
                    <ul class="my-1.5 mx-5 text-xs">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <!-- Live Shift Status Banner -->
        @if(isset($activeShift) && $activeShift)
            <div class="pos-shift-banner mb-3">
                <div class="pos-shift-info">
                    <span class="shift-status-badge open">الوردية مفتوحة</span>
                    <strong class="font-numeric text-xs text-[var(--text-main)]">{{ $activeShift->shift_number }}</strong>
                    <span class="text-xs text-[var(--text-muted)]">
                        الكاشير: <strong class="text-[var(--text-main)]">{{ $activeShift->user?->name }}</strong> ·
                        المستودع: <strong class="text-[var(--text-main)]">{{ $activeShift->warehouse?->name }}</strong> ·
                        رصيد الدرج المتوقع: <strong class="text-emerald-500 font-numeric text-sm">{{ number_format($activeShift->expected_closing_balance, 2) }} ج.م</strong>
                    </span>
                </div>
                <div class="pos-shift-actions">
                    <a href="{{ route('shifts.show', $activeShift) }}" class="erp-btn erp-btn-ghost erp-btn-sm" title="سجل حركة الخزينة">
                        <x-icon name="list" size="14" />
                        <span>كشف الدرج</span>
                    </a>
                </div>
            </div>
        @endif

        <form id="salesForm" action="{{ route('sales.update', $invoice) }}" method="POST" class="flex flex-col gap-4">
            @csrf
            @method('PUT')

            <!-- 1. Top Meta Strip -->
            <div class="pos-meta-strip">
                <div class="pos-meta-grid">
                    <!-- Customer Select -->
                    <div class="erp-form-group mb-0">
                        <label for="customerSelect" class="erp-form-label text-xs font-semibold flex items-center justify-between">
                            <span class="flex items-center gap-1">
                                <x-icon name="user" size="13" class="text-[var(--primary)]" />
                                <span>العميل</span>
                            </span>
                            <div class="flex items-center gap-1.5">
                                <span id="customerTierHint" class="text-[10px] text-[var(--text-muted)] font-normal">
                                    فئة: {{ $invoice->price_tier === 'wholesale' ? 'جملة' : ($invoice->price_tier === 'distributor' ? 'موزع' : 'تجزئة') }}
                                </span>
                                <span id="customerBalanceHint" class="text-[10px] font-semibold"></span>
                            </div>
                        </label>
                        <div class="flex items-center gap-1">
                            <select id="customerSelect" name="customer_id" class="erp-input w-full text-xs font-semibold @error('customer_id') erp-input-error @enderror" required>
                                @can('create-customers-pos')
                                    <option value="__add_new__" class="font-bold text-[var(--primary)]">+ إضافة عميل جديد...</option>
                                @endcan
                                @foreach($customers as $c)
                                    <option value="{{ $c->id }}" 
                                            data-tier="{{ $c->pricing_tier ?: 'retail' }}"
                                            {{ (string)old('customer_id', $invoice->customer_id) === (string)$c->id ? 'selected' : '' }}>
                                        {{ $c->name }} {{ !empty($c->phone) ? "({$c->phone})" : '' }}
                                    </option>
                                @endforeach
                            </select>
                            @can('create-customers-pos')
                                <button type="button" id="quickAddCustomerBtn" class="erp-btn erp-btn-secondary px-2 flex-shrink-0" title="إضافة عميل جديد">
                                    <x-icon name="plus" size="14" />
                                </button>
                            @endcan
                        </div>
                    </div>

                    <!-- Warehouse (Source) -->
                    <div class="erp-form-group mb-0">
                        <label for="warehouseSelect" class="erp-form-label text-xs font-semibold flex items-center justify-between">
                            <span class="flex items-center gap-1">
                                <x-icon name="home" size="13" class="text-[var(--primary)]" />
                                <span>مستودع الصرف</span>
                            </span>
                        </label>
                        <select id="warehouseSelect" name="warehouse_id" class="erp-input w-full text-xs @error('warehouse_id') erp-input-error @enderror" required>
                            @foreach($warehouses as $wh)
                                <option value="{{ $wh->id }}" {{ (string)old('warehouse_id', $invoice->warehouse_id) === (string)$wh->id ? 'selected' : '' }}>
                                    {{ $wh->name }} {{ $wh->is_default ? '(الافتراضي)' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Price Tier Selection -->
                    <div class="erp-form-group mb-0">
                        <label for="priceTierSelect" class="erp-form-label text-xs font-semibold flex items-center gap-1">
                            <x-icon name="tag" size="13" class="text-[var(--primary)]" />
                            <span>فئة التسعير</span>
                        </label>
                        <select id="priceTierSelect" name="price_tier" class="erp-input w-full text-xs font-bold text-[var(--primary)] @error('price_tier') erp-input-error @enderror" required>
                            <option value="retail" {{ old('price_tier', $invoice->price_tier) === 'retail' ? 'selected' : '' }}>سعر قطاعي (Retail)</option>
                            <option value="wholesale" {{ old('price_tier', $invoice->price_tier) === 'wholesale' ? 'selected' : '' }}>سعر جملة (Wholesale)</option>
                            <option value="distributor" {{ old('price_tier', $invoice->price_tier) === 'distributor' ? 'selected' : '' }}>سعر موزع (Distributor)</option>
                        </select>
                    </div>

                    <!-- Invoice Date -->
                    <div class="erp-form-group mb-0">
                        <label for="invoiceDate" class="erp-form-label text-xs font-semibold flex items-center gap-1">
                            <x-icon name="calendar" size="13" />
                            <span>تاريخ الفاتورة</span>
                        </label>
                        <input 
                            type="date" 
                            id="invoiceDate" 
                            name="invoice_date" 
                            value="{{ old('invoice_date', $invoice->invoice_date ? \Carbon\Carbon::parse($invoice->invoice_date)->format('Y-m-d') : date('Y-m-d')) }}" 
                            class="erp-input w-full text-xs font-numeric @error('invoice_date') erp-input-error @enderror" 
                            required
                        >
                    </div>

                    <!-- Shipping / Delivery Cost -->
                    <div class="erp-form-group mb-0">
                        <label for="shippingCost" class="erp-form-label text-xs font-semibold flex items-center gap-1">
                            <x-icon name="truck" size="13" />
                            <span>الشحن (ج.م)</span>
                        </label>
                        <input 
                            type="number" 
                            step="0.01" 
                            min="0" 
                            id="shippingCost" 
                            name="shipping_cost" 
                            value="{{ old('shipping_cost', number_format((float)$invoice->shipping_cost, 2, '.', '')) }}" 
                            class="erp-input w-full font-numeric text-xs @error('shipping_cost') erp-input-error @enderror" 
                            placeholder="0.00"
                        >
                    </div>
                </div>
            </div>

            <!-- 2. POS Quick Entry Panel -->
            <div class="purchases-quick-entry">
                <div class="purchases-quick-entry-header">
                    <div class="purchases-quick-entry-title flex items-center gap-2">
                        <x-icon name="zap" size="18" class="text-amber-500" />
                        <span>شريط الإدخال السريع لنقاط البيع (مسدس الباركود والكيبورد)</span>
                    </div>

                    <div class="flex items-center gap-2">
                        <!-- Fast-Scan Mode Toggle Pill -->
                        <label for="fastScanToggle" id="fastScanBadge" class="pos-fast-scan-badge" title="عند التفعيل: مسح الباركود يدرج الصنف فوراً أو يزيد كميته تلقائياً">
                            <input type="checkbox" id="fastScanToggle" class="hidden">
                            <x-icon name="barcode" size="14" />
                            <span>المسح المباشر السريع (معطل)</span>
                        </label>

                        <div class="purchases-quick-entry-shortcuts hidden md:flex">
                            <span class="purchases-shortcut-pill">
                                <kbd>F2</kbd> للبحث
                            </span>
                            <span class="purchases-shortcut-pill">
                                <kbd>Tab</kbd> للتنقل
                            </span>
                            <span class="purchases-shortcut-pill">
                                <kbd>Enter</kbd> للإدراج
                            </span>
                            <span class="purchases-shortcut-pill">
                                <kbd>F9</kbd> للصرف
                            </span>
                        </div>
                    </div>
                </div>
                
                <div class="purchases-quick-entry-body">
                    <!-- Row 1: Search Options, Fast Scan & Global Invoice Discount -->
                    <div class="purchases-quick-row-1">
                        <!-- Search Mode Options -->
                        <div class="erp-form-group mb-0">
                            <label for="searchModeSelect" class="erp-form-label text-xs font-semibold flex items-center gap-1">
                                <x-icon name="search" size="13" class="text-[var(--primary)]" />
                                <span>طريقة البحث</span>
                            </label>
                            <select id="searchModeSelect" class="erp-input text-xs">
                                <option value="barcode">البحث بالباركود (مسدس الباركود)</option>
                                <option value="sku">كود الصنف (SKU)</option>
                                <option value="name">اسم الصنف (Name)</option>
                            </select>
                        </div>

                        <!-- Invoice Discount Type -->
                        <div class="erp-form-group mb-0">
                            <label for="invoiceDiscountType" class="erp-form-label text-xs font-semibold flex items-center gap-1">
                                <x-icon name="tag" size="13" class="text-amber-500" />
                                <span>نوع خصم الفاتورة</span>
                            </label>
                            <select id="invoiceDiscountType" name="discount_type" class="erp-input text-xs">
                                <option value="fixed" {{ old('discount_type', $invoice->discount_type) === 'percentage' ? '' : 'selected' }}>مبلغ نقدي ثابت (ج.م)</option>
                                <option value="percentage" {{ old('discount_type', $invoice->discount_type) === 'percentage' ? 'selected' : '' }}>نسبة مئوية (%)</option>
                            </select>
                        </div>

                        <!-- Invoice Discount Value -->
                        <div class="erp-form-group mb-0">
                            <label for="invoiceDiscountValue" class="erp-form-label text-xs font-semibold">
                                قيمة / نسبة الخصم
                            </label>
                            <div class="purchases-input-affix">
                                <input 
                                    type="number" 
                                    step="0.01" 
                                    min="0" 
                                    id="invoiceDiscountValue" 
                                    name="discount_value" 
                                    value="{{ old('discount_value', number_format((float)($invoice->discount_value ?? 0), 2, '.', '')) }}" 
                                    class="erp-input font-numeric text-xs" 
                                    placeholder="0.00"
                                >
                                <span id="discountUnitLabel" class="purchases-input-affix-label">ج.م</span>
                            </div>
                        </div>

                        <!-- Invoice Notes Field -->
                        <div class="erp-form-group mb-0">
                            <label for="salesNotes" class="erp-form-label text-xs font-semibold">ملاحظات الفاتورة</label>
                            <input 
                                type="text" 
                                id="salesNotes" 
                                name="notes" 
                                value="{{ old('notes', $invoice->notes) }}" 
                                class="erp-input text-xs" 
                                placeholder="أي ملاحظات خاصة بالتسليم..."
                            >
                        </div>
                    </div>

                    <!-- Row 2: Selected Item + Live Warehouse Stock + UOM + Qty + Price + Line Discount + Add Button -->
                    <div class="purchases-quick-row-2">
                        <!-- Item Search & Barcode Input -->
                        <div class="erp-form-group mb-0 relative">
                            <label for="quickProductInput" class="erp-form-label text-xs font-semibold flex items-center justify-between">
                                <span class="flex items-center gap-1">
                                    <x-icon name="barcode" size="13" class="text-[var(--primary)]" />
                                    <span id="quickSearchInputLabel">مربع البحث / قراءة الباركود</span>
                                </span>
                                <span id="quickStockBadge" class="hidden pos-stock-badge in-stock font-bold">
                                    رصيد الصنف بالمخزن: 0 قطعة
                                </span>
                            </label>
                            <div class="relative w-full">
                                <input 
                                    type="text" 
                                    id="quickProductInput" 
                                    class="erp-input erp-hero-search-input text-xs w-full font-semibold pl-9" 
                                    placeholder="اضرب الباركود بمسدس الباركود (F2)..." 
                                    autocomplete="off"
                                    autofocus
                                >
                                <button 
                                    type="button" 
                                    data-action="open-barcode-scanner" 
                                    data-target-input="quickProductInput" 
                                    title="تشغيل كاميرا الموبايل لمسح الباركود"
                                    class="absolute left-1.5 top-1/2 -translate-y-1/2 h-7 w-7 border-0 bg-[var(--primary-soft)] text-[var(--primary)] hover:bg-[var(--primary)] hover:text-white rounded inline-flex items-center justify-center cursor-pointer transition-colors"
                                >
                                    <x-icon name="camera" size="15" />
                                </button>
                            </div>

                            <div id="quickSearchResults" class="purchases-autocomplete-dropdown hidden"></div>
                            <input type="hidden" id="quickProductId">
                        </div>

                        <!-- Unit of Measurement -->
                        <div class="erp-form-group mb-0">
                            <div class="flex items-center justify-between mb-1">
                                <label for="quickUnitSelect" class="erp-form-label text-xs font-semibold mb-0">
                                    وحدة القياس
                                </label>
                                <!-- Fast Unit Switcher Pills (Piece / Dozen) -->
                                <div class="pos-unit-switcher" id="posQuickUnitSwitcher">
                                    <button type="button" id="quickUnitPieceBtn" class="pos-unit-pill active" data-unit="piece" title="بيع بالقطعة الفردية (اختصار: P أو ق)">
                                        <x-icon name="disc" size="11" />
                                        <span>قطعة (×1)</span>
                                    </button>
                                    <button type="button" id="quickUnitDozenBtn" class="pos-unit-pill" data-unit="dozen" title="بيع بالدستة أو العبوة كاملة (اختصار: * أو D أو د)">
                                        <x-icon name="layers" size="11" />
                                        <span id="quickUnitDozenLabel">دستة (* 12)</span>
                                    </button>
                                </div>
                            </div>
                            <select id="quickUnitSelect" class="erp-input text-xs">
                                @forelse($units as $u)
                                    <option value="{{ $u->id }}" data-name="{{ $u->name }}" data-factor="{{ $u->conversion_factor }}" {{ $u->is_default ? 'selected' : '' }}>
                                        {{ $u->name }} ({{ $u->code }})
                                    </option>
                                @empty
                                    <option value="1" data-name="قطعة" data-factor="1.0000" selected>قطعة (PCS)</option>
                                @endforelse
                            </select>
                        </div>

                        <!-- Quantity -->
                        <div class="erp-form-group mb-0">
                            <label for="quickQtyInput" class="erp-form-label text-xs font-semibold">
                                الكمية
                            </label>
                            <div class="pos-qty-stepper">
                                <button type="button" class="pos-qty-btn" id="quickQtyMinus" tabindex="-1">−</button>
                                <input 
                                    type="number" 
                                    step="0.01" 
                                    min="0.01" 
                                    id="quickQtyInput" 
                                    class="pos-qty-input font-numeric" 
                                    value="1"
                                >
                                <button type="button" class="pos-qty-btn" id="quickQtyPlus" tabindex="-1">+</button>
                            </div>
                        </div>

                        <!-- Selling Price -->
                        <div class="erp-form-group mb-0">
                            <label for="quickPriceInput" class="erp-form-label text-xs font-semibold flex items-center justify-between">
                                <span>سعر البيع (ج.م)</span>
                                @if(!$canEditPrice)
                                    <span class="text-[10px] text-[var(--text-muted)]" title="تعديل السعر يتطلب صلاحية">(افتراضي)</span>
                                @endif
                            </label>
                            <input 
                                type="number" 
                                step="0.01" 
                                min="0" 
                                id="quickPriceInput" 
                                class="erp-input font-numeric text-center text-xs font-bold text-[var(--primary)] @if(!$canEditPrice) bg-[var(--surface-muted)] cursor-not-allowed @endif" 
                                value="0.00"
                                @if(!$canEditPrice) readonly tabindex="-1" @endif
                            >
                        </div>

                        <!-- Line Discount -->
                        <div class="erp-form-group mb-0">
                            <label for="quickLineDiscountInput" class="erp-form-label text-xs font-semibold">
                                خصم البند (ج.م)
                            </label>
                            <input 
                                type="number" 
                                step="0.01" 
                                min="0" 
                                id="quickLineDiscountInput" 
                                class="erp-input font-numeric text-center text-xs" 
                                value="0.00"
                            >
                        </div>

                        <!-- Add to Table Button -->
                        <div class="erp-form-group mb-0">
                            <button type="button" id="quickAddBtn" class="erp-btn erp-btn-primary pos-commit-btn purchases-commit-btn">
                                <x-icon name="arrow-down-circle" size="16" />
                                <span>إدراج للصندوق (Enter)</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. Sales Items Table (Pre-populated for Editing) -->
            <div class="erp-card">
                <div class="erp-card-header flex items-center justify-between">
                    <div class="erp-card-title flex items-center gap-2">
                        <x-icon name="shopping-bag" size="18" />
                        <span>أصناف سلة المبيعات المسجلة</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-xs text-[var(--text-muted)] bg-[var(--surface-muted)] px-2.5 py-1 rounded border border-[var(--border-color)]">
                            * أحدث صنف يظهر في أول صف · تكرار المسح يزيد كمية الصنف تلقائياً
                        </span>
                        <button type="button" id="addSalesItemBtn" class="erp-btn erp-btn-secondary text-xs">
                            <x-icon name="plus" size="13" />
                            <span>إضافة صنف يدوياً</span>
                        </button>
                    </div>
                </div>

                <div class="erp-card-body p-0">
                    <div class="erp-table-responsive">
                        <table class="erp-table" id="salesItemsTable">
                            <thead>
                                <tr>
                                    <th class="w-10 text-center">#</th>
                                    <th class="min-w-[200px]">الصنف المختار</th>
                                    <th class="w-36 text-center">وحدة القياس</th>
                                    <th class="w-36 text-center">الكمية</th>
                                    <th class="w-32 text-center">سعر الوحدة</th>
                                    <th class="w-28 text-center">خصم البند</th>
                                    <th class="w-32 text-center">الإجمالي الصافي</th>
                                    <th class="w-40">ملاحظات</th>
                                    <th class="w-12 text-center">حذف</th>
                                </tr>
                            </thead>
                            <tbody id="salesItemsBody">
                                @php
                                    $itemsList = old('items');
                                    if ($itemsList === null) {
                                        $itemsList = $invoice->items->map(function ($it) {
                                            return [
                                                'product_id' => $it->product_id,
                                                'unit_id' => $it->unit_id,
                                                'conversion_factor' => (float)$it->conversion_factor,
                                                'quantity' => (float)$it->quantity,
                                                'unit_price' => (float)$it->unit_price,
                                                'line_discount' => (float)$it->line_discount,
                                                'notes' => $it->notes,
                                            ];
                                        })->toArray();
                                    }
                                @endphp
                                @if(!empty($itemsList) && is_array($itemsList))
                                    @foreach($itemsList as $idx => $it)
                                        @php
                                            $prod = $products->firstWhere('id', (int)($it['product_id'] ?? 0));
                                            $factor = (float)($it['conversion_factor'] ?? 1.0);
                                            $qty = (float)($it['quantity'] ?? 1.0);
                                            $price = (float)($it['unit_price'] ?? 0.0);
                                            $lineDisc = (float)($it['line_discount'] ?? 0.0);
                                            $lineSub = max(0, ($qty * $price) - $lineDisc);
                                        @endphp
                                        <tr class="sales-item-row" data-row-idx="{{ $idx }}" data-product-id="{{ $it['product_id'] ?? '' }}">
                                            <td class="text-center font-numeric row-counter">{{ $loop->iteration }}</td>
                                            <td>
                                                <input type="hidden" name="items[{{ $idx }}][product_id]" value="{{ $it['product_id'] ?? '' }}" class="row-product-id">
                                                <span class="font-bold text-[var(--text-main)]">{{ $prod?->name ?? 'صنف غير معروف' }}</span>
                                                <div class="text-[11px] text-[var(--text-muted)] font-numeric">كود: {{ $prod?->sku ?? '—' }} @if($prod?->barcode) · باركود: {{ $prod->barcode }} @endif</div>
                                            </td>
                                            <td>
                                                <select name="items[{{ $idx }}][unit_id]" class="erp-input text-xs row-unit-select">
                                                    @foreach($units as $u)
                                                        <option value="{{ $u->id }}" data-factor="{{ $u->conversion_factor }}" {{ (string)($it['unit_id'] ?? '') === (string)$u->id ? 'selected' : '' }}>
                                                            {{ $u->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <input type="hidden" name="items[{{ $idx }}][conversion_factor]" value="{{ $factor }}" class="row-conversion-factor">
                                                <div class="row-factor-badge text-[11px] text-[var(--primary)] font-bold font-numeric mt-1 {{ $factor > 1 ? '' : 'hidden' }}">
                                                    (×{{ (float)$factor }} قطعة)
                                                </div>
                                            </td>
                                            <td>
                                                <div class="pos-qty-stepper mx-auto">
                                                    <button type="button" class="pos-qty-btn row-qty-minus" tabindex="-1">−</button>
                                                    <input type="number" step="0.01" min="0.01" name="items[{{ $idx }}][quantity]" value="{{ $qty }}" class="pos-qty-input font-numeric row-qty">
                                                    <button type="button" class="pos-qty-btn row-qty-plus" tabindex="-1">+</button>
                                                </div>
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" min="0" name="items[{{ $idx }}][unit_price]" value="{{ $price }}" class="erp-input font-numeric text-center text-xs row-price @if(!$canEditPrice) bg-[var(--surface-muted)] cursor-not-allowed @endif" @if(!$canEditPrice) readonly tabindex="-1" @endif>
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" min="0" name="items[{{ $idx }}][line_discount]" value="{{ $lineDisc }}" class="erp-input font-numeric text-center text-xs row-discount">
                                            </td>
                                            <td class="text-center font-numeric font-bold text-xs row-subtotal-text">
                                                {{ number_format($lineSub, 2) }}
                                            </td>
                                            <td>
                                                <input type="text" name="items[{{ $idx }}][notes]" value="{{ $it['notes'] ?? '' }}" class="erp-input text-xs" placeholder="ملاحظة...">
                                            </td>
                                            <td class="text-center">
                                                <button type="button" class="erp-btn erp-btn-ghost erp-btn-sm text-[var(--danger)] remove-row-btn" title="حذف البند">
                                                    <x-icon name="trash-2" size="14" />
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- 4. Footer Invoice Summary Cards -->
            <div class="purchases-summary-grid">
                <!-- Card 1: Count of Distinct Products -->
                <div class="purchases-summary-card">
                    <span class="purchases-summary-label">عدد الأصناف</span>
                    <span class="purchases-summary-value">
                        <span id="summaryItemsCount">0</span>
                        <span class="purchases-summary-unit">أصناف</span>
                    </span>
                </div>

                <!-- Card 2: Total Units Sold -->
                <div class="purchases-summary-card">
                    <span class="purchases-summary-label">إجمالي الكميات</span>
                    <span class="purchases-summary-value">
                        <span id="summaryTotalUnits">0</span>
                        <span class="purchases-summary-unit">وحدة</span>
                    </span>
                </div>

                <!-- Card 3: Items Subtotal (Before Discounts) -->
                <div class="purchases-summary-card">
                    <span class="purchases-summary-label">المجموع الفرعي</span>
                    <span class="purchases-summary-value">
                        <span id="summaryItemsSubtotal">0.00 ج.م</span>
                    </span>
                </div>

                <!-- Card 4: Total Discounts -->
                <div class="purchases-summary-card discount">
                    <span class="purchases-summary-label">إجمالي الخصم</span>
                    <span class="purchases-summary-value">
                        <span id="summaryDiscountDisplay">0.00 ج.م</span>
                    </span>
                    <span class="purchases-summary-unit" id="summaryDiscountHint">لا يوجد خصم مطبق</span>
                </div>

                <!-- Card 5: Grand Total (Net Payable) -->
                <div class="purchases-summary-card grand-total">
                    <span class="purchases-summary-label">صافي إجمالي الفاتورة</span>
                    <span class="purchases-summary-value">
                        <span id="summaryGrandTotal">0.00 ج.م</span>
                    </span>
                </div>
            </div>

            <!-- 5. Payment & Settlement Configuration (Cash vs Credit vs Partial) -->
            <div class="pos-payment-card erp-card">
                <div class="erp-card-header flex flex-wrap items-center justify-between gap-2 py-2.5">
                    <div class="erp-card-title flex items-center gap-2">
                        <x-icon name="dollar-sign" size="18" class="text-[var(--primary)]" />
                        <span>طريقة السداد والتحصيل المالي (نظام الآجل والتحصيل)</span>
                    </div>
                    <div id="customerCreditStatusBadge" class="hidden text-xs px-2.5 py-1 rounded font-semibold border"></div>
                </div>
                <div class="erp-card-body p-4 flex flex-col gap-4">
                    <!-- Row 1: Payment Method Toggle Bar -->
                    <div class="flex flex-wrap items-center justify-between gap-3 pb-3 border-b border-[var(--border-color)]">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold text-[var(--text-main)] flex items-center gap-1.5">
                                <x-icon name="credit-card" size="15" class="text-[var(--primary)]" />
                                <span>اختر طريقة سداد الفاتورة:</span>
                            </span>
                        </div>
                        <div class="payment-method-toggle-group" id="posPaymentToggleGroup">
                            <label class="payment-toggle-btn {{ old('payment_type', $invoice->payment_type ?? 'cash') === 'cash' ? 'active' : '' }}" data-type="cash">
                                <input type="radio" name="payment_type" value="cash" {{ old('payment_type', $invoice->payment_type ?? 'cash') === 'cash' ? 'checked' : '' }} class="sr-only">
                                <x-icon name="banknote" size="14" />
                                <span>نقدي (كاش بالكامل)</span>
                            </label>
                            <label class="payment-toggle-btn {{ old('payment_type', $invoice->payment_type ?? 'cash') === 'credit' ? 'active' : '' }}" data-type="credit">
                                <input type="radio" name="payment_type" value="credit" {{ old('payment_type', $invoice->payment_type ?? 'cash') === 'credit' ? 'checked' : '' }} class="sr-only">
                                <x-icon name="clock" size="14" />
                                <span>آجل بالكامل (مديونية)</span>
                            </label>
                            <label class="payment-toggle-btn {{ old('payment_type', $invoice->payment_type ?? 'cash') === 'partial' ? 'active' : '' }}" data-type="partial">
                                <input type="radio" name="payment_type" value="partial" {{ old('payment_type', $invoice->payment_type ?? 'cash') === 'partial' ? 'checked' : '' }} class="sr-only">
                                <x-icon name="pie-chart" size="14" />
                                <span>دفع جزئي (مقدم + آجل)</span>
                            </label>
                        </div>
                    </div>

                    <!-- Row 2: 3 Balanced Financial Fields -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- Paid Cash Amount -->
                        <div class="erp-form-group mb-0">
                            <label for="posPaidAmountInput" class="erp-form-label text-xs font-semibold flex items-center gap-1.5">
                                <x-icon name="check-circle" size="14" class="text-[var(--success)]" />
                                <span>المدفوع نقداً بالدرج (ج.م)</span>
                            </label>
                            <div class="relative">
                                <input 
                                    type="number" 
                                    step="0.01" 
                                    min="0" 
                                    id="posPaidAmountInput" 
                                    name="paid_amount" 
                                    value="{{ old('paid_amount', number_format((float)($invoice->paid_amount ?? 0), 2, '.', '')) }}" 
                                    class="erp-input font-numeric font-bold text-center text-sm text-[var(--success)] bg-[var(--surface-muted)]"
                                    readonly
                                >
                            </div>
                            <span id="posPaidHint" class="erp-form-hint text-[11px] block text-[var(--text-muted)] mt-1">كامل الفاتورة مسدد نقداً بالدرج</span>
                        </div>

                        <!-- Remaining Deferred Amount -->
                        <div class="erp-form-group mb-0">
                            <label for="posRemainingAmountInput" class="erp-form-label text-xs font-semibold flex items-center gap-1.5">
                                <x-icon name="alert-circle" size="14" class="text-[var(--danger)]" />
                                <span>المتبقي الآجل على العميل (ج.م)</span>
                            </label>
                            <div class="relative">
                                <input 
                                    type="number" 
                                    step="0.01" 
                                    id="posRemainingAmountInput" 
                                    name="remaining_amount" 
                                    value="{{ old('remaining_amount', number_format((float)($invoice->remaining_amount ?? 0), 2, '.', '')) }}" 
                                    class="erp-input font-numeric font-bold text-center text-sm text-[var(--danger)] bg-[var(--surface-muted)]" 
                                    readonly
                                    tabindex="-1"
                                >
                            </div>
                            <span id="posRemainingHint" class="erp-form-hint text-[11px] block text-[var(--text-muted)] mt-1">لا يوجد دين آجل على العميل</span>
                        </div>

                        <!-- Due Date (for Credit & Partial) -->
                        <div class="erp-form-group mb-0" id="posDueDateGroup">
                            <label for="posDueDateInput" class="erp-form-label text-xs font-semibold flex items-center gap-1.5">
                                <x-icon name="calendar" size="14" class="text-amber-500" />
                                <span>تاريخ استحقاق الآجل</span>
                            </label>
                            <div class="relative">
                                <input 
                                    type="date" 
                                    id="posDueDateInput" 
                                    name="due_date" 
                                    value="{{ old('due_date', $invoice->due_date?->format('Y-m-d') ?? '') }}" 
                                    class="erp-input text-xs font-numeric {{ old('payment_type', $invoice->payment_type ?? 'cash') === 'cash' ? 'bg-[var(--surface-muted)] cursor-not-allowed opacity-60' : '' }}"
                                    {{ old('payment_type', $invoice->payment_type ?? 'cash') === 'cash' ? 'disabled' : '' }}
                                >
                            </div>
                            <span id="posDueDateHint" class="erp-form-hint text-[11px] block text-[var(--text-muted)] mt-1">
                                {{ old('payment_type', $invoice->payment_type ?? 'cash') === 'cash' ? 'غير مطلوب في البيع النقدي' : 'حدد تاريخ المطالبة بسداد المتبقي' }}
                            </span>
                        </div>
                    </div>

                    <!-- Live Credit Limit & Warning Banner -->
                    <div id="creditLimitAlertBox" class="hidden p-3 rounded text-xs border flex items-center gap-2"></div>
                </div>
            </div>

            <!-- 6. Big Touch/POS Cashier Action Bar -->
            <div class="pos-cashier-actions">
                <div class="flex items-center gap-2">
                    <button type="button" id="clearCartBtn" class="erp-btn erp-btn-ghost text-[var(--danger)] text-xs">
                        <x-icon name="trash" size="14" />
                        <span>تفريغ السلة بالكامل</span>
                    </button>
                    <span class="text-xs text-[var(--text-muted)] hidden sm:inline">
                        اختصار لوحة المفاتيح: <kbd>F9</kbd> لتحديث وصرف الفاتورة مباشرة
                    </span>
                </div>

                <div class="flex items-center gap-3">
                    <button type="submit" name="action" value="draft" class="erp-btn erp-btn-secondary">
                        <x-icon name="file-text" size="16" />
                        <span>حفظ التعديلات كمسودة</span>
                    </button>

                    <button type="submit" id="posSubmitBtn" name="action" value="post" class="erp-btn erp-btn-primary px-6 py-2.5 font-bold text-sm">
                        <x-icon name="check-circle" size="18" />
                        <span>تحديث وصرف الفاتورة (F9)</span>
                    </button>
                </div>
            </div>
        </form>

        <!-- Quick Add Customer Modal Dialog -->
        <div id="quickCustomerModal" class="erp-modal-overlay" role="dialog" aria-modal="true" aria-labelledby="quickCustomerModalTitle">
            <div class="erp-modal-container">
                <div class="erp-modal-header">
                    <div class="erp-modal-title" id="quickCustomerModalTitle">
                        <x-icon name="user-plus" size="18" class="text-[var(--primary)]" />
                        <span>إضافة عميل جديد سريع</span>
                    </div>
                    <button type="button" id="closeQuickCustomerModalBtn" class="erp-modal-close" aria-label="إغلاق">
                        <x-icon name="x" size="16" />
                    </button>
                </div>

                <form id="quickCustomerForm" method="POST" action="{{ route('customers.store') }}">
                    @csrf
                    <div class="erp-modal-body">
                        <div id="quickCustomerAlert" class="erp-alert erp-alert-danger hidden mb-3">
                            <x-icon name="alert-triangle" size="16" />
                            <span id="quickCustomerAlertMsg"></span>
                        </div>

                        <div class="erp-form-group mb-3">
                            <label for="quickCustName" class="erp-form-label text-xs">
                                اسم العميل <span class="text-[var(--danger)]">*</span>
                            </label>
                            <input type="text" id="quickCustName" name="name" class="erp-input text-xs" placeholder="مثال: محمد محمود" required>
                        </div>

                        <div class="erp-form-group mb-3">
                            <label for="quickCustPhone" class="erp-form-label text-xs">
                                رقم الهاتف <span class="text-[var(--danger)]">*</span>
                            </label>
                            <input type="text" id="quickCustPhone" name="phone" class="erp-input text-xs font-numeric" placeholder="مثال: 01012345678" required>
                        </div>

                        <div class="erp-form-row-2col mb-3">
                            <div class="erp-form-group mb-0">
                                <label for="quickCustType" class="erp-form-label text-xs">نوع العميل</label>
                                <select id="quickCustType" name="customer_type" class="erp-input text-xs">
                                    <option value="retail" selected>عميل تجزئة</option>
                                    <option value="wholesale">تاجر جملة</option>
                                    <option value="distributor">موزع معتمد</option>
                                </select>
                            </div>

                            <div class="erp-form-group mb-0">
                                <label for="quickCustTier" class="erp-form-label text-xs">فئة التسعير</label>
                                <select id="quickCustTier" name="pricing_tier" class="erp-input text-xs">
                                    <option value="retail" selected>سعر التجزئة (قطاعي)</option>
                                    <option value="wholesale">سعر الجملة</option>
                                    <option value="distributor">سعر الموزع (موزع معتمد)</option>
                                </select>
                            </div>
                        </div>

                        <div class="erp-form-group mb-0">
                            <label for="quickCustCity" class="erp-form-label text-xs">المدينة / المنطقة (اختياري)</label>
                            <input type="text" id="quickCustCity" name="city" class="erp-input text-xs" placeholder="مثال: القاهرة / العتبة">
                        </div>
                    </div>

                    <div class="erp-modal-footer">
                        <button type="button" id="cancelQuickCustomerBtn" class="erp-btn erp-btn-secondary text-xs">إلغاء</button>
                        <button type="submit" id="saveQuickCustomerBtn" class="erp-btn erp-btn-primary text-xs">
                            <x-icon name="check" size="14" />
                            <span>حفظ وتحديد العميل</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Barcode Camera Scanner Modal -->
    <x-barcode-scanner-modal target-input-id="quickProductInput" />
@endsection
