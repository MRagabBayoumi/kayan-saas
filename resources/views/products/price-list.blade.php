@extends('layouts.app')

@section('control_panel')
    <x-control-panel title="قائمة أسعار وبيان تعبئة المنتجات للعملاء (Packing & Price List)" :breadcrumbs="['المخزون والمنتجات' => route('products.index'), 'قائمة الأسعار والباكنج ليست' => route('products.price-list')]">
        <x-slot:actions>
            <a href="{{ route('products.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="arrow-right" size="16" />
                <span>العودة لقائمة الأصناف</span>
            </a>

            <!-- Compact Print Dropdown -->
            <div class="erp-dropdown">
                <button type="button" class="erp-btn erp-btn-primary" data-dropdown-toggle="priceListPrintMenu">
                    <x-icon name="printer" size="16" />
                    <span>طباعة القائمة</span>
                    <x-icon name="chevron-down" size="14" />
                </button>
                <div id="priceListPrintMenu" class="erp-dropdown-menu">
                    <a href="{{ route('products.price-list.print', array_merge(request()->query(), ['format' => 'a4'])) }}" target="_blank" class="erp-dropdown-item">
                        <x-icon name="file-text" size="16" />
                        <span>طباعة A4 قياسي</span>
                    </a>
                    <a href="{{ route('products.price-list.print', array_merge(request()->query(), ['format' => 'a5'])) }}" target="_blank" class="erp-dropdown-item">
                        <x-icon name="file" size="16" />
                        <span>طباعة A5 وسط</span>
                    </a>
                </div>
            </div>
        </x-slot:actions>

        <x-slot:extra>
            <span class="erp-badge erp-badge-neutral text-xs font-numeric">
                إجمالي الأصناف: {{ $products->total() }}
            </span>
        </x-slot:extra>
    </x-control-panel>
@endsection

@section('content')
    <div data-page="product-price-list">
        <!-- Filter Card -->
        <div class="erp-card mb-6">
            <div class="erp-card-body p-4">
                <form method="GET" action="{{ route('products.price-list') }}" class="flex flex-wrap items-end gap-3">
                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-xs font-semibold text-[var(--text-muted)] mb-1">بحث بالاسم أو الكود أو الباركود</label>
                        <input type="text" name="search" value="{{ $search }}" placeholder="ابحث باسم المنتج أو الباركود..." class="erp-input w-full">
                    </div>

                    <div class="w-48">
                        <label class="block text-xs font-semibold text-[var(--text-muted)] mb-1">القسم / التصنيف</label>
                        <select name="category_id" class="erp-input w-full">
                            <option value="">جميع الأقسام</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ $categoryId == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="w-40">
                        <label class="block text-xs font-semibold text-[var(--text-muted)] mb-1">الوحدة</label>
                        <select name="unit" class="erp-input w-full">
                            <option value="">جميع الوحدات</option>
                            @foreach($units as $u)
                                <option value="{{ $u->name }}" {{ $unit === $u->name ? 'selected' : '' }}>{{ $u->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="w-48">
                        <label class="block text-xs font-semibold text-[var(--text-muted)] mb-1">نوع الأسعار المعروضة</label>
                        <select name="price_type" class="erp-input w-full">
                            <option value="all" {{ $priceType === 'all' ? 'selected' : '' }}>كافة الأسعار (جملة + قطاعي + موزع)</option>
                            <option value="wholesale" {{ $priceType === 'wholesale' ? 'selected' : '' }}>سعر الجملة فقط</option>
                            <option value="wholesale_retail" {{ $priceType === 'wholesale_retail' ? 'selected' : '' }}>جملة وقطاعي</option>
                            <option value="distributor" {{ $priceType === 'distributor' ? 'selected' : '' }}>سعر الموزع فقط</option>
                            <option value="retail" {{ $priceType === 'retail' ? 'selected' : '' }}>سعر القطاعي فقط</option>
                        </select>
                    </div>

                    <div class="flex flex-col gap-1.5 pt-2">
                        <label class="flex items-center gap-1.5 cursor-pointer text-xs font-semibold text-[var(--text-main)]">
                            <input type="checkbox" name="in_stock_only" value="1" {{ $inStockOnly ? 'checked' : '' }} class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                            <span>المتوفر بالمخزن فقط</span>
                        </label>
                        <label class="flex items-center gap-1.5 cursor-pointer text-xs font-semibold text-[var(--text-main)]">
                            <input type="checkbox" name="show_stock" value="1" {{ $showStock ? 'checked' : '' }} class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                            <span>إظهار رصيد المخزون</span>
                        </label>
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="submit" class="erp-btn erp-btn-primary">
                            <x-icon name="filter" size="16" />
                            <span>تصفية</span>
                        </button>
                        @if($search || $categoryId || $unit || $inStockOnly || $priceType !== 'all' || !$showStock)
                            <a href="{{ route('products.price-list') }}" class="erp-btn erp-btn-secondary">
                                <x-icon name="x" size="14" />
                                <span>إعادة ضبط</span>
                            </a>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        <!-- Products Table Card -->
        <div class="erp-card">
            <div class="erp-card-header flex items-center justify-between">
                <div class="erp-card-title flex items-center gap-2">
                    <x-icon name="clipboard" size="18" class="text-[var(--primary)]" />
                    <span>بيان أسعار وتعبئة الأصناف (Packing List & Prices)</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="erp-badge erp-badge-neutral text-xs font-numeric">
                        معروض: {{ $products->count() }} من أصل {{ $products->total() }}
                    </span>
                </div>
            </div>

            <div class="erp-card-body p-0">
                <div class="erp-table-responsive">
                    <table class="erp-table">
                        <thead>
                            <tr>
                                <th class="w-12 text-center">#</th>
                                <th class="w-28 text-center">الكود / الباركود</th>
                                <th>اسم الصنف / المنتج</th>
                                <th class="text-center">القسم</th>
                                <th class="text-center">الوحدة الأساسية</th>
                                <th class="text-center">التعبئة (الكرتونة/العبوة)</th>
                                @if(in_array($priceType, ['all', 'wholesale', 'wholesale_retail'], true))
                                    <th class="text-center bg-amber-500/5 text-amber-700 dark:text-amber-400 font-bold">سعر الجملة</th>
                                @endif
                                @if(in_array($priceType, ['all', 'retail', 'wholesale_retail'], true))
                                    <th class="text-center bg-blue-500/5 text-blue-700 dark:text-blue-400 font-bold">سعر القطاعي</th>
                                @endif
                                @if(in_array($priceType, ['all', 'distributor'], true))
                                    <th class="text-center bg-purple-500/5 text-purple-700 dark:text-purple-400 font-bold">سعر الموزع</th>
                                @endif
                                @if($showStock)
                                    <th class="text-center">الرصيد المتاح</th>
                                @endif
                                <th class="text-center w-24">الحالة</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($products as $index => $product)
                                @php
                                    $stock = (float) $product->total_stock;
                                    $piecesPerPack = (int) ($product->pieces_per_package ?: 12);
                                @endphp
                                <tr>
                                    <td class="text-center font-numeric text-xs text-[var(--text-muted)]">
                                        {{ $products->firstItem() + $index }}
                                    </td>
                                    <td class="text-center font-numeric text-xs">
                                        <div class="font-bold text-[var(--primary)]">{{ $product->sku }}</div>
                                        @if($product->barcode)
                                            <div class="text-[10px] text-[var(--text-muted)]">{{ $product->barcode }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('products.show', $product->id) }}" class="font-bold text-[var(--text-main)] hover:text-[var(--primary)] hover:underline">
                                            {{ $product->name }}
                                        </a>
                                    </td>
                                    <td class="text-center text-xs">
                                        <span class="erp-badge erp-badge-neutral text-xs">
                                            {{ $product->category?->name ?? 'عام' }}
                                        </span>
                                    </td>
                                    <td class="text-center text-xs font-semibold">
                                        {{ $product->unit ?? 'قطعة' }}
                                    </td>
                                    <td class="text-center font-numeric text-xs">
                                        <span class="erp-badge erp-badge-info text-xs">
                                            {{ $piecesPerPack }} قطعة / عبوة
                                        </span>
                                    </td>
                                    @if(in_array($priceType, ['all', 'wholesale', 'wholesale_retail'], true))
                                        <td class="text-center font-numeric font-black text-sm text-amber-700 dark:text-amber-400">
                                            {{ number_format($product->wholesale_price, 2) }} ج.م
                                        </td>
                                    @endif
                                    @if(in_array($priceType, ['all', 'retail', 'wholesale_retail'], true))
                                        <td class="text-center font-numeric font-bold text-sm text-blue-700 dark:text-blue-400">
                                            {{ number_format($product->retail_price, 2) }} ج.م
                                        </td>
                                    @endif
                                    @if(in_array($priceType, ['all', 'distributor'], true))
                                        <td class="text-center font-numeric font-bold text-sm text-purple-700 dark:text-purple-400">
                                            {{ number_format($product->distributor_price, 2) }} ج.م
                                        </td>
                                    @endif
                                    @if($showStock)
                                        <td class="text-center font-numeric text-xs font-bold {{ $stock > 0 ? 'text-emerald-600' : 'text-[var(--text-muted)]' }}">
                                            {{ number_format($stock, 0) }} {{ $product->unit }}
                                        </td>
                                    @endif
                                    <td class="text-center">
                                        @if($stock > 0)
                                            <span class="erp-badge erp-badge-success text-[10px]">متوفر</span>
                                        @else
                                            <span class="erp-badge erp-badge-neutral text-[10px]">غير متوفر</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="11" class="text-center py-8 text-[var(--text-muted)]">
                                        لا توجد أصناف مطابقة لخيارات البحث والتصفية
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($products->hasPages())
                    <div class="erp-card-footer p-3">
                        {{ $products->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
