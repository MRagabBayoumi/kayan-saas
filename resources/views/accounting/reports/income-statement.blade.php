@extends('layouts.app')

@section('control_panel')
    <x-control-panel title="قائمة الدخل والأرباح والخسائر (Income Statement / P&L)" :breadcrumbs="['المالية والحسابات' => route('treasuries.index'), 'شجرة الحسابات' => route('accounting.chart-of-accounts'), 'قائمة الدخل' => route('accounting.reports.income-statement')]">
        <x-slot:actions>
            <a href="{{ route('accounting.reports.income-statement.print', request()->query()) }}" target="_blank" class="erp-btn erp-btn-secondary">
                <x-icon name="printer" size="16" />
                <span>طباعة التقرير</span>
            </a>
            <a href="{{ route('accounting.reports.balance-sheet') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="pie-chart" size="16" />
                <span>الميزانية العمومية</span>
            </a>
        </x-slot:actions>

        <x-slot:extra>
            <span class="erp-badge {{ $report['net_profit'] >= 0 ? 'erp-badge-success' : 'erp-badge-danger' }} font-numeric px-3 py-1 text-xs">
                <span>صافي الربح:</span>
                <strong class="mr-1">{{ number_format($report['net_profit'], 2) }} ج.م</strong>
            </span>
        </x-slot:extra>
    </x-control-panel>
@endsection

@section('content')
    <div data-page="income-statement" class="space-y-6">
        @include('accounting.nav-tabs')

        <!-- KPI Summary Cards -->
        <div class="finance-kpi-grid">
            <div class="finance-kpi-card">
                <div class="finance-kpi-icon info">
                    <x-icon name="trending-up" size="22" />
                </div>
                <div class="finance-kpi-info">
                    <div class="finance-kpi-label">صافي الإيرادات والمبيعات</div>
                    <div class="finance-kpi-value font-numeric text-[var(--info-text)]">
                        {{ number_format($report['net_revenue'], 2) }} <span class="text-xs font-normal text-[var(--text-muted)]">ج.م</span>
                    </div>
                    <div class="finance-kpi-sub">إجمالي المبيعات: {{ number_format($report['total_gross_sales'], 2) }} ج.م</div>
                </div>
            </div>

            <div class="finance-kpi-card">
                <div class="finance-kpi-icon warning">
                    <x-icon name="package" size="22" />
                </div>
                <div class="finance-kpi-info">
                    <div class="finance-kpi-label">تكلفة البضاعة المباعة (COGS)</div>
                    <div class="finance-kpi-value font-numeric text-[var(--warning-text)]">
                        {{ number_format($report['total_cogs'], 2) }} <span class="text-xs font-normal text-[var(--text-muted)]">ج.م</span>
                    </div>
                    <div class="finance-kpi-sub">تكلفة المخزون الفعلي المصروف (FIFO)</div>
                </div>
            </div>

            <div class="finance-kpi-card">
                <div class="finance-kpi-icon success">
                    <x-icon name="percent" size="22" />
                </div>
                <div class="finance-kpi-info">
                    <div class="finance-kpi-label">مجمل الربح التجاري (Gross Profit)</div>
                    <div class="finance-kpi-value font-numeric text-[var(--success-text)]">
                        {{ number_format($report['gross_profit'], 2) }} <span class="text-xs font-normal text-[var(--text-muted)]">ج.م</span>
                    </div>
                    <div class="finance-kpi-sub font-semibold text-[var(--success-text)]">هامش ربح: {{ $report['gross_margin_pct'] }}%</div>
                </div>
            </div>

            <div class="finance-kpi-card">
                <div class="finance-kpi-icon {{ $report['net_profit'] >= 0 ? 'success' : 'danger' }}">
                    <x-icon name="{{ $report['net_profit'] >= 0 ? 'award' : 'alert-octagon' }}" size="22" />
                </div>
                <div class="finance-kpi-info">
                    <div class="finance-kpi-label">صافي ربح النشاط النهائي</div>
                    <div class="finance-kpi-value font-numeric {{ $report['net_profit'] >= 0 ? 'text-[var(--success-text)]' : 'text-[var(--danger)]' }}">
                        {{ number_format($report['net_profit'], 2) }} <span class="text-xs font-normal text-[var(--text-muted)]">ج.م</span>
                    </div>
                    <div class="finance-kpi-sub">المصروفات: {{ number_format($report['total_operating_expenses'], 2) }} ج.م</div>
                </div>
            </div>
        </div>

        <!-- Filter Toolbar -->
        <div class="finance-filter-bar">
            <form method="GET" action="{{ route('accounting.reports.income-statement') }}" class="flex flex-wrap items-end gap-3">
                <div class="w-full sm:w-48">
                    <label class="block text-xs font-semibold mb-1 text-[var(--text-muted)]">من تاريخ</label>
                    <input type="date" name="date_from" value="{{ $currentDateFrom }}" class="finance-filter-input w-full font-numeric text-xs">
                </div>

                <div class="w-full sm:w-48">
                    <label class="block text-xs font-semibold mb-1 text-[var(--text-muted)]">إلى تاريخ</label>
                    <input type="date" name="date_to" value="{{ $currentDateTo }}" class="finance-filter-input w-full font-numeric text-xs">
                </div>

                <div class="flex items-center gap-2">
                    <button type="submit" class="erp-btn erp-btn-primary text-xs px-4">
                        <x-icon name="filter" size="14" />
                        <span>تحديث القائمة</span>
                    </button>
                    <a href="{{ route('accounting.reports.income-statement') }}" class="erp-btn erp-btn-secondary text-xs px-3" title="إعادة ضبط">
                        <x-icon name="rotate-ccw" size="14" />
                        <span>إعادة ضبط</span>
                    </a>
                </div>
            </form>
        </div>

        <!-- Professional Financial Statement Sheet (Centered & Structured) -->
        <div class="max-w-5xl mx-auto">
            <div class="erp-card overflow-hidden shadow-sm border border-[var(--border-color)]">
                
                <!-- Sheet Header / Letterhead -->
                <div class="p-6 bg-[var(--bg-surface-alt)] border-b border-[var(--border-color)]">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="w-8 h-8 rounded-lg bg-[var(--primary-soft)] text-[var(--primary)] flex items-center justify-center font-bold">
                                    <x-icon name="trending-up" size="18" />
                                </span>
                                <div>
                                    <h2 class="text-base font-extrabold text-[var(--text-main)]">
                                        قائمة الدخل والأرباح والخسائر
                                    </h2>
                                    <p class="text-xs text-[var(--text-muted)] mt-0.5">
                                        نظام كيان لإدارة الموارد &bull; الإدارة المالية والحسابات الختامية
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="text-left sm:text-right flex flex-col items-start sm:items-end text-xs">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded bg-[var(--bg-surface)] border border-[var(--border-color)] text-[var(--text-secondary)] font-medium">
                                <x-icon name="calendar" size="13" class="text-[var(--text-muted)]" />
                                <span>الفترة: من <strong class="font-numeric">{{ $currentDateFrom ?: 'بداية النشاط' }}</strong> إلى <strong class="font-numeric">{{ $currentDateTo }}</strong></span>
                            </span>
                            <span class="text-[11px] text-[var(--text-muted)] mt-1">العملة: الجنيه المصري (EGP)</span>
                        </div>
                    </div>
                </div>

                <!-- Financial Statement Tabular Representation -->
                <div class="erp-table-responsive">
                    <table class="erp-table w-full text-xs">
                        <thead>
                            <tr class="bg-[var(--bg-surface-alt)] border-b border-[var(--border-color)] text-[var(--text-muted)] font-bold">
                                <th class="w-28 text-right p-3.5">كود الحساب</th>
                                <th class="text-right p-3.5">البيان / الحساب المحاسبي</th>
                                <th class="w-40 text-left font-numeric p-3.5">مبلغ جزئي (ج.م)</th>
                                <th class="w-44 text-left font-numeric p-3.5">إجمالي البند (ج.م)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[var(--border-light)]">

                            <!-- ========================================== -->
                            <!-- SECTION 1: REVENUE / SALES                 -->
                            <!-- ========================================== -->
                            <tr class="bg-[var(--primary-soft)]/40 font-bold border-t-2 border-[var(--primary)]/30">
                                <td colspan="4" class="p-3 text-[var(--primary)] text-sm">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <x-icon name="chevron-left" size="14" />
                                            <span>1. إيرادات النشاط والمبيعات (Operating Revenues)</span>
                                        </div>
                                        <span class="text-xs font-normal text-[var(--text-muted)]">حسابات الفئة (4)</span>
                                    </div>
                                </td>
                            </tr>

                            @foreach($report['gross_sales'] as $sale)
                                <tr class="erp-table-row hover:bg-[var(--bg-hover)]">
                                    <td class="font-numeric p-3">
                                        <span class="inline-block font-mono text-[11px] px-2 py-0.5 rounded bg-[var(--bg-surface-alt)] border border-[var(--border-color)] text-[var(--text-muted)]">
                                            {{ $sale['code'] }}
                                        </span>
                                    </td>
                                    <td class="p-3">
                                        @if(!empty($sale['id']))
                                            <a href="{{ route('accounting.accounts.ledger', $sale['id']) }}" class="text-[var(--text-main)] hover:text-[var(--primary)] font-semibold transition-colors">
                                                {{ $sale['name'] }}
                                            </a>
                                        @else
                                            <span class="text-[var(--text-main)] font-semibold">{{ $sale['name'] }}</span>
                                        @endif
                                    </td>
                                    <td class="p-3 text-left font-numeric font-medium text-[var(--text-secondary)]">
                                        {{ number_format($sale['amount'], 2) }}
                                    </td>
                                    <td class="p-3 text-left font-numeric text-[var(--text-light)]">—</td>
                                </tr>
                            @endforeach

                            @if(count($report['sales_deductions']) > 0)
                                <tr class="bg-[var(--bg-surface-alt)]/50 text-[11px] font-semibold text-[var(--text-muted)]">
                                    <td colspan="4" class="px-4 py-1.5">
                                        يخصم: المردودات والخصومات المسموحة للمبيعات:
                                    </td>
                                </tr>
                                @foreach($report['sales_deductions'] as $ded)
                                    <tr class="erp-table-row hover:bg-[var(--bg-hover)] text-[var(--danger)]">
                                        <td class="font-numeric p-3">
                                            <span class="inline-block font-mono text-[11px] px-2 py-0.5 rounded bg-[var(--danger-soft)] text-[var(--danger)]">
                                                {{ $ded['code'] }}
                                            </span>
                                        </td>
                                        <td class="p-3">
                                            @if(!empty($ded['id']))
                                                <a href="{{ route('accounting.accounts.ledger', $ded['id']) }}" class="text-[var(--danger)] hover:underline font-semibold">
                                                    (-) {{ $ded['name'] }}
                                                </a>
                                            @else
                                                <span class="font-semibold">(-) {{ $ded['name'] }}</span>
                                            @endif
                                        </td>
                                        <td class="p-3 text-left font-numeric font-medium text-[var(--danger)]">
                                            ({{ number_format($ded['amount'], 2) }})
                                        </td>
                                        <td class="p-3 text-left font-numeric text-[var(--text-light)]">—</td>
                                    </tr>
                                @endforeach
                            @endif

                            <!-- Subtotal: Net Revenue -->
                            <tr class="bg-[var(--info-soft)]/40 font-bold border-t border-b border-[var(--info-border)]">
                                <td colspan="2" class="p-3 text-[var(--info-text)] text-xs">
                                    <span class="mr-4">&bull; صافي الإيرادات والمبيعات (Net Revenue)</span>
                                </td>
                                <td class="p-3 text-left font-numeric text-[var(--text-muted)]">—</td>
                                <td class="p-3 text-left font-numeric text-[var(--info-text)] text-sm">
                                    {{ number_format($report['net_revenue'], 2) }} ج.م
                                </td>
                            </tr>

                            <!-- ========================================== -->
                            <!-- SECTION 2: COGS                            -->
                            <!-- ========================================== -->
                            <tr class="bg-[var(--warning-soft)]/40 font-bold border-t-2 border-[var(--warning)]/30">
                                <td colspan="4" class="p-3 text-[var(--warning-text)] text-sm">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <x-icon name="chevron-left" size="14" />
                                            <span>2. تكلفة المبيعات والبضاعة المباعة (Cost of Goods Sold - FIFO)</span>
                                        </div>
                                        <span class="text-xs font-normal text-[var(--text-muted)]">حسابات الفئة (51)</span>
                                    </div>
                                </td>
                            </tr>

                            @forelse($report['cogs'] as $cg)
                                <tr class="erp-table-row hover:bg-[var(--bg-hover)]">
                                    <td class="font-numeric p-3">
                                        <span class="inline-block font-mono text-[11px] px-2 py-0.5 rounded bg-[var(--bg-surface-alt)] border border-[var(--border-color)] text-[var(--text-muted)]">
                                            {{ $cg['code'] }}
                                        </span>
                                    </td>
                                    <td class="p-3">
                                        @if(!empty($cg['id']))
                                            <a href="{{ route('accounting.accounts.ledger', $cg['id']) }}" class="text-[var(--text-main)] hover:text-[var(--primary)] font-semibold transition-colors">
                                                (-) {{ $cg['name'] }}
                                            </a>
                                        @else
                                            <span class="text-[var(--text-main)] font-semibold">(-) {{ $cg['name'] }}</span>
                                        @endif
                                    </td>
                                    <td class="p-3 text-left font-numeric font-medium text-[var(--warning-text)]">
                                        ({{ number_format($cg['amount'], 2) }})
                                    </td>
                                    <td class="p-3 text-left font-numeric text-[var(--text-light)]">—</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="p-3 text-center text-[var(--text-muted)] italic">
                                        لا توجد تكاليف بضاعة مباعة مقيدة خلال الفترة.
                                    </td>
                                </tr>
                            @endforelse

                            <!-- Subtotal: Gross Profit -->
                            <tr class="bg-[var(--success-soft)] font-black border-t border-b border-[var(--success-border)]">
                                <td colspan="2" class="p-3 text-[var(--success-text)] text-xs">
                                    <div class="flex items-center gap-2">
                                        <span class="mr-4">&bull; مجمل الربح التجاري (Gross Profit)</span>
                                        <span class="erp-badge erp-badge-success text-[10px] py-0.5 px-2">هامش ربح {{ $report['gross_margin_pct'] }}%</span>
                                    </div>
                                </td>
                                <td class="p-3 text-left font-numeric text-[var(--text-muted)]">—</td>
                                <td class="p-3 text-left font-numeric text-[var(--success-text)] text-base">
                                    {{ number_format($report['gross_profit'], 2) }} ج.م
                                </td>
                            </tr>

                            <!-- ========================================== -->
                            <!-- SECTION 3: OPERATING EXPENSES              -->
                            <!-- ========================================== -->
                            <tr class="bg-[var(--bg-surface-alt)] font-bold border-t-2 border-[var(--border-color)]">
                                <td colspan="4" class="p-3 text-[var(--text-main)] text-sm">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <x-icon name="chevron-left" size="14" />
                                            <span>3. المصروفات التشغيلية والعمومية (Operating Expenses)</span>
                                        </div>
                                        <span class="text-xs font-normal text-[var(--text-muted)]">حسابات الفئة (52, 53)</span>
                                    </div>
                                </td>
                            </tr>

                            @forelse($report['operating_expenses'] as $exp)
                                <tr class="erp-table-row hover:bg-[var(--bg-hover)]">
                                    <td class="font-numeric p-3">
                                        <span class="inline-block font-mono text-[11px] px-2 py-0.5 rounded bg-[var(--bg-surface-alt)] border border-[var(--border-color)] text-[var(--text-muted)]">
                                            {{ $exp['code'] }}
                                        </span>
                                    </td>
                                    <td class="p-3">
                                        @if(!empty($exp['id']))
                                            <a href="{{ route('accounting.accounts.ledger', $exp['id']) }}" class="text-[var(--text-main)] hover:text-[var(--primary)] font-semibold transition-colors">
                                                (-) {{ $exp['name'] }}
                                            </a>
                                        @else
                                            <span class="text-[var(--text-main)] font-semibold">(-) {{ $exp['name'] }}</span>
                                        @endif
                                    </td>
                                    <td class="p-3 text-left font-numeric font-medium text-[var(--danger)]">
                                        ({{ number_format($exp['amount'], 2) }})
                                    </td>
                                    <td class="p-3 text-left font-numeric text-[var(--text-light)]">—</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="p-3 text-center text-[var(--text-muted)] italic">
                                        لا توجد مصروفات عمومية مسجلة في هذه الفترة.
                                    </td>
                                </tr>
                            @endforelse

                            <!-- Subtotal: Operating Expenses -->
                            <tr class="bg-[var(--bg-surface-alt)]/60 text-[var(--text-muted)] font-semibold border-t border-[var(--border-light)]">
                                <td colspan="2" class="p-2.5">
                                    <span class="mr-4">&bull; إجمالي المصروفات التشغيلية والعمومية</span>
                                </td>
                                <td class="p-2.5 text-left font-numeric">—</td>
                                <td class="p-2.5 text-left font-numeric text-[var(--danger)]">
                                    (-) {{ number_format($report['total_operating_expenses'], 2) }} ج.م
                                </td>
                            </tr>

                            <!-- Subtotal: Operating Profit -->
                            <tr class="bg-[var(--info-soft)]/20 font-bold border-t border-b border-[var(--info-border)]/50">
                                <td colspan="2" class="p-3 text-[var(--info-text)] text-xs">
                                    <span class="mr-4">&bull; صافي الربح التشغيلي (Operating Profit)</span>
                                </td>
                                <td class="p-3 text-left font-numeric text-[var(--text-muted)]">—</td>
                                <td class="p-3 text-left font-numeric text-[var(--info-text)] text-sm">
                                    {{ number_format($report['operating_profit'], 2) }} ج.م
                                </td>
                            </tr>

                            <!-- ========================================== -->
                            <!-- SECTION 4: OTHER REVENUE / EXPENSES        -->
                            <!-- ========================================== -->
                            @if(count($report['other_items']) > 0)
                                <tr class="bg-[var(--bg-surface-alt)] font-bold border-t-2 border-[var(--border-color)]">
                                    <td colspan="4" class="p-3 text-[var(--text-main)] text-sm">
                                        <div class="flex items-center gap-2">
                                            <x-icon name="chevron-left" size="14" />
                                            <span>4. إيرادات وتكاليف أخرى (Other Income / Expenses)</span>
                                        </div>
                                    </td>
                                </tr>

                                @foreach($report['other_items'] as $other)
                                    <tr class="erp-table-row hover:bg-[var(--bg-hover)]">
                                        <td class="font-numeric p-3">
                                            <span class="inline-block font-mono text-[11px] px-2 py-0.5 rounded bg-[var(--bg-surface-alt)] border border-[var(--border-color)] text-[var(--text-muted)]">
                                                {{ $other['code'] }}
                                            </span>
                                        </td>
                                        <td class="p-3">
                                            <a href="{{ route('accounting.accounts.ledger', $other['id']) }}" class="text-[var(--text-main)] hover:text-[var(--primary)] font-semibold transition-colors">
                                                (+) {{ $other['name'] }}
                                            </a>
                                        </td>
                                        <td class="p-3 text-left font-numeric font-medium text-[var(--success-text)]">
                                            {{ number_format($other['amount'], 2) }}
                                        </td>
                                        <td class="p-3 text-left font-numeric text-[var(--text-light)]">—</td>
                                    </tr>
                                @endforeach
                            @endif

                        </tbody>

                        <!-- ========================================== -->
                        <!-- GRAND TOTAL: NET PROFIT / LOSS FOOTER      -->
                        <!-- ========================================== -->
                        <tfoot>
                            <tr class="border-t-4 border-[var(--primary)] {{ $report['net_profit'] >= 0 ? 'bg-[var(--success-soft)]' : 'bg-[var(--danger-soft)]' }}">
                                <td colspan="2" class="p-5">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-xl {{ $report['net_profit'] >= 0 ? 'bg-[var(--success)] text-white' : 'bg-[var(--danger)] text-white' }} flex items-center justify-center flex-shrink-0 shadow-sm">
                                            <x-icon name="{{ $report['net_profit'] >= 0 ? 'award' : 'alert-octagon' }}" size="24" />
                                        </div>
                                        <div>
                                            <div class="text-base font-black {{ $report['net_profit'] >= 0 ? 'text-[var(--success-text)]' : 'text-[var(--danger)]' }}">
                                                صافي الربح / الخسارة النهائي للفترة (Net Profit / Loss):
                                            </div>
                                            <div class="text-[11px] text-[var(--text-muted)] mt-0.5 font-normal">
                                                النتيجة الختامية بعد كافة التكاليف والمصروفات &bull; يُرحل تلقائياً إلى بند حقوق الملكية بالميزانية العمومية
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-5 text-left font-numeric text-[var(--text-muted)]">—</td>
                                <td class="p-5 text-left font-numeric font-black text-2xl {{ $report['net_profit'] >= 0 ? 'text-[var(--success-text)]' : 'text-[var(--danger)]' }}">
                                    {{ number_format($report['net_profit'], 2) }} <span class="text-xs font-bold text-[var(--text-muted)]">ج.م</span>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Signatures Section at the base of the sheet -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 p-6 border-t border-[var(--border-color)] bg-[var(--bg-surface-alt)]/30 text-center text-xs">
                    <div class="space-y-8">
                        <div class="font-bold text-[var(--text-muted)]">المحاسب المسؤول (إعداد)</div>
                        <div class="border-t border-[var(--border-color)] pt-1.5 font-semibold text-[var(--text-main)]">
                            {{ auth()->user()->name ?? 'قسم الحسابات' }}
                        </div>
                    </div>

                    <div class="space-y-8">
                        <div class="font-bold text-[var(--text-muted)]">المراجعة والتدقيق المالي</div>
                        <div class="border-t border-[var(--border-color)] pt-1.5 text-[var(--text-muted)]">
                            التوقيع والاعتماد
                        </div>
                    </div>

                    <div class="space-y-8">
                        <div class="font-bold text-[var(--text-muted)]">المدير المالي / اعتماد الإدارة</div>
                        <div class="border-t border-[var(--border-color)] pt-1.5 text-[var(--text-muted)]">
                            الختم الرسمي
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection
