@extends('layouts.app')

@section('control_panel')
    <x-control-panel title="دفتر قيود اليومية العامة (General Journal)" :breadcrumbs="['المالية والحسابات' => route('treasuries.index'), 'شجرة الحسابات' => route('accounting.chart-of-accounts'), 'دفتر القيود' => route('accounting.journal-entries')]">
        <x-slot:actions>
            <a href="{{ route('accounting.chart-of-accounts') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="list" size="16" />
                <span>شجرة الحسابات</span>
            </a>
            <form action="{{ route('accounting.recalculate-balances') }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="erp-btn erp-btn-secondary" title="إعادة احتساب وتحديث مجاميع الأرصدة في الشجرة بالكامل">
                    <x-icon name="refresh-cw" size="16" />
                    <span>تحديث أرصدة الشجرة</span>
                </button>
            </form>
        </x-slot:actions>

        <x-slot:extra>
            <span class="erp-badge erp-badge-success font-numeric px-3 py-1 text-xs">
                <span>إجمالي القيود المرحلة:</span>
                <strong class="mr-1">{{ $metrics['total_posted'] }} قيد</strong>
            </span>
        </x-slot:extra>
    </x-control-panel>
@endsection

@section('content')
    <div data-page="journal-entries">
        @include('accounting.nav-tabs')

        @if(session('message'))
            <div class="erp-alert erp-alert-success mb-4" role="alert">
                <x-icon name="check-circle" size="18" />
                <div>{{ session('message') }}</div>
            </div>
        @endif

        <!-- Summary KPIs -->
        <div class="finance-kpi-grid">
            <div class="finance-kpi-card">
                <div class="finance-kpi-icon primary">
                    <x-icon name="file-text" size="22" />
                </div>
                <div class="finance-kpi-info">
                    <div class="finance-kpi-label">إجمالي القيود المسجلة</div>
                    <div class="finance-kpi-value font-numeric text-[var(--text-main)]">{{ number_format($metrics['total_entries']) }}</div>
                    <div class="finance-kpi-sub">سجلات القيود المحاسبية</div>
                </div>
            </div>

            <div class="finance-kpi-card">
                <div class="finance-kpi-icon success">
                    <x-icon name="trending-up" size="22" />
                </div>
                <div class="finance-kpi-info">
                    <div class="finance-kpi-label">إجمالي حركة المدين (Debit)</div>
                    <div class="finance-kpi-value font-numeric text-[var(--success-text)]">
                        {{ number_format($metrics['sum_debit'], 2) }} <span class="text-xs font-normal text-[var(--text-muted)]">ج.م</span>
                    </div>
                    <div class="finance-kpi-sub">الحركات المدينة المقيدة</div>
                </div>
            </div>

            <div class="finance-kpi-card">
                <div class="finance-kpi-icon info">
                    <x-icon name="trending-down" size="22" />
                </div>
                <div class="finance-kpi-info">
                    <div class="finance-kpi-label">إجمالي حركة الدائن (Credit)</div>
                    <div class="finance-kpi-value font-numeric text-[var(--info-text)]">
                        {{ number_format($metrics['sum_credit'], 2) }} <span class="text-xs font-normal text-[var(--text-muted)]">ج.م</span>
                    </div>
                    <div class="finance-kpi-sub">الحركات الدائنة المقيدة</div>
                </div>
            </div>

            <div class="finance-kpi-card">
                <div class="finance-kpi-icon {{ abs($metrics['sum_debit'] - $metrics['sum_credit']) < 0.01 ? 'success' : 'danger' }}">
                    <x-icon name="{{ abs($metrics['sum_debit'] - $metrics['sum_credit']) < 0.01 ? 'check-circle' : 'alert-triangle' }}" size="22" />
                </div>
                <div class="finance-kpi-info">
                    <div class="finance-kpi-label">التوازن المحاسبي العام</div>
                    <div class="finance-kpi-value font-numeric {{ abs($metrics['sum_debit'] - $metrics['sum_credit']) < 0.01 ? 'text-[var(--success-text)]' : 'text-[var(--danger)]' }}">
                        @if(abs($metrics['sum_debit'] - $metrics['sum_credit']) < 0.01)
                            متزن 100%
                        @else
                            {{ number_format(abs($metrics['sum_debit'] - $metrics['sum_credit']), 2) }} <span class="text-xs font-normal">ج.م</span>
                        @endif
                    </div>
                    <div class="finance-kpi-sub">
                        {{ abs($metrics['sum_debit'] - $metrics['sum_credit']) < 0.01 ? 'تطابق تام للمدين والدائن' : 'يوجد فارق يحتاج مراجعة' }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Search & Filter Card -->
        <div class="finance-filter-bar mb-4">
            <form action="{{ route('accounting.journal-entries') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 items-end">
                <!-- Search term -->
                <div>
                    <label class="block text-xs font-semibold mb-1 text-[var(--text-muted)]">بحث بالقيد أو الوصف أو الحساب</label>
                    <input type="text" name="search" value="{{ $currentSearch }}" placeholder="رقم القيد، البيان، اسم الحساب..." class="finance-filter-input w-full text-xs">
                </div>

                <!-- Reference Type -->
                <div>
                    <label class="block text-xs font-semibold mb-1 text-[var(--text-muted)]">نوع المستند المصدري</label>
                    <select name="reference_type" class="finance-filter-input w-full text-xs">
                        <option value="">-- كافة المستندات --</option>
                        <option value="sales_invoice" {{ $currentRefType === 'sales_invoice' ? 'selected' : '' }}>فاتورة مبيعات</option>
                        <option value="sales_return" {{ $currentRefType === 'sales_return' ? 'selected' : '' }}>مردود مبيعات</option>
                        <option value="purchase_invoice" {{ $currentRefType === 'purchase_invoice' ? 'selected' : '' }}>فاتورة مشتريات</option>
                        <option value="purchase_return" {{ $currentRefType === 'purchase_return' ? 'selected' : '' }}>مردود مشتريات</option>
                        <option value="customer_payment" {{ $currentRefType === 'customer_payment' ? 'selected' : '' }}>سند قبض عميل</option>
                        <option value="supplier_payment" {{ $currentRefType === 'supplier_payment' ? 'selected' : '' }}>سند صرف مورد</option>
                        <option value="expense" {{ $currentRefType === 'expense' ? 'selected' : '' }}>سند مصروفات</option>
                        <option value="opening_balance" {{ $currentRefType === 'opening_balance' ? 'selected' : '' }}>رصيد افتتاحي</option>
                        <option value="manual" {{ $currentRefType === 'manual' ? 'selected' : '' }}>قيد تسوية / يدوي</option>
                    </select>
                </div>

                <!-- Date From -->
                <div>
                    <label class="block text-xs font-semibold mb-1 text-[var(--text-muted)]">من تاريخ</label>
                    <input type="date" name="date_from" value="{{ $currentDateFrom }}" class="finance-filter-input w-full text-xs font-numeric">
                </div>

                <!-- Date To -->
                <div>
                    <label class="block text-xs font-semibold mb-1 text-[var(--text-muted)]">إلى تاريخ</label>
                    <input type="date" name="date_to" value="{{ $currentDateTo }}" class="finance-filter-input w-full text-xs font-numeric">
                </div>

                <!-- Actions -->
                <div class="flex items-center gap-2">
                    <button type="submit" class="erp-btn erp-btn-primary text-xs flex-1 justify-center">
                        <x-icon name="search" size="14" />
                        <span>تصفية</span>
                    </button>
                    <a href="{{ route('accounting.journal-entries') }}" class="erp-btn erp-btn-secondary text-xs" title="إلغاء التصفية">
                        <x-icon name="rotate-ccw" size="14" />
                    </a>
                </div>
            </form>
        </div>

        <!-- Journal Entries Table -->
        <div class="erp-card overflow-hidden">
            <div class="erp-table-responsive">
                <table class="erp-table erp-table-hover text-xs">
                    <thead>
                        <tr>
                            <th class="w-12 text-center">#</th>
                            <th class="w-32">رقم القيد</th>
                            <th class="w-28">التاريخ</th>
                            <th class="w-36">المستند المصدري</th>
                            <th>البيان والتفاصيل</th>
                            <th class="text-left font-numeric w-32">إجمالي المدين</th>
                            <th class="text-left font-numeric w-32">إجمالي الدائن</th>
                            <th class="text-center w-28">الحالة</th>
                            <th class="text-center w-16">السطور</th>
                            <th class="w-36 text-center">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($entries as $index => $entry)
                            <tr class="erp-table-row">
                                <td class="text-center text-[var(--text-muted)] font-numeric">{{ $entries->firstItem() + $index }}</td>
                                <td>
                                    <a href="{{ route('accounting.journal-entries.show', $entry) }}" 
                                       class="font-mono font-bold text-[var(--primary)] hover:underline inline-block">
                                        {{ $entry->entry_number }}
                                    </a>
                                </td>
                                <td class="font-numeric text-[var(--text-secondary)] whitespace-nowrap">
                                    {{ $entry->entry_date ? $entry->entry_date->format('Y-m-d') : '—' }}
                                </td>
                                <td>
                                    <span class="erp-badge erp-badge-neutral text-[11px] font-medium">
                                        {{ $entry->reference_type_label }}
                                    </span>
                                </td>
                                <td>
                                    <div class="font-medium text-[var(--text-main)] text-xs leading-relaxed max-w-md">
                                        {{ $entry->description }}
                                    </div>
                                    @if($entry->creator)
                                        <div class="text-[10px] text-[var(--text-muted)] mt-0.5">بواسطة: {{ $entry->creator->name }}</div>
                                    @endif
                                </td>
                                <td class="text-left font-numeric font-bold text-[var(--success-text)] text-xs">
                                    {{ number_format((float) $entry->total_debit, 2) }}
                                </td>
                                <td class="text-left font-numeric font-bold text-[var(--info-text)] text-xs">
                                    {{ number_format((float) $entry->total_credit, 2) }}
                                </td>
                                <td class="text-center">
                                    <span class="erp-badge {{ $entry->status_badge_class }} text-[10px]">
                                        {{ $entry->status_label }}
                                    </span>
                                </td>
                                <td class="text-center font-numeric">
                                    <span class="px-2 py-0.5 rounded-full bg-[var(--bg-surface-alt)] border border-[var(--border-color)] text-[11px] font-bold text-[var(--text-muted)]">
                                        {{ $entry->lines->count() }}
                                    </span>
                                </td>
                                <td class="text-center whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1.5">
                                        <!-- View Full Page -->
                                        <a href="{{ route('accounting.journal-entries.show', $entry) }}" 
                                           class="erp-btn erp-btn-secondary text-xs px-2.5 py-1" 
                                           title="عرض سند القيد في صفحة مستقلة">
                                            <x-icon name="eye" size="14" />
                                            <span>عرض</span>
                                        </a>

                                        <!-- Print Voucher Directly -->
                                        <a href="{{ route('accounting.journal-entries.print', $entry) }}" 
                                           target="_blank"
                                           class="erp-btn erp-btn-secondary text-xs px-2 py-1 text-[var(--primary)] hover:bg-[var(--primary-soft)]" 
                                           title="طباعة سند القيد">
                                            <x-icon name="printer" size="14" />
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-12 text-[var(--text-muted)]">
                                    <x-icon name="file-text" size="40" class="mx-auto mb-2 opacity-30" />
                                    <p class="text-sm font-semibold">لا توجد قيود يومية مسجلة مطابقة لمعايير البحث الحالية.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($entries->hasPages())
                <div class="p-4 border-t border-[var(--border-color)]">
                    {{ $entries->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
