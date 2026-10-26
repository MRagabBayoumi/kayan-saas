@extends('layouts.app')

@section('control_panel')
    <x-control-panel title="إذن مرتجع مشتريات: {{ $return->return_number }}" :breadcrumbs="['المشتريات والموردين' => route('purchases.index'), 'مرتجعات المشتريات' => route('purchase-returns.index'), $return->return_number => '#']">
        <x-slot:actions>
            @if($return->status === 'draft')
                @can('post-purchase-invoice')
                    <form action="{{ route('purchase-returns.post', $return) }}" method="POST" class="inline" onsubmit="return confirm('هل أنت متأكد من ترحيل المرتجع؟ سيتم خصم الكميات من المخزون وتسوية حساب المورد فوراً.');">
                        @csrf
                        <button type="submit" class="erp-btn erp-btn-primary bg-emerald-600 hover:bg-emerald-700 text-white">
                            <x-icon name="check-circle" size="16" />
                            <span>ترحيل المرتجع للمخزن</span>
                        </button>
                    </form>
                @endcan
            @endif

            <!-- Print Combobox Dropdown -->
            <x-print-dropdown 
                title="طباعة إذن المرتجع"
                code="{{ $return->return_number }}"
                urlThermal="{{ route('purchase-returns.print', [$return, 'format' => 'thermal_80mm']) }}"
                directThermal="{{ route('purchase-returns.direct-print', [$return, 'format' => 'thermal_80mm']) }}"
                urlA4="{{ route('purchase-returns.print', [$return, 'format' => 'a4']) }}"
                urlA5="{{ route('purchase-returns.print', [$return, 'format' => 'a5']) }}"
            />

            <a href="{{ route('purchase-returns.index') }}" class="erp-btn erp-btn-ghost">
                <x-icon name="arrow-right" size="16" />
                <span>العودة للمرتجعات</span>
            </a>
        </x-slot:actions>

        <x-slot:extra>
            <span class="erp-badge {{ $return->status_badge_class }}">
                {{ $return->status_label }}
            </span>
        </x-slot:extra>
    </x-control-panel>
@endsection

@section('content')
    <div data-page="purchase-returns-show">
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

        <!-- Return Details Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            <!-- Basic Info Card -->
            <div class="erp-card p-4">
                <h4 class="text-xs font-bold text-[var(--text-muted)] uppercase mb-3 flex items-center gap-1.5 pb-2 border-b border-[var(--border-color)]">
                    <x-icon name="info" size="14" class="text-[var(--primary)]" />
                    <span>بيانات المرتجع</span>
                </h4>
                <div class="space-y-2 text-xs">
                    <div class="flex justify-between py-1 border-b border-[var(--border-color)]/50">
                        <span class="text-[var(--text-muted)]">رقم المرتجع:</span>
                        <span class="font-mono font-bold text-[var(--text-main)]">{{ $return->return_number }}</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-[var(--border-color)]/50">
                        <span class="text-[var(--text-muted)]">تاريخ الإرجاع:</span>
                        <span class="font-numeric font-semibold">{{ $return->return_date ? $return->return_date->format('Y-m-d') : '—' }}</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-[var(--border-color)]/50">
                        <span class="text-[var(--text-muted)]">طريقة التسوية:</span>
                        <span class="erp-badge {{ $return->settlement_type === 'cash_refund' ? 'erp-badge-info' : 'erp-badge-neutral' }} text-[11px]">
                            {{ $return->settlement_type_label }}
                        </span>
                    </div>
                    @if($return->treasury)
                        <div class="flex justify-between py-1 border-b border-[var(--border-color)]/50">
                            <span class="text-[var(--text-muted)]">الخزينة المودع بها:</span>
                            <span class="font-semibold text-emerald-700">{{ $return->treasury->name }}</span>
                        </div>
                    @endif
                    <div class="flex justify-between py-1">
                        <span class="text-[var(--text-muted)]">المستودع المصروف منه:</span>
                        <span class="font-semibold">{{ $return->warehouse?->name ?? '—' }}</span>
                    </div>
                </div>
            </div>

            <!-- Supplier Card -->
            <div class="erp-card p-4">
                <h4 class="text-xs font-bold text-[var(--text-muted)] uppercase mb-3 flex items-center gap-1.5 pb-2 border-b border-[var(--border-color)]">
                    <x-icon name="factory" size="14" class="text-[var(--primary)]" />
                    <span>بيانات المورد</span>
                </h4>
                <div class="space-y-2 text-xs">
                    <div class="flex justify-between py-1 border-b border-[var(--border-color)]/50">
                        <span class="text-[var(--text-muted)]">اسم المورد:</span>
                        <span class="font-bold text-[var(--text-main)]">{{ $return->supplier?->name ?? '—' }}</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-[var(--border-color)]/50">
                        <span class="text-[var(--text-muted)]">الهاتف / تواصل:</span>
                        <span class="font-numeric">{{ $return->supplier?->phone ?? '—' }}</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-[var(--border-color)]/50">
                        <span class="text-[var(--text-muted)]">رصيد المورد الحالي:</span>
                        <span class="font-numeric font-bold {{ (float)($return->supplier?->current_balance ?? 0) > 0 ? 'text-[var(--danger)]' : 'text-emerald-600' }}">
                            {{ number_format((float)($return->supplier?->current_balance ?? 0), 2) }} ج.م
                        </span>
                    </div>
                    @if($return->supplier)
                        <div class="pt-1 text-left">
                            <a href="{{ route('suppliers.statement', $return->supplier) }}" class="text-[var(--primary)] hover:underline text-xs flex items-center gap-1 justify-end" target="_blank">
                                <x-icon name="file-text" size="13" />
                                <span>كشف حساب المورد</span>
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Audit Card -->
            <div class="erp-card p-4">
                <h4 class="text-xs font-bold text-[var(--text-muted)] uppercase mb-3 flex items-center gap-1.5 pb-2 border-b border-[var(--border-color)]">
                    <x-icon name="clock" size="14" class="text-[var(--primary)]" />
                    <span>التوثيق والربط</span>
                </h4>
                <div class="space-y-2 text-xs">
                    <div class="flex justify-between py-1 border-b border-[var(--border-color)]/50">
                        <span class="text-[var(--text-muted)]">الفاتورة الأصلية:</span>
                        @if($return->purchaseInvoice)
                            <a href="{{ route('purchases.show', $return->purchaseInvoice) }}" class="font-mono font-bold text-[var(--primary)] hover:underline">
                                {{ $return->purchaseInvoice->invoice_number }}
                            </a>
                        @else
                            <span class="text-[var(--text-muted)]">إرجاع مباشر مستقل</span>
                        @endif
                    </div>
                    <div class="flex justify-between py-1 border-b border-[var(--border-color)]/50">
                        <span class="text-[var(--text-muted)]">مُسجل الحركة:</span>
                        <span>{{ $return->creator?->name ?? 'النظام' }}</span>
                    </div>
                    @if($return->posted_at)
                        <div class="flex justify-between py-1 border-b border-[var(--border-color)]/50">
                            <span class="text-[var(--text-muted)]">تاريخ الترحيل:</span>
                            <span class="font-numeric">{{ $return->posted_at->format('Y-m-d H:i') }}</span>
                        </div>
                        <div class="flex justify-between py-1">
                            <span class="text-[var(--text-muted)]">تم الترحيل بواسطة:</span>
                            <span>{{ $return->poster?->name ?? '—' }}</span>
                        </div>
                    @else
                        <div class="flex justify-between py-1 text-[var(--warning)]">
                            <span>الحالة:</span>
                            <span class="font-semibold">بانتظار اعتماد الترحيل للمخازن</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        @if($return->notes)
            <div class="erp-card p-3 mb-4 bg-amber-50/50 dark:bg-amber-950/20 border-amber-200 dark:border-amber-900/50">
                <div class="flex items-start gap-2 text-xs">
                    <x-icon name="file-text" size="15" class="text-amber-600 mt-0.5 shrink-0" />
                    <div>
                        <strong class="text-amber-900 dark:text-amber-200">ملاحظات وسبب الإرجاع:</strong>
                        <p class="text-amber-800 dark:text-amber-300 mt-0.5">{{ $return->notes }}</p>
                    </div>
                </div>
            </div>
        @endif

        <!-- Items Table -->
        <div class="erp-card p-0 mb-4 overflow-hidden">
            <div class="p-3 bg-[var(--surface-muted)] border-b border-[var(--border-color)] flex items-center justify-between">
                <h3 class="text-sm font-bold text-[var(--text-main)] flex items-center gap-2">
                    <x-icon name="package" size="16" class="text-[var(--primary)]" />
                    <span>الأصناف والكميات المعادة للمورد</span>
                </h3>
                <span class="text-xs text-[var(--text-muted)] font-numeric">
                    إجمالي الأصناف: {{ $return->items->count() }}
                </span>
            </div>

            <div class="erp-table-responsive">
                <table class="erp-table">
                    <thead>
                        <tr>
                            <th style="width: 40px;">#</th>
                            <th>الصنف</th>
                            <th>الوحدة</th>
                            <th>معامل التحويل</th>
                            <th>الكمية المرتجعة</th>
                            <th>الكمية بالقطع الأساسية</th>
                            <th>سعر الشراء (ج.م)</th>
                            <th>تكلفة القطعة الأساسية</th>
                            <th>إجمالي المرتجع (ج.م)</th>
                            <th>الدفعة المخزنية</th>
                            <th>ملاحظات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($return->items as $idx => $item)
                            <tr>
                                <td class="text-center font-numeric text-xs">{{ $idx + 1 }}</td>
                                <td>
                                    <div class="font-bold text-xs text-[var(--text-main)]">{{ $item->product?->name }}</div>
                                    <div class="text-[11px] text-[var(--text-muted)] font-mono">
                                        كود: {{ $item->product?->sku ?? '—' }}
                                        @if($item->product?->barcode)
                                            • باركود: {{ $item->product->barcode }}
                                        @endif
                                    </div>
                                </td>
                                <td class="text-xs">
                                    {{ $item->unit?->name ?? 'قطعة' }}
                                </td>
                                <td class="font-numeric text-xs text-center">
                                    {{ (float)$item->conversion_factor }}
                                </td>
                                <td class="font-numeric font-bold text-xs text-center text-[var(--danger)]">
                                    {{ (float)$item->quantity }}
                                </td>
                                <td class="font-numeric text-xs text-center bg-gray-50 dark:bg-gray-800/40">
                                    {{ (float)$item->base_quantity }} قطعة
                                </td>
                                <td class="font-numeric text-xs">
                                    {{ number_format((float)$item->unit_cost, 2) }}
                                </td>
                                <td class="font-numeric text-xs font-mono text-[var(--text-muted)]">
                                    {{ number_format((float)$item->unit_cost_base, 4) }}
                                </td>
                                <td class="font-numeric font-bold text-sm text-[var(--primary)] text-left">
                                    {{ number_format((float)$item->total_cost, 2) }}
                                </td>
                                <td class="text-xs font-mono">
                                    @if($item->inventoryBatch)
                                        <span class="text-emerald-700 bg-emerald-50 dark:bg-emerald-950/40 px-1.5 py-0.5 rounded text-[11px]">
                                            {{ $item->inventoryBatch->batch_number }}
                                        </span>
                                    @else
                                        <span class="text-[var(--text-muted)]">FIFO عام</span>
                                    @endif
                                </td>
                                <td class="text-xs text-[var(--text-muted)]">
                                    {{ $item->notes ?: '—' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Table Footer -->
            <div class="p-4 bg-[var(--surface-muted)] border-t border-[var(--border-color)] flex items-center justify-between">
                <div class="text-xs text-[var(--text-muted)]">
                    @if($return->status === 'posted')
                        <span class="inline-flex items-center gap-1 text-emerald-700 font-semibold">
                            <x-icon name="check-circle" size="14" />
                            <span>تم خصم الكميات من المخزون وتوليد حركات الصرف المخزني.</span>
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 text-amber-700 font-semibold">
                            <x-icon name="alert-circle" size="14" />
                            <span>مسودة مرتجع — لم يتم الخصم من المخزون بعد.</span>
                        </span>
                    @endif
                </div>

                <div class="flex items-center gap-3">
                    <span class="text-sm font-bold text-[var(--text-main)]">صافي إجمالي المرتجع:</span>
                    <span class="text-2xl font-bold font-numeric text-[var(--primary)]">
                        {{ number_format((float)$return->total_amount, 2) }}
                    </span>
                    <span class="text-xs text-[var(--text-muted)] font-semibold">ج.م</span>
                </div>
            </div>
        </div>
    </div>
@endsection
