@extends('layouts.app')

@section('control_panel')
    <x-control-panel title="نظرة عامة على المخزون" :breadcrumbs="['المخزون والمنتجات' => route('inventory.index'), 'الأرصدة والمخازن' => route('inventory.index')]">
        <x-slot:actions>
            <button type="button" data-action="open-movement-modal" class="erp-btn erp-btn-primary">
                <x-icon name="arrow-left-right" size="16" />
                <span>تسجيل حركة مخزون جديدة</span>
            </button>
            <a href="{{ route('inventory.stocktake') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="clipboard-check" size="16" />
                <span>جرد وتسوية المخزون</span>
            </a>
            <a href="{{ route('inventory.movements') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="file-text" size="16" />
                <span>سجل الحركات</span>
            </a>
            <a href="{{ route('inventory.low-stock') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="alert-triangle" size="16" />
                <span>النواقص ({{ $metrics['low_stock_count'] + $metrics['out_of_stock_count'] }})</span>
            </a>
            <a href="{{ route('warehouses.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="home" size="16" />
                <span>إدارة المستودعات</span>
            </a>
        </x-slot:actions>

        <x-slot:extra>
            <span class="erp-badge erp-badge-neutral font-numeric px-2.5 py-1 text-xs">
                <span>إجمالي الأصناف:</span>
                <strong>{{ $metrics['total_products'] }}</strong>
            </span>
        </x-slot:extra>
    </x-control-panel>
@endsection

@section('content')
    <div data-page="inventory">
        <!-- Flash Messages -->
        @if(session('success'))
            <div class="erp-alert erp-alert-success mb-4" role="alert">
                <x-icon name="check" size="16" />
                <div>{{ session('success') }}</div>
            </div>
        @endif

        @if(session('error'))
            <div class="erp-alert erp-alert-danger mb-4" role="alert">
                <x-icon name="alert-triangle" size="16" />
                <div>{{ session('error') }}</div>
            </div>
        @endif

        <!-- KPI Metric Cards -->
        <div class="inventory-kpi-grid">
            <!-- Total Stock Units -->
            <div class="erp-card inventory-kpi-card">
                <div class="inventory-kpi-icon primary">
                    <x-icon name="package" size="22" />
                </div>
                <div>
                    <div class="text-xs text-[var(--text-muted)] font-semibold">إجمالي وحدات المخزون</div>
                    <div class="font-numeric text-xl font-extrabold text-[var(--text-main)] mt-0.5">
                        {{ number_format($metrics['total_stock_units']) }} <span class="text-xs font-normal text-[var(--text-muted)]">قطعة</span>
                    </div>
                </div>
            </div>

            <!-- FIFO Inventory Valuation -->
            <div class="erp-card inventory-kpi-card">
                <div class="inventory-kpi-icon info">
                    <x-icon name="banknote" size="22" />
                </div>
                <div>
                    <div class="text-xs text-[var(--text-muted)] font-semibold">تقييم المخزون بالتكلفة (FIFO)</div>
                    <div class="font-numeric text-xl font-extrabold text-[var(--text-main)] mt-0.5">
                        {{ number_format($metrics['total_inventory_value'], 2) }} <span class="text-xs font-normal text-[var(--text-muted)]">ج.م</span>
                    </div>
                </div>
            </div>

            <!-- Low Stock Items -->
            <div class="erp-card inventory-kpi-card">
                <div class="inventory-kpi-icon warning">
                    <x-icon name="alert-triangle" size="22" />
                </div>
                <div>
                    <div class="text-xs text-[var(--text-muted)] font-semibold">أصناف تحت حد الطلب</div>
                    <div class="font-numeric text-xl font-extrabold text-[#f59e0b] mt-0.5">
                        {{ $metrics['low_stock_count'] }} <span class="text-xs font-normal text-[var(--text-muted)]">صنف</span>
                    </div>
                </div>
            </div>

            <!-- Out of Stock Items -->
            <div class="erp-card inventory-kpi-card">
                <div class="inventory-kpi-icon danger">
                    <x-icon name="x" size="22" />
                </div>
                <div>
                    <div class="text-xs text-[var(--text-muted)] font-semibold">أصناف نفدت تماماً</div>
                    <div class="font-numeric text-xl font-extrabold text-[#ef4444] mt-0.5">
                        {{ $metrics['out_of_stock_count'] }} <span class="text-xs font-normal text-[var(--text-muted)]">صنف</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters & Search Bar -->
        <div class="erp-card mb-4 p-3">
            <form action="{{ route('inventory.index') }}" method="GET" class="erp-filter-form">
                <!-- Search with Camera Scanner -->
                <div class="flex-1 min-w-[220px] relative flex items-center">
                    <input 
                        type="search" 
                        id="inventorySearchInput"
                        name="search" 
                        value="{{ $searchTerm }}" 
                        class="erp-input font-numeric w-full pl-9" 
                        placeholder="بحث باسم الصنف، كود SKU، أو الباركود...">
                    <button 
                        type="button" 
                        data-action="open-barcode-scanner"
                        data-target-input="inventorySearchInput"
                        title="مسح الباركود بالكاميرا للبحث السريع"
                        class="absolute left-1 top-1/2 -translate-y-1/2 h-7 w-7 border-0 bg-[var(--primary-soft)] text-[var(--primary)] rounded inline-flex items-center justify-center cursor-pointer">
                        <x-icon name="camera" size="15" />
                    </button>
                </div>

                <!-- Warehouse Filter -->
                <div class="min-w-[180px]">
                    <select name="warehouse_id" class="erp-input" data-action="auto-submit">
                        <option value="">جميع المستودعات</option>
                        @foreach($warehouses as $wh)
                            <option value="{{ $wh->id }}" {{ $selectedWarehouseId == $wh->id ? 'selected' : '' }}>
                                {{ $wh->name }} {{ $wh->is_default ? '(الافتراضي)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Category Filter -->
                <div class="min-w-[180px]">
                    <select name="category_id" class="erp-input" data-action="auto-submit">
                        <option value="">جميع التصنيفات</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ $selectedCategoryId == $cat->id ? 'selected' : '' }}>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="erp-btn erp-btn-secondary">
                    <x-icon name="search" size="14" />
                    <span>تصفية</span>
                </button>

                @if($searchTerm || $selectedWarehouseId || $selectedCategoryId)
                    <a href="{{ route('inventory.index') }}" class="erp-btn erp-btn-secondary" title="إلغاء الفلاتر">
                        ✕ إعادة ضبط
                    </a>
                @endif
            </form>
        </div>

        <!-- Inventory Table -->
        <div class="erp-card overflow-x-auto mb-5">
            <table class="erp-table w-full">
                <thead>
                    <tr>
                        <th class="w-[50px]">#</th>
                        <th>كود الصنف (SKU)</th>
                        <th>اسم الصنف</th>
                        <th>التصنيف</th>
                        <th class="text-left">سعر التكلفة</th>
                        <th class="text-center">الرصيد الكلي</th>
                        <th>توزيع المخازن</th>
                        <th class="text-center">حالة الرصيد</th>
                        <th class="text-left">قيمة المخزون</th>
                        <th class="w-[130px] text-center">إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                        @php
                            $totalStock = (float) ($product->stock_balances_sum_quantity ?? 0);
                            $isOutOfStock = $totalStock <= 0;
                            $isLowStock = !$isOutOfStock && $totalStock <= $product->min_stock_alert;
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
                                <a href="{{ route('products.show', $product) }}" class="font-semibold text-[var(--text-main)] hover:text-[var(--primary)] block" title="عرض تفاصيل الصنف">
                                    {{ $product->name }}
                                </a>
                                <span class="text-[11px] text-[var(--text-muted)]">الوحدة: {{ $product->unit }}@if(($product->unit_factor ?? 1) > 1) ({{ (int)$product->unit_factor }} قطعة)@endif</span>
                            </td>
                            <td>
                                <span class="erp-badge erp-badge-neutral">{{ $product->category?->name ?? 'بدون تصنيف' }}</span>
                            </td>
                            <td class="text-left font-numeric">
                                <div>{{ number_format($product->cost_price, 2) }} ج.م</div>
                                @if(($product->unit_factor ?? 1) > 1)
                                    <small class="text-[10px] text-[var(--text-muted)] block">({{ number_format($product->base_cost_price, 2) }} ج.م/قطعة)</small>
                                @endif
                            </td>
                            <td class="text-center font-numeric">
                                <span class="text-sm font-extrabold {{ $isOutOfStock ? 'text-[var(--danger,#ef4444)]' : ($isLowStock ? 'text-[var(--warning,#f59e0b)]' : 'text-[var(--text-main)]') }}">
                                    {{ $product->formatStockBreakdown($totalStock) }}
                                </span>
                            </td>
                            <td>
                                <div class="flex flex-col gap-0.5">
                                    @forelse($product->stockBalances as $sb)
                                        @if($sb->quantity > 0)
                                            <div class="text-[11px] flex justify-between gap-2">
                                                <span class="text-[var(--text-secondary)]">{{ $sb->warehouse->name }}:</span>
                                                <strong class="font-numeric text-[var(--text-main)]">{{ $product->formatStockBreakdown((float)$sb->quantity) }}</strong>
                                            </div>
                                        @endif
                                    @empty
                                        <span class="text-[11px] text-[var(--text-muted)]">— لا يوجد رصيد —</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="text-center">
                                @if($isOutOfStock)
                                    <span class="erp-badge erp-badge-danger">نفد الرصيد</span>
                                @elseif($isLowStock)
                                    <span class="erp-badge erp-badge-warning">منخفض (حد: {{ $product->min_stock_alert }})</span>
                                @else
                                    <span class="erp-badge erp-badge-success">متوفر</span>
                                @endif
                            </td>
                            <td class="text-left font-numeric font-bold text-[var(--text-main)]">
                                {{ number_format($totalStock * (float) $product->base_cost_price, 2) }} ج.م
                            </td>
                            <td class="text-center">
                                <div class="flex items-center justify-center gap-1">
                                    <a 
                                        href="{{ route('products.ledger', $product) }}"
                                        class="erp-btn erp-btn-sm erp-btn-ghost erp-btn-icon"
                                        title="كشف حركة الصنف">
                                        <x-icon name="activity" size="14" />
                                    </a>
                                    <button 
                                        type="button" 
                                        data-action="quick-movement"
                                        data-product-id="{{ $product->id }}"
                                        data-product-name="{{ $product->name }}"
                                        data-product-sku="{{ $product->sku }}"
                                        data-product-cost="{{ (float) $product->base_cost_price }}"
                                        class="erp-btn erp-btn-sm erp-btn-secondary"
                                        title="إجراء حركة أو تسوية">
                                        <x-icon name="arrow-left-right" size="13" />
                                        <span>حركة</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center p-9 text-[var(--text-muted)]">
                                <x-icon name="package" size="36" class="mb-2 opacity-50 inline-block" />
                                <div>لا توجد أصناف مطابقة للبحث أو الفلتر المحدد</div>
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

        <!-- Modal: Stock Movement (Dynamic In / Out / Transfer / Adjustment) -->
        <div id="stockMovementModal" class="erp-modal-overlay">
            <div class="erp-modal-container max-w-[540px]">
                <div class="erp-modal-header">
                    <div>
                        <h3 class="erp-modal-title m-0">
                            <x-icon name="arrow-left-right" size="18" />
                            <span>تسجيل حركة مخزون</span>
                        </h3>
                        <div id="modal_product_subtitle" class="text-xs text-[var(--text-muted)] mt-0.5"></div>
                    </div>
                    <button type="button" data-action="close-movement-modal" class="erp-modal-close" title="إغلاق">✕</button>
                </div>

                <form action="{{ route('inventory.movements.store') }}" method="POST">
                    @csrf
                    <div class="erp-modal-body">
                        <!-- Movement Type Selector -->
                        <div class="mb-4">
                            <label class="erp-label block mb-1.5 text-xs font-semibold">نوع الحركة *</label>
                            <div class="erp-movement-type-grid grid grid-cols-4 gap-1.5">
                                <label class="movement-type-tab active" id="type_label_in">
                                    <input type="radio" name="movement_type" value="in" checked class="hidden">
                                    إيداع / وارد
                                </label>
                                <label class="movement-type-tab" id="type_label_out">
                                    <input type="radio" name="movement_type" value="out" class="hidden">
                                    صرف (FIFO)
                                </label>
                                <label class="movement-type-tab" id="type_label_transfer">
                                    <input type="radio" name="movement_type" value="transfer" class="hidden">
                                    تحويل مخزني
                                </label>
                                <label class="movement-type-tab" id="type_label_adjustment">
                                    <input type="radio" name="movement_type" value="adjustment" class="hidden">
                                    تسوية جردية
                                </label>
                            </div>
                        </div>

                        <!-- Product Selection -->
                        <div class="mb-3.5">
                            <label class="erp-label block mb-1.5 text-xs font-semibold">الصنف *</label>
                            <select id="modal_product_id" name="product_id" class="erp-input w-full" required>
                                <option value="">-- اختر الصنف --</option>
                                @foreach($products as $prod)
                                    <option value="{{ $prod->id }}" data-cost="{{ $prod->base_cost_price }}">
                                        [{{ $prod->sku }}] {{ $prod->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Standard Warehouse Selection (For In, Out, Adjustment) -->
                        <div id="single_warehouse_box" class="mb-3.5">
                            <label class="erp-label block mb-1.5 text-xs font-semibold">المستودع *</label>
                            <select name="warehouse_id" class="erp-input w-full">
                                @foreach($warehouses as $wh)
                                    <option value="{{ $wh->id }}" {{ $wh->is_default ? 'selected' : '' }}>
                                        {{ $wh->name }} {{ $wh->is_default ? '(الافتراضي)' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Transfer Warehouses Selection (From -> To) -->
                        <div id="transfer_warehouses_box" class="erp-form-row-2col hidden mb-3.5">
                            <div>
                                <label class="erp-label block mb-1.5 text-xs font-semibold">من مستودع *</label>
                                <select name="from_warehouse_id" class="erp-input w-full">
                                    @foreach($warehouses as $wh)
                                        <option value="{{ $wh->id }}">{{ $wh->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="erp-label block mb-1.5 text-xs font-semibold">إلى مستودع *</label>
                                <select name="to_warehouse_id" class="erp-input w-full">
                                    @foreach($warehouses as $wh)
                                        <option value="{{ $wh->id }}" {{ !$loop->first ? 'selected' : '' }}>{{ $wh->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Quantity & Unit Cost -->
                        <div class="erp-form-row-2col mb-3.5">
                            <div>
                                <label id="qty_label" class="erp-label block mb-1.5 text-xs font-semibold">الكمية *</label>
                                <input type="number" step="0.01" min="0.01" name="quantity" class="erp-input font-numeric w-full" placeholder="مثال: 50" required>
                            </div>
                            <div id="cost_box">
                                <label class="erp-label block mb-1.5 text-xs font-semibold">سعر التكلفة (ج.م)</label>
                                <input type="number" step="0.01" min="0" id="modal_unit_cost" name="unit_cost" class="erp-input font-numeric w-full" placeholder="التكلفة...">
                            </div>
                        </div>

                        <!-- Notes / Reason -->
                        <div class="mb-2.5">
                            <label class="erp-label block mb-1.5 text-xs font-semibold">ملاحظات / سبب الحركة</label>
                            <textarea name="notes" class="erp-input w-full" rows="2" placeholder="رقم الفاتورة، أو سبب التسوية أو التحويل..."></textarea>
                        </div>
                    </div>

                    <div class="erp-modal-footer">
                        <button type="button" data-action="close-movement-modal" class="erp-btn erp-btn-secondary">إلغاء</button>
                        <button type="submit" class="erp-btn erp-btn-primary">تأكيد وتنفيذ الحركة</button>
                    </div>
                </form>
            </div>
        </div>

        <x-barcode-scanner-modal target-input-id="inventorySearchInput" />
    </div>
@endsection
