@extends('layouts.app')

@section('control_panel')
    <x-control-panel :title="'مستودع: ' . $warehouse->name" :breadcrumbs="['المستودعات' => route('warehouses.index'), $warehouse->name => route('warehouses.show', $warehouse)]">
        <x-slot:actions>
            <a href="{{ route('warehouses.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="arrow-right" size="16" />
                <span>العودة للمستودعات</span>
            </a>
            <a href="{{ route('inventory.stocktake', ['warehouse_id' => $warehouse->id]) }}" class="erp-btn erp-btn-primary">
                <x-icon name="clipboard-check" size="16" />
                <span>بدء تسوية وجرد</span>
            </a>

            <!-- Compact Print Dropdown -->
            <div class="erp-dropdown">
                <button type="button" class="erp-btn erp-btn-secondary" data-dropdown-toggle="warehouseStockPrintMenu">
                    <x-icon name="printer" size="16" />
                    <span>طباعة التقرير</span>
                    <x-icon name="chevron-down" size="14" />
                </button>
                <div id="warehouseStockPrintMenu" class="erp-dropdown-menu">
                    <a href="{{ route('warehouses.stock.print', [$warehouse, 'format' => 'a4', 'search' => $search, 'category_id' => $categoryId]) }}" target="_blank" class="erp-dropdown-item">
                        <x-icon name="file-text" size="16" />
                        <span>تقرير مخزون A4 قياسي</span>
                    </a>
                    <a href="{{ route('warehouses.stock.print', [$warehouse, 'format' => 'a5', 'search' => $search, 'category_id' => $categoryId]) }}" target="_blank" class="erp-dropdown-item">
                        <x-icon name="file" size="16" />
                        <span>تقرير مخزون A5 وسط</span>
                    </a>
                    <a href="{{ route('warehouses.stock.print', [$warehouse, 'format' => 'thermal_80mm', 'search' => $search, 'category_id' => $categoryId]) }}" target="_blank" class="erp-dropdown-item">
                        <x-icon name="printer" size="16" />
                        <span>تقرير مخزون 80 مم حراري</span>
                    </a>
                </div>
            </div>
        </x-slot:actions>

        <x-slot:extra>
            <span class="erp-badge font-numeric px-2.5 py-1 text-xs {{ $warehouse->is_active ? 'erp-badge-success' : 'erp-badge-danger' }}">
                {{ $warehouse->is_active ? 'مستودع نشط' : 'معطل' }}
            </span>
            @if($warehouse->is_default)
                <span class="erp-badge erp-badge-neutral font-numeric px-2 py-0.5 text-xs">
                    افتراضي للنظام
                </span>
            @endif
        </x-slot:extra>
    </x-control-panel>
@endsection

@section('content')
<div class="flex flex-col gap-6">
    <!-- Warehouse Metrics Overview -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="erp-card p-4">
            <div class="text-xs text-[var(--text-secondary)] font-medium mb-1">كود المستودع</div>
            <div class="text-lg font-bold font-numeric text-[var(--text-main)]">{{ $warehouse->code }}</div>
            <div class="text-xs text-[var(--text-muted)] mt-1">{{ $warehouse->location ?: 'الموقع غير محدد' }}</div>
        </div>

        <div class="erp-card p-4">
            <div class="text-xs text-[var(--text-secondary)] font-medium mb-1">عدد الأصناف المتوفرة</div>
            <div class="text-xl font-bold font-numeric text-[var(--text-main)]">{{ number_format($breakdown['items_count']) }}</div>
            <div class="text-xs text-[var(--text-muted)] mt-1">صنف مسجل بالمستودع</div>
        </div>

        <div class="erp-card p-4">
            <div class="text-xs text-[var(--text-secondary)] font-medium mb-1">إجمالي كمية المخزون (بالوحدات الأساسية)</div>
            <div class="text-xl font-bold font-numeric text-blue-600 dark:text-blue-400">{{ number_format($breakdown['total_quantity'], 2) }}</div>
            <div class="text-xs text-[var(--text-muted)] mt-1">إجمالي وحدات المخزن</div>
        </div>

        <div class="erp-card p-4">
            <div class="text-xs text-[var(--text-secondary)] font-medium mb-1">إجمالي القيمة التقديرية بسعر التكلفة</div>
            <div class="text-xl font-bold font-numeric text-emerald-600 dark:text-emerald-400">{{ number_format($breakdown['total_valuation'], 2) }} ج.م</div>
            <div class="text-xs text-[var(--text-muted)] mt-1">تقييم المخزون الحالي</div>
        </div>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="erp-card p-4">
        <form method="GET" action="{{ route('warehouses.show', $warehouse) }}" class="flex flex-wrap items-center gap-3">
            <div class="flex-1 min-w-[220px]">
                <input 
                    type="text" 
                    name="search" 
                    value="{{ $search }}" 
                    placeholder="ابحث باسم الصنف، الكود SKU، أو الباركود..." 
                    class="erp-input w-full"
                />
            </div>

            <div class="min-w-[180px]">
                <select name="category_id" class="erp-input w-full">
                    <option value="">جميع الأقسام والتصنيفات</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ $categoryId == $category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="erp-btn erp-btn-primary">
                <x-icon name="search" size="14" />
                <span>تصفية</span>
            </button>

            @if($search || $categoryId)
                <a href="{{ route('warehouses.show', $warehouse) }}" class="erp-btn erp-btn-secondary">
                    <x-icon name="x" size="14" />
                    <span>إلغاء الفلتر</span>
                </a>
            @endif
        </form>
    </div>

    <!-- Detailed Inventory Breakdown Table -->
    <div class="erp-card overflow-hidden">
        <div class="p-4 border-b border-[var(--border-color)] flex items-center justify-between">
            <h2 class="text-base font-bold text-[var(--text-main)] flex items-center gap-2">
                <x-icon name="package" size="18" />
                <span>تفاصيل الأرصدة وتجزئة الوحدات وشرائح FIFO</span>
            </h2>
            <span class="text-xs text-[var(--text-secondary)]">
                تم استعراض {{ count($breakdown['items']) }} صنف
            </span>
        </div>

        <div class="table-responsive">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>كود الصنف / الباركود</th>
                        <th>اسم المنتج</th>
                        <th>التصنيف</th>
                        <th>الوحدة الأساسية</th>
                        <th class="text-center">الرصيد الفعلي (الأساسي)</th>
                        <th>تجزئة الطرود والكراتين</th>
                        <th class="text-left">سعر التكلفة</th>
                        <th class="text-left">إجمالي التقييم</th>
                        <th>شرائح وباتشات FIFO</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($breakdown['items'] as $index => $item)
                        <tr>
                            <td class="font-numeric text-xs text-[var(--text-muted)]">{{ $index + 1 }}</td>
                            <td>
                                <div class="font-bold text-xs font-numeric text-[var(--text-main)]">{{ $item['sku'] }}</div>
                                @if($item['barcode'])
                                    <div class="text-[11px] font-numeric text-[var(--text-muted)]">{{ $item['barcode'] }}</div>
                                @endif
                            </td>
                            <td>
                                <div class="font-semibold text-sm text-[var(--text-main)]">{{ $item['name'] }}</div>
                                @if($item['pieces_per_package'] > 1)
                                    <div class="text-[11px] text-[var(--text-secondary)]">سعة الطرد: {{ $item['pieces_per_package'] }} {{ $item['base_unit'] }}</div>
                                @endif
                            </td>
                            <td>
                                <span class="erp-badge erp-badge-neutral text-xs">{{ $item['category_name'] }}</span>
                            </td>
                            <td>
                                <span class="text-xs text-[var(--text-secondary)]">{{ $item['base_unit'] }}</span>
                            </td>
                            <td class="text-center font-numeric font-bold text-sm {{ $item['book_balance'] > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                                {{ number_format($item['book_balance'], 2) }}
                            </td>
                            <td>
                                <span class="text-xs font-medium text-[var(--text-secondary)]">
                                    {{ $item['package_breakdown'] }}
                                </span>
                            </td>
                            <td class="text-left font-numeric text-xs">
                                {{ number_format($item['cost_price'], 2) }} ج.م
                            </td>
                            <td class="text-left font-numeric font-bold text-xs text-[var(--text-main)]">
                                {{ number_format($item['valuation'], 2) }} ج.م
                            </td>
                            <td>
                                @if(count($item['batches']) > 0)
                                    <div class="flex flex-col gap-1 max-w-[200px]">
                                        @foreach($item['batches'] as $batch)
                                            <div class="text-[11px] font-numeric px-1.5 py-0.5 rounded bg-[var(--surface-color)] border border-[var(--border-color)] flex justify-between">
                                                <span>{{ $batch['batch_number'] ?: 'شريحة' }}</span>
                                                <span class="font-bold">{{ number_format($batch['quantity'], 1) }} @ {{ number_format($batch['cost_price'], 1) }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-xs text-[var(--text-muted)]">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-8 text-[var(--text-muted)]">
                                لا توجد أصناف تطابق معايير البحث في هذا المستودع.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if(count($breakdown['items']) > 0)
                    <tfoot>
                        <tr class="font-bold bg-[var(--surface-color)]">
                            <td colspan="5" class="text-right">الإجمالي الكلي:</td>
                            <td class="text-center font-numeric text-emerald-600 dark:text-emerald-400">
                                {{ number_format($breakdown['total_quantity'], 2) }}
                            </td>
                            <td colspan="2"></td>
                            <td class="text-left font-numeric text-emerald-600 dark:text-emerald-400">
                                {{ number_format($breakdown['total_valuation'], 2) }} ج.م
                            </td>
                            <td></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>
@endsection
