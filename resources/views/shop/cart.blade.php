@extends('shop.layout')

@section('content')
<div style="margin-top: 8px;">

    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
        <div>
            <h1 style="font-size: 22px; font-weight: 800; color: #0f172a; margin: 0 0 4px 0;">سلة المشتريات</h1>
            <p style="font-size: 12px; color: #64748b; margin: 0;">راجع المنتجات والكميات قبل الانتقال لتأكيد الطلب</p>
        </div>
        <a href="{{ route('shop.catalog') }}" style="display: inline-flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 700; color: #4f46e5; background: #eef2ff; border: 1px solid #c7d2fe; padding: 6px 14px; border-radius: 10px; text-decoration: none;">
            <x-icon name="arrow-right" size="14" />
            <span>مواصلة التسوق</span>
        </a>
    </div>

    @if(empty($cart))
        <!-- Empty Cart -->
        <div class="shop-card" style="text-align: center; max-width: 500px; margin: 40px auto; padding: 48px 24px;">
            <div class="product-placeholder-icon" style="width: 72px; height: 72px; margin: 0 auto 16px auto; border-radius: 20px;">
                <x-icon name="shopping-cart" size="32" />
            </div>
            <h3 style="font-size: 18px; font-weight: 800; color: #0f172a; margin: 0 0 6px 0;">سلة الشراء فارغة</h3>
            <p style="font-size: 13px; color: #64748b; margin: 0 0 24px 0;">لم تقم بإضافة أي منتجات إلى سلة المشتريات بعد.</p>
            <a href="{{ route('shop.catalog') }}" class="btn-primary-shop" style="width: auto; display: inline-flex; padding: 12px 28px;">
                <x-icon name="shopping-bag" size="18" />
                <span>تصفح المنتجات الآن</span>
            </a>
        </div>
    @else
        <!-- Cart with Items -->
        <div class="shop-two-col">
            
            <!-- Items Card -->
            <div class="shop-card">
                <h3 class="shop-card-title">
                    <x-icon name="package" size="18" style="color: #4f46e5;" />
                    <span>المنتجات المختارة ({{ count($cart) }})</span>
                </h3>

                <div>
                    @foreach($cart as $id => $item)
                        @php $lineTotal = $item['price'] * $item['quantity']; @endphp
                        <div class="cart-item-row">
                            
                            <!-- Info -->
                            <div class="cart-item-info">
                                <div class="cart-item-thumb">
                                    @if(!empty($item['image']))
                                        <img src="{{ Storage::url($item['image']) }}" alt="{{ $item['name'] }}">
                                    @else
                                        <x-icon name="package" size="20" style="color: #94a3b8;" />
                                    @endif
                                </div>
                                <div>
                                    <strong style="font-size: 13px; color: #0f172a; display: block; line-height: 1.3;">
                                        {{ $item['name'] }}
                                    </strong>
                                    <div style="font-size: 11px; color: #64748b; margin-top: 3px;">
                                        <span>كود: {{ $item['sku'] ?? 'N/A' }}</span>
                                        <span>&bull;</span>
                                        <span>سعر الوحدة: <strong>{{ number_format($item['price'], 2) }} ج.م</strong></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Quantity & Line Total & Remove -->
                            <div style="display: flex; align-items: center; justify-content: space-between; gap: 16px;">
                                
                                <form action="{{ route('shop.cart.update') }}" method="POST" style="margin: 0;">
                                    @csrf
                                    <input type="hidden" name="product_id" value="{{ $id }}">
                                    <div class="qty-control" style="height: 32px;">
                                        <button type="submit" name="quantity" value="{{ $item['quantity'] - 1 }}" class="qty-btn" style="width: 30px; height: 30px;">-</button>
                                        <span class="qty-input" style="width: 36px; line-height: 30px; display: inline-block;">{{ $item['quantity'] }}</span>
                                        <button type="submit" name="quantity" value="{{ $item['quantity'] + 1 }}" class="qty-btn" style="width: 30px; height: 30px;">+</button>
                                    </div>
                                </form>

                                <div style="min-width: 80px; text-align: left;">
                                    <strong style="font-size: 15px; color: #4338ca; display: block;">
                                        {{ number_format($lineTotal, 2) }}
                                    </strong>
                                    <span style="font-size: 10px; color: #94a3b8;">ج.م</span>
                                </div>

                                <form action="{{ route('shop.cart.remove') }}" method="POST" style="margin: 0;">
                                    @csrf
                                    <input type="hidden" name="product_id" value="{{ $id }}">
                                    <button type="submit" style="background: none; border: none; color: #94a3b8; cursor: pointer; padding: 4px; display: flex; align-items: center;" title="حذف">
                                        <x-icon name="trash-2" size="16" />
                                    </button>
                                </form>

                            </div>

                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Summary Sidebar -->
            <div>
                <div class="shop-card" style="position: sticky; top: 90px;">
                    <h3 class="shop-card-title">
                        <x-icon name="credit-card" size="18" style="color: #4f46e5;" />
                        <span>ملخص الحساب</span>
                    </h3>

                    <div style="display: flex; flex-direction: column; gap: 10px; font-size: 13px; color: #475569; padding-bottom: 14px; border-bottom: 1px solid #f1f5f9;">
                        <div style="display: flex; justify-content: space-between;">
                            <span>إجمالي المنتجات:</span>
                            <strong style="color: #0f172a;">{{ number_format($subtotal, 2) }} ج.م</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span>مصاريف الشحن:</span>
                            @if($shipping == 0)
                                <strong style="color: #059669;">مجاناً</strong>
                            @else
                                <strong style="color: #0f172a;">{{ number_format($shipping, 2) }} ج.م</strong>
                            @endif
                        </div>

                        @if($settings->free_shipping_threshold > 0 && $subtotal < $settings->free_shipping_threshold)
                            <div style="background: #fffbeb; border: 1px solid #fde68a; padding: 8px 12px; border-radius: 10px; font-size: 11px; color: #92400e; display: flex; align-items: center; gap: 6px;">
                                <x-icon name="truck" size="14" style="color: #b45309;" />
                                <span>أضف بـ <strong>{{ number_format($settings->free_shipping_threshold - $subtotal, 2) }} ج.م</strong> للشحن المجاني!</span>
                            </div>
                        @endif
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: baseline; padding: 14px 0 20px 0;">
                        <span style="font-size: 14px; font-weight: 800; color: #0f172a;">المبلغ الإجمالي:</span>
                        <div style="text-align: left;">
                            <span style="font-size: 24px; font-weight: 900; color: #4338ca;">{{ number_format($total, 2) }}</span>
                            <span style="font-size: 12px; font-weight: 700; color: #64748b;">ج.م</span>
                        </div>
                    </div>

                    <a href="{{ route('shop.checkout') }}" class="btn-primary-shop">
                        <span>متابعة إتمام الطلب</span>
                        <x-icon name="chevron-left" size="16" />
                    </a>

                    <div style="margin-top: 14px; text-align: center; font-size: 11px; color: #94a3b8; display: flex; align-items: center; justify-content: center; gap: 6px;">
                        <x-icon name="shield-check" size="14" style="color: #059669;" />
                        <span>تسوق آمن والدفع نقداً عند الاستلام</span>
                    </div>
                </div>
            </div>

        </div>
    @endif

</div>
@endsection
