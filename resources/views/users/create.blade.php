@extends('layouts.app')

@section('control_panel')
    <x-control-panel title="إضافة مستخدم جديد" :breadcrumbs="['الإدارة والنظام' => route('settings.index'), 'المستخدمون' => route('users.index'), 'إضافة مستخدم' => route('users.create')]">
        <x-slot:actions>
            <a href="{{ route('users.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="arrow-right" size="16" />
                <span>العودة للمستخدمين</span>
            </a>
        </x-slot:actions>
    </x-control-panel>
@endsection

@section('content')
    <div class="user-form-container">
        @if($errors->any())
            <div class="erp-alert erp-alert-danger mb-4" role="alert">
                <x-icon name="alert-triangle" size="16" />
                <div>
                    <strong>يرجى تصحيح الأخطاء التالية:</strong>
                    <ul class="my-1 mx-4 text-xs list-disc">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <div class="user-form-card">
            <div class="user-form-header">
                <div class="erp-card-title">
                    <x-icon name="user-plus" size="18" />
                    <span>بيانات الحساب الجديد والصلاحيات</span>
                </div>
                <span class="erp-badge erp-badge-neutral text-xs">حساب جديد</span>
            </div>

            <form action="{{ route('users.store') }}" method="POST">
                @csrf

                <div class="user-form-body">
                    <!-- Row 1: Name, Email & Phone -->
                    <div class="erp-form-grid mb-4">
                        <div class="erp-form-group">
                            <label for="userName" class="erp-form-label">
                                اسم المستخدم الكامل <span class="text-[var(--danger)]">*</span>
                            </label>
                            <input
                                type="text"
                                id="userName"
                                name="name"
                                value="{{ old('name') }}"
                                class="erp-input @error('name') erp-input-error @enderror"
                                placeholder="مثال: أحمد عبد الله"
                                required
                                autofocus
                            >
                        </div>

                        <div class="erp-form-group">
                            <label for="userEmail" class="erp-form-label">
                                البريد الإلكتروني للدخول <span class="text-[var(--danger)]">*</span>
                            </label>
                            <input
                                type="email"
                                id="userEmail"
                                name="email"
                                value="{{ old('email') }}"
                                class="erp-input font-numeric @error('email') erp-input-error @enderror"
                                placeholder="user@kayan-erp.com"
                                required
                            >
                        </div>

                        <div class="erp-form-group">
                            <label for="userPhone" class="erp-form-label">
                                رقم الموبايل / واتساب
                            </label>
                            <input
                                type="text"
                                id="userPhone"
                                name="phone"
                                value="{{ old('phone') }}"
                                class="erp-input font-numeric @error('phone') erp-input-error @enderror"
                                placeholder="010XXXXXXXX"
                            >
                        </div>
                    </div>

                    <!-- Row 2: Password & Role -->
                    <div class="erp-form-row-2col mb-4">
                        <div class="erp-form-group">
                            <label for="userPassword" class="erp-form-label">
                                كلمة المرور <span class="text-[var(--danger)]">*</span>
                            </label>
                            <input
                                type="password"
                                id="userPassword"
                                name="password"
                                class="erp-input font-numeric @error('password') erp-input-error @enderror"
                                placeholder="••••••••"
                                required
                            >
                            <small class="text-[11px] text-[var(--text-muted)] mt-1">6 أحرف على الأقل</small>
                        </div>

                        <div class="erp-form-group">
                            <label for="userRole" class="erp-form-label">
                                الدور الوظيفي والصلاحيات <span class="text-[var(--danger)]">*</span>
                            </label>
                            <select id="userRole" name="role_id" class="erp-input @error('role_id') erp-input-error @enderror" required>
                                <option value="">-- اختر الدور الوظيفي --</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role->id }}" data-slug="{{ $role->slug }}" {{ (string) old('role_id') === (string) $role->id ? 'selected' : '' }}>
                                        {{ $role->name }} @if($role->description)— {{ $role->description }}@endif
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-[11px] text-[var(--text-muted)] mt-1">يحدد الصلاحيات المتاحة لهذا الحساب</small>
                        </div>
                    </div>



                    <!-- Row 3: Workplace & Warehouse Access Permissions -->
                    <div class="user-status-card mb-4">
                        <div class="w-full">
                            <div class="font-bold text-sm text-[var(--text-main)] flex items-center gap-1.5 mb-1">
                                <x-icon name="home" size="16" class="text-[var(--primary)]" />
                                <span>مكان العمل ومستودعات الصرف (نقطة البيع والكاشير)</span>
                            </div>
                            <div class="text-xs text-[var(--text-muted)] mb-3">
                                حدد المستودع الأساسي لمكان عمل الموظف، أو امنحه صلاحية معالجة المعاملات والبيع من عدة مستودعات.
                            </div>

                            <div class="erp-form-row-2col">
                                <div class="erp-form-group mb-0">
                                    <label for="userWarehouse" class="erp-form-label text-xs font-semibold">
                                        مكان العمل / المستودع الافتراضي
                                    </label>
                                    <select id="userWarehouse" name="warehouse_id" class="erp-input @error('warehouse_id') erp-input-error @enderror">
                                        <option value="">-- كل المستودعات أو غير محدد --</option>
                                        @foreach($warehouses as $wh)
                                            <option value="{{ $wh->id }}" {{ (string) old('warehouse_id') === (string) $wh->id ? 'selected' : '' }}>
                                                {{ $wh->name }} {{ $wh->is_default ? '(المستودع الرئيسي)' : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="text-[11px] text-[var(--text-muted)] mt-1">المستودع الذي يعمل به الكاشير ويتم الصرف منه تلقائياً</small>
                                </div>

                                <div class="erp-form-group mb-0 flex flex-col justify-center">
                                    <label class="erp-form-label text-xs font-semibold">صلاحية التبديل بين المستودعات</label>
                                    <label class="inline-flex items-center gap-2 cursor-pointer select-none mt-1 p-2 border border-[var(--border-color)] rounded-[var(--border-radius-sm)] bg-[var(--bg-surface)]">
                                        <input type="checkbox" name="can_access_all_warehouses" value="1" {{ old('can_access_all_warehouses') ? 'checked' : '' }} class="w-4 h-4 accent-[var(--primary)]">
                                        <span class="font-semibold text-xs text-[var(--text-main)]">السماح بمعالجة المعاملات والبيع من عدة مستودعات</span>
                                    </label>
                                    <small class="text-[11px] text-[var(--text-muted)] mt-1">إذا لم يتم التفعيل، سيقتصر الكاشير على مستودعه المحدد فقط</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Row 4: Account Active Status Card -->
                    <div class="user-status-card">
                        <div>
                            <div class="font-bold text-sm text-[var(--text-main)]">حالة تفعيل الحساب</div>
                            <div class="text-xs text-[var(--text-muted)] mt-0.5">الحساب المفعل يتمكن من تسجيل الدخول واستخدام النظام فوراً</div>
                        </div>
                        <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', '1') === '1' ? 'checked' : '' }} class="w-4 h-4 accent-[var(--primary)]">
                            <span class="font-semibold text-xs text-[var(--text-main)]">مفعل</span>
                        </label>
                    </div>
                </div>

                <div class="user-form-footer">
                    <a href="{{ route('users.index') }}" class="erp-btn erp-btn-secondary">إلغاء</a>
                    <button type="submit" class="erp-btn erp-btn-primary">
                        <x-icon name="check" size="16" />
                        <span>حفظ بيانات المستخدم</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
