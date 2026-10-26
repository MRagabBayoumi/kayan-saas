@extends('layouts.app')

@section('control_panel')
    <x-control-panel title="إدارة وتحليل أداء مناديب المبيعات" :breadcrumbs="['المبيعات' => route('sales.index'), 'المناديب والمبيعات الميدانية' => '#']">
        <x-slot:actions>
            <button type="button" id="openAddRepBtn" data-action="open-add-rep-modal" onclick="openAddRepModal()" class="erp-btn erp-btn-primary">
                <x-icon name="user-plus" size="16" />
                <span>إضافة مندوب مبيعات سريع</span>
            </button>
            <a href="{{ route('sales-reps.create') }}" class="erp-btn erp-btn-secondary" title="إضافة مندوب عبر صفحة كاملة">
                <x-icon name="plus-circle" size="16" />
                <span>صفحة الإضافة</span>
            </a>
            <a href="{{ route('portal.index') }}" target="_blank" class="erp-btn erp-btn-secondary" title="معاينة بوابة المندوب الميداني">
                <x-icon name="smartphone" size="16" class="text-sky-600" />
                <span>فتح بوابة المندوب</span>
            </a>
        </x-slot:actions>

        <x-slot:extra>
            <span class="erp-badge erp-badge-neutral font-numeric px-2.5 py-1 text-xs">
                <span>إجمالي المناديب:</span>
                <strong>{{ $metrics['total_reps'] }}</strong>
                <span>(نشط: {{ $metrics['active_reps'] }})</span>
            </span>
        </x-slot:extra>
    </x-control-panel>
@endsection

@section('content')
<div class="space-y-4" data-page="sales-reps">

    @if(session('success'))
        <div class="erp-alert erp-alert-success mb-4" role="alert">
            <x-icon name="check-circle" size="18" />
            <div>{{ session('success') }}</div>
        </div>
    @endif

    @if(session('error'))
        <div class="erp-alert erp-alert-danger mb-4" role="alert">
            <x-icon name="alert-triangle" size="18" />
            <div>{{ session('error') }}</div>
        </div>
    @endif

    @if($errors->any())
        <div class="erp-alert erp-alert-danger mb-4" role="alert">
            <x-icon name="alert-circle" size="18" />
            <div>
                <strong>يرجى مراجعة الأخطاء التالية:</strong>
                <ul class="list-disc list-inside mt-1 text-xs">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <!-- Enterprise Sub-Navigation Tabs -->
    <x-sales-reps-nav-tabs active="reps" :pending-requests-count="$metrics['pending_customer_requests'] ?? 0" />

    <!-- Global Reps Aggregate KPI Summary Cards (Faithful to Kayan ERP Design System) -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 mb-4">
        <!-- 1. Total Sales Volume -->
        <div class="kpi-card flex flex-col justify-between">
            <div class="kpi-header mb-1">
                <span class="kpi-label">مبيعات المناديب</span>
                <span class="kpi-icon text-[var(--primary)]"><x-icon name="trending-up" size="18" /></span>
            </div>
            <div class="kpi-value font-numeric text-base sm:text-lg font-black text-[var(--text-main)]">
                {{ number_format($metrics['total_sales_volume'], 2) }} <span class="text-xs font-normal text-[var(--text-muted)]">ج.م</span>
            </div>
            <div class="kpi-note font-numeric text-xs text-[var(--text-muted)]">
                <span>فواتير معتمدة</span>
            </div>
        </div>

        <!-- 2. Total Invoices -->
        <div class="kpi-card flex flex-col justify-between">
            <div class="kpi-header mb-1">
                <span class="kpi-label">عدد الفواتير</span>
                <span class="kpi-icon text-sky-600"><x-icon name="file-text" size="18" /></span>
            </div>
            <div class="kpi-value font-numeric text-base sm:text-lg font-black text-[var(--primary)]">
                {{ number_format($metrics['total_invoices_count']) }} <span class="text-xs font-normal text-[var(--text-muted)]">فاتورة</span>
            </div>
            <div class="kpi-note text-xs text-[var(--text-muted)]">
                <span>طلبات محررة</span>
            </div>
        </div>

        <!-- 3. Total Commissions -->
        <div class="kpi-card flex flex-col justify-between">
            <div class="kpi-header mb-1">
                <span class="kpi-label text-indigo-900 font-semibold">إجمالي العمولات</span>
                <span class="kpi-icon text-indigo-600"><x-icon name="dollar-sign" size="18" /></span>
            </div>
            <div class="kpi-value font-numeric text-base sm:text-lg font-black text-indigo-700">
                {{ number_format($metrics['total_commissions'], 2) }} <span class="text-xs font-normal text-indigo-600">ج.م</span>
            </div>
            <div class="kpi-note text-indigo-600 text-xs">
                <span>مستحقات الأداء</span>
            </div>
        </div>

        <!-- 4. Pending Commissions -->
        <div class="kpi-card flex flex-col justify-between border-amber-200 bg-amber-50/40">
            <div class="kpi-header mb-1">
                <span class="kpi-label text-amber-900 font-bold">عمولات معلقة</span>
                <span class="kpi-icon text-amber-600"><x-icon name="clock" size="18" /></span>
            </div>
            <div class="kpi-value font-numeric text-base sm:text-lg font-black text-amber-800">
                {{ number_format($metrics['pending_commissions'], 2) }} <span class="text-xs font-bold text-amber-700">ج.م</span>
            </div>
            <div class="kpi-note text-amber-700 text-xs font-medium">
                <span>قيد الصرف والاعتماد</span>
            </div>
        </div>

        <!-- 5. Paid Commissions -->
        <div class="kpi-card flex flex-col justify-between border-emerald-200 bg-emerald-50/40">
            <div class="kpi-header mb-1">
                <span class="kpi-label text-emerald-900 font-bold">عمولات تم صرفها</span>
                <span class="kpi-icon text-emerald-600"><x-icon name="check-circle" size="18" /></span>
            </div>
            <div class="kpi-value font-numeric text-base sm:text-lg font-black text-emerald-800">
                {{ number_format($metrics['paid_commissions'], 2) }} <span class="text-xs font-bold text-emerald-700">ج.م</span>
            </div>
            <div class="kpi-note text-emerald-700 text-xs font-medium">
                <span>تم تحويلها للمناديب</span>
            </div>
        </div>

        <!-- 6. Active Reps -->
        <div class="kpi-card flex flex-col justify-between">
            <div class="kpi-header mb-1">
                <span class="kpi-label">المناديب النشطون</span>
                <span class="kpi-icon text-[var(--primary)]"><x-icon name="users" size="18" /></span>
            </div>
            <div class="kpi-value font-numeric text-base sm:text-lg font-black text-[var(--text-main)]">
                {{ $metrics['active_reps'] }} <span class="text-xs font-normal text-[var(--text-muted)]">من {{ $metrics['total_reps'] }}</span>
            </div>
            <div class="kpi-note text-emerald-700 text-xs font-medium">
                <span>حسابات نشطة تعمل</span>
            </div>
        </div>
    </div>

    <!-- Search & Filter Bar -->
    <div class="erp-card p-4 mb-0">
        <form method="GET" action="{{ route('sales-reps.index') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-2.5">
            <div class="sm:col-span-8 lg:col-span-9 relative">
                <input type="search" name="search" value="{{ $currentSearch }}" placeholder="ابحث باسم المندوب، رقم الهاتف، أو البريد الإلكتروني..."
                    class="erp-input pr-9 text-xs sm:text-sm">
                <div class="absolute right-3 top-1/2 -translate-y-1/2 text-[var(--text-muted)] pointer-events-none">
                    <x-icon name="search" size="15" />
                </div>
            </div>

            <div class="sm:col-span-2 lg:col-span-2">
                <select name="status" class="erp-select text-xs">
                    <option value="">كافة الحالات</option>
                    <option value="active" {{ $currentStatus === 'active' ? 'selected' : '' }}>نشط فقط</option>
                    <option value="inactive" {{ $currentStatus === 'inactive' ? 'selected' : '' }}>معطل</option>
                </select>
            </div>

            <div class="sm:col-span-2 lg:col-span-1 flex items-center gap-1.5">
                <button type="submit" class="erp-btn erp-btn-primary w-full text-xs h-[38px] justify-center">
                    <span>بحث</span>
                </button>
                @if($currentSearch || $currentStatus)
                    <a href="{{ route('sales-reps.index') }}" class="erp-btn erp-btn-secondary px-2.5 h-[38px]" title="إعادة تعيين">
                        <x-icon name="rotate-ccw" size="14" />
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Reps Comparison & Performance Table -->
    <div class="erp-card mb-0 overflow-hidden">
        <div class="erp-card-header">
            <h2 class="erp-card-title text-sm">
                <x-icon name="users" size="16" class="text-[var(--primary)]" />
                <span>قائمة مناديب المبيعات ومؤشرات الأداء</span>
            </h2>
            <span class="text-xs text-[var(--text-muted)] font-numeric">إجمالي المعروض: {{ $salesReps->total() }}</span>
        </div>

        <div class="overflow-x-auto">
            <table class="erp-table w-full text-right min-w-[950px]">
                <thead>
                    <tr>
                        <th class="w-12">#</th>
                        <th>المندوب</th>
                        <th>المستودع / الفرع</th>
                        <th>نسبة العمولة</th>
                        <th>العملاء</th>
                        <th>الفواتير</th>
                        <th>إجمالي المبيعات</th>
                        <th>إجمالي العمولات</th>
                        <th>الحالة</th>
                        <th class="w-48 text-center">الإجراءات والتحليل</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[var(--border-light)]">
                    @forelse($salesReps as $rep)
                        <tr class="hover:bg-[var(--bg-hover)] transition-colors">
                            <td class="font-numeric text-xs text-[var(--text-muted)]">{{ $rep->id }}</td>
                            <td>
                                <div class="font-bold text-xs sm:text-sm text-[var(--text-main)] flex items-center gap-1.5 flex-wrap">
                                    <span>{{ $rep->name }}</span>
                                    @if($rep->username)
                                        <span class="text-[10px] font-mono text-sky-700 bg-sky-50 border border-sky-200 rounded px-1.5 py-0.5" dir="ltr" title="اسم المستخدم لتسجيل الدخول">{{ '@' . $rep->username }}</span>
                                    @endif
                                </div>
                                <div class="text-[11px] text-[var(--text-muted)] font-numeric flex items-center gap-2 mt-0.5">
                                    <span>{{ $rep->phone ?? 'بدون هاتف' }}</span>
                                    @if($rep->email)
                                        <span>·</span>
                                        <span>{{ $rep->email }}</span>
                                    @endif
                                </div>
                            </td>
                            <td class="text-xs text-[var(--text-secondary)]">
                                {{ $rep->warehouse?->name ?? 'كافة الفروع' }}
                            </td>
                            <td>
                                <span class="erp-badge erp-badge-primary font-numeric text-xs font-bold">
                                    {{ (float) $rep->commission_rate }}{{ $rep->commission_type === 'percentage' ? '%' : ' ج.م' }}
                                </span>
                            </td>
                            <td>
                                <span class="font-bold font-numeric text-xs text-[var(--text-main)]">{{ $rep->assigned_customers_count }}</span>
                                <span class="text-[10px] text-[var(--text-muted)]">عميل</span>
                            </td>
                            <td>
                                <span class="font-bold font-numeric text-xs text-[var(--text-main)]">{{ $rep->posted_invoices_count }}</span>
                                <span class="text-[10px] text-[var(--text-muted)]">فاتورة</span>
                            </td>
                            <td class="font-numeric font-bold text-xs sm:text-sm text-[var(--text-main)]">
                                {{ number_format((float) ($rep->total_sales_volume ?? 0), 2) }} <span class="text-[10px] text-[var(--text-muted)]">ج.م</span>
                            </td>
                            <td class="font-numeric font-bold text-xs text-emerald-700">
                                {{ number_format((float) ($rep->total_commissions_earned ?? 0), 2) }} <span class="text-[10px] text-emerald-600">ج.م</span>
                            </td>
                            <td>
                                @if($rep->is_active)
                                    <span class="erp-badge erp-badge-success text-[10px]">نشط</span>
                                @else
                                    <span class="erp-badge erp-badge-danger text-[10px]">معطل</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="inline-flex items-center gap-1.5">
                                    <!-- Performance Analytics Dashboard Link -->
                                    <a href="{{ route('sales-reps.show', $rep) }}" class="erp-btn erp-btn-sm erp-btn-primary text-xs py-1 px-2.5" title="تحليل وتقارير أداء المندوب">
                                        <x-icon name="bar-chart-2" size="13" />
                                        <span>تحليل الأداء</span>
                                    </a>

                                    <!-- Edit Rep Button -->
                                    <button type="button" 
                                        data-action="open-edit-rep-modal" 
                                        data-rep-id="{{ $rep->id }}"
                                        data-rep-name="{{ $rep->name }}"
                                        data-rep-username="{{ $rep->username }}"
                                        data-rep-email="{{ $rep->email ?? '' }}"
                                        data-rep-phone="{{ $rep->phone ?? '' }}"
                                        data-rep-commission-rate="{{ (float) $rep->commission_rate }}"
                                        data-rep-commission-type="{{ $rep->commission_type }}"
                                        data-rep-warehouse-id="{{ $rep->warehouse_id }}"
                                        data-rep-active="{{ $rep->is_active ? '1' : '0' }}"
                                        class="erp-btn erp-btn-sm erp-btn-secondary p-1.5" 
                                        title="تعديل بيانات المندوب">
                                        <x-icon name="edit-3" size="13" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-12 text-[var(--text-muted)]">
                                <x-icon name="users" size="36" class="text-slate-300 mx-auto mb-2" />
                                <div class="text-sm font-bold text-[var(--text-main)]">لا يوجد مناديب مسجلين مطابقين للبحث</div>
                                <div class="text-xs text-[var(--text-muted)] mt-1">اضغط على «إضافة مندوب مبيعات جديد» لبدء تفعيل المناديب الميدانيين.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($salesReps->hasPages())
            <div class="p-3 border-t border-[var(--border-light)]">
                {{ $salesReps->links() }}
            </div>
        @endif
    </div>

</div>

<!-- Modal: Add New Sales Representative -->
<div id="addRepModal" class="erp-modal-overlay" style="display: none;">
    <div class="erp-modal-container max-w-lg">
        <div class="erp-modal-header">
            <div class="erp-modal-title">
                <x-icon name="user-plus" size="18" class="text-[var(--primary)]" />
                <span>إضافة مندوب مبيعات ميداني جديد</span>
            </div>
            <button type="button" class="erp-modal-close" data-action="close-modal" data-target="addRepModal" onclick="closeRepModal('addRepModal')" title="إغلاق">✕</button>
        </div>

        <form method="POST" action="{{ route('sales-reps.store') }}">
            @csrf
            <div class="erp-modal-body p-5 space-y-3.5 overflow-y-auto max-h-[70vh]">
                @if($errors->any())
                    <div class="p-3 bg-rose-50 border border-rose-200 text-rose-700 text-xs rounded-md">
                        <div class="font-bold mb-1">يرجى تصحيح الأخطاء التالية:</div>
                        <ul class="list-disc list-inside space-y-0.5">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- Name -->
                <div class="erp-form-group mb-0">
                    <label class="erp-form-label">اسم المندوب بالكامل <span class="required">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" required placeholder="مثال: أحمد محمود إبراهيم" class="erp-input text-xs sm:text-sm">
                </div>

                <!-- Username & Password -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="erp-form-group mb-0">
                        <label class="erp-form-label">اسم المستخدم للدخول <span class="required">*</span></label>
                        <input type="text" name="username" value="{{ old('username') }}" required placeholder="مثال: ahmed_rep" class="erp-input text-xs sm:text-sm font-mono text-left" dir="ltr">
                    </div>

                    <div class="erp-form-group mb-0">
                        <label class="erp-form-label">كلمة المرور للدخول <span class="required">*</span></label>
                        <input type="password" name="password" required minlength="6" placeholder="لا تقل عن 6 أحرف" class="erp-input text-xs sm:text-sm font-mono text-left" dir="ltr">
                    </div>
                </div>

                <!-- Phone & Email -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="erp-form-group mb-0">
                        <label class="erp-form-label">رقم الهاتف (اختياري)</label>
                        <input type="tel" name="phone" value="{{ old('phone') }}" placeholder="مثال: 01012345678" class="erp-input text-xs sm:text-sm font-numeric">
                    </div>

                    <div class="erp-form-group mb-0">
                        <label class="erp-form-label">البريد الإلكتروني (اختياري)</label>
                        <input type="email" name="email" value="{{ old('email') }}" placeholder="rep@company.com" class="erp-input text-xs sm:text-sm text-left" dir="ltr">
                    </div>
                </div>

                <!-- Commission Rate & Type -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="erp-form-group mb-0">
                        <label class="erp-form-label">نوع احتساب العمولة <span class="required">*</span></label>
                        <select name="commission_type" required class="erp-select text-xs">
                            <option value="percentage" {{ old('commission_type', 'percentage') === 'percentage' ? 'selected' : '' }}>نسبة مئوية (%) من قيمة الفاتورة</option>
                            <option value="fixed" {{ old('commission_type') === 'fixed' ? 'selected' : '' }}>مبلغ ثابت (ج.م) لكل فاتورة</option>
                        </select>
                    </div>

                    <div class="erp-form-group mb-0">
                        <label class="erp-form-label">قيمة أو نسبة العمولة <span class="required">*</span></label>
                        <div class="relative">
                            <input type="number" step="0.01" min="0" name="commission_rate" value="{{ old('commission_rate', '0.00') }}" required placeholder="مثال: 5 أو 50" class="erp-input text-xs font-numeric">
                        </div>
                    </div>
                </div>

                <!-- Warehouse Assignment -->
                <div class="erp-form-group mb-0">
                    <label class="erp-form-label">المستودع أو الفرع التابع له المندوب</label>
                    <select name="warehouse_id" class="erp-select text-xs">
                        <option value="">-- كافة المستودعات والفروع --</option>
                        @foreach($warehouses as $wh)
                            <option value="{{ $wh->id }}" {{ (string)old('warehouse_id') === (string)$wh->id ? 'selected' : '' }}>{{ $wh->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Active Checkbox -->
                <div class="pt-1">
                    <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', '1') === '1' ? 'checked' : '' }} class="rounded border-[var(--border-color)] text-[var(--primary)] focus:ring-[var(--primary)]">
                        <span class="text-xs font-semibold text-[var(--text-main)]">الحساب نشط ويحق له تسجيل الدخول لبوابة المندوب الميداني</span>
                    </label>
                </div>
            </div>

            <!-- Actions Footer -->
            <div class="erp-modal-footer p-4 flex items-center justify-end gap-2.5 bg-[var(--bg-surface-alt)] border-t border-[var(--border-light)]">
                <button type="button" data-action="close-modal" data-target="addRepModal" onclick="closeRepModal('addRepModal')" class="erp-btn erp-btn-secondary text-xs">إلغاء</button>
                <button type="submit" class="erp-btn erp-btn-primary text-xs">
                    <x-icon name="check" size="14" />
                    <span>حفظ وإنشاء حساب المندوب</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Edit Sales Representative -->
<div id="editRepModal" class="erp-modal-overlay" style="display: none;">
    <div class="erp-modal-container max-w-lg">
        <div class="erp-modal-header">
            <div class="erp-modal-title">
                <x-icon name="edit" size="18" class="text-[var(--primary)]" />
                <span>تعديل بيانات المندوب ونسبة العمولة</span>
            </div>
            <button type="button" class="erp-modal-close" data-action="close-modal" data-target="editRepModal" onclick="closeRepModal('editRepModal')" title="إغلاق">✕</button>
        </div>

        <form id="editRepForm" method="POST" action="">
            @csrf
            @method('PUT')
            <div class="erp-modal-body p-5 space-y-3.5 overflow-y-auto max-h-[70vh]">
                <!-- Name -->
                <div class="erp-form-group mb-0">
                    <label class="erp-form-label">اسم المندوب بالكامل <span class="required">*</span></label>
                    <input type="text" id="editRepName" name="name" required class="erp-input text-xs sm:text-sm">
                </div>

                <!-- Username & Password Change -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="erp-form-group mb-0">
                        <label class="erp-form-label">اسم المستخدم للدخول <span class="required">*</span></label>
                        <input type="text" id="editRepUsername" name="username" required class="erp-input text-xs sm:text-sm font-mono text-left" dir="ltr">
                    </div>

                    <div class="erp-form-group mb-0">
                        <label class="erp-form-label">كلمة المرور (اتركه فارغاً للتثبيت)</label>
                        <input type="password" name="password" minlength="6" placeholder="كلمة مرور جديدة (اختياري)" class="erp-input text-xs sm:text-sm font-mono text-left" dir="ltr">
                    </div>
                </div>

                <!-- Phone & Email -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="erp-form-group mb-0">
                        <label class="erp-form-label">رقم الهاتف (اختياري)</label>
                        <input type="tel" id="editRepPhone" name="phone" class="erp-input text-xs sm:text-sm font-numeric">
                    </div>

                    <div class="erp-form-group mb-0">
                        <label class="erp-form-label">البريد الإلكتروني (اختياري)</label>
                        <input type="email" id="editRepEmail" name="email" class="erp-input text-xs sm:text-sm text-left" dir="ltr">
                    </div>
                </div>

                <!-- Commission Rate & Type -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="erp-form-group mb-0">
                        <label class="erp-form-label">نوع العمولة <span class="required">*</span></label>
                        <select id="editRepCommissionType" name="commission_type" required class="erp-select text-xs">
                            <option value="percentage">نسبة مئوية (%)</option>
                            <option value="fixed">مبلغ ثابت (ج.م)</option>
                        </select>
                    </div>

                    <div class="erp-form-group mb-0">
                        <label class="erp-form-label">نسبة أو قيمة العمولة <span class="required">*</span></label>
                        <input type="number" step="0.01" min="0" id="editRepCommissionRate" name="commission_rate" required class="erp-input text-xs font-numeric">
                    </div>
                </div>

                <!-- Warehouse Assignment -->
                <div class="erp-form-group mb-0">
                    <label class="erp-form-label">المستودع / الفرع</label>
                    <select id="editRepWarehouseId" name="warehouse_id" class="erp-select text-xs">
                        <option value="">-- كافة المستودعات والفروع --</option>
                        @foreach($warehouses as $wh)
                            <option value="{{ $wh->id }}">{{ $wh->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Active Checkbox -->
                <div class="pt-1">
                    <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                        <input type="checkbox" id="editRepActive" name="is_active" value="1" class="rounded border-[var(--border-color)] text-[var(--primary)] focus:ring-[var(--primary)]">
                        <span class="text-xs font-semibold text-[var(--text-main)]">الحساب نشط ويحق له استخدام النظام</span>
                    </label>
                </div>
            </div>

            <!-- Actions Footer -->
            <div class="erp-modal-footer p-4 flex items-center justify-end gap-2.5 bg-[var(--bg-surface-alt)] border-t border-[var(--border-light)]">
                <button type="button" data-action="close-modal" data-target="editRepModal" onclick="closeRepModal('editRepModal')" class="erp-btn erp-btn-secondary text-xs">إلغاء</button>
                <button type="submit" class="erp-btn erp-btn-primary text-xs">
                    <x-icon name="check" size="14" />
                    <span>تحديث بيانات المندوب</span>
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function openAddRepModal() {
        const modal = document.getElementById('addRepModal');
        if (modal) {
            modal.classList.add('open');
            modal.classList.remove('hidden');
            modal.style.display = 'flex';
            const firstInput = modal.querySelector('input[name="name"]');
            if (firstInput) firstInput.focus();
        }
    }

    function closeRepModal(modalId) {
        const modal = typeof modalId === 'string' ? document.getElementById(modalId) : modalId;
        if (modal) {
            modal.classList.remove('open');
            modal.classList.add('hidden');
            modal.style.display = 'none';
        }
    }

    function openEditRepModal(btn) {
        const modal = document.getElementById('editRepModal');
        if (!modal) return;

        const repId = btn.getAttribute('data-rep-id');
        const form = document.getElementById('editRepForm');
        if (form) {
            form.action = `/sales-reps/${repId}`;
        }

        const setVal = (id, val) => {
            const el = document.getElementById(id);
            if (el) el.value = val ?? '';
        };

        setVal('editRepName', btn.getAttribute('data-rep-name') || '');
        setVal('editRepUsername', btn.getAttribute('data-rep-username') || '');
        setVal('editRepEmail', btn.getAttribute('data-rep-email') || '');
        setVal('editRepPhone', btn.getAttribute('data-rep-phone') || '');
        setVal('editRepCommissionRate', btn.getAttribute('data-rep-commission-rate') || 0);
        setVal('editRepCommissionType', btn.getAttribute('data-rep-commission-type') || 'percentage');
        setVal('editRepWarehouseId', btn.getAttribute('data-rep-warehouse-id') || '');

        const activeCb = document.getElementById('editRepActive');
        if (activeCb) {
            activeCb.checked = btn.getAttribute('data-rep-active') === '1';
        }

        modal.classList.add('open');
        modal.classList.remove('hidden');
        modal.style.display = 'flex';

        const nameInput = document.getElementById('editRepName');
        if (nameInput) nameInput.focus();
    }

    // Auto-open add modal if there were validation errors
    @if($errors->any())
        openAddRepModal();
    @endif

    // Global click listener delegation for maximum reliability
    document.addEventListener('click', function(e) {
        const openAddBtn = e.target.closest('[data-action="open-add-rep-modal"]');
        if (openAddBtn) {
            e.preventDefault();
            openAddRepModal();
            return;
        }

        const openEditBtn = e.target.closest('[data-action="open-edit-rep-modal"]');
        if (openEditBtn) {
            e.preventDefault();
            openEditRepModal(openEditBtn);
            return;
        }

        const closeBtn = e.target.closest('[data-action="close-modal"]');
        if (closeBtn) {
            e.preventDefault();
            const target = closeBtn.getAttribute('data-target');
            closeRepModal(target);
            return;
        }

        if (e.target.classList && e.target.classList.contains('erp-modal-overlay')) {
            closeRepModal(e.target);
        }
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeRepModal('addRepModal');
            closeRepModal('editRepModal');
        }
    });
</script>
@endpush
@endsection
