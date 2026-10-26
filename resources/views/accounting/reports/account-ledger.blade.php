@extends('layouts.app')

@section('control_panel')
    <x-control-panel title="كشف حساب الأستاذ العام: {{ $account->name }} [{{ $account->code }}]" :breadcrumbs="['المالية والحسابات' => route('treasuries.index'), 'شجرة الحسابات' => route('accounting.chart-of-accounts'), $account->name => '#']">
        <x-slot:actions>
            <a href="{{ route('accounting.accounts.ledger.print', array_merge(['account' => $account->id], request()->query())) }}" target="_blank" class="erp-btn erp-btn-secondary">
                <x-icon name="printer" size="16" />
                <span>طباعة كشف الحساب</span>
            </a>
            <a href="{{ route('accounting.chart-of-accounts') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="list" size="16" />
                <span>شجرة الحسابات</span>
            </a>
            <a href="{{ route('accounting.reports.trial-balance') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="layers" size="16" />
                <span>ميزان المراجعة</span>
            </a>
        </x-slot:actions>

        <x-slot:extra>
            <span class="erp-badge font-numeric px-3 py-1 text-xs {{ $account->nature === 'debit' ? 'erp-badge-info' : 'erp-badge-success' }}">
                <span>طبيعة الحساب:</span>
                <strong class="mr-1">{{ $account->nature_label }}</strong>
            </span>
        </x-slot:extra>
    </x-control-panel>
@endsection

@section('content')
    <div data-page="account-ledger">
        @include('accounting.nav-tabs')

        <!-- Account Info & KPI Cards -->
        <div class="finance-kpi-grid">
            <div class="finance-kpi-card">
                <div class="finance-kpi-icon primary">
                    <x-icon name="calendar" size="22" />
                </div>
                <div class="finance-kpi-info">
                    <div class="finance-kpi-label">الرصيد الافتتاحي (قبل الفترة)</div>
                    <div class="finance-kpi-value font-numeric text-[var(--text-main)]">
                        {{ number_format($ledger['opening_balance'], 2) }} <span class="text-xs font-normal text-[var(--text-muted)]">ج.م</span>
                    </div>
                    <div class="finance-kpi-sub">
                        {{ $ledger['opening_balance'] >= 0 ? ($account->nature === 'debit' ? 'رصيد مدين' : 'رصيد دائن') : 'عكس الطبيعة' }}
                    </div>
                </div>
            </div>

            <div class="finance-kpi-card">
                <div class="finance-kpi-icon success">
                    <x-icon name="trending-up" size="22" />
                </div>
                <div class="finance-kpi-info">
                    <div class="finance-kpi-label">إجمالي حركات المدين (+)</div>
                    <div class="finance-kpi-value font-numeric text-[var(--success-text)]">
                        {{ number_format($ledger['total_debit'], 2) }} <span class="text-xs font-normal text-[var(--text-muted)]">ج.م</span>
                    </div>
                    <div class="finance-kpi-sub">إجمالي قيود الجانب المدين</div>
                </div>
            </div>

            <div class="finance-kpi-card">
                <div class="finance-kpi-icon info">
                    <x-icon name="trending-down" size="22" />
                </div>
                <div class="finance-kpi-info">
                    <div class="finance-kpi-label">إجمالي حركات الدائن (-)</div>
                    <div class="finance-kpi-value font-numeric text-[var(--info-text)]">
                        {{ number_format($ledger['total_credit'], 2) }} <span class="text-xs font-normal text-[var(--text-muted)]">ج.م</span>
                    </div>
                    <div class="finance-kpi-sub">إجمالي قيود الجانب الدائن</div>
                </div>
            </div>

            <div class="finance-kpi-card">
                <div class="finance-kpi-icon warning">
                    <x-icon name="award" size="22" />
                </div>
                <div class="finance-kpi-info">
                    <div class="finance-kpi-label">الرصيد الختامي التراكمي</div>
                    <div class="finance-kpi-value font-numeric text-[var(--primary)]">
                        {{ number_format($ledger['closing_balance'], 2) }} <span class="text-xs font-normal text-[var(--text-muted)]">ج.م</span>
                    </div>
                    <div class="finance-kpi-sub">
                        {{ $ledger['closing_balance'] >= 0 ? ($account->nature === 'debit' ? 'رصيد مدين' : 'رصيد دائن') : 'رصيد شاذ (عكس الطبيعة)' }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Toolbar -->
        <div class="finance-filter-bar mb-4">
            <form method="GET" action="{{ route('accounting.accounts.ledger', $account) }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-end">
                <div>
                    <label class="block text-xs font-semibold mb-1 text-[var(--text-muted)]">من تاريخ</label>
                    <input type="date" name="date_from" value="{{ $currentDateFrom }}" class="finance-filter-input w-full font-numeric text-xs">
                </div>

                <div>
                    <label class="block text-xs font-semibold mb-1 text-[var(--text-muted)]">إلى تاريخ</label>
                    <input type="date" name="date_to" value="{{ $currentDateTo }}" class="finance-filter-input w-full font-numeric text-xs">
                </div>

                <div class="flex gap-2">
                    <button type="submit" class="erp-btn erp-btn-primary flex-1 justify-center text-xs">
                        <x-icon name="filter" size="14" />
                        <span>تحديث الكشف</span>
                    </button>
                    <a href="{{ route('accounting.accounts.ledger', $account) }}" class="erp-btn erp-btn-secondary text-xs" title="إعادة ضبط">
                        <x-icon name="rotate-ccw" size="14" />
                    </a>
                </div>
            </form>
        </div>

        <!-- General Ledger Movements Table -->
        <div class="erp-card overflow-hidden">
            <div class="p-4 bg-[var(--bg-surface-alt)] border-b border-[var(--border-color)] flex justify-between items-center">
                <div>
                    <h3 class="font-bold text-sm text-[var(--text-main)]">سجل حركات حساب الأستاذ العام</h3>
                    <p class="text-xs text-[var(--text-muted)] mt-0.5">
                        {{ $account->name }} &bull; كود: {{ $account->code }} &bull; النوع: {{ $account->type_label }}
                    </p>
                </div>
                <span class="text-xs font-numeric text-[var(--text-muted)]">
                    عدد الحركات: {{ count($ledger['lines']) }} حركة
                </span>
            </div>

            <div class="erp-table-responsive">
                <table class="erp-table erp-table-hover text-xs">
                    <thead>
                        <tr>
                            <th class="w-28 text-right font-bold">التاريخ</th>
                            <th class="w-36 text-right font-bold">رقم القيد / المرجع</th>
                            <th class="text-right font-bold">البيان والشرح</th>
                            <th class="w-36 text-right font-bold">الطرف المقابل</th>
                            <th class="w-28 text-center font-bold text-[var(--success-text)]">مدين (+)</th>
                            <th class="w-28 text-center font-bold text-[var(--info-text)]">دائن (-)</th>
                            <th class="w-32 text-center font-bold">الرصيد التراكمي</th>
                            <th class="w-20 text-center font-bold">معاينة</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Opening balance row if filtered -->
                        @if($currentDateFrom && $ledger['opening_balance'] != 0)
                            <tr class="bg-[var(--bg-surface-alt)] font-semibold italic text-[var(--text-muted)]">
                                <td class="font-numeric">{{ $currentDateFrom }}</td>
                                <td class="font-numeric">-</td>
                                <td colspan="2">الرصيد الافتتاحي المنقول قبل تاريخ {{ $currentDateFrom }}</td>
                                <td class="text-center font-numeric">{{ $account->nature === 'debit' && $ledger['opening_balance'] > 0 ? number_format($ledger['opening_balance'], 2) : '-' }}</td>
                                <td class="text-center font-numeric">{{ $account->nature === 'credit' && $ledger['opening_balance'] > 0 ? number_format($ledger['opening_balance'], 2) : '-' }}</td>
                                <td class="text-center font-numeric font-bold text-[var(--text-main)]">{{ number_format($ledger['opening_balance'], 2) }}</td>
                                <td class="text-center">-</td>
                            </tr>
                        @endif

                        @forelse($ledger['lines'] as $line)
                            <tr class="erp-table-row">
                                <td class="font-numeric text-[var(--text-secondary)] whitespace-nowrap">{{ $line['entry_date'] }}</td>
                                <td class="font-numeric font-semibold">
                                    <a href="{{ route('accounting.journal-entries.show', $line['entry_id']) }}" class="text-[var(--primary)] hover:underline inline-flex items-center gap-1">
                                        <x-icon name="file-text" size="13" />
                                        <span>{{ $line['entry_number'] }}</span>
                                    </a>
                                </td>
                                <td>
                                    <div class="font-medium text-[var(--text-main)]">{{ $line['description'] }}</div>
                                    @if($line['reference_number'])
                                        <div class="text-[11px] text-[var(--text-muted)] font-numeric">مرجع: {{ $line['reference_number'] }}</div>
                                    @endif
                                </td>
                                <td class="text-[var(--text-secondary)]">
                                    {{ $line['party_name'] ?: '—' }}
                                </td>
                                <td class="text-center font-numeric text-[var(--success-text)] font-semibold">
                                    {{ $line['debit'] > 0 ? number_format($line['debit'], 2) : '—' }}
                                </td>
                                <td class="text-center font-numeric text-[var(--info-text)] font-semibold">
                                    {{ $line['credit'] > 0 ? number_format($line['credit'], 2) : '—' }}
                                </td>
                                <td class="text-center font-numeric font-bold text-[var(--text-main)] bg-[var(--bg-surface-alt)]">
                                    {{ number_format($line['running_balance'], 2) }}
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('accounting.journal-entries.show', $line['entry_id']) }}" class="erp-btn erp-btn-secondary text-xs p-1" title="معاينة سند القيد">
                                        <x-icon name="eye" size="14" />
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="p-8 text-center text-[var(--text-muted)]">
                                    لا توجد حركات مسجلة لهذا الحساب خلال الفترة المحددة.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="bg-[var(--bg-surface-alt)] font-bold border-t-2 border-[var(--border-color)] text-xs font-numeric">
                            <td colspan="4" class="p-3 text-right">
                                إجمالي الحركات والرصيد الختامي:
                            </td>
                            <td class="p-3 text-center text-[var(--success-text)]">
                                {{ number_format($ledger['total_debit'], 2) }}
                            </td>
                            <td class="p-3 text-center text-[var(--info-text)]">
                                {{ number_format($ledger['total_credit'], 2) }}
                            </td>
                            <td class="p-3 text-center text-base text-[var(--primary)] font-extrabold">
                                {{ number_format($ledger['closing_balance'], 2) }}
                            </td>
                            <td class="p-3 text-center text-[10px] text-[var(--text-muted)]">
                                {{ $ledger['closing_nature'] === 'debit' ? 'مدين' : 'دائن' }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
@endsection
