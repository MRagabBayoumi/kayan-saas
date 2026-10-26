@extends('layouts.portal', ['title' => 'كتالوج المنتجات'])

@section('content')
<div class="space-y-4">

    <!-- Header & Search Controls (Kayan ERP Card) -->
    <div class="erp-card mb-0 p-3 sm:p-4">
        <div class="flex items-center justify-between mb-2.5 pb-2 border-b border-[var(--border-light)]">
            <h1 class="text-xs sm:text-base font-bold text-[var(--text-main)] flex items-center gap-2">
                <x-icon name="package" size="17" class="text-[var(--primary)]" />
                <span>كتالوج منتجات المعرض والمخازن</span>
            </h1>
            <span class="erp-badge erp-badge-neutral font-numeric text-[11px] sm:text-xs">
                {{ $products->total() }} صنف
            </span>
        </div>

        <form method="GET" action="{{ route('portal.catalog') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-2.5">
            <!-- Search Bar with integrated camera scanner -->
            <div class="relative sm:col-span-6 lg:col-span-6">
                <input type="search" id="catalogSearchInput" name="search" value="{{ $currentSearch }}" placeholder="ابحث باسم المنتج، الكود، أو الباركود..."
                    class="erp-input w-full pr-9 pl-10 text-xs sm:text-sm">
                <div class="absolute right-3 top-1/2 -translate-y-1/2 text-[var(--text-muted)] pointer-events-none">
                    <x-icon name="search" size="15" />
                </div>
                <button type="button" data-action="open-barcode-scanner" data-target-input="catalogSearchInput" title="مسح الباركود بالكاميرا" 
                    class="absolute left-1.5 top-1/2 -translate-y-1/2 p-1.5 text-[var(--primary)] hover:bg-[var(--primary-soft)] rounded transition-colors flex items-center justify-center">
                    <x-icon name="camera" size="16" />
                </button>
            </div>

            <!-- Category Filter -->
            <div class="sm:col-span-3 lg:col-span-3">
                <select name="category_id" class="portal-auto-submit erp-select text-xs">
                    <option value="">جميع الأقسام والتصنيفات</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ (string)$currentCategory === (string)$cat->id ? 'selected' : '' }}>
                            {{ $cat->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- In Stock Checkbox & Submit -->
            <div class="flex items-center gap-2 sm:col-span-3 lg:col-span-3">
                <label class="flex items-center gap-2 px-3 py-2 bg-[var(--bg-hover)] border border-[var(--border-color)] rounded text-xs text-[var(--text-main)] cursor-pointer select-none flex-1 justify-center h-[38px]">
                    <input type="checkbox" name="in_stock_only" value="1" {{ $inStockOnly ? 'checked' : '' }} class="portal-auto-submit rounded border-[var(--border-color)] text-[var(--primary)] focus:ring-[var(--primary)]">
                    <span class="font-medium">المتوفر فقط</span>
                </label>

                <button type="submit" class="erp-btn erp-btn-primary shrink-0 text-xs h-[38px] px-4">
                    <x-icon name="filter" size="13" />
                    <span>تصفية</span>
                </button>

                @if($currentSearch || $currentCategory || $inStockOnly)
                    <a href="{{ route('portal.catalog') }}" class="erp-btn erp-btn-secondary shrink-0 text-xs h-[38px] px-3" title="إعادة تعيين">
                        <x-icon name="rotate-ccw" size="13" />
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Product Cards Responsive Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3 sm:gap-4">
        @php
            $isAdmin = auth()->user()?->isAdmin() || auth()->user()?->can('manage-inventory');
        @endphp

        @forelse($products as $product)
            @php
                $totalStock = (float) $product->stockBalances->sum('quantity');
                $threshold = ($product->min_stock_alert && (float)$product->min_stock_alert > 0) ? (float)$product->min_stock_alert : 1.0;
                $isAvailable = $totalStock >= $threshold;
                $stockDisplay = $isAdmin
                    ? ($isAvailable ? 'متوفر (' . $totalStock . ' ' . ($product->unit ?? 'قطعة') . ')' : 'غير متوفر (' . $totalStock . ' / حد أدنى ' . $threshold . ')')
                    : ($isAvailable ? 'متوفر للطلب' : 'غير متوفر حالياً');
            @endphp
            <div class="erp-card mb-0 p-3 sm:p-3.5 hover:border-[var(--primary-border)] hover:shadow-md transition-all flex flex-col justify-between">
                <div>
                    <!-- Product Image Card with Customer Showcase Action -->
                    <div class="relative group cursor-pointer overflow-hidden rounded-lg bg-slate-50 border border-slate-200/80 mb-2.5 h-36 sm:h-44 flex items-center justify-center transition-all hover:bg-slate-100"
                         data-action="open-showcase"
                         data-product-id="{{ $product->id }}"
                         data-product-name="{{ $product->name }}"
                         data-product-sku="{{ $product->sku ?? '---' }}"
                         data-product-barcode="{{ $product->barcode ?? '---' }}"
                         data-product-category="{{ $product->category?->name ?? 'عام' }}"
                         data-product-price="{{ number_format($product->wholesale_price, 2) }}"
                         data-product-unit="{{ $product->unit ?? 'قطعة' }}"
                         data-product-package="{{ $product->pieces_per_package }}"
                         data-product-image="{{ $product->image_url ?? '' }}"
                         data-product-available="{{ $isAvailable ? '1' : '0' }}"
                         data-product-stock-display="{{ $stockDisplay }}"
                         data-order-url="{{ route('portal.orders.create', ['product_id' => $product->id]) }}"
                         title="انقر لفتح معاينة المنتج وعرضه للعميل">
                        
                        @if($product->image_url)
                            <img src="{{ $product->image_url }}" alt="{{ $product->name }}" loading="lazy" class="w-full h-full object-contain p-2 group-hover:scale-105 transition-transform duration-200">
                        @else
                            <div class="flex flex-col items-center justify-center text-slate-300 py-3">
                                <x-icon name="image" size="28" class="text-slate-300 mb-1" />
                                <span class="text-[10px] text-slate-400 font-medium">لا توجد صورة</span>
                            </div>
                        @endif

                        <!-- Showcase Badge Pill -->
                        <div class="absolute top-2 left-2 bg-black/60 backdrop-blur-xs text-white text-[10px] font-medium py-0.5 px-2 rounded-full flex items-center gap-1 shadow-xs group-hover:bg-[#714B67] transition-colors">
                            <x-icon name="eye" size="11" class="text-sky-300" />
                            <span>عرض للعميل</span>
                        </div>
                    </div>

                    <!-- Header: Name & Category -->
                    <div class="flex items-start justify-between gap-2 mb-1.5">
                        <div class="min-w-0 flex-1">
                            <h2 class="text-xs sm:text-sm font-bold text-[var(--text-main)] leading-snug line-clamp-2">{{ $product->name }}</h2>
                            <div class="flex flex-wrap items-center gap-2 text-[10px] sm:text-[11px] text-[var(--text-muted)] mt-0.5 font-numeric">
                                @if($product->sku)
                                    <span>كود: <strong class="text-[var(--text-secondary)]">{{ $product->sku }}</strong></span>
                                @endif
                                @if($product->barcode)
                                    <span>باركود: <strong class="text-[var(--text-secondary)]">{{ $product->barcode }}</strong></span>
                                @endif
                            </div>
                        </div>

                        @if($product->category)
                            <span class="erp-badge erp-badge-neutral text-[9px] sm:text-[10px] shrink-0">
                                {{ $product->category->name }}
                            </span>
                        @endif
                    </div>

                    <!-- Wholesale Price Only (Prompt Requirement) -->
                    <div class="p-2 rounded-lg bg-emerald-50/80 border border-emerald-200/80 my-2 flex items-center justify-between">
                        <span class="text-xs font-bold text-emerald-900">سعر الجملة:</span>
                        <div class="text-left font-numeric">
                            <span class="text-sm sm:text-base font-extrabold text-emerald-700">{{ number_format($product->wholesale_price, 2) }}</span>
                            <span class="text-[10px] font-semibold text-emerald-600 mr-0.5">ج.م</span>
                        </div>
                    </div>
                </div>

                <!-- Footer: Availability Status & Actions -->
                <div class="flex items-center justify-between pt-2.5 border-t border-[var(--border-light)] mt-1 gap-2">
                    <div class="flex items-center gap-1.5 min-w-0">
                        @if($isAvailable)
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 shrink-0"></span>
                            @if($isAdmin)
                                <span class="text-xs font-bold text-emerald-700 font-numeric truncate">
                                    متوفر ({{ $totalStock }} {{ $product->unit ?? 'قطعة' }})
                                </span>
                            @else
                                <span class="text-xs font-bold text-emerald-700 truncate">
                                    متوفر للطلب
                                </span>
                            @endif
                        @else
                            <span class="w-2.5 h-2.5 rounded-full bg-rose-500 shrink-0"></span>
                            @if($isAdmin)
                                <span class="text-xs font-bold text-rose-600 font-numeric truncate">
                                    غير متوفر ({{ $totalStock }} / حد: {{ $threshold }})
                                </span>
                            @else
                                <span class="text-xs font-bold text-rose-600 truncate">
                                    غير متوفر حالياً
                                </span>
                            @endif
                        @endif
                    </div>

                    <div class="flex items-center gap-1.5 shrink-0">
                        <button type="button" 
                            data-action="open-showcase"
                            data-product-id="{{ $product->id }}"
                            data-product-name="{{ $product->name }}"
                            data-product-sku="{{ $product->sku ?? '---' }}"
                            data-product-barcode="{{ $product->barcode ?? '---' }}"
                            data-product-category="{{ $product->category?->name ?? 'عام' }}"
                            data-product-price="{{ number_format($product->wholesale_price, 2) }}"
                            data-product-unit="{{ $product->unit ?? 'قطعة' }}"
                            data-product-package="{{ $product->pieces_per_package }}"
                            data-product-image="{{ $product->image_url ?? '' }}"
                            data-product-available="{{ $isAvailable ? '1' : '0' }}"
                            data-product-stock-display="{{ $stockDisplay }}"
                            data-order-url="{{ route('portal.orders.create', ['product_id' => $product->id]) }}"
                            class="erp-btn erp-btn-sm erp-btn-secondary text-[11px] py-1 px-2"
                            title="معاينة للعميل">
                            <x-icon name="eye" size="13" class="text-sky-600" />
                            <span class="hidden sm:inline">معاينة</span>
                        </button>

                        <a href="{{ route('portal.orders.create', ['product_id' => $product->id]) }}" class="erp-btn erp-btn-sm erp-btn-primary text-[11px] py-1 px-2.5" title="بدء طلب بيع لهذا الصنف">
                            <x-icon name="plus" size="12" />
                            <span>طلب</span>
                        </a>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full erp-card p-12 text-center">
                <x-icon name="inbox" size="36" class="text-slate-300 mx-auto mb-2" />
                <div class="text-sm font-bold text-[var(--text-main)]">لا توجد منتجات مطابقة للبحث</div>
                <div class="text-xs text-[var(--text-muted)] mt-1">جرب تغيير مصطلح البحث أو إزالة التصفية</div>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($products->hasPages())
        <div class="erp-card p-3 mb-0">
            {{ $products->links() }}
        </div>
    @endif

</div>

<!-- Customer Product Showcase Modal (نافذة استعراض المنتج للعميل) -->
<div id="productShowcaseModal" class="erp-modal-overlay" style="display: none;">
    <div class="erp-modal-container max-w-lg w-full">
        <div class="erp-modal-header bg-[var(--bg-hover)]">
            <div class="erp-modal-title flex items-center gap-2">
                <x-icon name="smartphone" size="18" class="text-[var(--primary)]" />
                <span>استعراض المنتج للعميل</span>
                <span id="showcaseCategory" class="erp-badge erp-badge-neutral text-[10px] mr-1"></span>
            </div>
            <button type="button" class="erp-modal-close" onclick="closeShowcaseModal()" title="إغلاق">✕</button>
        </div>

        <div class="erp-modal-body p-4 sm:p-5 space-y-4 overflow-y-auto max-h-[75vh]">
            <!-- Big Image with Lightbox Zoom Trigger -->
            <div class="relative bg-slate-50 border border-[var(--border-light)] rounded-xl overflow-hidden h-64 sm:h-72 flex items-center justify-center cursor-pointer group"
                 id="showcaseImageWrapper"
                 onclick="openFullscreenLightbox()"
                 title="انقر لتكبير الصورة على كامل الشاشة للعميل">
                <img id="showcaseImage" src="" alt="" class="w-full h-full object-contain p-3 group-hover:scale-105 transition-transform duration-200">
                <div id="showcaseNoImage" class="hidden flex-col items-center justify-center text-slate-300">
                    <x-icon name="image" size="48" class="text-slate-300 mb-2" />
                    <span class="text-xs text-slate-400">لا توجد صورة لهذا المنتج</span>
                </div>
                <div id="showcaseZoomHint" class="absolute bottom-2 left-2 bg-black/60 backdrop-blur-xs text-white text-[10px] px-2.5 py-1 rounded-full flex items-center gap-1.5 opacity-90 group-hover:opacity-100">
                    <x-icon name="maximize" size="12" class="text-sky-300" />
                    <span>تكبير الصورة للعميل</span>
                </div>
            </div>

            <!-- Product Details -->
            <div>
                <h3 id="showcaseTitle" class="text-base sm:text-lg font-bold text-[var(--text-main)] leading-snug"></h3>
                <div class="flex flex-wrap items-center gap-3 text-xs text-[var(--text-muted)] mt-1.5 font-numeric">
                    <span>كود المنتج: <strong id="showcaseSku" class="text-[var(--text-secondary)]"></strong></span>
                    <span>باركود: <strong id="showcaseBarcode" class="text-[var(--text-secondary)]"></strong></span>
                </div>
            </div>

            <!-- Wholesale Price & Availability Highlight -->
            <div class="grid grid-cols-2 gap-3 p-3.5 rounded-xl bg-slate-50 border border-slate-200">
                <div>
                    <span class="text-[11px] font-bold text-slate-500 block mb-0.5">سعر الجملة</span>
                    <div class="font-numeric">
                        <span id="showcasePrice" class="text-xl sm:text-2xl font-black text-emerald-700"></span>
                        <span class="text-xs font-bold text-emerald-600 mr-0.5">ج.م</span>
                    </div>
                </div>

                <div class="text-left flex flex-col justify-center items-end">
                    <span class="text-[11px] font-bold text-slate-500 block mb-1">حالة التوفر</span>
                    <div id="showcaseStockStatus"></div>
                </div>
            </div>

            <!-- Packaging & Unit Information -->
            <div id="showcasePackageInfo" class="text-xs text-[var(--text-secondary)] p-3 rounded-lg bg-[var(--bg-hover)] border border-[var(--border-light)] flex items-center justify-between">
                <span>معلومات التعبئة:</span>
                <strong id="showcaseUnitDetails" class="text-[var(--text-main)] font-numeric"></strong>
            </div>
        </div>

        <div class="erp-modal-footer p-4 flex items-center justify-between gap-2.5 bg-[var(--bg-surface-alt)] border-t border-[var(--border-light)]">
            <button type="button" onclick="closeShowcaseModal()" class="erp-btn erp-btn-secondary text-xs px-4">
                <span>إغلاق</span>
            </button>
            <a id="showcaseOrderBtn" href="" class="erp-btn erp-btn-primary text-xs px-5 py-2">
                <x-icon name="plus" size="14" />
                <span>بدء طلب بيع لهذا المنتج</span>
            </a>
        </div>
    </div>
</div>

<!-- Fullscreen Lightbox Modal (تكبير الصورة للعميل على كامل الشاشة) -->
<div id="imageLightboxModal" class="erp-modal-overlay" style="display: none; background-color: rgba(0,0,0,0.92);" onclick="closeFullscreenLightbox()">
    <div class="relative w-full h-full flex flex-col items-center justify-center p-4">
        <button type="button" onclick="closeFullscreenLightbox()" class="absolute top-4 right-4 text-white hover:text-rose-400 bg-white/10 hover:bg-white/20 rounded-full p-2.5 transition-colors z-50">
            <x-icon name="x" size="24" />
        </button>
        <img id="lightboxImage" src="" alt="" class="max-w-[95vw] max-h-[85vh] object-contain rounded-lg shadow-2xl select-none" onclick="event.stopPropagation()">
        <div id="lightboxCaption" class="text-white text-sm font-bold mt-3 text-center px-4 py-1.5 bg-black/50 rounded-full backdrop-blur-xs select-none"></div>
    </div>
</div>

@push('scripts')
<script>
    let currentShowcaseImageUrl = '';
    let currentShowcaseTitle = '';

    function openShowcaseModal(data) {
        const modal = document.getElementById('productShowcaseModal');
        if (!modal) return;

        currentShowcaseImageUrl = data.image || '';
        currentShowcaseTitle = data.name || '';

        document.getElementById('showcaseTitle').textContent = data.name || '';
        document.getElementById('showcaseCategory').textContent = data.category || 'عام';
        document.getElementById('showcaseSku').textContent = data.sku || '---';
        document.getElementById('showcaseBarcode').textContent = data.barcode || '---';
        document.getElementById('showcasePrice').textContent = data.price || '0.00';
        document.getElementById('showcaseOrderBtn').href = data.orderUrl || '#';

        // Image Handling
        const imgEl = document.getElementById('showcaseImage');
        const noImgEl = document.getElementById('showcaseNoImage');
        const zoomHint = document.getElementById('showcaseZoomHint');
        const wrapper = document.getElementById('showcaseImageWrapper');

        if (data.image && data.image.trim() !== '') {
            imgEl.src = data.image;
            imgEl.classList.remove('hidden');
            noImgEl.classList.add('hidden');
            zoomHint.classList.remove('hidden');
            wrapper.classList.add('cursor-pointer');
        } else {
            imgEl.src = '';
            imgEl.classList.add('hidden');
            noImgEl.classList.remove('hidden');
            zoomHint.classList.add('hidden');
            wrapper.classList.remove('cursor-pointer');
        }

        // Availability badge
        const stockStatusEl = document.getElementById('showcaseStockStatus');
        if (data.available === '1') {
            stockStatusEl.innerHTML = `<span class="erp-badge erp-badge-success text-xs font-bold px-3 py-1 flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                <span>${data.stockDisplay || ''}</span>
            </span>`;
        } else {
            stockStatusEl.innerHTML = `<span class="erp-badge erp-badge-danger text-xs font-bold px-3 py-1 flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                <span>${data.stockDisplay || ''}</span>
            </span>`;
        }

        // Packaging / Unit details
        const packageInfo = document.getElementById('showcasePackageInfo');
        const unitDetails = document.getElementById('showcaseUnitDetails');
        let pkgText = `الوحدة: ${data.unit || 'قطعة'}`;
        if (data.package && Number(data.package) > 1) {
            pkgText += ` (${data.package} قطعة بالعبوة/الدستة)`;
        }
        unitDetails.textContent = pkgText;

        modal.classList.add('open');
        modal.classList.remove('hidden');
        modal.style.display = 'flex';
    }

    function closeShowcaseModal() {
        const modal = document.getElementById('productShowcaseModal');
        if (modal) {
            modal.classList.remove('open');
            modal.classList.add('hidden');
            modal.style.display = 'none';
        }
    }

    function openFullscreenLightbox() {
        if (!currentShowcaseImageUrl) return;

        const lightbox = document.getElementById('imageLightboxModal');
        const img = document.getElementById('lightboxImage');
        const cap = document.getElementById('lightboxCaption');

        if (lightbox && img) {
            img.src = currentShowcaseImageUrl;
            cap.textContent = currentShowcaseTitle;
            lightbox.classList.add('open');
            lightbox.classList.remove('hidden');
            lightbox.style.display = 'flex';
        }
    }

    function closeFullscreenLightbox() {
        const lightbox = document.getElementById('imageLightboxModal');
        if (lightbox) {
            lightbox.classList.remove('open');
            lightbox.classList.add('hidden');
            lightbox.style.display = 'none';
        }
    }

    // Delegated click listener for showcase triggers
    document.addEventListener('click', function(e) {
        const showcaseBtn = e.target.closest('[data-action="open-showcase"]');
        if (showcaseBtn) {
            e.preventDefault();
            const data = {
                id: showcaseBtn.getAttribute('data-product-id'),
                name: showcaseBtn.getAttribute('data-product-name'),
                sku: showcaseBtn.getAttribute('data-product-sku'),
                barcode: showcaseBtn.getAttribute('data-product-barcode'),
                category: showcaseBtn.getAttribute('data-product-category'),
                price: showcaseBtn.getAttribute('data-product-price'),
                unit: showcaseBtn.getAttribute('data-product-unit'),
                package: showcaseBtn.getAttribute('data-product-package'),
                image: showcaseBtn.getAttribute('data-product-image'),
                available: showcaseBtn.getAttribute('data-product-available'),
                stockDisplay: showcaseBtn.getAttribute('data-product-stock-display'),
                orderUrl: showcaseBtn.getAttribute('data-order-url'),
            };
            openShowcaseModal(data);
            return;
        }

        // Close on backdrop click for showcase
        const showcaseModal = document.getElementById('productShowcaseModal');
        if (e.target === showcaseModal) {
            closeShowcaseModal();
        }
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeFullscreenLightbox();
            closeShowcaseModal();
        }
    });
</script>
@endpush
@endsection
