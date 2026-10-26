@extends('layouts.app')

@section('control_panel')
    <x-control-panel title="ميزان المراجعة (Trial Balance)" :breadcrumbs="['المالية والحسابات' => route('treasuries.index'), 'شجرة الحسابات' => route('accounting.chart-of-accounts'), 'ميزان المراجعة' => route('accounting.reports.trial-balance')]">
        <x-slot:actions>
            <a href="{{ route('accounting.reports.trial-balance.print', request()->query()) }}" target="_blank" class="erp-btn erp-btn-secondary">
                <x-icon name="printer" size="16" />
                <span>طباعة التقرير</span>
            </a>
            <a href="{{ route('accounting.chart-of-accounts') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="list" size="16" />
                <span>دليل الحسابات</span>
            </a>
        </x-slot:actions>

        <x-slot:extra>
            @if($report['totals']['is_balanced'])
                <span class="erp-badge erp-badge-success font-numeric px-3 py-1 text-xs">
                    <x-icon name="check-circle" size="13" />
                    <span class="mr-1">الميزان متوازن ومطابق بنسبة 100%</span>
                </span>
            @else
                <span class="erp-badge erp-badge-danger font-numeric px-3 py-1 text-xs">
                    <x-icon name="alert-triangle" size="13" />
                    <span class="mr-1">يوجد فارق غير متوازن: {{ number_format($report['totals']['diff'], 2) }} ج.م</span>
                </span>
            @endif
        </x-slot:extra>
    </x-control-panel>
@endsection

@section('content')
    <div data-page="trial-balance">
        @include('accounting.nav-tabs')

        <!-- KPI Summary Cards -->
        <div class="finance-kpi-grid">
            <div class="finance-kpi-card">
                <div class="finance-kpi-icon primary">
                    <x-icon name="trending-up" size="22" />
                </div>
                <div class="finance-kpi-info">
                    <div class="finance-kpi-label">حركات الفترة (مدين)</div>
                    <div class="finance-kpi-value font-numeric text-[var(--primary)]">
                        {{ number_format($report['totals']['period_debit'], 2) }} <span class="text-xs font-normal text-[var(--text-muted)]">ج.م</span>
                    </div>
                    <div class="finance-kpi-sub">إجمالي قيود المدين للفترة</div>
                </div>
            </div>

            <div class="finance-kpi-card">
                <div class="finance-kpi-icon info">
                    <x-icon name="trending-down" size="22" />
                </div>
                <div class="finance-kpi-info">
                    <div class="finance-kpi-label">حركات الفترة (دائن)</div>
                    <div class="finance-kpi-value font-numeric text-[var(--info-text)]">
                        {{ number_format($report['totals']['period_credit'], 2) }} <span class="text-xs font-normal text-[var(--text-muted)]">ج.م</span>
                    </div>
                    <div class="finance-kpi-sub">إجمالي قيود الدائن للفترة</div>
                </div>
            </div>

            <div class="finance-kpi-card">
                <div class="finance-kpi-icon success">
                    <x-icon name="layers" size="22" />
                </div>
                <div class="finance-kpi-info">
                    <div class="finance-kpi-label">إجمالي الأرصدة الختامية</div>
                    <div class="finance-kpi-value font-numeric text-[var(--success-text)]">
                        {{ number_format($report['totals']['closing_debit'], 2) }} <span class="text-xs font-normal text-[var(--text-muted)]">ج.م</span>
                    </div>
                    <div class="finance-kpi-sub">مجموع أرصدة الإغلاق المدينة/الدائنة</div>
                </div>
            </div>

            <div class="finance-kpi-card">
                <div class="finance-kpi-icon {{ $report['totals']['is_balanced'] ? 'success' : 'danger' }}">
                    <x-icon name="{{ $report['totals']['is_balanced'] ? 'shield' : 'alert-octagon' }}" size="22" />
                </div>
                <div class="finance-kpi-info">
                    <div class="finance-kpi-label">حالة التوازن المحاسبي</div>
                    <div class="finance-kpi-value font-numeric {{ $report['totals']['is_balanced'] ? 'text-[var(--success-text)]' : 'text-[var(--danger)]' }}">
                        {{ $report['totals']['is_balanced'] ? 'متوازن تماماً (0.00)' : 'فارق: '.number_format($report['totals']['diff'], 2).' ج.م' }}
                    </div>
                    <div class="finance-kpi-sub">
                        {{ $report['totals']['is_balanced'] ? 'تطابق تام بين المدين والدائن' : 'يوجد عدم اتزان يتطلب مراجعة' }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter & Search Toolbar -->
        <div class="finance-filter-bar mb-4">
            <form method="GET" action="{{ route('accounting.reports.trial-balance') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-3 items-end">
                <div>
                    <label class="block text-xs font-semibold mb-1 text-[var(--text-muted)]">من تاريخ</label>
                    <input type="date" name="date_from" value="{{ $currentDateFrom }}" class="finance-filter-input w-full font-numeric text-xs">
                </div>

                <div>
                    <label class="block text-xs font-semibold mb-1 text-[var(--text-muted)]">إلى تاريخ</label>
                    <input type="date" name="date_to" value="{{ $currentDateTo }}" class="finance-filter-input w-full font-numeric text-xs">
                </div>

                <div>
                    <label class="block text-xs font-semibold mb-1 text-[var(--text-muted)]">مستوى العرض</label>
                    <select name="level" class="finance-filter-input w-full text-xs">
                        <option value="">كافة المستويات (تفصيلي)</option>
                        <option value="1" {{ $currentLevel === 1 ? 'selected' : '' }}>المستوى الأول (الحسابات الرئيسية)</option>
                        <option value="2" {{ $currentLevel === 2 ? 'selected' : '' }}>المستوى الثاني (المجموعات)</option>
                        <option value="3" {{ $currentLevel === 3 ? 'selected' : '' }}>المستوى الثالث (الحسابات العامة)</option>
                    </select>
                </div>

                <div class="flex items-center pb-2">
                    <label class="inline-flex items-center gap-2 cursor-pointer text-xs font-semibold select-none text-[var(--text-main)]">
                        <input type="checkbox" name="show_zero" value="1" {{ $currentShowZero ? 'checked' : '' }} class="rounded border-[var(--border-color)] text-[var(--primary)] focus:ring-[var(--primary)]">
                        <span>إظهار الحسابات الصفرية</span>
                    </label>
                </div>

                <div class="flex gap-2">
                    <button type="submit" class="erp-btn erp-btn-primary flex-1 justify-center text-xs">
                        <x-icon name="filter" size="14" />
                        <span>تطبيق الفلترة</span>
                    </button>
                    <a href="{{ route('accounting.reports.trial-balance') }}" class="erp-btn erp-btn-secondary text-xs" title="إعادة ضبط">
                        <x-icon name="rotate-ccw" size="14" />
                    </a>
                </div>
            </form>
        </div>

        <!-- Trial Balance Table -->
        <div class="erp-card overflow-hidden">
            <div class="erp-table-responsive">
                <table class="erp-table erp-table-hover text-xs">
                    <thead>
                        <tr>
                            <th rowspan="2" class="text-right font-bold w-28">كود الحساب</th>
                            <th rowspan="2" class="text-right font-bold">اسم الحساب</th>
                            <th rowspan="2" class="text-center font-bold w-24">النوع / الطبيعة</th>
                            <th colspan="2" class="text-center font-bold border-l border-r border-[var(--border-color)] bg-[var(--bg-surface-alt)]">الرصيد الافتتاحي</th>
                            <th colspan="2" class="text-center font-bold border-r border-[var(--border-color)] bg-[var(--info-soft)]">حركات الفترة</th>
                            <th colspan="2" class="text-center font-bold border-r border-[var(--border-color)] bg-[var(--success-soft)]">الرصيد الختامي</th>
                            <th rowspan="2" class="text-center font-bold w-24">كشف الأستاذ</th>
                        </tr>
                        <tr class="text-[11px] text-[var(--text-muted)] bg-[var(--bg-surface-alt)]">
                            <th class="text-center border-l border-[var(--border-color)]">مدين</th>
                            <th class="text-center border-r border-[var(--border-color)]">دائن</th>
                            <th class="text-center">مدين</th>
                            <th class="text-center border-r border-[var(--border-color)]">دائن</th>
                            <th class="text-center">مدين</th>
                            <th class="text-center border-r border-[var(--border-color)]">دائن</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($report['accounts'] as $item)
                            <tr class="erp-table-row {{ $item['level'] === 1 ? 'font-bold bg-[var(--bg-surface-alt)]' : ($item['level'] === 2 ? 'font-semibold' : '') }}">
                                <td class="font-numeric text-[var(--text-muted)]">
                                    <span class="inline-block px-1.5 py-0.5 rounded {{ $item['level'] === 1 ? 'bg-[var(--primary-soft)] text-[var(--primary)] font-bold' : '' }}">
                                        {{ $item['code'] }}
                                    </span>
                                </td>
                                <td>
                                    <div style="padding-right: {{ ($item['level'] - 1) * 16 }}px;" class="flex items-center gap-1.5">
                                        @if($item['level'] > 1)
                                            <span class="text-[var(--text-muted)] text-[10px]">↳</span>
                                        @endif
                                        <a href="{{ route('accounting.accounts.ledger', $item['id']) }}" class="text-[var(--text-main)] hover:text-[var(--primary)] font-bold">
                                            {{ $item['name'] }}
                                        </a>
                                        @if(! $item['is_leaf'])
                                            <span class="text-[10px] text-[var(--text-muted)] font-normal">(رئيسي)</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="erp-badge text-[10px] py-0.5 px-2 {{ $item['nature'] === 'debit' ? 'erp-badge-info' : 'erp-badge-success' }}">
                                        {{ $item['nature'] === 'debit' ? 'مدين' : 'دائن' }}
                                    </span>
                                </td>
                                <td class="text-center font-numeric border-l border-[var(--border-color)]">
                                    {{ $item['opening_debit'] > 0 ? number_format($item['opening_debit'], 2) : '-' }}
                                </td>
                                <td class="text-center font-numeric border-r border-[var(--border-color)]">
                                    {{ $item['opening_credit'] > 0 ? number_format($item['opening_credit'], 2) : '-' }}
                                </td>
                                <td class="text-center font-numeric text-[var(--info-text)]">
                                    {{ $item['period_debit'] > 0 ? number_format($item['period_debit'], 2) : '-' }}
                                </td>
                                <td class="text-center font-numeric text-[var(--info-text)] border-r border-[var(--border-color)]">
                                    {{ $item['period_credit'] > 0 ? number_format($item['period_credit'], 2) : '-' }}
                                </td>
                                <td class="text-center font-numeric font-bold text-[var(--success-text)]">
                                    {{ $item['closing_debit'] > 0 ? number_format($item['closing_debit'], 2) : '-' }}
                                </td>
                                <td class="text-center font-numeric font-bold text-[var(--success-text)] border-r border-[var(--border-color)]">
                                    {{ $item['closing_credit'] > 0 ? number_format($item['closing_credit'], 2) : '-' }}
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('accounting.accounts.ledger', $item['id']) }}" class="erp-btn erp-btn-secondary text-xs py-0.5 px-2" title="عرض كشف حركة الحساب في الأستاذ">
                                        <x-icon name="eye" size="14" />
                                        <span>كشف</span>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="p-8 text-center text-[var(--text-muted)]">
                                    لا توجد حسابات مطابقة لمعايير البحث الحالية.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="bg-[var(--bg-surface-alt)] font-bold border-t-2 border-[var(--border-color)] text-xs font-numeric">
                            <td colspan="3" class="p-3 text-right">
                                الإجمالي العام المتوازن (مستوى الحسابات الرئيسية):
                            </td>
                            <td class="p-3 text-center border-l border-[var(--border-color)]">
                                {{ number_format($report['totals']['opening_debit'], 2) }}
                            </td>
                            <td class="p-3 text-center border-r border-[var(--border-color)]">
                                {{ number_format($report['totals']['opening_credit'], 2) }}
                            </td>
                            <td class="p-3 text-center text-[var(--info-text)]">
                                {{ number_format($report['totals']['period_debit'], 2) }}
                            </td>
                            <td class="p-3 text-center text-[var(--info-text)] border-r border-[var(--border-color)]">
                                {{ number_format($report['totals']['period_credit'], 2) }}
                            </td>
                            <td class="p-3 text-center text-[var(--success-text)]">
                                {{ number_format($report['totals']['closing_debit'], 2) }}
                            </td>
                            <td class="p-3 text-center text-[var(--success-text)] border-r border-[var(--border-color)]">
                                {{ number_format($report['totals']['closing_credit'], 2) }}
                            </td>
                            <td class="p-3 text-center text-[10px] {{ $report['totals']['is_balanced'] ? 'text-[var(--success-text)]' : 'text-[var(--danger)]' }}">
                                {{ $report['totals']['is_balanced'] ? 'متوازن 100%' : 'فارق: '.number_format($report['totals']['diff'], 2) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
@endsection
