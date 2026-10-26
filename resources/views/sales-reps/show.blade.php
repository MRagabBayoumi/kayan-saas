@extends('layouts.app')

@section('control_panel')
    <x-control-panel title="تحليل أداء المندوب: {{ $rep->name }}" :breadcrumbs="['المناديب' => route('sales-reps.index'), $rep->name => '#']">
        <x-slot:actions>
            <a href="{{ route('sales-reps.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="arrow-right" size="16" />
                <span>رجوع لقائمة المناديب</span>
            </a>
            <a href="{{ route('portal.index') }}" target="_blank" class="erp-btn erp-btn-primary" title="معاينة بوابة المندوب الميداني">
                <x-icon name="smartphone" size="16" />
                <span>فتح بوابة المندوب</span>
            </a>
        </x-slot:actions>

        <x-slot:extra>
            <span class="erp-badge erp-badge-primary font-numeric px-2.5 py-1 text-xs font-bold">
                <span>العمولة:</span>
                <span>{{ (float) $rep->commission_rate }}{{ $rep->commission_type === 'percentage' ? '%' : ' ج.م' }}</span>
            </span>
            @if($rep->is_active)
                <span class="erp-badge erp-badge-success text-xs">حساب نشط</span>
            @else
                <span class="erp-badge erp-badge-danger text-xs">حساب معطل</span>
            @endif
        </x-slot:extra>
    </x-control-panel>
@endsection

@section('content')
<div class="space-y-4" data-page="sales-rep-analytics">

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

    <!-- Sales Rep Profile & Date Filter Card -->
    <div class="erp-card p-4 sm:p-5 mb-0">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-4 border-b border-[var(--border-light)]">
            <!-- Rep Identity Info -->
            <div class="flex items-center gap-3.5">
                <div class="w-12 h-12 rounded-lg bg-[var(--primary-soft)] text-[var(--primary)] font-bold text-lg flex items-center justify-center border border-[var(--primary-border)]">
                    {{ mb_substr($rep->name, 0, 1) }}
                </div>
                <div>
                    <h1 class="text-base sm:text-lg font-bold text-[var(--text-main)] flex items-center gap-2">
                        <span>{{ $rep->name }}</span>
                        @if($rep->is_active)
                            <span class="w-2 h-2 rounded-full bg-emerald-500" title="نشط"></span>
                        @else
                            <span class="w-2 h-2 rounded-full bg-rose-500" title="معطل"></span>
                        @endif
                    </h1>
                    <div class="text-xs text-[var(--text-muted)] font-numeric flex flex-wrap items-center gap-2 mt-0.5">
                        @if($rep->username)
                            <span class="text-[11px] font-mono text-sky-700 bg-sky-50 border border-sky-200 rounded px-1.5 py-0.5" dir="ltr" title="اسم المستخدم لتسجيل الدخول">{{ '@' . $rep->username }}</span>
                            <span>·</span>
                        @endif
                        <span>هاتف: <strong>{{ $rep->phone ?? '---' }}</strong></span>
                        <span>·</span>
                        <span>بريد: <strong>{{ $rep->email ?? '---' }}</strong></span>
                        <span>·</span>
                        <span>الفرع: <strong>{{ $rep->warehouse?->name ?? 'كافة الفروع' }}</strong></span>
                    </div>
                </div>
            </div>

            <!-- Date Range Filter Tabs -->
            <form method="GET" action="{{ route('sales-reps.show', $rep) }}" class="flex flex-wrap items-center gap-1.5 self-start md:self-auto">
                <input type="hidden" name="tab" value="{{ $activeTab }}">
                
                <div class="inline-flex rounded border border-[var(--border-color)] bg-[var(--bg-hover)] p-0.5 text-xs font-semibold">
                    <a href="{{ route('sales-reps.show', ['rep' => $rep->id, 'range' => 'all', 'tab' => $activeTab]) }}" 
                       class="px-2.5 py-1 rounded {{ $currentRange === 'all' ? 'bg-white text-[var(--primary)] font-bold shadow-xs' : 'text-[var(--text-secondary)] hover:text-[var(--text-main)]' }}">
                        الكل
                    </a>
                    <a href="{{ route('sales-reps.show', ['rep' => $rep->id, 'range' => 'today', 'tab' => $activeTab]) }}" 
                       class="px-2.5 py-1 rounded {{ $currentRange === 'today' ? 'bg-white text-[var(--primary)] font-bold shadow-xs' : 'text-[var(--text-secondary)] hover:text-[var(--text-main)]' }}">
                        اليوم
                    </a>
                    <a href="{{ route('sales-reps.show', ['rep' => $rep->id, 'range' => 'this_week', 'tab' => $activeTab]) }}" 
                       class="px-2.5 py-1 rounded {{ $currentRange === 'this_week' ? 'bg-white text-[var(--primary)] font-bold shadow-xs' : 'text-[var(--text-secondary)] hover:text-[var(--text-main)]' }}">
                        هذا الأسبوع
                    </a>
                    <a href="{{ route('sales-reps.show', ['rep' => $rep->id, 'range' => 'this_month', 'tab' => $activeTab]) }}" 
                       class="px-2.5 py-1 rounded {{ $currentRange === 'this_month' ? 'bg-white text-[var(--primary)] font-bold shadow-xs' : 'text-[var(--text-secondary)] hover:text-[var(--text-main)]' }}">
                        هذا الشهر
                    </a>
                </div>

                <div class="flex items-center gap-1">
                    <input type="date" name="from_date" value="{{ $fromDate }}" class="erp-input text-xs font-numeric w-32 h-[30px]" title="من تاريخ">
                    <span class="text-xs text-[var(--text-muted)]">-</span>
                    <input type="date" name="to_date" value="{{ $toDate }}" class="erp-input text-xs font-numeric w-32 h-[30px]" title="إلى تاريخ">
                    <input type="hidden" name="range" value="custom">
                    <button type="submit" class="erp-btn erp-btn-secondary text-xs h-[30px] px-2.5">
                        <x-icon name="filter" size="12" />
                        <span>تطبيق</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Analytical Performance Metrics (10 Deep KPIs) -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 pt-4">
            <!-- 1. Total Sales Volume -->
            <div class="kpi-card flex flex-col justify-between mb-0">
                <div class="kpi-header mb-1">
                    <span class="kpi-label">إجمالي المبيعات</span>
                    <span class="kpi-icon text-[var(--primary)]"><x-icon name="trending-up" size="16" /></span>
                </div>
                <div class="kpi-value font-numeric text-base sm:text-lg font-black text-[var(--text-main)]">
                    {{ number_format($analytics['total_sales'], 2) }} <span class="text-xs font-normal text-[var(--text-muted)]">ج.م</span>
                </div>
                <div class="kpi-note font-numeric text-xs text-[var(--text-muted)]">
                    <span>فواتير معتمدة في الفترة</span>
                </div>
            </div>

            <!-- 2. Orders Count & Avg Order -->
            <div class="kpi-card flex flex-col justify-between mb-0">
                <div class="kpi-header mb-1">
                    <span class="kpi-label">الطلبات والمتوسط</span>
                    <span class="kpi-icon text-sky-600"><x-icon name="shopping-bag" size="16" /></span>
                </div>
                <div class="kpi-value font-numeric text-base sm:text-lg font-black text-[var(--primary)]">
                    {{ $analytics['invoices_count'] }} <span class="text-xs font-normal text-[var(--text-muted)]">طلب</span>
                </div>
                <div class="kpi-note font-numeric text-xs text-[var(--text-muted)]">
                    <span>متوسط: {{ number_format($analytics['avg_order_value'], 2) }} ج.م</span>
                </div>
            </div>

            <!-- 3. Total Earned Commissions -->
            <div class="kpi-card flex flex-col justify-between mb-0">
                <div class="kpi-header mb-1">
                    <span class="kpi-label text-indigo-900 font-semibold">إجمالي العمولات</span>
                    <span class="kpi-icon text-indigo-600"><x-icon name="dollar-sign" size="16" /></span>
                </div>
                <div class="kpi-value font-numeric text-base sm:text-lg font-black text-indigo-700">
                    {{ number_format($analytics['total_commissions'], 2) }} <span class="text-xs font-normal text-indigo-600">ج.م</span>
                </div>
                <div class="kpi-note font-numeric text-xs text-indigo-600">
                    <span>نسبة: {{ (float)$rep->commission_rate }}{{ $rep->commission_type === 'percentage' ? '%' : ' ج.م' }}</span>
                </div>
            </div>

            <!-- 4. Paid vs Pending Commissions -->
            <div class="kpi-card flex flex-col justify-between mb-0 border-amber-200 bg-amber-50/30">
                <div class="kpi-header mb-1">
                    <span class="kpi-label text-amber-900 font-bold">المصروف والمعلق</span>
                    <span class="kpi-icon text-amber-600"><x-icon name="clock" size="16" /></span>
                </div>
                <div class="kpi-value font-numeric text-sm sm:text-base font-black text-emerald-700">
                    صُرف: {{ number_format($analytics['paid_commissions'], 2) }} <span class="text-[11px] font-normal text-emerald-600">ج.م</span>
                </div>
                <div class="kpi-note font-numeric text-xs font-bold text-amber-700">
                    <span>معلق: {{ number_format($analytics['pending_commissions'], 2) }} ج.م</span>
                </div>
            </div>

            <!-- 5. Customers Count & Debt -->
            <div class="kpi-card flex flex-col justify-between mb-0">
                <div class="kpi-header mb-1">
                    <span class="kpi-label">العملاء التابعين</span>
                    <span class="kpi-icon text-[var(--primary)]"><x-icon name="users" size="16" /></span>
                </div>
                <div class="kpi-value font-numeric text-base sm:text-lg font-black text-[var(--text-main)]">
                    {{ $analytics['customers_count'] }} <span class="text-xs font-normal text-[var(--text-muted)]">عميل</span>
                </div>
                <div class="kpi-note font-numeric text-xs text-rose-600 font-medium">
                    <span>مديونيات: {{ number_format($analytics['customers_debt'], 2) }} ج.م</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs for Deep Inspection (Faithful to Kayan ERP Design System) -->
    <div class="erp-card erp-tab-nav-card mb-4">
        <nav class="erp-tab-bar">
            <a href="{{ route('sales-reps.show', ['rep' => $rep->id, 'range' => $currentRange, 'from_date' => $fromDate, 'to_date' => $toDate, 'tab' => 'invoices']) }}"
               class="erp-tab-link {{ $activeTab === 'invoices' ? 'active' : '' }}">
                <x-icon name="shopping-bag" size="16" />
                <span>فواتير ومبيعات المندوب ({{ $invoices->total() }})</span>
            </a>

            <a href="{{ route('sales-reps.show', ['rep' => $rep->id, 'range' => $currentRange, 'from_date' => $fromDate, 'to_date' => $toDate, 'tab' => 'customers']) }}"
               class="erp-tab-link {{ $activeTab === 'customers' ? 'active' : '' }}">
                <x-icon name="users" size="16" />
                <span>عملاء المندوب التابعين ({{ $customers->total() }})</span>
            </a>

            <a href="{{ route('sales-reps.show', ['rep' => $rep->id, 'range' => $currentRange, 'from_date' => $fromDate, 'to_date' => $toDate, 'tab' => 'commissions']) }}"
               class="erp-tab-link {{ $activeTab === 'commissions' ? 'active' : '' }}">
                <x-icon name="dollar-sign" size="16" />
                <span>سجل العمولات والصرف ({{ $commissions->total() }})</span>
            </a>
        </nav>
    </div>

        <!-- TAB 1: Invoices and Orders -->
        @if($activeTab === 'invoices')
            <div class="overflow-x-auto">
                <table class="erp-table w-full text-right min-w-[920px]">
                    <thead>
                        <tr>
                            <th>رقم الفاتورة</th>
                            <th>التاريخ</th>
                            <th>العميل</th>
                            <th>المستودع</th>
                            <th>طريقة الدفع</th>
                            <th>إجمالي الفاتورة</th>
                            <th>المدفوع</th>
                            <th>المتبقي</th>
                            <th>العمولة</th>
                            <th>الحالة</th>
                            <th class="text-center">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[var(--border-light)]">
                        @forelse($invoices as $inv)
                            <tr class="hover:bg-[var(--bg-hover)] transition-colors">
                                <td class="font-numeric font-bold text-xs">#{{ $inv->invoice_number }}</td>
                                <td class="font-numeric text-xs text-[var(--text-secondary)]">{{ $inv->invoice_date->format('Y/m/d') }}</td>
                                <td>
                                    <div class="font-bold text-xs text-[var(--text-main)]">{{ $inv->customer?->name ?? 'عميل نقدي' }}</div>
                                    @if($inv->customer?->phone)
                                        <div class="text-[11px] text-[var(--text-muted)] font-numeric">{{ $inv->customer->phone }}</div>
                                    @endif
                                </td>
                                <td class="text-xs text-[var(--text-secondary)]">{{ $inv->warehouse?->name ?? '---' }}</td>
                                <td>
                                    <span class="erp-badge erp-badge-neutral text-[10px]">
                                        {{ match($inv->payment_type) {
                                            'credit' => 'آجل',
                                            'partial' => 'جزئي',
                                            default => 'نقدي'
                                        } }}
                                    </span>
                                </td>
                                <td class="font-numeric font-bold text-xs text-[var(--text-main)]">
                                    {{ number_format($inv->total_amount, 2) }} ج.م
                                </td>
                                <td class="font-numeric text-xs text-emerald-700">
                                    {{ number_format($inv->paid_amount, 2) }} ج.م
                                </td>
                                <td class="font-numeric text-xs {{ (float)$inv->remaining_amount > 0 ? 'text-rose-600 font-bold' : 'text-slate-400' }}">
                                    {{ number_format($inv->remaining_amount, 2) }} ج.م
                                </td>
                                <td class="font-numeric font-bold text-xs text-emerald-700">
                                    @if($inv->commission)
                                        +{{ number_format($inv->commission->commission_amount, 2) }} ج.م
                                    @else
                                        ---
                                    @endif
                                </td>
                                <td>
                                    @if($inv->status === 'posted')
                                        <span class="erp-badge erp-badge-success text-[10px]">معتمدة ومرحلة</span>
                                    @else
                                        <span class="erp-badge erp-badge-warning text-[10px]">مسودة</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="inline-flex items-center gap-1">
                                        <a href="{{ route('sales.show', $inv) }}" class="erp-btn erp-btn-sm erp-btn-secondary p-1.5" title="عرض الفاتورة">
                                            <x-icon name="eye" size="13" />
                                        </a>
                                        <a href="{{ route('sales.print', ['sale' => $inv->id, 'type' => 'thermal']) }}" target="_blank" class="erp-btn erp-btn-sm erp-btn-secondary p-1.5" title="طباعة الإيصال">
                                            <x-icon name="printer" size="13" />
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center py-10 text-[var(--text-muted)] text-xs">
                                    لا توجد فواتير بيع مسجلة لهذا المندوب في الفترة المحددة.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($invoices->hasPages())
                <div class="p-3 border-t border-[var(--border-light)]">
                    {{ $invoices->links() }}
                </div>
            @endif
        @endif

        <!-- TAB 2: Assigned Customers -->
        @if($activeTab === 'customers')
            <div class="overflow-x-auto">
                <table class="erp-table w-full text-right min-w-[920px]">
                    <thead>
                        <tr>
                            <th>العميل</th>
                            <th>الشركة / المتجر</th>
                            <th>الهاتف</th>
                            <th>المدينة / العنوان</th>
                            <th>فئة التسعير</th>
                            <th>الرصيد الحالي</th>
                            <th>عدد الفواتير</th>
                            <th class="text-center">التواصل والموقع</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[var(--border-light)]">
                        @forelse($customers as $c)
                            <tr class="hover:bg-[var(--bg-hover)] transition-colors">
                                <td class="font-bold text-xs text-[var(--text-main)]">
                                    <a href="{{ route('customers.show', $c) }}" class="hover:underline text-[var(--primary)]">{{ $c->name }}</a>
                                </td>
                                <td class="text-xs text-[var(--text-secondary)]">{{ $c->company_name ?? '---' }}</td>
                                <td class="font-numeric text-xs text-[var(--text-secondary)]">{{ $c->phone }}</td>
                                <td class="text-xs text-[var(--text-muted)]">
                                    {{ implode(' - ', array_filter([$c->city, $c->address])) ?: '---' }}
                                </td>
                                <td>
                                    <span class="erp-badge erp-badge-neutral text-[10px]">
                                        {{ match($c->pricing_tier) {
                                            'wholesale' => 'جملة',
                                            'distributor' => 'موزع',
                                            default => 'قطاعي'
                                        } }}
                                    </span>
                                </td>
                                <td class="font-numeric font-bold text-xs {{ (float)$c->current_balance > 0 ? 'text-rose-600' : 'text-slate-600' }}">
                                    {{ number_format($c->current_balance, 2) }} ج.م
                                </td>
                                <td class="font-numeric text-xs text-center font-bold">{{ $c->sales_invoices_count }}</td>
                                <td class="text-center">
                                    <div class="inline-flex items-center gap-1.5">
                                        @if($c->phone)
                                            <a href="tel:{{ $c->phone }}" class="p-1.5 rounded border border-[var(--border-color)] text-slate-600 hover:text-emerald-700 hover:bg-emerald-50" title="اتصال">
                                                <x-icon name="phone" size="13" />
                                            </a>
                                            @php
                                                $waPhone = preg_replace('/[^0-9]/', '', $c->phone);
                                                if (str_starts_with($waPhone, '0')) $waPhone = '2' . $waPhone;
                                            @endphp
                                            <a href="https://wa.me/{{ $waPhone }}" target="_blank" class="p-1.5 rounded border border-emerald-200 text-emerald-700 bg-emerald-50 hover:bg-emerald-100" title="واتساب">
                                                <x-icon name="message-circle" size="13" />
                                            </a>
                                        @endif
                                        @if($c->latitude && $c->longitude)
                                            <a href="https://www.google.com/maps/search/?api=1&query={{ $c->latitude }},{{ $c->longitude }}" target="_blank" class="p-1.5 rounded border border-[var(--border-color)] text-sky-600 hover:bg-sky-50" title="موقع الـ GPS">
                                                <x-icon name="map-pin" size="13" />
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-10 text-[var(--text-muted)] text-xs">
                                    لم يقم هذا المندوب بربط أو تسجيل عملاء حتى الآن.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($customers->hasPages())
                <div class="p-3 border-t border-[var(--border-light)]">
                    {{ $customers->links() }}
                </div>
            @endif
        @endif

        <!-- TAB 3: Commissions Ledger -->
        @if($activeTab === 'commissions')
            <div class="overflow-x-auto">
                <table class="erp-table w-full text-right min-w-[920px]">
                    <thead>
                        <tr>
                            <th>رقم الفاتورة</th>
                            <th>التاريخ</th>
                            <th>العميل</th>
                            <th>قيمة الفاتورة</th>
                            <th>نسبة العمولة</th>
                            <th>قيمة العمولة المستحقة</th>
                            <th>الحالة</th>
                            <th>تاريخ الصرف</th>
                            <th class="text-center">إجراء الصرف</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[var(--border-light)]">
                        @forelse($commissions as $comm)
                            <tr class="hover:bg-[var(--bg-hover)] transition-colors">
                                <td class="font-numeric font-bold text-xs">
                                    @if($comm->salesInvoice)
                                        <a href="{{ route('sales.show', $comm->salesInvoice) }}" class="text-[var(--primary)] hover:underline">
                                            #{{ $comm->salesInvoice->invoice_number }}
                                        </a>
                                    @else
                                        ---
                                    @endif
                                </td>
                                <td class="font-numeric text-xs text-[var(--text-secondary)]">{{ $comm->created_at->format('Y/m/d') }}</td>
                                <td class="text-xs font-bold text-[var(--text-main)]">{{ $comm->customer?->name ?? 'عميل نقدي' }}</td>
                                <td class="font-numeric text-xs text-[var(--text-main)]">{{ number_format($comm->invoice_total, 2) }} ج.م</td>
                                <td class="font-numeric text-xs text-[var(--text-muted)]">{{ (float)$comm->commission_rate }}%</td>
                                <td class="font-numeric font-bold text-xs text-emerald-700">
                                    +{{ number_format($comm->commission_amount, 2) }} ج.م
                                </td>
                                <td>
                                    @if($comm->status === 'paid')
                                        <span class="erp-badge erp-badge-success text-[10px]">تم الصرف</span>
                                    @elseif($comm->status === 'approved')
                                        <span class="erp-badge erp-badge-info text-[10px]">معتمدة</span>
                                    @else
                                        <span class="erp-badge erp-badge-warning text-[10px]">قيد الانتظار</span>
                                    @endif
                                </td>
                                <td class="font-numeric text-xs text-[var(--text-muted)]">
                                    {{ $comm->paid_at ? $comm->paid_at->format('Y/m/d H:i') : '---' }}
                                </td>
                                <td class="text-center">
                                    @if($comm->status !== 'paid')
                                        <form method="POST" action="{{ route('sales-reps.commissions.pay', $comm) }}" class="inline" onsubmit="return confirm('تأكيد تسجيل صرف العمولة بقيمة {{ number_format($comm->commission_amount, 2) }} ج.م للمندوب؟');">
                                            @csrf
                                            <button type="submit" class="erp-btn erp-btn-sm erp-btn-primary text-[11px] py-1 px-2.5">
                                                <x-icon name="check" size="12" />
                                                <span>صرف العمولة</span>
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-xs text-emerald-700 font-bold flex items-center justify-center gap-1">
                                            <x-icon name="check-circle" size="13" />
                                            <span>مدفوعة</span>
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-10 text-[var(--text-muted)] text-xs">
                                    لا توجد عمولات مسجلة لهذا المندوب في الفترة المحددة.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($commissions->hasPages())
                <div class="p-3 border-t border-[var(--border-light)]">
                    {{ $commissions->links() }}
                </div>
            @endif
        @endif
    </div>

</div>
@endsection
