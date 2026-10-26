@extends('layouts.app')

@section('control_panel')
    <x-control-panel title="مرتجعات المشتريات والتوريد" :breadcrumbs="['المشتريات والموردين' => route('purchases.index'), 'مرتجعات المشتريات' => '#']">
        <x-slot:actions>
            <a href="{{ route('purchase-returns.create') }}" class="erp-btn erp-btn-primary">
                <x-icon name="plus" size="16" />
                <span>تسجيل مرتجع جديد</span>
            </a>
            <a href="{{ route('purchases.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="shopping-bag" size="16" />
                <span>فواتير المشتريات</span>
            </a>
        </x-slot:actions>

        <x-slot:extra>
            <span class="erp-badge erp-badge-neutral font-numeric">
                <x-icon name="rotate-ccw" size="14" />
                <span>إجمالي المرتجعات: {{ $metrics['total_count'] }}</span>
            </span>
        </x-slot:extra>
    </x-control-panel>
@endsection

@section('content')
    <div data-page="purchase-returns-index">
        <!-- Enterprise Sub-Navigation Tabs Bar (Faithful to Kayan ERP Design System) -->
        <div class="erp-card erp-tab-nav-card mb-4">
            <nav class="erp-tab-bar" aria-label="أقسام فواتير ومرتجعات الشراء">
                <a href="{{ route('purchases.index') }}" class="erp-tab-link">
                    <x-icon name="shopping-bag" size="16" />
                    <span>فواتير الشراء والتوريد</span>
                </a>
                <a href="{{ route('purchase-returns.index') }}" class="erp-tab-link active">
                    <x-icon name="rotate-ccw" size="16" />
                    <span>مرتجعات المشتريات</span>
                    <span class="erp-badge erp-badge-neutral mr-1.5 px-2 py-0.5 text-xs font-bold font-numeric">
                        {{ $metrics['total_count'] }}
                    </span>
                </a>
            </nav>
        </div>

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

        <!-- Summary KPI Card -->
        <div class="erp-card mb-4 p-4">
            <div class="purchases-kpi-row">
                <div class="purchases-kpi-stat">
                    <div class="purchases-kpi-icon-wrap">
                        <x-icon name="rotate-ccw" size="22" />
                    </div>
                    <div class="purchases-kpi-info">
                        <div class="purchases-kpi-label">إجمالي حركات المرتجع</div>
                        <div class="purchases-kpi-value font-numeric">{{ $metrics['total_count'] }}</div>
                        <div class="purchases-kpi-desc">جميع المرتجعات المسجلة</div>
                    </div>
                </div>

                <div class="purchases-kpi-stat">
                    <div class="purchases-kpi-icon-wrap success">
                        <x-icon name="check-circle" size="22" />
                    </div>
                    <div class="purchases-kpi-info">
                        <div class="purchases-kpi-label">مرتجعات مُرحلة للمخازن</div>
                        <div class="purchases-kpi-value font-numeric text-[var(--success)]">{{ number_format($metrics['posted_total'], 2) }} ج.م</div>
                        <div class="purchases-kpi-desc">تم خصمها من المخزن وتسوية الحساب</div>
                    </div>
                </div>

                <div class="purchases-kpi-stat">
                    <div class="purchases-kpi-icon-wrap warning">
                        <x-icon name="file-text" size="22" />
                    </div>
                    <div class="purchases-kpi-info">
                        <div class="purchases-kpi-label">مسودات قيد المراجعة</div>
                        <div class="purchases-kpi-value font-numeric text-[var(--warning)]">{{ $metrics['draft_count'] }}</div>
                        <div class="purchases-kpi-desc">تتطلب اعتماد وترحيل نهائي</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="erp-card mb-4 p-3">
            <form action="{{ route('purchase-returns.index') }}" method="GET" class="erp-filter-form">
                <div class="flex-1 min-w-[220px]">
                    <div class="relative">
                        <input 
                            type="text" 
                            name="search" 
                            value="{{ $currentSearch }}" 
                            class="erp-input w-full pr-8" 
                            placeholder="بحث برقم المرتجع أو اسم المورد أو رقم الفاتورة..."
                        >
                        <span class="absolute right-2.5 top-1/2 -translate-y-1/2 text-[var(--text-muted)] pointer-events-none">
                            <x-icon name="search" size="15" />
                        </span>
                    </div>
                </div>

                <div class="w-40 min-w-[140px]">
                    <select name="status" class="erp-input w-full">
                        <option value="">-- كل الحالات --</option>
                        <option value="draft" {{ $currentStatus === 'draft' ? 'selected' : '' }}>مسودة</option>
                        <option value="posted" {{ $currentStatus === 'posted' ? 'selected' : '' }}>مرحلة للمخازن</option>
                        <option value="cancelled" {{ $currentStatus === 'cancelled' ? 'selected' : '' }}>ملغاة</option>
                    </select>
                </div>

                <div class="w-48 min-w-[160px]">
                    <select name="warehouse_id" class="erp-input w-full">
                        <option value="">-- كل المستودعات --</option>
                        @foreach($warehouses as $wh)
                            <option value="{{ $wh->id }}" {{ (string)$currentWarehouseId === (string)$wh->id ? 'selected' : '' }}>
                                {{ $wh->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="w-48 min-w-[160px]">
                    <select name="supplier_id" class="erp-input w-full">
                        <option value="">-- كل الموردين --</option>
                        @foreach($suppliers as $sup)
                            <option value="{{ $sup->id }}" {{ (string)$currentSupplierId === (string)$sup->id ? 'selected' : '' }}>
                                {{ $sup->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="erp-btn erp-btn-secondary">
                    <x-icon name="filter" size="15" />
                    <span>تصفية</span>
                </button>

                @if($currentSearch || $currentStatus || $currentWarehouseId || $currentSupplierId)
                    <a href="{{ route('purchase-returns.index') }}" class="erp-btn erp-btn-ghost text-xs text-[var(--danger)]">
                        <x-icon name="x" size="14" />
                        <span>إلغاء التصفية</span>
                    </a>
                @endif
            </form>
        </div>

        <!-- Table Card -->
        <div class="erp-card p-0 overflow-hidden">
            <div class="erp-table-responsive">
                <table class="erp-table">
                    <thead>
                        <tr>
                            <th>رقم المرتجع</th>
                            <th>تاريخ المرتجع</th>
                            <th>المورد</th>
                            <th>المستودع المصروف منه</th>
                            <th>الفاتورة الأصلية</th>
                            <th>طريقة التسوية</th>
                            <th>الأصناف</th>
                            <th>إجمالي المرتجع</th>
                            <th>الحالة</th>
                            <th class="text-center">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($returns as $return)
                            <tr>
                                <td>
                                    <a href="{{ route('purchase-returns.show', $return) }}" class="font-bold text-[var(--primary)] hover:underline font-mono">
                                        {{ $return->return_number }}
                                    </a>
                                </td>
                                <td class="font-numeric text-xs">
                                    {{ $return->return_date ? $return->return_date->format('Y-m-d') : '—' }}
                                </td>
                                <td>
                                    <div class="font-semibold text-xs text-[var(--text-main)]">
                                        {{ $return->supplier?->name ?? 'غير محدد' }}
                                    </div>
                                    <div class="text-[11px] text-[var(--text-muted)] font-numeric">
                                        {{ $return->supplier?->phone ?? '—' }}
                                    </div>
                                </td>
                                <td class="text-xs">
                                    <span class="inline-flex items-center gap-1">
                                        <x-icon name="archive" size="13" class="text-[var(--text-muted)]" />
                                        <span>{{ $return->warehouse?->name ?? '—' }}</span>
                                    </span>
                                </td>
                                <td class="text-xs">
                                    @if($return->purchaseInvoice)
                                        <a href="{{ route('purchases.show', $return->purchaseInvoice) }}" class="text-[var(--primary)] hover:underline font-mono text-xs">
                                            {{ $return->purchaseInvoice->invoice_number }}
                                        </a>
                                    @else
                                        <span class="text-[var(--text-muted)]">إرجاع مباشر (بدون فاتورة)</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="erp-badge {{ $return->settlement_type === 'cash_refund' ? 'erp-badge-info' : 'erp-badge-neutral' }} text-[11px]">
                                        {{ $return->settlement_type_label }}
                                    </span>
                                </td>
                                <td class="text-xs font-numeric">
                                    {{ $return->items->count() }} صنف
                                </td>
                                <td class="font-numeric font-bold text-sm text-[var(--primary)]">
                                    {{ number_format((float) $return->total_amount, 2) }} ج.م
                                </td>
                                <td>
                                    <span class="erp-badge {{ $return->status_badge_class }} text-xs">
                                        {{ $return->status_label }}
                                    </span>
                                </td>
                                <td>
                                    <div class="flex items-center justify-center gap-1">
                                        <a 
                                            href="{{ route('purchase-returns.show', $return) }}" 
                                            class="erp-btn erp-btn-ghost p-1.5" 
                                            title="عرض تفاصيل المرتجع"
                                        >
                                            <x-icon name="eye" size="16" />
                                        </a>
                                        <!-- Print Combobox Dropdown -->
                                        <x-print-dropdown 
                                            size="sm"
                                            title="طباعة"
                                            code="{{ $return->return_number }}"
                                            urlThermal="{{ route('purchase-returns.print', [$return, 'format' => 'thermal_80mm']) }}"
                                            directThermal="{{ route('purchase-returns.direct-print', [$return, 'format' => 'thermal_80mm']) }}"
                                            urlA4="{{ route('purchase-returns.print', [$return, 'format' => 'a4']) }}"
                                            urlA5="{{ route('purchase-returns.print', [$return, 'format' => 'a5']) }}"
                                        />
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-10">
                                    <div class="flex flex-col items-center justify-center gap-2 text-[var(--text-muted)]">
                                        <x-icon name="rotate-ccw" size="40" class="opacity-40" />
                                        <p class="text-sm font-semibold">لا توجد مرتجعات مشتريات مسجلة</p>
                                        <a href="{{ route('purchase-returns.create') }}" class="erp-btn erp-btn-primary erp-btn-sm mt-2">
                                            <x-icon name="plus" size="14" />
                                            <span>تسجيل أول مرتجع الآن</span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($returns->hasPages())
                <div class="p-3 border-t border-[var(--border-color)]">
                    {{ $returns->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
