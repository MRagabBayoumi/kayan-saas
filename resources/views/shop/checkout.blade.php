@extends('shop.layout')

@section('content')
<div style="margin-top: 8px;">

    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
        <div>
            <h1 style="font-size: 22px; font-weight: 800; color: #0f172a; margin: 0 0 4px 0;">إتمام الطلب وتحديد عنوان الشحن</h1>
            <p style="font-size: 12px; color: #64748b; margin: 0;">أدخل بيانات المستلم ليصلك المندوب بأسرع وقت</p>
        </div>
        <a href="{{ route('shop.cart') }}" style="display: inline-flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 700; color: #475569; background: #ffffff; border: 1px solid #cbd5e1; padding: 6px 14px; border-radius: 10px; text-decoration: none;">
            <x-icon name="arrow-right" size="14" />
            <span>العودة للسلة</span>
        </a>
    </div>

    <form action="{{ route('shop.checkout.place') }}" method="POST" style="margin: 0;">
        @csrf

        <div class="shop-two-col">
            
            <!-- Customer & Shipping Form -->
            <div style="display: flex; flex-direction: column; gap: 20px;">
                
                <!-- Google 1-Click Autofill Banner -->
                <div style="background: linear-gradient(135deg, #eff6ff 0%, #e0e7ff 100%); border: 1px solid #bfdbfe; border-radius: 16px; padding: 16px; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div style="width: 36px; height: 36px; background: #ffffff; border-radius: 10px; display: flex; align-items: center; justify-content: center; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                            <svg width="20" height="20" viewBox="0 0 24 24">
                                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
                            </svg>
                        </div>
                        <div>
                            <strong style="font-size: 13px; color: #0f172a; display: block;">ملء البيانات تلقائياً بحساب Google</strong>
                            <span style="font-size: 11px; color: #64748b;">تعبئة الاسم والبريد بضغطة زر واحدة</span>
                        </div>
                    </div>

                    <button type="button" 
                            id="googleAutofillBtn" 
                            onclick="triggerGoogleMockFill()" 
                            style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 10px; padding: 7px 14px; font-size: 12px; font-weight: 700; color: #334155; cursor: pointer; display: flex; align-items: center; gap: 6px;">
                        <span>تعبئة تلقائية</span>
                    </button>
                </div>

                <!-- Form Card -->
                <div class="shop-card">
                    <h3 class="shop-card-title">
                        <x-icon name="user" size="18" style="color: #4f46e5;" />
                        <span>بيانات المستلم وعنوان التوصيل</span>
                    </h3>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="customer_name">الاسم بالكامل *</label>
                            <input type="text" id="customer_name" name="customer_name" value="{{ old('customer_name') }}" required placeholder="مثال: أحمد محمد علي" class="form-control">
                            @error('customer_name')
                                <span style="font-size: 11px; color: #e11d48; margin-top: 4px; display: block;">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="customer_phone">رقم الهاتف (للتوصيل والواتساب) *</label>
                            <input type="tel" id="customer_phone" name="customer_phone" value="{{ old('customer_phone') }}" required placeholder="01012345678" dir="ltr" class="form-control" style="text-align: right;">
                            @error('customer_phone')
                                <span style="font-size: 11px; color: #e11d48; margin-top: 4px; display: block;">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="customer_email">البريد الإلكتروني (اختياري)</label>
                            <input type="email" id="customer_email" name="customer_email" value="{{ old('customer_email') }}" placeholder="name@example.com" class="form-control">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="customer_city">المحافظة / المدينة</label>
                            <input type="text" id="customer_city" name="customer_city" value="{{ old('customer_city') }}" placeholder="مثال: القاهرة / الإسكندرية / الجيزة" class="form-control">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="customer_address">عنوان التوصيل بالتفصيل *</label>
                        <textarea id="customer_address" name="customer_address" rows="2" required placeholder="اسم الشارع، رقم العقار، الدور، الشقة، وأقرب علامة مميزة..." class="form-control">{{ old('customer_address') }}</textarea>
                        @error('customer_address')
                            <span style="font-size: 11px; color: #e11d48; margin-top: 4px; display: block;">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label" for="customer_notes">ملاحظات إضافية للمندوب (اختياري)</label>
                        <textarea id="customer_notes" name="customer_notes" rows="2" placeholder="أي تعليمات خاصة بالموعد المفضل أو الاستلام..." class="form-control">{{ old('customer_notes') }}</textarea>
                    </div>
                </div>

                <!-- Payment Method -->
                <div class="shop-card">
                    <h3 class="shop-card-title">
                        <x-icon name="credit-card" size="18" style="color: #4f46e5;" />
                        <span>طريقة الدفع</span>
                    </h3>

                    <label style="display: flex; align-items: center; gap: 12px; padding: 14px; border: 2px solid #4f46e5; background: #f5f3ff; border-radius: 14px; cursor: pointer;">
                        <input type="radio" name="payment_method" value="cod" checked style="accent-color: #4f46e5; width: 16px; height: 16px;">
                        <div style="flex: 1;">
                            <strong style="font-size: 14px; color: #0f172a; display: block;">الدفع نقداً عند الاستلام (COD)</strong>
                            <span style="font-size: 11px; color: #64748b;">ادفع قيمة الطلب للمندوب عند وصول الشحنة وفحصها</span>
                        </div>
                        <x-icon name="shield-check" size="20" style="color: #059669;" />
                    </label>
                </div>

            </div>

            <!-- Sidebar Items Review & Submit -->
            <div>
                <div class="shop-card" style="position: sticky; top: 90px;">
                    <h3 class="shop-card-title">
                        <span>مراجعة الطلب ({{ count($cart) }})</span>
                    </h3>

                    <div style="max-height: 240px; overflow-y: auto; display: flex; flex-direction: column; gap: 10px; margin-bottom: 16px;">
                        @foreach($cart as $item)
                            <div style="display: flex; justify-content: space-between; align-items: center; font-size: 12px; padding-bottom: 8px; border-bottom: 1px solid #f1f5f9;">
                                <div>
                                    <strong style="color: #0f172a; display: block;">{{ $item['name'] }}</strong>
                                    <span style="color: #94a3b8;">{{ $item['quantity'] }} &times; {{ number_format($item['price'], 2) }} ج.م</span>
                                </div>
                                <strong style="color: #0f172a; white-space: nowrap;">
                                    {{ number_format($item['price'] * $item['quantity'], 2) }} ج.م
                                </strong>
                            </div>
                        @endforeach
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 8px; font-size: 13px; color: #64748b; padding-bottom: 14px; border-bottom: 1px solid #f1f5f9;">
                        <div style="display: flex; justify-content: space-between;">
                            <span>المجموع الفرعي:</span>
                            <strong style="color: #0f172a;">{{ number_format($subtotal, 2) }} ج.م</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span>الشحن:</span>
                            @if($shipping == 0)
                                <strong style="color: #059669;">مجاناً</strong>
                            @else
                                <strong style="color: #0f172a;">{{ number_format($shipping, 2) }} ج.م</strong>
                            @endif
                        </div>
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: baseline; padding: 14px 0 20px 0;">
                        <span style="font-size: 14px; font-weight: 800; color: #0f172a;">المبلغ المطلوب:</span>
                        <div style="text-align: left;">
                            <span style="font-size: 24px; font-weight: 900; color: #4338ca;">{{ number_format($total, 2) }}</span>
                            <span style="font-size: 12px; font-weight: 700; color: #64748b;">ج.م</span>
                        </div>
                    </div>

                    <button type="submit" class="btn-success-shop">
                        <x-icon name="check-circle" size="18" />
                        <span>تأكيد وإرسال الطلب الآن</span>
                    </button>

                    <div style="margin-top: 12px; text-align: center; font-size: 11px; color: #94a3b8;">
                        بالضغط على تأكيد، يتم حجز الأصناف وتجهيز الشحنة فوراً
                    </div>
                </div>
            </div>

        </div>
    </form>

</div>
@endsection

@push('scripts')
<script>
    function triggerGoogleMockFill() {
        const btn = document.getElementById('googleAutofillBtn');
        btn.innerHTML = '<span>جاري التحقق...</span>';
        setTimeout(() => {
            const nameInput = document.getElementById('customer_name');
            const emailInput = document.getElementById('customer_email');
            if (!nameInput.value) nameInput.value = 'عميل Google المميز';
            if (!emailInput.value) emailInput.value = 'customer@gmail.com';
            btn.innerHTML = '<span style="color: #059669; font-weight: 700;">✓ تم الملء</span>';
        }, 400);
    }
</script>
@endpush
