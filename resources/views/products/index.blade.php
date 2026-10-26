@extends('layouts.app')

@section('control_panel')
    <x-control-panel title="المنتجات والأصناف" :breadcrumbs="['المخزون والمنتجات' => route('products.index')]">
        <x-slot:actions>
            <a href="{{ route('products.create') }}" class="erp-btn erp-btn-primary">
                <x-icon name="plus" size="16" />
                <span>إضافة منتج جديد</span>
            </a>
            <button type="button" id="topBatchPrintBtn" data-action="direct-print-batch" class="erp-btn erp-btn-secondary hidden text-[var(--primary)]" title="طباعة فورية للأصناف المحددة بدون نوافذ">
                <x-icon name="printer" size="16" />
                <span>طباعة ملصقات (<span id="topBatchCount" class="font-numeric">0</span>)</span>
            </button>
            <a href="{{ route('products.import.form') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="upload" size="16" />
                <span>استيراد من إكسيل</span>
            </a>
            <a href="{{ route('categories.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="tag" size="16" />
                <span>إدارة التصنيفات</span>
            </a>
        </x-slot:actions>

        <x-slot:extra>
            <span class="erp-badge erp-badge-neutral font-numeric px-2.5 py-1 text-xs">
                <span>إجمالي الأصناف:</span>
                <strong>{{ $products->total() }}</strong>
            </span>
        </x-slot:extra>
    </x-control-panel>
@endsection

@section('content')
    <div data-page="products-index">
        <!-- Flash Messages -->
        @if(session('status') === 'success')
            <div class="erp-alert erp-alert-success mb-4" role="alert">
                <x-icon name="check" size="16" />
                <div>{{ session('message', 'تمت العملية بنجاح.') }}</div>
            </div>
        @endif

        @if(session('status') === 'error')
            <div class="erp-alert erp-alert-danger mb-4" role="alert">
                <x-icon name="alert-triangle" size="16" />
                <div>{{ session('message', 'حدث خطأ أثناء تنفيذ العملية.') }}</div>
            </div>
        @endif

        <!-- Search & Filter Bar -->
        <div class="erp-card erp-card-filter mb-4 p-3 overflow-visible">
            <form action="{{ route('products.index') }}" method="GET" class="erp-filter-form">
                <!-- Search Input with Camera Barcode Scanner -->
                <div class="flex-1 min-w-[220px] relative flex items-center">
                    <input 
                        type="search" 
                        id="productSearchInput"
                        name="search" 
                        value="{{ $currentSearch }}" 
                        class="erp-input erp-search-input font-numeric w-full" 
                        placeholder="بحث باسم المنتج، كود SKU، أو الباركود..."
                    >
                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-[var(--text-muted)] flex items-center pointer-events-none">
                        <x-icon name="search" size="16" />
                    </span>
                    <button 
                        type="button" 
                        data-action="open-barcode-scanner"
                        data-target-input="productSearchInput"
                        title="مسح الباركود بالكاميرا للبحث السريع"
                        class="absolute left-1.5 top-1/2 -translate-y-1/2 h-7 w-7 border-0 bg-[var(--primary-soft)] text-[var(--primary)] rounded inline-flex items-center justify-center cursor-pointer">
                        <x-icon name="camera" size="15" />
                    </button>
                </div>

                <!-- Category Multi-Select Filter -->
                <div class="erp-dropdown min-w-[200px]">
                    <button type="button" class="erp-btn erp-btn-secondary w-full justify-between" data-dropdown-toggle="productCategoryFilterDropdown">
                        <span class="truncate" id="productCategoryFilterLabel">
                            @if(empty($selectedCategoryIds))
                                كل التصنيفات
                            @elseif(count($selectedCategoryIds) === 1)
                                {{ $categories->firstWhere('id', $selectedCategoryIds[0])?->name ?? 'تصنيف محدد' }}
                            @else
                                {{ count($selectedCategoryIds) }} تصنيفات محددة
                            @endif
                        </span>
                        <x-icon name="chevron-down" size="14" />
                    </button>
                    <div id="productCategoryFilterDropdown" class="erp-dropdown-menu p-3 max-h-64 overflow-y-auto w-64 shadow-lg">
                        <div class="flex items-center justify-between text-xs font-bold text-[var(--text-muted)] mb-2 pb-1 border-b border-[var(--border-color)]">
                            <span>اختر التصنيفات:</span>
                            <div class="flex gap-2">
                                <button type="button" id="selectAllCategoriesBtn" class="text-[var(--primary)] hover:underline text-[11px] font-normal cursor-pointer">تحديد الكل</button>
                                <span class="text-[var(--border-color)]">|</span>
                                <button type="button" id="clearAllCategoriesBtn" class="text-[var(--danger)] hover:underline text-[11px] font-normal cursor-pointer">مسح</button>
                            </div>
                        </div>
                        @foreach($categories as $category)
                            <label class="flex items-center gap-2 py-1.5 px-2 hover:bg-[var(--bg-hover)] rounded cursor-pointer text-xs">
                                <input type="checkbox" name="category_ids[]" value="{{ $category->id }}" {{ in_array($category->id, $selectedCategoryIds) ? 'checked' : '' }} class="category-filter-cb rounded border-[var(--border-color)]">
                                <span class="text-[var(--text-main)]">{{ $category->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <!-- Unit Filter -->
                <div class="min-w-[140px]">
                    <select name="unit" class="erp-input w-full">
                        <option value="">-- كل وحدات القياس --</option>
                        @foreach($units as $unitItem)
                            <option value="{{ $unitItem->name }}" {{ $currentUnit === $unitItem->name || $currentUnit === $unitItem->code ? 'selected' : '' }}>
                                {{ $unitItem->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Status Filter -->
                <div class="min-w-[130px]">
                    <select name="status" class="erp-input w-full">
                        <option value="">-- كل الحالات --</option>
                        <option value="active" {{ $currentStatus === 'active' ? 'selected' : '' }}>نشط فقط</option>
                        <option value="inactive" {{ $currentStatus === 'inactive' ? 'selected' : '' }}>معطل فقط</option>
                    </select>
                </div>

                <!-- Actions -->
                <button type="submit" class="erp-btn erp-btn-primary py-1.5 px-4">
                    <span>تصفية</span>
                </button>

                @if($currentSearch || !empty($selectedCategoryIds) || $currentStatus || $currentUnit)
                    <a href="{{ route('products.index') }}" class="erp-btn erp-btn-secondary py-1.5 px-3" title="إلغاء التصفية">
                        <x-icon name="x" size="14" />
                        <span>إلغاء الفلترة</span>
                    </a>
                @endif
            </form>

            @if(!empty($selectedCategoryIds) || $currentUnit || $currentStatus)
                <div class="flex flex-wrap items-center gap-2 mt-3 pt-3 border-t border-[var(--border-color)]">
                    @if(!empty($selectedCategoryIds))
                        <span class="text-xs text-[var(--text-muted)] font-bold">التصنيفات ({{ count($selectedCategoryIds) }}):</span>
                        @foreach($categories->whereIn('id', $selectedCategoryIds) as $selCat)
                            <span class="erp-badge erp-badge-info text-xs flex items-center gap-1">
                                <span>{{ $selCat->name }}</span>
                            </span>
                        @endforeach
                    @endif

                    @if($currentUnit)
                        <span class="erp-badge erp-badge-primary text-xs flex items-center gap-1">
                            <x-icon name="maximize-2" size="12" />
                            <span>الوحدة: {{ $currentUnit }}</span>
                            <a href="{{ request()->fullUrlWithQuery(['unit' => null]) }}" class="hover:text-red-500 mr-1 font-bold text-xs" title="إزالة فلتر الوحدة">×</a>
                        </span>
                    @endif

                    @if($currentStatus)
                        <span class="erp-badge erp-badge-neutral text-xs flex items-center gap-1">
                            <span>الحالة: {{ $currentStatus === 'active' ? 'نشط' : 'معطل' }}</span>
                            <a href="{{ request()->fullUrlWithQuery(['status' => null]) }}" class="hover:text-red-500 mr-1 font-bold text-xs" title="إزالة فلتر الحالة">×</a>
                        </span>
                    @endif

                    <a href="{{ route('products.index') }}" class="text-xs text-[var(--danger)] hover:underline mr-2">
                        مسح كافة الفلاتر
                    </a>
                </div>
            @endif
        </div>

        <!-- Products Table Card -->
        <div class="erp-card p-0 overflow-hidden">
            @if($products->isEmpty())
                <div class="text-center py-12 px-5">
                    <div class="mb-3 opacity-40 flex justify-center">
                        <x-icon name="package" size="48" />
                    </div>
                    <h3 class="text-base font-bold mb-1.5 text-[var(--text-main)]">لم يتم العثور على أي منتجات</h3>
                    <p class="text-xs text-[var(--text-muted)] mb-4">
                        @if($currentSearch || $currentCategoryId || $currentStatus)
                            لا توجد أصناف تطابق معايير البحث الحالية. جرب تغيير كلمات البحث أو إلغاء الفلترة.
                        @else
                            ابدأ بتسجيل أول صنف في مخزون إكسسوارات كيان.
                        @endif
                    </p>
                    @if(!($currentSearch || $currentCategoryId || $currentStatus))
                        <a href="{{ route('products.create') }}" class="erp-btn erp-btn-primary">
                            <x-icon name="plus" size="16" />
                            <span>إضافة أول منتج</span>
                        </a>
                    @endif
                </div>
            @else
                <div class="erp-table-responsive erp-table-sticky-header">
                    <table class="erp-table">
                        <thead>
                            <tr>
                                <th class="w-10 text-center">
                                    <input type="checkbox" id="selectAllCheckbox" title="تحديد جميع الأصناف" class="cursor-pointer w-4 h-4">
                                </th>
                                <th class="min-w-[180px] max-w-[240px]">اسم الصنف</th>
                                <th class="w-[110px]">التصنيف</th>
                                <th class="w-[100px]">كود الصنف</th>
                                <th class="w-[120px]">باركود</th>
                                <th class="w-[90px] text-left">سعر الشراء</th>
                                <th class="w-[90px] text-left">سعر التجزئة</th>
                                <th class="w-[90px] text-left">الجملة</th>
                                <th class="w-[90px] text-left">الموزع</th>
                                <th class="w-16 text-center">وحدة القياس</th>
                                <th class="w-[145px] text-center whitespace-nowrap">رصيد الصنف</th>
                                <th class="w-[120px] text-center">إجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($products as $product)
                                <tr id="product-row-{{ $product->id }}" class="product-row-clickable h-10" data-url="{{ route('products.show', $product) }}" title="عرض تفاصيل الصنف: {{ $product->name }}">
                                    <!-- Checkbox -->
                                    <td class="text-center">
                                        <input type="checkbox" 
                                               class="product-select-cb cursor-pointer w-4 h-4" 
                                               value="{{ $product->id }}" 
                                               data-id="{{ $product->id }}" 
                                               data-name="{{ $product->name }}" 
                                               data-sku="{{ $product->sku }}" 
                                               data-barcode="{{ $product->barcode ?: $product->sku }}">
                                    </td>

                                    <!-- 1. اسم الصنف -->
                                    <td class="max-w-[240px]">
                                        <div class="flex items-center gap-2">
                                            @if($product->image_url)
                                                <a href="{{ route('products.show', $product) }}" class="inline-block shrink-0" title="{{ $product->name }}">
                                                    <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="w-6 h-6 object-cover rounded border border-[var(--border-color)]">
                                                </a>
                                            @endif
                                            <div class="min-w-0 flex-1">
                                                <div class="flex items-center gap-1.5">
                                                    <a href="{{ route('products.show', $product) }}" class="font-bold text-[var(--text-main)] hover:text-[var(--primary)] truncate block" title="{{ $product->name }}">
                                                        {{ $product->name }}
                                                    </a>
                                                    @if(! $product->is_active)
                                                        <span class="erp-badge erp-badge-danger text-[10px] py-0 px-1 font-normal shrink-0">معطل</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- 2. التصنيف -->
                                    <td class="whitespace-nowrap">
                                        <span class="erp-badge erp-badge-neutral text-xs whitespace-nowrap">
                                            {{ $product->category?->name ?? 'غير محدد' }}
                                        </span>
                                    </td>

                                    <!-- 3. كود الصنف -->
                                    <td class="whitespace-nowrap">
                                        <a href="{{ route('products.show', $product) }}" class="font-numeric font-bold text-[var(--primary)] hover:underline inline-block whitespace-nowrap" title="عرض تفاصيل الصنف">
                                            {{ $product->sku }}
                                        </a>
                                    </td>

                                    <!-- 4. باركود -->
                                    <td class="whitespace-nowrap">
                                        @if($product->barcode)
                                            <span class="font-numeric font-mono bg-[var(--bg-surface-alt)] py-0.5 px-1.5 rounded text-xs border border-[var(--border-color)] whitespace-nowrap">
                                                {{ $product->barcode }}
                                            </span>
                                        @else
                                            <span class="text-[var(--text-light)] text-[11px] whitespace-nowrap">بدون باركود</span>
                                        @endif
                                    </td>

                                    <!-- 5. سعر الشراء -->
                                    <td class="text-left font-numeric whitespace-nowrap">
                                        <span>{{ number_format((float)$product->cost_price, 2) }}</span>
                                        <small class="text-[var(--text-muted)] text-[10px]">ج.م</small>
                                    </td>

                                    <!-- 6. سعر التجزئة -->
                                    <td class="text-left font-numeric whitespace-nowrap">
                                        <strong class="text-[var(--primary)]">{{ number_format((float)$product->retail_price, 2) }}</strong>
                                        <small class="text-[var(--text-muted)] text-[10px]">ج.م</small>
                                    </td>

                                    <!-- 7. الجملة -->
                                    <td class="text-left font-numeric whitespace-nowrap">
                                        <strong class="text-[var(--success-text)]">{{ number_format((float)$product->wholesale_price, 2) }}</strong>
                                        <small class="text-[var(--text-muted)] text-[10px]">ج.م</small>
                                    </td>

                                    <!-- 8. الموزع -->
                                    <td class="text-left font-numeric whitespace-nowrap">
                                        <span>{{ number_format((float)($product->distributor_price ?? 0), 2) }}</span>
                                        <small class="text-[var(--text-muted)] text-[10px]">ج.م</small>
                                    </td>

                                    <!-- 9. وحدة القياس -->
                                    <td class="text-center text-xs text-[var(--text-muted)] whitespace-nowrap">
                                        {{ $product->unit }}
                                    </td>

                                    <!-- 10. رصيد الصنف -->
                                    <td class="text-center font-numeric font-bold text-xs whitespace-nowrap">
                                        <span class="erp-badge whitespace-nowrap inline-flex items-center justify-center py-0.5 px-2 leading-tight {{ $product->total_stock <= 0 ? 'erp-badge-danger' : ($product->is_low_stock ? 'erp-badge-warning' : 'erp-badge-success') }}" title="{{ $product->formatStockBreakdown() }}">
                                            {{ $product->formatStockBreakdown() }}
                                        </span>
                                    </td>

                                    <!-- 11. إجراءات -->
                                    <td class="text-center whitespace-nowrap">
                                        <div class="flex items-center justify-center gap-1">
                                            <button type="button" class="erp-btn erp-btn-ghost erp-btn-icon" title="طباعة فورية للملصق ({{ (int) ($barcodeSettings->label_width_mm ?? 38) }}×{{ (int) ($barcodeSettings->label_height_mm ?? 15) }} مم) بدون نوافذ" data-action="direct-print-row" data-product-id="{{ $product->id }}">
                                                <x-icon name="printer" size="15" />
                                            </button>
                                            <a href="{{ route('products.show', $product) }}" class="erp-btn erp-btn-ghost erp-btn-icon" title="عرض تفاصيل الصنف">
                                                <x-icon name="eye" size="15" />
                                            </a>
                                            <a href="{{ route('products.edit', $product) }}" class="erp-btn erp-btn-ghost erp-btn-icon" title="تعديل">
                                                <x-icon name="pencil" size="15" />
                                            </a>
                                            <form action="{{ route('products.toggle-status', $product) }}" method="POST" class="inline" data-confirm="هل تريد {{ $product->is_active ? 'تعطيل' : 'تنشيط' }} هذا الصنف: {{ $product->name }}؟">
                                                @csrf
                                                <button type="submit" class="erp-btn erp-btn-ghost erp-btn-icon {{ $product->is_active ? 'text-amber-600 hover:text-amber-700' : 'text-emerald-600 hover:text-emerald-700' }}" title="{{ $product->is_active ? 'تعطيل الصنف' : 'تنشيط الصنف' }}">
                                                    @if($product->is_active)
                                                        <x-icon name="lock" size="15" />
                                                    @else
                                                        <x-icon name="check-circle" size="15" />
                                                    @endif
                                                </button>
                                            </form>
                                            <form action="{{ route('products.destroy', $product) }}" method="POST" data-confirm="هل أنت متأكد من حذف المنتج: {{ $product->name }}؟" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="erp-btn erp-btn-ghost erp-btn-icon text-[var(--danger)]" title="حذف">
                                                    <x-icon name="trash" size="15" />
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Table Footer & Pagination -->
                <div class="erp-table-footer">
                    <div class="erp-table-footer-info">
                        <span class="font-numeric">
                            @if($products->total() > 0)
                                عرض <strong>{{ $products->firstItem() }}</strong> إلى <strong>{{ $products->lastItem() }}</strong> من إجمالي <strong>{{ $products->total() }}</strong> صنف
                            @else
                                لا توجد أصناف مسجلة
                            @endif
                        </span>
                        @if($products->lastPage() > 1)
                            <span class="text-[var(--text-muted)] opacity-40">•</span>
                            <span>
                                صفحة <strong class="font-numeric">{{ $products->currentPage() }}</strong> من <strong class="font-numeric">{{ $products->lastPage() }}</strong>
                            </span>
                        @endif
                        <span class="text-[var(--text-muted)] opacity-40">•</span>
                        <div class="inline-flex items-center gap-1.5">
                            <label for="perPageSelect" class="text-xs text-[var(--text-muted)]">لكل صفحة:</label>
                            <select id="perPageSelect" class="erp-per-page-select font-numeric" data-action="change-per-page">
                                <option value="10" {{ ($currentPerPage ?? 15) == 10 ? 'selected' : '' }}>10</option>
                                <option value="15" {{ ($currentPerPage ?? 15) == 15 ? 'selected' : '' }}>15</option>
                                <option value="25" {{ ($currentPerPage ?? 15) == 25 ? 'selected' : '' }}>25</option>
                                <option value="50" {{ ($currentPerPage ?? 15) == 50 ? 'selected' : '' }}>50</option>
                                <option value="100" {{ ($currentPerPage ?? 15) == 100 ? 'selected' : '' }}>100</option>
                            </select>
                        </div>
                    </div>

                    <div class="erp-table-footer-controls">
                        {{ $products->links() }}
                    </div>
                </div>
            @endif
        </div>

        <!-- Floating Batch Actions Bar -->
        <div id="batchActionBar" class="erp-batch-action-bar hidden">
            <div class="flex items-center gap-3 flex-wrap">
                <span class="erp-badge erp-badge-primary text-xs py-1.5 px-3">
                    <span>تم تحديد:</span>
                    <strong id="selectedProductsCount" class="font-numeric mx-1">0</strong>
                    <span>صنف</span>
                </span>
                <span class="text-xs text-[var(--text-muted)]">
                    جاهز لطباعة ملصقات الباركود على طابعة <strong>{{ $barcodeSettings->printer_name ?: 'طابعة الباركود' }}</strong> ({{ (int) ($barcodeSettings->label_width_mm ?? 38) }}×{{ (int) ($barcodeSettings->label_height_mm ?? 15) }} مم)
                </span>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" data-action="direct-print-batch" class="erp-btn erp-btn-primary" title="طباعة فورية للملصقات مباشرة بدون نوافذ">
                    <x-icon name="printer" size="16" />
                    <span>طباعة فورية للمحددة</span>
                </button>
                <button type="button" data-action="open-batch-modal" class="erp-btn erp-btn-secondary" title="تخصيص عدد النسخ لكل صنف">
                    <x-icon name="sliders" size="16" />
                    <span>تخصيص الكميات</span>
                </button>
                <button type="button" data-action="clear-selections" class="erp-btn erp-btn-secondary">
                    <x-icon name="x" size="14" />
                    <span>إلغاء التحديد</span>
                </button>
            </div>
        </div>

        <!-- Batch Barcode Print Modal -->
        <div id="batchPrintModal" class="erp-modal-overlay hidden">
            <div class="erp-card erp-modal-container max-w-[650px]">
                <!-- Modal Header -->
                <div class="erp-card-header erp-modal-header">
                    <div class="erp-card-title erp-modal-title">
                        <x-icon name="printer" size="18" />
                        <span>طباعة ملصقات الباركود للمنتجات المحددة ({{ (int) ($barcodeSettings->label_width_mm ?? 38) }}×{{ (int) ($barcodeSettings->label_height_mm ?? 15) }} مم)</span>
                    </div>
                    <button type="button" data-action="close-batch-modal" class="erp-btn erp-btn-ghost erp-btn-icon erp-modal-close" title="إغلاق">
                        <x-icon name="x" size="16" />
                    </button>
                </div>

                <!-- Modal Sub-header: Global Quantity Controls -->
                <div class="bg-[var(--bg-surface)] py-3 px-4.5 border-b border-[var(--border-color)] flex items-center justify-between flex-wrap gap-2.5 shrink-0">
                    <div class="text-xs font-semibold text-[var(--text-main)]">
                        تعيين كمية موحدة للجميع:
                    </div>
                    <div class="flex gap-1.5">
                        <button type="button" data-preset-qty="1" class="erp-btn erp-btn-ghost font-numeric py-1 px-2.5 text-xs">1 ملصق</button>
                        <button type="button" data-preset-qty="2" class="erp-btn erp-btn-ghost font-numeric py-1 px-2.5 text-xs">2 ملصق</button>
                        <button type="button" data-preset-qty="5" class="erp-btn erp-btn-ghost font-numeric py-1 px-2.5 text-xs">5 ملصقات</button>
                        <button type="button" data-preset-qty="10" class="erp-btn erp-btn-ghost font-numeric py-1 px-2.5 text-xs">10 ملصقات</button>
                        <button type="button" data-preset-qty="20" class="erp-btn erp-btn-ghost font-numeric py-1 px-2.5 text-xs">20 ملصق</button>
                    </div>
                </div>

                <!-- Modal Body: Scrollable Selected Items Table -->
                <div class="p-0 overflow-y-auto flex-1">
                    <form id="batchPrintForm" action="{{ route('products.print-batch') }}" method="POST" target="_blank">
                        @csrf
                        <input type="hidden" name="autoprint" id="batchFormAutoprint" value="0">
                        <div id="batchFormHiddenInputs"></div>

                        <table class="erp-table m-0">
                            <thead>
                                <tr class="bg-[var(--bg-surface-alt)] sticky top-0 z-10">
                                    <th class="w-[50px] text-center">#</th>
                                    <th>اسم المنتج</th>
                                    <th class="w-[140px]">كود الصنف (SKU)</th>
                                    <th class="w-[160px] text-center">عدد الملصقات</th>
                                </tr>
                            </thead>
                            <tbody id="batchModalTableBody">
                                <!-- Populated via JavaScript -->
                            </tbody>
                        </table>
                    </form>
                </div>

                <!-- Modal Footer: Summary & Actions -->
                <div class="erp-card-footer bg-[var(--bg-surface-alt)] border-t border-[var(--border-color)] py-3 px-4.5 flex items-center justify-between flex-wrap gap-3 shrink-0">
                    <div class="text-xs">
                        <span>إجمالي الأصناف: <strong id="batchSummaryProductsCount" class="font-numeric">0</strong></span>
                        <span class="mx-2 text-[var(--border-color)]">|</span>
                        <span>إجمالي الملصقات: <strong id="batchSummaryTotalLabels" class="font-numeric text-[var(--primary)]">0</strong> ملصق</span>
                    </div>
                    <div class="flex gap-2">
                        <button type="button" data-action="close-batch-modal" class="erp-btn erp-btn-secondary">
                            <span>إلغاء</span>
                        </button>
                        <button type="button" data-action="submit-batch-print" data-autoprint="true" class="erp-btn erp-btn-primary" title="طباعة فورية مباشرة لجميع الملصقات على طابعة الباركود">
                            <x-icon name="printer" size="16" />
                            <span>طباعة فورية للطابعة</span>
                        </button>
                        <button type="button" data-action="submit-batch-print" data-autoprint="false" class="erp-btn erp-btn-secondary" title="فتح المعاينة في نافذة جديدة بالمتصفح">
                            <x-icon name="eye" size="16" />
                            <span>معاينة في المتصفح</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <x-barcode-scanner-modal target-input-id="productSearchInput" />

        <!-- Toast Notification for Direct Print Feedback -->
        <div id="printNotificationToast" class="erp-toast hidden">
            <span class="erp-toast-icon"><x-icon name="printer" size="18" /></span>
            <span id="printToastMessage" class="erp-toast-message">جاري إرسال الملصق للطباعة...</span>
        </div>
    </div>
@endsection
