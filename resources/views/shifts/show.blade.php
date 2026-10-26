@extends('layouts.app')

@section('control_panel')
    <x-control-panel title="تفاصيل الوردية: {{ $shift->shift_number }}" :breadcrumbs="['المبيعات' => route('sales.index'), 'ورديات الكاشير' => route('shifts.index'), $shift->shift_number => '#']">
        <x-slot:actions>
            <!-- Print Combobox Dropdown -->
            <x-print-dropdown 
                title="طباعة التقرير"
                code="{{ $shift->shift_number }}"
                urlThermal="{{ route('shifts.print', [$shift, 'format' => 'thermal_80mm']) }}"
                directThermal="{{ route('shifts.direct-print', [$shift, 'format' => 'thermal_80mm']) }}"
                urlA4="{{ route('shifts.print', [$shift, 'format' => 'a4']) }}"
                urlA5="{{ route('shifts.print', [$shift, 'format' => 'a5']) }}"
            />
            @if($shift->status === 'open' && (auth()->id() === $shift->user_id || auth()->user()->isAdmin()))
                <button type="button" id="closeThisShiftBtn" class="erp-btn erp-btn-danger close-shift-trigger-btn"
                    data-shift-id="{{ $shift->id }}"
                    data-shift-number="{{ $shift->shift_number }}"
                    data-expected="{{ $shift->expected_closing_balance }}"
                    data-opening="{{ $shift->opening_balance }}"
                    data-sales="{{ $shift->cash_sales_amount }}"
                    data-additions="{{ $shift->cash_additions }}"
                    data-expenses="{{ $shift->cash_expenses }}"
                    data-cashier="{{ $shift->user?->name }}"
                    data-warehouse="{{ $shift->warehouse?->name }}">
                    <x-icon name="lock" size="16" />
                    <span>إغلاق الوردية وجرد الدرج</span>
                </button>
            @endif
            <a href="{{ route('shifts.index') }}" class="erp-btn erp-btn-ghost">
                <x-icon name="arrow-right" size="16" />
                <span>العودة للورديات</span>
            </a>
        </x-slot:actions>

        <x-slot:extra>
            @if($shift->status === 'open')
                <span class="shift-status-badge open">وردية نشطة (مفتوحة)</span>
            @else
                <span class="shift-status-badge closed">وردية مغلقة</span>
            @endif
        </x-slot:extra>
    </x-control-panel>
@endsection

@section('content')
    <div data-page="shift-show">
        <!-- Status Alerts -->
        @if(session('status') === 'success')
            <div class="erp-alert erp-alert-success mb-4" role="alert">
                <x-icon name="check-circle" size="18" />
                <span>{{ session('message') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div class="erp-alert erp-alert-danger mb-4" role="alert">
                <x-icon name="alert-triangle" size="18" />
                <span>{{ session('error') }}</span>
            </div>
        @endif

        <!-- 1. Key Metrics Cards -->
        <div class="shift-summary-grid">
            <div class="shift-kpi-card">
                <div class="shift-kpi-icon bg-blue-500/10 text-blue-500">
                    <x-icon name="credit-card" size="22" />
                </div>
                <div>
                    <div class="shift-kpi-label">رصيد بداية العهدة</div>
                    <div class="shift-kpi-value font-numeric">{{ number_format($shift->opening_balance, 2) }} <span class="text-xs font-normal">ج.م</span></div>
                </div>
            </div>

            <div class="shift-kpi-card">
                <div class="shift-kpi-icon bg-emerald-500/10 text-emerald-500">
                    <x-icon name="shopping-cart" size="22" />
                </div>
                <div>
                    <div class="shift-kpi-label">إجمالي المبيعات النقدية</div>
                    <div class="shift-kpi-value font-numeric text-emerald-500">{{ number_format($shift->cash_sales_amount, 2) }} <span class="text-xs font-normal">ج.م</span></div>
                </div>
            </div>

            <div class="shift-kpi-card">
                <div class="shift-kpi-icon bg-purple-500/10 text-purple-500">
                    <x-icon name="calculator" size="22" />
                </div>
                <div>
                    <div class="shift-kpi-label">الرصيد الدفتري المتوقع</div>
                    <div class="shift-kpi-value font-numeric">{{ number_format($shift->expected_closing_balance, 2) }} <span class="text-xs font-normal">ج.م</span></div>
                </div>
            </div>

            <div class="shift-kpi-card">
                <div class="shift-kpi-icon {{ $shift->difference < 0 ? 'bg-red-500/10 text-red-500' : ($shift->difference > 0 ? 'bg-blue-500/10 text-blue-500' : 'bg-emerald-500/10 text-emerald-500') }}">
                    <x-icon name="scale" size="22" />
                </div>
                <div>
                    <div class="shift-kpi-label">جرد الدرج الفعلي / الفارق</div>
                    <div class="shift-kpi-value font-numeric">
                        @if($shift->actual_closing_balance !== null)
                            {{ number_format($shift->actual_closing_balance, 2) }} <span class="text-xs font-normal">ج.م</span>
                            <div class="text-xs mt-1">
                                @if($shift->difference == 0)
                                    <span class="text-emerald-500 font-bold">مطابق تماماً (0)</span>
                                @elseif($shift->difference > 0)
                                    <span class="text-blue-500 font-bold">زيادة: +{{ number_format($shift->difference, 2) }} ج.م</span>
                                @else
                                    <span class="text-red-500 font-bold">عجز: {{ number_format($shift->difference, 2) }} ج.م</span>
                                @endif
                            </div>
                        @else
                            <span class="text-xs text-[var(--text-muted)] font-normal">الوردية لا تزال مفتوحة</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">
            <!-- Shift Info Card -->
            <div class="erp-card p-4">
                <h3 class="font-bold text-sm text-[var(--text-main)] mb-3 pb-2 border-b border-[var(--border-color)] flex items-center gap-2">
                    <x-icon name="info" size="16" class="text-[var(--primary)]" />
                    <span>بيانات الوردية</span>
                </h3>
                <div class="space-y-2 text-xs">
                    <div class="flex justify-between py-1 border-b border-[var(--border-color)]">
                        <span class="text-[var(--text-muted)]">الكاشير المسؤول:</span>
                        <strong class="text-[var(--text-main)]">{{ $shift->user?->name }}</strong>
                    </div>
                    @if($shift->opened_by)
                    <div class="flex justify-between py-1 border-b border-[var(--border-color)]">
                        <span class="text-[var(--text-muted)]">المشرف فاتح الوردية:</span>
                        <strong class="text-[var(--primary)]">{{ $shift->openedBy?->name ?? 'مدير النظام' }}</strong>
                    </div>
                    @endif
                    <div class="flex justify-between py-1 border-b border-[var(--border-color)]">
                        <span class="text-[var(--text-muted)]">المستودع / الفرع:</span>
                        <strong class="text-[var(--text-main)]">{{ $shift->warehouse?->name }}</strong>
                    </div>
                    <div class="flex justify-between py-1 border-b border-[var(--border-color)]">
                        <span class="text-[var(--text-muted)]">تاريخ ووقت الفتح:</span>
                        <span class="font-numeric">{{ $shift->opened_at->format('Y-m-d H:i') }}</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-[var(--border-color)]">
                        <span class="text-[var(--text-muted)]">تاريخ ووقت الإغلاق:</span>
                        <span class="font-numeric">{{ $shift->closed_at ? $shift->closed_at->format('Y-m-d H:i') : 'لا تزال مفتوحة' }}</span>
                    </div>
                    @if($shift->closedBy)
                        <div class="flex justify-between py-1 border-b border-[var(--border-color)]">
                            <span class="text-[var(--text-muted)]">المستلم / معتمد الإغلاق:</span>
                            <span class="font-bold text-[var(--primary)]">{{ $shift->closedBy->name }}</span>
                        </div>
                    @endif
                    @if($shift->opening_notes)
                        <div class="py-1">
                            <span class="text-[var(--text-muted)] block mb-1">ملاحظات الافتتاح:</span>
                            <p class="bg-[var(--surface-muted)] p-2 rounded text-[11px] text-[var(--text-main)] m-0">{{ $shift->opening_notes }}</p>
                        </div>
                    @endif
                    @if($shift->closing_notes)
                        <div class="py-1">
                            <span class="text-[var(--text-muted)] block mb-1">ملاحظات الإغلاق:</span>
                            <p class="bg-[var(--surface-muted)] p-2 rounded text-[11px] text-[var(--text-main)] m-0">{{ $shift->closing_notes }}</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Financial Calculation Breakdown -->
            <div class="erp-card p-4 lg:col-span-2">
                <h3 class="font-bold text-sm text-[var(--text-main)] mb-3 pb-2 border-b border-[var(--border-color)] flex items-center gap-2">
                    <x-icon name="dollar-sign" size="16" class="text-emerald-500" />
                    <span>المعادلة الحسابية للدرج (Cash Drawer Balance)</span>
                </h3>

                <div class="shift-calc-box mt-0">
                    <div class="shift-calc-row">
                        <span>(+) رصيد بداية العهدة النقدية (Opening Cash)</span>
                        <span class="font-numeric font-bold">{{ number_format($shift->opening_balance, 2) }} ج.م</span>
                    </div>
                    <div class="shift-calc-row text-emerald-500">
                        <span>(+) إجمالي المبيعات النقدية الصادرة بالوردية (Cash Sales)</span>
                        <span class="font-numeric font-bold">+{{ number_format($shift->cash_sales_amount, 2) }} ج.م</span>
                    </div>
                    <div class="shift-calc-row text-blue-500">
                        <span>(+) إيداعات نقدية إضافية بالدرج (Cash Additions)</span>
                        <span class="font-numeric font-bold">+{{ number_format($shift->cash_additions, 2) }} ج.م</span>
                    </div>
                    <div class="shift-calc-row text-amber-500">
                        <span>(−) مرتجعات نقدية منصرفة من الدرج (Cash Returns)</span>
                        <span class="font-numeric font-bold">−{{ number_format($shift->cash_returns_amount, 2) }} ج.م</span>
                    </div>
                    <div class="shift-calc-row text-red-500">
                        <span>(−) مسحوبات ومصروفات نثرية منصرفة (Cash Expenses)</span>
                        <span class="font-numeric font-bold">−{{ number_format($shift->cash_expenses, 2) }} ج.م</span>
                    </div>
                    <div class="shift-calc-row text-[var(--text-main)]">
                        <span>(=) الرصيد الدفتري المتوقع بالدرج (Expected Cash)</span>
                        <span class="font-numeric text-emerald-500">{{ number_format($shift->expected_closing_balance, 2) }} ج.م</span>
                    </div>
                    @if($shift->actual_closing_balance !== null)
                        <div class="shift-calc-row text-[var(--text-main)]">
                            <span>الجرد الفعلي المحسوب عند الإغلاق (Actual Cash)</span>
                            <span class="font-numeric font-bold">{{ number_format($shift->actual_closing_balance, 2) }} ج.م</span>
                        </div>
                        <div class="shift-calc-row {{ $shift->difference < 0 ? 'text-red-500' : ($shift->difference > 0 ? 'text-blue-500' : 'text-emerald-500') }}">
                            <span>صافي الفارق (الزيادة / العجز)</span>
                            <span class="font-numeric font-bold">
                                {{ $shift->difference > 0 ? '+' : '' }}{{ number_format($shift->difference, 2) }} ج.م
                                ({{ $shift->difference == 0 ? 'متطابق' : ($shift->difference > 0 ? 'فائض' : 'عجز') }})
                            </span>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- 2. Cash Drawer Movements Timeline -->
        <div class="erp-card mb-4 overflow-hidden">
            <div class="p-3 bg-[var(--surface-muted)] border-b border-[var(--border-color)] flex items-center justify-between">
                <h3 class="font-bold text-sm text-[var(--text-main)] flex items-center gap-2 m-0">
                    <x-icon name="list" size="16" class="text-blue-500" />
                    <span>سجل حركات الخزينة والدرج للوردية (Cash Movements Log)</span>
                </h3>
                <span class="erp-badge erp-badge-neutral font-numeric">{{ $shift->cashMovements->count() }} حركة</span>
            </div>
            <div class="erp-table-responsive">
                <table class="erp-table">
                    <thead>
                        <tr>
                            <th class="w-12 text-center">#</th>
                            <th class="w-32">نوع الحركة</th>
                            <th class="text-center w-28">المبلغ (ج.م)</th>
                            <th>البيان والملاحظات</th>
                            <th class="w-36">المستخدم</th>
                            <th class="w-36 text-center">التوقيت</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($shift->cashMovements as $mov)
                            <tr>
                                <td class="text-center font-numeric text-xs">{{ $loop->iteration }}</td>
                                <td>
                                    <span class="shift-diff-badge {{ in_array($mov->movement_type, ['opening', 'sale', 'cash_in']) ? 'surplus' : ($mov->movement_type === 'closing' ? 'matched' : 'deficit') }}">
                                        {{ $mov->movement_type_label }}
                                    </span>
                                </td>
                                <td class="text-center font-numeric font-bold text-xs">
                                    {{ number_format($mov->amount, 2) }}
                                </td>
                                <td class="text-xs">
                                    {{ $mov->notes ?? '—' }}
                                </td>
                                <td class="text-xs font-bold text-[var(--text-main)]">
                                    {{ $mov->user?->name ?? '—' }}
                                </td>
                                <td class="text-center font-numeric text-xs">
                                    {{ $mov->created_at->format('Y-m-d H:i:s') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-6 text-[var(--text-muted)]">
                                    لا توجد حركات مسجلة بالدرج حتى الآن.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 3. Sales Invoices Issued in this Shift -->
        <div class="erp-card overflow-hidden">
            <div class="p-3 bg-[var(--surface-muted)] border-b border-[var(--border-color)] flex items-center justify-between">
                <h3 class="font-bold text-sm text-[var(--text-main)] flex items-center gap-2 m-0">
                    <x-icon name="shopping-cart" size="16" class="text-emerald-500" />
                    <span>فواتير المبيعات الصادرة في هذه الوردية</span>
                </h3>
                <span class="erp-badge erp-badge-neutral font-numeric">{{ $shift->salesInvoices->count() }} فاتورة</span>
            </div>
            <div class="erp-table-responsive">
                <table class="erp-table">
                    <thead>
                        <tr>
                            <th class="w-36">رقم الفاتورة</th>
                            <th>العميل</th>
                            <th class="text-center">فئة السعر</th>
                            <th class="text-center">إجمالي الفاتورة</th>
                            <th class="text-center">الحالة</th>
                            <th class="text-center">التاريخ</th>
                            <th class="text-center w-20">معاينة</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($shift->salesInvoices as $inv)
                            <tr>
                                <td>
                                    <a href="{{ route('sales.show', $inv) }}" class="font-numeric font-bold text-[var(--primary)] hover:underline">
                                        {{ $inv->invoice_number }}
                                    </a>
                                </td>
                                <td>{{ $inv->customer?->name ?? 'عميل نقدي' }}</td>
                                <td class="text-center text-xs">
                                    <span class="erp-badge erp-badge-neutral">{{ $inv->price_tier }}</span>
                                </td>
                                <td class="text-center font-numeric font-bold text-xs">
                                    {{ number_format($inv->total_amount, 2) }} ج.م
                                </td>
                                <td class="text-center">
                                    <span class="erp-badge {{ $inv->status === 'posted' ? 'erp-badge-success' : 'erp-badge-warning' }}">
                                        {{ $inv->status === 'posted' ? 'مرحلة ومحصلة' : 'مسودة' }}
                                    </span>
                                </td>
                                <td class="text-center font-numeric text-xs">
                                    {{ $inv->invoice_date->format('Y-m-d') }}
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('sales.show', $inv) }}" class="erp-btn erp-btn-ghost erp-btn-sm" title="عرض الفاتورة">
                                        <x-icon name="eye" size="14" />
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-6 text-[var(--text-muted)]">
                                    لم يتم إصدار فواتير مبيعات مرتبطة بهذه الوردية بعد.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 4. Sold Products Breakdown in this Shift -->
        <div class="erp-card overflow-hidden mt-4">
            <div class="p-3 bg-[var(--surface-muted)] border-b border-[var(--border-color)] flex items-center justify-between">
                <h3 class="font-bold text-sm text-[var(--text-main)] flex items-center gap-2 m-0">
                    <x-icon name="package" size="16" class="text-[var(--primary)]" />
                    <span>تقرير الأصناف والكميات المباعة في الوردية</span>
                </h3>
                <span class="erp-badge erp-badge-info font-numeric">{{ isset($soldItems) ? count($soldItems) : 0 }} صنف مباع</span>
            </div>
            <div class="erp-table-responsive">
                <table class="erp-table">
                    <thead>
                        <tr>
                            <th class="w-12 text-center">#</th>
                            <th>اسم الصنف</th>
                            <th class="w-36">كود الصنف</th>
                            <th class="w-32 text-center">الكمية المباعة</th>
                            <th class="w-24 text-center">وحدة القياس</th>
                            <th class="w-36 text-left">إجمالي المبيعات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($soldItems ?? [] as $sItem)
                            <tr>
                                <td class="text-center font-numeric text-xs">{{ $loop->iteration }}</td>
                                <td class="font-semibold text-xs text-[var(--text-main)]">
                                    {{ $sItem->product_name }}
                                </td>
                                <td class="font-numeric font-mono text-xs">
                                    {{ $sItem->product_sku ?: '—' }}
                                </td>
                                <td class="text-center font-numeric font-bold text-sm text-emerald-600">
                                    {{ (float)$sItem->total_qty }}
                                </td>
                                <td class="text-center text-xs text-[var(--text-muted)]">
                                    {{ $sItem->unit_name ?: 'قطعة' }}
                                </td>
                                <td class="text-left font-numeric font-bold text-sm text-[var(--primary)]">
                                    {{ number_format((float)$sItem->total_amount, 2) }} ج.م
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-6 text-[var(--text-muted)]">
                                    لم يتم بيع أي أصناف مرحلة خلال هذه الوردية بعد.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Close Shift Modal -->
        <div id="closeShiftModal" class="erp-modal-overlay hidden" role="dialog" aria-modal="true">
            <div class="erp-modal-container">
                <div class="erp-modal-header">
                    <div class="erp-modal-title">
                        <x-icon name="lock" size="18" class="text-[var(--danger)]" />
                        <span>إغلاق وردية الكاشير وجرد الدرج</span>
                    </div>
                    <button type="button" class="erp-modal-close close-modal-btn" aria-label="إغلاق">
                        <x-icon name="x" size="16" />
                    </button>
                </div>
                <form id="closeShiftForm" method="POST" action="{{ route('shifts.close', $shift) }}">
                    @csrf
                    <div class="erp-modal-body">
                        <div class="text-xs text-[var(--text-muted)] mb-3">
                            رقم الوردية: <strong id="closeShiftNumberSpan" class="text-[var(--text-main)] font-numeric">{{ $shift->shift_number }}</strong>
                        </div>

                        <div class="shift-calc-box mt-0 mb-3">
                            <div class="shift-calc-row">
                                <span>كاشير الوردية:</span>
                                <strong id="closeShiftCashierSpan" class="text-[var(--text-main)]">{{ $shift->user?->name }}</strong>
                            </div>
                            <div class="shift-calc-row">
                                <span>المستودع / الفرع:</span>
                                <strong id="closeShiftWarehouseSpan" class="text-[var(--text-main)]">{{ $shift->warehouse?->name }}</strong>
                            </div>
                            <div class="shift-calc-row text-xs text-[var(--text-muted)]">
                                <span>(+) رصيد بداية العهدة:</span>
                                <span id="closeShiftOpeningSpan" class="font-numeric">{{ number_format($shift->opening_balance, 2) }} ج.م</span>
                            </div>
                            <div class="shift-calc-row text-xs text-emerald-600">
                                <span>(+) إجمالي المبيعات النقدية:</span>
                                <span id="closeShiftSalesSpan" class="font-numeric">{{ number_format($shift->cash_sales_amount, 2) }} ج.م</span>
                            </div>
                            <div class="shift-calc-row text-xs text-blue-600">
                                <span>(+) إيداعات نقدية إضافية:</span>
                                <span id="closeShiftAdditionsSpan" class="font-numeric">{{ number_format($shift->cash_additions, 2) }} ج.م</span>
                            </div>
                            <div class="shift-calc-row text-xs text-amber-600">
                                <span>(-) سحوبات ومصروفات:</span>
                                <span id="closeShiftExpensesSpan" class="font-numeric">{{ number_format($shift->cash_expenses, 2) }} ج.م</span>
                            </div>
                            <div class="shift-calc-row pt-2 border-t border-[var(--border-color)]">
                                <span class="font-bold text-sm">الرصيد الدفتري المتوقع بالدرج:</span>
                                <strong id="closeShiftExpectedSpan" class="font-numeric text-base text-emerald-500">{{ number_format($shift->expected_closing_balance, 2) }} ج.م</strong>
                            </div>
                        </div>

                        <div class="erp-form-group mb-3">
                            <label for="closeShiftActualBalance" class="erp-form-label text-xs">
                                الجرد النقدي الفعلي في الدرج (ج.م) <span class="text-[var(--danger)]">*</span>
                            </label>
                            <input type="number" step="0.01" min="0" id="closeShiftActualBalance" name="actual_closing_balance" class="erp-input font-numeric text-base font-bold" placeholder="0.00" required>
                            <span class="text-[11px] text-[var(--text-muted)]">قم بعد النقدية الموجودة في الدرج بالكامل وإدخالها هنا.</span>
                        </div>

                        <div id="shiftDiffResultBox" class="erp-alert erp-alert-info mb-3 hidden">
                            <x-icon name="info" size="16" />
                            <span id="shiftDiffResultText"></span>
                        </div>

                        @cannot('close-shifts')
                            <div class="erp-alert erp-alert-warning mb-3">
                                <x-icon name="shield-alert" size="16" />
                                <span class="text-xs">إغلاق الوردية يتطلب اعتماد مدير النظام أو المشرف المباشر.</span>
                            </div>

                            <div class="erp-form-group mb-3">
                                <label for="closeSupervisorEmail" class="erp-form-label text-xs">
                                    بريد المشرف / المدير <span class="text-[var(--danger)]">*</span>
                                </label>
                                <input type="email" id="closeSupervisorEmail" name="supervisor_email" class="erp-input text-xs" placeholder="supervisor@example.com" required>
                            </div>

                            <div class="erp-form-group mb-3">
                                <label for="closeSupervisorPassword" class="erp-form-label text-xs">
                                    كلمة مرور المشرف / المدير <span class="text-[var(--danger)]">*</span>
                                </label>
                                <input type="password" id="closeSupervisorPassword" name="supervisor_password" class="erp-input text-xs" placeholder="••••••••" required>
                            </div>
                        @endcannot

                        <div class="erp-form-group mb-0">
                            <label for="closeShiftNotes" class="erp-form-label text-xs">ملاحظات الإغلاق والجرد</label>
                            <textarea id="closeShiftNotes" name="closing_notes" rows="2" class="erp-input text-xs" placeholder="سبب العجز أو الزيادة إن وجد، أو أي ملاحظات تسليم..."></textarea>
                        </div>
                    </div>
                    <div class="erp-modal-footer">
                        <button type="button" class="erp-btn erp-btn-secondary close-modal-btn">إلغاء</button>
                        <button type="submit" class="erp-btn erp-btn-danger">
                            <x-icon name="lock" size="16" />
                            <span>تأكيد الإغلاق النهائي</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
