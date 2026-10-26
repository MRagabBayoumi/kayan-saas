@extends('layouts.app')

@section('control_panel')
    <x-control-panel title="مدفوعات وسندات صرف الموردين" :breadcrumbs="['المالية والحسابات' => route('treasuries.index'), 'مدفوعات الموردين' => route('supplier-payments.index')]">
        <x-slot:actions>
            <button type="button" class="erp-btn erp-btn-primary" data-action="open-new-payment-modal">
                <x-icon name="plus" size="16" />
                <span>تسجيل سند صرف جديد</span>
            </button>
            <a href="{{ route('supplier-installments.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="calendar" size="16" />
                <span>جدول الأقساط والمستحقات</span>
                @if(($metrics['overdue_installments_count'] ?? 0) > 0)
                    <span class="inline-flex items-center justify-center px-1.5 py-0.5 text-[10px] font-bold text-white bg-rose-600 rounded-full mr-1">
                        {{ $metrics['overdue_installments_count'] }}
                    </span>
                @endif
            </a>
            <a href="{{ route('suppliers.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="truck" size="16" />
                <span>دليل الموردين</span>
            </a>
            <a href="{{ route('purchases.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="shopping-bag" size="16" />
                <span>فواتير المشتريات الآجلة</span>
            </a>
            <a href="{{ route('treasuries.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="vault" size="16" />
                <span>الخزائن والبنوك</span>
            </a>
        </x-slot:actions>

        <x-slot:extra>
            <span class="erp-badge erp-badge-neutral font-numeric px-3 py-1 text-xs">
                <span>إجمالي ما تم سداده:</span>
                <strong class="text-[var(--danger)] mr-1">{{ number_format($metrics['total_payments'], 2) }} ج.م</strong>
            </span>
        </x-slot:extra>
    </x-control-panel>
@endsection

@section('content')
    <div data-page="supplier-payments">
        @if(session('success'))
            <div class="erp-alert erp-alert-success mb-4" role="alert">
                <x-icon name="check-circle" size="18" />
                <div>{{ session('success') }}</div>
            </div>
        @endif

        <!-- KPI Cards Grid -->
        <div class="finance-kpi-grid">
            <div class="finance-kpi-card">
                <div class="finance-kpi-icon danger">
                    <x-icon name="credit-card" size="22" />
                </div>
                <div class="finance-kpi-info">
                    <div class="finance-kpi-label">إجمالي المدفوعات المسددة للموردين</div>
                    <div class="finance-kpi-value font-numeric text-[var(--danger)]">
                        {{ number_format($metrics['total_payments'], 2) }} <span class="text-xs font-normal text-[var(--text-muted)]">ج.م</span>
                    </div>
                    <div class="finance-kpi-sub">حسب نتائج التصفية الحالية</div>
                </div>
            </div>

            <div class="finance-kpi-card">
                <div class="finance-kpi-icon primary">
                    <x-icon name="receipt" size="22" />
                </div>
                <div class="finance-kpi-info">
                    <div class="finance-kpi-label">عدد سندات الصرف المسجلة</div>
                    <div class="finance-kpi-value font-numeric">
                        {{ $metrics['payments_count'] }}
                    </div>
                    <div class="finance-kpi-sub">سند صرف مسجل في النظام</div>
                </div>
            </div>

            <div class="finance-kpi-card">
                <div class="finance-kpi-icon warning">
                    <x-icon name="clock" size="22" />
                </div>
                <div class="finance-kpi-info">
                    <div class="finance-kpi-label">إجمالي المديونيات المتبقية للموردين</div>
                    <div class="finance-kpi-value font-numeric text-[var(--warning-text)]">
                        {{ number_format($metrics['total_payables'], 2) }} <span class="text-xs font-normal text-[var(--text-muted)]">ج.م</span>
                    </div>
                    <div class="finance-kpi-sub">مستحقات واجبة السداد</div>
                </div>
            </div>

            <div class="finance-kpi-card">
                <div class="finance-kpi-icon info">
                    <x-icon name="factory" size="22" />
                </div>
                <div class="finance-kpi-info">
                    <div class="finance-kpi-label">إجمالي الموردين والجهات</div>
                    <div class="finance-kpi-value font-numeric">
                        {{ $metrics['suppliers_count'] }}
                    </div>
                    <div class="finance-kpi-sub">مصانع وموردين ومكاتب شحن</div>
                </div>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="finance-filter-bar">
            <form method="GET" action="{{ route('supplier-payments.index') }}" class="finance-filter-inputs">
                <select name="supplier_id" class="finance-filter-input">
                    <option value="">-- كل الموردين --</option>
                    @foreach($suppliers as $sup)
                        <option value="{{ $sup->id }}" {{ request('supplier_id') == $sup->id ? 'selected' : '' }}>
                            {{ $sup->name }}
                        </option>
                    @endforeach
                </select>

                <select name="payment_method" class="finance-filter-input">
                    <option value="">-- كل طرق السداد --</option>
                    <option value="cash" {{ request('payment_method') === 'cash' ? 'selected' : '' }}>نقداً</option>
                    <option value="bank_transfer" {{ request('payment_method') === 'bank_transfer' ? 'selected' : '' }}>تحويل بنكي</option>
                    <option value="cheque" {{ request('payment_method') === 'cheque' ? 'selected' : '' }}>شيك</option>
                    <option value="card" {{ request('payment_method') === 'card' ? 'selected' : '' }}>بطاقة</option>
                </select>

                <input type="date" name="from_date" value="{{ request('from_date') }}" class="finance-filter-input font-numeric" placeholder="من تاريخ">
                <input type="date" name="to_date" value="{{ request('to_date') }}" class="finance-filter-input font-numeric" placeholder="إلى تاريخ">

                <button type="submit" class="erp-btn erp-btn-primary">
                    <x-icon name="filter" size="14" />
                    <span>تصفية</span>
                </button>

                @if(request()->hasAny(['supplier_id', 'payment_method', 'from_date', 'to_date']))
                    <a href="{{ route('supplier-payments.index') }}" class="erp-btn erp-btn-secondary" title="إلغاء التصفية">
                        <x-icon name="x" size="14" />
                    </a>
                @endif
            </form>
        </div>

        <!-- Payments Table Card -->
        <div class="erp-card">
            <div class="erp-card-header">
                <div class="erp-card-title">
                    <x-icon name="receipt" size="18" />
                    <span>سجل سندات الصرف للموردين</span>
                </div>
                <span class="text-xs text-[var(--text-muted)] font-numeric">إجمالي النتائج: {{ $payments->total() }}</span>
            </div>

            @if($payments->isEmpty())
                <div class="text-center py-12 text-[var(--text-muted)] text-sm">
                    لا توجد سندات صرف مسجلة مطابقة لخيارات البحث.
                </div>
            @else
                <div class="erp-table-responsive">
                    <table class="erp-table">
                        <thead>
                            <tr>
                                <th>رقم السند</th>
                                <th>التاريخ</th>
                                <th>المورد</th>
                                <th>الفاتورة المرتبطة</th>
                                <th>الخزينة / المصدر</th>
                                <th>طريقة الصرف</th>
                                <th>المبلغ المصروف</th>
                                <th>المرجع / ملاحظات</th>
                                <th>المسؤول</th>
                                <th style="text-align: center;">إجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($payments as $pay)
                                <tr class="erp-table-row">
                                    <td class="font-mono font-medium text-[var(--text-main)]">{{ $pay->payment_number }}</td>
                                    <td class="font-numeric text-[var(--text-secondary)]">{{ $pay->payment_date ? \Carbon\Carbon::parse($pay->payment_date)->format('Y-m-d') : '—' }}</td>
                                    <td class="font-bold">
                                        <a href="{{ route('suppliers.show', $pay->supplier_id) }}" class="text-[var(--primary)] hover:underline">
                                            {{ $pay->supplier?->name ?? '—' }}
                                        </a>
                                    </td>
                                    <td>
                                        @if($pay->purchaseInvoice)
                                            <a href="{{ route('purchases.show', $pay->purchase_invoice_id) }}" class="font-mono text-[var(--primary)] hover:underline font-bold block">
                                                {{ $pay->purchaseInvoice->invoice_number }}
                                            </a>
                                        @endif

                                        @if($pay->installment)
                                            <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-indigo-700 bg-indigo-50 px-1.5 py-0.5 rounded border border-indigo-200 mt-0.5">
                                                <x-icon name="calendar" size="11" />
                                                <span>{{ $pay->installment->installment_title }}</span>
                                            </span>
                                        @elseif(!$pay->purchaseInvoice)
                                            <span class="text-[var(--text-muted)] text-xs">دفعة عامة لحساب المورد</span>
                                        @endif
                                    </td>
                                    <td class="text-[var(--text-secondary)]">
                                        {{ $pay->treasury?->name ?? 'درج الكاشير' }}
                                    </td>
                                    <td>
                                        <span class="erp-badge {{ $pay->payment_method === 'cash' ? 'erp-badge-success' : ($pay->payment_method === 'bank_transfer' ? 'erp-badge-info' : 'erp-badge-warning') }}">
                                            {{ $pay->payment_method_label }}
                                        </span>
                                    </td>
                                    <td class="font-bold font-numeric text-[var(--danger)]">
                                        {{ number_format((float)$pay->amount, 2) }} ج.م
                                    </td>
                                    <td class="text-[var(--text-secondary)] max-w-xs truncate">
                                        {{ $pay->reference_number ? "مرجع: {$pay->reference_number}" : '' }}
                                        {{ $pay->notes ? "({$pay->notes})" : '' }}
                                        @if(!$pay->reference_number && !$pay->notes)
                                            —
                                        @endif
                                    </td>
                                    <td class="text-[var(--text-muted)]">{{ $pay->creator?->name ?? 'النظام' }}</td>
                                    <td style="text-align: center;">
                                        <div style="display: inline-flex; align-items: center; gap: 6px;">
                                            <a href="{{ route('supplier-payments.receipt', $pay) }}" target="_blank" class="erp-btn erp-btn-secondary text-xs py-1 px-2" title="طباعة سند الصرف">
                                                <x-icon name="printer" size="14" />
                                            </a>
                                            <form action="{{ route('supplier-payments.destroy', $pay) }}" method="POST" onsubmit="return confirm('هل أنت متأكد من إلغاء سند الصرف رقم {{ $pay->payment_number }}؟ سيتم استرجاع المبلغ للخزينة وإعادة المديونية للمورد.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="erp-btn erp-btn-danger text-xs py-1 px-2" title="إلغاء واسترداد">
                                                    <x-icon name="trash-2" size="14" />
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($payments->hasPages())
                    <div style="padding: 12px 16px; border-top: 1px solid var(--border-light);">
                        {{ $payments->links() }}
                    </div>
                @endif
            @endif
        </div>

        <!-- Modal: Record New Supplier Payment -->
        <div id="newSupplierPaymentModal" class="erp-modal-overlay">
            <div class="erp-modal-container max-w-lg">
                <div class="erp-modal-header">
                    <div class="erp-modal-title">
                        <x-icon name="credit-card" size="18" />
                        <span>تسجيل سند صرف دفعة لمورد</span>
                    </div>
                    <button type="button" data-action="close-new-payment-modal" class="erp-modal-close">✕</button>
                </div>

                <form id="newSupplierPaymentForm" method="POST">
                    @csrf
                    <div class="erp-modal-body p-5">
                        <!-- Supplier Picker -->
                        <div class="mb-3.5">
                            <label class="erp-label block mb-1.5 text-xs font-semibold">المورد / المصنع *</label>
                            <select id="nsp_supplier_id" class="erp-input w-full" required onchange="handleNspSupplierChange(this.value)">
                                <option value="">-- اختر المورد المسجل --</option>
                                @foreach($suppliers as $sup)
                                    <option value="{{ $sup->id }}">
                                        {{ $sup->name }} (مديونية: {{ number_format((float)$sup->current_balance, 2) }} {{ $sup->currency }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Supplier Balance Info Banner -->
                        <div id="nsp_supplier_info" class="p-3 mb-3.5 rounded-lg bg-sky-50/60 border border-sky-200" style="display: none;">
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-sky-800">إجمالي المديونية المستحقة الحالية:</span>
                                <strong id="nsp_supplier_balance" class="text-sm font-numeric text-rose-600"></strong>
                            </div>
                        </div>

                        <!-- Target Option: Installment or Invoice or General -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5 mb-3.5">
                            <div>
                                <label class="erp-label block mb-1.5 text-xs font-semibold">سداد على قسط محدد (اختياري)</label>
                                <select name="supplier_installment_id" id="nsp_installment_id" class="erp-input w-full" onchange="handleNspInstallmentChange(this)">
                                    <option value="">-- بدون قسط (سداد عام) --</option>
                                </select>
                            </div>
                            <div>
                                <label class="erp-label block mb-1.5 text-xs font-semibold">سداد على فاتورة محددة (اختياري)</label>
                                <select name="purchase_invoice_id" id="nsp_invoice_id" class="erp-input w-full" onchange="handleNspInvoiceChange(this)">
                                    <option value="">-- بدون فاتورة (سداد عام) --</option>
                                </select>
                            </div>
                        </div>

                        <!-- Amount & Treasury -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5 mb-3.5">
                            <div>
                                <label class="erp-label block mb-1.5 text-xs font-semibold">المبلغ المنصرف *</label>
                                <input type="number" step="0.01" min="0.01" name="amount" id="nsp_amount" class="erp-input font-numeric font-bold text-base text-[var(--danger)] w-full" required placeholder="0.00">
                            </div>
                            <div>
                                <label class="erp-label block mb-1.5 text-xs font-semibold">الخزينة / المصدر *</label>
                                <select name="treasury_id" class="erp-input w-full" required>
                                    @foreach($treasuries as $tr)
                                        <option value="{{ $tr->id }}">
                                            {{ $tr->name }} (رصيد: {{ number_format((float)$tr->current_balance, 2) }} ج.م)
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Date & Method -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5 mb-3.5">
                            <div>
                                <label class="erp-label block mb-1.5 text-xs font-semibold">تاريخ الصرف *</label>
                                <input type="date" name="payment_date" value="{{ date('Y-m-d') }}" class="erp-input font-numeric w-full" required>
                            </div>
                            <div>
                                <label class="erp-label block mb-1.5 text-xs font-semibold">طريقة الصرف *</label>
                                <select name="payment_method" class="erp-input w-full" required>
                                    <option value="cash" selected>نقداً من الخزينة / الدرج</option>
                                    <option value="bank_transfer">تحويل بنكي</option>
                                    <option value="cheque">شيك مصرفي</option>
                                    <option value="card">بطاقة دفع / فيزا</option>
                                </select>
                            </div>
                        </div>

                        <!-- Reference & Notes -->
                        <div class="mb-3.5">
                            <label class="erp-label block mb-1.5 text-xs font-semibold">رقم المرجع / الشيك (اختياري)</label>
                            <input type="text" name="reference_number" class="erp-input font-numeric w-full" placeholder="رقم الشيك أو الحوالة البنكية...">
                        </div>

                        <div class="mb-3.5">
                            <label class="erp-label block mb-1.5 text-xs font-semibold">ملاحظات وبيان الصرف</label>
                            <textarea name="notes" class="erp-input w-full" rows="2" placeholder="بيان الصرف..."></textarea>
                        </div>
                    </div>

                    <div class="erp-modal-footer p-4 flex justify-end gap-2.5 bg-[var(--bg-surface-alt)] border-t border-[var(--border-light)]">
                        <button type="button" data-action="close-new-payment-modal" class="erp-btn erp-btn-secondary">إلغاء</button>
                        <button type="submit" class="erp-btn erp-btn-success">تأكيد وصرف السند</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function handleNspSupplierChange(supplierId) {
            const form = document.getElementById('newSupplierPaymentForm');
            const infoBox = document.getElementById('nsp_supplier_info');
            const balEl = document.getElementById('nsp_supplier_balance');
            const instSelect = document.getElementById('nsp_installment_id');
            const invSelect = document.getElementById('nsp_invoice_id');
            const amountInput = document.getElementById('nsp_amount');

            instSelect.innerHTML = '<option value="">-- بدون قسط (سداد عام) --</option>';
            invSelect.innerHTML = '<option value="">-- بدون فاتورة (سداد عام) --</option>';

            if (!supplierId) {
                if (form) form.action = '';
                if (infoBox) infoBox.style.display = 'none';
                return;
            }

            if (form) {
                form.action = `/suppliers/${supplierId}/payments`;
            }

            fetch(`/suppliers/${supplierId}/payables-data`)
                .then(res => res.json())
                .then(data => {
                    if (!data.success) return;

                    if (infoBox && balEl) {
                        infoBox.style.display = 'block';
                        balEl.textContent = `${Number(data.supplier.current_balance).toLocaleString('ar-EG', {minimumFractionDigits: 2})} ${data.supplier.currency}`;
                    }

                    if (data.installments && data.installments.length > 0) {
                        data.installments.forEach(inst => {
                            const opt = document.createElement('option');
                            opt.value = inst.id;
                            opt.dataset.remaining = inst.remaining_amount;
                            opt.dataset.invoiceId = inst.purchase_invoice_id || '';
                            opt.textContent = `${inst.installment_title} (متبقي ${inst.remaining_amount} ج.م - ${inst.status_label})`;
                            instSelect.appendChild(opt);
                        });
                    }

                    if (data.invoices && data.invoices.length > 0) {
                        data.invoices.forEach(inv => {
                            const opt = document.createElement('option');
                            opt.value = inv.id;
                            opt.dataset.remaining = inv.remaining_amount;
                            opt.textContent = `فاتورة #${inv.invoice_number} (متبقي ${Number(inv.remaining_amount).toLocaleString('ar-EG', {minimumFractionDigits: 2})} ${inv.currency})`;
                            invSelect.appendChild(opt);
                        });
                    }
                })
                .catch(e => console.error('Error fetching supplier payables:', e));
        }

        function handleNspInstallmentChange(select) {
            const opt = select.selectedOptions[0];
            const amountInput = document.getElementById('nsp_amount');
            const invSelect = document.getElementById('nsp_invoice_id');

            if (opt && opt.dataset.remaining) {
                if (amountInput) {
                    amountInput.value = opt.dataset.remaining;
                    amountInput.max = opt.dataset.remaining;
                }
                if (opt.dataset.invoiceId && invSelect) {
                    invSelect.value = opt.dataset.invoiceId;
                }
            } else {
                if (amountInput) amountInput.removeAttribute('max');
            }
        }

        function handleNspInvoiceChange(select) {
            const opt = select.selectedOptions[0];
            const amountInput = document.getElementById('nsp_amount');

            if (opt && opt.dataset.remaining) {
                if (amountInput && (!amountInput.value || Number(amountInput.value) <= 0)) {
                    amountInput.value = opt.dataset.remaining;
                }
                if (amountInput) {
                    amountInput.max = opt.dataset.remaining;
                }
            } else {
                if (amountInput) amountInput.removeAttribute('max');
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('newSupplierPaymentModal');

            document.querySelectorAll('[data-action="open-new-payment-modal"]').forEach(btn => {
                btn.addEventListener('click', () => {
                    if (modal) {
                        modal.classList.add('open');
                        modal.style.display = 'flex';
                    }
                });
            });

            document.querySelectorAll('[data-action="close-new-payment-modal"]').forEach(btn => {
                btn.addEventListener('click', () => {
                    if (modal) {
                        modal.classList.remove('open');
                        modal.style.display = 'none';
                    }
                });
            });

            if (modal) {
                modal.addEventListener('click', (e) => {
                    if (e.target === modal) {
                        modal.classList.remove('open');
                        modal.style.display = 'none';
                    }
                });
            }
        });
    </script>
@endsection
