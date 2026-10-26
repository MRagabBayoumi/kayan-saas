@extends('layouts.app')

@section('module_name', 'إعدادات المتجر الإلكتروني')

@section('control_panel')
    <x-control-panel title="إعدادات المتجر الإلكتروني (Storefront Settings)" :breadcrumbs="['المتجر الإلكتروني' => route('ecommerce.orders.index'), 'الإعدادات' => route('ecommerce.settings')]">
        <x-slot:actions>
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
<div class="max-w-4xl mx-auto space-y-6">

    @if(session('success'))
        <div class="erp-alert erp-alert-success" role="alert">
            <x-icon name="check" size="16" />
            <div>{{ session('success') }}</div>
        </div>
    @endif

    <form action="{{ route('ecommerce.settings.update') }}" method="POST">
        @csrf

        <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs divide-y divide-slate-100 overflow-hidden">
            
            <!-- Section 1: General Store Info -->
            <div class="p-6 space-y-4">
                <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                    <x-icon name="store" size="18" class="text-indigo-600" />
                    <span>الهوية والبيانات الأساسية للمتجر</span>
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="store_name" class="block text-xs font-bold text-slate-700 mb-1">اسم المتجر الإلكتروني:</label>
                        <input type="text" 
                               id="store_name" 
                               name="store_name" 
                               value="{{ old('store_name', $settings->store_name) }}" 
                               placeholder="مثال: متجر كيان التجاري" 
                               class="w-full text-xs p-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-indigo-500 outline-none">
                    </div>

                    <div>
                        <label for="store_tagline" class="block text-xs font-bold text-slate-700 mb-1">الشعار اللفظي (Slogan / الوصف المختصر):</label>
                        <input type="text" 
                               id="store_tagline" 
                               name="store_tagline" 
                               value="{{ old('store_tagline', $settings->store_tagline) }}" 
                               placeholder="مثال: أفضل المنتجات بأسعار الجملة والتجزئة والتوصيل السريع" 
                               class="w-full text-xs p-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-indigo-500 outline-none">
                    </div>
                </div>

                <div>
                    <label for="announcement_text" class="block text-xs font-bold text-slate-700 mb-1">شريط الإعلانات أعلى المتجر (Banner Text):</label>
                    <input type="text" 
                           id="announcement_text" 
                           name="announcement_text" 
                           value="{{ old('announcement_text', $settings->announcement_text) }}" 
                           placeholder="مثال: خصم 10% بمناسبة الافتتاح وشحن مجاني للطلبات فوق 500 جنيه!" 
                           class="w-full text-xs p-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-indigo-500 outline-none">
                </div>
            </div>

            <!-- Section 2: Contact Details -->
            <div class="p-6 space-y-4">
                <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                    <x-icon name="phone" size="18" class="text-indigo-600" />
                    <span>بيانات خدمة العملاء والتواصل</span>
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="contact_phone" class="block text-xs font-bold text-slate-700 mb-1">هاتف خدمة العملاء:</label>
                        <input type="text" 
                               id="contact_phone" 
                               name="contact_phone" 
                               value="{{ old('contact_phone', $settings->contact_phone) }}" 
                               placeholder="01000000000" 
                               dir="ltr"
                               class="w-full text-xs p-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-indigo-500 outline-none text-right">
                    </div>

                    <div>
                        <label for="contact_whatsapp" class="block text-xs font-bold text-slate-700 mb-1">رقم الواتساب الرسمي:</label>
                        <input type="text" 
                               id="contact_whatsapp" 
                               name="contact_whatsapp" 
                               value="{{ old('contact_whatsapp', $settings->contact_whatsapp) }}" 
                               placeholder="+201000000000" 
                               dir="ltr"
                               class="w-full text-xs p-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-indigo-500 outline-none text-right">
                    </div>
                </div>
            </div>

            <!-- Section 3: Shipping & Payment Rules -->
            <div class="p-6 space-y-4">
                <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                    <x-icon name="truck" size="18" class="text-indigo-600" />
                    <span>سياسة الشحن والدفع</span>
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="shipping_flat_rate" class="block text-xs font-bold text-slate-700 mb-1">تكلفة الشحن الثابتة (ج.م):</label>
                        <input type="number" 
                               step="0.01" 
                               id="shipping_flat_rate" 
                               name="shipping_flat_rate" 
                               value="{{ old('shipping_flat_rate', $settings->shipping_flat_rate) }}" 
                               required 
                               class="w-full text-xs p-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-indigo-500 outline-none font-bold">
                    </div>

                    <div>
                        <label for="free_shipping_threshold" class="block text-xs font-bold text-slate-700 mb-1">حد الشحن المجاني (ج.م):</label>
                        <input type="number" 
                               step="0.01" 
                               id="free_shipping_threshold" 
                               name="free_shipping_threshold" 
                               value="{{ old('free_shipping_threshold', $settings->free_shipping_threshold) }}" 
                               required 
                               class="w-full text-xs p-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-indigo-500 outline-none font-bold">
                    </div>
                </div>

                <div class="pt-2 space-y-3">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" name="allow_cod" value="1" {{ $settings->allow_cod ? 'checked' : '' }} class="w-4 h-4 text-indigo-600 rounded">
                        <div>
                            <strong class="text-xs text-slate-800 block">تفعيل الدفع نقداً عند الاستلام (COD)</strong>
                            <span class="text-[11px] text-slate-500">تمكين العملاء من دفع قيمة الطلب نقداً للمندوب عند وصول الشحنة</span>
                        </div>
                    </label>

                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" name="is_store_open" value="1" {{ $settings->is_store_open ? 'checked' : '' }} class="w-4 h-4 text-indigo-600 rounded">
                        <div>
                            <strong class="text-xs text-slate-800 block">المتجر مفتوح ومتاح لاستقبال طلبات الشراء</strong>
                            <span class="text-[11px] text-slate-500">إذا تم إلغاء التحديد، لن يتمكن العملاء من إتمام checkout وسيظهر تنبيه بالإغلاق المؤقت</span>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Submit Buttons -->
            <div class="p-6 bg-slate-50 flex items-center justify-end gap-3">
                <a href="{{ route('ecommerce.orders.index') }}" class="erp-btn erp-btn-secondary">
                    <span>إلغاء</span>
                </a>
                <button type="submit" class="erp-btn erp-btn-primary px-6">
                    <x-icon name="check" size="16" />
                    <span>حفظ الإعدادات</span>
                </button>
            </div>

        </div>
    </form>

</div>
@endsection
