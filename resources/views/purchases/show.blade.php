@extends('layouts.app')

@section('control_panel')
    <x-control-panel title="فاتورة شراء: {{ $invoice->invoice_number }}" :breadcrumbs="['المشتريات والموردين' => route('purchases.index'), $invoice->invoice_number => '#']">
        <x-slot:actions>
            @if($invoice->status === 'draft')
                <a href="{{ route('purchases.edit', $invoice) }}" class="erp-btn erp-btn-secondary">
                    <x-icon name="edit" size="16" />
                    <span>تعديل الفاتورة</span>
                </a>

                <a href="{{ route('purchases.pricing-review', $invoice) }}" class="erp-btn erp-btn-warning">
                    <x-icon name="tag" size="16" />
                    <span>مراجعة الأسعار والترحيل</span>
                </a>

                <form action="{{ route('purchases.post', $invoice) }}" method="POST" data-confirm="هل تريد ترحيل هذه الفاتورة إلى المخازن وتوليد دفعات FIFO آلياً؟">
                    @csrf
                    <button type="submit" class="erp-btn erp-btn-primary">
                        <x-icon name="check-circle" size="16" />
                        <span>ترحيل مباشر (FIFO)</span>
                    </button>
                </form>

                <form action="{{ route('purchases.cancel', $invoice) }}" method="POST" data-confirm="تأكيد إلغاء هذه المسودة؟">
                    @csrf
                    <button type="submit" class="erp-btn erp-btn-secondary text-[var(--danger)]">
                        <x-icon name="x-circle" size="16" />
                        <span>إلغاء الفاتورة</span>
                    </button>
                </form>
            @elseif($invoice->status === 'posted')
                <a href="{{ route('purchase-returns.create', ['purchase_invoice_id' => $invoice->id]) }}" class="erp-btn erp-btn-secondary text-[var(--danger)] border-[var(--danger)] hover:bg-rose-50" title="تسجيل مرتجع أصناف من هذه الفاتورة إلى المورد">
                    <x-icon name="rotate-ccw" size="16" />
                    <span>مرتجع مشتريات</span>
                </a>
            @endif

            @if($invoice->status !== 'posted')
                <form action="{{ route('purchases.destroy', $invoice) }}" method="POST" data-confirm="هل أنت متأكد من مسح فاتورة الشراء (#{{ $invoice->invoice_number }}) نهائياً من النظام؟">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="erp-btn erp-btn-secondary text-rose-600 hover:text-rose-700" title="مسح الفاتورة نهائياً">
                        <x-icon name="trash" size="16" />
                        <span>مسح الفاتورة</span>
                    </button>
                </form>
            @endif

            <!-- Print Combobox Dropdown -->
            <x-print-dropdown 
                title="طباعة الفاتورة"
                code="{{ $invoice->invoice_number }}"
                urlThermal="{{ route('purchases.print', [$invoice, 'format' => 'thermal_80mm']) }}"
                directThermal="{{ route('purchases.direct-print', [$invoice, 'format' => 'thermal_80mm']) }}"
                urlA4="{{ route('purchases.print', [$invoice, 'format' => 'a4']) }}"
                urlA5="{{ route('purchases.print', [$invoice, 'format' => 'a5']) }}"
            />

            @if($invoice->status === 'posted' && (float)$invoice->remaining_amount > 0)
                <button type="button" onclick="document.getElementById('recordSupplierPaymentModal').classList.remove('hidden')" class="erp-btn erp-btn-success">
                    <x-icon name="credit-card" size="16" />
                    <span>تسجيل دفعة سداد للمورد</span>
                </button>
            @endif

            <a href="{{ route('purchases.index') }}" class="erp-btn erp-btn-ghost">
                <x-icon name="arrow-left" size="16" />
                <span>العودة للقائمة</span>
            </a>
        </x-slot:actions>

        <x-slot:extra>
            <span class="erp-badge {{ $invoice->status_badge_class }} py-1 px-2.5 text-xs font-semibold">
                {{ $invoice->status_label }}
            </span>
            <span class="px-2.5 py-1 text-xs font-bold rounded-full border {{ $invoice->payment_status_badge_class }}">
                {{ $invoice->payment_status_label }}
            </span>
        </x-slot:extra>
    </x-control-panel>
@endsection

@section('content')
    <div data-page="purchases-show" class="flex flex-col gap-5">
        <!-- Status Messages -->
        @if(session('status') === 'success')
            <div class="erp-alert erp-alert-success" role="alert">
                <x-icon name="check-circle" size="18" />
                <span>{{ session('message') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div class="erp-alert erp-alert-danger" role="alert">
                <x-icon name="alert-triangle" size="18" />
                <span>{{ session('error') }}</span>
            </div>
        @endif

        <!-- Invoice Details Cards Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Supplier Card -->
            <div class="erp-card">
                <div class="erp-card-header">
                    <div class="erp-card-title">
                        <x-icon name="factory" size="18" />
                        <span>بيانات المورد والمصدر</span>
                    </div>
                </div>
                <div class="erp-card-body flex flex-col gap-2 text-xs">
                    <div>
                        <span class="text-[var(--text-muted)] block mb-0.5">اسم المورد / المصنع:</span>
                        <strong class="text-sm text-[var(--text-main)]">{{ $invoice->supplier->name ?? 'غير محدد' }}</strong>
                    </div>
                    <div>
                        <span class="text-[var(--text-muted)] block mb-0.5">الدولة والمدينة:</span>
                        <span>{{ $invoice->supplier->country ?? '—' }} ({{ $invoice->supplier->city ?? '—' }})</span>
                    </div>
                    <div>
                        <span class="text-[var(--text-muted)] block mb-0.5">رقم الهاتف / ويشات:</span>
                        <span class="font-numeric">{{ $invoice->supplier->phone ?? '—' }}</span>
                    </div>
                </div>
            </div>

            <!-- Warehouse & Delivery Card -->
            <div class="erp-card">
                <div class="erp-card-header">
                    <div class="erp-card-title">
                        <x-icon name="home" size="18" />
                        <span>مستودع وتاريخ الاستلام</span>
                    </div>
                </div>
                <div class="erp-card-body flex flex-col gap-2 text-xs">
                    <div>
                        <span class="text-[var(--text-muted)] block mb-0.5">المستودع المستلم:</span>
                        <strong class="text-sm text-[var(--primary)]">{{ $invoice->warehouse->name ?? 'غير محدد' }}</strong>
                        <span class="text-[11px] text-[var(--text-muted)]">({{ $invoice->warehouse->code ?? '' }})</span>
                    </div>
                    <div>
                        <span class="text-[var(--text-muted)] block mb-0.5">تاريخ الفاتورة:</span>
                        <span class="font-numeric font-bold text-xs">{{ $invoice->invoice_date->format('Y-m-d') }}</span>
                    </div>
                    <div>
                        <span class="text-[var(--text-muted)] block mb-0.5">تاريخ الترحيل للمخزن:</span>
                        <span class="font-numeric">{{ $invoice->posted_at ? $invoice->posted_at->format('Y-m-d H:i') : 'لم يتم الترحيل بعد' }}</span>
                    </div>
                </div>
            </div>

            <!-- Financial Summary Card -->
            <div class="erp-card">
                <div class="erp-card-header">
                    <div class="erp-card-title">
                        <x-icon name="banknote" size="18" />
                        <span>الملخص المالي للفاتورة</span>
                    </div>
                </div>
                <div class="erp-card-body flex flex-col gap-2 text-xs font-numeric">
                    <div class="flex justify-between items-center py-1 border-b border-[var(--border-color)]">
                        <span class="text-[var(--text-muted)]">مجموع الأصناف:</span>
                        <span class="font-bold">{{ number_format((float)$invoice->subtotal, 2) }} ج.م</span>
                    </div>
                    <div class="flex justify-between items-center py-1 border-b border-[var(--border-color)]">
                        <span class="text-[var(--text-muted)]">الشحن والتخليص:</span>
                        <span>{{ number_format((float)$invoice->shipping_cost, 2) }} ج.م</span>
                    </div>
                    <div class="flex justify-between items-center py-1 border-b border-[var(--border-color)]">
                        <span class="text-[var(--text-muted)]">طريقة السداد:</span>
                        <span class="erp-badge {{ $invoice->payment_type_badge_class }} text-[11px]">{{ $invoice->payment_type_label }}</span>
                    </div>
                    <div class="flex justify-between items-center py-1 border-b border-[var(--border-color)]">
                        <span class="text-[var(--text-muted)]">المسدد للمورد:</span>
                        <span class="font-bold text-emerald-600">{{ number_format((float)($invoice->paid_amount ?? 0), 2) }} ج.م</span>
                    </div>
                    <div class="flex justify-between items-center py-1 border-b border-[var(--border-color)]">
                        <span class="text-[var(--text-muted)]">المتبقي الآجل المعلق:</span>
                        <span class="font-bold text-rose-600">{{ number_format((float)($invoice->remaining_amount ?? 0), 2) }} ج.م</span>
                    </div>
                    @if($invoice->due_date)
                    <div class="flex justify-between items-center py-1 border-b border-[var(--border-color)]">
                        <span class="text-[var(--text-muted)]">تاريخ الاستحقاق:</span>
                        <span class="text-amber-700 font-semibold">{{ $invoice->due_date->format('Y-m-d') }}</span>
                    </div>
                    @endif
                    <div class="flex justify-between items-center pt-1 text-sm font-bold text-[var(--primary)]">
                        <span>إجمالي الفاتورة:</span>
                        <span class="text-base">{{ number_format((float)$invoice->total_amount, 2) }} ج.م</span>
                    </div>
                </div>
            </div>
        </div>

        @if($invoice->notes)
            <div class="erp-card p-3 text-xs bg-[var(--surface-muted)]">
                <strong class="text-[var(--text-main)] block mb-1">ملاحظات الفاتورة:</strong>
                <p class="m-0 text-[var(--text-muted)] leading-relaxed">{{ $invoice->notes }}</p>
            </div>
        @endif

        <!-- Items Table Card -->
        <div class="erp-card overflow-hidden">
            <div class="erp-card-header">
                <div class="erp-card-title">
                    <x-icon name="package" size="18" />
                    <span>أصناف الفاتورة ({{ $invoice->items->count() }} صنف)</span>
                </div>
            </div>
            <div class="erp-table-responsive">
                <table class="erp-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>كود الصنف</th>
                            <th>اسم المنتج</th>
                            <th>التصنيف</th>
                            <th class="text-center">الكمية</th>
                            <th class="text-center">سعر التكلفة</th>
                            <th class="text-center">الإجمالي</th>
                            <th>ملاحظات البند</th>
                            <th class="text-center">كشف الحركة</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($invoice->items as $index => $item)
                            <tr>
                                <td class="font-numeric text-center text-xs text-[var(--text-muted)]">{{ $index + 1 }}</td>
                                <td>
                                    <span class="font-numeric font-bold text-xs text-[var(--primary)]">{{ $item->product->sku ?? '—' }}</span>
                                </td>
                                <td>
                                    <a href="{{ route('products.show', $item->product) }}" class="font-semibold text-xs text-[var(--text-main)] hover:underline">
                                        {{ $item->product->name ?? '—' }}
                                    </a>
                                </td>
                                <td>
                                    <span class="erp-badge erp-badge-neutral text-[11px]">{{ $item->product->category->name ?? '—' }}</span>
                                </td>
                                <td class="text-center text-xs">
                                    <span class="font-numeric font-bold">{{ (float)$item->quantity }}</span>
                                    <span class="text-[var(--text-muted)]">{{ $item->unit->name ?? ($item->product->unit ?? 'قطعة') }}</span>
                                    @if((float)($item->conversion_factor ?? 1.0) != 1.0)
                                        <div class="text-[10px] text-[var(--primary)] font-numeric font-semibold">
                                            = {{ (float)($item->base_quantity ?: ($item->quantity * $item->conversion_factor)) }} قطعة
                                        </div>
                                    @endif
                                </td>
                                <td class="font-numeric text-center text-xs">
                                    {{ number_format((float)$item->unit_cost, 2) }} ج.م
                                </td>
                                <td class="font-numeric text-center font-bold text-xs text-[var(--text-main)]">
                                    {{ number_format((float)$item->subtotal, 2) }} ج.م
                                </td>
                                <td class="text-xs text-[var(--text-muted)]">
                                    {{ $item->notes ?? '—' }}
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('products.ledger', $item->product) }}" class="erp-btn erp-btn-ghost erp-btn-icon text-xs text-[var(--primary)]" title="كشف حركة الصنف">
                                        <x-icon name="activity" size="14" />
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Generated FIFO Batches (If Posted) -->
        @if($invoice->status === 'posted' && $invoice->batches->isNotEmpty())
            <div class="erp-card overflow-hidden">
                <div class="erp-card-header flex items-center justify-between">
                    <div class="erp-card-title">
                        <x-icon name="layers" size="18" />
                        <span>دفعات المخزون الناتجة عن هذه الفاتورة (FIFO Batches)</span>
                    </div>
                    <span class="erp-badge erp-badge-success text-xs">مُسجلة بالمخازن</span>
                </div>
                <div class="erp-table-responsive">
                    <table class="erp-table">
                        <thead>
                            <tr>
                                <th>رقم الدفعة</th>
                                <th>الصنف</th>
                                <th>المستودع</th>
                                <th class="text-center">الكمية الأولية</th>
                                <th class="text-center">الكمية المتبقية</th>
                                <th class="text-center">تكلفة الوحدة</th>
                                <th class="text-center">حالة الصرف</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($invoice->batches as $batch)
                                <tr>
                                    <td>
                                        <span class="font-numeric font-bold text-xs text-[var(--primary)]">{{ $batch->batch_number }}</span>
                                    </td>
                                    <td>
                                        <a href="{{ route('products.show', $batch->product) }}" class="font-semibold text-xs hover:underline">
                                            {{ $batch->product->name ?? '—' }}
                                        </a>
                                    </td>
                                    <td>
                                        <span class="text-xs">{{ $batch->warehouse->name ?? '—' }}</span>
                                    </td>
                                    <td class="font-numeric text-center text-xs">
                                        {{ number_format((float)$batch->initial_quantity, 2) }}
                                    </td>
                                    <td class="font-numeric text-center font-bold text-xs {{ $batch->remaining_quantity > 0 ? 'text-[var(--success-text)]' : 'text-[var(--danger)]' }}">
                                        {{ number_format((float)$batch->remaining_quantity, 2) }}
                                    </td>
                                    <td class="font-numeric text-center text-xs">
                                        {{ number_format((float)$batch->cost_price, 2) }} ج.م
                                    </td>
                                    <td class="text-center">
                                        @if($batch->is_exhausted)
                                            <span class="erp-badge erp-badge-neutral text-[10px]">نَفِدت بالكامل</span>
                                        @else
                                            <span class="erp-badge erp-badge-success text-[10px]">متاح منها للصرف</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <!-- Payments & Installments Section -->
        @if($invoice->status === 'posted')
            <div class="erp-card">
                <div class="erp-card-header">
                    <div class="erp-card-title">
                        <x-icon name="receipt" size="18" />
                        <span>سجل دفعات وأقساط الفاتورة للمورد (سندات الصرف)</span>
                    </div>

                    @if((float)$invoice->remaining_amount > 0)
                        <button type="button" onclick="document.getElementById('recordSupplierPaymentModal').style.display='flex'" class="erp-btn erp-btn-success text-xs py-1 px-3">
                            <x-icon name="plus-circle" size="14" />
                            <span>تسجيل دفعة سداد (متبقي {{ number_format((float)$invoice->remaining_amount, 2) }} ج.م)</span>
                        </button>
                    @endif
                </div>

                @if($invoice->payments->isEmpty())
                    <div class="text-center py-6 text-[var(--text-muted)] text-xs" style="padding: 24px;">
                        لا توجد سندات صرف أو أقساط مسجلة على هذه الفاتورة بعد.
                        @if((float)$invoice->paid_amount > 0)
                            <div class="text-[11px] text-[var(--text-secondary)] mt-1">
                                (تم سداد مبلغ {{ number_format((float)$invoice->paid_amount, 2) }} ج.م كدفعة أولى عند إنشاء الفاتورة)
                            </div>
                        @endif
                    </div>
                @else
                    <div class="erp-table-responsive">
                        <table class="erp-table">
                            <thead>
                                <tr>
                                    <th>رقم السند</th>
                                    <th>تاريخ الصرف</th>
                                    <th>طريقة الصرف</th>
                                    <th>الخزينة / المصدر</th>
                                    <th>المبلغ المسدد</th>
                                    <th>رقم المرجع / التحويل</th>
                                    <th>الملاحظات</th>
                                    <th>المسؤول</th>
                                    <th style="text-align: center;">إجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($invoice->payments as $pay)
                                    <tr class="erp-table-row">
                                        <td class="font-mono font-medium text-[var(--text-main)]">{{ $pay->payment_number }}</td>
                                        <td class="font-numeric text-[var(--text-secondary)]">{{ $pay->payment_date ? \Carbon\Carbon::parse($pay->payment_date)->format('Y-m-d') : '—' }}</td>
                                        <td>
                                            <span class="erp-badge {{ $pay->payment_method === 'cash' ? 'erp-badge-success' : 'erp-badge-info' }}">
                                                {{ $pay->payment_method_label }}
                                            </span>
                                        </td>
                                        <td class="text-[var(--text-secondary)]">{{ $pay->treasury?->name ?? 'درج الكاشير' }}</td>
                                        <td class="font-bold font-numeric text-[var(--danger)]">
                                            {{ number_format((float)$pay->amount, 2) }} ج.م
                                        </td>
                                        <td class="font-mono text-[var(--text-secondary)]">{{ $pay->reference_number ?: '—' }}</td>
                                        <td class="text-[var(--text-secondary)] max-w-xs truncate" title="{{ $pay->notes }}">{{ $pay->notes ?: '—' }}</td>
                                        <td class="text-[var(--text-muted)]">{{ $pay->creator?->name ?? 'النظام' }}</td>
                                        <td style="text-align: center;">
                                            <div style="display: inline-flex; align-items: center; gap: 6px;">
                                                <a href="{{ route('supplier-payments.receipt', $pay) }}" target="_blank" class="erp-btn erp-btn-secondary text-xs py-1 px-2" title="طباعة سند الصرف">
                                                    <x-icon name="printer" size="14" />
                                                </a>
                                                <form action="{{ route('supplier-payments.destroy', $pay) }}" method="POST" onsubmit="return confirm('هل تريد إلغاء سند الصرف واسترجاع المديونية؟');">
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
                @endif
            </div>
        @endif

        <!-- Linked Returns Section -->
        @if($invoice->returns && $invoice->returns->isNotEmpty())
            <div class="erp-card mb-4">
                <div class="erp-card-header flex items-center justify-between">
                    <div class="erp-card-title flex items-center gap-2">
                        <x-icon name="rotate-ccw" size="18" class="text-[var(--danger)]" />
                        <span>سجل المرتجعات المسجلة على هذه الفاتورة</span>
                    </div>
                    <a href="{{ route('purchase-returns.create', ['purchase_invoice_id' => $invoice->id]) }}" class="erp-btn erp-btn-sm erp-btn-secondary text-[var(--danger)]">
                        <x-icon name="plus" size="14" />
                        <span>تسجيل مرتجع إضافي</span>
                    </a>
                </div>

                <div class="erp-table-responsive">
                    <table class="erp-table">
                        <thead>
                            <tr>
                                <th>رقم المرتجع</th>
                                <th>تاريخ المرتجع</th>
                                <th>طريقة التسوية</th>
                                <th>عدد الأصناف</th>
                                <th>إجمالي المرتجع</th>
                                <th>الحالة</th>
                                <th class="text-center">إجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($invoice->returns as $ret)
                                <tr>
                                    <td>
                                        <a href="{{ route('purchase-returns.show', $ret) }}" class="font-bold text-[var(--primary)] hover:underline font-mono">
                                            {{ $ret->return_number }}
                                        </a>
                                    </td>
                                    <td class="font-numeric text-xs">{{ $ret->return_date ? $ret->return_date->format('Y-m-d') : '—' }}</td>
                                    <td>
                                        <span class="erp-badge {{ $ret->settlement_type === 'cash_refund' ? 'erp-badge-info' : 'erp-badge-neutral' }} text-[11px]">
                                            {{ $ret->settlement_type_label }}
                                        </span>
                                    </td>
                                    <td class="font-numeric text-xs">{{ $ret->items->count() }} صنف</td>
                                    <td class="font-numeric font-bold text-xs text-[var(--danger)]">{{ number_format((float)$ret->total_amount, 2) }} ج.م</td>
                                    <td>
                                        <span class="erp-badge {{ $ret->status_badge_class }} text-[11px]">
                                            {{ $ret->status_label }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('purchase-returns.show', $ret) }}" class="erp-btn erp-btn-ghost erp-btn-sm p-1">
                                            <x-icon name="eye" size="15" />
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>

    <!-- Modal: Record Supplier Payment -->
    <div id="recordSupplierPaymentModal" class="erp-modal-overlay">
        <div class="erp-modal-container">
            <div class="erp-modal-header">
                <h3 class="erp-modal-title">
                    <x-icon name="credit-card" size="18" />
                    <span>تسجيل دفعة سداد للمورد</span>
                </h3>
                <button type="button" class="erp-modal-close" onclick="document.getElementById('recordSupplierPaymentModal').style.display='none'">&times;</button>
            </div>

            <form action="{{ route('purchases.payments.store', $invoice) }}" method="POST">
                @csrf
                <div class="erp-modal-body">
                    <div style="background: var(--bg-surface-alt); padding: 12px 14px; border-radius: var(--border-radius-sm); border: 1px solid var(--border-color); margin-bottom: 14px;">
                        <div style="display: flex; justify-content: space-between; font-size: 12px;">
                            <span style="color: var(--text-muted);">المورد:</span>
                            <strong style="color: var(--text-main);">{{ $invoice->supplier?->name }}</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 12px; margin-top: 6px;">
                            <span style="color: var(--text-muted);">المتبقي على الفاتورة:</span>
                            <strong class="font-numeric" style="color: var(--danger); font-size: 14px;" id="supplierRemainingDisplay">
                                {{ number_format((float)$invoice->remaining_amount, 2) }} ج.م
                            </strong>
                        </div>
                    </div>

                    <div class="erp-form-group">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                            <label class="erp-form-label" style="margin-bottom: 0;">المبلغ المسدد (ج.م) <span class="required">*</span></label>
                            <button type="button" onclick="document.getElementById('supplierPaymentAmountInput').value = '{{ (float)$invoice->remaining_amount }}'" class="text-xs text-[var(--primary)] hover:underline font-bold">
                                سداد كامل المتبقي
                            </button>
                        </div>
                        <input type="number" step="0.01" min="0.01" max="{{ (float)$invoice->remaining_amount }}" name="amount" id="supplierPaymentAmountInput" required placeholder="0.00" class="erp-input font-numeric font-bold text-base text-[var(--danger)]">
                    </div>

                    <div class="erp-form-grid">
                        <div class="erp-form-group">
                            <label class="erp-form-label">تاريخ السداد <span class="required">*</span></label>
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
                            <label class="erp-form-label">ملاحظات إضافية</label>
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
@endsection
