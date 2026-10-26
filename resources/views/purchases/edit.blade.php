@extends('layouts.app')

@section('control_panel')
    <x-control-panel title="تعديل مسودة فاتورة شراء: {{ $invoice->invoice_number }}" :breadcrumbs="['المشتريات والموردين' => route('purchases.index'), $invoice->invoice_number => route('purchases.show', $invoice), 'تعديل' => '#']">
        <x-slot:actions>
            <button type="submit" form="purchaseForm" name="action" value="draft" class="erp-btn erp-btn-secondary">
                <x-icon name="file-text" size="16" />
                <span>حفظ التعديلات كمسودة</span>
            </button>
            <button type="submit" form="purchaseForm" name="action" value="review_pricing" class="erp-btn erp-btn-warning">
                <x-icon name="tag" size="16" />
                <span>حفظ ومراجعة الأسعار قبل الترحيل</span>
            </button>
            <button type="submit" form="purchaseForm" name="action" value="post" class="erp-btn erp-btn-primary">
                <x-icon name="check-circle" size="16" />
                <span>حفظ وترحيل فوري للمخازن</span>
            </button>
            <a href="{{ route('purchases.show', $invoice) }}" class="erp-btn erp-btn-ghost">
                <x-icon name="x" size="16" />
                <span>إلغاء وعودة</span>
            </a>
        </x-slot:actions>
    </x-control-panel>
@endsection

@section('content')
    <div data-page="purchases-create"
         data-products="{{ json_encode($productsJson ?? []) }}"
         data-units="{{ json_encode($unitsJson ?? []) }}">
        <!-- Validation Errors Alert -->
        @if($errors->any())
            <div class="erp-alert erp-alert-danger mb-5" role="alert">
                <x-icon name="alert-triangle" size="16" />
                <div>
                    <strong>يرجى تصحيح الأخطاء التالية قبل حفظ الفاتورة:</strong>
                    <ul class="my-1.5 mx-5 text-xs">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <form id="purchaseForm" action="{{ route('purchases.update', $invoice) }}" method="POST" class="flex flex-col gap-5">
            @csrf
            @method('PUT')

            <!-- Header Card: Invoice Meta -->
            <div class="erp-card">
                <div class="erp-card-header">
                    <div class="erp-card-title">
                        <x-icon name="file-text" size="18" />
                        <span>بيانات فاتورة الشراء الأساسية</span>
                    </div>
                    <div class="font-numeric text-xs font-mono text-[var(--text-muted)] bg-[var(--surface-muted)] px-2.5 py-1 rounded border border-[var(--border-color)]">
                        رقم الفاتورة: <strong class="text-[var(--text-main)]">{{ $invoice->invoice_number }}</strong>
                    </div>
                </div>

                <div class="erp-card-body p-4">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <!-- Supplier -->
                        <div class="erp-form-group">
                            <label for="supplierId" class="erp-form-label">
                                المورد <span class="text-[var(--danger)]">*</span>
                            </label>
                            <div class="flex items-center gap-1.5">
                                <select name="supplier_id" id="supplierId" class="erp-input w-full @error('supplier_id') erp-input-error @enderror" required>
                                    <option value="__add_new__" class="font-bold text-[var(--primary)]">+ إضافة مورد جديد...</option>
                                    <option value="">-- اختر المورد المطلوب --</option>
                                    @foreach($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}" data-type="{{ $supplier->supplier_type }}" data-currency="{{ $supplier->currency }}" {{ (string)old('supplier_id', $invoice->supplier_id) === (string)$supplier->id ? 'selected' : '' }}>
                                            {{ $supplier->name }} (رصيد: {{ number_format((float)$supplier->current_balance, 2) }} ج.م)
                                        </option>
                                    @endforeach
                                </select>
                                <button type="button" id="quickAddSupplierBtn" class="erp-btn erp-btn-secondary px-2.5 flex-shrink-0" title="إضافة مورد جديد">
                                    <x-icon name="plus" size="14" />
                                </button>
                            </div>
                        </div>

                        <!-- Warehouse -->
                        <div class="erp-form-group">
                            <label for="warehouseId" class="erp-form-label">
                                مستودع الاستلام <span class="text-[var(--danger)]">*</span>
                            </label>
                            <select name="warehouse_id" id="warehouseId" class="erp-input @error('warehouse_id') erp-input-error @enderror" required>
                                <option value="">-- اختر مستودع التخزين --</option>
                                @foreach($warehouses as $wh)
                                    <option value="{{ $wh->id }}" {{ (string)old('warehouse_id', $invoice->warehouse_id) === (string)$wh->id ? 'selected' : '' }}>
                                        {{ $wh->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Invoice Date -->
                        <div class="erp-form-group">
                            <label for="invoiceDate" class="erp-form-label">
                                تاريخ الفاتورة <span class="text-[var(--danger)]">*</span>
                            </label>
                            <input 
                                type="date" 
                                id="invoiceDate" 
                                name="invoice_date" 
                                value="{{ old('invoice_date', $invoice->invoice_date?->format('Y-m-d') ?? date('Y-m-d')) }}" 
                                class="erp-input font-numeric @error('invoice_date') erp-input-error @enderror" 
                                required
                            >
                        </div>

                        <!-- Shipping Cost -->
                        <div class="erp-form-group">
                            <label for="shippingCost" class="erp-form-label">
                                مصاريف الشحن / التخليص
                            </label>
                            <input 
                                type="number" 
                                step="0.01" 
                                min="0" 
                                id="shippingCost" 
                                name="shipping_cost" 
                                value="{{ old('shipping_cost', (float)$invoice->shipping_cost) }}" 
                                class="erp-input font-numeric" 
                                placeholder="0.00"
                            >
                        </div>
                    </div>

                    <!-- Discount & Notes Row -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-3 pt-3 border-t border-[var(--border-color)]">
                        <div class="erp-form-group mb-0">
                            <label for="invoiceDiscountType" class="erp-form-label text-xs">نوع الخصم</label>
                            <select id="invoiceDiscountType" name="discount_type" class="erp-input text-xs">
                                <option value="fixed" {{ old('discount_type') === 'fixed' ? 'selected' : '' }}>مبلغ نقدي ثابت (ج.م)</option>
                                <option value="percentage" {{ old('discount_type') === 'percentage' ? 'selected' : '' }}>نسبة مئوية (%)</option>
                            </select>
                        </div>

                        <div class="erp-form-group mb-0">
                            <label for="invoiceDiscountValue" class="erp-form-label text-xs flex justify-between">
                                <span>قيمة الخصم</span>
                                <span id="discountUnitLabel" class="text-[var(--primary)] font-bold">ج.م</span>
                            </label>
                            <input 
                                type="number" 
                                step="0.01" 
                                min="0" 
                                id="invoiceDiscountValue" 
                                name="discount_value" 
                                value="{{ old('discount_value', 0) }}" 
                                class="erp-input text-xs font-numeric" 
                                placeholder="0.00"
                            >
                        </div>

                        <div class="erp-form-group mb-0 md:col-span-2">
                            <label for="invoiceNotes" class="erp-form-label text-xs">ملاحظات الفاتورة</label>
                            <input 
                                type="text" 
                                id="invoiceNotes" 
                                name="notes" 
                                value="{{ old('notes', $invoice->notes) }}" 
                                class="erp-input text-xs" 
                                placeholder="رقم بوليصة الشحن، اسم السائق، أو تفاصيل الفاتورة الورقية..."
                            >
                        </div>
                    </div>
                </div>
            </div>

            <!-- Upper Quick Input Panel -->
            <div class="purchases-quick-entry-panel erp-card">
                <div class="erp-card-body p-4">
                    <div class="purchases-quick-row-1">
                        <div class="purchases-search-mode-selector">
                            <label for="searchModeSelect" class="erp-form-label text-xs font-bold mb-0">طريقة الإدخال:</label>
                            <select id="searchModeSelect" class="erp-input text-xs">
                                <option value="barcode">مسدس الباركود (افتراضي)</option>
                                <option value="sku">كود الصنف (SKU)</option>
                                <option value="name">اسم الصنف (بحث)</option>
                            </select>
                        </div>
                    </div>

                    <div class="purchases-quick-row-2">
                        <div class="erp-form-group mb-0 relative">
                            <label for="quickProductInput" class="erp-form-label text-xs font-semibold flex items-center justify-between">
                                <span class="flex items-center gap-1">
                                    <x-icon name="barcode" size="14" class="text-[var(--primary)]" />
                                    <span id="quickSearchInputLabel">مربع البحث / قراءة الباركود</span>
                                </span>
                                <div class="flex items-center gap-1.5">
                                    <!-- Live Available Stock Badge -->
                                    <span id="quickStockBadge" class="hidden pos-stock-badge in-stock font-bold">
                                        رصيد الصنف بالمخزن: 0 قطعة
                                    </span>
                                    <span id="selectedProductBadge" class="hidden text-[10px] text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded font-bold">
                                        صنف محدد
                                    </span>
                                    <!-- Quick Add Product Button -->
                                    <button 
                                        type="button" 
                                        id="openQuickProductModalBtn" 
                                        class="erp-btn erp-btn-xs erp-btn-secondary text-[11px] font-bold text-[var(--primary)] hover:bg-[var(--primary-soft)] border-dashed border-[var(--primary)] cursor-pointer flex items-center gap-1 px-2 py-0.5"
                                        title="إضافة صنف جديد سريع وإدراجه بالفاتورة (اختصار: F3)"
                                    >
                                        <x-icon name="plus" size="12" />
                                        <span>صنف جديد (F3)</span>
                                    </button>
                                </div>
                            </label>
                            <div class="relative w-full">
                                <input 
                                    type="text" 
                                    id="quickProductInput" 
                                    class="erp-input erp-hero-search-input text-xs w-full pl-9" 
                                    placeholder="اضرب الباركود بالمسدس أو اكتب الكود..." 
                                    autocomplete="off"
                                >
                            </div>
                            <div id="quickSearchResults" class="purchases-autocomplete-dropdown hidden"></div>
                            <input type="hidden" id="quickProductId">
                        </div>

                        <!-- مربع وحدة القياس مع سويتش القطعة والدستة -->
                        <div class="erp-form-group mb-0">
                            <div class="flex items-center justify-between mb-1">
                                <label for="quickUnitSelect" class="erp-form-label text-xs font-semibold mb-0">
                                    وحدة القياس
                                </label>
                                <!-- Fast Unit Switcher Pills (Piece / Dozen) -->
                                <div class="pos-unit-switcher" id="posQuickUnitSwitcher">
                                    <button type="button" id="quickUnitPieceBtn" class="pos-unit-pill active" data-unit="piece" title="شراء بالقطعة الفردية (اختصار: P أو ق)">
                                        <x-icon name="disc" size="11" />
                                        <span>قطعة (×1)</span>
                                    </button>
                                    <button type="button" id="quickUnitDozenBtn" class="pos-unit-pill" data-unit="dozen" title="شراء بالدستة أو العبوة كاملة (اختصار: * أو D أو د)">
                                        <x-icon name="layers" size="11" />
                                        <span id="quickUnitDozenLabel">دستة (* 12)</span>
                                    </button>
                                </div>
                            </div>
                            <select id="quickUnitSelect" class="erp-input text-xs font-semibold">
                                @forelse($units as $u)
                                    <option value="{{ $u->id }}" data-name="{{ $u->name }}" data-factor="{{ $u->conversion_factor }}" {{ $u->is_default ? 'selected' : '' }}>
                                        {{ $u->name }} ({{ $u->code }})
                                    </option>
                                @empty
                                    <option value="1" data-name="قطعة" data-factor="1.0000" selected>قطعة (PCS)</option>
                                @endforelse
                            </select>
                        </div>

                        <div class="erp-form-group mb-0">
                            <label for="quickQtyInput" class="erp-form-label text-xs font-semibold">الكمية</label>
                            <input type="number" step="0.01" min="0.01" id="quickQtyInput" class="erp-input font-numeric text-center text-xs" value="1">
                        </div>

                        <div class="erp-form-group mb-0">
                            <label for="quickCostInput" class="erp-form-label text-xs font-semibold">سعر الشراء (ج.م)</label>
                            <input type="number" step="0.01" min="0" id="quickCostInput" class="erp-input font-numeric text-center text-xs font-bold text-[var(--primary)]" value="0.00">
                        </div>

                        <div class="erp-form-group mb-0">
                            <button type="button" id="quickAddBtn" class="erp-btn erp-btn-primary purchases-commit-btn text-xs">
                                <x-icon name="arrow-down-circle" size="16" />
                                <span>إنزال للجدول (Enter)</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Items Table Card -->
            <div class="erp-card">
                <div class="erp-card-header flex items-center justify-between">
                    <div class="erp-card-title">
                        <x-icon name="list" size="18" />
                        <span>جدول أصناف الفاتورة المسجلة</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <button type="button" id="addPurchaseItemBtn" class="erp-btn erp-btn-secondary text-xs">
                            <x-icon name="plus" size="14" />
                            <span>إضافة صنف يدوياً</span>
                        </button>
                    </div>
                </div>

                <div class="erp-card-body p-0">
                    <div class="erp-table-responsive">
                        <table class="erp-table" id="purchaseItemsTable">
                            <thead>
                                <tr>
                                    <th class="w-12 text-center">#</th>
                                    <th>الصنف المختار</th>
                                    <th class="w-28 text-center">وحدة القياس</th>
                                    <th class="w-28 text-center">الكمية</th>
                                    <th class="w-32 text-center">سعر التكلفة (ج.م)</th>
                                    <th class="w-32 text-center">الإجمالي (ج.م)</th>
                                    <th class="w-48">ملاحظات البند</th>
                                    <th class="w-12 text-center">حذف</th>
                                </tr>
                            </thead>
                            <tbody id="purchaseItemsBody">
                                @php
                                    $itemsToRender = old('items');
                                    if (empty($itemsToRender)) {
                                        $itemsToRender = $invoice->items->map(fn($it) => [
                                            'product_id' => $it->product_id,
                                            'unit_id' => $it->unit_id,
                                            'unit_name' => $it->unit?->name ?? 'قطعة',
                                            'conversion_factor' => (float)$it->conversion_factor,
                                            'quantity' => (float)$it->quantity,
                                            'unit_cost' => (float)$it->unit_cost,
                                            'subtotal' => (float)$it->subtotal,
                                            'notes' => $it->notes,
                                        ])->toArray();
                                    }
                                @endphp

                                @foreach($itemsToRender as $idx => $row)
                                    <tr class="purchase-item-row" data-row-index="{{ $idx }}">
                                        <td class="text-center font-numeric text-xs font-bold text-[var(--text-muted)] row-num-cell">
                                            {{ $loop->iteration }}
                                        </td>
                                        <td>
                                            <select name="items[{{ $idx }}][product_id]" class="erp-input purchase-product-select w-full text-xs" required>
                                                <option value="">-- اختر الصنف المطلوب --</option>
                                                @foreach($products as $p)
                                                    <option value="{{ $p->id }}" data-cost="{{ $p->cost_price }}" data-factor="{{ (float)($p->unit_factor ?? 1.0) }}" data-unit="{{ $p->unit }}" {{ (string)($row['product_id'] ?? '') === (string)$p->id ? 'selected' : '' }}>
                                                        {{ $p->name }} ({{ $p->sku }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td class="text-center text-xs">
                                            <span class="inline-block bg-[var(--surface-muted)] px-2 py-1 rounded font-semibold text-[var(--text-main)] purchase-unit-badge">
                                                {{ $row['unit_name'] ?? 'قطعة' }}
                                            </span>
                                            <input type="hidden" name="items[{{ $idx }}][unit_id]" value="{{ $row['unit_id'] ?? '' }}" class="purchase-unit-id-input">
                                            <input type="hidden" name="items[{{ $idx }}][unit_name]" value="{{ $row['unit_name'] ?? 'قطعة' }}" class="purchase-unit-input">
                                            <input type="hidden" name="items[{{ $idx }}][conversion_factor]" value="{{ $row['conversion_factor'] ?? '1.0' }}" class="purchase-factor-input">
                                        </td>
                                        <td>
                                            <input type="number" step="0.01" min="0.01" name="items[{{ $idx }}][quantity]" value="{{ $row['quantity'] ?? '1' }}" class="erp-input font-numeric text-center purchase-qty-input w-full text-xs" required>
                                        </td>
                                        <td>
                                            <input type="number" step="0.01" min="0" name="items[{{ $idx }}][unit_cost]" value="{{ $row['unit_cost'] ?? '0.00' }}" class="erp-input font-numeric text-center purchase-cost-input w-full text-xs" required>
                                        </td>
                                        <td class="font-numeric font-bold text-center text-xs text-[var(--text-main)] purchase-line-subtotal">
                                            {{ number_format(((float)($row['quantity'] ?? 0)) * ((float)($row['unit_cost'] ?? 0)), 2) }}
                                        </td>
                                        <td>
                                            <input type="text" name="items[{{ $idx }}][notes]" value="{{ $row['notes'] ?? '' }}" class="erp-input text-xs w-full" placeholder="ملاحظات...">
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="erp-btn erp-btn-ghost erp-btn-icon text-[var(--danger)] remove-row-btn" title="حذف السطر">
                                                <x-icon name="trash" size="14" />
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Footer Summary Cards -->
                <div class="erp-card-footer p-4 bg-[var(--surface-muted)] flex flex-col gap-3">
                    <div class="purchases-summary-grid">
                        <div class="purchases-summary-card">
                            <span class="purchases-summary-label">عدد المنتجات</span>
                            <span class="purchases-summary-value">
                                <span id="purchaseItemsCountText">{{ count($itemsToRender) }}</span>
                                <span class="purchases-summary-unit">أصناف</span>
                            </span>
                        </div>

                        <div class="purchases-summary-card">
                            <span class="purchases-summary-label">إجمالي الكميات</span>
                            <span class="purchases-summary-value">
                                <span id="purchaseTotalQtyText">0.00</span>
                                <span class="purchases-summary-unit">وحدة</span>
                            </span>
                        </div>

                        <div class="purchases-summary-card">
                            <span class="purchases-summary-label">المجموع الفرعي</span>
                            <span class="purchases-summary-value">
                                <span id="purchaseSubtotalText">{{ number_format((float)$invoice->subtotal, 2) }}</span>
                                <span class="purchases-summary-unit">ج.م</span>
                            </span>
                        </div>

                        <div class="purchases-summary-card discount">
                            <span class="purchases-summary-label">الخصم المطبق</span>
                            <span class="purchases-summary-value">
                                -<span id="purchaseDiscountText">0.00</span>
                                <span class="purchases-summary-unit">ج.م</span>
                            </span>
                        </div>

                        <div class="purchases-summary-card">
                            <span class="purchases-summary-label">الشحن / التخليص</span>
                            <span class="purchases-summary-value">
                                +<span id="purchaseShippingText">{{ number_format((float)$invoice->shipping_cost, 2) }}</span>
                                <span class="purchases-summary-unit">ج.م</span>
                            </span>
                        </div>

                        <div class="purchases-summary-card grand-total">
                            <span class="purchases-summary-label">صافي إجمالي الفاتورة</span>
                            <span class="purchases-summary-value">
                                <span id="purchaseGrandTotalText">{{ number_format((float)$invoice->total_amount, 2) }}</span>
                                <span class="purchases-summary-unit">ج.م</span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment & Supplier Settlement -->
            <div class="purchases-payment-card erp-card">
                <div class="erp-card-header flex items-center justify-between py-2.5">
                    <div class="erp-card-title flex items-center gap-2">
                        <x-icon name="credit-card" size="18" class="text-[var(--primary)]" />
                        <span>طريقة السداد للمورد (نظام الآجل والتحصيل)</span>
                    </div>
                </div>
                <div class="erp-card-body p-4">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                        <div class="erp-form-group mb-0">
                            <label class="erp-form-label text-xs font-bold mb-1.5 flex items-center gap-1">
                                <x-icon name="dollar-sign" size="13" class="text-[var(--primary)]" />
                                <span>طريقة الدفع للمورد</span>
                            </label>
                            <div class="payment-method-toggle-group" id="purchasePaymentToggleGroup">
                                <label class="payment-toggle-btn {{ old('payment_type', $invoice->payment_type) === 'cash' ? 'active' : '' }}" data-type="cash">
                                    <input type="radio" name="payment_type" value="cash" {{ old('payment_type', $invoice->payment_type) === 'cash' ? 'checked' : '' }} class="sr-only">
                                    <span>نقدي (مسدد)</span>
                                </label>
                                <label class="payment-toggle-btn {{ old('payment_type', $invoice->payment_type) === 'credit' ? 'active' : '' }}" data-type="credit">
                                    <input type="radio" name="payment_type" value="credit" {{ old('payment_type', $invoice->payment_type) === 'credit' ? 'checked' : '' }} class="sr-only">
                                    <span>آجل بالكامل</span>
                                </label>
                                <label class="payment-toggle-btn {{ old('payment_type', $invoice->payment_type) === 'partial' ? 'active' : '' }}" data-type="partial">
                                    <input type="radio" name="payment_type" value="partial" {{ old('payment_type', $invoice->payment_type) === 'partial' ? 'checked' : '' }} class="sr-only">
                                    <span>دفع جزئي</span>
                                </label>
                            </div>
                        </div>

                        <div class="erp-form-group mb-0">
                            <label for="purchasePaidAmountInput" class="erp-form-label text-xs font-semibold flex items-center justify-between">
                                <span class="flex items-center gap-1">
                                    <x-icon name="check" size="13" class="text-[var(--success)]" />
                                    <span>المدفوع نقداً للمورد (ج.م)</span>
                                </span>
                                <span id="purchasePaidHint" class="text-[10px] text-[var(--text-muted)]"></span>
                            </label>
                            <input 
                                type="number" 
                                step="0.01" 
                                min="0" 
                                id="purchasePaidAmountInput" 
                                name="paid_amount" 
                                value="{{ old('paid_amount', (float)$invoice->paid_amount) }}" 
                                class="erp-input font-numeric font-bold text-center text-sm text-[var(--success)]"
                            >
                        </div>

                        <div class="erp-form-group mb-0">
                            <label for="purchaseRemainingAmountInput" class="erp-form-label text-xs font-semibold flex items-center justify-between">
                                <span class="flex items-center gap-1">
                                    <x-icon name="clock" size="13" class="text-[var(--danger)]" />
                                    <span>المتبقي الآجل للمورد (ج.م)</span>
                                </span>
                                <span id="purchaseRemainingHint" class="text-[10px] text-[var(--text-muted)]"></span>
                            </label>
                            <input 
                                type="number" 
                                step="0.01" 
                                id="purchaseRemainingAmountInput" 
                                name="remaining_amount" 
                                value="{{ old('remaining_amount', (float)$invoice->remaining_amount) }}" 
                                class="erp-input font-numeric font-bold text-center text-sm text-[var(--danger)] bg-[var(--surface-muted)]" 
                                readonly
                                tabindex="-1"
                            >
                        </div>

                        <div class="erp-form-group mb-0 {{ old('payment_type', $invoice->payment_type) === 'cash' ? 'hidden' : '' }}" id="purchaseDueDateGroup">
                            <label for="purchaseDueDateInput" class="erp-form-label text-xs font-semibold flex items-center gap-1">
                                <x-icon name="calendar" size="13" class="text-amber-500" />
                                <span>تاريخ استحقاق المورد</span>
                            </label>
                            <input 
                                type="date" 
                                id="purchaseDueDateInput" 
                                name="due_date" 
                                value="{{ old('due_date', $invoice->due_date?->format('Y-m-d')) }}" 
                                class="erp-input text-xs font-numeric"
                            >
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Hidden Template for Dynamic Row Addition -->
    <template id="purchaseItemRowTemplate">
        <tr class="purchase-item-row" data-row-index="__INDEX__">
            <td class="text-center font-numeric text-xs font-bold text-[var(--text-muted)] row-num-cell">
                __NUM__
            </td>
            <td>
                <select name="items[__INDEX__][product_id]" class="erp-input purchase-product-select w-full text-xs" required>
                    <option value="">-- اختر الصنف المطلوب --</option>
                    @foreach($products as $p)
                        <option value="{{ $p->id }}" data-cost="{{ $p->cost_price }}" data-factor="{{ (float)($p->unit_factor ?? 1.0) }}" data-unit="{{ $p->unit }}">
                            {{ $p->name }} ({{ $p->sku }})
                        </option>
                    @endforeach
                </select>
            </td>
            <td class="text-center text-xs">
                <span class="inline-block bg-[var(--surface-muted)] px-2 py-1 rounded font-semibold text-[var(--text-main)] purchase-unit-badge">
                    __UNIT__
                </span>
                <input type="hidden" name="items[__INDEX__][unit_id]" value="__UNIT_ID__" class="purchase-unit-id-input">
                <input type="hidden" name="items[__INDEX__][unit_name]" value="__UNIT__" class="purchase-unit-input">
                <input type="hidden" name="items[__INDEX__][conversion_factor]" value="__FACTOR__" class="purchase-factor-input">
            </td>
            <td>
                <input type="number" step="0.01" min="0.01" name="items[__INDEX__][quantity]" value="__QTY__" class="erp-input font-numeric text-center purchase-qty-input w-full text-xs" required>
            </td>
            <td>
                <input type="number" step="0.01" min="0" name="items[__INDEX__][unit_cost]" value="__COST__" class="erp-input font-numeric text-center purchase-cost-input w-full text-xs" required>
            </td>
            <td class="font-numeric font-bold text-center text-xs text-[var(--text-main)] purchase-line-subtotal">
                __SUBTOTAL__
            </td>
            <td>
                <input type="text" name="items[__INDEX__][notes]" class="erp-input text-xs w-full" placeholder="ملاحظات...">
            </td>
            <td class="text-center">
                <button type="button" class="erp-btn erp-btn-ghost erp-btn-icon text-[var(--danger)] remove-row-btn" title="حذف السطر">
                    <x-icon name="trash" size="14" />
                </button>
            </td>
        </tr>
    </template>

    <!-- Quick Add Product Modal Dialog -->
    <x-quick-product-modal :categories="$categories" :units="$units" :suggested-sku="$suggestedSku ?? null" />

    <!-- Quick Add Supplier Modal Dialog -->
    <x-quick-supplier-modal />
@endsection
