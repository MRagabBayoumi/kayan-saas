@extends('layouts.app')

@section('control_panel')
    <x-control-panel title="سجل وتقارير الزيارات الميدانية لمناديب المبيعات" :breadcrumbs="[
        'المبيعات' => route('sales.index'),
        'مناديب المبيعات' => route('sales-reps.index'),
        'سجل الزيارات' => '#'
    ]">
        <x-slot:actions>
            <a href="{{ route('sales-reps.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="arrow-right" size="16" />
                <span>رجوع للمناديب</span>
            </a>
            <a href="{{ route('sales-reps.customer-requests.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="user-check" size="16" />
                <span>طلبات اعتماد العملاء</span>
            </a>
            <a href="{{ route('sales-reps.attendance.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="clock" size="16" />
                <span>سجل الحضور والغياب</span>
            </a>
        </x-slot:actions>

        <x-slot:extra>
            <span class="erp-badge erp-badge-primary font-numeric px-2.5 py-1 text-xs">
                <span>زيارات اليوم:</span>
                <strong>{{ $todayVisitsCount }}</strong>
                <span>(طلبات محققة: {{ $todayOrdersPlacedCount }})</span>
            </span>
        </x-slot:extra>
    </x-control-panel>
@endsection

@section('content')
<div class="space-y-4">

    <!-- Enterprise Sub-Navigation Tabs -->
    <x-sales-reps-nav-tabs active="visits" />

    <!-- Quick Metrics Cards (Faithful to Kayan ERP Design System) -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-4">
        <div class="kpi-card flex flex-col justify-between mb-0">
            <div class="kpi-header mb-1">
                <span class="kpi-label">إجمالي زيارات اليوم</span>
                <span class="kpi-icon text-sky-600"><x-icon name="map-pin" size="18" /></span>
            </div>
            <div class="kpi-value font-numeric text-xl font-black text-[var(--text-main)]">
                {{ $todayVisitsCount }} <span class="text-xs font-normal text-[var(--text-muted)]">زيارة</span>
            </div>
            <div class="kpi-note text-xs text-[var(--text-muted)]">
                <span>زيارات ميدانية موثقة اليوم</span>
            </div>
        </div>

        <div class="kpi-card flex flex-col justify-between mb-0 border-emerald-200 bg-emerald-50/40">
            <div class="kpi-header mb-1">
                <span class="kpi-label text-emerald-900 font-bold">طلبات بيع محققة من الزيارات</span>
                <span class="kpi-icon text-emerald-600"><x-icon name="shopping-bag" size="18" /></span>
            </div>
            <div class="kpi-value font-numeric text-xl font-black text-emerald-800">
                {{ $todayOrdersPlacedCount }} <span class="text-xs font-bold text-emerald-700">طلب</span>
            </div>
            <div class="kpi-note text-emerald-700 text-xs font-medium">
                <span>فواتير ناتجة عن زيارات</span>
            </div>
        </div>

        <div class="kpi-card flex flex-col justify-between mb-0 border-amber-200 bg-amber-50/40">
            <div class="kpi-header mb-1">
                <span class="kpi-label text-amber-900 font-bold">زيارات قيد التنفيذ الآن</span>
                <span class="kpi-icon text-amber-600"><x-icon name="activity" size="18" /></span>
            </div>
            <div class="kpi-value font-numeric text-xl font-black text-amber-800">
                {{ $activeVisitsCount }} <span class="text-xs font-bold text-amber-700">زيارة</span>
            </div>
            <div class="kpi-note text-amber-700 text-xs font-medium">
                <span>المندوب متواجد بالموقع الآن</span>
            </div>
        </div>
    </div>

    <!-- Filters Header -->
    <div class="erp-card mb-0 p-4">
        <form method="GET" action="{{ route('sales-reps.visits.index') }}" class="grid grid-cols-1 sm:grid-cols-5 gap-3 items-end">
            <div>
                <label class="erp-form-label font-bold text-xs mb-1">التاريخ</label>
                <input type="date" name="date" value="{{ $selectedDate === 'all' ? '' : $selectedDate }}" class="erp-input text-xs">
            </div>

            <div>
                <label class="erp-form-label font-bold text-xs mb-1">المندوب</label>
                <select name="sales_rep_id" class="erp-input text-xs">
                    <option value="">كل المناديب</option>
                    @foreach($salesReps as $rep)
                        <option value="{{ $rep->id }}" {{ (string)request('sales_rep_id') === (string)$rep->id ? 'selected' : '' }}>
                            {{ $rep->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="erp-form-label font-bold text-xs mb-1">نتيجة المقابلة</label>
                <select name="outcome" class="erp-input text-xs">
                    <option value="">كل النتائج</option>
                    @foreach(\App\Models\SalesRepVisit::OUTCOMES as $k => $v)
                        <option value="{{ $k }}" {{ request('outcome') === $k ? 'selected' : '' }}>{{ $v }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="erp-form-label font-bold text-xs mb-1">بحث بالعميل أو الهاتف</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="اسم العميل..." class="erp-input text-xs">
            </div>

            <div class="flex items-center gap-1.5">
                <button type="submit" class="erp-btn erp-btn-primary flex-1 text-xs justify-center py-2">
                    <x-icon name="filter" size="13" />
                    <span>فلترة</span>
                </button>
                <a href="{{ route('sales-reps.visits.index') }}" class="erp-btn erp-btn-secondary text-xs py-2">
                    اليوم
                </a>
                <a href="{{ route('sales-reps.visits.index', ['date' => 'all']) }}" class="erp-btn erp-btn-secondary text-xs py-2">
                    الكل
                </a>
            </div>
        </form>
    </div>

    <!-- Visits Table -->
    <div class="erp-card mb-0 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="erp-table w-full text-xs">
                <thead>
                    <tr>
                        <th class="w-12 text-center">#</th>
                        <th>المندوب</th>
                        <th>العميل</th>
                        <th>التاريخ والوقت</th>
                        <th>مدة المقابلة</th>
                        <th>الغرض</th>
                        <th>نتيجة الزيارة</th>
                        <th>موقع الـ GPS</th>
                        <th>الصورة والتقرير</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[var(--border-light)]">
                    @forelse($visits as $visit)
                        <tr class="hover:bg-[var(--bg-hover)] transition-colors">
                            <td class="text-center font-numeric text-[var(--text-muted)]">{{ $visit->id }}</td>
                            <td>
                                @if($visit->salesRep)
                                    <div class="font-bold text-[var(--text-main)]">{{ $visit->salesRep->name }}</div>
                                    <div class="text-[10px] text-[var(--text-muted)] font-numeric">{{ $visit->salesRep->phone }}</div>
                                @else
                                    <span class="text-[var(--text-muted)]">-</span>
                                @endif
                            </td>
                            <td>
                                <div class="font-bold text-[var(--text-main)] text-sm">{{ $visit->customer->name }}</div>
                                <div class="text-[10px] text-[var(--text-muted)] font-numeric">{{ $visit->customer->phone }}</div>
                                @if($visit->customer->city)
                                    <div class="text-[10px] text-[var(--text-secondary)]">{{ $visit->customer->city }}</div>
                                @endif
                            </td>
                            <td class="font-numeric text-[var(--text-secondary)] text-[11px]">
                                {{ $visit->visit_date->format('Y-m-d') }}
                                <div class="text-[10px] text-[var(--text-muted)]">
                                    وصول: {{ $visit->check_in_at->format('h:i A') }}
                                    @if($visit->check_out_at)
                                        | مغادرة: {{ $visit->check_out_at->format('h:i A') }}
                                    @endif
                                </div>
                            </td>
                            <td class="font-numeric font-bold text-emerald-700">
                                @if($visit->duration_minutes)
                                    {{ $visit->duration_minutes }} دقيقة
                                @elseif(! $visit->check_out_at)
                                    <span class="text-amber-600 animate-pulse font-normal">جارية حالياً</span>
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                <span class="px-2 py-0.5 rounded text-[10px] bg-slate-100 text-slate-700 border border-slate-200">
                                    {{ $visit->purpose_label }}
                                </span>
                            </td>
                            <td>
                                @if(! $visit->check_out_at)
                                    <span class="erp-badge erp-badge-warning text-[10px]">قيد التنفيذ</span>
                                @elseif($visit->outcome === 'order_placed')
                                    <span class="erp-badge erp-badge-success text-[10px]">تم إنشاء طلب</span>
                                    @if($visit->invoice)
                                        <div class="text-[10px] text-emerald-700 font-numeric mt-0.5">
                                            فاتورة: #{{ $visit->invoice->invoice_number }}
                                        </div>
                                    @endif
                                @elseif($visit->outcome === 'payment_collected')
                                    <span class="erp-badge erp-badge-primary text-[10px]">تم التحصيل</span>
                                @else
                                    <span class="erp-badge erp-badge-neutral text-[10px]">{{ $visit->outcome_label }}</span>
                                @endif
                            </td>
                            <td>
                                @if($visit->check_in_latitude && $visit->check_in_longitude)
                                    <a href="https://www.google.com/maps/search/?api=1&query={{ $visit->check_in_latitude }},{{ $visit->check_in_longitude }}" target="_blank"
                                        class="inline-flex items-center gap-1 text-[11px] text-sky-600 hover:text-sky-800 bg-sky-50 px-2 py-0.5 rounded border border-sky-200">
                                        <x-icon name="map-pin" size="11" class="text-rose-500" />
                                        <span>عرض الموقع</span>
                                    </a>
                                @else
                                    <span class="text-[var(--text-muted)]">-</span>
                                @endif
                            </td>
                            <td>
                                <div class="flex items-center gap-2">
                                    @if($visit->visit_image_path)
                                        <a href="{{ $visit->image_url }}" target="_blank" class="w-8 h-8 rounded border border-[var(--border-color)] overflow-hidden shrink-0 bg-slate-100 hover:opacity-80">
                                            <img src="{{ $visit->image_url }}" alt="صورة الزيارة" class="w-full h-full object-cover">
                                        </a>
                                    @endif
                                    @if($visit->notes)
                                        <div class="text-[11px] text-[var(--text-secondary)] line-clamp-2 max-w-xs" title="{{ $visit->notes }}">
                                            {{ $visit->notes }}
                                        </div>
                                    @else
                                        <span class="text-[var(--text-muted)]">-</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="p-8 text-center text-[var(--text-muted)]">
                                <x-icon name="inbox" size="32" class="text-slate-300 mx-auto mb-2" />
                                <div class="font-bold text-sm">لا توجد زيارات مسجلة لهذا التاريخ أو الفلتر</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($visits->hasPages())
            <div class="p-3 border-t border-[var(--border-light)]">
                {{ $visits->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
