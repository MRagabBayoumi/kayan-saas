@extends('shop.layout')

@section('content')
<div>

    <!-- Store Hero Banner -->
    <div class="shop-hero">
        <div class="hero-tag">
            <x-icon name="sparkles" size="13" />
            <span>المتجر الرسمي المعتمد</span>
        </div>
        <h1 class="hero-title">
            {{ $settings->store_name ?? 'متجرنا الإلكتروني المتكامل' }}
        </h1>
        <p class="hero-desc">
            {{ $settings->store_tagline ?? 'اكتشف مجموعتنا المختارة من أفضل المنتجات بأسعار الجملة والتجزئة مع شحن سريع حتى باب المنزل.' }}
        </p>

        <!-- Trust Badges -->
        <div class="hero-badges">
            <div class="hero-badge-pill">
                <x-icon name="truck" size="14" style="color: #34d399;" />
                <span>شحن مجاني للطلبات فوق {{ number_format($settings->free_shipping_threshold, 0) }} ج.م</span>
            </div>
            <div class="hero-badge-pill">
                <x-icon name="shield-check" size="14" style="color: #818cf8;" />
                <span>الدفع عند الاستلام نقداً (COD)</span>
            </div>
            <div class="hero-badge-pill">
                <x-icon name="check-circle" size="14" style="color: #60a5fa;" />
                <span>ربط فوري بالمخازن</span>
            </div>
        </div>
    </div>

    <!-- Category Filters Pills -->
    @if($categories->count() > 0)
        <div class="shop-categories-bar">
            <a href="{{ route('shop.catalog') }}" 
               class="cat-pill {{ !request('category') ? 'active' : '' }}">
                جميع الأصناف
            </a>
            @foreach($categories as $category)
                <a href="{{ route('shop.catalog', ['category' => $category->id]) }}" 
                   class="cat-pill {{ request('category') == $category->id ? 'active' : '' }}">
                    {{ $category->name }}
                </a>
            @endforeach
        </div>
    @endif

    <!-- Section Header -->
    <div class="shop-section-head">
        <div>
            <h2 class="shop-section-title">المنتجات المعروضة</h2>
            <div class="shop-section-sub">إجمالي {{ $products->total() }} منتج متاح للطلب الفوري</div>
        </div>
    </div>

    @if($products->isEmpty())
        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 20px; padding: 48px 24px; text-align: center; max-width: 480px; margin: 24px auto;">
            <div class="product-placeholder-icon" style="margin: 0 auto 16px auto; width: 64px; height: 64px;">
                <x-icon name="shopping-bag" size="28" />
            </div>
            <h3 style="font-size: 16px; font-weight: 800; color: #0f172a; margin: 0 0 6px 0;">لا توجد منتجات مطابقة</h3>
            <p style="font-size: 12px; color: #64748b; margin: 0 0 20px 0;">لم يتم العثور على أي منتج يطابق معايير البحث الحالية.</p>
            <a href="{{ route('shop.catalog') }}" class="btn-primary-shop" style="width: auto; display: inline-flex; padding: 10px 24px;">
                عرض كافة المنتجات
            </a>
        </div>
    @else
        <!-- Products Grid -->
        <div class="products-grid">
            @foreach($products as $product)
                @php
                    $inStock = $product->total_stock > 0;
                    $price = $product->effective_online_price;
                @endphp
                <div class="product-card">
                    
                    <!-- Product Image Box -->
                    <a href="{{ route('shop.product', $product) }}" class="product-thumb">
                        @if($product->image_path)
                            <img src="{{ Storage::url($product->image_path) }}" 
                                 alt="{{ $product->name }}" 
                                 loading="lazy">
                        @else
                            <div class="product-placeholder-icon">
                                <x-icon name="package" size="28" />
                            </div>
                        @endif

                        <!-- Stock Pill Badge -->
                        @if($inStock)
                            <span class="stock-pill in-stock">
                                <span style="width: 6px; height: 6px; border-radius: 50%; background: #10b981;"></span>
                                متوفر ({{ (int) $product->total_stock }})
                            </span>
                        @else
                            <span class="stock-pill out-of-stock">
                                <span style="width: 6px; height: 6px; border-radius: 50%; background: #f43f5e;"></span>
                                نفد المخزون
                            </span>
                        @endif

                        @if($product->category)
                            <span class="category-tag">
                                {{ $product->category->name }}
                            </span>
                        @endif
                    </a>

                    <!-- Product Info Body -->
                    <div class="product-body">
                        <div>
                            <a href="{{ route('shop.product', $product) }}" class="product-title" title="{{ $product->name }}">
                                {{ $product->name }}
                            </a>
                            <div class="product-meta">
                                <span>كود: {{ $product->sku }}</span>
                                <span>&bull;</span>
                                <span>{{ $product->unit ?? 'قطعة' }}</span>
                            </div>
                        </div>

                        <!-- Price and Add Button -->
                        <div class="product-bottom">
                            <div class="price-box">
                                <span class="price-label">السعر</span>
                                <div>
                                    <span class="price-val">{{ number_format($price, 2) }}</span>
                                    <span class="price-unit">ج.م</span>
                                </div>
                            </div>

                            @if($inStock)
                                <form action="{{ route('shop.cart.add') }}" method="POST" style="margin: 0;">
                                    @csrf
                                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                                    <input type="hidden" name="quantity" value="1">
                                    <button type="submit" class="btn-add-cart" title="إضافة إلى السلة">
                                        <x-icon name="plus" size="18" />
                                    </button>
                                </form>
                            @else
                                <button disabled class="btn-add-cart" title="غير متوفر">
                                    <x-icon name="x" size="16" />
                                </button>
                            @endif
                        </div>
                    </div>

                </div>
            @endforeach
        </div>

        <!-- Pagination -->
        <div style="margin-top: 24px; display: flex; justify-content: center;">
            {{ $products->links() }}
        </div>
    @endif

</div>
@endsection
