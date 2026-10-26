@extends('layouts.app')

@section('control_panel')
    <x-control-panel title="مرتجعات فواتير المبيعات" :breadcrumbs="['المبيعات والعملاء' => route('sales.index'), 'مرتجعات المبيعات' => '#']">
        <x-slot:actions>
            <a href="{{ route('sales-returns.create') }}" class="erp-btn erp-btn-primary">
                <x-icon name="plus" size="16" />
                <span>تسجيل مرتجع جديد</span>
            </a>
            <a href="{{ route('sales.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="shopping-cart" size="16" />
                <span>فواتير المبيعات</span>
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
    <div data-page="sales-returns-index">
        <!-- Enterprise Sub-Navigation Tabs Bar (Faithful to Kayan ERP Design System) -->
        <div class="erp-card erp-tab-nav-card mb-4">
            <nav class="erp-tab-bar" aria-label="أقسام فواتير ومرتجعات المبيعات">
                <!-- 1. Sales Invoices -->
                <a href="{{ route('sales.index') }}" class="erp-tab-link">
                    <x-icon name="shopping-cart" size="16" />
                    <span>فواتير المبيعات</span>
                </a>

                <!-- 2. Sales Returns -->
                <a href="{{ route('sales-returns.index') }}" class="erp-tab-link active">
                    <x-icon name="rotate-ccw" size="16" />
                    <span>مرتجعات المبيعات</span>
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

        <!-- Global KPI Summary Cards (Faithful to Kayan ERP Design System) -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4">
            <!-- 1. Total Returns Count -->
            <div class="kpi-card flex flex-col justify-between">
                <div class="kpi-header mb-1">
                    <span class="kpi-label">إجمالي حركات المرتجع</span>
                    <span class="kpi-icon"><x-icon name="rotate-ccw" size="18" /></span>
                </div>
                <div class="kpi-value font-numeric text-base sm:text-lg font-black text-[var(--text-main)]">
                    {{ number_format($metrics['total_count']) }} <span class="text-xs font-normal text-[var(--text-muted)]">إذن</span>
                </div>
                <div class="kpi-note font-numeric text-xs text-[var(--text-muted)]">
                    <span>جميع المرتجعات المسجلة</span>
                </div>
            </div>

            <!-- 2. Posted Returns (Inventory Restored) -->
            <div class="kpi-card flex flex-col justify-between border-emerald-200 bg-emerald-50/40">
                <div class="kpi-header mb-1">
                    <span class="kpi-label text-emerald-900 font-bold">مرتجعات مُرحلة للمخزن</span>
                    <span class="kpi-icon text-emerald-600 bg-emerald-100/70"><x-icon name="check-circle" size="18" /></span>
                </div>
                <div class="kpi-value font-numeric text-base sm:text-lg font-black text-emerald-800">
                    {{ number_format($metrics['posted_total'], 2) }} <span class="text-xs font-bold text-emerald-700">ج.م</span>
                </div>
                <div class="kpi-note text-emerald-700 text-xs font-medium">
                    <span>تم استرجاعها وتسوية الحساب</span>
                </div>
            </div>

            <!-- 3. Draft Returns -->
            <div class="kpi-card flex flex-col justify-between border-amber-200 bg-amber-50/40">
                <div class="kpi-header mb-1">
                    <span class="kpi-label text-amber-900 font-bold">مسودات قيد المراجعة</span>
                    <span class="kpi-icon text-amber-600 bg-amber-100/70"><x-icon name="file-text" size="18" /></span>
                </div>
                <div class="kpi-value font-numeric text-base sm:text-lg font-black text-amber-800">
                    {{ $metrics['draft_count'] }} <span class="text-xs font-normal text-[var(--text-muted)]">مسودة</span>
                </div>
                <div class="kpi-note text-amber-700 text-xs font-medium">
                    <span>تتطلب اعتماد وترحيل نهائي</span>
                </div>
            </div>
        </div>

        <!-- Search & Filter Bar -->
        <div class="erp-card p-4 mb-4">
            <form method="GET" action="{{ route('sales-returns.index') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-2.5 items-center">
                <div class="sm:col-span-12 lg:col-span-5 relative">
                    <input 
                        type="search" 
                        name="search" 
                        value="{{ $currentSearch }}" 
                        class="erp-input pr-9 text-xs sm:text-sm" 
                        placeholder="ابحث برقم المرتجع، الفاتورة أو العميل..."
                    >
                    <div class="absolute right-3 top-1/2 -translate-y-1/2 text-[var(--text-muted)] pointer-events-none">
                        <x-icon name="search" size="15" />
                    </div>
                </div>

                <div class="sm:col-span-6 lg:col-span-2">
                    <select name="status" class="erp-input text-xs sm:text-sm">
                        <option value="">-- كل الحالات --</option>
                        <option value="draft" {{ $currentStatus === 'draft' ? 'selected' : '' }}>مسودة</option>
                        <option value="posted" {{ $currentStatus === 'posted' ? 'selected' : '' }}>مُرحل للمخزن</option>
                    </select>
                </div>

                <div class="sm:col-span-6 lg:col-span-2">
                    <select name="warehouse_id" class="erp-input text-xs sm:text-sm">
                        <option value="">-- كل المخازن --</option>
                        @foreach($warehouses as $wh)
                            <option value="{{ $wh->id }}" {{ (string)$currentWarehouseId === (string)$wh->id ? 'selected' : '' }}>
                                {{ $wh->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="sm:col-span-6 lg:col-span-2">
                    <select name="customer_id" class="erp-input text-xs sm:text-sm">
                        <option value="">-- كل العملاء --</option>
                        @foreach($customers as $c)
                            <option value="{{ $c->id }}" {{ (string)$currentCustomerId === (string)$c->id ? 'selected' : '' }}>
                                {{ $c->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="sm:col-span-6 lg:col-span-1 flex items-center gap-1.5">
                    <button type="submit" class="erp-btn erp-btn-secondary text-xs sm:text-sm font-bold flex-1 justify-center">
                        <x-icon name="filter" size="14" />
                        <span>تصفية</span>
                    </button>
                    @if($currentSearch || $currentStatus || $currentWarehouseId || $currentCustomerId)
                        <a href="{{ route('sales-returns.index') }}" class="erp-btn erp-btn-ghost text-xs text-rose-600 hover:bg-rose-50 px-2.5" title="إلغاء الفلاتر">
                            <x-icon name="x" size="14" />
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Returns Table View -->
        <div class="erp-card">
            @if($returns->count() > 0)
                <div class="erp-table-responsive">
                    <table class="erp-table">
                        <thead>
                            <tr>
                                <th>رقم المرتجع</th>
                                <th>تاريخ المرتجع</th>
                                <th>فاتورة المبيعات الأصلية</th>
                                <th>العميل</th>
                                <th>مستودع الاسترجاع</th>
                                <th class="text-center">عدد البنود</th>
                                <th>إجمالي قيمة المرتجع</th>
                                <th>الحالة</th>
                                <th class="text-center">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($returns as $ret)
                                <tr>
                                    <td>
                                        <a href="{{ route('sales-returns.show', $ret) }}" class="font-numeric font-bold text-[var(--primary)] hover:underline">
                                            {{ $ret->return_number }}
                                        </a>
                                    </td>
                                    <td class="font-numeric text-xs">
                                        {{ \Carbon\Carbon::parse($ret->return_date)->format('Y-m-d') }}
                                    </td>
                                    <td>
                                        @if($ret->salesInvoice)
                                            <a href="{{ route('sales.show', $ret->salesInvoice) }}" class="font-numeric text-xs font-semibold text-[var(--primary)] hover:underline flex items-center gap-1">
                                                <x-icon name="file-text" size="12" />
                                                <span>{{ $ret->salesInvoice->invoice_number }}</span>
                                            </a>
                                        @else
                                            <span class="text-[var(--text-muted)] text-xs">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="font-semibold text-[var(--text-main)]">{{ $ret->customer->name ?? 'غير محدد' }}</div>
                                        @if(!empty($ret->customer->phone))
                                            <div class="text-[11px] text-[var(--text-muted)] font-numeric">{{ $ret->customer->phone }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="text-xs">{{ $ret->warehouse->name ?? '—' }}</span>
                                    </td>
                                    <td class="font-numeric text-center">
                                        {{ $ret->items->count() }}
                                    </td>
                                    <td class="font-numeric font-bold text-rose-700">
                                        {{ number_format((float)$ret->total_amount, 2) }} ج.م
                                    </td>
                                    <td>
                                        <span class="erp-badge {{ $ret->status_badge_class }}">
                                            {{ $ret->status_label }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="inline-flex items-center gap-1.5">
                                            <a href="{{ route('sales-returns.show', $ret) }}" class="erp-btn erp-btn-ghost erp-btn-sm" title="عرض تفاصيل المرتجع">
                                                <x-icon name="eye" size="14" />
                                            </a>
                                            <a href="{{ route('sales-returns.print', $ret) }}" target="_blank" class="erp-btn erp-btn-ghost erp-btn-sm text-[var(--primary)]" title="طباعة إذن المرتجع">
                                                <x-icon name="printer" size="14" />
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($returns->hasPages())
                    <div class="p-3 border-t border-[var(--border-light)]">
                        {{ $returns->links() }}
                    </div>
                @endif
            @else
                <div class="erp-empty-state p-10 text-center">
                    <div class="erp-empty-icon mb-3 text-[var(--text-muted)]">
                        <x-icon name="rotate-ccw" size="48" />
                    </div>
                    <div class="font-bold text-base text-[var(--text-main)] mb-1">لا توجد مرتجعات مبيعات مسجلة</div>
                    <p class="text-xs text-[var(--text-muted)] mb-4">يمكنك إنشاء إذن مرتجع جديد بناءً على أي فاتورة مبيعات معتمدة.</p>
                    <a href="{{ route('sales-returns.create') }}" class="erp-btn erp-btn-primary">
                        <x-icon name="plus" size="16" />
                        <span>تسجيل أول مرتجع مبيعات</span>
                    </a>
                </div>
            @endif
        </div>
    </div>
@endsection
