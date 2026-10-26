@extends('layouts.app')

@section('control_panel')
    <x-control-panel title="إضافة عميل جديد" :breadcrumbs="['العملاء' => route('customers.index'), 'إضافة عميل' => '#']">
        <x-slot:actions>
            <a href="{{ route('customers.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="arrow-right" size="16" />
                <span>رجوع لدليل العملاء</span>
            </a>
        </x-slot:actions>
    </x-control-panel>
@endsection

@section('content')
<div class="max-w-4xl mx-auto">
    @if($errors->any())
        <div class="erp-alert erp-alert-danger mb-4" role="alert">
            <x-icon name="alert-triangle" size="18" />
            <div>
                <strong>يرجى تصحيح الأخطاء التالية:</strong>
                <ul class="list-disc list-inside mt-1 text-xs">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('customers.store') }}" class="erp-card p-5 space-y-4">
        @csrf

        <div class="border-b border-[var(--border-light)] pb-3 mb-4">
            <h2 class="erp-card-title text-base font-bold">
                <x-icon name="user-plus" size="20" class="text-[var(--primary)]" />
                <span>البيانات الأساسية للعميل</span>
            </h2>
            <p class="text-xs text-[var(--text-muted)] mt-1">أدخل بيانات العميل وفئة التسعير والحد الائتماني والمندوب المسؤول</p>
        </div>

        <!-- Name & Company -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="erp-form-group mb-0">
                <label class="erp-form-label">
                    اسم العميل / المحل <span class="required">*</span>
                </label>
                <input type="text" name="name" value="{{ old('name') }}" required placeholder="مثال: محلات النور أو الأستاذ محمد علي" class="erp-input">
            </div>

            <div class="erp-form-group mb-0">
                <label class="erp-form-label">الاسم التجاري / الشركة (اختياري)</label>
                <input type="text" name="company_name" value="{{ old('company_name') }}" placeholder="اسم المعرض أو المؤسسة" class="erp-input">
            </div>
        </div>

        <!-- Phones -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="erp-form-group mb-0">
                <label class="erp-form-label">
                    رقم الهاتف الأساسي <span class="required">*</span>
                </label>
                <input type="tel" name="phone" value="{{ old('phone') }}" required placeholder="مثال: 01012345678" class="erp-input font-numeric">
            </div>

            <div class="erp-form-group mb-0">
                <label class="erp-form-label">هاتف إضافي / واتساب</label>
                <input type="tel" name="phone_alt" value="{{ old('phone_alt') }}" placeholder="مثال: 01223456789" class="erp-input font-numeric">
            </div>
        </div>

        <!-- Customer Type, Pricing Tier, Sales Rep -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="erp-form-group mb-0">
                <label class="erp-form-label">
                    نوع العميل <span class="required">*</span>
                </label>
                <select name="customer_type" required class="erp-select">
                    <option value="wholesale" {{ old('customer_type') === 'wholesale' ? 'selected' : '' }}>جملة</option>
                    <option value="retail" {{ old('customer_type', 'retail') === 'retail' ? 'selected' : '' }}>قطاعي / تجزئة</option>
                    <option value="distributor" {{ old('customer_type') === 'distributor' ? 'selected' : '' }}>موزع رئيسي</option>
                </select>
            </div>

            <div class="erp-form-group mb-0">
                <label class="erp-form-label">
                    فئة السعر المطبقة <span class="required">*</span>
                </label>
                <select name="pricing_tier" required class="erp-select">
                    <option value="retail" {{ old('pricing_tier', 'retail') === 'retail' ? 'selected' : '' }}>سعر القطاعي</option>
                    <option value="wholesale" {{ old('pricing_tier') === 'wholesale' ? 'selected' : '' }}>سعر الجملة</option>
                    <option value="distributor" {{ old('pricing_tier') === 'distributor' ? 'selected' : '' }}>سعر الموزع</option>
                </select>
            </div>

            <div class="erp-form-group mb-0">
                <label class="erp-form-label">المندوب المسؤول الميداني</label>
                <select name="sales_rep_id" class="erp-select">
                    <option value="">-- بدون ربط بمندوب --</option>
                    @foreach($salesReps as $rep)
                        <option value="{{ $rep->id }}" {{ (string)old('sales_rep_id') === (string)$rep->id ? 'selected' : '' }}>
                            {{ $rep->name }} (عمولة: {{ (float)$rep->commission_rate }}{{ $rep->commission_type === 'percentage' ? '%' : ' ج.م' }})
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- Financial: Credit Limit & Opening Balance -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="erp-form-group mb-0">
                <label class="erp-form-label">الحد الائتماني المسموح (آجل)</label>
                <div class="relative">
                    <input type="number" step="0.01" min="0" name="credit_limit" value="{{ old('credit_limit', 0) }}" placeholder="0.00" class="erp-input font-numeric">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs text-[var(--text-muted)]">ج.م</span>
                </div>
            </div>

            <div class="erp-form-group mb-0">
                <label class="erp-form-label">رصيد أول المدة (مديونية سابقة)</label>
                <div class="relative">
                    <input type="number" step="0.01" name="opening_balance" value="{{ old('opening_balance', 0) }}" placeholder="0.00" class="erp-input font-numeric">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs text-[var(--text-muted)]">ج.م</span>
                </div>
                <div class="erp-form-hint">المبلغ الموجب يعني أن العميل مدين للشركة بمبلغ سابق</div>
            </div>
        </div>

        <!-- City & Address -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="erp-form-group mb-0">
                <label class="erp-form-label">المدينة / المنطقة</label>
                <input type="text" name="city" value="{{ old('city') }}" placeholder="مثال: القاهرة / الجيزة / طنطا" class="erp-input">
            </div>

            <div class="erp-form-group mb-0">
                <label class="erp-form-label">العنوان التفصيلي / معالم مميزة</label>
                <input type="text" name="address" value="{{ old('address') }}" placeholder="الشارع، رقم المبنى، علامة مميزة" class="erp-input">
            </div>
        </div>

        <!-- Notes -->
        <div class="erp-form-group mb-0">
            <label class="erp-form-label">ملاحظات إضافية</label>
            <textarea name="notes" rows="2" placeholder="ملاحظات حول طريقة السداد، مواعيد التسليم، تفضيلات البضاعة..." class="erp-textarea">{{ old('notes') }}</textarea>
        </div>

        <!-- Active Toggle -->
        <div class="pt-2">
            <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', '1') ? 'checked' : '' }} class="rounded border-[var(--border-color)] text-[var(--primary)] focus:ring-[var(--primary)]">
                <span class="text-xs font-semibold text-[var(--text-main)]">العميل نشط ومتاح للتعامل في المبيعات</span>
            </label>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-end gap-2 pt-4 border-t border-[var(--border-light)]">
            <a href="{{ route('customers.index') }}" class="erp-btn erp-btn-secondary">
                <span>إلغاء</span>
            </a>
            <button type="submit" class="erp-btn erp-btn-primary">
                <x-icon name="check" size="16" />
                <span>حفظ بيانات العميل</span>
            </button>
        </div>
    </form>
</div>
@endsection
