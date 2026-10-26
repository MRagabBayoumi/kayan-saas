@extends('layouts.app')

@section('control_panel')
    <x-control-panel title="فاتورة مبيعات: {{ $invoice->invoice_number }}" :breadcrumbs="['المبيعات والعملاء' => route('sales.index'), $invoice->invoice_number => '#']">
        <x-slot:actions>
            @if($invoice->status === 'draft')
                <a href="{{ route('sales.edit', $invoice) }}" class="erp-btn erp-btn-warning">
                    <x-icon name="edit-3" size="16" />
                    <span>تعديل الفاتورة</span>
                </a>
                @can('post-sales-invoice')
                <form action="{{ route('sales.post', $invoice) }}" method="POST" data-confirm="هل تريد ترحيل هذه الفاتورة وصرف الكميات بنظام FIFO آلياً؟">
                    @csrf
                    <button type="submit" class="erp-btn erp-btn-primary">
                        <x-icon name="check-circle" size="16" />
                        <span>ترحيل وصرف FIFO</span>
                    </button>
                </form>
                @endcan
                <form action="{{ route('sales.void', $invoice) }}" method="POST" data-confirm="هل تريد إلغاء هذه المسودة؟">
                    @csrf
                    <button type="submit" class="erp-btn erp-btn-secondary text-amber-600">
                        <x-icon name="x-circle" size="16" />
                        <span>إلغاء المسودة</span>
                    </button>
                </form>
            @endif

            @if($invoice->status === 'posted')
                <form action="{{ route('sales.unpost-and-edit', $invoice) }}" method="POST" data-confirm="سيتم فك ترحيل الفاتورة واسترجاع أرصدة المخزون مؤقتاً لتعديل بنودها في جدول البيع. هل تريد المتابعة؟">
                    @csrf
                    <button type="submit" class="erp-btn erp-btn-warning">
                        <x-icon name="edit-3" size="16" />
                        <span>تعديل الفاتورة (إرجاع للرصيد)</span>
                    </button>
                </form>

                @can('void-sales-invoice')
                <form action="{{ route('sales.void', $invoice) }}" method="POST" data-confirm="تحذير محاسبي: هل أنت متأكد من إلغاء هذه الفاتورة وإعادة الكميات المسحوبة للدفعات المخزنية؟">
                    @csrf
                    <input type="hidden" name="void_reason" value="إلغاء الفاتورة من قبل الإدارة">
                    <button type="submit" class="erp-btn erp-btn-secondary text-[var(--danger)]">
                        <x-icon name="x-circle" size="16" />
                        <span>إلغاء واسترجاع المخزون</span>
                    </button>
                </form>
                @endcan
                @can('access-sales')
                <a href="{{ route('sales-returns.create', ['sales_invoice_id' => $invoice->id]) }}" class="erp-btn erp-btn-secondary text-purple-700 hover:text-purple-800">
                    <x-icon name="rotate-ccw" size="16" />
                    <span>تحرير إذن إرجاع</span>
                </a>
                @endcan
            @endif

            <!-- مسح الفاتورة نهائياً -->
            <form action="{{ route('sales.destroy', $invoice) }}" method="POST" data-confirm="تحذير: هل أنت متأكد من مسح هذه الفاتورة (#{{ $invoice->invoice_number }}) نهائياً من النظام؟ {{ $invoice->status === 'posted' ? 'سيتم إلغاء تأثيرها المخزني والمالي أولاً ثم حذفها.' : '' }}">
                @csrf
                @method('DELETE')
                <button type="submit" class="erp-btn erp-btn-secondary text-rose-600 hover:text-rose-700" title="مسح الفاتورة نهائياً">
                    <x-icon name="trash" size="16" />
                    <span>مسح الفاتورة</span>
                </button>
            </form>

            @if($invoice->status === 'posted' && (float)$invoice->remaining_amount > 0)
                <button type="button" class="erp-btn erp-btn-success" data-action="open-payment-modal" title="تسجيل دفعة سداد أو قسط لهذه الفاتورة">
                    <x-icon name="dollar-sign" size="16" />
                    <span>تسجيل دفعة سداد</span>
                </button>
            @endif

            <!-- Print Combobox Dropdown -->
            <x-print-dropdown 
                title="طباعة الفاتورة"
                code="{{ $invoice->invoice_number }}"
                urlThermal="{{ route('sales.print', [$invoice, 'format' => 'thermal_80mm']) }}"
                directThermal="{{ route('sales.direct-print', [$invoice, 'format' => 'thermal_80mm']) }}"
                urlA4="{{ route('sales.print', [$invoice, 'format' => 'a4']) }}"
                urlA5="{{ route('sales.print', [$invoice, 'format' => 'a5']) }}"
            />

            <a href="{{ route('sales.index') }}" class="erp-btn erp-btn-ghost">
                <x-icon name="arrow-left" size="16" />
                <span>العودة للقائمة</span>
            </a>
        </x-slot:actions>

        <x-slot:extra>
            <div class="flex items-center gap-2">
                <span class="erp-badge {{ $invoice->status_badge_class }} py-1 px-2.5 text-xs font-semibold">
                    {{ $invoice->status_label }}
                </span>
                @if($invoice->status === 'posted')
                    <span class="erp-badge {{ $invoice->payment_status_badge_class }} py-1 px-2.5 text-xs font-semibold">
                        {{ $invoice->payment_status_label }}
                    </span>
                @endif
            </div>
        </x-slot:extra>
    </x-control-panel>
@endsection

@section('content')
    <div data-page="sales-show" class="flex flex-col gap-5">
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

        @if($invoice->status === 'voided')
            <div class="erp-alert erp-alert-warning" role="alert">
                <x-icon name="alert-triangle" size="18" />
                <div>
                    <strong>فاتورة مبيعات ملغاة:</strong>
                    <span>تم إلغاء هذه الفاتورة وإرجاع جميع الكميات المصروفة إلى دفعاتها الأصلية مع تسجيل حركات تسوية عكسية موثقة. سبب الإلغاء: {{ $invoice->void_reason ?? 'غير محدد' }}</span>
                </div>
            </div>
        @endif

        <!-- Invoice Details Cards Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Customer Card -->
            <div class="erp-card">
                <div class="erp-card-header">
                    <div class="erp-card-title">
                        <x-icon name="user" size="18" />
                        <span>بيانات العميل</span>
                    </div>
                </div>
                <div class="erp-card-body flex flex-col gap-2 text-xs">
                    <div>
                        <span class="text-[var(--text-muted)] block mb-0.5">اسم العميل:</span>
                        <strong class="text-sm text-[var(--text-main)]">{{ $invoice->customer->name ?? 'عميل نقدي' }}</strong>
                    </div>
                    <div>
                        <span class="text-[var(--text-muted)] block mb-0.5">رقم الهاتف:</span>
                        <span class="font-numeric">{{ $invoice->customer->phone ?? '—' }}</span>
                    </div>
                    <div>
                        <span class="text-[var(--text-muted)] block mb-0.5">العنوان:</span>
                        <span>{{ $invoice->customer->address ?? '—' }}</span>
                    </div>
                </div>
            </div>

            <!-- Warehouse & Delivery Card -->
            <div class="erp-card">
                <div class="erp-card-header">
                    <div class="erp-card-title">
                        <x-icon name="home" size="18" />
                        <span>مستودع وتاريخ الصرف</span>
                    </div>
                </div>
                <div class="erp-card-body flex flex-col gap-2 text-xs">
                    <div>
                        <span class="text-[var(--text-muted)] block mb-0.5">المستودع المصروف منه:</span>
                        <strong class="text-sm text-[var(--text-main)]">{{ $invoice->warehouse->name ?? 'المخزن الرئيسي' }}</strong>
                    </div>
                    <div>
                        <span class="text-[var(--text-muted)] block mb-0.5">تاريخ الفاتورة:</span>
                        <span class="font-numeric">{{ \Carbon\Carbon::parse($invoice->invoice_date)->format('Y-m-d') }}</span>
                    </div>
                    <div>
                        <span class="text-[var(--text-muted)] block mb-0.5">فئة التسعير المطبقة:</span>
                        @if($invoice->price_tier === 'distributor')
                            <span class="erp-badge erp-badge-neutral text-xs">سعر موزع (Distributor)</span>
                        @elseif($invoice->price_tier === 'wholesale')
                            <span class="erp-badge erp-badge-neutral text-xs">سعر جملة (Wholesale)</span>
                        @else
                            <span class="erp-badge erp-badge-neutral text-xs">سعر قطاعي (Retail)</span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Audit Trail & Financial Meta Card -->
            <div class="erp-card">
                <div class="erp-card-header">
                    <div class="erp-card-title">
                        <x-icon name="shield-check" size="18" />
                        <span>سجل التدقيق والمستخدمين</span>
                    </div>
                </div>
                <div class="erp-card-body flex flex-col gap-2 text-xs">
                    <div>
                        <span class="text-[var(--text-muted)] block mb-0.5">مسجل الفاتورة:</span>
                        <span class="font-semibold">{{ $invoice->creator->name ?? 'مدير النظام' }}</span>
                        <span class="text-[var(--text-muted)] text-[10px] block font-numeric">{{ $invoice->created_at->format('Y-m-d H:i') }}</span>
                    </div>
                    @if($invoice->poster)
                    <div>
                        <span class="text-[var(--text-muted)] block mb-0.5">المُرحِل ومسؤول الصرف:</span>
                        <span class="font-semibold text-[var(--success)]">{{ $invoice->poster->name }}</span>
                        <span class="text-[var(--text-muted)] text-[10px] block font-numeric">{{ $invoice->posted_at?->format('Y-m-d H:i') }}</span>
                    </div>
                    @endif
                    @if($invoice->voider)
                    <div>
                        <span class="text-[var(--text-muted)] block mb-0.5">المُلغي:</span>
                        <span class="font-semibold text-[var(--danger)]">{{ $invoice->voider->name }}</span>
                        <span class="text-[var(--text-muted)] text-[10px] block font-numeric">{{ $invoice->voided_at?->format('Y-m-d H:i') }}</span>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Consolidated Line Items Table -->
        <div class="erp-card">
            <div class="erp-card-header flex items-center justify-between">
                <div class="erp-card-title">
                    <x-icon name="list" size="18" />
                    <span>بنود الفاتورة المجمعة (Consolidated Sales Items)</span>
                </div>
                <span class="text-xs text-[var(--text-muted)] bg-[var(--surface-muted)] px-3 py-1 rounded border border-[var(--border-color)]">
                    تظهر البنود مجمعة للعميل، مع حفظ تفاصيل الدفعات والتكلفة محاسبياً
                </span>
            </div>

            <div class="erp-card-body p-0">
                <div class="erp-table-responsive">
                    <table class="erp-table">
                        <thead>
                            <tr>
                                <th class="w-12 text-center">#</th>
                                <th>الصنف والوصف</th>
                                <th class="w-28 text-center">وحدة البيع</th>
                                <th class="w-24 text-center">الكمية المباعة</th>
                                <th class="w-28 text-center">الكمية الأساسية</th>
                                <th class="w-28 text-center">سعر الوحدة</th>
                                <th class="w-24 text-center">خصم البند</th>
                                <th class="w-28 text-center">صافي البيع</th>
                                @if($canViewCogs)
                                <th class="w-28 text-center">تكلفة FIFO</th>
                                <th class="w-28 text-center">الربح المحقق</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($invoice->items as $item)
                                <tr>
                                    <td class="text-center font-numeric">{{ $loop->iteration }}</td>
                                    <td>
                                        <div class="font-bold text-[var(--text-main)]">{{ $item->product->name ?? 'صنف غير معروف' }}</div>
                                        <div class="text-[11px] text-[var(--text-muted)] font-numeric">
                                            كود: {{ $item->product->sku ?? '—' }}
                                            @if($item->product->barcode)
                                                · باركود: {{ $item->product->barcode }}
                                            @endif
                                        </div>
                                        @if($item->notes)
                                            <div class="text-[11px] text-amber-700 mt-0.5">ملاحظة: {{ $item->notes }}</div>
                                        @endif
                                    </td>
                                    <td class="text-center text-xs">
                                        {{ $item->unit->name ?? 'قطعة' }}
                                        @if((float)$item->conversion_factor != 1.0)
                                            <div class="text-[10px] text-[var(--text-muted)] font-numeric">معامل: {{ (float)$item->conversion_factor }}</div>
                                        @endif
                                    </td>
                                    <td class="text-center font-numeric font-semibold">
                                        {{ (float)$item->quantity }}
                                    </td>
                                    <td class="text-center font-numeric text-xs text-[var(--text-muted)]">
                                        {{ (float)$item->base_quantity }} قطعة
                                    </td>
                                    <td class="text-center font-numeric">
                                        {{ number_format((float)$item->unit_price, 2) }} ج.م
                                    </td>
                                    <td class="text-center font-numeric text-xs text-amber-600">
                                        {{ (float)$item->line_discount > 0 ? number_format((float)$item->line_discount, 2) . ' ج.م' : '—' }}
                                    </td>
                                    <td class="text-center font-numeric font-bold text-[var(--text-main)]">
                                        {{ number_format((float)$item->subtotal, 2) }} ج.م
                                    </td>
                                    @if($canViewCogs)
                                    <td class="text-center font-numeric text-xs text-[var(--text-muted)]">
                                        {{ number_format((float)$item->total_cogs, 2) }} ج.م
                                    </td>
                                    <td class="text-center font-numeric text-xs font-bold {{ (float)$item->total_profit >= 0 ? 'text-[var(--success)]' : 'text-[var(--danger)]' }}">
                                        {{ number_format((float)$item->total_profit, 2) }} ج.م
                                    </td>
                                    @endif
                                </tr>

                                @if($invoice->status === 'posted' && $item->batchAllocations->isNotEmpty())
                                    <tr class="bg-[var(--surface-muted)]">
                                        <td colspan="{{ $canViewCogs ? 10 : 8 }}" class="py-2.5 px-6">
                                            <div class="text-xs font-semibold text-[var(--primary)] mb-1 flex items-center gap-1">
                                                <x-icon name="layers" size="14" />
                                                <span>تفاصيل توزيع دفعات المخزن (FIFO Batch Allocation):</span>
                                            </div>
                                            <div class="erp-table-responsive">
                                                <table class="erp-table text-xs border border-[var(--border-color)]">
                                                    <thead>
                                                        <tr class="bg-[var(--bg-surface-alt)]">
                                                            <th>رقم الدفعة</th>
                                                            <th>المستودع</th>
                                                            <th>الكمية المنصرفة</th>
                                                            <th>سعر البيع المطبق</th>
                                                            @if($canViewCogs)
                                                            <th>تكلفة القطعة (Base Cost)</th>
                                                            <th>إجمالي التكلفة (COGS)</th>
                                                            <th>هامش الربح</th>
                                                            @endif
                                                            <th>حالة التخصيص</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($item->batchAllocations as $alloc)
                                                            <tr class="hover:bg-[var(--bg-hover)]">
                                                                <td class="font-numeric font-bold text-[var(--primary)]">
                                                                    {{ $alloc->batch->batch_number ?? '#' . $alloc->inventory_batch_id }}
                                                                </td>
                                                                <td>{{ $alloc->batch->warehouse->name ?? 'المخزن' }}</td>
                                                                <td class="font-numeric font-semibold">{{ (float)($alloc->quantity_base ?? $alloc->quantity) }} {{ $item->product->unit ?? 'قطعة' }}</td>
                                                                <td class="font-numeric">{{ number_format((float)($alloc->actual_selling_price_base ?? $alloc->unit_price), 2) }} ج.م</td>
                                                                @if($canViewCogs)
                                                                <td class="font-numeric text-[var(--text-muted)]">{{ number_format((float)($alloc->unit_cost_base ?? $alloc->unit_cost), 4) }} ج.م</td>
                                                                <td class="font-numeric">{{ number_format((float)$alloc->total_cogs, 2) }} ج.م</td>
                                                                <td class="font-numeric font-bold {{ (float)($alloc->profit_margin ?? $alloc->profit) >= 0 ? 'text-[var(--success)]' : 'text-[var(--danger)]' }}">
                                                                    {{ number_format((float)($alloc->profit_margin ?? $alloc->profit), 2) }} ج.م
                                                                </td>
                                                                @endif
                                                                <td>
                                                                    @if($alloc->is_reversed)
                                                                        <span class="erp-badge erp-badge-danger text-[10px]">مسترجع / معكوس</span>
                                                                    @else
                                                                        <span class="erp-badge erp-badge-success text-[10px]">سارٍ ومصروف</span>
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Payments & Installments History Card -->
        <div class="erp-card">
            <div class="erp-card-header flex items-center justify-between">
                <div class="erp-card-title flex items-center gap-2">
                    <x-icon name="dollar-sign" size="18" class="text-emerald-600" />
                    <span>سجل دفعات وأقساط الفاتورة (سندات القبض المالية)</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="erp-badge font-numeric {{ $invoice->payment_status_badge_class }} text-xs">
                        {{ $invoice->payment_status_label }}
                    </span>
                    @if($invoice->status === 'posted' && (float)$invoice->remaining_amount > 0)
                        <button type="button" class="erp-btn erp-btn-sm erp-btn-success" data-action="open-payment-modal">
                            <x-icon name="plus" size="14" />
                            <span>إضافة دفعة سداد</span>
                        </button>
                    @endif
                </div>
            </div>

            <div class="erp-card-body p-0">
                @if($invoice->payments->count() > 0)
                    <div class="erp-table-responsive">
                        <table class="erp-table text-xs">
                            <thead>
                                <tr>
                                    <th class="w-12 text-center">#</th>
                                    <th>رقم سند القبض</th>
                                    <th>تاريخ السداد</th>
                                    <th>طريقة الدفع</th>
                                    <th>مرجع / شيك</th>
                                    <th class="text-left">المبلغ المسدد</th>
                                    <th>المحصل</th>
                                    <th>ملاحظات</th>
                                    <th class="text-center w-28">الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($invoice->payments as $index => $pay)
                                    <tr>
                                        <td class="text-center font-numeric text-[var(--text-muted)]">{{ $index + 1 }}</td>
                                        <td class="font-numeric font-bold text-[var(--primary)]">{{ $pay->payment_number }}</td>
                                        <td class="font-numeric">{{ $pay->payment_date ? $pay->payment_date->format('Y-m-d') : '—' }}</td>
                                        <td>
                                            <span class="erp-badge {{ $pay->payment_method_badge_class }} text-[11px]">
                                                {{ $pay->payment_method_label }}
                                            </span>
                                        </td>
                                        <td class="font-mono text-xs">{{ $pay->reference_number ?: '—' }}</td>
                                        <td class="text-left font-numeric font-bold text-emerald-600 text-sm">
                                            {{ number_format((float)$pay->amount, 2) }} ج.م
                                        </td>
                                        <td>{{ $pay->creator->name ?? '—' }}</td>
                                        <td class="text-[var(--text-muted)]">{{ $pay->notes ?: '—' }}</td>
                                        <td class="text-center">
                                            <div class="inline-flex items-center gap-1.5">
                                                <a href="{{ route('sales.payments.receipt', $pay) }}" target="_blank" class="erp-btn erp-btn-ghost erp-btn-sm" title="طباعة سند القبض">
                                                    <x-icon name="printer" size="14" />
                                                    <span>طباعة</span>
                                                </a>
                                                @if(auth()->user()?->isAdmin())
                                                    <form action="{{ route('sales.payments.destroy', $pay) }}" method="POST" class="inline" data-confirm="هل أنت متأكد من إلغاء وحذف سند القبض رقم {{ $pay->payment_number }}؟ سيتم استرجاع الرصيد إلى الفاتورة وحساب العميل آلياً.">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="erp-btn erp-btn-ghost erp-btn-sm text-[var(--danger)]" title="إلغاء سند القبض">
                                                            <x-icon name="trash" size="14" />
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
                @else
                    <div class="p-6 text-center text-xs">
                        @if((float)$invoice->remaining_amount <= 0.001)
                            <div class="text-emerald-600 font-bold flex items-center justify-center gap-1.5">
                                <x-icon name="check-circle" size="16" />
                                <span>تم سداد هذه الفاتورة بالكامل عند الشراء. لا توجد دفعات متبقية.</span>
                            </div>
                        @else
                            <p class="text-[var(--text-muted)] mb-3">
                                لم يتم تسجيل أي دفعات سداد إضافية على هذه الفاتورة حتى الآن. المتبقي الآجل حالياً:
                                <strong class="text-rose-600 font-numeric font-bold text-sm">{{ number_format((float)$invoice->remaining_amount, 2) }} ج.م</strong>
                            </p>
                            @if($invoice->status === 'posted')
                                <button type="button" class="erp-btn erp-btn-sm erp-btn-success" data-action="open-payment-modal">
                                    <x-icon name="plus" size="14" />
                                    <span>تسجيل دفعة الآن</span>
                                </button>
                            @endif
                        @endif
                    </div>
                @endif
            </div>
        </div>

        <!-- Invoice Totals & Summary Card -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Notes Card -->
            <div class="erp-card">
                <div class="erp-card-header">
                    <div class="erp-card-title">
                        <x-icon name="file-text" size="18" />
                        <span>ملاحظات الفاتورة</span>
                    </div>
                </div>
                <div class="erp-card-body text-xs text-[var(--text-muted)]">
                    {{ $invoice->notes ?: 'لا توجد ملاحظات إضافية مسجلة لهذه الفاتورة.' }}
                </div>
            </div>

            <!-- Financial Summary Box -->
            <div class="erp-card">
                <div class="erp-card-header">
                    <div class="erp-card-title">
                        <x-icon name="calculator" size="18" />
                        <span>الملخص المالي للفاتورة</span>
                    </div>
                </div>
                <div class="erp-card-body p-0">
                    <table class="erp-table text-xs">
                        <tbody>
                            <tr>
                                <td class="text-[var(--text-muted)] font-semibold">إجمالي بنود المبيعات:</td>
                                <td class="text-left font-numeric font-bold">{{ number_format((float)$invoice->subtotal, 2) }} ج.م</td>
                            </tr>
                            @if((float)$invoice->discount_amount > 0)
                            <tr>
                                <td class="text-amber-600 font-semibold">
                                    خصم الفاتورة 
                                    @if($invoice->discount_type === 'percentage')
                                        ({{ (float)$invoice->discount_value }}%):
                                    @else
                                        (قيمة نقدية):
                                    @endif
                                </td>
                                <td class="text-left font-numeric font-bold text-amber-600">- {{ number_format((float)$invoice->discount_amount, 2) }} ج.م</td>
                            </tr>
                            @endif
                            @if((float)$invoice->shipping_cost > 0)
                            <tr>
                                <td class="text-[var(--text-muted)] font-semibold">مصاريف الشحن / التوصيل:</td>
                                <td class="text-left font-numeric font-bold">+ {{ number_format((float)$invoice->shipping_cost, 2) }} ج.م</td>
                            </tr>
                            @endif
                            <tr class="bg-[var(--surface-muted)] font-bold text-sm">
                                <td class="text-[var(--text-main)]">صافي إجمالي الفاتورة:</td>
                                <td class="text-left font-numeric text-[var(--primary)] text-base">{{ number_format((float)$invoice->total_amount, 2) }} ج.م</td>
                            </tr>
                            <tr>
                                <td class="text-[var(--text-muted)] font-semibold">طريقة السداد الأساسية:</td>
                                <td class="text-left">
                                    <span class="erp-badge {{ $invoice->payment_type_badge_class }} text-xs">{{ $invoice->payment_type_label }}</span>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-[var(--text-muted)] font-semibold">حالة السداد الحالية:</td>
                                <td class="text-left">
                                    <span class="erp-badge {{ $invoice->payment_status_badge_class }} text-xs">{{ $invoice->payment_status_label }}</span>
                                </td>
                            </tr>
                            @php
                                $subsequentPaymentsSum = (float) $invoice->payments->sum('amount');
                                $initialPaid = max(0.00, (float) $invoice->paid_amount - $subsequentPaymentsSum);
                            @endphp
                            @if($subsequentPaymentsSum > 0)
                            <tr>
                                <td class="text-[var(--text-muted)]">المسدد عند إنشاء الفاتورة:</td>
                                <td class="text-left font-numeric text-xs">{{ number_format($initialPaid, 2) }} ج.م</td>
                            </tr>
                            <tr>
                                <td class="text-emerald-700 font-semibold">إجمالي الدفعات اللاحقة المسددة:</td>
                                <td class="text-left font-numeric font-bold text-emerald-600">+ {{ number_format($subsequentPaymentsSum, 2) }} ج.م</td>
                            </tr>
                            @endif
                            <tr>
                                <td class="text-emerald-700 font-bold">إجمالي ما تم سداده:</td>
                                <td class="text-left font-numeric font-bold text-emerald-600 text-sm">{{ number_format((float)($invoice->paid_amount ?? 0), 2) }} ج.م</td>
                            </tr>
                            <tr>
                                <td class="text-rose-700 font-bold">المتبقي الآجل حالياً:</td>
                                <td class="text-left font-numeric font-black text-rose-600 text-base">
                                    @if((float)$invoice->remaining_amount <= 0.001)
                                        <span class="text-emerald-600">0.00 ج.م (خالص)</span>
                                    @else
                                        {{ number_format((float)$invoice->remaining_amount, 2) }} ج.م
                                    @endif
                                </td>
                            </tr>
                            @if($invoice->customer && $invoice->customer->name !== 'عميل نقدي')
                                @php
                                    $isPosted = $invoice->status === 'posted';
                                    $custCurrentBal = (float) $invoice->customer->current_balance;
                                    $remAmount = (float) ($invoice->remaining_amount ?? 0);
                                    $custPrevBal = $isPosted ? round($custCurrentBal - $remAmount, 2) : round($custCurrentBal, 2);
                                    $custFinalBal = $isPosted ? round($custCurrentBal, 2) : round($custCurrentBal + $remAmount, 2);
                                @endphp
                                <tr>
                                    <td class="text-[var(--text-muted)] font-semibold">الرصيد السابق للعميل (قبل الفاتورة):</td>
                                    <td class="text-left font-numeric font-bold">
                                        @if($custPrevBal > 0)
                                            <span class="text-rose-600">{{ number_format($custPrevBal, 2) }} ج.م (مدين عليه)</span>
                                        @elseif($custPrevBal < 0)
                                            <span class="text-emerald-600">{{ number_format(abs($custPrevBal), 2) }} ج.م (رصيد دائن له)</span>
                                        @else
                                            <span class="text-[var(--text-muted)]">0.00 ج.م (متزن)</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr class="bg-[var(--surface-muted)]">
                                    <td class="text-[var(--text-main)] font-bold">إجمالي حساب العميل بعد الفاتورة:</td>
                                    <td class="text-left font-numeric font-black">
                                        @if($custFinalBal > 0)
                                            <span class="text-rose-600">{{ number_format($custFinalBal, 2) }} ج.م (مستحق عليه)</span>
                                        @elseif($custFinalBal < 0)
                                            <span class="text-emerald-600">{{ number_format(abs($custFinalBal), 2) }} ج.م (رصيد دائن له)</span>
                                        @else
                                            <span class="text-[var(--text-muted)]">0.00 ج.م (متزن)</span>
                                        @endif
                                    </td>
                                </tr>
                            @endif
                            @if($invoice->due_date)
                            <tr>
                                <td class="text-amber-700 font-semibold">تاريخ الاستحقاق:</td>
                                <td class="text-left font-numeric text-amber-700 font-bold">{{ $invoice->due_date->format('Y-m-d') }}</td>
                            </tr>
                            @endif
                            @if($canViewCogs && $invoice->status === 'posted')
                            <tr>
                                <td class="text-[var(--text-muted)] font-semibold">إجمالي تكلفة البضاعة المباعة (COGS):</td>
                                <td class="text-left font-numeric">{{ number_format((float)$invoice->total_cogs, 2) }} ج.م</td>
                            </tr>
                            <tr class="bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 font-bold">
                                <td>صافي ربح الفاتورة:</td>
                                <td class="text-left font-numeric">{{ number_format((float)$invoice->total_profit, 2) }} ج.م</td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @if($invoice->status === 'posted' && (float)$invoice->remaining_amount > 0)
    <!-- Record Payment Modal -->
    <div id="recordPaymentModal" class="erp-modal-overlay hidden" role="dialog" aria-modal="true">
        <div class="erp-modal-container max-w-lg">
            <form action="{{ route('sales.payments.store', $invoice) }}" method="POST" id="recordPaymentForm">
                @csrf
                <div class="erp-modal-header">
                    <div class="erp-modal-title flex items-center gap-2">
                        <x-icon name="dollar-sign" size="18" class="text-emerald-600" />
                        <span>تسجيل دفعة سداد نقدية / سند قبض</span>
                    </div>
                    <button type="button" class="erp-modal-close" data-action="close-payment-modal" aria-label="إغلاق">✕</button>
                </div>
                <div class="erp-modal-body p-5 flex flex-col gap-4 text-xs">
                    <div class="p-3 bg-emerald-500/10 border border-emerald-500/20 rounded-lg flex items-center justify-between">
                        <div>
                            <span class="text-[var(--text-muted)] block text-[11px]">العميل:</span>
                            <strong class="text-sm text-[var(--text-main)]">{{ $invoice->customer->name }}</strong>
                            <span class="text-[10px] text-[var(--text-muted)] block">فاتورة: #{{ $invoice->invoice_number }}</span>
                        </div>
                        <div class="text-left">
                            <span class="text-[var(--text-muted)] block text-[11px]">المتبقي الآجل:</span>
                            <strong class="font-numeric font-black text-base text-rose-600" id="modalRemainingAmount">{{ number_format((float)$invoice->remaining_amount, 2) }} ج.م</strong>
                        </div>
                    </div>

                    <div class="erp-form-group">
                        <div class="flex items-center justify-between mb-1">
                            <label for="paymentAmount" class="erp-form-label font-bold mb-0">
                                مبلغ الدفعة المسدد (ج.م) <span class="text-[var(--danger)]">*</span>
                            </label>
                            <button type="button" id="payFullRemainingBtn" class="text-xs text-[var(--primary)] font-semibold hover:underline">
                                سداد كامل المتبقي ({{ number_format((float)$invoice->remaining_amount, 2) }})
                            </button>
                        </div>
                        <input 
                            type="number" 
                            id="paymentAmount" 
                            name="amount" 
                            step="0.01" 
                            min="0.01" 
                            max="{{ (float)$invoice->remaining_amount }}" 
                            value="{{ (float)$invoice->remaining_amount }}" 
                            class="erp-input font-numeric font-bold text-base" 
                            required
                        >
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="erp-form-group">
                            <label for="paymentDate" class="erp-form-label">
                                تاريخ الدفعة <span class="text-[var(--danger)]">*</span>
                            </label>
                            <input 
                                type="date" 
                                id="paymentDate" 
                                name="payment_date" 
                                value="{{ date('Y-m-d') }}" 
                                class="erp-input font-numeric" 
                                required
                            >
                        </div>

                        <div class="erp-form-group">
                            <label for="paymentMethod" class="erp-form-label">
                                طريقة السداد <span class="text-[var(--danger)]">*</span>
                            </label>
                            <select id="paymentMethod" name="payment_method" class="erp-input" required>
                                <option value="cash" selected>نقدي (بالدرج / الخزينة)</option>
                                <option value="bank_transfer">تحويل بنكي / فودافون كاش</option>
                                <option value="cheque">شيك بنكي</option>
                                <option value="card">شبكة / بطاقة دفع (POS)</option>
                            </select>
                        </div>
                    </div>

                    <div class="erp-form-group">
                        <label for="paymentRef" class="erp-form-label">
                            رقم المرجع / رقم الشيك (اختياري)
                        </label>
                        <input 
                            type="text" 
                            id="paymentRef" 
                            name="reference_number" 
                            class="erp-input" 
                            placeholder="مثال: رقم التحويل البنكي أو رقم الشيك"
                        >
                    </div>

                    <div class="erp-form-group">
                        <label for="paymentNotes" class="erp-form-label">
                            ملاحظات الدفعة
                        </label>
                        <textarea 
                            id="paymentNotes" 
                            name="notes" 
                            rows="2" 
                            class="erp-input" 
                            placeholder="أي تفاصيل أو ملاحظات تخص هذا السند..."
                        ></textarea>
                    </div>
                </div>
                <div class="erp-modal-footer p-4 flex justify-end gap-2 bg-[var(--surface-muted)] border-t border-[var(--border-color)]">
                    <button type="button" class="erp-btn erp-btn-secondary" data-action="close-payment-modal">إلغاء</button>
                    <button type="submit" class="erp-btn erp-btn-success">
                        <x-icon name="check" size="16" />
                        <span>تأكيد السداد وحفظ سند القبض</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const modal = document.getElementById('recordPaymentModal');
            if (!modal) return;

            const openBtns = document.querySelectorAll('[data-action="open-payment-modal"]');
            const closeBtns = modal.querySelectorAll('[data-action="close-payment-modal"]');
            const payFullBtn = document.getElementById('payFullRemainingBtn');
            const amountInput = document.getElementById('paymentAmount');
            const fullRemaining = {{ (float)$invoice->remaining_amount }};

            openBtns.forEach(btn => {
                btn.addEventListener('click', () => {
                    modal.classList.remove('hidden');
                    modal.classList.add('open');
                    if (amountInput) setTimeout(() => amountInput.focus(), 100);
                });
            });

            closeBtns.forEach(btn => {
                btn.addEventListener('click', () => {
                    modal.classList.add('hidden');
                    modal.classList.remove('open');
                });
            });

            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.classList.add('hidden');
                    modal.classList.remove('open');
                }
            });

            if (payFullBtn && amountInput) {
                payFullBtn.addEventListener('click', () => {
                    amountInput.value = fullRemaining.toFixed(2);
                });
            }
        });
    </script>
    @endif
@endsection
