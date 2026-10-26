@extends('layouts.app')

@section('control_panel')
    <x-control-panel title="فواتير المبيعات والعملاء" :breadcrumbs="['المبيعات' => '#', 'فواتير المبيعات' => '#']">
        <x-slot:actions>
            <a href="{{ route('sales.create') }}" class="erp-btn erp-btn-primary">
                <x-icon name="plus" size="16" />
                <span>فاتورة بيع جديدة</span>
            </a>
            @can('manage-customers')
            <a href="{{ route('customers.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="users" size="16" />
                <span>دليل العملاء</span>
            </a>
            @endcan
        </x-slot:actions>

        <x-slot:extra>
            <span class="erp-badge erp-badge-neutral font-numeric">
                <x-icon name="shopping-cart" size="14" />
                <span>إجمالي الفواتير: {{ $metrics['total_count'] }}</span>
            </span>
        </x-slot:extra>
    </x-control-panel>
@endsection

@section('content')
    <div data-page="sales-index">
        <!-- Enterprise Sub-Navigation Tabs Bar (Faithful to Kayan ERP Design System) -->
        <div class="erp-card erp-tab-nav-card mb-4">
            <nav class="erp-tab-bar" aria-label="أقسام فواتير ومرتجعات المبيعات">
                <!-- 1. Sales Invoices -->
                <a href="{{ route('sales.index') }}" class="erp-tab-link active">
                    <x-icon name="shopping-cart" size="16" />
                    <span>فواتير المبيعات</span>
                    <span class="erp-badge erp-badge-neutral mr-1.5 px-2 py-0.5 text-xs font-bold font-numeric">
                        {{ $metrics['total_count'] }}
                    </span>
                </a>

                <!-- 2. Sales Returns -->
                <a href="{{ route('sales-returns.index') }}" class="erp-tab-link">
                    <x-icon name="rotate-ccw" size="16" />
                    <span>مرتجعات المبيعات</span>
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
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
            <!-- 1. Total Invoices Count -->
            <div class="kpi-card flex flex-col justify-between">
                <div class="kpi-header mb-1">
                    <span class="kpi-label">إجمالي فواتير المبيعات</span>
                    <span class="kpi-icon"><x-icon name="shopping-cart" size="18" /></span>
                </div>
                <div class="kpi-value font-numeric text-base sm:text-lg font-black text-[var(--text-main)]">
                    {{ number_format($metrics['total_count']) }} <span class="text-xs font-normal text-[var(--text-muted)]">فاتورة</span>
                </div>
                <div class="kpi-note font-numeric text-xs text-[var(--text-muted)]">
                    <span>جميع فواتير البيع المسجلة</span>
                </div>
            </div>

            <!-- 2. Posted Sales (Confirmed Revenue) -->
            <div class="kpi-card flex flex-col justify-between border-emerald-200 bg-emerald-50/40">
                <div class="kpi-header mb-1">
                    <span class="kpi-label text-emerald-900 font-bold">مبيعات مُرحلة (إيرادات)</span>
                    <span class="kpi-icon text-emerald-600 bg-emerald-100/70"><x-icon name="check-circle" size="18" /></span>
                </div>
                <div class="kpi-value font-numeric text-base sm:text-lg font-black text-emerald-800">
                    {{ number_format($metrics['posted_total'], 2) }} <span class="text-xs font-bold text-emerald-700">ج.م</span>
                </div>
                <div class="kpi-note text-emerald-700 text-xs font-medium">
                    <span>تم صرفها وتأكيد إيرادها</span>
                </div>
            </div>

            <!-- 3. Receivables (Remaining / Debt) -->
            <div class="kpi-card flex flex-col justify-between border-amber-200 bg-amber-50/40">
                <div class="kpi-header mb-1">
                    <span class="kpi-label text-amber-900 font-bold">متبقي آجل للتحصيل</span>
                    <span class="kpi-icon text-amber-600 bg-amber-100/70"><x-icon name="dollar-sign" size="18" /></span>
                </div>
                <div class="kpi-value font-numeric text-base sm:text-lg font-black text-amber-800">
                    {{ number_format($metrics['total_remaining'] ?? 0, 2) }} <span class="text-xs font-bold text-amber-700">ج.م</span>
                </div>
                <div class="kpi-note text-amber-700 text-xs font-medium">
                    <span>أقساط ومتبقيات على العملاء</span>
                </div>
            </div>

            <!-- 4. Profit or Drafts -->
            @can('view-cogs')
            <div class="kpi-card flex flex-col justify-between">
                <div class="kpi-header mb-1">
                    <span class="kpi-label text-indigo-900 font-semibold">إجمالي الأرباح المحققة</span>
                    <span class="kpi-icon text-indigo-600 bg-indigo-50"><x-icon name="trending-up" size="18" /></span>
                </div>
                <div class="kpi-value font-numeric text-base sm:text-lg font-black text-indigo-700">
                    {{ number_format($metrics['total_profit'], 2) }} <span class="text-xs font-normal text-indigo-600">ج.م</span>
                </div>
                <div class="kpi-note text-indigo-600 text-xs">
                    <span>الإيرادات مطروحاً منها التكلفة</span>
                </div>
            </div>
            @else
            <div class="kpi-card flex flex-col justify-between">
                <div class="kpi-header mb-1">
                    <span class="kpi-label">مسودات قيد المراجعة</span>
                    <span class="kpi-icon text-amber-600 bg-amber-50"><x-icon name="file-text" size="18" /></span>
                </div>
                <div class="kpi-value font-numeric text-base sm:text-lg font-black text-amber-800">
                    {{ $metrics['draft_count'] }} <span class="text-xs font-normal text-[var(--text-muted)]">فاتورة</span>
                </div>
                <div class="kpi-note font-numeric text-xs text-[var(--text-muted)]">
                    <span>فواتير لم تصرف من المخزن</span>
                </div>
            </div>
            @endcan
        </div>

        <!-- Search & Filter Bar -->
        <div class="erp-card p-4 mb-4">
            <form method="GET" action="{{ route('sales.index') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-2.5 items-center">
                <div class="sm:col-span-12 lg:col-span-4 relative">
                    <input 
                        type="search" 
                        name="search" 
                        value="{{ $currentSearch }}" 
                        class="erp-input pr-9 text-xs sm:text-sm" 
                        placeholder="ابحث برقم الفاتورة، اسم العميل أو الهاتف..."
                    >
                    <div class="absolute right-3 top-1/2 -translate-y-1/2 text-[var(--text-muted)] pointer-events-none">
                        <x-icon name="search" size="15" />
                    </div>
                </div>

                <div class="sm:col-span-6 lg:col-span-2">
                    <select name="status" class="erp-input text-xs sm:text-sm">
                        <option value="">-- كل الحالات --</option>
                        <option value="draft" {{ $currentStatus === 'draft' ? 'selected' : '' }}>مسودة</option>
                        <option value="posted" {{ $currentStatus === 'posted' ? 'selected' : '' }}>مرحلة (تم الصرف)</option>
                        <option value="voided" {{ $currentStatus === 'voided' ? 'selected' : '' }}>ملغاة ومسترجعة</option>
                    </select>
                </div>

                <div class="sm:col-span-6 lg:col-span-2">
                    <select name="payment_status" class="erp-input text-xs sm:text-sm">
                        <option value="">-- حالة السداد --</option>
                        <option value="paid" {{ ($currentPaymentStatus ?? '') === 'paid' ? 'selected' : '' }}>خالص (مسدد بالكامل)</option>
                        <option value="unpaid" {{ ($currentPaymentStatus ?? '') === 'unpaid' ? 'selected' : '' }}>آجل / به متبقي</option>
                    </select>
                </div>

                <div class="sm:col-span-4 lg:col-span-1">
                    <select name="price_tier" class="erp-input text-xs sm:text-sm">
                        <option value="">-- فئة السعر --</option>
                        <option value="retail" {{ $currentPriceTier === 'retail' ? 'selected' : '' }}>قطاعي</option>
                        <option value="wholesale" {{ $currentPriceTier === 'wholesale' ? 'selected' : '' }}>جملة</option>
                        <option value="distributor" {{ $currentPriceTier === 'distributor' ? 'selected' : '' }}>موزع</option>
                    </select>
                </div>

                <div class="sm:col-span-4 lg:col-span-1">
                    <select name="warehouse_id" class="erp-input text-xs sm:text-sm">
                        <option value="">-- المخزن --</option>
                        @foreach($warehouses as $wh)
                            <option value="{{ $wh->id }}" {{ (string)$currentWarehouseId === (string)$wh->id ? 'selected' : '' }}>
                                {{ $wh->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="sm:col-span-4 lg:col-span-2 flex items-center gap-1.5">
                    <button type="submit" class="erp-btn erp-btn-secondary text-xs sm:text-sm font-bold flex-1 justify-center">
                        <x-icon name="filter" size="14" />
                        <span>تصفية</span>
                    </button>
                    @if($currentSearch || $currentStatus || $currentWarehouseId || $currentCustomerId || $currentPriceTier || ($currentPaymentStatus ?? false))
                        <a href="{{ route('sales.index') }}" class="erp-btn erp-btn-ghost text-xs text-rose-600 hover:bg-rose-50 px-2.5" title="إلغاء الفلاتر">
                            <x-icon name="x" size="14" />
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Table View -->
        <div class="erp-card">
            @if($invoices->count() > 0)
                <div class="erp-table-responsive">
                    <table class="erp-table">
                        <thead>
                            <tr>
                                <th>رقم الفاتورة</th>
                                <th>تاريخ الفاتورة</th>
                                <th>العميل</th>
                                <th>المخزن المنصرف منه</th>
                                <th>فئة السعر</th>
                                <th>عدد البنود</th>
                                <th>إجمالي الفاتورة</th>
                                @can('view-cogs')
                                <th>التكلفة (COGS)</th>
                                <th>صافي الربح</th>
                                @endcan
                                <th>حالة الصرف</th>
                                <th>حالة السداد والمتبقي</th>
                                <th class="text-center">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($invoices as $inv)
                                <tr>
                                    <td>
                                        <a href="{{ route('sales.show', $inv) }}" class="font-numeric font-bold text-[var(--primary)] hover:underline">
                                            {{ $inv->invoice_number }}
                                        </a>
                                    </td>
                                    <td class="font-numeric text-xs">
                                        {{ \Carbon\Carbon::parse($inv->invoice_date)->format('Y-m-d') }}
                                    </td>
                                    <td>
                                        <div class="font-semibold text-[var(--text-main)]">{{ $inv->customer->name ?? 'غير محدد' }}</div>
                                        @if(!empty($inv->customer->phone))
                                            <div class="text-[11px] text-[var(--text-muted)] font-numeric">{{ $inv->customer->phone }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="text-xs">{{ $inv->warehouse->name ?? '—' }}</span>
                                    </td>
                                    <td>
                                        @if($inv->price_tier === 'distributor')
                                            <span class="erp-badge erp-badge-neutral text-xs">موزع</span>
                                        @elseif($inv->price_tier === 'wholesale')
                                            <span class="erp-badge erp-badge-neutral text-xs">جملة</span>
                                        @else
                                            <span class="erp-badge erp-badge-neutral text-xs">قطاعي</span>
                                        @endif
                                    </td>
                                    <td class="font-numeric text-center">
                                        {{ $inv->items->count() }}
                                    </td>
                                    <td class="font-numeric font-bold text-[var(--text-main)]">
                                        {{ number_format((float)$inv->total_amount, 2) }} ج.م
                                    </td>
                                    @can('view-cogs')
                                    <td class="font-numeric text-xs text-[var(--text-muted)]">
                                        {{ number_format((float)$inv->total_cogs, 2) }} ج.م
                                    </td>
                                    <td class="font-numeric text-xs font-bold {{ $inv->total_profit >= 0 ? 'text-[var(--success)]' : 'text-[var(--danger)]' }}">
                                        {{ number_format((float)$inv->total_profit, 2) }} ج.م
                                    </td>
                                    @endcan
                                    <td>
                                        <span class="erp-badge {{ $inv->status_badge_class }}">
                                            {{ $inv->status_label }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($inv->status === 'posted')
                                            <span class="erp-badge {{ $inv->payment_status_badge_class }} text-[11px]">
                                                {{ $inv->payment_status_label }}
                                            </span>
                                            @if((float) $inv->remaining_amount > 0)
                                                <div class="text-[11px] font-numeric font-bold text-rose-600 mt-0.5">
                                                    متبقي: {{ number_format((float)$inv->remaining_amount, 2) }} ج.م
                                                </div>
                                            @endif
                                        @else
                                            <span class="text-[var(--text-muted)] text-xs">—</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="inline-flex items-center gap-1.5">
                                            <a href="{{ route('sales.show', $inv) }}" class="erp-btn erp-btn-ghost erp-btn-sm" title="عرض التفاصيل">
                                                <x-icon name="eye" size="14" />
                                            </a>
                                            @if($inv->status === 'posted' && (float)$inv->remaining_amount > 0)
                                                <a href="{{ route('sales.show', $inv) }}" class="erp-btn erp-btn-ghost erp-btn-sm text-emerald-600 font-bold" title="تسجيل دفعة سداد">
                                                    <x-icon name="dollar-sign" size="14" />
                                                </a>
                                            @endif
                                            @if($inv->status === 'draft')
                                                <a href="{{ route('sales.edit', $inv) }}" class="erp-btn erp-btn-ghost erp-btn-sm text-amber-600" title="تعديل الفاتورة">
                                                    <x-icon name="edit-3" size="14" />
                                                </a>
                                            @elseif($inv->status === 'posted')
                                                <form action="{{ route('sales.unpost-and-edit', $inv) }}" method="POST" class="inline" data-confirm="سيتم فك ترحيل الفاتورة واسترجاع أرصدة المخزون للتعديل. هل تريد المتابعة؟">
                                                    @csrf
                                                    <button type="submit" class="erp-btn erp-btn-ghost erp-btn-sm text-amber-600" title="تعديل الفاتورة (إرجاع للرصيد)">
                                                        <x-icon name="edit-3" size="14" />
                                                    </button>
                                                </form>
                                            @endif
                                            <!-- Print Combobox Dropdown -->
                                            <x-print-dropdown 
                                                size="sm"
                                                title="طباعة"
                                                code="{{ $inv->invoice_number }}"
                                                urlThermal="{{ route('sales.print', [$inv, 'format' => 'thermal_80mm']) }}"
                                                directThermal="{{ route('sales.direct-print', [$inv, 'format' => 'thermal_80mm']) }}"
                                                urlA4="{{ route('sales.print', [$inv, 'format' => 'a4']) }}"
                                                urlA5="{{ route('sales.print', [$inv, 'format' => 'a5']) }}"
                                            />

                                            <!-- حذف الفاتورة نهائياً -->
                                            <form action="{{ route('sales.destroy', $inv) }}" method="POST" class="inline" data-confirm="هل تريد مسح الفاتورة (#{{ $inv->invoice_number }}) نهائياً؟ {{ $inv->status === 'posted' ? 'سيتم استرجاع رصيد المخزون وإلغاء المديونية أولاً.' : '' }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="erp-btn erp-btn-ghost erp-btn-sm text-rose-600 hover:text-rose-700" title="مسح الفاتورة">
                                                    <x-icon name="trash" size="14" />
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($invoices->hasPages())
                    <div class="p-3 border-t border-[var(--border-light)]">
                        {{ $invoices->links() }}
                    </div>
                @endif
            @else
                <div class="erp-empty-state p-10 text-center">
                    <div class="erp-empty-icon mb-3 text-[var(--text-muted)]">
                        <x-icon name="shopping-cart" size="48" />
                    </div>
                    <div class="font-bold text-base text-[var(--text-main)] mb-1">لا توجد فواتير مبيعات مسجلة</div>
                    <p class="text-xs text-[var(--text-muted)] mb-4">ابدأ بإنشاء أول فاتورة مبيعات لصرف المنتجات وتطبيق تسعير ودفعات FIFO آلياً.</p>
                    <a href="{{ route('sales.create') }}" class="erp-btn erp-btn-primary">
                        <x-icon name="plus" size="16" />
                        <span>إنشاء فاتورة مبيعات جديدة</span>
                    </a>
                </div>
            @endif
        </div>
    </div>
@endsection
