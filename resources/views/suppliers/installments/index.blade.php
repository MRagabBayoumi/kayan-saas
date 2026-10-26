@extends('layouts.app')

@section('control_panel')
    <x-control-panel title="جدول أقساط ومستحقات الموردين المجدولة" :breadcrumbs="['دليل الموردين' => route('suppliers.index'), 'مدفوعات وسندات الصرف' => route('supplier-payments.index'), 'أقساط الموردين' => route('supplier-installments.index')]">
        <x-slot:actions>
            <button type="button" class="erp-btn erp-btn-primary" data-action="open-add-installment">
                <x-icon name="plus" size="16" />
                <span>إضافة قسط / جدول أقساط</span>
            </button>
            <a href="{{ route('supplier-payments.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="receipt" size="16" />
                <span>سجل سندات الصرف</span>
            </a>
            <a href="{{ route('suppliers.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="truck" size="16" />
                <span>دليل الموردين</span>
            </a>
        </x-slot:actions>

        <x-slot:extra>
            @if($metrics['overdue_count'] > 0)
                <span class="erp-badge erp-badge-danger font-numeric px-3 py-1 text-xs animate-pulse">
                    <span>تنبيه: يوجد {{ $metrics['overdue_count'] }} أقساط متأخرة السداد</span>
                    <strong class="mr-1">({{ number_format($metrics['overdue_due'], 2) }} ج.م)</strong>
                </span>
            @endif
        </x-slot:extra>
    </x-control-panel>
@endsection

@section('content')
    <div data-page="supplier-installments">
        @if(session('success'))
            <div class="erp-alert erp-alert-success mb-4" role="alert">
                <x-icon name="check-circle" size="18" />
                <div>{{ session('success') }}</div>
            </div>
        @endif

        @if(session('error'))
            <div class="erp-alert erp-alert-danger mb-4" role="alert">
                <x-icon name="alert-triangle" size="18" />
                <div>{{ session('error') }}</div>
            </div>
        @endif

        <!-- KPI Cards Grid -->
        <div class="finance-kpi-grid">
            <div class="finance-kpi-card">
                <div class="finance-kpi-icon danger">
                    <x-icon name="alert-circle" size="22" />
                </div>
                <div class="finance-kpi-info">
                    <div class="finance-kpi-label">أقساط متأخرة واجبة السداد فوراً</div>
                    <div class="finance-kpi-value font-numeric text-rose-600">
                        {{ number_format($metrics['overdue_due'], 2) }} <span class="text-xs font-normal text-[var(--text-muted)]">ج.م</span>
                    </div>
                    <div class="finance-kpi-sub font-numeric">{{ $metrics['overdue_count'] }} قسط تجاوز تاريخ استحقاقه</div>
                </div>
            </div>

            <div class="finance-kpi-card">
                <div class="finance-kpi-icon warning">
                    <x-icon name="clock" size="22" />
                </div>
                <div class="finance-kpi-info">
                    <div class="finance-kpi-label">مستحقة قريباً (خلال 7 أيام)</div>
                    <div class="finance-kpi-value font-numeric text-amber-600">
                        {{ number_format($metrics['due_soon_due'], 2) }} <span class="text-xs font-normal text-[var(--text-muted)]">ج.م</span>
                    </div>
                    <div class="finance-kpi-sub font-numeric">{{ $metrics['due_soon_count'] }} قسط يستحق هذا الأسبوع</div>
                </div>
            </div>

            <div class="finance-kpi-card">
                <div class="finance-kpi-icon primary">
                    <x-icon name="calendar" size="22" />
                </div>
                <div class="finance-kpi-info">
                    <div class="finance-kpi-label">إجمالي الأقساط المجدولة المتبقية</div>
                    <div class="finance-kpi-value font-numeric text-[var(--primary)]">
                        {{ number_format($metrics['total_due'], 2) }} <span class="text-xs font-normal text-[var(--text-muted)]">ج.م</span>
                    </div>
                    <div class="finance-kpi-sub font-numeric">{{ $metrics['pending_count'] }} قسط قيد الانتظار</div>
                </div>
            </div>

            <div class="finance-kpi-card">
                <div class="finance-kpi-icon success">
                    <x-icon name="check-circle" size="22" />
                </div>
                <div class="finance-kpi-info">
                    <div class="finance-kpi-label">إجمالي الأقساط المسددة للموردين</div>
                    <div class="finance-kpi-value font-numeric text-emerald-600">
                        {{ number_format($metrics['total_paid'], 2) }} <span class="text-xs font-normal text-[var(--text-muted)]">ج.م</span>
                    </div>
                    <div class="finance-kpi-sub font-numeric">{{ $metrics['paid_count'] }} قسط مسدد بالكامل</div>
                </div>
            </div>
        </div>

        <!-- Filter & Search Bar -->
        <div class="erp-card mb-4 p-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <!-- Status Filter Pills -->
                <div class="flex flex-wrap items-center gap-1.5">
                    <a href="{{ route('supplier-installments.index', array_merge(request()->except('status', 'page'), ['status' => 'pending'])) }}" 
                       class="erp-btn erp-btn-sm {{ $currentFilter === 'pending' ? 'erp-btn-primary' : 'erp-btn-secondary' }}">
                        <span>قيد الانتظار ({{ $metrics['pending_count'] }})</span>
                    </a>
                    <a href="{{ route('supplier-installments.index', array_merge(request()->except('status', 'page'), ['status' => 'overdue'])) }}" 
                       class="erp-btn erp-btn-sm {{ $currentFilter === 'overdue' ? 'bg-rose-600 text-white hover:bg-rose-700' : 'erp-btn-secondary text-rose-600' }}">
                        <span>متأخرة ({{ $metrics['overdue_count'] }})</span>
                    </a>
                    <a href="{{ route('supplier-installments.index', array_merge(request()->except('status', 'page'), ['status' => 'due_soon'])) }}" 
                       class="erp-btn erp-btn-sm {{ $currentFilter === 'due_soon' ? 'bg-amber-600 text-white hover:bg-amber-700' : 'erp-btn-secondary text-amber-600' }}">
                        <span>مستحقة خلال أسبوع ({{ $metrics['due_soon_count'] }})</span>
                    </a>
                    <a href="{{ route('supplier-installments.index', array_merge(request()->except('status', 'page'), ['status' => 'paid'])) }}" 
                       class="erp-btn erp-btn-sm {{ $currentFilter === 'paid' ? 'bg-emerald-600 text-white hover:bg-emerald-700' : 'erp-btn-secondary text-emerald-600' }}">
                        <span>مسددة بالكامل ({{ $metrics['paid_count'] }})</span>
                    </a>
                    <a href="{{ route('supplier-installments.index', array_merge(request()->except('status', 'page'), ['status' => 'all'])) }}" 
                       class="erp-btn erp-btn-sm {{ $currentFilter === 'all' ? 'erp-btn-primary' : 'erp-btn-secondary' }}">
                        <span>الكل</span>
                    </a>
                </div>

                <!-- Supplier Filter Dropdown -->
                <form method="GET" action="{{ route('supplier-installments.index') }}" class="flex items-center gap-2">
                    <input type="hidden" name="status" value="{{ $currentFilter }}">
                    <select name="supplier_id" class="erp-input text-xs py-1.5" onchange="this.form.submit()">
                        <option value="">-- كل الموردين --</option>
                        @foreach($suppliers as $sup)
                            <option value="{{ $sup->id }}" {{ $selectedSupplierId == $sup->id ? 'selected' : '' }}>
                                {{ $sup->name }}
                            </option>
                        @endforeach
                    </select>

                    @if($selectedSupplierId)
                        <a href="{{ route('supplier-installments.index', ['status' => $currentFilter]) }}" class="erp-btn erp-btn-secondary erp-btn-sm" title="إلغاء تصفية المورد">
                            <x-icon name="x" size="14" />
                        </a>
                    @endif
                </form>
            </div>
        </div>

        <!-- Installments Table Card -->
        <div class="erp-card">
            <div class="erp-card-header">
                <div class="erp-card-title">
                    <x-icon name="calendar" size="18" />
                    <span>جدول الأقساط والدفعات المستحقة</span>
                </div>
                <span class="text-xs text-[var(--text-muted)] font-numeric">إجمالي النتائج: {{ $installments->total() }}</span>
            </div>

            @if($installments->isEmpty())
                <div class="text-center py-12 text-[var(--text-muted)] text-sm">
                    لا توجد أقساط مجدولة مطابقة للخيارات المحددة.
                </div>
            @else
                <div class="erp-table-responsive">
                    <table class="erp-table">
                        <thead>
                            <tr>
                                <th>المورد</th>
                                <th>عنوان القسط / البيان</th>
                                <th>الفاتورة المرتبطة</th>
                                <th>تاريخ الاستحقاق</th>
                                <th>قيمة القسط</th>
                                <th>المسدد</th>
                                <th>المتبقي</th>
                                <th>الحالة</th>
                                <th style="text-align: center;">إجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($installments as $inst)
                                <tr class="erp-table-row {{ $inst->is_overdue ? 'bg-rose-50/20' : '' }}">
                                    <td class="font-bold">
                                        <a href="{{ route('suppliers.show', $inst->supplier_id) }}" class="text-[var(--primary)] hover:underline flex items-center gap-1.5">
                                            <x-icon name="truck" size="14" />
                                            <span>{{ $inst->supplier?->name ?? '—' }}</span>
                                        </a>
                                        <span class="text-[10px] text-[var(--text-muted)] block font-normal">
                                            {{ $inst->supplier?->supplier_type_label }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="font-semibold text-xs text-[var(--text-main)]">{{ $inst->installment_title }}</span>
                                        @if($inst->notes)
                                            <span class="block text-[11px] text-[var(--text-muted)] truncate max-w-xs">{{ $inst->notes }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($inst->purchaseInvoice)
                                            <a href="{{ route('purchases.show', $inst->purchase_invoice_id) }}" class="font-mono text-xs text-[var(--primary)] font-bold hover:underline">
                                                {{ $inst->purchaseInvoice->invoice_number }}
                                            </a>
                                        @else
                                            <span class="text-[var(--text-muted)] text-xs">حساب عام</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="font-numeric font-medium text-xs {{ $inst->is_overdue ? 'text-rose-600 font-bold' : ($inst->is_due_today ? 'text-amber-600 font-bold' : 'text-[var(--text-secondary)]') }}">
                                            {{ $inst->due_date ? $inst->due_date->format('Y-m-d') : '—' }}
                                        </div>
                                        @if($inst->is_overdue)
                                            <span class="text-[10px] text-rose-600 block font-semibold">متأخر {{ $inst->due_date->diffForHumans() }}</span>
                                        @elseif($inst->is_due_today)
                                            <span class="text-[10px] text-amber-600 block font-semibold">مستحق اليوم!</span>
                                        @elseif($inst->status !== 'paid')
                                            <span class="text-[10px] text-[var(--text-muted)] block">خلال {{ $inst->due_date->diffForHumans(null, true) }}</span>
                                        @endif
                                    </td>
                                    <td class="font-numeric font-bold text-xs text-[var(--text-main)]">
                                        {{ number_format((float)$inst->amount, 2) }} ج.م
                                    </td>
                                    <td class="font-numeric text-xs text-emerald-600 font-semibold">
                                        {{ number_format((float)$inst->paid_amount, 2) }} ج.م
                                    </td>
                                    <td class="font-numeric font-bold text-xs {{ $inst->remaining_amount > 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                                        {{ number_format((float)$inst->remaining_amount, 2) }} ج.م
                                    </td>
                                    <td>
                                        <span class="erp-badge {{ $inst->status_badge_class }} text-xs">
                                            {{ $inst->status_label }}
                                        </span>
                                    </td>
                                    <td style="text-align: center;">
                                        <div style="display: inline-flex; align-items: center; gap: 6px;">
                                            @if($inst->remaining_amount > 0)
                                                <button type="button" 
                                                        class="erp-btn erp-btn-success text-xs py-1 px-2.5 flex items-center gap-1"
                                                        data-action="pay-installment"
                                                        data-supplier-id="{{ $inst->supplier_id }}"
                                                        data-supplier-name="{{ $inst->supplier?->name }}"
                                                        data-installment-id="{{ $inst->id }}"
                                                        data-installment-title="{{ $inst->installment_title }}"
                                                        data-remaining="{{ (float)$inst->remaining_amount }}"
                                                        data-invoice-id="{{ $inst->purchase_invoice_id }}"
                                                        title="سداد هذا القسط">
                                                    <x-icon name="credit-card" size="13" />
                                                    <span>صرف دفعة</span>
                                                </button>
                                            @else
                                                <span class="text-xs text-emerald-600 font-bold px-2">✓ تم السداد</span>
                                            @endif

                                            @if((float)$inst->paid_amount <= 0)
                                                <form action="{{ route('supplier-installments.destroy', $inst) }}" method="POST" onsubmit="return confirm('هل أنت متأكد من حذف هذا القسط المجدول؟');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="erp-btn erp-btn-danger text-xs py-1 px-2" title="حذف القسط">
                                                        <x-icon name="trash-2" size="13" />
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($installments->hasPages())
                    <div style="padding: 12px 16px; border-top: 1px solid var(--border-light);">
                        {{ $installments->links() }}
                    </div>
                @endif
            @endif
        </div>

        <!-- Modal: Add Installment or Schedule -->
        <div id="addInstallmentModal" class="erp-modal-overlay">
            <div class="erp-modal-container max-w-lg">
                <div class="erp-modal-header">
                    <div class="erp-modal-title">
                        <x-icon name="calendar-plus" size="18" />
                        <span>جدولة قسط أو خطة سداد لمورد</span>
                    </div>
                    <button type="button" data-action="close-add-installment" class="erp-modal-close">✕</button>
                </div>

                <form action="{{ route('supplier-installments.store') }}" method="POST">
                    @csrf
                    <div class="erp-modal-body p-5">
                        <!-- Mode Selector Tabs -->
                        <div class="flex items-center gap-2 mb-4 p-1 bg-[var(--bg-surface-alt)] rounded-lg border border-[var(--border-light)]">
                            <label class="flex-1 text-center py-1.5 px-3 rounded cursor-pointer text-xs font-bold transition has-[:checked]:bg-[var(--primary)] has-[:checked]:text-white">
                                <input type="radio" name="plan_type" value="single" class="sr-only" checked onchange="togglePlanType('single')">
                                <span>قسط مفرد محدد</span>
                            </label>
                            <label class="flex-1 text-center py-1.5 px-3 rounded cursor-pointer text-xs font-bold transition has-[:checked]:bg-[var(--primary)] has-[:checked]:text-white">
                                <input type="radio" name="plan_type" value="schedule" class="sr-only" onchange="togglePlanType('schedule')">
                                <span>جدولة خطة أقساط متعددة</span>
                            </label>
                        </div>

                        <!-- Supplier Selection -->
                        <div class="mb-3.5">
                            <label class="erp-label block mb-1.5 text-xs font-semibold">المورد / المصنع *</label>
                            <select name="supplier_id" id="inst_supplier_id" class="erp-input w-full" required onchange="loadSupplierInvoices(this.value)">
                                <option value="">-- اختر المورد --</option>
                                @foreach($suppliers as $sup)
                                    <option value="{{ $sup->id }}" data-balance="{{ (float)$sup->current_balance }}">
                                        {{ $sup->name }} (مديونية: {{ number_format((float)$sup->current_balance, 2) }} {{ $sup->currency }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Linked Invoice (Optional) -->
                        <div class="mb-3.5">
                            <label class="erp-label block mb-1.5 text-xs font-semibold">الفاتورة المرتبطة (اختياري)</label>
                            <select name="purchase_invoice_id" id="inst_invoice_id" class="erp-input w-full">
                                <option value="">-- سداد عام لحساب المورد (بدون فاتورة محددة) --</option>
                            </select>
                        </div>

                        <!-- Single Installment Section -->
                        <div id="single_installment_section">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5 mb-3.5">
                                <div>
                                    <label class="erp-label block mb-1.5 text-xs font-semibold">عنوان / بيان القسط *</label>
                                    <input type="text" name="installment_title" id="inst_title" class="erp-input w-full" placeholder="مثال: دفعة استلام الحاوية الأولى">
                                </div>
                                <div>
                                    <label class="erp-label block mb-1.5 text-xs font-semibold">مبلغ القسط *</label>
                                    <input type="number" step="0.01" min="0.01" name="amount" id="inst_amount" class="erp-input font-numeric font-bold w-full" placeholder="0.00">
                                </div>
                            </div>

                            <div class="mb-3.5">
                                <label class="erp-label block mb-1.5 text-xs font-semibold">تاريخ الاستحقاق *</label>
                                <input type="date" name="due_date" id="inst_due_date" value="{{ date('Y-m-d', strtotime('+7 days')) }}" class="erp-input font-numeric w-full">
                            </div>
                        </div>

                        <!-- Multiple Schedule Section -->
                        <div id="multiple_schedule_section" style="display: none;">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5 mb-3.5">
                                <div>
                                    <label class="erp-label block mb-1.5 text-xs font-semibold">إجمالي المبلغ المراد توزيعه *</label>
                                    <input type="number" step="0.01" min="0.01" name="total_amount" id="inst_total_amount" class="erp-input font-numeric font-bold w-full" placeholder="0.00">
                                </div>
                                <div>
                                    <label class="erp-label block mb-1.5 text-xs font-semibold">عدد الأقساط *</label>
                                    <input type="number" min="2" max="36" name="installments_count" value="3" class="erp-input font-numeric w-full">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5 mb-3.5">
                                <div>
                                    <label class="erp-label block mb-1.5 text-xs font-semibold">تاريخ أول قسط *</label>
                                    <input type="date" name="first_due_date" value="{{ date('Y-m-d', strtotime('+14 days')) }}" class="erp-input font-numeric w-full">
                                </div>
                                <div>
                                    <label class="erp-label block mb-1.5 text-xs font-semibold">الدورية والتكرار *</label>
                                    <select name="interval" class="erp-input w-full">
                                        <option value="monthly" selected>شهرياً (كل شهر)</option>
                                        <option value="biweekly">كل أسبوعين</option>
                                        <option value="weekly">أسبوعياً</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3.5">
                            <label class="erp-label block mb-1.5 text-xs font-semibold">ملاحظات إضافية</label>
                            <textarea name="notes" class="erp-input w-full" rows="2" placeholder="ملاحظات وشروط استحقاق الدفعة..."></textarea>
                        </div>
                    </div>

                    <div class="erp-modal-footer p-4 flex justify-end gap-2.5 bg-[var(--bg-surface-alt)] border-t border-[var(--border-light)]">
                        <button type="button" data-action="close-add-installment" class="erp-btn erp-btn-secondary">إلغاء</button>
                        <button type="submit" class="erp-btn erp-btn-primary">حفظ وتأكيد الجدولة</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal: Quick Pay Installment -->
        <div id="quickPayModal" class="erp-modal-overlay">
            <div class="erp-modal-container max-w-lg">
                <div class="erp-modal-header">
                    <div class="erp-modal-title">
                        <x-icon name="credit-card" size="18" />
                        <span>تسجيل سند صرف دفعة للمورد</span>
                    </div>
                    <button type="button" data-action="close-quick-pay" class="erp-modal-close">✕</button>
                </div>

                <form id="quickPayForm" method="POST">
                    @csrf
                    <input type="hidden" name="supplier_installment_id" id="qp_installment_id">
                    <input type="hidden" name="purchase_invoice_id" id="qp_invoice_id">

                    <div class="erp-modal-body p-5">
                        <div class="p-3 mb-4 rounded-lg bg-sky-50/50 border border-sky-200">
                            <div class="text-xs text-sky-800">
                                <span>المورد: </span><strong id="qp_supplier_name" class="text-sm"></strong>
                            </div>
                            <div class="text-xs text-sky-700 mt-1" id="qp_target_info"></div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5 mb-3.5">
                            <div>
                                <label class="erp-label block mb-1.5 text-xs font-semibold">المبلغ المنصرف *</label>
                                <input type="number" step="0.01" min="0.01" name="amount" id="qp_amount" class="erp-input font-numeric font-bold text-base text-[var(--danger)] w-full" required>
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
                        <button type="button" data-action="close-quick-pay" class="erp-btn erp-btn-secondary">إلغاء</button>
                        <button type="submit" class="erp-btn erp-btn-success">تأكيد وصرف السند</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function togglePlanType(type) {
            const single = document.getElementById('single_installment_section');
            const multiple = document.getElementById('multiple_schedule_section');
            const instTitle = document.getElementById('inst_title');
            const instAmount = document.getElementById('inst_amount');

            if (type === 'schedule') {
                if (single) single.style.display = 'none';
                if (multiple) multiple.style.display = 'block';
                if (instTitle) instTitle.removeAttribute('required');
                if (instAmount) instAmount.removeAttribute('required');
            } else {
                if (single) single.style.display = 'block';
                if (multiple) multiple.style.display = 'none';
                if (instTitle) instTitle.setAttribute('required', 'required');
                if (instAmount) instAmount.setAttribute('required', 'required');
            }
        }

        function loadSupplierInvoices(supplierId) {
            const invoiceSelect = document.getElementById('inst_invoice_id');
            if (!invoiceSelect) return;
            invoiceSelect.innerHTML = '<option value="">-- سداد عام لحساب المورد (بدون فاتورة محددة) --</option>';

            if (!supplierId) return;

            fetch(`/suppliers/${supplierId}/payables-data`)
                .then(res => res.json())
                .then(data => {
                    if (data.success && data.invoices) {
                        data.invoices.forEach(inv => {
                            const opt = document.createElement('option');
                            opt.value = inv.id;
                            opt.textContent = `فاتورة #${inv.invoice_number} (تاريخ ${inv.invoice_date} - متبقي ${Number(inv.remaining_amount).toLocaleString('ar-EG', {minimumFractionDigits: 2})} ${inv.currency})`;
                            invoiceSelect.appendChild(opt);
                        });
                    }
                })
                .catch(e => console.error('Error fetching invoices:', e));
        }

        document.addEventListener('DOMContentLoaded', function() {
            const addModal = document.getElementById('addInstallmentModal');
            const payModal = document.getElementById('quickPayModal');

            // Open/Close Add Modal
            document.querySelectorAll('[data-action="open-add-installment"]').forEach(btn => {
                btn.addEventListener('click', () => {
                    if (addModal) {
                        addModal.classList.add('open');
                        addModal.style.display = 'flex';
                    }
                });
            });

            document.querySelectorAll('[data-action="close-add-installment"]').forEach(btn => {
                btn.addEventListener('click', () => {
                    if (addModal) {
                        addModal.classList.remove('open');
                        addModal.style.display = 'none';
                    }
                });
            });

            // Open/Close Quick Pay Modal
            document.querySelectorAll('[data-action="pay-installment"]').forEach(btn => {
                btn.addEventListener('click', () => {
                    const supId = btn.dataset.supplierId;
                    const supName = btn.dataset.supplierName;
                    const instId = btn.dataset.installmentId;
                    const instTitle = btn.dataset.installmentTitle;
                    const remaining = btn.dataset.remaining;
                    const invId = btn.dataset.invoiceId;

                    const form = document.getElementById('quickPayForm');
                    if (form) {
                        form.action = `/suppliers/${supId}/payments`;
                    }

                    document.getElementById('qp_supplier_name').textContent = supName;
                    document.getElementById('qp_installment_id').value = instId;
                    document.getElementById('qp_invoice_id').value = invId || '';
                    document.getElementById('qp_amount').value = remaining;
                    document.getElementById('qp_amount').max = remaining;
                    document.getElementById('qp_target_info').textContent = `سداد قسط: "${instTitle}" بمبلغ متبقي ${remaining} ج.م`;

                    if (payModal) {
                        payModal.classList.add('open');
                        payModal.style.display = 'flex';
                    }
                });
            });

            document.querySelectorAll('[data-action="close-quick-pay"]').forEach(btn => {
                btn.addEventListener('click', () => {
                    if (payModal) {
                        payModal.classList.remove('open');
                        payModal.style.display = 'none';
                    }
                });
            });

            // Backdrop click dismiss
            [addModal, payModal].forEach(m => {
                if (m) {
                    m.addEventListener('click', (e) => {
                        if (e.target === m) {
                            m.classList.remove('open');
                            m.style.display = 'none';
                        }
                    });
                }
            });
        });
    </script>
@endsection
