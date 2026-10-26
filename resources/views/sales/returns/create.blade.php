@extends('layouts.app')

@section('control_panel')
    <x-control-panel title="تسجيل مرتجع مبيعات" :breadcrumbs="['المبيعات والعملاء' => route('sales.index'), 'مرتجعات المبيعات' => route('sales-returns.index'), 'مرتجع جديد' => '#']">
        <x-slot:actions>
            <a href="{{ route('sales-returns.index') }}" class="erp-btn erp-btn-secondary">
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
    <div data-page="sales-returns-create">

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

        @if(! $selectedInvoice)
            <!-- Invoice Selection Card -->
            <div class="erp-card p-6 max-w-2xl mx-auto my-6 text-center border border-slate-200/80 shadow-sm bg-white">
                <div class="w-14 h-14 rounded-2xl bg-purple-50 text-purple-600 mx-auto flex items-center justify-center mb-3">
                    <x-icon name="file-text" size="28" />
                </div>
                <h3 class="text-base font-bold text-slate-800 mb-1">اختر فاتورة المبيعات المراد تحرير مرتجع لها</h3>
                <p class="text-xs text-slate-500 mb-5">يتطلب المرتجع المحاسبي ربطه بفاتورة بيع مرحلة لحساب تكلفة COGS واسترجاع الأصناف لدفعاتها الأصلية بدقة.</p>

                <form method="GET" action="{{ route('sales-returns.create') }}" class="flex flex-col sm:flex-row gap-2.5 max-w-lg mx-auto">
                    <select name="sales_invoice_id" class="erp-input text-xs flex-1" required>
                        <option value="">-- اختر الفاتورة من القائمة الأخيرة --</option>
                        @foreach($invoices as $inv)
                            <option value="{{ $inv->id }}">
                                {{ $inv->invoice_number }} — {{ $inv->customer->name ?? 'عميل نقدي' }} ({{ number_format((float)$inv->total_amount, 2) }} ج.م) — {{ $inv->invoice_date?->format('Y-m-d') }}
                            </option>
                        @endforeach
                    </select>
                    <button type="submit" class="erp-btn erp-btn-primary text-xs shrink-0 py-2 px-4 justify-center">
                        <x-icon name="check" size="14" />
                        <span>متابعة الإرجاع</span>
                    </button>
                </form>
            </div>
        @else
            <!-- Invoice Banner -->
            <div class="erp-alert erp-alert-info mb-4" role="alert">
                <x-icon name="info" size="18" />
                <div class="flex items-center justify-between w-full flex-wrap gap-2">
                    <span class="text-xs">
                        يتم الآن إنشاء مرتجع مرتبط بفاتورة المبيعات رقم: 
                        <strong class="font-numeric font-bold text-sm">{{ $selectedInvoice->invoice_number }}</strong>
                        · العميل: <strong>{{ $selectedInvoice->customer->name ?? 'عميل نقدي' }}</strong>
                        · المخزن: <strong>{{ $selectedInvoice->warehouse->name ?? '—' }}</strong>
                        · تاريخ الفاتورة: <span class="font-numeric">{{ $selectedInvoice->invoice_date?->format('Y-m-d') }}</span>
                    </span>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('sales-returns.create') }}" class="erp-btn erp-btn-sm erp-btn-secondary text-xs">
                            <x-icon name="refresh-cw" size="12" />
                            <span>تغيير الفاتورة</span>
                        </a>
                        <a href="{{ route('sales.show', $selectedInvoice) }}" class="erp-btn erp-btn-sm erp-btn-ghost text-xs" target="_blank">
                            <x-icon name="external-link" size="12" />
                            <span>معاينة الفاتورة</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Return Form -->
            <form action="{{ route('sales-returns.store') }}" method="POST" id="salesReturnForm">
                @csrf
                <input type="hidden" name="sales_invoice_id" value="{{ $selectedInvoice->id }}">

                <!-- Metadata Card -->
                <div class="erp-card mb-4 p-4 border border-slate-200/80 bg-white shadow-xs">
                    <h3 class="text-xs font-bold text-slate-800 mb-3 pb-2 border-b border-slate-100 flex items-center gap-2">
                        <x-icon name="file-text" size="15" class="text-[var(--primary)]" />
                        <span>بيانات إذن المرتجع</span>
                    </h3>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
                        <div>
                            <label class="erp-form-label text-xs font-semibold mb-1">رقم إذن المرتجع <span class="text-rose-500">*</span></label>
                            <input type="text" name="return_number" value="{{ old('return_number', $suggestedNumber) }}" class="erp-input w-full text-xs font-numeric font-bold" required>
                        </div>

                        <div>
                            <label class="erp-form-label text-xs font-semibold mb-1">تاريخ المرتجع <span class="text-rose-500">*</span></label>
                            <input type="date" name="return_date" value="{{ old('return_date', date('Y-m-d')) }}" class="erp-input w-full text-xs font-numeric" required>
                        </div>

                        <div>
                            <label class="erp-form-label text-xs font-semibold mb-1">ملاحظات وسبب الإرجاع</label>
                            <input type="text" name="notes" value="{{ old('notes') }}" placeholder="سبب الإرجاع أو ملاحظات العميل..." class="erp-input w-full text-xs">
                        </div>
                    </div>
                </div>

                <!-- Return Items Card -->
                <div class="erp-card mb-4 p-4 border border-slate-200/80 bg-white shadow-xs">
                    <div class="flex items-center justify-between mb-3 pb-2 border-b border-slate-100">
                        <h3 class="text-xs font-bold text-slate-800 flex items-center gap-2">
                            <x-icon name="package" size="15" class="text-[var(--primary)]" />
                            <span>أصناف الفاتورة المتاحة للإرجاع</span>
                        </h3>
                        <span class="text-[11px] text-slate-400">حدد الكمية المراد إرجاعها أمام كل صنف</span>
                    </div>

                    <div class="erp-table-responsive">
                        <table class="erp-table" id="returnItemsTable">
                            <thead>
                                <tr>
                                    <th class="w-12 text-center">#</th>
                                    <th>الصنف</th>
                                    <th>الوحدة</th>
                                    <th class="text-center">الكمية المباعة</th>
                                    <th class="text-center">مرتجع سابقاً</th>
                                    <th class="text-center">المتبقي للإرجاع</th>
                                    <th class="w-32 text-center">كمية المرتجع</th>
                                    <th class="w-32 text-center">سعر الاسترداد (ج.م)</th>
                                    <th class="w-32 text-center">إجمالي الاسترداد</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($returnableItems as $idx => $rItem)
                                    @php
                                        $item = $rItem['item'];
                                        $sold = $rItem['sold_quantity'];
                                        $alreadyReturned = $rItem['already_returned'];
                                        $maxReturnable = $rItem['returnable_quantity'];
                                        $isExhausted = $maxReturnable <= 0;
                                    @endphp
                                    <tr class="{{ $isExhausted ? 'opacity-50 bg-slate-50' : '' }}">
                                        <td class="text-center font-numeric text-xs text-slate-400">{{ $idx + 1 }}</td>
                                        <td>
                                            <div class="font-bold text-xs text-slate-800">{{ $item->product->name }}</div>
                                            <div class="text-[10px] text-slate-400 font-numeric">كود: {{ $item->product->sku ?: '—' }}</div>
                                            <input type="hidden" name="items[{{ $idx }}][sales_invoice_item_id]" value="{{ $item->id }}">
                                        </td>
                                        <td>
                                            <span class="erp-badge erp-badge-neutral text-[10px]">{{ $item->unit->name ?? $item->product->unit ?? 'قطعة' }}</span>
                                        </td>
                                        <td class="text-center font-numeric text-xs font-semibold">{{ $sold }}</td>
                                        <td class="text-center font-numeric text-xs {{ $alreadyReturned > 0 ? 'text-amber-600 font-bold' : 'text-slate-400' }}">
                                            {{ $alreadyReturned }}
                                        </td>
                                        <td class="text-center font-numeric text-xs font-extrabold {{ $maxReturnable > 0 ? 'text-emerald-700' : 'text-rose-600' }}">
                                            {{ $maxReturnable }}
                                        </td>
                                        <td>
                                            @if($isExhausted)
                                                <div class="text-center text-[11px] text-slate-400">تم إرجاعه بالكامل</div>
                                                <input type="hidden" name="items[{{ $idx }}][quantity]" value="0">
                                            @else
                                                <input 
                                                    type="number" 
                                                    step="0.0001" 
                                                    min="0" 
                                                    max="{{ $maxReturnable }}" 
                                                    name="items[{{ $idx }}][quantity]" 
                                                    value="{{ old("items.{$idx}.quantity", 0) }}" 
                                                    class="erp-input text-center text-xs font-numeric font-bold return-qty-input w-full"
                                                    data-index="{{ $idx }}"
                                                    data-max="{{ $maxReturnable }}"
                                                >
                                            @endif
                                        </td>
                                        <td>
                                            <input 
                                                type="number" 
                                                step="0.01" 
                                                min="0" 
                                                name="items[{{ $idx }}][unit_refund_price]" 
                                                value="{{ old("items.{$idx}.unit_refund_price", $item->unit_price) }}" 
                                                class="erp-input text-center text-xs font-numeric font-bold return-price-input w-full"
                                                data-index="{{ $idx }}"
                                                {{ $isExhausted ? 'readonly' : '' }}
                                            >
                                        </td>
                                        <td class="text-center font-numeric font-bold text-xs text-rose-700 return-line-total" id="lineTotal_{{ $idx }}">
                                            0.00 ج.م
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Summary Bar and Submission -->
                <div class="erp-card p-4 border border-slate-200/80 bg-white shadow-xs flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="text-xs text-slate-500">
                            إجمالي قيمة المرتجع المستردة:
                            <span class="text-xl font-black font-numeric text-rose-700 mr-1" id="grandTotalRefund">0.00</span>
                            <span class="text-xs font-bold text-slate-400">ج.م</span>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 w-full sm:w-auto">
                        <button type="submit" name="action" value="draft" class="erp-btn erp-btn-secondary text-xs font-bold py-2.5 px-4 flex-1 sm:flex-none justify-center">
                            <x-icon name="file-text" size="14" />
                            <span>حفظ كمسودة</span>
                        </button>

                        <button type="submit" name="action" value="post" class="erp-btn erp-btn-primary text-xs font-bold py-2.5 px-5 flex-1 sm:flex-none justify-center">
                            <x-icon name="check-circle" size="14" />
                            <span>ترحيل واسترجاع للمخزن فوراً</span>
                        </button>
                    </div>
                </div>
            </form>
        @endif
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            function recalculateTotals() {
                let grandTotal = 0;
                document.querySelectorAll('.return-qty-input').forEach(function (qtyInput) {
                    const idx = qtyInput.dataset.index;
                    const max = parseFloat(qtyInput.dataset.max || 0);
                    let qty = parseFloat(qtyInput.value || 0);

                    if (qty > max) {
                        qty = max;
                        qtyInput.value = max;
                    }
                    if (qty < 0) {
                        qty = 0;
                        qtyInput.value = 0;
                    }

                    const priceInput = document.querySelector(`.return-price-input[data-index="${idx}"]`);
                    const price = parseFloat(priceInput ? priceInput.value : 0) || 0;
                    const lineTotal = qty * price;
                    grandTotal += lineTotal;

                    const lineCell = document.getElementById(`lineTotal_${idx}`);
                    if (lineCell) {
                        lineCell.textContent = lineTotal.toFixed(2) + ' ج.م';
                    }
                });

                const totalDisplay = document.getElementById('grandTotalRefund');
                if (totalDisplay) {
                    totalDisplay.textContent = grandTotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                }
            }

            document.querySelectorAll('.return-qty-input, .return-price-input').forEach(function (el) {
                el.addEventListener('input', recalculateTotals);
            });

            recalculateTotals();
        });
    </script>
    @endpush
@endsection
