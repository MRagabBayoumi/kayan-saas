@extends('layouts.app')

@section('control_panel')
    <x-control-panel title="الميزانية العمومية والمركز المالي (Balance Sheet)" :breadcrumbs="['المالية والحسابات' => route('treasuries.index'), 'شجرة الحسابات' => route('accounting.chart-of-accounts'), 'الميزانية العمومية' => route('accounting.reports.balance-sheet')]">
        <x-slot:actions>
            <a href="{{ route('accounting.reports.balance-sheet.print', request()->query()) }}" target="_blank" class="erp-btn erp-btn-secondary">
                <x-icon name="printer" size="16" />
                <span>طباعة الميزانية</span>
            </a>
            <a href="{{ route('accounting.reports.income-statement') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="trending-up" size="16" />
                <span>قائمة الدخل</span>
            </a>
        </x-slot:actions>

        <x-slot:extra>
            @if($report['is_balanced'])
                <span class="erp-badge erp-badge-success font-numeric px-3 py-1 text-xs">
                    <x-icon name="check-circle" size="13" />
                    <span class="mr-1">الميزانية متوازنة ومطابقة تماماً (0.00 ج.م)</span>
                </span>
            @else
                <span class="erp-badge erp-badge-danger font-numeric px-3 py-1 text-xs">
                    <x-icon name="alert-triangle" size="13" />
                    <span class="mr-1">يوجد فارق غير متوازن: {{ number_format($report['diff'], 2) }} ج.م</span>
                </span>
            @endif
        </x-slot:extra>
    </x-control-panel>
@endsection

@section('content')
    <div data-page="balance-sheet" class="space-y-6">
        @include('accounting.nav-tabs')

        <!-- KPI Summary Cards -->
        <div class="finance-kpi-grid">
            <div class="finance-kpi-card">
                <div class="finance-kpi-icon info">
                    <x-icon name="layers" size="22" />
                </div>
                <div class="finance-kpi-info">
                    <div class="finance-kpi-label">إجمالي الأصول (Assets)</div>
                    <div class="finance-kpi-value font-numeric text-[var(--info-text)]">
                        {{ number_format($report['total_assets'], 2) }} <span class="text-xs font-normal text-[var(--text-muted)]">ج.م</span>
                    </div>
                    <div class="finance-kpi-sub">أصول متداولة: {{ number_format($report['total_current_assets'], 2) }} ج.م</div>
                </div>
            </div>

            <div class="finance-kpi-card">
                <div class="finance-kpi-icon warning">
                    <x-icon name="credit-card" size="22" />
                </div>
                <div class="finance-kpi-info">
                    <div class="finance-kpi-label">إجمالي الخصوم والالتزامات</div>
                    <div class="finance-kpi-value font-numeric text-[var(--warning-text)]">
                        {{ number_format($report['total_liabilities'], 2) }} <span class="text-xs font-normal text-[var(--text-muted)]">ج.م</span>
                    </div>
                    <div class="finance-kpi-sub">مستحقات الموردين والالتزامات</div>
                </div>
            </div>

            <div class="finance-kpi-card">
                <div class="finance-kpi-icon primary">
                    <x-icon name="shield" size="22" />
                </div>
                <div class="finance-kpi-info">
                    <div class="finance-kpi-label">إجمالي حقوق الملكية (Equity)</div>
                    <div class="finance-kpi-value font-numeric text-[var(--primary)]">
                        {{ number_format($report['total_equity'], 2) }} <span class="text-xs font-normal text-[var(--text-muted)]">ج.م</span>
                    </div>
                    <div class="finance-kpi-sub">تشمل ربح الفترة: {{ number_format($report['current_period_net_profit'], 2) }} ج.م</div>
                </div>
            </div>

            <div class="finance-kpi-card">
                <div class="finance-kpi-icon {{ $report['is_balanced'] ? 'success' : 'danger' }}">
                    <x-icon name="{{ $report['is_balanced'] ? 'check-circle' : 'alert-octagon' }}" size="22" />
                </div>
                <div class="finance-kpi-info">
                    <div class="finance-kpi-label">توازن المركز المالي</div>
                    <div class="finance-kpi-value font-numeric {{ $report['is_balanced'] ? 'text-[var(--success-text)]' : 'text-[var(--danger)]' }}">
                        {{ $report['is_balanced'] ? 'الأصول = الخصوم + الملكية' : 'فارق: '.number_format($report['diff'], 2).' ج.م' }}
                    </div>
                    <div class="finance-kpi-sub">
                        {{ $report['is_balanced'] ? 'المعادلة متطابقة 100%' : 'تتطلب مراجعة قيود التسوية' }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Date Filter Toolbar -->
        <div class="finance-filter-bar">
            <form method="GET" action="{{ route('accounting.reports.balance-sheet') }}" class="flex flex-wrap items-end gap-3">
                <div class="w-full sm:w-56">
                    <label class="block text-xs font-semibold mb-1 text-[var(--text-muted)]">حتى تاريخ (As of Date)</label>
                    <input type="date" name="as_of_date" value="{{ $currentAsOfDate }}" class="finance-filter-input w-full font-numeric text-xs">
                </div>

                <div class="flex items-center gap-2">
                    <button type="submit" class="erp-btn erp-btn-primary text-xs px-4">
                        <x-icon name="filter" size="14" />
                        <span>تحديث الميزانية</span>
                    </button>
                    <a href="{{ route('accounting.reports.balance-sheet') }}" class="erp-btn erp-btn-secondary text-xs px-3" title="اليوم">
                        <x-icon name="calendar" size="14" />
                        <span>اليوم الحالي</span>
                    </a>
                </div>
            </form>
        </div>

        <!-- Accounting Equation Validation Banner -->
        <div class="p-4 rounded-xl border {{ $report['is_balanced'] ? 'bg-[var(--success-soft)] border-[var(--success-border)]' : 'bg-[var(--danger-soft)] border-[var(--danger-border)]' }}">
            <div class="flex flex-col md:flex-row items-center justify-between gap-3 text-xs">
                <div class="flex items-center gap-2.5">
                    <span class="w-7 h-7 rounded-full {{ $report['is_balanced'] ? 'bg-[var(--success)] text-white' : 'bg-[var(--danger)] text-white' }} flex items-center justify-center flex-shrink-0">
                        <x-icon name="{{ $report['is_balanced'] ? 'check' : 'alert-triangle' }}" size="15" />
                    </span>
                    <div>
                        <span class="font-extrabold text-sm {{ $report['is_balanced'] ? 'text-[var(--success-text)]' : 'text-[var(--danger)]' }}">
                            {{ $report['is_balanced'] ? 'المعادلة المحاسبية متطابقة تماماً (الأصول = الخصوم + حقوق الملكية)' : 'تنبيه: المعادلة المحاسبية غير متوازنة ويوجد فارق دفتري!' }}
                        </span>
                        <div class="text-[11px] text-[var(--text-muted)] mt-0.5">
                            {{ $report['is_balanced'] ? 'الميزانية متوازنة ومطابقة تماماً' : 'يوجد فارق غير متوازن: '.number_format($report['diff'], 2).' ج.م' }}
                        </div>
                    </div>
                </div>

                <div class="font-numeric text-xs font-bold px-3 py-1.5 rounded-lg bg-[var(--bg-surface)] border border-[var(--border-color)] text-[var(--text-main)] flex items-center gap-2">
                    <span class="text-[var(--info-text)]">الأصول: {{ number_format($report['total_assets'], 2) }}</span>
                    <span class="text-[var(--text-muted)]">=</span>
                    <span class="text-[var(--warning-text)]">الخصوم: {{ number_format($report['total_liabilities'], 2) }}</span>
                    <span class="text-[var(--text-muted)]">+</span>
                    <span class="text-[var(--primary)]">الملكية: {{ number_format($report['total_equity'], 2) }}</span>
                </div>
            </div>
        </div>

        <!-- Balance Sheet Two-Column Layout (Side-by-Side Financial Tables) -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 items-start">
            
            <!-- ============================================================= -->
            <!-- RIGHT COLUMN: ASSETS (الأصول)                                 -->
            <!-- ============================================================= -->
            <div class="erp-card overflow-hidden shadow-sm border border-[var(--border-color)]">
                <!-- Column Header -->
                <div class="p-4 bg-[var(--info-soft)] border-b border-[var(--border-color)] flex justify-between items-center">
                    <div class="font-bold text-sm text-[var(--info-text)] flex items-center gap-2">
                        <span class="w-6 h-6 rounded bg-[var(--info)] text-white flex items-center justify-center">
                            <x-icon name="briefcase" size="14" />
                        </span>
                        <span>جانب الأصول (Assets)</span>
                    </div>
                    <span class="text-xs font-numeric font-extrabold text-[var(--info-text)] px-2.5 py-1 rounded bg-[var(--bg-surface)] border border-[var(--info-border)]">
                        {{ number_format($report['total_assets'], 2) }} ج.م
                    </span>
                </div>

                <!-- Assets Table -->
                <div class="erp-table-responsive">
                    <table class="erp-table w-full text-xs">
                        <thead>
                            <tr class="bg-[var(--bg-surface-alt)] border-b border-[var(--border-color)] text-[var(--text-muted)] font-bold">
                                <th class="w-24 text-right p-3">الكود</th>
                                <th class="text-right p-3">اسم الحساب / البند</th>
                                <th class="w-36 text-left font-numeric p-3">الرصيد (ج.م)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[var(--border-light)]">
                            
                            <!-- Group 1: Current Assets -->
                            <tr class="bg-[var(--bg-surface-alt)] font-bold">
                                <td colspan="2" class="p-2.5 text-[var(--text-main)] text-xs">
                                    <div class="flex items-center gap-1.5">
                                        <x-icon name="folder" size="14" class="text-[var(--info)]" />
                                        <span>أولاً: الأصول المتداولة (Current Assets)</span>
                                    </div>
                                </td>
                                <td class="p-2.5 text-left font-numeric text-[var(--info-text)] font-extrabold">
                                    {{ number_format($report['total_current_assets'], 2) }}
                                </td>
                            </tr>

                            @forelse($report['current_assets'] as $asset)
                                @php
                                    $bal = (float)$asset['balance'];
                                    $isNegative = $bal < -0.001;
                                @endphp
                                <tr class="erp-table-row hover:bg-[var(--bg-hover)]">
                                    <td class="font-numeric p-2.5">
                                        <span class="inline-block font-mono text-[11px] px-1.5 py-0.5 rounded bg-[var(--bg-surface-alt)] border border-[var(--border-color)] text-[var(--text-muted)]">
                                            {{ $asset['code'] }}
                                        </span>
                                    </td>
                                    <td class="p-2.5 pr-6">
                                        <a href="{{ route('accounting.accounts.ledger', $asset['id']) }}" class="text-[var(--text-main)] hover:text-[var(--primary)] font-medium transition-colors">
                                            {{ $asset['name'] }}
                                        </a>
                                    </td>
                                    <td class="p-2.5 text-left font-numeric font-semibold {{ $isNegative ? 'text-[var(--danger)]' : 'text-[var(--text-main)]' }}">
                                        @if($isNegative)
                                            ({{ number_format(abs($bal), 2) }})
                                        @else
                                            {{ number_format($bal, 2) }}
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="p-3 text-center text-[var(--text-muted)] italic">
                                        لا توجد أصول متداولة مسجلة.
                                    </td>
                                </tr>
                            @endforelse

                            <!-- Group 2: Fixed & Non-Current Assets -->
                            <tr class="bg-[var(--bg-surface-alt)] font-bold border-t border-[var(--border-color)]">
                                <td colspan="2" class="p-2.5 text-[var(--text-main)] text-xs">
                                    <div class="flex items-center gap-1.5">
                                        <x-icon name="folder" size="14" class="text-[var(--text-muted)]" />
                                        <span>ثانياً: الأصول الثابتة وغير المتداولة (Fixed Assets)</span>
                                    </div>
                                </td>
                                <td class="p-2.5 text-left font-numeric text-[var(--text-main)] font-extrabold">
                                    {{ number_format($report['total_non_current_assets'], 2) }}
                                </td>
                            </tr>

                            @forelse($report['non_current_assets'] as $asset)
                                @php
                                    $bal = (float)$asset['balance'];
                                    $isNegative = $bal < -0.001;
                                @endphp
                                <tr class="erp-table-row hover:bg-[var(--bg-hover)]">
                                    <td class="font-numeric p-2.5">
                                        <span class="inline-block font-mono text-[11px] px-1.5 py-0.5 rounded bg-[var(--bg-surface-alt)] border border-[var(--border-color)] text-[var(--text-muted)]">
                                            {{ $asset['code'] }}
                                        </span>
                                    </td>
                                    <td class="p-2.5 pr-6">
                                        <a href="{{ route('accounting.accounts.ledger', $asset['id']) }}" class="text-[var(--text-main)] hover:text-[var(--primary)] font-medium transition-colors">
                                            {{ $asset['name'] }}
                                        </a>
                                    </td>
                                    <td class="p-2.5 text-left font-numeric font-semibold {{ $isNegative ? 'text-[var(--danger)]' : 'text-[var(--text-main)]' }}">
                                        @if($isNegative)
                                            ({{ number_format(abs($bal), 2) }})
                                        @else
                                            {{ number_format($bal, 2) }}
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="p-3 text-center text-[var(--text-muted)] italic">
                                        لا توجد أصول ثابتة مسجلة حالياً.
                                    </td>
                                </tr>
                            @endforelse

                        </tbody>
                        
                        <!-- Assets Footer -->
                        <tfoot>
                            <tr class="border-t-2 border-[var(--info)] bg-[var(--info-soft)] font-black text-sm">
                                <td colspan="2" class="p-4 text-[var(--info-text)]">
                                    إجمالي الأصول (Total Assets):
                                </td>
                                <td class="p-4 text-left font-numeric text-base text-[var(--info-text)]">
                                    {{ number_format($report['total_assets'], 2) }} ج.م
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- ============================================================= -->
            <!-- LEFT COLUMN: LIABILITIES & EQUITY (الخصوم وحقوق الملكية)       -->
            <!-- ============================================================= -->
            <div class="erp-card overflow-hidden shadow-sm border border-[var(--border-color)]">
                <!-- Column Header -->
                <div class="p-4 bg-[var(--warning-soft)] border-b border-[var(--border-color)] flex justify-between items-center">
                    <div class="font-bold text-sm text-[var(--warning-text)] flex items-center gap-2">
                        <span class="w-6 h-6 rounded bg-[var(--warning)] text-white flex items-center justify-center">
                            <x-icon name="shield" size="14" />
                        </span>
                        <span>جانب الخصوم وحقوق الملكية (Liabilities & Equity)</span>
                    </div>
                    <span class="text-xs font-numeric font-extrabold text-[var(--warning-text)] px-2.5 py-1 rounded bg-[var(--bg-surface)] border border-[var(--warning-border)]">
                        {{ number_format($report['total_liabilities_and_equity'], 2) }} ج.م
                    </span>
                </div>

                <!-- Liabilities & Equity Table -->
                <div class="erp-table-responsive">
                    <table class="erp-table w-full text-xs">
                        <thead>
                            <tr class="bg-[var(--bg-surface-alt)] border-b border-[var(--border-color)] text-[var(--text-muted)] font-bold">
                                <th class="w-24 text-right p-3">الكود</th>
                                <th class="text-right p-3">اسم الحساب / البند</th>
                                <th class="w-36 text-left font-numeric p-3">الرصيد (ج.م)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[var(--border-light)]">
                            
                            <!-- Group 1: Current Liabilities -->
                            <tr class="bg-[var(--bg-surface-alt)] font-bold">
                                <td colspan="2" class="p-2.5 text-[var(--text-main)] text-xs">
                                    <div class="flex items-center gap-1.5">
                                        <x-icon name="folder" size="14" class="text-[var(--danger)]" />
                                        <span>أولاً: الخصوم المتداولة (Current Liabilities)</span>
                                    </div>
                                </td>
                                <td class="p-2.5 text-left font-numeric text-[var(--danger)] font-extrabold">
                                    {{ number_format($report['total_current_liabilities'], 2) }}
                                </td>
                            </tr>

                            @forelse($report['current_liabilities'] as $liab)
                                @php
                                    $bal = (float)$liab['balance'];
                                    $isNegative = $bal < -0.001;
                                @endphp
                                <tr class="erp-table-row hover:bg-[var(--bg-hover)]">
                                    <td class="font-numeric p-2.5">
                                        <span class="inline-block font-mono text-[11px] px-1.5 py-0.5 rounded bg-[var(--bg-surface-alt)] border border-[var(--border-color)] text-[var(--text-muted)]">
                                            {{ $liab['code'] }}
                                        </span>
                                    </td>
                                    <td class="p-2.5 pr-6">
                                        <a href="{{ route('accounting.accounts.ledger', $liab['id']) }}" class="text-[var(--text-main)] hover:text-[var(--primary)] font-medium transition-colors">
                                            {{ $liab['name'] }}
                                        </a>
                                    </td>
                                    <td class="p-2.5 text-left font-numeric font-semibold {{ $isNegative ? 'text-[var(--danger)]' : 'text-[var(--text-main)]' }}">
                                        @if($isNegative)
                                            ({{ number_format(abs($bal), 2) }})
                                        @else
                                            {{ number_format($bal, 2) }}
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="p-3 text-center text-[var(--text-muted)] italic">
                                        لا توجد التزامات متداولة.
                                    </td>
                                </tr>
                            @endforelse

                            <!-- Group 2: Non-Current Liabilities (if any) -->
                            @if($report['total_non_current_liabilities'] > 0)
                                <tr class="bg-[var(--bg-surface-alt)] font-bold border-t border-[var(--border-color)]">
                                    <td colspan="2" class="p-2.5 text-[var(--text-main)] text-xs">
                                        <div class="flex items-center gap-1.5">
                                            <x-icon name="folder" size="14" class="text-[var(--text-muted)]" />
                                            <span>ثانياً: الخصوم طويلة الأجل</span>
                                        </div>
                                    </td>
                                    <td class="p-2.5 text-left font-numeric text-[var(--danger)] font-extrabold">
                                        {{ number_format($report['total_non_current_liabilities'], 2) }}
                                    </td>
                                </tr>

                                @foreach($report['non_current_liabilities'] as $liab)
                                    @php
                                        $bal = (float)$liab['balance'];
                                        $isNegative = $bal < -0.001;
                                    @endphp
                                    <tr class="erp-table-row hover:bg-[var(--bg-hover)]">
                                        <td class="font-numeric p-2.5">
                                            <span class="inline-block font-mono text-[11px] px-1.5 py-0.5 rounded bg-[var(--bg-surface-alt)] border border-[var(--border-color)] text-[var(--text-muted)]">
                                                {{ $liab['code'] }}
                                            </span>
                                        </td>
                                        <td class="p-2.5 pr-6">
                                            <a href="{{ route('accounting.accounts.ledger', $liab['id']) }}" class="text-[var(--text-main)] hover:text-[var(--primary)] font-medium transition-colors">
                                                {{ $liab['name'] }}
                                            </a>
                                        </td>
                                        <td class="p-2.5 text-left font-numeric font-semibold {{ $isNegative ? 'text-[var(--danger)]' : 'text-[var(--text-main)]' }}">
                                            @if($isNegative)
                                                ({{ number_format(abs($bal), 2) }})
                                            @else
                                                {{ number_format($bal, 2) }}
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            @endif

                            <!-- Group 3: Owners' Equity -->
                            <tr class="bg-[var(--primary-soft)]/50 font-bold border-t border-[var(--border-color)]">
                                <td colspan="2" class="p-2.5 text-[var(--primary)] text-xs">
                                    <div class="flex items-center gap-1.5">
                                        <x-icon name="shield" size="14" class="text-[var(--primary)]" />
                                        <span>ثالثاً: حقوق الملكية (Owners' Equity)</span>
                                    </div>
                                </td>
                                <td class="p-2.5 text-left font-numeric text-[var(--primary)] font-extrabold">
                                    {{ number_format($report['total_equity'], 2) }}
                                </td>
                            </tr>

                            @foreach($report['equity_items'] as $eq)
                                @php
                                    $bal = (float)$eq['balance'];
                                    $isNegative = $bal < -0.001;
                                @endphp
                                <tr class="erp-table-row hover:bg-[var(--bg-hover)]">
                                    <td class="font-numeric p-2.5">
                                        <span class="inline-block font-mono text-[11px] px-1.5 py-0.5 rounded bg-[var(--bg-surface-alt)] border border-[var(--border-color)] text-[var(--text-muted)]">
                                            {{ $eq['code'] }}
                                        </span>
                                    </td>
                                    <td class="p-2.5 pr-6">
                                        <a href="{{ route('accounting.accounts.ledger', $eq['id']) }}" class="text-[var(--text-main)] hover:text-[var(--primary)] font-medium transition-colors">
                                            {{ $eq['name'] }}
                                        </a>
                                    </td>
                                    <td class="p-2.5 text-left font-numeric font-semibold {{ $isNegative ? 'text-[var(--danger)]' : 'text-[var(--text-main)]' }}">
                                        @if($isNegative)
                                            ({{ number_format(abs($bal), 2) }})
                                        @else
                                            {{ number_format($bal, 2) }}
                                        @endif
                                    </td>
                                </tr>
                            @endforeach

                            <!-- Current Period Net Profit line from Income Statement -->
                            <tr class="bg-[var(--success-soft)] font-bold border-t border-b border-[var(--success-border)]">
                                <td class="font-numeric p-2.5">
                                    <span class="inline-block font-mono text-[11px] px-1.5 py-0.5 rounded bg-[var(--success)] text-white font-bold">
                                        P&L
                                    </span>
                                </td>
                                <td class="p-2.5 pr-6">
                                    <a href="{{ route('accounting.reports.income-statement') }}" class="text-[var(--success-text)] hover:underline font-extrabold flex items-center gap-1.5">
                                        <span>صافي أرباح الفترة الحالية (من قائمة الدخل)</span>
                                        <x-icon name="external-link" size="12" />
                                    </a>
                                </td>
                                <td class="p-2.5 text-left font-numeric font-black text-sm text-[var(--success-text)]">
                                    {{ number_format($report['current_period_net_profit'], 2) }}
                                </td>
                            </tr>

                        </tbody>
                        
                        <!-- Liabilities & Equity Footer -->
                        <tfoot>
                            <tr class="border-t-2 border-[var(--primary)] bg-[var(--primary-soft)] font-black text-sm">
                                <td colspan="2" class="p-4 text-[var(--primary)]">
                                    إجمالي الخصوم وحقوق الملكية:
                                </td>
                                <td class="p-4 text-left font-numeric text-base text-[var(--primary)]">
                                    {{ number_format($report['total_liabilities_and_equity'], 2) }} ج.م
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

        </div>

    </div>
@endsection
