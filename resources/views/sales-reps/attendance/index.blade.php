@extends('layouts.app')

@section('control_panel')
    <x-control-panel title="سجل ومتابعة حضور وانصراف المناديب الميداني (GPS)" :breadcrumbs="[
        'المبيعات' => route('sales.index'),
        'مناديب المبيعات' => route('sales-reps.index'),
        'الحضور والانصراف' => '#'
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
            <a href="{{ route('sales-reps.visits.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="map-pin" size="16" />
                <span>سجل الزيارات</span>
            </a>
        </x-slot:actions>

        <x-slot:extra>
            <span class="erp-badge erp-badge-success font-numeric px-2.5 py-1 text-xs">
                <span>حاضر اليوم:</span>
                <strong>{{ $presentTodayCount + $checkedOutTodayCount }}</strong>
                <span>من أصل {{ $activeRepsCount }}</span>
            </span>
        </x-slot:extra>
    </x-control-panel>
@endsection

@section('content')
<div class="space-y-4">

    <!-- Enterprise Sub-Navigation Tabs -->
    <x-sales-reps-nav-tabs active="attendance" />

    <!-- Quick Metrics Cards (Faithful to Kayan ERP Design System) -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-4">
        <div class="kpi-card flex flex-col justify-between mb-0">
            <div class="kpi-header mb-1">
                <span class="kpi-label">إجمالي المناديب النشطين</span>
                <span class="kpi-icon text-sky-600"><x-icon name="users" size="18" /></span>
            </div>
            <div class="kpi-value font-numeric text-xl font-black text-[var(--text-main)]">
                {{ $activeRepsCount }} <span class="text-xs font-normal text-[var(--text-muted)]">مندوب</span>
            </div>
            <div class="kpi-note text-xs text-[var(--text-muted)]">
                <span>مسجلين بالنظام الميداني</span>
            </div>
        </div>

        <div class="kpi-card flex flex-col justify-between mb-0 border-amber-200 bg-amber-50/40">
            <div class="kpi-header mb-1">
                <span class="kpi-label text-amber-900 font-bold">على رأس العمل الآن (الميدان)</span>
                <span class="kpi-icon text-amber-600"><x-icon name="activity" size="18" /></span>
            </div>
            <div class="kpi-value font-numeric text-xl font-black text-amber-800">
                {{ $presentTodayCount }} <span class="text-xs font-bold text-amber-700">مندوب</span>
            </div>
            <div class="kpi-note text-amber-700 text-xs font-medium">
                <span>جلسة دوام نشطة حالياً</span>
            </div>
        </div>

        <div class="kpi-card flex flex-col justify-between mb-0 border-emerald-200 bg-emerald-50/40">
            <div class="kpi-header mb-1">
                <span class="kpi-label text-emerald-900 font-bold">أتموا الانصراف اليوم</span>
                <span class="kpi-icon text-emerald-600"><x-icon name="check-circle" size="18" /></span>
            </div>
            <div class="kpi-value font-numeric text-xl font-black text-emerald-800">
                {{ $checkedOutTodayCount }} <span class="text-xs font-bold text-emerald-700">مندوب</span>
            </div>
            <div class="kpi-note text-emerald-700 text-xs font-medium">
                <span>انصراف موثق بنجاح</span>
            </div>
        </div>
    </div>

    <!-- Filters Header -->
    <div class="erp-card mb-0 p-4">
        <form method="GET" action="{{ route('sales-reps.attendance.index') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3 items-end">
            <div>
                <label class="erp-form-label font-bold text-xs mb-1">التاريخ</label>
                <input type="date" name="date" value="{{ $selectedDate === 'all' ? '' : $selectedDate }}" class="erp-input text-xs">
            </div>

            <div>
                <label class="erp-form-label font-bold text-xs mb-1">المندوب</label>
                <select name="sales_rep_id" class="erp-input text-xs">
                    <option value="">كل المناديب</option>
                    @foreach($salesReps as $rep)
                        <option value="{{ $rep->id }}" {{ (string)$selectedRep === (string)$rep->id ? 'selected' : '' }}>
                            {{ $rep->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="erp-form-label font-bold text-xs mb-1">حالة الدوام</label>
                <select name="status" class="erp-input text-xs">
                    <option value="">الكل</option>
                    <option value="present" {{ $selectedStatus === 'present' ? 'selected' : '' }}>على رأس العمل (حاضر)</option>
                    <option value="checked_out" {{ $selectedStatus === 'checked_out' ? 'selected' : '' }}>أتم الانصراف</option>
                </select>
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" class="erp-btn erp-btn-primary flex-1 text-xs justify-center py-2">
                    <x-icon name="filter" size="13" />
                    <span>تطبيق الفلتر</span>
                </button>
                <a href="{{ route('sales-reps.attendance.index') }}" class="erp-btn erp-btn-secondary text-xs py-2">
                    اليوم
                </a>
                <a href="{{ route('sales-reps.attendance.index', ['date' => 'all']) }}" class="erp-btn erp-btn-secondary text-xs py-2">
                    كل التواريخ
                </a>
            </div>
        </form>
    </div>

    <!-- Attendance Table -->
    <div class="erp-card mb-0 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="erp-table w-full text-xs">
                <thead>
                    <tr>
                        <th class="w-12 text-center">#</th>
                        <th>اسم المندوب</th>
                        <th>التاريخ</th>
                        <th>وقت الحضور</th>
                        <th>موقع الحضور (GPS)</th>
                        <th>وقت الانصراف</th>
                        <th>موقع الانصراف (GPS)</th>
                        <th>إجمالي وقت العمل</th>
                        <th class="text-center">الحالة</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[var(--border-light)]">
                    @forelse($attendances as $attendance)
                        <tr class="hover:bg-[var(--bg-hover)] transition-colors">
                            <td class="text-center font-numeric text-[var(--text-muted)]">{{ $attendance->id }}</td>
                            <td>
                                @if($attendance->salesRep)
                                    <div class="font-bold text-[var(--text-main)] text-sm">{{ $attendance->salesRep->name }}</div>
                                    <div class="text-[10px] text-[var(--text-muted)] font-numeric">{{ $attendance->salesRep->phone }}</div>
                                @else
                                    <span class="text-[var(--text-muted)]">-</span>
                                @endif
                            </td>
                            <td class="font-numeric text-[var(--text-main)]">
                                {{ $attendance->date->format('Y-m-d') }}
                                <div class="text-[10px] text-[var(--text-muted)]">{{ $attendance->date->translatedFormat('l') }}</div>
                            </td>
                            <td class="font-numeric text-[var(--text-secondary)]">
                                {{ $attendance->check_in_time ? $attendance->check_in_time->format('h:i A') : '-' }}
                            </td>
                            <td>
                                @if($attendance->check_in_latitude && $attendance->check_in_longitude)
                                    <a href="https://www.google.com/maps/search/?api=1&query={{ $attendance->check_in_latitude }},{{ $attendance->check_in_longitude }}" target="_blank"
                                        class="inline-flex items-center gap-1 text-[11px] text-sky-600 hover:text-sky-800 bg-sky-50 px-2 py-0.5 rounded border border-sky-200">
                                        <x-icon name="map-pin" size="11" class="text-rose-500" />
                                        <span>عرض الموقع</span>
                                    </a>
                                @else
                                    <span class="text-[var(--text-muted)]">-</span>
                                @endif
                            </td>
                            <td class="font-numeric text-[var(--text-secondary)]">
                                {{ $attendance->check_out_time ? $attendance->check_out_time->format('h:i A') : '-' }}
                            </td>
                            <td>
                                @if($attendance->check_out_latitude && $attendance->check_out_longitude)
                                    <a href="https://www.google.com/maps/search/?api=1&query={{ $attendance->check_out_latitude }},{{ $attendance->check_out_longitude }}" target="_blank"
                                        class="inline-flex items-center gap-1 text-[11px] text-sky-600 hover:text-sky-800 bg-sky-50 px-2 py-0.5 rounded border border-sky-200">
                                        <x-icon name="map-pin" size="11" class="text-rose-500" />
                                        <span>موقع الانصراف</span>
                                    </a>
                                @else
                                    <span class="text-[var(--text-muted)]">-</span>
                                @endif
                            </td>
                            <td class="font-numeric font-bold text-emerald-700">
                                {{ $attendance->formatted_work_duration }}
                            </td>
                            <td class="text-center">
                                @if($attendance->status === 'checked_out')
                                    <span class="erp-badge erp-badge-success text-[10px]">
                                        <x-icon name="check" size="10" />
                                        <span>انصراف مكتمل</span>
                                    </span>
                                @elseif($attendance->status === 'present')
                                    <span class="erp-badge erp-badge-warning text-[10px] animate-pulse">
                                        <x-icon name="clock" size="10" />
                                        <span>على رأس العمل</span>
                                    </span>
                                @else
                                    <span class="erp-badge erp-badge-neutral text-[10px]">{{ $attendance->status }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="p-8 text-center text-[var(--text-muted)]">
                                <x-icon name="calendar-x" size="32" class="text-slate-300 mx-auto mb-2" />
                                <div class="font-bold text-sm">لا توجد سجلات حضور مسجلة لهذا التاريخ أو الفلتر</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($attendances->hasPages())
            <div class="p-3 border-t border-[var(--border-light)]">
                {{ $attendances->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
