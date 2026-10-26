@extends('layouts.app')

@section('control_panel')
    <x-control-panel title="تسجيل مرتجع مشتريات" :breadcrumbs="['المشتريات والموردين' => route('purchases.index'), 'مرتجعات المشتريات' => route('purchase-returns.index'), 'مرتجع جديد' => '#']">
        <x-slot:actions>
            <a href="{{ route('purchase-returns.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="arrow-right" size="16" />
                <span>العودة لقائمة المرتجعات</span>
            </a>
        </x-slot:actions>

        <x-slot:extra>
            <span class="erp-badge erp-badge-neutral font-numeric">
                <x-icon name="hash" size="14" />
                <span>رقم المرتجع المقترح: {{ $suggestedNumber }}</span>
            </span>
        </x-slot:extra>
    </x-control-panel>
@endsection

@section('content')
    <div data-page="purchase-returns-create"
         data-products="{{ json_encode($productsJson ?? []) }}"
         data-units="{{ json_encode($unitsJson ?? []) }}">

        @if(session('error'))
            <div class="erp-alert erp-alert-danger mb-4" role="alert">
                <x-icon name="alert-triangle" size="18" />
                <span>{{ session('error') }}</span>
            </div>
        @endif

        @if($errors->any())
            <div class="erp-alert erp-alert-danger mb-4" role="alert">
                <x-icon name="alert-circle" size="18" />
                <div>
                    <strong class="font-bold">يرجى تصحيح الأخطاء التالية:</strong>
                    <ul class="list-disc list-inside mt-1 text-xs">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        @if($selectedInvoice)
            <div class="erp-alert erp-alert-info mb-4" role="alert">
                <x-icon name="info" size="18" />
                <div class="flex items-center justify-between w-full">
                    <span>
                        يتم الآن إنشاء مرتجع مرتبط بفاتورة الشراء رقم: 
                        <strong class="font-mono font-bold">{{ $selectedInvoice->invoice_number }}</strong>
                        تاريخ: {{ $selectedInvoice->invoice_date?->format('Y-m-d') }}
                    </span>
                    <a href="{{ route('purchases.show', $selectedInvoice) }}" class="erp-btn erp-btn-sm erp-btn-secondary" target="_blank">
                        <x-icon name="external-link" size="14" />
                        <span>معاينة الفاتورة الأصلية</span>
                    </a>
                </div>
            </div>
        @endif

        <form action="{{ route('purchase-returns.store') }}" method="POST" id="purchaseReturnForm">
            @csrf
            @if($selectedInvoice)
                <input type="hidden" name="purchase_invoice_id" value="{{ $selectedInvoice->id }}">
            @endif

            <!-- Metadata Card -->
            <div class="erp-card mb-4 p-4">
                <h3 class="text-sm font-bold text-[var(--text-main)] mb-3 pb-2 border-b border-[var(--border-color)] flex items-center gap-2">
                    <x-icon name="file-text" size="16" class="text-[var(--primary)]" />
                    <span>بيانات المرتجع والمورد</span>
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <!-- Supplier -->
                    <div class="erp-form-group">
                        <label for="supplierId" class="erp-form-label">
                            المورد <span class="text-[var(--danger)]">*</span>
                        </label>
                        <select name="supplier_id" id="supplierId" class="erp-input @error('supplier_id') erp-input-error @enderror" required {{ $selectedInvoice ? 'readonly' : '' }}>
                            <option value="">-- اختر المورد --</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" {{ (string)old('supplier_id', $selectedInvoice?->supplier_id) === (string)$supplier->id ? 'selected' : '' }}>
                                    {{ $supplier->name }} (رصيد: {{ number_format((float)$supplier->current_balance, 2) }} ج.م)
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Warehouse -->
                    <div class="erp-form-group">
                        <label for="warehouseId" class="erp-form-label">
                            مستودع الصرف (الإرجاع منه) <span class="text-[var(--danger)]">*</span>
                        </label>
                        <select name="warehouse_id" id="warehouseId" class="erp-input @error('warehouse_id') erp-input-error @enderror" required {{ $selectedInvoice ? 'readonly' : '' }}>
                            <option value="">-- اختر المستودع --</option>
                            @foreach($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}" {{ (string)old('warehouse_id', $selectedInvoice?->warehouse_id ?? $defaultWarehouse?->id) === (string)$warehouse->id ? 'selected' : '' }}>
                                    {{ $warehouse->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Return Date -->
                    <div class="erp-form-group">
                        <label for="returnDate" class="erp-form-label">
                            تاريخ المرتجع <span class="text-[var(--danger)]">*</span>
                        </label>
                        <input 
                            type="date" 
                            name="return_date" 
                            id="returnDate" 
                            value="{{ old('return_date', date('Y-m-d')) }}" 
                            class="erp-input font-numeric @error('return_date') erp-input-error @enderror" 
                            required
                        >
                    </div>

                    <!-- Settlement Type -->
                    <div class="erp-form-group">
                        <label for="settlementType" class="erp-form-label">
                            طريقة التسوية المالية <span class="text-[var(--danger)]">*</span>
                        </label>
                        <select name="settlement_type" id="settlementType" class="erp-input font-semibold @error('settlement_type') erp-input-error @enderror" required>
                            <option value="deduct_balance" {{ old('settlement_type', 'deduct_balance') === 'deduct_balance' ? 'selected' : '' }}>
                                خصم من مديونية المورد (آجل / رصيد)
                            </option>
                            <option value="cash_refund" {{ old('settlement_type') === 'cash_refund' ? 'selected' : '' }}>
                                استرداد نقدي فوري (إيداع بالخزينة)
                            </option>
                        </select>
                    </div>
                </div>

                <!-- Treasury (shown if cash_refund) -->
                <div id="treasuryFieldRow" class="{{ old('settlement_type') === 'cash_refund' ? '' : 'hidden' }} mt-3 pt-3 border-t border-[var(--border-color)]">
                    <div class="max-w-md">
                        <label for="treasuryId" class="erp-form-label">
                            الخزينة المستلمة للنقدية المستردة <span class="text-[var(--danger)]">*</span>
                        </label>
                        <select name="treasury_id" id="treasuryId" class="erp-input">
                            <option value="">-- اختر الخزينة --</option>
                            @foreach($treasuries as $trx)
                                <option value="{{ $trx->id }}" {{ (string)old('treasury_id') === (string)$trx->id ? 'selected' : '' }}>
                                    {{ $trx->name }} (رصيد حالي: {{ number_format((float)$trx->current_balance, 2) }} ج.م)
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="mt-3">
                    <label for="notes" class="erp-form-label">ملاحظات / سبب الإرجاع للمورد</label>
                    <input 
                        type="text" 
                        name="notes" 
                        id="notes" 
                        value="{{ old('notes') }}" 
                        class="erp-input text-xs" 
                        placeholder="مثال: أصناف بها عيوب تصنيع، شحنة تالفة، عدم مطابقة للمواصفات..."
                    >
                </div>
            </div>

            <!-- Items Table Card -->
            <div class="erp-card p-0 mb-4 overflow-hidden">
                <div class="p-3 bg-[var(--surface-muted)] border-b border-[var(--border-color)] flex items-center justify-between">
                    <h3 class="text-sm font-bold text-[var(--text-main)] flex items-center gap-2">
                        <x-icon name="package" size="16" class="text-[var(--primary)]" />
                        <span>الأصناف والكميات المرتجعة</span>
                    </h3>
                    <button type="button" id="addReturnItemRowBtn" class="erp-btn erp-btn-sm erp-btn-secondary">
                        <x-icon name="plus" size="14" />
                        <span>إضافة صنف آخر</span>
                    </button>
                </div>

                <div class="erp-table-responsive">
                    <table class="erp-table" id="returnItemsTable">
                        <thead>
                            <tr>
                                <th style="width: 40px;">#</th>
                                <th style="min-width: 250px;">الصنف</th>
                                <th style="width: 140px;">الوحدة</th>
                                <th style="width: 130px;">سعر الشراء (ج.م)</th>
                                <th style="width: 130px;">الكمية المرتجعة</th>
                                <th style="width: 140px;">إجمالي المرتجع (ج.م)</th>
                                <th>سبب / ملاحظات</th>
                                <th style="width: 50px;">حذف</th>
                            </tr>
                        </thead>
                        <tbody id="returnItemsBody">
                            @if($selectedInvoice && $selectedInvoice->items->isNotEmpty())
                                @foreach($selectedInvoice->items as $idx => $invItem)
                                    @php
                                        $prodFactor = (float)($invItem->product?->unit_factor ?? 1.0);
                                        $batch = $selectedInvoice->batches->where('product_id', $invItem->product_id)->first();
                                    @endphp
                                    <tr class="return-item-row" data-row-index="{{ $idx }}">
                                        <td class="text-center font-numeric row-num-cell">{{ $idx + 1 }}</td>
                                        <td>
                                            <input type="hidden" name="items[{{ $idx }}][product_id]" value="{{ $invItem->product_id }}">
                                            <input type="hidden" name="items[{{ $idx }}][purchase_invoice_item_id]" value="{{ $invItem->id }}">
                                            @if($batch)
                                                <input type="hidden" name="items[{{ $idx }}][inventory_batch_id]" value="{{ $batch->id }}">
                                            @endif
                                            <div class="font-bold text-xs text-[var(--text-main)]">{{ $invItem->product?->name }}</div>
                                            <div class="text-[11px] text-[var(--text-muted)] font-mono">
                                                كود: {{ $invItem->product?->sku }}
                                                @if($batch)
                                                    • دفعة: {{ $batch->batch_number }} (متبقي بالدفعة: {{ (float)$batch->remaining_quantity }})
                                                @endif
                                            </div>
                                            <div class="text-[10px] text-sky-700 bg-sky-50 dark:bg-sky-950/40 px-1.5 py-0.5 rounded inline-block mt-0.5 font-numeric">
                                                الكمية بالفاتورة الأصلية: {{ (float)$invItem->quantity }} {{ $invItem->unit?->name ?? 'قطعة' }}
                                            </div>
                                        </td>
                                        <td>
                                            <select name="items[{{ $idx }}][unit_id]" class="erp-input text-xs return-unit-select">
                                                @foreach($units as $u)
                                                    <option value="{{ $u->id }}" data-factor="{{ $u->conversion_factor }}" {{ (string)$invItem->unit_id === (string)$u->id ? 'selected' : '' }}>
                                                        {{ $u->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <input 
                                                type="number" 
                                                step="0.01" 
                                                min="0" 
                                                name="items[{{ $idx }}][unit_cost]" 
                                                value="{{ (float)$invItem->unit_cost }}" 
                                                class="erp-input text-xs font-numeric return-cost-input" 
                                                required
                                            >
                                        </td>
                                        <td>
                                            <input 
                                                type="number" 
                                                step="0.01" 
                                                min="0.01" 
                                                max="{{ (float)$invItem->quantity }}" 
                                                name="items[{{ $idx }}][quantity]" 
                                                value="{{ (float)$invItem->quantity }}" 
                                                class="erp-input text-xs font-numeric text-center return-qty-input font-bold" 
                                                required
                                            >
                                        </td>
                                        <td class="font-numeric font-bold text-sm text-[var(--primary)] text-left return-line-subtotal">
                                            {{ number_format((float)$invItem->subtotal, 2) }}
                                        </td>
                                        <td>
                                            <input 
                                                type="text" 
                                                name="items[{{ $idx }}][notes]" 
                                                class="erp-input text-xs" 
                                                placeholder="ملاحظة..."
                                            >
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="erp-btn erp-btn-ghost erp-btn-sm text-[var(--danger)] remove-row-btn p-1">
                                                <x-icon name="trash-2" size="15" />
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr class="return-item-row" data-row-index="0">
                                    <td class="text-center font-numeric row-num-cell">1</td>
                                    <td>
                                        <select name="items[0][product_id]" class="erp-input text-xs return-product-select" required>
                                            <option value="">-- اختر الصنف --</option>
                                            @foreach($products as $p)
                                                <option value="{{ $p->id }}" data-cost="{{ (float)$p->cost_price }}" data-factor="{{ (float)($p->unit_factor ?? 1.0) }}" data-unit="{{ $p->unit ?? 'قطعة' }}">
                                                    {{ $p->name }} ({{ $p->sku }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <select name="items[0][unit_id]" class="erp-input text-xs return-unit-select">
                                            @foreach($units as $u)
                                                <option value="{{ $u->id }}" data-factor="{{ $u->conversion_factor }}" {{ $u->is_default ? 'selected' : '' }}>
                                                    {{ $u->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <input 
                                            type="number" 
                                            step="0.01" 
                                            min="0" 
                                            name="items[0][unit_cost]" 
                                            value="0.00" 
                                            class="erp-input text-xs font-numeric return-cost-input" 
                                            required
                                        >
                                    </td>
                                    <td>
                                        <input 
                                            type="number" 
                                            step="0.01" 
                                            min="0.01" 
                                            name="items[0][quantity]" 
                                            value="1" 
                                            class="erp-input text-xs font-numeric text-center return-qty-input font-bold" 
                                            required
                                        >
                                    </td>
                                    <td class="font-numeric font-bold text-sm text-[var(--primary)] text-left return-line-subtotal">
                                        0.00
                                    </td>
                                    <td>
                                        <input 
                                            type="text" 
                                            name="items[0][notes]" 
                                            class="erp-input text-xs" 
                                            placeholder="ملاحظة..."
                                        >
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="erp-btn erp-btn-ghost erp-btn-sm text-[var(--danger)] remove-row-btn p-1">
                                            <x-icon name="trash-2" size="15" />
                                        </button>
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>

                <!-- Footer Summary -->
                <div class="p-4 bg-[var(--surface-muted)] border-t border-[var(--border-color)] flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-4 text-xs font-semibold text-[var(--text-muted)]">
                        <span>عدد الأصناف: <strong id="returnItemsCount" class="font-numeric text-[var(--text-main)]">0</strong></span>
                        <span>إجمالي الكمية: <strong id="returnTotalQty" class="font-numeric text-[var(--text-main)]">0.00</strong></span>
                    </div>

                    <div class="flex items-center gap-3">
                        <span class="text-sm font-bold text-[var(--text-main)]">إجمالي المرتجع:</span>
                        <span id="returnGrandTotal" class="text-xl font-bold font-numeric text-[var(--danger)]">0.00</span>
                        <span class="text-xs text-[var(--text-muted)] font-semibold">ج.م</span>
                    </div>
                </div>
            </div>

            <!-- Submit Buttons -->
            <div class="flex items-center justify-end gap-3 mb-8">
                <button type="submit" name="action" value="draft" class="erp-btn erp-btn-secondary">
                    <x-icon name="save" size="16" />
                    <span>حفظ كمسودة مرتجع</span>
                </button>
                <button type="submit" name="action" value="post" class="erp-btn erp-btn-primary bg-emerald-600 hover:bg-emerald-700 text-white">
                    <x-icon name="check-circle" size="16" />
                    <span>حفظ وترحيل فوري للمخازن</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Hidden Template for Dynamic Row Addition -->
    <template id="returnRowTemplate">
        <tr class="return-item-row">
            <td class="text-center font-numeric row-num-cell">#</td>
            <td>
                <select name="items[INDEX][product_id]" class="erp-input text-xs return-product-select" required>
                    <option value="">-- اختر الصنف --</option>
                    @foreach($products as $p)
                        <option value="{{ $p->id }}" data-cost="{{ (float)$p->cost_price }}" data-factor="{{ (float)($p->unit_factor ?? 1.0) }}" data-unit="{{ $p->unit ?? 'قطعة' }}">
                            {{ $p->name }} ({{ $p->sku }})
                        </option>
                    @endforeach
                </select>
            </td>
            <td>
                <select name="items[INDEX][unit_id]" class="erp-input text-xs return-unit-select">
                    @foreach($units as $u)
                        <option value="{{ $u->id }}" data-factor="{{ $u->conversion_factor }}" {{ $u->is_default ? 'selected' : '' }}>
                            {{ $u->name }}
                        </option>
                    @endforeach
                </select>
            </td>
            <td>
                <input 
                    type="number" 
                    step="0.01" 
                    min="0" 
                    name="items[INDEX][unit_cost]" 
                    value="0.00" 
                    class="erp-input text-xs font-numeric return-cost-input" 
                    required
                >
            </td>
            <td>
                <input 
                    type="number" 
                    step="0.01" 
                    min="0.01" 
                    name="items[INDEX][quantity]" 
                    value="1" 
                    class="erp-input text-xs font-numeric text-center return-qty-input font-bold" 
                    required
                >
            </td>
            <td class="font-numeric font-bold text-sm text-[var(--primary)] text-left return-line-subtotal">
                0.00
            </td>
            <td>
                <input 
                    type="text" 
                    name="items[INDEX][notes]" 
                    class="erp-input text-xs" 
                    placeholder="ملاحظة..."
                >
            </td>
            <td class="text-center">
                <button type="button" class="erp-btn erp-btn-ghost erp-btn-sm text-[var(--danger)] remove-row-btn p-1">
                    <x-icon name="trash-2" size="15" />
                </button>
            </td>
        </tr>
    </template>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const tableBody = document.getElementById('returnItemsBody');
            const template = document.getElementById('returnRowTemplate');
            const addBtn = document.getElementById('addReturnItemRowBtn');
            const settlementSelect = document.getElementById('settlementType');
            const treasuryRow = document.getElementById('treasuryFieldRow');
            const treasurySelect = document.getElementById('treasuryId');

            // Toggle treasury select based on settlement type
            if (settlementSelect && treasuryRow) {
                settlementSelect.addEventListener('change', function () {
                    if (this.value === 'cash_refund') {
                        treasuryRow.classList.remove('hidden');
                        if (treasurySelect) treasurySelect.required = true;
                    } else {
                        treasuryRow.classList.add('hidden');
                        if (treasurySelect) treasurySelect.required = false;
                    }
                });
            }

            function attachEvents(row) {
                const prodSelect = row.querySelector('.return-product-select');
                const unitSelect = row.querySelector('.return-unit-select');
                const costInput = row.querySelector('.return-cost-input');
                const qtyInput = row.querySelector('.return-qty-input');
                const removeBtn = row.querySelector('.remove-row-btn');

                if (prodSelect) {
                    prodSelect.addEventListener('change', function () {
                        const opt = this.options[this.selectedIndex];
                        if (opt && opt.value) {
                            const cost = parseFloat(opt.dataset.cost) || 0;
                            const factor = parseFloat(opt.dataset.factor) || 1.0;
                            const unitName = (opt.dataset.unit || 'قطعة').trim();

                            if (unitSelect) {
                                for (let i = 0; i < unitSelect.options.length; i++) {
                                    if (unitSelect.options[i].text.includes(unitName)) {
                                        unitSelect.selectedIndex = i;
                                        break;
                                    }
                                }
                            }
                            if (costInput) {
                                costInput.value = cost.toFixed(2);
                            }
                        }
                        recalculateTotals();
                    });
                }

                if (unitSelect) {
                    unitSelect.addEventListener('change', function () {
                        if (prodSelect) {
                            const opt = prodSelect.options[prodSelect.selectedIndex];
                            if (opt && opt.value && costInput) {
                                const prodFactor = parseFloat(opt.dataset.factor) || 1.0;
                                const baseCost = (parseFloat(opt.dataset.cost) || 0) / prodFactor;
                                const targetFactor = parseFloat(this.options[this.selectedIndex]?.dataset?.factor) || 1.0;
                                costInput.value = (baseCost * targetFactor).toFixed(2);
                            }
                        }
                        recalculateTotals();
                    });
                }

                if (costInput) costInput.addEventListener('input', recalculateTotals);
                if (qtyInput) qtyInput.addEventListener('input', recalculateTotals);

                if (removeBtn) {
                    removeBtn.addEventListener('click', function () {
                        const rows = tableBody.querySelectorAll('.return-item-row');
                        if (rows.length <= 1) {
                            alert('يجب الإبقاء على صنف واحد على الأقل في المرتجع.');
                            return;
                        }
                        row.remove();
                        reindex();
                        recalculateTotals();
                    });
                }
            }

            function reindex() {
                const rows = tableBody.querySelectorAll('.return-item-row');
                rows.forEach((row, idx) => {
                    row.setAttribute('data-row-index', idx);
                    const numCell = row.querySelector('.row-num-cell');
                    if (numCell) numCell.textContent = idx + 1;

                    row.querySelectorAll('input, select').forEach(input => {
                        const name = input.getAttribute('name');
                        if (name) {
                            input.setAttribute('name', name.replace(/items\[\d+\]/, `items[${idx}]`));
                        }
                    });
                });
            }

            function recalculateTotals() {
                let grandTotal = 0;
                let totalQty = 0;
                const rows = tableBody.querySelectorAll('.return-item-row');

                rows.forEach(row => {
                    const costInput = row.querySelector('.return-cost-input');
                    const qtyInput = row.querySelector('.return-qty-input');
                    const lineCell = row.querySelector('.return-line-subtotal');

                    const cost = parseFloat(costInput ? costInput.value : 0) || 0;
                    const qty = parseFloat(qtyInput ? qtyInput.value : 0) || 0;
                    const lineTotal = cost * qty;

                    if (lineCell) lineCell.textContent = lineTotal.toFixed(2);
                    grandTotal += lineTotal;
                    totalQty += qty;
                });

                const countEl = document.getElementById('returnItemsCount');
                const qtyEl = document.getElementById('returnTotalQty');
                const totalEl = document.getElementById('returnGrandTotal');

                if (countEl) countEl.textContent = rows.length;
                if (qtyEl) qtyEl.textContent = totalQty.toFixed(2);
                if (totalEl) totalEl.textContent = grandTotal.toFixed(2);
            }

            if (addBtn && template) {
                addBtn.addEventListener('click', function () {
                    const idx = tableBody.querySelectorAll('.return-item-row').length;
                    const clone = template.content.cloneNode(true);
                    const row = clone.querySelector('.return-item-row');
                    row.innerHTML = row.innerHTML.replace(/INDEX/g, idx);
                    tableBody.appendChild(row);
                    attachEvents(row);
                    reindex();
                    recalculateTotals();
                });
            }

            // Init existing rows
            tableBody.querySelectorAll('.return-item-row').forEach(row => attachEvents(row));
            recalculateTotals();
        });
    </script>
    @endpush
@endsection
