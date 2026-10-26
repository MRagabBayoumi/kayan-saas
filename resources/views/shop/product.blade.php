@extends('shop.layout')

@section('content')
<div style="margin-top: 8px;">

    <!-- Breadcrumb Nav -->
    <nav style="display: flex; align-items: center; gap: 8px; font-size: 12px; color: #64748b; margin-bottom: 16px;">
        <a href="{{ route('shop.catalog') }}" style="color: inherit; text-decoration: none;">المتجر</a>
        <x-icon name="chevron-left" size="13" style="color: #cbd5e1;" />
        @if($product->category)
            <a href="{{ route('shop.catalog', ['category' => $product->category->id]) }}" style="color: inherit; text-decoration: none;">
                {{ $product->category->name }}
            </a>
            <x-icon name="chevron-left" size="13" style="color: #cbd5e1;" />
        @endif
        <span style="color: #0f172a; font-weight: 700;">{{ $product->name }}</span>
    </nav>

    <!-- Product Showcase Card -->
    <div class="product-detail-card">
        
        <!-- Image Box -->
        <div class="product-detail-img">
            @if($product->image_path)
                <img src="{{ Storage::url($product->image_path) }}" alt="{{ $product->name }}">
            @else
                <div class="product-placeholder-icon" style="width: 80px; height: 80px;">
                    <x-icon name="package" size="40" />
                </div>
            @endif

            @if($product->total_stock > 0)
                <span class="stock-pill in-stock" style="top: 16px; right: 16px; padding: 5px 12px; font-size: 12px;">
                    <span style="width: 7px; height: 7px; border-radius: 50%; background: #10b981;"></span>
                    متوفر بالمخزن ({{ (int) $product->total_stock }} {{ $product->unit ?? 'قطعة' }})
                </span>
            @else
                <span class="stock-pill out-of-stock" style="top: 16px; right: 16px; padding: 5px 12px; font-size: 12px;">
                    <span style="width: 7px; height: 7px; border-radius: 50%; background: #f43f5e;"></span>
                    نفد المخزون مؤقتاً
                </span>
            @endif
        </div>

        <!-- Info & Cart Panel -->
        <div style="display: flex; flex-direction: column; justify-content: space-between; gap: 24px;">
            <div style="display: flex; flex-direction: column; gap: 12px;">
                @if($product->category)
                    <div>
                        <span style="display: inline-block; font-size: 11px; font-weight: 700; color: #4f46e5; background: #eef2ff; border: 1px solid #c7d2fe; padding: 3px 10px; border-radius: 8px;">
                            {{ $product->category->name }}
                        </span>
                    </div>
                @endif

                <h1 style="font-size: 24px; font-weight: 800; color: #0f172a; line-height: 1.3; margin: 0;">
                    {{ $product->name }}
                </h1>

                <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 12px; font-size: 12px; color: #64748b; padding-bottom: 12px; border-bottom: 1px solid #f1f5f9;">
                    <span>كود المنتج: <strong style="color: #0f172a; font-family: monospace;">{{ $product->sku }}</strong></span>
                    @if($product->barcode)
                        <span>&bull;</span>
                        <span>الباركود: <strong style="color: #0f172a; font-family: monospace;">{{ $product->barcode }}</strong></span>
                    @endif
                    <span>&bull;</span>
                    <span>الوحدة: <strong style="color: #0f172a;">{{ $product->unit ?? 'قطعة' }}</strong></span>
                </div>

                <!-- Price Box -->
                <div style="padding: 8px 0;">
                    <span style="font-size: 11px; color: #94a3b8; display: block; margin-bottom: 4px;">السعر الرسمي</span>
                    <div style="display: flex; align-items: baseline; gap: 6px;">
                        <span style="font-size: 30px; font-weight: 900; color: #4338ca;">
                            {{ number_format($product->effective_online_price, 2) }}
                        </span>
                        <span style="font-size: 14px; font-weight: 700; color: #64748b;">جنيه مصري</span>
                    </div>
                </div>

                <!-- Description -->
                <div>
                    <h4 style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; margin: 0 0 8px 0;">وصف المنتج ومميزاته</h4>
                    <div style="font-size: 13px; color: #334155; line-height: 1.7; background: #f8fafc; border: 1px solid #f1f5f9; padding: 16px; border-radius: 16px; white-space: pre-line;">
                        {{ $product->online_description ?: ($product->name . ' - منتج أصلي عالي الجودة متوفر للطلب المباشر مع ضمان وسرعة التوصيل.') }}
                    </div>
                </div>
            </div>

            <!-- Form -->
            <div style="padding-top: 16px; border-top: 1px solid #f1f5f9; display: flex; flex-direction: column; gap: 16px;">
                @if($product->total_stock > 0)
                    <form action="{{ route('shop.cart.add') }}" method="POST" style="display: flex; flex-direction: column; gap: 14px; margin: 0;">
                        @csrf
                        <input type="hidden" name="product_id" value="{{ $product->id }}">
                        
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <label for="quantity" style="font-size: 13px; font-weight: 700; color: #334155;">الكمية:</label>
                            <div class="qty-control">
                                <button type="button" onclick="decrementQty()" class="qty-btn">-</button>
                                <input type="number" 
                                       id="quantity" 
                                       name="quantity" 
                                       value="1" 
                                       min="1" 
                                       max="{{ (int) $product->total_stock }}" 
                                       class="qty-input">
                                <button type="button" onclick="incrementQty({{ (int) $product->total_stock }})" class="qty-btn">+</button>
                            </div>
                            <span style="font-size: 11px; color: #94a3b8;">(أقصى حد متاح: {{ (int) $product->total_stock }})</span>
                        </div>

                        <button type="submit" class="btn-primary-shop">
                            <x-icon name="shopping-cart" size="18" />
                            <span>إضافة إلى سلة المشتريات</span>
                        </button>
                    </form>
                @else
                    <div style="background: #fff1f2; border: 1px solid #fecdd3; border-radius: 14px; padding: 14px; text-align: center;">
                        <strong style="color: #be123c; font-size: 13px; display: block; margin-bottom: 2px;">المنتج غير متوفر حالياً بالمخزن</strong>
                        <span style="font-size: 11px; color: #9f1239;">يمكنك متابعة المتجر عند تجديد الرصيد المخزني</span>
                    </div>
                @endif

                <!-- Badges -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; font-size: 11px; color: #64748b;">
                    <div style="display: flex; align-items: center; gap: 6px; background: #f8fafc; padding: 10px 12px; border-radius: 10px; border: 1px solid #f1f5f9;">
                        <x-icon name="shield-check" size="16" style="color: #059669;" />
                        <span>دفع نقداً عند الاستلام</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 6px; background: #f8fafc; padding: 10px 12px; border-radius: 10px; border: 1px solid #f1f5f9;">
                        <x-icon name="truck" size="16" style="color: #4f46e5;" />
                        <span>توصيل سريع وآمن</span>
                    </div>
                </div>
            </div>

        </div>

    </div>

    <!-- Related Products -->
    @if($relatedProducts->count() > 0)
        <div style="margin-top: 40px;">
            <h3 style="font-size: 18px; font-weight: 800; color: #0f172a; margin-bottom: 16px;">منتجات مشابهة</h3>
            <div class="products-grid">
                @foreach($relatedProducts as $rel)
                    <div class="product-card">
                        <a href="{{ route('shop.product', $rel) }}" class="product-thumb">
                            @if($rel->image_path)
                                <img src="{{ Storage::url($rel->image_path) }}" alt="{{ $rel->name }}">
                            @else
                                <div class="product-placeholder-icon">
                                    <x-icon name="package" size="24" />
                                </div>
                            @endif
                        </a>
                        <div class="product-body">
                            <a href="{{ route('shop.product', $rel) }}" class="product-title" title="{{ $rel->name }}">
                                {{ $rel->name }}
                            </a>
                            <div class="product-bottom">
                                <div class="price-box">
                                    <span class="price-val">{{ number_format($rel->effective_online_price, 2) }}</span>
                                    <span class="price-unit">ج.م</span>
                                </div>
                                <a href="{{ route('shop.product', $rel) }}" class="btn-add-cart" style="text-decoration: none;">
                                    <x-icon name="eye" size="16" />
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

</div>
@endsection

@push('scripts')
<script>
    function incrementQty(max) {
        const input = document.getElementById('quantity');
        let current = parseInt(input.value) || 1;
        if (current < max) { input.value = current + 1; }
    }
    function decrementQty() {
        const input = document.getElementById('quantity');
        let current = parseInt(input.value) || 1;
        if (current > 1) { input.value = current - 1; }
    }
</script>
@endpush
