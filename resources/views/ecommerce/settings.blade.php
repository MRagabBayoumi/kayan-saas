@extends('layouts.app')

@section('module_name', 'إعدادات المتجر الإلكتروني')

@section('control_panel')
    <x-control-panel title="إعدادات المتجر الإلكتروني (Storefront Settings)" :breadcrumbs="['المتجر الإلكتروني' => route('ecommerce.orders.index'), 'الإعدادات' => route('ecommerce.settings')]">
        <x-slot:actions>
            <button type="submit" form="ecommerceSettingsForm" class="erp-btn erp-btn-primary">
                <x-icon name="check" size="16" />
                <span>حفظ التعديلات</span>
            </button>
            <a href="{{ route('shop.catalog') }}" target="_blank" class="erp-btn erp-btn-secondary">
                <x-icon name="external-link" size="16" />
                <span>معاينة المتجر المباشر</span>
            </a>
            <a href="{{ route('ecommerce.orders.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="shopping-cart" size="16" />
                <span>قائمة الطلبات</span>
            </a>
        </x-slot:actions>
    </x-control-panel>
@endsection

@section('content')
<div style="max-width: 960px; margin: 0 auto;">

    @if(session('success'))
        <div class="erp-alert erp-alert-success mb-4" role="alert">
            <x-icon name="check" size="16" />
            <div>{{ session('success') }}</div>
        </div>
    @endif

    @if($errors->any())
        <div class="erp-alert erp-alert-danger mb-4" role="alert">
            <x-icon name="alert-triangle" size="16" />
            <div>
                <strong>يرجى تصحيح الأخطاء التالية:</strong>
                <ul class="my-1.5 mx-5 text-xs">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <form id="ecommerceSettingsForm" action="{{ route('ecommerce.settings.update') }}" method="POST">
        @csrf

        <!-- Card 1: Store Identity & Branding -->
        <div class="erp-card mb-4">
            <div class="erp-card-header">
                <h3 class="erp-card-title">
                    <x-icon name="store" size="18" style="color: var(--primary);" />
                    <span>الهوية والبيانات الأساسية للمتجر</span>
                </h3>
            </div>
            <div class="erp-card-body">
                <div class="erp-form-grid">
                    <div class="erp-form-group">
                        <label for="store_name" class="erp-form-label">
                            <span>اسم المتجر الإلكتروني</span>
                            <span class="required">*</span>
                        </label>
                        <input type="text" 
                               id="store_name" 
                               name="store_name" 
                               value="{{ old('store_name', $settings->store_name) }}" 
                               required
                               placeholder="مثال: متجر كيان التجاري" 
                               class="erp-input">
                        <span class="erp-form-hint">الاسم الذي يظهر للمشترين أعلى الموقع وفي الفواتير.</span>
                    </div>

                    <div class="erp-form-group">
                        <label for="store_tagline" class="erp-form-label">
                            <span>الشعار اللفظي (Slogan)</span>
                        </label>
                        <input type="text" 
                               id="store_tagline" 
                               name="store_tagline" 
                               value="{{ old('store_tagline', $settings->store_tagline) }}" 
                               placeholder="مثال: أفضل المنتجات بأسعار الجملة والتجزئة والتوصيل السريع" 
                               class="erp-input">
                        <span class="erp-form-hint">وصف تسويقي مختصر يظهر أسفل اسم المتجر.</span>
                    </div>

                    <div class="erp-form-group erp-form-full">
                        <label for="announcement_text" class="erp-form-label">
                            <span>شريط الإعلانات أعلى المتجر (Banner Text)</span>
                        </label>
                        <input type="text" 
                               id="announcement_text" 
                               name="announcement_text" 
                               value="{{ old('announcement_text', $settings->announcement_text) }}" 
                               placeholder="مثال: عرض حصري: شحن مجاني للطلبات فوق 300 جنيه والدفع عند الاستلام!" 
                               class="erp-input">
                        <span class="erp-form-hint">شريط علوي بارز ومتحرك لإعلانات الخصومات والشحن. اترك الحقل فارغاً لإخفائه.</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 2: Contact & Support -->
        <div class="erp-card mb-4">
            <div class="erp-card-header">
                <h3 class="erp-card-title">
                    <x-icon name="phone" size="18" style="color: var(--primary);" />
                    <span>بيانات خدمة العملاء والتواصل المباشر</span>
                </h3>
            </div>
            <div class="erp-card-body">
                <div class="erp-form-grid">
                    <div class="erp-form-group">
                        <label for="contact_phone" class="erp-form-label">
                            <span>هاتف خدمة العملاء</span>
                        </label>
                        <input type="text" 
                               id="contact_phone" 
                               name="contact_phone" 
                               value="{{ old('contact_phone', $settings->contact_phone) }}" 
                               placeholder="01012345678" 
                               dir="ltr"
                               class="erp-input font-numeric" 
                               style="text-align: right;">
                        <span class="erp-form-hint">رقم الهاتف الظاهر في تذييل المتجر وصفحة النجاح.</span>
                    </div>

                    <div class="erp-form-group">
                        <label for="contact_whatsapp" class="erp-form-label">
                            <span>رقم الواتساب الرسمي (مع كود الدولة)</span>
                        </label>
                        <input type="text" 
                               id="contact_whatsapp" 
                               name="contact_whatsapp" 
                               value="{{ old('contact_whatsapp', $settings->contact_whatsapp) }}" 
                               placeholder="+201012345678" 
                               dir="ltr"
                               class="erp-input font-numeric" 
                               style="text-align: right;">
                        <span class="erp-form-hint">يُستخدم لتوجيه العميل إلى دردشة الواتساب لتتبع الطلب.</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 3: Shipping & Payment Policy -->
        <div class="erp-card mb-4">
            <div class="erp-card-header">
                <h3 class="erp-card-title">
                    <x-icon name="truck" size="18" style="color: var(--primary);" />
                    <span>سياسة الشحن ومصاريف التوصيل والدفع</span>
                </h3>
            </div>
            <div class="erp-card-body">
                <div class="erp-form-grid">
                    <div class="erp-form-group">
                        <label for="shipping_flat_rate" class="erp-form-label">
                            <span>تكلفة الشحن الثابتة (ج.م)</span>
                            <span class="required">*</span>
                        </label>
                        <input type="number" 
                               step="0.01" 
                               id="shipping_flat_rate" 
                               name="shipping_flat_rate" 
                               value="{{ old('shipping_flat_rate', $settings->shipping_flat_rate) }}" 
                               required 
                               class="erp-input font-numeric">
                        <span class="erp-form-hint">المبلغ المضاف على إجمالي السلة لتوصيل الطلب.</span>
                    </div>

                    <div class="erp-form-group">
                        <label for="free_shipping_threshold" class="erp-form-label">
                            <span>حد الشحن المجاني (ج.م)</span>
                            <span class="required">*</span>
                        </label>
                        <input type="number" 
                               step="0.01" 
                               id="free_shipping_threshold" 
                               name="free_shipping_threshold" 
                               value="{{ old('free_shipping_threshold', $settings->free_shipping_threshold) }}" 
                               required 
                               class="erp-input font-numeric">
                        <span class="erp-form-hint">إذا تخطت مشتريات العميل هذا المبلغ يُمنح شحناً مجانياً تلقائياً.</span>
                    </div>

                    <div class="erp-form-group erp-form-full">
                        <div style="display: flex; flex-direction: column; gap: 14px; padding-top: 8px;">
                            <label style="display: flex; align-items: flex-start; gap: 10px; cursor: pointer;">
                                <input type="checkbox" name="allow_cod" value="1" {{ $settings->allow_cod ? 'checked' : '' }} style="margin-top: 3px; width: 16px; height: 16px; accent-color: var(--primary);">
                                <div>
                                    <strong style="font-size: 13px; color: var(--text-main); display: block;">تفعيل الدفع نقداً عند الاستلام (Cash On Delivery)</strong>
                                    <span style="font-size: 12px; color: var(--text-muted);">السماح للعملاء بالطلب ودفع الحساب للمندوب عند استلام الشحنة وفحصها.</span>
                                </div>
                            </label>

                            <label style="display: flex; align-items: flex-start; gap: 10px; cursor: pointer;">
                                <input type="checkbox" name="is_store_open" value="1" {{ $settings->is_store_open ? 'checked' : '' }} style="margin-top: 3px; width: 16px; height: 16px; accent-color: var(--primary);">
                                <div>
                                    <strong style="font-size: 13px; color: var(--text-main); display: block;">المتجر مفتوح ومتاح لاستقبال طلبات الشراء أونلاين</strong>
                                    <span style="font-size: 12px; color: var(--text-muted);">في حالة إلغاء التحديد، يتم إغلاق استقبال الطلبات ويظهر تنبيه للعملاء بالإغلاق المؤقت للصيانة.</span>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer Save Bar -->
            <div style="padding: 14px 20px; background-color: var(--bg-surface-alt); border-top: 1px solid var(--border-light); display: flex; align-items: center; justify-content: flex-end; gap: 10px;">
                <a href="{{ route('ecommerce.orders.index') }}" class="erp-btn erp-btn-secondary">
                    <span>إلغاء</span>
                </a>
                <button type="submit" class="erp-btn erp-btn-primary" style="padding: 8px 24px;">
                    <x-icon name="check" size="16" />
                    <span>حفظ كافة الإعدادات</span>
                </button>
            </div>
        </div>

    </form>

</div>
@endsection
