<!-- Kayan ERP — Unified Accounting & Financial Management Tabs Navigation -->
<div class="erp-card erp-tab-nav-card mb-4">
    <nav class="erp-tab-bar" aria-label="أقسام الإدارة المالية والمحاسبية">
        <!-- 1. Chart of Accounts -->
        <a href="{{ route('accounting.chart-of-accounts') }}" 
           class="erp-tab-link {{ request()->routeIs('accounting.chart-of-accounts') ? 'active' : '' }}">
            <x-icon name="list" size="16" />
            <span>شجرة الحسابات</span>
        </a>

        <!-- 2. General Journal Entries -->
        <a href="{{ route('accounting.journal-entries') }}" 
           class="erp-tab-link {{ request()->routeIs('accounting.journal-entries*') ? 'active' : '' }}">
            <x-icon name="file-text" size="16" />
            <span>دفتر قيود اليومية</span>
        </a>

        <!-- 3. Trial Balance -->
        <a href="{{ route('accounting.reports.trial-balance') }}" 
           class="erp-tab-link {{ request()->routeIs('accounting.reports.trial-balance*') ? 'active' : '' }}">
            <x-icon name="layers" size="16" />
            <span>ميزان المراجعة</span>
        </a>

        <!-- 4. Income Statement (P&L) -->
        <a href="{{ route('accounting.reports.income-statement') }}" 
           class="erp-tab-link {{ request()->routeIs('accounting.reports.income-statement*') ? 'active' : '' }}">
            <x-icon name="trending-up" size="16" />
            <span>قائمة الدخل والأرباح</span>
        </a>

        <!-- 5. Balance Sheet -->
        <a href="{{ route('accounting.reports.balance-sheet') }}" 
           class="erp-tab-link {{ request()->routeIs('accounting.reports.balance-sheet*') ? 'active' : '' }}">
            <x-icon name="pie-chart" size="16" />
            <span>الميزانية العمومية والمركز المالي</span>
        </a>
    </nav>
</div>
