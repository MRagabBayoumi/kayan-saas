@extends('layouts.app')

@section('control_panel')
    <x-control-panel title="جدول مواعيد وأقساط تحصيل العملاء" :breadcrumbs="['دليل العملاء' => route('customers.index'), 'مقبوضات وسندات القبض' => route('sales.payments.index'), 'مواعيد التحصيل' => route('customer-installments.index')]">
        <x-slot:actions>
            <button type="button" class="erp-btn erp-btn-primary" data-action="open-add-installment">
                <x-icon name="plus" size="16" />
                <span>إضافة قسط / جدول تحصيل</span>
            </button>
            <a href="{{ route('sales.payments.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="receipt" size="16" />
                <span>سجل سندات القبض</span>
            </a>
            <a href="{{ route('customers.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="users" size="16" />
                <span>دليل العملاء</span>
            </a>
        </x-slot:actions>

        <x-slot:extra>
            @if($metrics['overdue_count'] > 0)
                <span class="erp-badge erp-badge-danger font-numeric px-3 py-1 text-xs animate-pulse">
                    <span>تنبيه: يوجد {{ $metrics['overdue_count'] }} أقساط متأخرة التحصيل</span>
                    <strong class="mr-1">({{ number_format($metrics['overdue_due'], 2) }} ج.م)</strong>
                </span>
            @endif
        </x-slot:extra>
    </x-control-panel>
@endsection

@section('content')
    <div data-page="customer-installments">
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
                    <div class="finance-kpi-label">أقساط متأخرة واجبة التحصيل فوراً</div>
                    <div class="finance-kpi-value font-numeric text-rose-600">
                        {{ number_format($metrics['overdue_due'], 2) }} <span class="text-xs font-normal text-[var(--text-muted)]">ج.م</span>
                    </div>
                    <div class="finance-kpi-sub font-numeric">{{ $metrics['overdue_count'] }} قسط تجاوز موعد استحقاقه</div>
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
                    <div class="finance-kpi-label">إجمالي الأقساط قيد التحصيل</div>
                    <div class="finance-kpi-value font-numeric text-[var(--primary)]">
                        {{ number_format($metrics['total_due'], 2) }} <span class="text-xs font-normal text-[var(--text-muted)]">ج.م</span>
                    </div>
                    <div class="finance-kpi-sub font-numeric">{{ $metrics['pending_count'] }} قسط قيد المتابعة</div>
                </div>
            </div>

            <div class="finance-kpi-card">
                <div class="finance-kpi-icon success">
                    <x-icon name="check-circle" size="22" />
                </div>
                <div class="finance-kpi-info">
                    <div class="finance-kpi-label">إجمالي ما تم تحصيله من العملاء</div>
                    <div class="finance-kpi-value font-numeric text-emerald-600">
                        {{ number_format($metrics['total_collected'], 2) }} <span class="text-xs font-normal text-[var(--text-muted)]">ج.م</span>
                    </div>
                    <div class="finance-kpi-sub font-numeric">{{ $metrics['paid_count'] }} قسط محصل بالكامل</div>
                </div>
            </div>
        </div>

        <!-- Filter & Search Bar -->
        <div class="erp-card mb-4 p-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <!-- Status Filter Pills -->
                <div class="flex flex-wrap items-center gap-1.5">
                    <a href="{{ route('customer-installments.index', array_merge(request()->except('status', 'page'), ['status' => 'pending'])) }}" 
                       class="erp-btn erp-btn-sm {{ $currentFilter === 'pending' ? 'erp-btn-primary' : 'erp-btn-secondary' }}">
                        <span>قيد التحصيل ({{ $metrics['pending_count'] }})</span>
                    </a>
                    <a href="{{ route('customer-installments.index', array_merge(request()->except('status', 'page'), ['status' => 'overdue'])) }}" 
                       class="erp-btn erp-btn-sm {{ $currentFilter === 'overdue' ? 'bg-rose-600 text-white hover:bg-rose-700' : 'erp-btn-secondary text-rose-600' }}">
                        <span>متأخرة ({{ $metrics['overdue_count'] }})</span>
                    </a>
                    <a href="{{ route('customer-installments.index', array_merge(request()->except('status', 'page'), ['status' => 'due_soon'])) }}" 
                       class="erp-btn erp-btn-sm {{ $currentFilter === 'due_soon' ? 'bg-amber-600 text-white hover:bg-amber-700' : 'erp-btn-secondary text-amber-600' }}">
                        <span>مستحقة خلال أسبوع ({{ $metrics['due_soon_count'] }})</span>
                    </a>
                    <a href="{{ route('customer-installments.index', array_merge(request()->except('status', 'page'), ['status' => 'paid'])) }}" 
                       class="erp-btn erp-btn-sm {{ $currentFilter === 'paid' ? 'bg-emerald-600 text-white hover:bg-emerald-700' : 'erp-btn-secondary text-emerald-600' }}">
                        <span>محصلة بالكامل ({{ $metrics['paid_count'] }})</span>
                    </a>
                    <a href="{{ route('customer-installments.index', array_merge(request()->except('status', 'page'), ['status' => 'all'])) }}" 
                       class="erp-btn erp-btn-sm {{ $currentFilter === 'all' ? 'erp-btn-primary' : 'erp-btn-secondary' }}">
                        <span>الكل</span>
                    </a>
                </div>

                <!-- Customer Filter Dropdown -->
                <form method="GET" action="{{ route('customer-installments.index') }}" class="flex items-center gap-2">
                    <input type="hidden" name="status" value="{{ $currentFilter }}">
                    <select name="customer_id" class="erp-input text-xs py-1.5" data-action="auto-submit-filter">
                        <option value="">-- كل العملاء --</option>
                        @foreach($customers as $c)
                            <option value="{{ $c->id }}" {{ $selectedCustomerId == $c->id ? 'selected' : '' }}>
                                {{ $c->name }} {{ $c->phone ? "({$c->phone})" : '' }}
                            </option>
                        @endforeach
                    </select>

                    @if($selectedCustomerId)
                        <a href="{{ route('customer-installments.index', ['status' => $currentFilter]) }}" class="erp-btn erp-btn-secondary erp-btn-sm" title="إلغاء تصفية العميل">
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
                    <span>جدول الأقساط ومواعيد التحصيل</span>
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
                                <th>العميل</th>
                                <th>عنوان القسط / البيان</th>
                                <th>الفاتورة المرتبطة</th>
                                <th>تاريخ الاستحقاق</th>
                                <th>قيمة القسط</th>
                                <th>المحصل</th>
                                <th>المتبقي</th>
                                <th>الحالة</th>
                                <th class="text-center">إجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($installments as $inst)
                                <tr class="erp-table-row {{ $inst->is_overdue ? 'bg-rose-50/20' : '' }}">
                                    <td class="font-bold">
                                        <a href="{{ route('customers.show', $inst->customer_id) }}" class="text-[var(--primary)] hover:underline flex items-center gap-1.5">
                                            <x-icon name="user" size="14" />
                                            <span>{{ $inst->customer?->name ?? '—' }}</span>
                                        </a>
                                        @if($inst->customer?->phone)
                                            <span class="text-[10px] text-[var(--text-muted)] block font-numeric font-normal">
                                                {{ $inst->customer->phone }}
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="font-semibold text-xs text-[var(--text-main)]">{{ $inst->installment_title }}</span>
                                        @if($inst->notes)
                                            <span class="block text-[11px] text-[var(--text-muted)] truncate max-w-xs">{{ $inst->notes }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($inst->salesInvoice)
                                            <a href="{{ route('sales.show', $inst->sales_invoice_id) }}" class="font-mono text-xs text-[var(--primary)] font-bold hover:underline">
                                                {{ $inst->salesInvoice->invoice_number }}
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
                                            <span class="text-[10px] text-amber-600 block font-semibold">مستحق التحصيل اليوم!</span>
                                        @elseif($inst->status !== 'paid')
                                            <span class="text-[10px] text-[var(--text-muted)] block font-numeric">خلال {{ $inst->due_date->diffForHumans(null, true) }}</span>
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
                                    <td class="text-center">
                                        <div class="inline-flex items-center gap-1.5">
                                            @if($inst->remaining_amount > 0)
                                                <button type="button" 
                                                        class="erp-btn erp-btn-success text-xs py-1 px-2.5 flex items-center gap-1"
                                                        data-action="collect-installment"
                                                        data-customer-id="{{ $inst->customer_id }}"
                                                        data-customer-name="{{ $inst->customer?->name }}"
                                                        data-installment-id="{{ $inst->id }}"
                                                        data-installment-title="{{ $inst->installment_title }}"
                                                        data-remaining="{{ (float)$inst->remaining_amount }}"
                                                        data-invoice-id="{{ $inst->sales_invoice_id }}"
                                                        title="تحصيل هذا القسط">
                                                    <x-icon name="banknote" size="13" />
                                                    <span>تحصيل دفعة</span>
                                                </button>
                                            @else
                                                <span class="text-xs text-emerald-600 font-bold px-2">✓ تم التحصيل</span>
                                            @endif

                                            @if((float)$inst->paid_amount <= 0)
                                                <form action="{{ route('customer-installments.destroy', $inst) }}" method="POST" class="inline" data-action="confirm-delete-installment">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="erp-btn erp-btn-ghost erp-btn-icon text-[var(--danger)] hover:bg-rose-50" title="حذف القسط المجدول">
                                                        <x-icon name="trash" size="13" />
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
                    <div class="p-4 border-t border-[var(--border-light)]">
                        {{ $installments->links() }}
                    </div>
                @endif
            @endif
        </div>

        <!-- Modal: Add Installment or Schedule -->
        <div id="addCustomerInstallmentModal" class="erp-modal-overlay">
            <div class="erp-modal-container max-w-xl">
                <div class="erp-modal-header">
                    <div class="erp-modal-title">
                        <x-icon name="calendar" size="18" />
                        <span>جدولة قسط أو خطة تحصيل للعميل</span>
                    </div>
                    <button type="button" data-action="close-add-installment" class="erp-modal-close" aria-label="إغلاق">✕</button>
                </div>

                <form id="addCustomerInstallmentForm" action="{{ route('customer-installments.store') }}" method="POST">
                    @csrf
                    <div class="erp-modal-body p-5">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5 mb-3.5">
                            <div>
                                <label class="erp-label block mb-1.5 text-xs font-semibold">العميل *</label>
                                <select id="ci_customer_id" name="customer_id" class="erp-input w-full" required>
                                    <option value="">-- اختر العميل --</option>
                                    @foreach($customers as $c)
                                        <option value="{{ $c->id }}" {{ $selectedCustomerId == $c->id ? 'selected' : '' }}>
                                            {{ $c->name }} (رصيد: {{ number_format((float)$c->current_balance, 2) }} ج.م)
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="erp-label block mb-1.5 text-xs font-semibold">ربط بفاتورة مبيعات محددة</label>
                                <select id="ci_invoice_id" name="sales_invoice_id" class="erp-input w-full">
                                    <option value="">-- تحصيل عام لحساب العميل (بدون فاتورة محددة) --</option>
                                </select>
                            </div>
                        </div>

                        <!-- Schedule vs Single Installment Options -->
                        <div class="p-3 mb-3.5 rounded-lg border border-[var(--border-light)] bg-[var(--bg-surface-alt)]">
                            <label class="erp-label block mb-2 text-xs font-semibold">طريقة الجدولة والتحصيل *</label>
                            <div class="flex items-center gap-6 text-xs">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" name="plan_type" value="single" checked data-action="toggle-ci-plan">
                                    <span class="font-bold text-[var(--text-main)]">قسط / دفعة واحدة محددة</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" name="plan_type" value="schedule" data-action="toggle-ci-plan">
                                    <span class="font-bold text-[var(--text-main)]">تقسيم على أقساط مجدولة (جدول أقساط)</span>
                                </label>
                            </div>
                        </div>

                        <!-- Single Installment Section -->
                        <div id="ci_single_section">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5 mb-3.5">
                                <div>
                                    <label class="erp-label block mb-1.5 text-xs font-semibold">بيان / عنوان القسط *</label>
                                    <input type="text" id="ci_inst_title" name="installment_title" class="erp-input w-full" placeholder="مثال: دفعة مؤجلة من فاتورة مبيعات" required>
                                </div>
                                <div>
                                    <label class="erp-label block mb-1.5 text-xs font-semibold">تاريخ الاستحقاق *</label>
                                    <input type="date" id="ci_due_date" name="due_date" value="{{ date('Y-m-d', strtotime('+7 days')) }}" class="erp-input font-numeric w-full" required>
                                </div>
                            </div>
                            <div class="mb-3.5">
                                <label class="erp-label block mb-1.5 text-xs font-semibold">مبلغ القسط المستحق (ج.م) *</label>
                                <input type="number" step="0.01" min="0.01" id="ci_inst_amount" name="amount" class="erp-input font-numeric font-bold text-[var(--primary)] w-full" placeholder="0.00" required>
                            </div>
                        </div>

                        <!-- Multiple Schedule Section -->
                        <div id="ci_multiple_section" class="hidden">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5 mb-3.5">
                                <div>
                                    <label class="erp-label block mb-1.5 text-xs font-semibold">إجمالي المبلغ المطلوب جدولته (ج.م) *</label>
                                    <input type="number" step="0.01" min="0.01" name="total_amount" class="erp-input font-numeric font-bold text-[var(--primary)] w-full" placeholder="0.00">
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
                            <textarea name="notes" class="erp-input w-full" rows="2" placeholder="ملاحظات وشروط التحصيل..."></textarea>
                        </div>
                    </div>

                    <div class="erp-modal-footer p-4 flex justify-end gap-2.5 bg-[var(--bg-surface-alt)] border-t border-[var(--border-light)]">
                        <button type="button" data-action="close-add-installment" class="erp-btn erp-btn-secondary">إلغاء</button>
                        <button type="submit" class="erp-btn erp-btn-primary">حفظ وتأكيد الجدولة</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal: Quick Collect Installment -->
        <div id="quickCollectModal" class="erp-modal-overlay">
            <div class="erp-modal-container max-w-lg">
                <div class="erp-modal-header">
                    <div class="erp-modal-title">
                        <x-icon name="banknote" size="18" />
                        <span>تسجيل سند قبض وتحصيل دفعة من العميل</span>
                    </div>
                    <button type="button" data-action="close-quick-collect" class="erp-modal-close" aria-label="إغلاق">✕</button>
                </div>

                <form id="quickCollectForm" method="POST">
                    @csrf
                    <div class="erp-modal-body p-5">
                        <div class="p-3 mb-4 rounded-lg bg-emerald-50/50 border border-emerald-200">
                            <div class="text-xs text-emerald-800">
                                <span>العميل: </span><strong id="qc_customer_name" class="text-sm"></strong>
                            </div>
                            <div class="text-xs text-emerald-700 mt-1" id="qc_target_info"></div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5 mb-3.5">
                            <div>
                                <label class="erp-label block mb-1.5 text-xs font-semibold">المبلغ المحصل (ج.م) *</label>
                                <input type="number" step="0.01" min="0.01" name="amount" id="qc_amount" class="erp-input font-numeric font-bold text-base text-emerald-600 w-full" required>
                            </div>
                            <div>
                                <label class="erp-label block mb-1.5 text-xs font-semibold">الخزينة / الدرج المستلم *</label>
                                <select name="treasury_id" class="erp-input w-full">
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
                                <label class="erp-label block mb-1.5 text-xs font-semibold">تاريخ التحصيل *</label>
                                <input type="date" name="payment_date" value="{{ date('Y-m-d') }}" class="erp-input font-numeric w-full" required>
                            </div>
                            <div>
                                <label class="erp-label block mb-1.5 text-xs font-semibold">طريقة التحصيل *</label>
                                <select name="payment_method" class="erp-input w-full" required>
                                    <option value="cash" selected>نقداً في الدرج / الخزينة</option>
                                    <option value="bank_transfer">تحويل بنكي / إنستاباي</option>
                                    <option value="cheque">شيك مصرفي</option>
                                    <option value="card">بطاقة دفع / فيزا</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3.5">
                            <label class="erp-label block mb-1.5 text-xs font-semibold">رقم المرجع / الشيك / الحوالة (اختياري)</label>
                            <input type="text" name="reference_number" class="erp-input font-numeric w-full" placeholder="رقم الشيك أو الحوالة...">
                        </div>

                        <div class="mb-3.5">
                            <label class="erp-label block mb-1.5 text-xs font-semibold">ملاحظات وبيان القبض</label>
                            <textarea name="notes" class="erp-input w-full" rows="2" placeholder="بيان التحصيل..."></textarea>
                        </div>
                    </div>

                    <div class="erp-modal-footer p-4 flex justify-end gap-2.5 bg-[var(--bg-surface-alt)] border-t border-[var(--border-light)]">
                        <button type="button" data-action="close-quick-collect" class="erp-btn erp-btn-secondary">إلغاء</button>
                        <button type="submit" class="erp-btn erp-btn-success">تأكيد وتسجيل سند القبض</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
