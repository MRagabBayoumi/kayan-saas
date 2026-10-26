@props([
    'active' => 'reps', // 'reps', 'customer-requests', 'attendance', 'visits'
    'pendingRequestsCount' => null,
])

@php
    if ($pendingRequestsCount === null) {
        $pendingRequestsCount = \App\Models\Customer::whereNotNull('sales_rep_id')
            ->where('approval_status', \App\Models\Customer::APPROVAL_PENDING)
            ->count();
    }
@endphp

<!-- Enterprise Sub-Navigation Tabs Bar for Sales Reps Management (Faithful to Kayan ERP Design System) -->
<div class="erp-card erp-tab-nav-card mb-4">
    <nav class="erp-tab-bar" aria-label="أقسام إدارة المناديب">
        <!-- 1. Reps Team & Commissions -->
        <a href="{{ route('sales-reps.index') }}"
           class="erp-tab-link {{ $active === 'reps' ? 'active' : '' }}">
            <x-icon name="users" size="16" />
            <span>فريق المناديب والعمولات</span>
        </a>

        <!-- 2. Customer Approval Requests -->
        <a href="{{ route('sales-reps.customer-requests.index') }}"
           class="erp-tab-link {{ $active === 'customer-requests' ? 'active' : '' }}">
            <x-icon name="user-check" size="16" />
            <span>طلبات اعتماد العملاء</span>
            @if($pendingRequestsCount > 0)
                <span class="erp-badge erp-badge-warning mr-1.5 px-2 py-0.5 text-xs font-bold font-numeric">
                    {{ $pendingRequestsCount }}
                </span>
            @endif
        </a>

        <!-- 3. Field Attendance & Departure (GPS) -->
        <a href="{{ route('sales-reps.attendance.index') }}"
           class="erp-tab-link {{ $active === 'attendance' ? 'active' : '' }}">
            <x-icon name="clock" size="16" />
            <span>الحضور والانصراف (GPS)</span>
        </a>

        <!-- 4. Field Visits Tracker (GPS) -->
        <a href="{{ route('sales-reps.visits.index') }}"
           class="erp-tab-link {{ $active === 'visits' ? 'active' : '' }}">
            <x-icon name="map-pin" size="16" />
            <span>الزيارات الميدانية (GPS)</span>
        </a>
    </nav>
</div>
