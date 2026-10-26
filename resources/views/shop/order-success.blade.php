@extends('shop.layout')

@section('content')
<div style="max-width: 680px; margin: 24px auto; display: flex; flex-direction: column; gap: 20px;">

    <!-- Celebration Card -->
    <div class="shop-card" style="text-align: center; padding: 36px 20px;">
        <div style="width: 68px; height: 68px; background: #ecfdf5; border-radius: 20px; color: #059669; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px auto;">
            <x-icon name="check-circle" size="36" />
        </div>

        <h1 style="font-size: 22px; font-weight: 800; color: #0f172a; margin: 0 0 6px 0;">
            شكراً لطلبك! تم استلام الطلب بنجاح
        </h1>
        <p style="font-size: 13px; color: #64748b; margin: 0 0 20px 0;">
            تم تسجيل طلبك في نظام المبيعات وسيقوم فريقنا بتجهيزه فوراً للتوصيل.
        </p>

        <!-- Tracking Number Pill -->
        <div style="display: inline-flex; align-items: center; gap: 10px; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 14px; padding: 10px 18px;">
            <span style="font-size: 12px; color: #64748b;">رقم تتبع الطلب:</span>
            <span id="orderNum" style="font-size: 16px; font-weight: 900; font-family: monospace; color: #4338ca; letter-spacing: 1px;">
                {{ $order->order_number }}
            </span>
            <button type="button" onclick="copyOrderNumber()" style="background: none; border: none; color: #94a3b8; cursor: pointer; padding: 2px;" title="نسخ">
                <x-icon name="copy" size="16" />
            </button>
        </div>

        <div style="margin-top: 16px; font-size: 12px; color: #92400e; background: #fffbeb; border: 1px solid #fde68a; border-radius: 12px; padding: 10px 14px;">
            سيتصل بك مندوب الشحن على الهاتف ({{ $order->customer_phone }}) قبل تسليم الشحنة.
        </div>
    </div>

    <!-- Details Card -->
    <div class="shop-card">
        <h3 class="shop-card-title">
            <x-icon name="file-text" size="18" style="color: #4f46e5;" />
            <span>تفاصيل الشحنة والمستلم</span>
        </h3>

        <div style="display: grid; grid-template-columns: 1fr; gap: 12px; font-size: 12px; padding-bottom: 16px; border-bottom: 1px solid #f1f5f9;">
            <div style="display: flex; justify-content: space-between;">
                <span style="color: #64748b;">اسم المستلم:</span>
                <strong style="color: #0f172a;">{{ $order->customer_name }}</strong>
            </div>
            <div style="display: flex; justify-content: space-between;">
                <span style="color: #64748b;">رقم الهاتف:</span>
                <strong style="color: #0f172a;" dir="ltr">{{ $order->customer_phone }}</strong>
            </div>
            <div style="display: flex; justify-content: space-between;">
                <span style="color: #64748b;">عنوان التوصيل:</span>
                <strong style="color: #0f172a; text-align: left; max-width: 65%;">{{ $order->customer_address }} ({{ $order->customer_city ?? 'غير محدد' }})</strong>
            </div>
            <div style="display: flex; justify-content: space-between;">
                <span style="color: #64748b;">طريقة الدفع:</span>
                <strong style="color: #0f172a;">الدفع عند الاستلام نقداً (COD)</strong>
            </div>
        </div>

        <!-- Ordered Items -->
        <div style="padding: 16px 0; border-bottom: 1px solid #f1f5f9;">
            <h4 style="font-size: 12px; font-weight: 700; color: #64748b; margin: 0 0 10px 0; text-transform: uppercase;">الأصناف المطلوبة</h4>
            <div style="display: flex; flex-direction: column; gap: 8px;">
                @foreach($order->items as $item)
                    <div style="display: flex; justify-content: space-between; font-size: 12px;">
                        <div>
                            <strong style="color: #0f172a;">{{ $item->product_name }}</strong>
                            <span style="color: #94a3b8; font-size: 11px; display: block;">{{ (float) $item->quantity }} {{ $item->unit }} &times; {{ number_format($item->unit_price, 2) }} ج.م</span>
                        </div>
                        <strong style="color: #0f172a;">{{ number_format($item->subtotal, 2) }} ج.م</strong>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Costs -->
        <div style="padding-top: 14px; display: flex; flex-direction: column; gap: 8px; font-size: 13px; color: #64748b;">
            <div style="display: flex; justify-content: space-between;">
                <span>المجموع الفرعي:</span>
                <strong style="color: #0f172a;">{{ number_format($order->subtotal, 2) }} ج.م</strong>
            </div>
            <div style="display: flex; justify-content: space-between;">
                <span>مصاريف الشحن:</span>
                @if($order->shipping_cost == 0)
                    <strong style="color: #059669;">مجاناً</strong>
                @else
                    <strong style="color: #0f172a;">{{ number_format($order->shipping_cost, 2) }} ج.م</strong>
                @endif
            </div>
            <div style="display: flex; justify-content: space-between; align-items: baseline; padding-top: 10px; border-top: 1px solid #f1f5f9;">
                <span style="font-size: 14px; font-weight: 800; color: #0f172a;">المبلغ المطلوب سداده:</span>
                <strong style="font-size: 20px; font-weight: 900; color: #4338ca;">{{ number_format($order->total, 2) }} ج.م</strong>
            </div>
        </div>
    </div>

    <!-- Actions -->
    <div style="display: flex; flex-wrap: wrap; gap: 12px; justify-content: center;">
        <a href="{{ route('shop.catalog') }}" class="btn-primary-shop" style="width: auto; padding: 12px 28px;">
            <x-icon name="shopping-bag" size="18" />
            <span>العودة ومواصلة التسوق</span>
        </a>

        @if(!empty($settings->contact_whatsapp))
            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $settings->contact_whatsapp) }}?text={{ urlencode('مرحباً، أود الاستفسار عن حالة طلبي رقم: ' . $order->order_number) }}" 
               target="_blank" 
               class="btn-success-shop" 
               style="width: auto; padding: 12px 28px; text-decoration: none;">
                <x-icon name="message-circle" size="18" />
                <span>متابعة الطلب عبر واتساب</span>
            </a>
        @endif
    </div>

</div>
@endsection

@push('scripts')
<script>
    function copyOrderNumber() {
        const text = document.getElementById('orderNum').innerText;
        navigator.clipboard.writeText(text);
        alert('تم نسخ رقم الطلب: ' + text);
    }
</script>
@endpush
