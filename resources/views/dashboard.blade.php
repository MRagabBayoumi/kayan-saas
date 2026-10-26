@extends('layouts.app')

@section('control_panel')
    <x-control-panel title="لوحة التحكم والمؤشرات الإدارية" :breadcrumbs="[]">
        <x-slot:actions>
            <a href="{{ route('sales.create') }}" class="erp-btn erp-btn-primary">
                <x-icon name="shopping-cart" size="16" />
                <span>فاتورة بيع جديدة</span>
            </a>
            <a href="{{ route('purchases.create') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="truck" size="16" />
                <span>فاتورة مشتريات</span>
            </a>
            <a href="{{ url('/products') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="package" size="16" />
                <span>إدارة المنتجات</span>
            </a>
            <a href="{{ route('inventory.low-stock') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="alert-triangle" size="16" />
                <span>نواقص المخزون</span>
            </a>
        </x-slot:actions>

        <x-slot:extra>
            <span class="erp-badge erp-badge-neutral font-numeric">
                <x-icon name="calendar" size="14" />
                <span>{{ date('Y-m-d') }}</span>
            </span>
        </x-slot:extra>
    </x-control-panel>
@endsection

@section('content')
    <!-- Fast Financial & Status Ribbon (Image 2 Enterprise KPI Style) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5 mb-5">
        <!-- 1. رصيد الخزائن والنقدية -->
        <a href="{{ route('treasuries.index') }}" class="kpi-card flex flex-col justify-between hover:border-[var(--primary)] transition group">
            <div class="kpi-header mb-1">
                <span class="kpi-label">رصيد الخزائن والنقدية</span>
                <span class="kpi-icon text-[var(--primary)]"><x-icon name="wallet" size="18" /></span>
            </div>
            <div class="kpi-value font-numeric text-base sm:text-lg font-black text-[var(--text-main)]">
                {{ number_format($financialSummary['treasuries_balance'] ?? 0, 2) }} <span class="text-xs font-normal text-[var(--text-muted)]">ج.م</span>
            </div>
            <div class="kpi-note font-numeric text-xs text-[var(--text-muted)] flex items-center justify-between">
                <span>سيولة نقدية متوفرة</span>
                <span class="text-[var(--primary)] text-[11px] group-hover:underline flex items-center gap-0.5">
                    <span>الخزائن</span>
                    <x-icon name="arrow-left" size="12" />
                </span>
            </div>
        </a>

        <!-- 2. مديونيات العملاء بالسوق -->
        <a href="{{ route('customers.index') }}" class="kpi-card flex flex-col justify-between border-amber-200 bg-amber-50/40 hover:border-amber-300 transition group">
            <div class="kpi-header mb-1">
                <span class="kpi-label text-amber-900 font-bold">مديونيات العملاء بالسوق</span>
                <span class="kpi-icon text-amber-600 bg-amber-100/80"><x-icon name="users" size="18" /></span>
            </div>
            <div class="kpi-value font-numeric text-base sm:text-lg font-black text-amber-800">
                {{ number_format($financialSummary['customer_due'] ?? 0, 2) }} <span class="text-xs font-bold text-amber-700">ج.م</span>
            </div>
            <div class="kpi-note font-numeric text-amber-700 text-xs font-medium flex items-center justify-between">
                <span>مستحقات آجل بالسوق</span>
                <span class="text-amber-800 text-[11px] group-hover:underline flex items-center gap-0.5">
                    <span>دليل العملاء</span>
                    <x-icon name="arrow-left" size="12" />
                </span>
            </div>
        </a>

        <!-- 3. مستحقات الموردين ومصانع الصين -->
        <a href="{{ route('suppliers.index') }}" class="kpi-card flex flex-col justify-between border-sky-200 bg-sky-50/40 hover:border-sky-300 transition group">
            <div class="kpi-header mb-1">
                <span class="kpi-label text-sky-900 font-bold">مستحقات الموردين ومصانع الصين</span>
                <span class="kpi-icon text-sky-600 bg-sky-100/80"><x-icon name="truck" size="18" /></span>
            </div>
            <div class="kpi-value font-numeric text-base sm:text-lg font-black text-sky-800">
                {{ number_format($financialSummary['supplier_due'] ?? 0, 2) }} <span class="text-xs font-bold text-sky-700">ج.م</span>
            </div>
            <div class="kpi-note font-numeric text-sky-700 text-xs font-medium flex items-center justify-between">
                <span>التزامات توريد وشحن</span>
                <span class="text-sky-800 text-[11px] group-hover:underline flex items-center gap-0.5">
                    <span>دليل الموردين</span>
                    <x-icon name="arrow-left" size="12" />
                </span>
            </div>
        </a>

        <!-- 4. نواقص وتنبيهات المخزون -->
        <a href="{{ route('inventory.low-stock') }}" class="kpi-card flex flex-col justify-between border-rose-200 bg-rose-50/40 hover:border-rose-300 transition group">
            <div class="kpi-header mb-1">
                <span class="kpi-label text-rose-900 font-bold">نواقص وتنبيهات المخزون</span>
                <span class="kpi-icon text-rose-600 bg-rose-100/80"><x-icon name="alert-triangle" size="18" /></span>
            </div>
            <div class="kpi-value font-numeric text-base sm:text-lg font-black text-rose-800">
                {{ number_format($stockMetrics['low_stock_count'] ?? 0) }} <span class="text-xs font-bold text-rose-700">صنف مطلوب</span>
            </div>
            <div class="kpi-note font-numeric text-rose-700 text-xs font-medium flex items-center justify-between">
                <span>تحت حد الطلب المخزني</span>
                <span class="text-rose-800 text-[11px] group-hover:underline flex items-center gap-0.5">
                    <span>عرض النواقص</span>
                    <x-icon name="arrow-left" size="12" />
                </span>
            </div>
        </a>
    </div>

    <!-- 4 Main Requested KPI Cards -->
    <div class="section-title-bar">
        <h3>المؤشرات المالية والمخزنية الرئيسية</h3>
        <small class="text-[var(--text-muted)]">بيانات حية مباشرة من قاعدة بيانات النظام</small>
    </div>

    <section class="kpi-grid">
        <!-- 1. إجمالي رصيد المخزن -->
        <div class="kpi-card relative overflow-hidden">
            <div class="kpi-header">
                <span class="kpi-label font-bold text-[var(--text-main)]">إجمالي رصيد المخزن</span>
                <span class="kpi-icon bg-indigo-50 text-indigo-600 dark:bg-indigo-950/50 dark:text-indigo-400">
                    <x-icon name="warehouse" size="20" />
                </span>
            </div>
            <div class="kpi-value font-numeric text-indigo-700 dark:text-indigo-300">
                {{ number_format($stockMetrics['cost_value'] ?? 0, 2) }}
                <span class="text-xs font-normal text-[var(--text-muted)]">ج.م (تكلفة)</span>
            </div>
            <div class="text-xs text-[var(--text-secondary)] font-numeric mb-2">
                <span>إجمالي الرصيد: <strong>{{ number_format($stockMetrics['quantity'] ?? 0) }}</strong> قطعة</span>
                <span class="mx-1 text-[var(--border-color)]">|</span>
                <span><strong>{{ number_format($stockMetrics['products_count'] ?? 0) }}</strong> صنف</span>
            </div>
            <div class="kpi-note font-numeric pt-2 border-t border-[var(--border-light)] flex justify-between items-center">
                <span class="text-[11px] text-[var(--text-muted)]">قيمة البيع: {{ number_format($stockMetrics['retail_value'] ?? 0, 2) }} ج.م</span>
                <a href="{{ route('inventory.index') }}" class="text-[var(--primary)] font-semibold inline-flex items-center gap-1 hover:underline text-xs">
                    <span>المخزون</span>
                    <x-icon name="arrow-left" size="12" />
                </a>
            </div>
        </div>

        <!-- 2. إجمالي فواتير المشتريات -->
        <div class="kpi-card relative overflow-hidden">
            <div class="kpi-header">
                <span class="kpi-label font-bold text-[var(--text-main)]">إجمالي فواتير المشتريات</span>
                <span class="kpi-icon bg-teal-50 text-teal-600 dark:bg-teal-950/50 dark:text-teal-400">
                    <x-icon name="truck" size="20" />
                </span>
            </div>
            <div class="kpi-value font-numeric text-teal-700 dark:text-teal-300">
                {{ number_format($purchaseMetrics['total'] ?? 0, 2) }}
                <span class="text-xs font-normal text-[var(--text-muted)]">ج.م</span>
            </div>
            <div class="text-xs text-[var(--text-secondary)] font-numeric mb-2">
                @if(($purchaseMetrics['count'] ?? 0) > 0)
                    <span>عدد الفواتير المعتمدة: <strong>{{ number_format($purchaseMetrics['count']) }}</strong></span>
                    <span class="mx-1 text-[var(--border-color)]">|</span>
                    <span>المسدد: <strong>{{ number_format($purchaseMetrics['paid'] ?? 0, 2) }}</strong> ج.م</span>
                @else
                    <span class="text-[var(--text-muted)]">لا توجد فواتير معتمدة</span>
                    @if(($purchaseMetrics['cancelled_count'] ?? 0) > 0)
                        <span class="text-[11px] text-rose-500 font-normal">({{ $purchaseMetrics['cancelled_count'] }} فاتورة ملغاة)</span>
                    @endif
                @endif
            </div>
            <div class="kpi-note font-numeric pt-2 border-t border-[var(--border-light)] flex justify-between items-center">
                <span class="text-[11px] text-[var(--text-muted)]">المتبقي: {{ number_format($purchaseMetrics['remaining'] ?? 0, 2) }} ج.م</span>
                <a href="{{ route('purchases.index') }}" class="text-[var(--primary)] font-semibold inline-flex items-center gap-1 hover:underline text-xs">
                    <span>سجل المشتريات</span>
                    <x-icon name="arrow-left" size="12" />
                </a>
            </div>
        </div>

        <!-- 3. إجمالي فواتير المبيعات -->
        <div class="kpi-card relative overflow-hidden">
            <div class="kpi-header">
                <span class="kpi-label font-bold text-[var(--text-main)]">إجمالي فواتير المبيعات</span>
                <span class="kpi-icon bg-emerald-50 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-400">
                    <x-icon name="shopping-cart" size="20" />
                </span>
            </div>
            <div class="kpi-value font-numeric text-emerald-700 dark:text-emerald-300">
                {{ number_format($salesMetrics['total'] ?? 0, 2) }}
                <span class="text-xs font-normal text-[var(--text-muted)]">ج.م</span>
            </div>
            <div class="text-xs text-[var(--text-secondary)] font-numeric mb-2">
                <span>فواتير معتمدة: <strong>{{ number_format($salesMetrics['count'] ?? 0) }}</strong></span>
                <span class="mx-1 text-[var(--border-color)]">|</span>
                <span>المحصل: <strong>{{ number_format($salesMetrics['paid'] ?? 0, 2) }}</strong> ج.م</span>
            </div>
            <div class="kpi-note font-numeric pt-2 border-t border-[var(--border-light)] flex justify-between items-center">
                <span class="text-[11px] text-[var(--text-muted)]">مبيعات اليوم: {{ number_format($salesMetrics['today'] ?? 0, 2) }} ج.م</span>
                <span class="text-[11px] text-amber-600 dark:text-amber-400 font-semibold">آجل: {{ number_format($salesMetrics['remaining'] ?? 0, 2) }}</span>
            </div>
        </div>

        <!-- 4. الأرباح المتوقعة قبل المصاريف -->
        <div class="kpi-card relative overflow-hidden border-amber-200/90 bg-amber-50/20">
            <div class="kpi-header">
                <div class="flex items-center gap-1.5">
                    <span class="kpi-label font-bold text-[var(--text-main)]">الأرباح المتوقعة قبل المصاريف</span>
                    <span class="cursor-help text-[var(--text-muted)]" title="المعادلة = تكلفة رصيد المخزن ({{ number_format($stockMetrics['cost_value'] ?? 0, 2) }} ج.م) × نسبة هامش الربح ({{ $profitMetrics['margin_percent'] ?? 0 }}%) المحققة من قسمة أرباح المبيعات على تكلفة البضاعة المباعة">
                        <x-icon name="info" size="13" />
                    </span>
                </div>
                <span class="kpi-icon bg-amber-50 text-amber-600 dark:bg-amber-950/50 dark:text-amber-400">
                    <x-icon name="trending-up" size="20" />
                </span>
            </div>
            <div class="kpi-value font-numeric text-amber-700 dark:text-amber-300">
                {{ number_format($profitMetrics['expected_total'] ?? 0, 2) }}
                <span class="text-xs font-normal text-[var(--text-muted)]">ج.م</span>
            </div>
            <div class="text-xs text-[var(--text-secondary)] font-numeric mb-2 flex flex-col gap-1">
                <div class="flex items-center justify-between">
                    <span>نسبة هامش ربح المبيعات:</span>
                    <strong class="text-amber-700 dark:text-amber-300 font-bold">{{ $profitMetrics['margin_percent'] ?? 0 }}%</strong>
                </div>
                <div class="flex items-center justify-between">
                    <span>مقاسة على تكلفة المخزن:</span>
                    <strong class="text-indigo-600 dark:text-indigo-400">{{ number_format($stockMetrics['cost_value'] ?? 0, 2) }} ج.م</strong>
                </div>
            </div>
            <div class="kpi-note font-numeric pt-2 border-t border-[var(--border-light)] flex justify-between items-center text-[11px] text-[var(--text-muted)]">
                <span title="إجمالي الأرباح المحققة من فواتير المبيعات المعتمدة">أرباح المبيعات المحققة: <strong class="text-emerald-600">{{ number_format($profitMetrics['realized_profit'] ?? 0, 2) }}</strong> ج.م</span>
                <span class="text-[10px] text-[var(--text-muted)]">تكلفة المباع: {{ number_format($profitMetrics['sales_cogs'] ?? 0, 2) }} ج.م</span>
            </div>
        </div>
    </section>

    <!-- Manager Operations Log & Analytics Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-6">
        <!-- Main: سجل آخر عمليات للمدير (2 Cols) -->
        <div class="lg:col-span-2 erp-card overflow-hidden">
            <div class="erp-card-header flex flex-wrap items-center justify-between gap-3 border-b border-[var(--border-color)] pb-3">
                <div class="erp-card-title flex items-center gap-2">
                    <x-icon name="activity" size="18" class="text-[var(--primary)]" />
                    <span class="font-bold text-sm">سجل آخر عمليات للمدير</span>
                </div>

                <!-- Tabs Switching Buttons -->
                <div class="erp-tab-bar flex gap-1 bg-[var(--bg-surface-alt)] p-1 rounded-md border border-[var(--border-color)]">
                    <button type="button" class="erp-tab-link text-xs py-1 px-3 active" onclick="switchManagerTab('sales-tab', this)">
                        <x-icon name="shopping-cart" size="13" />
                        <span>آخر المبيعات</span>
                        <span class="erp-badge erp-badge-neutral text-[10px] py-0 px-1">{{ $recentSales->count() }}</span>
                    </button>
                    <button type="button" class="erp-tab-link text-xs py-1 px-3" onclick="switchManagerTab('movements-tab', this)">
                        <x-icon name="refresh-cw" size="13" />
                        <span>حركات المخزن</span>
                        <span class="erp-badge erp-badge-neutral text-[10px] py-0 px-1">{{ $recentMovements->count() }}</span>
                    </button>
                    <button type="button" class="erp-tab-link text-xs py-1 px-3" onclick="switchManagerTab('purchases-tab', this)">
                        <x-icon name="truck" size="13" />
                        <span>آخر المشتريات</span>
                        <span class="erp-badge erp-badge-neutral text-[10px] py-0 px-1">{{ $recentPurchases->count() }}</span>
                    </button>
                </div>
            </div>

            <div class="erp-card-body p-0">
                <!-- Tab 1: آخر فواتير المبيعات -->
                <div id="sales-tab" class="manager-tab-content block">
                    <div class="erp-table-responsive">
                        <table class="erp-table">
                            <thead>
                                <tr>
                                    <th>رقم الفاتورة</th>
                                    <th>العميل</th>
                                    <th>القيمة</th>
                                    <th>السداد</th>
                                    <th>المسؤول</th>
                                    <th>التوقيت</th>
                                    <th class="text-center">إجراء</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentSales as $sale)
                                    <tr class="hover:bg-[var(--bg-hover)] transition">
                                        <td class="font-numeric font-bold text-[var(--primary)]">
                                            #{{ $sale->invoice_number }}
                                        </td>
                                        <td>
                                            <span class="font-semibold text-xs">{{ $sale->customer?->name ?? 'عميل نقدي' }}</span>
                                        </td>
                                        <td class="font-numeric font-bold text-xs">
                                            {{ number_format($sale->total_amount, 2) }} <small class="text-[var(--text-muted)]">ج.م</small>
                                        </td>
                                        <td>
                                            <span class="erp-badge {{ $sale->payment_type_badge_class }} text-[11px]">
                                                {{ $sale->payment_type_label }}
                                            </span>
                                        </td>
                                        <td class="text-xs text-[var(--text-secondary)]">
                                            {{ $sale->creator?->name ?? 'النظام' }}
                                        </td>
                                        <td class="font-numeric text-[11px] text-[var(--text-muted)]" dir="ltr">
                                            {{ $sale->created_at->format('Y-m-d H:i') }}
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route('sales.show', $sale->id) }}" class="erp-btn erp-btn-secondary text-xs py-1 px-2" title="عرض الفاتورة">
                                                <x-icon name="eye" size="13" />
                                                <span>عرض</span>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-6 text-[var(--text-muted)]">
                                            لا توجد فواتير مبيعات مسجلة حتى الآن.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Tab 2: حركات المخزن الأخيرة -->
                <div id="movements-tab" class="manager-tab-content hidden">
                    <div class="erp-table-responsive">
                        <table class="erp-table">
                            <thead>
                                <tr>
                                    <th>رقم الحركة</th>
                                    <th>الصنف</th>
                                    <th>نوع الحركة</th>
                                    <th>الكمية</th>
                                    <th>المستودع</th>
                                    <th>المسؤول</th>
                                    <th>التاريخ</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentMovements as $mv)
                                    <tr class="hover:bg-[var(--bg-hover)] transition">
                                        <td class="font-numeric text-xs font-semibold text-[var(--text-muted)]">
                                            {{ $mv->movement_number }}
                                        </td>
                                        <td>
                                            <div class="font-semibold text-xs text-[var(--text-main)]">{{ $mv->product?->name ?? 'صنف غير معروف' }}</div>
                                            <small class="text-[var(--text-muted)] font-numeric">{{ $mv->product?->sku }}</small>
                                        </td>
                                        <td>
                                            @if($mv->type === 'in')
                                                <span class="erp-badge erp-badge-success text-[10px]">وارِد / إضافة</span>
                                            @elseif($mv->type === 'out')
                                                <span class="erp-badge erp-badge-danger text-[10px]">منصرف / بيع</span>
                                            @elseif($mv->type === 'transfer')
                                                <span class="erp-badge erp-badge-info text-[10px]">تحويل مخزني</span>
                                            @else
                                                <span class="erp-badge erp-badge-warning text-[10px]">تسوية جرد</span>
                                            @endif
                                        </td>
                                        <td class="font-numeric font-bold text-xs {{ $mv->type === 'in' ? 'text-emerald-600' : 'text-rose-600' }}">
                                            {{ $mv->type === 'in' ? '+' : '-' }}{{ number_format($mv->quantity) }}
                                        </td>
                                        <td class="text-xs text-[var(--text-secondary)]">
                                            {{ $mv->fromWarehouse?->name ?? $mv->toWarehouse?->name ?? 'المستودع الرئيسي' }}
                                        </td>
                                        <td class="text-xs text-[var(--text-secondary)]">
                                            {{ $mv->user?->name ?? 'النظام' }}
                                        </td>
                                        <td class="font-numeric text-[11px] text-[var(--text-muted)]" dir="ltr">
                                            {{ $mv->created_at->format('Y-m-d H:i') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-6 text-[var(--text-muted)]">
                                            لا توجد حركات مخزنية مسجلة.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Tab 3: آخر فواتير المشتريات -->
                <div id="purchases-tab" class="manager-tab-content hidden">
                    <div class="erp-table-responsive">
                        <table class="erp-table">
                            <thead>
                                <tr>
                                    <th>رقم الفاتورة</th>
                                    <th>المورد / المصنع</th>
                                    <th>القيمة الإجمالية</th>
                                    <th>الحالة</th>
                                    <th>المسؤول</th>
                                    <th>التاريخ</th>
                                    <th class="text-center">إجراء</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentPurchases as $purchase)
                                    <tr class="hover:bg-[var(--bg-hover)] transition">
                                        <td class="font-numeric font-bold text-[var(--primary)]">
                                            {{ $purchase->invoice_number }}
                                        </td>
                                        <td>
                                            <span class="font-semibold text-xs">{{ $purchase->supplier?->name ?? 'مورد عام' }}</span>
                                        </td>
                                        <td class="font-numeric font-bold text-xs">
                                            {{ number_format($purchase->total_amount, 2) }} <small class="text-[var(--text-muted)]">ج.م</small>
                                        </td>
                                        <td>
                                            @if($purchase->status === 'posted')
                                                <span class="erp-badge erp-badge-success text-[10px]">معتمدة</span>
                                            @elseif($purchase->status === 'cancelled')
                                                <span class="erp-badge erp-badge-danger text-[10px]">ملغاة</span>
                                            @else
                                                <span class="erp-badge erp-badge-warning text-[10px]">مسودة</span>
                                            @endif
                                        </td>
                                        <td class="text-xs text-[var(--text-secondary)]">
                                            {{ $purchase->creator?->name ?? 'النظام' }}
                                        </td>
                                        <td class="font-numeric text-[11px] text-[var(--text-muted)]" dir="ltr">
                                            {{ $purchase->created_at->format('Y-m-d H:i') }}
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route('purchases.show', $purchase->id) }}" class="erp-btn erp-btn-secondary text-xs py-1 px-2" title="عرض فاتورة الشراء">
                                                <x-icon name="eye" size="13" />
                                                <span>عرض</span>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-6 text-[var(--text-muted)]">
                                            لا توجد فواتير مشتريات مسجلة.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar / Side Column: الأكثر مبيعاً ونظرة سريعة (1 Col) -->
        <div class="flex flex-col gap-4">
            <!-- Top Selling Products -->
            <div class="erp-card flex-1">
                <div class="erp-card-header border-b border-[var(--border-color)] pb-3">
                    <div class="erp-card-title flex items-center justify-between w-full">
                        <div class="flex items-center gap-2">
                            <x-icon name="trending-up" size="16" class="text-emerald-600" />
                            <span class="font-bold text-xs">الأصناف الأكثر مبيعاً</span>
                        </div>
                        <a href="{{ route('inventory.index') }}" class="text-[11px] text-[var(--primary)] hover:underline font-semibold">عرض الكل</a>
                    </div>
                </div>
                <div class="erp-card-body p-3">
                    <div class="flex flex-col gap-3">
                        @forelse($topSellingProducts as $item)
                            <div class="flex items-center justify-between gap-3 p-2 rounded-lg bg-[var(--bg-surface-alt)] hover:bg-[var(--bg-hover)] transition">
                                <div class="min-w-0 flex-1">
                                    <h4 class="text-xs font-bold text-[var(--text-main)] truncate" title="{{ $item->product?->name }}">
                                        {{ $item->product?->name ?? 'صنف مباع' }}
                                    </h4>
                                    <div class="text-[10px] text-[var(--text-muted)] font-numeric flex items-center gap-2 mt-0.5">
                                        <span>مبيعات: {{ number_format($item->total_sales, 2) }} ج.م</span>
                                        <span class="text-emerald-600 font-semibold">ربح: {{ number_format($item->total_profit, 2) }} ج.م</span>
                                    </div>
                                </div>
                                <div class="text-end font-numeric flex-shrink-0">
                                    <span class="erp-badge erp-badge-neutral font-bold text-xs">
                                        {{ number_format($item->total_qty) }} <span class="text-[10px] font-normal">قطعة</span>
                                    </span>
                                </div>
                            </div>
                        @empty
                            <p class="text-xs text-[var(--text-muted)] text-center py-4">
                                لا توجد مبيعات مسجلة حتى الآن.
                            </p>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Quick Management Tools -->
            <div class="erp-card">
                <div class="erp-card-header border-b border-[var(--border-color)] pb-2">
                    <div class="erp-card-title text-xs font-bold flex items-center gap-2">
                        <x-icon name="sliders" size="15" />
                        <span>اختصارات العمل الإداري السريع</span>
                    </div>
                </div>
                <div class="erp-card-body p-2 flex flex-col gap-1.5">
                    <a href="{{ route('sales.create') }}" class="quick-action-link">
                        <span class="inline-flex items-center gap-2">
                            <x-icon name="shopping-cart" size="14" />
                            <span>تسجيل فاتورة بيع جديدة</span>
                        </span>
                        <x-icon name="arrow-left" size="12" />
                    </a>
                    <a href="{{ route('purchases.create') }}" class="quick-action-link">
                        <span class="inline-flex items-center gap-2">
                            <x-icon name="truck" size="14" />
                            <span>تسجيل فاتورة شراء واستيراد</span>
                        </span>
                        <x-icon name="arrow-left" size="12" />
                    </a>
                    <a href="{{ route('inventory.low-stock') }}" class="quick-action-link">
                        <span class="inline-flex items-center gap-2">
                            <x-icon name="alert-triangle" size="14" class="text-rose-500" />
                            <span>تقرير نواقص وحد طلب المخزون</span>
                        </span>
                        <x-icon name="arrow-left" size="12" />
                    </a>
                    <a href="{{ route('treasuries.index') }}" class="quick-action-link">
                        <span class="inline-flex items-center gap-2">
                            <x-icon name="wallet" size="14" class="text-indigo-500" />
                            <span>حركة النقدية والخزائن</span>
                        </span>
                        <x-icon name="arrow-left" size="12" />
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        function switchManagerTab(tabId, btn) {
            document.querySelectorAll('.manager-tab-content').forEach(function(el) {
                el.classList.add('hidden');
                el.classList.remove('block');
            });
            const target = document.getElementById(tabId);
            if (target) {
                target.classList.remove('hidden');
                target.classList.add('block');
            }
            if (btn && btn.parentElement) {
                btn.parentElement.querySelectorAll('.erp-tab-link').forEach(function(link) {
                    link.classList.remove('active');
                });
                btn.classList.add('active');
            }
        }
    </script>
@endsection
