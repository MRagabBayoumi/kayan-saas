@extends('layouts.app')

@section('control_panel')
    <x-control-panel title="تقرير أرصدة ومستحقات الموردين (Balance Due)" :breadcrumbs="['المشتريات والمخازن' => route('purchases.index'), 'دليل الموردين' => route('suppliers.index'), 'تقرير أرصدة الموردين' => route('suppliers.balance-due-report')]">
        <x-slot:actions>
            <a href="{{ route('suppliers.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="arrow-right" size="16" />
                <span>العودة لقائمة الموردين</span>
            </a>

            <!-- Compact Print Dropdown -->
            <div class="erp-dropdown">
                <button type="button" class="erp-btn erp-btn-primary" data-dropdown-toggle="balanceDuePrintMenu">
                    <x-icon name="printer" size="16" />
                    <span>طباعة التقرير</span>
                    <x-icon name="chevron-down" size="14" />
                </button>
                <div id="balanceDuePrintMenu" class="erp-dropdown-menu">
                    <a href="{{ route('suppliers.balance-due-report.print', ['format' => 'a4', 'currency' => $currency, 'supplier_id' => $supplierId]) }}" target="_blank" class="erp-dropdown-item">
                        <x-icon name="file-text" size="16" />
                        <span>طباعة A4 قياسي</span>
                    </a>
                    <a href="{{ route('suppliers.balance-due-report.print', ['format' => 'a5', 'currency' => $currency, 'supplier_id' => $supplierId]) }}" target="_blank" class="erp-dropdown-item">
                        <x-icon name="file" size="16" />
                        <span>طباعة A5 وسط</span>
                    </a>
                    <a href="{{ route('suppliers.balance-due-report.print', ['format' => 'thermal_80mm', 'currency' => $currency, 'supplier_id' => $supplierId]) }}" target="_blank" class="erp-dropdown-item">
                        <x-icon name="printer" size="16" />
                        <span>طباعة 80 مم حراري</span>
                    </a>
                </div>
            </div>
        </x-slot:actions>

        <x-slot:extra>
            <span class="erp-badge erp-badge-warning text-xs font-numeric">
                إجمالي الموردين: {{ $report['total_suppliers_count'] }}
            </span>
        </x-slot:extra>
    </x-control-panel>
@endsection

@section('content')
    <div data-page="supplier-balance-due">
        <!-- Filter Card -->
        <div class="erp-card mb-6">
            <div class="erp-card-body p-4">
                <form method="GET" action="{{ route('suppliers.balance-due-report') }}" class="flex flex-wrap items-end gap-3">
                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-xs font-semibold text-[var(--text-muted)] mb-1">المورد / المصنع</label>
                        <select name="supplier_id" class="erp-input w-full">
                            <option value="">جميع الموردين</option>
                            @foreach($allSuppliers as $sup)
                                <option value="{{ $sup->id }}" {{ $supplierId == $sup->id ? 'selected' : '' }}>
                                    {{ $sup->name }} ({{ $sup->currency }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="w-48">
                        <label class="block text-xs font-semibold text-[var(--text-muted)] mb-1">عملة الحساب</label>
                        <select name="currency" class="erp-input w-full">
                            <option value="">جميع العملات</option>
                            <option value="CNY" {{ $currency === 'CNY' ? 'selected' : '' }}>اليوان الصيني (CNY)</option>
                            <option value="EGP" {{ $currency === 'EGP' ? 'selected' : '' }}>الجنيه المصري (EGP)</option>
                            <option value="USD" {{ $currency === 'USD' ? 'selected' : '' }}>الدولار الأمريكي (USD)</option>
                        </select>
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="submit" class="erp-btn erp-btn-primary">
                            <x-icon name="filter" size="16" />
                            <span>تصفية</span>
                        </button>
                        @if($supplierId || $currency)
                            <a href="{{ route('suppliers.balance-due-report') }}" class="erp-btn erp-btn-secondary">
                                <x-icon name="x" size="14" />
                                <span>إعادة ضبط</span>
                            </a>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        <!-- Totals by Currency KPIs -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            @foreach(['CNY' => 'اليوان الصيني', 'EGP' => 'الجنيه المصري', 'USD' => 'الدولار الأمريكي'] as $currCode => $currName)
                <div class="erp-card p-4 text-center">
                    <div class="text-xs text-[var(--text-muted)] font-medium mb-1">إجمالي المستحق ({{ $currName }})</div>
                    <div class="font-numeric font-black text-2xl text-[var(--danger)]">
                        {{ number_format($report['totals_by_currency'][$currCode] ?? 0.00, 2) }}
                        <span class="text-xs font-semibold text-[var(--text-muted)]">{{ $currCode }}</span>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Suppliers Table -->
        <div class="erp-card">
            <div class="erp-card-header flex items-center justify-between">
                <div class="erp-card-title flex items-center gap-2">
                    <x-icon name="briefcase" size="18" class="text-[var(--primary)]" />
                    <span>كشف أرصدة ومستحقات الموردين والمصانع</span>
                </div>
                <span class="erp-badge erp-badge-neutral text-xs font-numeric">
                    {{ $report['total_suppliers_count'] }} مورد مسجل
                </span>
            </div>

            <div class="erp-card-body p-0">
                <div class="erp-table-responsive">
                    <table class="erp-table">
                        <thead>
                            <tr>
                                <th class="w-12 text-center">#</th>
                                <th>اسم المورد / المصنع</th>
                                <th class="text-center">النوع والتصنيف</th>
                                <th class="text-center">الهاتف / الاتصال</th>
                                <th class="text-center">الدولة والمدينة</th>
                                <th class="text-center">آخر توريد</th>
                                <th class="text-center">العملة</th>
                                <th class="text-center">الرصيد المستحق</th>
                                <th class="w-24 text-center">إجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($report['rows'] as $index => $row)
                                <tr>
                                    <td class="text-center font-numeric text-xs text-[var(--text-muted)]">
                                        {{ $index + 1 }}
                                    </td>
                                    <td>
                                        <a href="{{ route('suppliers.show', $row['id']) }}" class="font-bold text-[var(--text-main)] hover:text-[var(--primary)] hover:underline block">
                                            {{ $row['name'] }}
                                        </a>
                                        @if($row['contact_person'] && $row['contact_person'] !== '—')
                                            <span class="text-xs text-[var(--text-muted)]">المسؤول: {{ $row['contact_person'] }}</span>
                                        @endif
                                    </td>
                                    <td class="text-center text-xs">
                                        <span class="erp-badge erp-badge-neutral text-xs">{{ $row['type_label'] }}</span>
                                    </td>
                                    <td class="text-center font-numeric text-xs">
                                        {{ $row['phone'] }}
                                    </td>
                                    <td class="text-center text-xs text-[var(--text-muted)]">
                                        {{ $row['supplier']->country }} {{ $row['supplier']->city ? '- ' . $row['supplier']->city : '' }}
                                    </td>
                                    <td class="text-center text-xs font-numeric">
                                        @if($row['last_purchase_date'])
                                            <div>{{ $row['last_purchase_date'] }}</div>
                                            <div class="text-[10px] text-[var(--text-muted)]">{{ $row['last_purchase_number'] }}</div>
                                        @else
                                            <span class="text-[var(--text-muted)]">—</span>
                                        @endif
                                    </td>
                                    <td class="text-center font-bold text-xs font-numeric">
                                        {{ $row['currency'] }}
                                    </td>
                                    <td class="text-center font-numeric font-black text-sm text-[var(--danger)]">
                                        {{ number_format($row['current_balance'], 2) }}
                                    </td>
                                    <td class="text-center">
                                        <div class="flex items-center justify-center gap-1">
                                            <a href="{{ route('suppliers.statement', $row['id']) }}" class="erp-btn erp-btn-ghost erp-btn-xs" title="كشف الحساب">
                                                <x-icon name="file-text" size="14" />
                                            </a>
                                            <a href="{{ route('suppliers.show', $row['id']) }}" class="erp-btn erp-btn-ghost erp-btn-xs" title="ملف المورد">
                                                <x-icon name="eye" size="14" />
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center py-8 text-[var(--text-muted)]">
                                        لا توجد بيانات موردين مطابقة لخيارات التصفية
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
