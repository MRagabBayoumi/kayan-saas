@extends('layouts.app')

@section('control_panel')
    <x-control-panel title="إضافة مندوب مبيعات جديد" :breadcrumbs="['مناديب المبيعات' => route('sales-reps.index'), 'إضافة مندوب' => '#']">
        <x-slot:actions>
            <a href="{{ route('sales-reps.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="arrow-right" size="16" />
                <span>رجوع لقائمة المناديب</span>
            </a>
        </x-slot:actions>
    </x-control-panel>
@endsection

@section('content')
<div class="max-w-3xl mx-auto">
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

    <form method="POST" action="{{ route('sales-reps.store') }}" class="erp-card p-6 space-y-6">
        @csrf

        <!-- Section 1: بيانات الحساب وتسجيل الدخول -->
        <div>
            <div class="border-b border-[var(--border-light)] pb-3 mb-4">
                <h2 class="erp-card-title text-base font-bold flex items-center gap-2">
                    <x-icon name="user-check" size="20" class="text-[var(--primary)]" />
                    <span>بيانات المندوب وحساب تسجيل الدخول</span>
                </h2>
                <p class="text-xs text-[var(--text-muted)] mt-1">يستخدم المندوب اسم المستخدم وكلمة المرور للدخول لبوابة المندوب الميداني عبر الموبايل.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Name -->
                <div class="erp-form-group mb-0 sm:col-span-2">
                    <label class="erp-form-label">
                        اسم المندوب بالكامل <span class="required">*</span>
                    </label>
                    <input type="text" name="name" value="{{ old('name') }}" required placeholder="مثال: أحمد محمود إبراهيم" class="erp-input">
                </div>

                <!-- Username -->
                <div class="erp-form-group mb-0">
                    <label class="erp-form-label">
                        اسم المستخدم (لتسجيل الدخول) <span class="required">*</span>
                    </label>
                    <div class="relative">
                        <input type="text" name="username" value="{{ old('username') }}" required placeholder="مثال: ahmed_rep أو rep1" class="erp-input font-mono text-left" dir="ltr">
                    </div>
                    <span class="text-[11px] text-[var(--text-muted)] mt-1 block">اسم فريد يدخل به المندوب للنظام بدلاً من البريد الإلكتروني.</span>
                </div>

                <!-- Password -->
                <div class="erp-form-group mb-0">
                    <label class="erp-form-label">
                        كلمة المرور <span class="required">*</span>
                    </label>
                    <input type="password" name="password" required minlength="6" placeholder="لا تقل عن 6 أحرف أو أرقام" class="erp-input font-mono text-left" dir="ltr">
                    <span class="text-[11px] text-[var(--text-muted)] mt-1 block">كلمة سر قوية وسهلة الحفظ للمندوب.</span>
                </div>

                <!-- Phone -->
                <div class="erp-form-group mb-0">
                    <label class="erp-form-label">رقم الهاتف (اختياري)</label>
                    <input type="tel" name="phone" value="{{ old('phone') }}" placeholder="مثال: 01012345678" class="erp-input font-numeric">
                    <span class="text-[11px] text-[var(--text-muted)] mt-1 block">يمكن استخدامه للتواصل وتسجيل الدخول أيضاً.</span>
                </div>

                <!-- Email -->
                <div class="erp-form-group mb-0">
                    <label class="erp-form-label">البريد الإلكتروني (اختياري)</label>
                    <input type="email" name="email" value="{{ old('email') }}" placeholder="rep@company.com" class="erp-input text-left" dir="ltr">
                    <span class="text-[11px] text-[var(--text-muted)] mt-1 block">غير إلزامي، يمكنك تركه فارغاً تماماً.</span>
                </div>
            </div>
        </div>

        <!-- Section 2: نظام العمولة والمستودع -->
        <div>
            <div class="border-b border-[var(--border-light)] pb-3 mb-4">
                <h2 class="erp-card-title text-base font-bold flex items-center gap-2">
                    <x-icon name="percent" size="20" class="text-[var(--primary)]" />
                    <span>إعدادات العمولة والفرع التابع له</span>
                </h2>
                <p class="text-xs text-[var(--text-muted)] mt-1">تحديد كيفية احتساب عمولة المندوب تلقائياً مع كل فاتورة مبيعات معتمدة.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Commission Type -->
                <div class="erp-form-group mb-0">
                    <label class="erp-form-label">نوع احتساب العمولة <span class="required">*</span></label>
                    <select name="commission_type" required class="erp-select">
                        <option value="percentage" {{ old('commission_type', 'percentage') === 'percentage' ? 'selected' : '' }}>نسبة مئوية (%) من إجمالي الفاتورة</option>
                        <option value="fixed" {{ old('commission_type') === 'fixed' ? 'selected' : '' }}>مبلغ ثابت (ج.م) لكل فاتورة</option>
                    </select>
                </div>

                <!-- Commission Rate -->
                <div class="erp-form-group mb-0">
                    <label class="erp-form-label">قيمة أو نسبة العمولة <span class="required">*</span></label>
                    <div class="relative">
                        <input type="number" step="0.01" min="0" name="commission_rate" value="{{ old('commission_rate', '0.00') }}" required placeholder="مثال: 5 أو 50" class="erp-input font-numeric">
                    </div>
                    <span class="text-[11px] text-[var(--text-muted)] mt-1 block">مثال: إذا كانت 5 والنوع نسبة، يُحسب 5% عمولة.</span>
                </div>

                <!-- Warehouse Assignment -->
                <div class="erp-form-group mb-0 sm:col-span-2">
                    <label class="erp-form-label">المستودع أو الفرع التابع له المندوب</label>
                    <select name="warehouse_id" class="erp-select">
                        <option value="">-- كافة المستودعات والفروع (عام) --</option>
                        @foreach($warehouses as $wh)
                            <option value="{{ $wh->id }}" {{ (string)old('warehouse_id') === (string)$wh->id ? 'selected' : '' }}>{{ $wh->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Active Checkbox -->
            <div class="pt-4 mt-2">
                <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', '1') === '1' ? 'checked' : '' }} class="rounded border-[var(--border-color)] text-[var(--primary)] focus:ring-[var(--primary)]">
                    <span class="text-sm font-semibold text-[var(--text-main)]">الحساب نشط ويحق للمندوب الدخول والبيع الميداني فوراً</span>
                </label>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="flex items-center justify-end gap-3 pt-4 border-t border-[var(--border-light)]">
            <a href="{{ route('sales-reps.index') }}" class="erp-btn erp-btn-secondary">إلغاء</a>
            <button type="submit" class="erp-btn erp-btn-primary">
                <x-icon name="check-circle" size="16" />
                <span>حفظ وإنشاء حساب المندوب</span>
            </button>
        </div>
    </form>
</div>
@endsection
