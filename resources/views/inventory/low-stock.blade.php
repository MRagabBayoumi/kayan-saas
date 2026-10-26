@extends('layouts.app')

@section('control_panel')
    <x-control-panel title="نواقص وتنبيهات المخزون" :breadcrumbs="['المخزون والمنتجات' => route('inventory.index'), 'نواقص المخزون' => route('inventory.low-stock')]">
        <x-slot:actions>
            <button type="button" data-action="python-direct-print" data-url="{{ route('inventory.low-stock.direct-print', request()->query()) }}" class="erp-btn erp-btn-primary" title="طباعة مباشرة عبر بايثون">
                <x-icon name="printer" size="16" />
                <span>طباعة بايثون المباشرة</span>
            </button>
            <a href="{{ route('inventory.low-stock.print', request()->query()) }}" target="_blank" class="erp-btn erp-btn-secondary" title="معاينة وطباعة عبر المتصفح">
                <x-icon name="printer" size="16" />
                <span>معاينة وطباعة</span>
            </a>
            <a href="{{ route('inventory.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="plus" size="16" />
                <span>تسجيل إيداع مخزون</span>
            </a>
            <a href="{{ route('inventory.movements') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="arrow-left-right" size="16" />
                <span>سجل الحركات</span>
            </a>
        </x-slot:actions>

        <x-slot:extra>
            <span class="erp-badge erp-badge-warning font-numeric px-2.5 py-1 text-xs">
                <span>أصناف تتطلب إعادة الطلب:</span>
                <strong>{{ $products->total() }}</strong>
            </span>
        </x-slot:extra>
    </x-control-panel>
@endsection

@section('content')
    <!-- Alert Notice -->
    <div class="erp-alert erp-alert-warning mb-4" role="alert">
        <x-icon name="alert-triangle" size="18" />
        <div>
            <strong>تنبيه إعادة الطلب:</strong> تُعرض هنا كافة الأصناف التي وصل رصيدها الفعلي في جميع المستودعات إلى حد الأمان المحدد للصنف (الحد الأدنى للطلب) أو نفدت تماماً.
        </div>
    </div>

    <!-- Category Filter -->
    <div class="erp-card mb-4 p-3">
        <form action="{{ route('inventory.low-stock') }}" method="GET" class="erp-filter-form">
            <div class="min-w-[200px]">
                <select name="category_id" class="erp-input" data-action="auto-submit">
                    <option value="">جميع التصنيفات</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ $selectedCategoryId == $cat->id ? 'selected' : '' }}>
                            {{ $cat->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            @if($selectedCategoryId)
                <a href="{{ route('inventory.low-stock') }}" class="erp-btn erp-btn-secondary" title="إلغاء الفلتر">
                    ✕ عرض الكل
                </a>
            @endif
        </form>
    </div>

    <!-- Low Stock Table -->
    <div class="erp-card overflow-x-auto mb-5">
        <table class="erp-table w-full">
            <thead>
                <tr>
                    <th class="w-[50px]">#</th>
                    <th>كود الصنف (SKU)</th>
                    <th>اسم الصنف</th>
                    <th>التصنيف</th>
                    <th class="text-center">الرصيد المتبقي</th>
                    <th class="text-center">الحد الأدنى للطلب</th>
                    <th class="text-center">العجز / المطلوب</th>
                    <th class="text-center">الحالة</th>
                    <th class="text-left">سعر التكلفة</th>
                    <th class="w-[130px] text-center">الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $product)
                    @php
                        $currentStock = (float) ($product->stock_balances_sum_quantity ?? 0);
                        $minAlert = (int) $product->min_stock_alert;
                        $deficit = max(0, $minAlert - $currentStock);
                        $isOut = $currentStock <= 0;
                    @endphp
                    <tr>
                        <td class="font-numeric text-[var(--text-muted)]">{{ $loop->iteration + ($products->currentPage() - 1) * $products->perPage() }}</td>
                        <td>
                            <a href="{{ route('products.show', $product) }}" class="font-numeric font-bold text-[var(--primary)] hover:underline block" title="عرض تفاصيل الصنف">
                                {{ $product->sku }}
                            </a>
                            @if($product->barcode)
                                <div class="font-numeric text-[11px] text-[var(--text-muted)]">{{ $product->barcode }}</div>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('products.show', $product) }}" class="font-semibold text-[var(--text-main)] hover:text-[var(--primary)] block truncate" title="عرض تفاصيل الصنف">
                                {{ $product->name }}
                            </a>
                            <span class="text-[11px] text-[var(--text-muted)]">الوحدة: {{ $product->unit }}@if(($product->unit_factor ?? 1) > 1) ({{ (int)$product->unit_factor }} قطعة)@endif</span>
                        </td>
                        <td>
                            <span class="erp-badge erp-badge-neutral">{{ $product->category?->name ?? 'بدون تصنيف' }}</span>
                        </td>
                        <td class="text-center">
                            <span class="font-numeric text-sm font-extrabold {{ $isOut ? 'text-[var(--danger,#ef4444)]' : 'text-[var(--warning,#f59e0b)]' }}">
                                {{ $product->formatStockBreakdown($currentStock) }}
                            </span>
                        </td>
                        <td class="text-center font-numeric text-sm text-[var(--text-secondary)]">
                            {{ $minAlert }}
                        </td>
                        <td class="text-center">
                            <span class="font-numeric font-bold text-[var(--danger,#ef4444)]">
                                +{{ number_format($deficit > 0 ? $deficit : 1) }} قطعة
                            </span>
                        </td>
                        <td class="text-center">
                            @if($isOut)
                                <span class="erp-badge erp-badge-danger">نفد بالكامل</span>
                            @else
                                <span class="erp-badge erp-badge-warning">أوشك على النفاد</span>
                            @endif
                        </td>
                        <td class="text-left font-numeric">
                            <div>{{ number_format($product->cost_price, 2) }} ج.م</div>
                            @if(($product->unit_factor ?? 1) > 1)
                                <small class="text-[10px] text-[var(--text-muted)] block">({{ number_format($product->base_cost_price, 2) }} ج.م/قطعة)</small>
                            @endif
                        </td>
                        <td class="text-center">
                            <div class="flex items-center justify-center gap-1">
                                <a href="{{ route('products.show', $product) }}" class="erp-btn erp-btn-sm erp-btn-ghost erp-btn-icon" title="عرض تفاصيل الصنف">
                                    <x-icon name="eye" size="14" />
                                </a>
                                <a href="{{ route('inventory.index') }}?search={{ urlencode($product->sku) }}" class="erp-btn erp-btn-sm erp-btn-secondary" title="إيداع مخزني">
                                    <x-icon name="plus" size="13" />
                                    <span>إيداع</span>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-center p-10 text-[var(--text-muted)]">
                            <x-icon name="check-circle" size="36" class="text-[var(--success,#10b981)] mb-2 inline-block" />
                            <div class="text-sm font-semibold text-[var(--text-main)]">ممتاز! لا توجد نواقص في المخزون حالياً</div>
                            <div class="text-xs mt-1">كافة الأصناف تتجاوز الحد الأدنى للأمان.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($products->hasPages())
        <div class="mt-4">
            {{ $products->links() }}
        </div>
    @endif
@endsection
