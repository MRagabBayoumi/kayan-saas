@extends('layouts.app')

@section('control_panel')
    <x-control-panel title="تقرير أرصدة ومديونيات العملاء (حسابات العملاء)" :breadcrumbs="['المبيعات والعملاء' => route('customers.index'), 'دليل العملاء' => route('customers.index'), 'تقرير أرصدة العملاء' => route('customers.balance-due-report')]">
        <x-slot:actions>
            <a href="{{ route('customers.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="arrow-right" size="16" />
                <span>العودة لقائمة العملاء</span>
            </a>

            <!-- Compact Print Dropdown -->
            <div class="erp-dropdown">
                <button type="button" class="erp-btn erp-btn-primary" data-dropdown-toggle="balanceDuePrintMenu">
                    <x-icon name="printer" size="16" />
                    <span>طباعة التقرير</span>
                    <x-icon name="chevron-down" size="14" />
                </button>
                <div id="balanceDuePrintMenu" class="erp-dropdown-menu">
                    <a href="{{ route('customers.balance-due-report.print', array_merge(request()->query(), ['format' => 'a4'])) }}" target="_blank" class="erp-dropdown-item">
                        <x-icon name="file-text" size="16" />
                        <span>طباعة A4 قياسي</span>
                    </a>
                    <a href="{{ route('customers.balance-due-report.print', array_merge(request()->query(), ['format' => 'a5'])) }}" target="_blank" class="erp-dropdown-item">
                        <x-icon name="file" size="16" />
                        <span>طباعة A5 وسط</span>
                    </a>
                    <a href="{{ route('customers.balance-due-report.print', array_merge(request()->query(), ['format' => 'thermal_80mm'])) }}" target="_blank" class="erp-dropdown-item">
                        <x-icon name="printer" size="16" />
                        <span>طباعة 80 مم حراري</span>
                    </a>
                </div>
            </div>
        </x-slot:actions>

        <x-slot:extra>
            <span class="erp-badge erp-badge-warning text-xs font-numeric">
                إجمالي المديونيات: {{ number_format($report['total_receivables'], 2) }} ج.م
            </span>
        </x-slot:extra>
    </x-control-panel>
@endsection

@section('content')
    <div data-page="customer-balance-due">
        <!-- Filter Card -->
        <div class="erp-card mb-6">
            <div class="erp-card-body p-4">
                <form method="GET" action="{{ route('customers.balance-due-report') }}" class="flex flex-wrap items-end gap-3">
                    <div class="flex-1 min-w-[180px]">
                        <label class="block text-xs font-semibold text-[var(--text-muted)] mb-1">بحث بالاسم أو الهاتف</label>
                        <input type="text" name="search" value="{{ $search }}" placeholder="ابحث باسم العميل أو الشركة..." class="erp-input w-full">
                    </div>

                    <div class="w-48">
                        <label class="block text-xs font-semibold text-[var(--text-muted)] mb-1">نوع العميل</label>
                        <select name="type" class="erp-input w-full">
                            <option value="">جميع الفئات</option>
                            <option value="wholesale" {{ $customerType === 'wholesale' ? 'selected' : '' }}>جملة</option>
                            <option value="retail" {{ $customerType === 'retail' ? 'selected' : '' }}>تجزئة</option>
                            <option value="distributor" {{ $customerType === 'distributor' ? 'selected' : '' }}>موزع</option>
                        </select>
                    </div>

                    @if($cities->isNotEmpty())
                    <div class="w-40">
                        <label class="block text-xs font-semibold text-[var(--text-muted)] mb-1">المدينة / المحافظة</label>
                        <select name="city" class="erp-input w-full">
                            <option value="">جميع المدن</option>
                            @foreach($cities as $c)
                                <option value="{{ $c }}" {{ $city === $c ? 'selected' : '' }}>{{ $c }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    @if($salesReps->isNotEmpty())
                    <div class="w-44">
                        <label class="block text-xs font-semibold text-[var(--text-muted)] mb-1">المندوب المسؤول</label>
                        <select name="sales_rep_id" class="erp-input w-full">
                            <option value="">جميع المناديب</option>
                            @foreach($salesReps as $rep)
                                <option value="{{ $rep->id }}" {{ $salesRepId == $rep->id ? 'selected' : '' }}>{{ $rep->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    <div class="flex items-center gap-2 pt-2">
                        <label class="flex items-center gap-1.5 cursor-pointer text-xs font-semibold text-[var(--text-main)]">
                            <input type="checkbox" name="only_with_balance" value="1" {{ $onlyWithBalance ? 'checked' : '' }} class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                            <span>المدينين فقط (عليهم مبالغ)</span>
                        </label>
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="submit" class="erp-btn erp-btn-primary">
                            <x-icon name="filter" size="16" />
                            <span>تصفية</span>
                        </button>
                        @if($search || $customerType || $city || $salesRepId || $onlyWithBalance)
                            <a href="{{ route('customers.balance-due-report') }}" class="erp-btn erp-btn-secondary">
                                <x-icon name="x" size="14" />
                                <span>إعادة ضبط</span>
                            </a>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        <!-- KPIs Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="erp-card p-4 text-center">
                <div class="text-xs text-[var(--text-muted)] font-medium mb-1">إجمالي المديونيات المستحقة على العملاء</div>
                <div class="font-numeric font-black text-2xl text-[var(--danger)]">
                    {{ number_format($report['total_receivables'], 2) }}
                    <span class="text-xs font-semibold text-[var(--text-muted)]">ج.م</span>
                </div>
            </div>

            <div class="erp-card p-4 text-center">
                <div class="text-xs text-[var(--text-muted)] font-medium mb-1">عدد العملاء المطالبين بالسداد</div>
                <div class="font-numeric font-black text-2xl text-amber-600">
                    {{ number_format($report['customers_with_balance_count']) }}
                    <span class="text-xs font-semibold text-[var(--text-muted)]">عميل مدين</span>
                </div>
            </div>

            <div class="erp-card p-4 text-center">
                <div class="text-xs text-[var(--text-muted)] font-medium mb-1">إجمالي العملاء المعروضين بالتقرير</div>
                <div class="font-numeric font-black text-2xl text-[var(--primary)]">
                    {{ number_format($report['total_customers_count']) }}
                    <span class="text-xs font-semibold text-[var(--text-muted)]">عميل</span>
                </div>
            </div>
        </div>

        <!-- Customers Table -->
        <div class="erp-card">
            <div class="erp-card-header flex items-center justify-between">
                <div class="erp-card-title flex items-center gap-2">
                    <x-icon name="users" size="18" class="text-[var(--primary)]" />
                    <span>كشف أرصدة وحسابات العملاء (كل عميل عليه كام)</span>
                </div>
                <span class="erp-badge erp-badge-neutral text-xs font-numeric">
                    {{ $report['total_customers_count'] }} عميل
                </span>
            </div>

            <div class="erp-card-body p-0">
                <div class="erp-table-responsive">
                    <table class="erp-table">
                        <thead>
                            <tr>
                                <th class="w-12 text-center">#</th>
                                <th>اسم العميل / النشاط</th>
                                <th class="text-center">الفئة</th>
                                <th class="text-center">الهاتف</th>
                                <th class="text-center">المدينة</th>
                                <th class="text-center">المندوب</th>
                                <th class="text-center">آخر فاتورة</th>
                                <th class="text-center">آخر سداد</th>
                                <th class="text-center">الرصيد المستحق (عليه كام)</th>
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
                                        <a href="{{ route('customers.show', $row['id']) }}" class="font-bold text-[var(--text-main)] hover:text-[var(--primary)] hover:underline block">
                                            {{ $row['name'] }}
                                        </a>
                                        @if($row['company_name'] && $row['company_name'] !== '—')
                                            <span class="text-xs text-[var(--text-muted)]">{{ $row['company_name'] }}</span>
                                        @endif
                                    </td>
                                    <td class="text-center text-xs">
                                        <span class="erp-badge erp-badge-neutral text-xs">{{ $row['type_label'] }}</span>
                                    </td>
                                    <td class="text-center font-numeric text-xs">
                                        {{ $row['phone'] }}
                                    </td>
                                    <td class="text-center text-xs text-[var(--text-muted)]">
                                        {{ $row['city'] }}
                                    </td>
                                    <td class="text-center text-xs">
                                        {{ $row['sales_rep_name'] }}
                                    </td>
                                    <td class="text-center text-xs font-numeric">
                                        @if($row['last_sale_date'])
                                            <div>{{ $row['last_sale_date'] }}</div>
                                            <div class="text-[10px] text-[var(--text-muted)]">{{ number_format($row['last_sale_amount'], 2) }} ج.م</div>
                                        @else
                                            <span class="text-[var(--text-muted)]">—</span>
                                        @endif
                                    </td>
                                    <td class="text-center text-xs font-numeric">
                                        @if($row['last_payment_date'])
                                            <div>{{ $row['last_payment_date'] }}</div>
                                            <div class="text-[10px] text-emerald-600 font-bold">{{ number_format($row['last_payment_amount'], 2) }} ج.م</div>
                                        @else
                                            <span class="text-[var(--text-muted)]">—</span>
                                        @endif
                                    </td>
                                    <td class="text-center font-numeric font-black text-sm">
                                        @if($row['current_balance'] > 0)
                                            <span class="text-[var(--danger)]">
                                                {{ number_format($row['current_balance'], 2) }} ج.م
                                            </span>
                                            <div class="text-[10px] text-[var(--danger)] font-normal">مدين (مستحق عليه)</div>
                                        @elseif($row['current_balance'] < 0)
                                            <span class="text-emerald-600">
                                                {{ number_format(abs($row['current_balance']), 2) }} ج.م
                                            </span>
                                            <div class="text-[10px] text-emerald-600 font-normal">دائن (رصيد له)</div>
                                        @else
                                            <span class="text-[var(--text-muted)]">0.00 ج.م</span>
                                            <div class="text-[10px] text-[var(--text-muted)] font-normal">خالص الحساب</div>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="flex items-center justify-center gap-1">
                                            <a href="{{ route('customers.statement', $row['id']) }}" class="erp-btn erp-btn-ghost erp-btn-xs" title="كشف الحساب التفصيلي">
                                                <x-icon name="file-text" size="14" />
                                            </a>
                                            <a href="{{ route('customers.show', $row['id']) }}" class="erp-btn erp-btn-ghost erp-btn-xs" title="ملف العميل">
                                                <x-icon name="eye" size="14" />
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center py-8 text-[var(--text-muted)]">
                                        لا توجد بيانات عملاء مطابقة لخيارات التصفية
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if(!empty($report['rows']))
                            <tfoot>
                                <tr class="bg-slate-50 dark:bg-slate-900/50 font-bold">
                                    <td colspan="8" class="text-left pl-4">إجمالي مديونيات العملاء المستحقة:</td>
                                    <td class="text-center font-numeric font-black text-base text-[var(--danger)]">
                                        {{ number_format($report['total_receivables'], 2) }} ج.م
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
