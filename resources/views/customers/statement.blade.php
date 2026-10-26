@extends('layouts.app')

@section('control_panel')
    <x-control-panel :title="'كشف حساب العميل: ' . $customer->name" :breadcrumbs="['العملاء والموردون' => route('customers.index'), 'دليل العملاء' => route('customers.index'), $customer->name => route('customers.show', $customer), 'كشف الحساب المالي' => route('customers.statement', $customer)]">
        <x-slot:actions>
            <a href="{{ route('customers.show', $customer) }}" class="erp-btn erp-btn-secondary">
                <x-icon name="arrow-right" size="16" />
                <span>العودة لملف العميل</span>
            </a>

            <a href="{{ $whatsAppUrl }}" target="_blank" rel="noopener noreferrer" class="erp-btn erp-btn-success">
                <x-icon name="message-circle" size="16" />
                <span>مشاركة كشف الحساب عبر واتساب</span>
            </a>

            <!-- Compact Print Dropdown -->
            <div class="erp-dropdown">
                <button type="button" class="erp-btn erp-btn-primary" data-dropdown-toggle="customerStatementPrintMenu">
                    <x-icon name="printer" size="16" />
                    <span>طباعة كشف الحساب</span>
                    <x-icon name="chevron-down" size="14" />
                </button>
                <div id="customerStatementPrintMenu" class="erp-dropdown-menu">
                    <a href="{{ route('customers.statement.print', [$customer, 'format' => 'a4', 'from_date' => $fromDate, 'to_date' => $toDate]) }}" target="_blank" class="erp-dropdown-item">
                        <x-icon name="file-text" size="16" />
                        <span>طباعة A4 قياسي</span>
                    </a>
                    <a href="{{ route('customers.statement.print', [$customer, 'format' => 'a5', 'from_date' => $fromDate, 'to_date' => $toDate]) }}" target="_blank" class="erp-dropdown-item">
                        <x-icon name="file" size="16" />
                        <span>طباعة A5 وسط</span>
                    </a>
                    <a href="{{ route('customers.statement.print', [$customer, 'format' => 'thermal_80mm', 'from_date' => $fromDate, 'to_date' => $toDate]) }}" target="_blank" class="erp-dropdown-item">
                        <x-icon name="printer" size="16" />
                        <span>طباعة 80 مم حراري</span>
                    </a>
                </div>
            </div>
        </x-slot:actions>

        <x-slot:extra>
            <span class="erp-badge {{ $statement['is_debt'] ? 'erp-badge-danger' : ($statement['is_credit'] ? 'erp-badge-success' : 'erp-badge-neutral') }} text-xs">
                {{ $statement['balance_status_label'] }}: {{ number_format($statement['final_balance'], 2) }} ج.م
            </span>
        </x-slot:extra>
    </x-control-panel>
@endsection

@section('content')
    <div data-page="customer-statement">
        <!-- Date Filters Card -->
        <div class="erp-card mb-6">
            <div class="erp-card-body p-4">
                <form method="GET" action="{{ route('customers.statement', $customer) }}" class="flex flex-wrap items-end gap-4">
                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-xs font-semibold text-[var(--text-muted)] mb-1">من تاريخ</label>
                        <input type="date" name="from_date" value="{{ $fromDate }}" class="erp-input w-full">
                    </div>
                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-xs font-semibold text-[var(--text-muted)] mb-1">إلى تاريخ</label>
                        <input type="date" name="to_date" value="{{ $toDate }}" class="erp-input w-full">
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="submit" class="erp-btn erp-btn-primary">
                            <x-icon name="filter" size="16" />
                            <span>تحديث التقرير</span>
                        </button>
                        @if($fromDate || $toDate)
                            <a href="{{ route('customers.statement', $customer) }}" class="erp-btn erp-btn-secondary">
                                <x-icon name="x" size="14" />
                                <span>إلغاء التصفية</span>
                            </a>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        <!-- Financial Summary KPIs -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div class="erp-card p-4 text-center">
                <div class="text-xs text-[var(--text-muted)] font-medium mb-1">رصيد ما قبل الفترة</div>
                <div class="font-numeric font-bold text-xl text-[var(--text-main)]">
                    {{ number_format($statement['pre_period_balance'], 2) }} ج.م
                </div>
            </div>

            <div class="erp-card p-4 text-center">
                <div class="text-xs text-[var(--text-muted)] font-medium mb-1">إجمالي المسحوبات (مدين)</div>
                <div class="font-numeric font-bold text-xl text-[var(--danger)]">
                    +{{ number_format($statement['total_debits'], 2) }} ج.م
                </div>
            </div>

            <div class="erp-card p-4 text-center">
                <div class="text-xs text-[var(--text-muted)] font-medium mb-1">إجمالي المدفوعات (دائن)</div>
                <div class="font-numeric font-bold text-xl text-[var(--success)]">
                    -{{ number_format($statement['total_credits'], 2) }} ج.م
                </div>
            </div>

            <div class="erp-card p-4 text-center">
                <div class="text-xs text-[var(--text-muted)] font-medium mb-1">صافي الرصيد الحالي</div>
                <div class="font-numeric font-black text-2xl {{ $statement['is_debt'] ? 'text-[var(--danger)]' : ($statement['is_credit'] ? 'text-[var(--success)]' : 'text-[var(--text-muted)]') }}">
                    {{ number_format($statement['final_balance'], 2) }} ج.م
                </div>
            </div>
        </div>

        <!-- Statement Ledger Table -->
        <div class="erp-card">
            <div class="erp-card-header flex items-center justify-between">
                <div class="erp-card-title flex items-center gap-2">
                    <x-icon name="file-text" size="18" class="text-[var(--primary)]" />
                    <span>حركات كشف الحساب التفصيلي والأرصدة المتتابعة</span>
                </div>
                <span class="erp-badge erp-badge-neutral text-xs font-numeric">
                    {{ count($statement['rows']) }} حركات مسجلة
                </span>
            </div>

            <div class="erp-card-body p-0">
                <div class="erp-table-responsive">
                    <table class="erp-table">
                        <thead>
                            <tr>
                                <th class="w-12 text-center">#</th>
                                <th class="text-center">التاريخ</th>
                                <th class="text-center">رقم المستند</th>
                                <th>البيان والتفاصيل</th>
                                <th class="text-center">المستودع</th>
                                <th class="text-center">مدين (ج.م)</th>
                                <th class="text-center">دائن (ج.م)</th>
                                <th class="text-center">الرصيد التراكمي (ج.م)</th>
                                <th class="w-20 text-center">عرض</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Pre-period Row -->
                            <tr class="bg-[var(--bg-surface-alt)] font-semibold">
                                <td class="text-center font-numeric">—</td>
                                <td class="text-center font-numeric text-xs">{{ $fromDate ?: '—' }}</td>
                                <td class="text-center font-numeric text-xs">OPENING</td>
                                <td>رصيد ما قبل الفترة / الافتتاحي</td>
                                <td class="text-center text-xs">—</td>
                                <td class="text-center font-numeric text-xs">{{ $statement['pre_period_balance'] > 0 ? number_format($statement['pre_period_balance'], 2) : '0.00' }}</td>
                                <td class="text-center font-numeric text-xs">{{ $statement['pre_period_balance'] < 0 ? number_format(abs($statement['pre_period_balance']), 2) : '0.00' }}</td>
                                <td class="text-center font-numeric text-xs font-bold">{{ number_format($statement['pre_period_balance'], 2) }}</td>
                                <td class="text-center">—</td>
                            </tr>

                            @forelse($statement['rows'] as $index => $row)
                                <tr>
                                    <td class="text-center font-numeric text-xs text-[var(--text-muted)]">
                                        {{ $index + 1 }}
                                    </td>
                                    <td class="text-center text-xs font-numeric">
                                        {{ $row['date'] }}
                                        <div class="text-[10px] text-[var(--text-muted)]">{{ $row['time'] }}</div>
                                    </td>
                                    <td class="text-center font-numeric font-bold text-xs">
                                        {{ $row['doc_number'] }}
                                    </td>
                                    <td class="text-xs">
                                        <span class="font-medium text-[var(--text-main)]">{{ $row['description'] }}</span>
                                        @if($row['due_date'])
                                            <div class="text-[11px] text-[var(--text-muted)]">تاريخ استحقاق الآجل: {{ $row['due_date'] }}</div>
                                        @endif
                                    </td>
                                    <td class="text-center text-xs text-[var(--text-muted)]">
                                        {{ $row['warehouse_name'] }}
                                    </td>
                                    <td class="text-center font-numeric font-bold text-xs {{ $row['debit'] > 0 ? 'text-[var(--danger)]' : 'text-[var(--text-muted)]' }}">
                                        {{ $row['debit'] > 0 ? number_format($row['debit'], 2) : '0.00' }}
                                    </td>
                                    <td class="text-center font-numeric font-bold text-xs {{ $row['credit'] > 0 ? 'text-[var(--success)]' : 'text-[var(--text-muted)]' }}">
                                        {{ $row['credit'] > 0 ? number_format($row['credit'], 2) : '0.00' }}
                                    </td>
                                    <td class="text-center font-numeric font-black text-xs">
                                        {{ number_format($row['running_balance'], 2) }}
                                    </td>
                                    <td class="text-center">
                                        @if($row['url'] !== '#')
                                            <a href="{{ $row['url'] }}" class="erp-btn erp-btn-ghost erp-btn-xs" title="عرض المستند">
                                                <x-icon name="eye" size="14" />
                                            </a>
                                        @else
                                            <span>—</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center py-8 text-[var(--text-muted)]">
                                        لا توجد حركات مالية مسجلة لهذا العميل خلال الفترة المحددة
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if(count($statement['rows']) > 0)
                            <tfoot>
                                <tr class="bg-[var(--bg-surface-alt)] font-bold">
                                    <td colspan="5" class="text-start py-3 px-4">إجمالي الفترة وصافي الرصيد المستحق:</td>
                                    <td class="text-center font-numeric text-[var(--danger)]">{{ number_format($statement['total_debits'], 2) }} ج.م</td>
                                    <td class="text-center font-numeric text-[var(--success)]">{{ number_format($statement['total_credits'], 2) }} ج.م</td>
                                    <td class="text-center font-numeric font-black text-sm {{ $statement['is_debt'] ? 'text-[var(--danger)]' : 'text-[var(--success)]' }}">
                                        {{ number_format($statement['final_balance'], 2) }} ج.م
                                    </td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
