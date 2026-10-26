@extends('layouts.app')

@section('control_panel')
    <x-control-panel :title="$supplier->name" :breadcrumbs="['العملاء والموردون' => route('suppliers.index'), 'دليل الموردين' => route('suppliers.index'), $supplier->name => route('suppliers.show', $supplier)]">
        <x-slot:actions>
            <a href="{{ route('suppliers.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="arrow-right" size="16" />
                <span>العودة للموردين</span>
            </a>
            <button type="button" onclick="document.getElementById('recordSupplierPaymentModal').style.display='flex'" class="erp-btn erp-btn-success">
                <x-icon name="credit-card" size="16" />
                <span>تسجيل دفعة / سند صرف</span>
            </button>
            <a href="{{ route('suppliers.statement', $supplier) }}" class="erp-btn erp-btn-primary">
                <x-icon name="file-text" size="16" />
                <span>كشف الحساب المالي</span>
            </a>

            <!-- Compact Print Dropdown -->
            <div class="erp-dropdown">
                <button type="button" class="erp-btn erp-btn-secondary" data-dropdown-toggle="supplierShowPrintMenu">
                    <x-icon name="printer" size="16" />
                    <span>طباعة</span>
                    <x-icon name="chevron-down" size="14" />
                </button>
                <div id="supplierShowPrintMenu" class="erp-dropdown-menu">
                    <a href="{{ route('suppliers.statement.print', [$supplier, 'format' => 'a4']) }}" target="_blank" class="erp-dropdown-item">
                        <x-icon name="file-text" size="16" />
                        <span>كشف حساب A4 قياسي</span>
                    </a>
                    <a href="{{ route('suppliers.statement.print', [$supplier, 'format' => 'a5']) }}" target="_blank" class="erp-dropdown-item">
                        <x-icon name="file" size="16" />
                        <span>كشف حساب A5 وسط</span>
                    </a>
                    <a href="{{ route('suppliers.statement.print', [$supplier, 'format' => 'thermal_80mm']) }}" target="_blank" class="erp-dropdown-item">
                        <x-icon name="printer" size="16" />
                        <span>كشف حساب 80 مم حراري</span>
                    </a>
                </div>
            </div>

            @if($supplier->phone)
                <a href="tel:{{ $supplier->phone }}" class="erp-btn erp-btn-secondary">
                    <x-icon name="phone" size="16" />
                    <span>اتصال</span>
                </a>
            @endif
        </x-slot:actions>

        <x-slot:extra>
            <span class="erp-badge {{ $supplier->is_active ? 'erp-badge-success' : 'erp-badge-danger' }} text-xs">
                {{ $supplier->is_active ? 'حساب نشط' : 'حساب معطل' }}
            </span>
        </x-slot:extra>
    </x-control-panel>
@endsection

@section('content')
    <div data-page="supplier-show">
        @if(session('success'))
            <div class="erp-alert erp-alert-success mb-4" role="alert">
                <x-icon name="check" size="16" />
                <div>{{ session('success') }}</div>
            </div>
        @endif

        <!-- Supplier Dossier Card -->
        <div class="supplier-profile-card">
            <div class="supplier-profile-header">
                <div>
                    <h2 class="supplier-profile-title">{{ $supplier->name }}</h2>
                    <div class="supplier-profile-sub flex items-center gap-2">
                        <span>كود المورد: #{{ $supplier->id }}</span>
                        <span>•</span>
                        @if($supplier->supplier_type === 'china_factory')
                            <span class="erp-badge erp-badge-danger text-xs">مصنع بالصين</span>
                        @elseif($supplier->supplier_type === 'shipping_agent')
                            <span class="erp-badge erp-badge-warning text-xs">وكيل شحن وتخليص</span>
                        @else
                            <span class="erp-badge erp-badge-info text-xs">مورد محلي</span>
                        @endif
                        <span>•</span>
                        <span class="font-bold text-[var(--text-main)]">{{ $supplier->country ?: 'China' }}</span>
                    </div>
                </div>

                <div class="text-start md:text-end">
                    <div class="text-xs text-[var(--text-muted)] font-semibold mb-1">الرصيد المالي المعلق</div>
                    <div class="font-numeric font-black text-2xl">
                        @if($supplier->current_balance != 0)
                            <span class="{{ $supplier->current_balance > 0 ? 'text-[var(--warning)]' : 'text-[var(--success)]' }}">
                                {{ number_format($supplier->current_balance, 2) }} {{ $supplier->currency }}
                            </span>
                        @else
                            <span class="text-[var(--text-muted)]">0.00 {{ $supplier->currency }}</span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Details Grid -->
            <div class="supplier-details-grid">
                <div class="supplier-detail-item">
                    <div class="supplier-detail-label">المسؤول للتواصل</div>
                    <div class="supplier-detail-val">
                        {{ $supplier->contact_person ?: 'غير محدد' }}
                    </div>
                </div>

                <div class="supplier-detail-item">
                    <div class="supplier-detail-label">معرف WeChat</div>
                    <div class="supplier-detail-val font-numeric">
                        @if($supplier->wechat_id)
                            <span class="supplier-wechat-tag">
                                <x-icon name="smartphone" size="12" />
                                <span>{{ $supplier->wechat_id }}</span>
                            </span>
                        @else
                            <span>—</span>
                        @endif
                    </div>
                </div>

                <div class="supplier-detail-item">
                    <div class="supplier-detail-label">الهاتف / الواتساب</div>
                    <div class="supplier-detail-val font-numeric">
                        @if($supplier->phone)
                            <a href="tel:{{ $supplier->phone }}" class="text-[var(--primary)] hover:underline direction-ltr inline-block">
                                {{ $supplier->phone }}
                            </a>
                        @else
                            <span>—</span>
                        @endif
                    </div>
                </div>

                <div class="supplier-detail-item">
                    <div class="supplier-detail-label">البريد الإلكتروني</div>
                    <div class="supplier-detail-val font-numeric text-xs">
                        @if($supplier->email)
                            <a href="mailto:{{ $supplier->email }}" class="text-[var(--primary)] hover:underline direction-ltr inline-block">
                                {{ $supplier->email }}
                            </a>
                        @else
                            <span>—</span>
                        @endif
                    </div>
                </div>

                <div class="supplier-detail-item">
                    <div class="supplier-detail-label">المدينة / المقاطعة</div>
                    <div class="supplier-detail-val">
                        {{ $supplier->city ?: '—' }}
                    </div>
                </div>

                <div class="supplier-detail-item">
                    <div class="supplier-detail-label">عملة الحساب</div>
                    <div class="supplier-detail-val font-numeric">
                        {{ $supplier->currency }}
                    </div>
                </div>
            </div>

            @if($supplier->address)
                <div class="mt-4 p-3 bg-[var(--bg-surface-alt)] border border-[var(--border-light)] rounded">
                    <div class="text-xs font-bold text-[var(--text-secondary)] mb-1">العنوان التفصيلي وموقع المصنع:</div>
                    <div class="text-sm text-[var(--text-main)]">{{ $supplier->address }}</div>
                </div>
            @endif

            @if($supplier->notes)
                <div class="mt-3 p-3 bg-[var(--bg-surface-alt)] border border-[var(--border-light)] rounded">
                    <div class="text-xs font-bold text-[var(--text-secondary)] mb-1">ملاحظات وشروط التعامل:</div>
                    <div class="text-sm text-[var(--text-main)]">{{ $supplier->notes }}</div>
                </div>
            @endif
        </div>

        <!-- Supplier Invoices & Payables Ledger -->
        <div class="erp-card mt-6">
            <div class="erp-card-header flex items-center justify-between">
                <div class="erp-card-title flex items-center gap-2">
                    <x-icon name="shopping-bag" size="18" class="text-[var(--primary)]" />
                    <span>سجل فواتير الشراء وحركات الآجل للمورد (سجل أوامر الشراء والاستيراد من الصين)</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="erp-badge erp-badge-neutral text-xs">
                        إجمالي الفواتير: {{ $purchaseInvoices->count() }}
                    </span>
                    <a href="{{ route('purchases.create') }}" class="erp-btn erp-btn-primary erp-btn-sm">
                        <x-icon name="plus" size="14" />
                        <span>فاتورة شراء جديدة</span>
                    </a>
                </div>
            </div>

            <!-- Supplier Financial KPI Row -->
            <div class="p-4 border-b border-[var(--border-color)] bg-[var(--bg-surface-alt)]">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-center">
                    <div class="p-3 bg-[var(--bg-surface)] rounded border border-[var(--border-color)]">
                        <div class="text-xs text-[var(--text-muted)] font-medium mb-1">إجمالي المشتريات والتوريدات</div>
                        <div class="font-numeric font-bold text-lg text-[var(--text-main)]">
                            {{ number_format($purchaseInvoices->where('status', 'posted')->sum('total_amount'), 2) }} {{ $supplier->currency }}
                        </div>
                    </div>
                    <div class="p-3 bg-[var(--bg-surface)] rounded border border-[var(--border-color)]">
                        <div class="text-xs text-[var(--text-muted)] font-medium mb-1">إجمالي المسدد للمورد</div>
                        <div class="font-numeric font-bold text-lg text-[var(--success)]">
                            {{ number_format($purchaseInvoices->where('status', 'posted')->sum('paid_amount'), 2) }} {{ $supplier->currency }}
                        </div>
                    </div>
                    <div class="p-3 bg-[var(--bg-surface)] rounded border border-[var(--border-color)]">
                        <div class="text-xs text-[var(--text-muted)] font-medium mb-1">الرصيد المعلق المستحق للمورد</div>
                        <div class="font-numeric font-bold text-lg text-[var(--warning)]">
                            {{ number_format($supplier->current_balance, 2) }} {{ $supplier->currency }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="erp-card-body p-0">
                @if($purchaseInvoices->isEmpty())
                    <div class="text-center py-10">
                        <x-icon name="truck" size="40" class="text-[var(--text-muted)] mb-2 inline-block" />
                        <h4 class="font-bold text-base m-0 mb-1">لا توجد فواتير شراء مسجلة لهذا المورد حتى الآن</h4>
                        <p class="text-xs text-[var(--text-muted)] max-w-md mx-auto m-0 mb-4">
                            يمكنك تسجيل فاتورة توريد وشراء جديدة سواء نقدية أو آجلة وسيتم تحديث رصيد المورد ومخزون الأصناف مباشرة.
                        </p>
                        <a href="{{ route('purchases.create') }}" class="erp-btn erp-btn-primary erp-btn-sm inline-flex">
                            <x-icon name="plus" size="14" />
                            <span>تسجيل فاتورة شراء</span>
                        </a>
                    </div>
                @else
                    <div class="erp-table-responsive">
                        <table class="erp-table">
                            <thead>
                                <tr>
                                    <th class="w-12 text-center">#</th>
                                    <th>رقم الفاتورة</th>
                                    <th>تاريخ الفاتورة</th>
                                    <th>مستودع الاستلام</th>
                                    <th class="text-center">طريقة السداد</th>
                                    <th class="text-center">إجمالي الفاتورة</th>
                                    <th class="text-center">المدفوع نقداً</th>
                                    <th class="text-center">المتبقي الآجل</th>
                                    <th class="text-center">تاريخ الاستحقاق</th>
                                    <th class="text-center">الحالة</th>
                                    <th class="w-24 text-center">إجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if((float) $supplier->opening_balance != 0.0)
                                    <tr class="bg-[var(--bg-surface-alt)] font-semibold">
                                        <td class="text-center font-numeric text-xs">—</td>
                                        <td class="font-numeric font-bold text-xs">OPENING</td>
                                        <td class="text-xs">—</td>
                                        <td class="text-xs">الرصيد الافتتاحي للمورد</td>
                                        <td class="text-center text-xs">—</td>
                                        <td class="text-center font-numeric font-bold text-xs">{{ number_format($supplier->opening_balance, 2) }} {{ $supplier->currency }}</td>
                                        <td class="text-center text-xs font-numeric">0.00 {{ $supplier->currency }}</td>
                                        <td class="text-center font-numeric font-bold text-xs text-[var(--danger)]">{{ number_format($supplier->opening_balance, 2) }} {{ $supplier->currency }}</td>
                                        <td class="text-center text-xs">—</td>
                                        <td class="text-center"><span class="erp-badge erp-badge-neutral text-xs">افتتاحي</span></td>
                                        <td class="text-center">—</td>
                                    </tr>
                                @endif
                                @foreach($purchaseInvoices as $index => $invoice)
                                    <tr>
                                        <td class="text-center font-numeric text-xs text-[var(--text-muted)]">
                                            {{ $index + 1 }}
                                        </td>
                                        <td class="font-numeric font-bold">
                                            <a href="{{ route('purchases.show', $invoice) }}" class="text-[var(--primary)] hover:underline">
                                                {{ $invoice->invoice_number }}
                                            </a>
                                        </td>
                                        <td class="text-xs font-numeric">
                                            {{ $invoice->invoice_date ? $invoice->invoice_date->format('Y-m-d') : '—' }}
                                        </td>
                                        <td class="text-xs">
                                            {{ $invoice->warehouse?->name ?? '—' }}
                                        </td>
                                        <td class="text-center">
                                            <span class="erp-badge {{ $invoice->payment_type_badge_class }} text-xs">
                                                {{ $invoice->payment_type_label }}
                                            </span>
                                        </td>
                                        <td class="text-center font-numeric font-bold text-xs">
                                            {{ number_format($invoice->total_amount, 2) }} {{ $supplier->currency }}
                                        </td>
                                        <td class="text-center font-numeric text-xs text-[var(--success)] font-semibold">
                                            {{ number_format($invoice->paid_amount, 2) }} {{ $supplier->currency }}
                                        </td>
                                        <td class="text-center font-numeric text-xs {{ $invoice->remaining_amount > 0 ? 'text-[var(--danger)] font-bold' : 'text-[var(--text-muted)]' }}">
                                            {{ number_format($invoice->remaining_amount, 2) }} {{ $supplier->currency }}
                                        </td>
                                        <td class="text-center font-numeric text-xs text-[var(--text-muted)]">
                                            {{ $invoice->due_date ? $invoice->due_date->format('Y-m-d') : '—' }}
                                        </td>
                                        <td class="text-center">
                                            <span class="erp-badge {{ $invoice->status_badge_class }} text-xs">
                                                {{ $invoice->status_label }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="flex items-center justify-center gap-1">
                                                <a href="{{ route('purchases.show', $invoice) }}" class="erp-btn erp-btn-ghost erp-btn-xs" title="عرض الفاتورة">
                                                    <x-icon name="eye" size="14" />
                                                </a>
                                                <a href="{{ route('purchases.print', $invoice) }}" target="_blank" class="erp-btn erp-btn-ghost erp-btn-xs" title="طباعة الفاتورة">
                                                    <x-icon name="printer" size="14" />
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        <!-- Supplier Scheduled Installments Section -->
        <div class="erp-card mb-6">
            <div class="erp-card-header flex items-center justify-between">
                <div class="erp-card-title flex items-center gap-2">
                    <x-icon name="calendar" size="18" />
                    <span>جدول الأقساط والدفعات المستحقة للمورد</span>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" onclick="document.getElementById('addSupplierInstallmentModal').style.display='flex'" class="erp-btn erp-btn-sm erp-btn-primary">
                        <x-icon name="plus" size="14" />
                        <span>جدولة قسط جديد</span>
                    </button>
                    <a href="{{ route('supplier-installments.index', ['supplier_id' => $supplier->id]) }}" class="erp-btn erp-btn-sm erp-btn-secondary">
                        <x-icon name="external-link" size="14" />
                        <span>كافة الأقساط</span>
                    </a>
                </div>
            </div>

            @if($installments->isEmpty())
                <div class="text-center py-8 text-[var(--text-muted)] text-sm">
                    لا توجد أقساط مجدولة مسجلة لهذا المورد حالياً. يمكنك جدولة أقساط أو خطة سداد لتنظيم الدفعات ومواعيد الاستحقاق.
                </div>
            @else
                <div class="erp-table-responsive">
                    <table class="erp-table">
                        <thead>
                            <tr>
                                <th>بيان القسط</th>
                                <th>الفاتورة المرتبطة</th>
                                <th>تاريخ الاستحقاق</th>
                                <th>قيمة القسط</th>
                                <th>المسدد</th>
                                <th>المتبقي</th>
                                <th>الحالة</th>
                                <th style="text-align: center;">إجراء</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($installments as $inst)
                                <tr class="erp-table-row {{ $inst->is_overdue ? 'bg-rose-50/20' : '' }}">
                                    <td>
                                        <span class="font-semibold text-xs text-[var(--text-main)]">{{ $inst->installment_title }}</span>
                                        @if($inst->notes)
                                            <span class="block text-[11px] text-[var(--text-muted)]">{{ $inst->notes }}</span>
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
                                        <span class="font-numeric text-xs font-semibold {{ $inst->is_overdue ? 'text-rose-600' : ($inst->is_due_today ? 'text-amber-600' : 'text-[var(--text-secondary)]') }}">
                                            {{ $inst->due_date ? $inst->due_date->format('Y-m-d') : '—' }}
                                        </span>
                                        @if($inst->is_overdue)
                                            <span class="text-[10px] text-rose-600 block font-bold">متأخر</span>
                                        @elseif($inst->is_due_today)
                                            <span class="text-[10px] text-amber-600 block font-bold">اليوم</span>
                                        @endif
                                    </td>
                                    <td class="font-numeric font-bold text-xs">
                                        {{ number_format((float)$inst->amount, 2) }} ج.م
                                    </td>
                                    <td class="font-numeric text-xs text-emerald-600">
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
                                        @if($inst->remaining_amount > 0)
                                            <button type="button" 
                                                    class="erp-btn erp-btn-success text-xs py-1 px-2.5"
                                                    onclick="openPaymentForInstallment({{ $inst->id }}, '{{ addslashes($inst->installment_title) }}', {{ (float)$inst->remaining_amount }}, '{{ $inst->purchase_invoice_id ?? '' }}')">
                                                <span>صرف دفعة</span>
                                            </button>
                                        @else
                                            <span class="text-xs text-emerald-600 font-bold">✓ مسدد</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <!-- Modal: Record Supplier Payment -->
    <div id="recordSupplierPaymentModal" class="erp-modal-overlay">
        <div class="erp-modal-container">
            <div class="erp-modal-header">
                <h3 class="erp-modal-title">
                    <x-icon name="credit-card" size="18" />
                    <span>تسجيل دفعة / سند صرف للمورد: {{ $supplier->name }}</span>
                </h3>
                <button type="button" class="erp-modal-close" onclick="document.getElementById('recordSupplierPaymentModal').style.display='none'">&times;</button>
            </div>

            <form action="{{ route('suppliers.payments.store', $supplier) }}" method="POST">
                @csrf
                <div class="erp-modal-body">
                    <div style="background: var(--bg-surface-alt); padding: 12px 14px; border-radius: var(--border-radius-sm); border: 1px solid var(--border-color); margin-bottom: 14px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; font-size: 12px;">
                            <span style="color: var(--text-muted);">الرصيد المستحق للمورد حالياً:</span>
                            <span class="font-bold font-numeric text-rose-700 text-sm" style="color: var(--danger); font-size: 14px;">
                                {{ number_format((float)$supplier->current_balance, 2) }} {{ $supplier->getCurrencySymbol() }}
                            </span>
                        </div>
                    </div>

                    <div class="erp-form-grid mb-3">
                        <div class="erp-form-group">
                            <label class="erp-form-label">سداد على قسط محدد (اختياري)</label>
                            <select name="supplier_installment_id" id="supplierInstallmentSelect" onchange="handleSupplierInstallmentChange(this)" class="erp-input w-full">
                                <option value="" data-remaining="">-- بدون قسط (سداد عام) --</option>
                                @foreach($installments->where('status', '!=', 'paid') as $inst)
                                    <option value="{{ $inst->id }}" data-remaining="{{ (float)$inst->remaining_amount }}" data-invoice-id="{{ $inst->purchase_invoice_id ?? '' }}">
                                        {{ $inst->installment_title }} (متبقي: {{ number_format((float)$inst->remaining_amount, 2) }} ج.م)
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="erp-form-group">
                            <label class="erp-form-label">تخصيص الدفعة على فاتورة محددة (اختياري)</label>
                            <select name="purchase_invoice_id" id="supplierInvoiceSelect" onchange="handleSupplierInvoiceChange(this)" class="erp-input w-full">
                                <option value="" data-remaining="">-- دفعة عامة على الحساب (تخفيض المديونية) --</option>
                                @foreach($unpaidInvoices ?? [] as $unpaid)
                                    <option value="{{ $unpaid->id }}" data-remaining="{{ (float)$unpaid->remaining_amount }}">
                                        فاتورة رقم {{ $unpaid->invoice_number }} (تاريخ: {{ $unpaid->invoice_date->format('Y-m-d') }} - متبقي: {{ number_format((float)$unpaid->remaining_amount, 2) }} ج.م)
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="erp-form-group">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                            <label class="erp-form-label" style="margin-bottom: 0;">المبلغ المسدد <span class="required">*</span></label>
                            <button type="button" id="supplierFillMaxBtn" onclick="fillSupplierMax()" class="text-xs text-[var(--primary)] hover:underline font-bold hidden">
                                سداد كامل الفاتورة
                            </button>
                        </div>
                        <input type="number" step="0.01" min="0.01" name="amount" id="supplierPaymentAmount" required placeholder="0.00" class="erp-input font-numeric font-bold text-base text-[var(--danger)]">
                    </div>

                    <div class="erp-form-grid">
                        <div class="erp-form-group">
                            <label class="erp-form-label">تاريخ الصرف <span class="required">*</span></label>
                            <input type="date" name="payment_date" required value="{{ date('Y-m-d') }}" class="erp-input font-numeric">
                        </div>
                        <div class="erp-form-group">
                            <label class="erp-form-label">طريقة الصرف <span class="required">*</span></label>
                            <select name="payment_method" required class="erp-input">
                                <option value="cash">نقداً</option>
                                <option value="bank_transfer">تحويل بنكي</option>
                                <option value="card">فيزا / بطاقة</option>
                                <option value="cheque">شيك</option>
                            </select>
                        </div>
                    </div>

                    <div class="erp-form-group">
                        <label class="erp-form-label">الخزينة المسحوب منها</label>
                        <select name="treasury_id" class="erp-input">
                            <option value="">-- الخزينة الافتراضية --</option>
                            @foreach($treasuries ?? [] as $tr)
                                <option value="{{ $tr->id }}">{{ $tr->name }} (رصيد: {{ number_format((float)$tr->current_balance, 2) }} ج.م)</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="erp-form-grid">
                        <div class="erp-form-group">
                            <label class="erp-form-label">رقم المرجع / التحويل (إن وجد)</label>
                            <input type="text" name="reference_number" placeholder="رقم الشيك أو الحوالة..." class="erp-input font-mono">
                        </div>
                        <div class="erp-form-group">
                            <label class="erp-form-label">ملاحظات</label>
                            <input type="text" name="notes" placeholder="ملاحظات..." class="erp-input">
                        </div>
                    </div>
                </div>

                <div class="erp-modal-footer">
                    <button type="button" onclick="document.getElementById('recordSupplierPaymentModal').style.display='none'" class="erp-btn erp-btn-secondary">إلغاء</button>
                    <button type="submit" class="erp-btn erp-btn-primary">حفظ وتأكيد السند</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Add Installment for this Supplier -->
    <div id="addSupplierInstallmentModal" class="erp-modal-overlay">
        <div class="erp-modal-container max-w-lg">
            <div class="erp-modal-header">
                <h3 class="erp-modal-title">
                    <x-icon name="calendar-plus" size="18" />
                    <span>جدولة قسط مستحق للمورد: {{ $supplier->name }}</span>
                </h3>
                <button type="button" class="erp-modal-close" onclick="document.getElementById('addSupplierInstallmentModal').style.display='none'">&times;</button>
            </div>

            <form action="{{ route('supplier-installments.store') }}" method="POST">
                @csrf
                <input type="hidden" name="supplier_id" value="{{ $supplier->id }}">
                <input type="hidden" name="plan_type" value="single">

                <div class="erp-modal-body p-5">
                    <div class="mb-3.5">
                        <label class="erp-label block mb-1.5 text-xs font-semibold">الفاتورة المرتبطة (اختياري)</label>
                        <select name="purchase_invoice_id" class="erp-input w-full">
                            <option value="">-- حساب عام للمورد --</option>
                            @foreach($unpaidInvoices ?? [] as $unpaid)
                                <option value="{{ $unpaid->id }}">
                                    فاتورة رقم {{ $unpaid->invoice_number }} (متبقي: {{ number_format((float)$unpaid->remaining_amount, 2) }} ج.م)
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5 mb-3.5">
                        <div>
                            <label class="erp-label block mb-1.5 text-xs font-semibold">عنوان القسط *</label>
                            <input type="text" name="installment_title" class="erp-input w-full" placeholder="مثال: قسط توريد بضاعة..." required>
                        </div>
                        <div>
                            <label class="erp-label block mb-1.5 text-xs font-semibold">مبلغ القسط *</label>
                            <input type="number" step="0.01" min="0.01" name="amount" class="erp-input font-numeric font-bold w-full" required placeholder="0.00">
                        </div>
                    </div>

                    <div class="mb-3.5">
                        <label class="erp-label block mb-1.5 text-xs font-semibold">تاريخ الاستحقاق *</label>
                        <input type="date" name="due_date" value="{{ date('Y-m-d', strtotime('+7 days')) }}" class="erp-input font-numeric w-full" required>
                    </div>

                    <div class="mb-3.5">
                        <label class="erp-label block mb-1.5 text-xs font-semibold">ملاحظات إضافية</label>
                        <textarea name="notes" class="erp-input w-full" rows="2" placeholder="ملاحظات وشروط الدفعة..."></textarea>
                    </div>
                </div>

                <div class="erp-modal-footer p-4 flex justify-end gap-2.5 bg-[var(--bg-surface-alt)] border-t border-[var(--border-light)]">
                    <button type="button" onclick="document.getElementById('addSupplierInstallmentModal').style.display='none'" class="erp-btn erp-btn-secondary">إلغاء</button>
                    <button type="submit" class="erp-btn erp-btn-primary">حفظ القسط المجدول</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function handleSupplierInstallmentChange(select) {
            const opt = select.selectedOptions[0];
            const amountInput = document.getElementById('supplierPaymentAmount');
            const invSelect = document.getElementById('supplierInvoiceSelect');

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

        function handleSupplierInvoiceChange(select) {
            const selectedOpt = select.options[select.selectedIndex];
            const remaining = selectedOpt.getAttribute('data-remaining');
            const fillBtn = document.getElementById('supplierFillMaxBtn');
            const amountInput = document.getElementById('supplierPaymentAmount');

            if (remaining && parseFloat(remaining) > 0) {
                fillBtn.classList.remove('hidden');
                fillBtn.textContent = 'سداد كامل الفاتورة (' + parseFloat(remaining).toFixed(2) + ')';
                amountInput.max = remaining;
            } else {
                fillBtn.classList.add('hidden');
                amountInput.removeAttribute('max');
            }
        }

        function fillSupplierMax() {
            const select = document.getElementById('supplierInvoiceSelect');
            const selectedOpt = select.options[select.selectedIndex];
            const remaining = selectedOpt.getAttribute('data-remaining');
            if (remaining) {
                document.getElementById('supplierPaymentAmount').value = remaining;
            }
        }

        function openPaymentForInstallment(instId, instTitle, remaining, invId) {
            const modal = document.getElementById('recordSupplierPaymentModal');
            const instSelect = document.getElementById('supplierInstallmentSelect');
            const invSelect = document.getElementById('supplierInvoiceSelect');
            const amountInput = document.getElementById('supplierPaymentAmount');

            if (instSelect) instSelect.value = instId;
            if (invSelect && invId) invSelect.value = invId;
            if (amountInput) {
                amountInput.value = remaining;
                amountInput.max = remaining;
            }

            if (modal) {
                modal.style.display = 'flex';
                modal.classList.add('open');
            }
        }
    </script>
@endsection
