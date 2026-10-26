@extends('layouts.app')

@section('control_panel')
    <x-control-panel :title="'تقرير مسحوبات وأصناف العميل: ' . $customer->name" :breadcrumbs="['العملاء والموردون' => route('customers.index'), 'دليل العملاء' => route('customers.index'), $customer->name => route('customers.show', $customer), 'مسحوبات الأصناف' => route('customers.purchased-items', $customer)]">
        <x-slot:actions>
            <a href="{{ route('customers.show', $customer) }}" class="erp-btn erp-btn-secondary">
                <x-icon name="arrow-right" size="16" />
                <span>العودة لملف العميل</span>
            </a>

            <a href="{{ route('customers.statement', $customer) }}" class="erp-btn erp-btn-secondary">
                <x-icon name="file-text" size="16" />
                <span>كشف الحساب المالي</span>
            </a>

            <!-- Compact Print Dropdown -->
            <div class="erp-dropdown">
                <button type="button" class="erp-btn erp-btn-primary" data-dropdown-toggle="purchasedItemsPrintMenu">
                    <x-icon name="printer" size="16" />
                    <span>طباعة تقرير المسحوبات</span>
                    <x-icon name="chevron-down" size="14" />
                </button>
                <div id="purchasedItemsPrintMenu" class="erp-dropdown-menu">
                    <a href="{{ route('customers.purchased-items.print', [$customer, 'format' => 'a4', 'from_date' => $fromDate, 'to_date' => $toDate, 'category_id' => $categoryId, 'search' => $search]) }}" target="_blank" class="erp-dropdown-item">
                        <x-icon name="file-text" size="16" />
                        <span>طباعة A4 قياسي</span>
                    </a>
                    <a href="{{ route('customers.purchased-items.print', [$customer, 'format' => 'a5', 'from_date' => $fromDate, 'to_date' => $toDate, 'category_id' => $categoryId, 'search' => $search]) }}" target="_blank" class="erp-dropdown-item">
                        <x-icon name="file" size="16" />
                        <span>طباعة A5 وسط</span>
                    </a>
                    <a href="{{ route('customers.purchased-items.print', [$customer, 'format' => 'thermal_80mm', 'from_date' => $fromDate, 'to_date' => $toDate, 'category_id' => $categoryId, 'search' => $search]) }}" target="_blank" class="erp-dropdown-item">
                        <x-icon name="printer" size="16" />
                        <span>طباعة 80 مم حراري</span>
                    </a>
                </div>
            </div>
        </x-slot:actions>

        <x-slot:extra>
            <span class="erp-badge erp-badge-neutral text-xs">
                فئة العميل: {{ $customer->getPricingTierLabel() }}
            </span>
        </x-slot:extra>
    </x-control-panel>
@endsection

@section('content')
    <div data-page="customer-purchased-items">
        <!-- Search & Filter Card -->
        <div class="erp-card mb-6">
            <div class="erp-card-body p-4">
                <form method="GET" action="{{ route('customers.purchased-items', $customer) }}" class="flex flex-wrap items-end gap-3">
                    <div class="flex-1 min-w-[180px]">
                        <label class="block text-xs font-semibold text-[var(--text-muted)] mb-1">بحث بالاسم أو الكود / الباركود</label>
                        <input type="text" name="search" value="{{ $search }}" placeholder="ابحث عن صنف..." class="erp-input w-full">
                    </div>

                    <div class="w-48">
                        <label class="block text-xs font-semibold text-[var(--text-muted)] mb-1">تصنيف الصنف</label>
                        <select name="category_id" class="erp-input w-full">
                            <option value="">جميع التصنيفات</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ $categoryId == $cat->id ? 'selected' : '' }}>
                                    {{ $cat->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="w-40">
                        <label class="block text-xs font-semibold text-[var(--text-muted)] mb-1">من تاريخ</label>
                        <input type="date" name="from_date" value="{{ $fromDate }}" class="erp-input w-full">
                    </div>

                    <div class="w-40">
                        <label class="block text-xs font-semibold text-[var(--text-muted)] mb-1">إلى تاريخ</label>
                        <input type="date" name="to_date" value="{{ $toDate }}" class="erp-input w-full">
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="submit" class="erp-btn erp-btn-primary">
                            <x-icon name="filter" size="16" />
                            <span>تصفية</span>
                        </button>
                        @if($search || $categoryId || $fromDate || $toDate)
                            <a href="{{ route('customers.purchased-items', $customer) }}" class="erp-btn erp-btn-secondary">
                                <x-icon name="x" size="14" />
                                <span>إعادة ضبط</span>
                            </a>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        <!-- Summary KPIs -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div class="erp-card p-4 text-center">
                <div class="text-xs text-[var(--text-muted)] font-medium mb-1">إجمالي عدد البنود</div>
                <div class="font-numeric font-bold text-xl text-[var(--text-main)]">
                    {{ $report['items_count'] }} بند
                </div>
            </div>

            <div class="erp-card p-4 text-center">
                <div class="text-xs text-[var(--text-muted)] font-medium mb-1">إجمالي الكميات المسحوبة</div>
                <div class="font-numeric font-bold text-xl text-[var(--primary)]">
                    {{ number_format($report['total_quantity'], 2) }}
                </div>
            </div>

            <div class="erp-card p-4 text-center">
                <div class="text-xs text-[var(--text-muted)] font-medium mb-1">إجمالي قيمة المسحوبات</div>
                <div class="font-numeric font-black text-2xl text-[var(--text-main)]">
                    {{ number_format($report['total_spend'], 2) }} ج.م
                </div>
            </div>

            <div class="erp-card p-4 text-center">
                <div class="text-xs text-[var(--text-muted)] font-medium mb-1">متوسط سعر الوحدة</div>
                <div class="font-numeric font-bold text-xl text-[var(--success)]">
                    {{ $report['total_quantity'] > 0 ? number_format($report['total_spend'] / $report['total_quantity'], 2) : '0.00' }} ج.م
                </div>
            </div>
        </div>

        <!-- Items Table -->
        <div class="erp-card">
            <div class="erp-card-header flex items-center justify-between">
                <div class="erp-card-title flex items-center gap-2">
                    <x-icon name="shopping-bag" size="18" class="text-[var(--primary)]" />
                    <span>تفاصيل الأصناف والمسحوبات مع مقارنة فئات الأسعار القياسية</span>
                </div>
                <span class="erp-badge erp-badge-neutral text-xs font-numeric">
                    {{ $report['items_count'] }} بند مسجل
                </span>
            </div>

            <div class="erp-card-body p-0">
                <div class="erp-table-responsive">
                    <table class="erp-table">
                        <thead>
                            <tr>
                                <th class="w-10 text-center">#</th>
                                <th class="text-center">التاريخ</th>
                                <th class="text-center">الفاتورة</th>
                                <th>اسم الصنف / الكود والباركود</th>
                                <th class="text-center">التصنيف</th>
                                <th class="text-center">الكمية والوحدة</th>
                                <th class="text-center">سعر البيع الفعلي</th>
                                <th class="text-center">سعر الفئة القياسي</th>
                                <th class="text-center">فارق السعر</th>
                                <th class="text-center">الإجمالي (ج.م)</th>
                                <th class="w-16 text-center">الفاتورة</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($report['items'] as $index => $item)
                                <tr>
                                    <td class="text-center font-numeric text-xs text-[var(--text-muted)]">
                                        {{ $index + 1 }}
                                    </td>
                                    <td class="text-center text-xs font-numeric">
                                        {{ $item['invoice_date'] }}
                                    </td>
                                    <td class="text-center font-numeric font-bold text-xs">
                                        <a href="{{ $item['url'] }}" class="text-[var(--primary)] hover:underline">
                                            {{ $item['invoice_number'] }}
                                        </a>
                                    </td>
                                    <td>
                                        <strong class="text-sm font-semibold text-[var(--text-main)] block">{{ $item['product_name'] }}</strong>
                                        <span class="text-xs font-numeric text-[var(--text-muted)]">
                                            كود: {{ $item['product_sku'] }}
                                            @if($item['product_barcode'])
                                                · باركود: {{ $item['product_barcode'] }}
                                            @endif
                                        </span>
                                    </td>
                                    <td class="text-center text-xs">
                                        <span class="erp-badge erp-badge-neutral text-xs">{{ $item['category_name'] }}</span>
                                    </td>
                                    <td class="text-center font-numeric font-bold text-xs">
                                        {{ number_format($item['quantity'], 2) }} {{ $item['unit_name'] }}
                                    </td>
                                    <td class="text-center font-numeric font-bold text-xs">
                                        {{ number_format($item['unit_price'], 2) }} ج.م
                                    </td>
                                    <td class="text-center font-numeric text-xs text-[var(--text-muted)]">
                                        {{ $item['tier_benchmark'] > 0 ? number_format($item['tier_benchmark'], 2) . ' ج.م' : '—' }}
                                    </td>
                                    <td class="text-center font-numeric text-xs">
                                        @if($item['diff_vs_tier'] < 0)
                                            <span class="text-[var(--success)] font-semibold" title="خصم عن سعر الفئة القياسي">
                                                {{ number_format($item['diff_vs_tier'], 2) }}
                                            </span>
                                        @elseif($item['diff_vs_tier'] > 0)
                                            <span class="text-[var(--danger)] font-semibold" title="زيادة عن سعر الفئة">
                                                +{{ number_format($item['diff_vs_tier'], 2) }}
                                            </span>
                                        @else
                                            <span class="text-[var(--text-muted)]">مطابق</span>
                                        @endif
                                    </td>
                                    <td class="text-center font-numeric font-black text-xs">
                                        {{ number_format($item['subtotal'], 2) }} ج.م
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ $item['url'] }}" class="erp-btn erp-btn-ghost erp-btn-xs" title="عرض الفاتورة">
                                            <x-icon name="eye" size="14" />
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="11" class="text-center py-8 text-[var(--text-muted)]">
                                        لا توجد أصناف مطابقة لخيارات التصفية المسجلة لهذا العميل
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if($report['items_count'] > 0)
                            <tfoot>
                                <tr class="bg-[var(--bg-surface-alt)] font-bold">
                                    <td colspan="5" class="text-start py-3 px-4">الإجمالي العام لمسحوبات العميل:</td>
                                    <td class="text-center font-numeric text-[var(--primary)]">{{ number_format($report['total_quantity'], 2) }}</td>
                                    <td colspan="3"></td>
                                    <td class="text-center font-numeric font-black text-sm text-[var(--text-main)]">
                                        {{ number_format($report['total_spend'], 2) }} ج.م
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
