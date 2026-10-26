@extends('layouts.app')

@section('control_panel')
    <x-control-panel title="دليل عملاء الجملة والتجزئة" :breadcrumbs="['العملاء والموردون' => route('customers.index'), 'العملاء' => route('customers.index')]">
        <x-slot:actions>
            <a href="{{ route('customers.create') }}" class="erp-btn erp-btn-primary">
                <x-icon name="plus" size="16" />
                <span>إضافة عميل جديد</span>
            </a>
            <a href="{{ route('suppliers.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="truck" size="16" />
                <span>الموردون ومصانع الصين</span>
            </a>
            <a href="{{ route('shipping-offices.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="navigation" size="16" />
                <span>مكاتب الشحن</span>
            </a>
        </x-slot:actions>

        <x-slot:extra>
            <span class="erp-badge erp-badge-neutral font-numeric px-2.5 py-1 text-xs">
                <span>إجمالي العملاء:</span>
                <strong>{{ $customers->total() }}</strong>
            </span>
        </x-slot:extra>
    </x-control-panel>
@endsection

@section('content')
    <div data-page="customers">
        @if(session('success'))
            <div class="erp-alert erp-alert-success mb-4" role="alert">
                <x-icon name="check" size="16" />
                <div>{{ session('success') }}</div>
            </div>
        @endif

        @if(session('error'))
            <div class="erp-alert erp-alert-danger mb-4" role="alert">
                <x-icon name="alert-triangle" size="16" />
                <div>{{ session('error') }}</div>
            </div>
        @endif

        @if($errors->any())
            <div class="erp-alert erp-alert-danger mb-4" role="alert">
                <x-icon name="alert-triangle" size="16" />
                <div>
                    <strong>يوجد خطأ في إدخال البيانات:</strong>
                    <ul class="m-0 mt-1 pr-4">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <!-- KPI Summary Cards -->
        <div class="customer-kpi-grid">
            <div class="customer-kpi-card">
                <div class="customer-kpi-icon primary">
                    <x-icon name="users" size="22" />
                </div>
                <div class="customer-kpi-info">
                    <div class="customer-kpi-label">إجمالي العملاء المسجلين</div>
                    <div class="customer-kpi-value font-numeric">{{ number_format($metrics['total_customers']) }}</div>
                </div>
            </div>

            <div class="customer-kpi-card">
                <div class="customer-kpi-icon success">
                    <x-icon name="package" size="22" />
                </div>
                <div class="customer-kpi-info">
                    <div class="customer-kpi-label">تجار الجملة المعتمدين</div>
                    <div class="customer-kpi-value font-numeric">{{ number_format($metrics['wholesale_count']) }}</div>
                </div>
            </div>

            <div class="customer-kpi-card">
                <div class="customer-kpi-icon info">
                    <x-icon name="shopping-bag" size="22" />
                </div>
                <div class="customer-kpi-info">
                    <div class="customer-kpi-label">عملاء التجزئة</div>
                    <div class="customer-kpi-value font-numeric">{{ number_format($metrics['retail_count']) }}</div>
                </div>
            </div>

            <div class="customer-kpi-card">
                <div class="customer-kpi-icon warning">
                    <x-icon name="calculator" size="22" />
                </div>
                <div class="customer-kpi-info">
                    <div class="customer-kpi-label">مستحقات آجلة لدى العملاء</div>
                    <div class="customer-kpi-value font-numeric">{{ number_format($metrics['total_receivables'], 2) }} <span class="text-xs font-normal text-[var(--text-muted)]">ج.م</span></div>
                </div>
            </div>
        </div>

        <!-- Filter & Search Bar -->
        <div class="customer-filter-bar">
            <form action="{{ route('customers.index') }}" method="GET" class="customer-filter-inputs">
                <input 
                    type="text" 
                    name="search" 
                    value="{{ request('search') }}" 
                    class="customer-filter-input" 
                    placeholder="ابحث بالاسم، المحل، أو الهاتف...">

                <select name="type" class="customer-filter-input">
                    <option value="">جميع أنواع العملاء</option>
                    <option value="wholesale" @selected(request('type') === 'wholesale')>تاجر جملة</option>
                    <option value="retail" @selected(request('type') === 'retail')>قطاعي</option>
                    <option value="distributor" @selected(request('type') === 'distributor')>موزع معتمد</option>
                </select>

                @if($cities->isNotEmpty())
                    <select name="city" class="customer-filter-input">
                        <option value="">جميع المحافظات / المدن</option>
                        @foreach($cities as $c)
                            <option value="{{ $c }}" @selected(request('city') === $c)>{{ $c }}</option>
                        @endforeach
                    </select>
                @endif

                <button type="submit" class="erp-btn erp-btn-sm erp-btn-primary">
                    <x-icon name="search" size="14" />
                    <span>تصفية</span>
                </button>

                @if(request()->hasAny(['search', 'type', 'city']))
                    <a href="{{ route('customers.index') }}" class="erp-btn erp-btn-sm erp-btn-secondary" title="إلغاء التصفية">
                        <x-icon name="refresh" size="14" />
                        <span>إلغاء</span>
                    </a>
                @endif
            </form>
        </div>

        <!-- Customers Table -->
        <div class="erp-card overflow-hidden">
            <div class="erp-table-responsive">
                <table class="erp-table">
                    <thead>
                        <tr>
                            <th class="w-12 text-center">#</th>
                            <th>الاسم والمحل / الشركة</th>
                            <th>نوع العميل</th>
                            <th>رقم الهاتف</th>
                            <th>المدينة / العنوان</th>
                            <th>فئة التسعير</th>
                            <th class="text-end">الرصيد المالي الحالي</th>
                            <th class="text-end">حد الائتمان</th>
                            <th class="text-center">الحالة</th>
                            <th class="text-center w-28">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($customers as $customer)
                            <tr>
                                <td class="text-center font-numeric text-xs text-[var(--text-muted)]">
                                    {{ $customer->id }}
                                </td>
                                <td>
                                    <a href="{{ route('customers.show', $customer) }}" class="font-bold text-[var(--text-main)] hover:text-[var(--primary)] block">
                                        {{ $customer->name }}
                                    </a>
                                    @if($customer->company_name)
                                        <div class="text-xs text-[var(--text-muted)] flex items-center gap-1 mt-0.5">
                                            <x-icon name="briefcase" size="12" />
                                            <span>{{ $customer->company_name }}</span>
                                        </div>
                                    @endif
                                    @if($customer->salesRep)
                                        <div class="text-[11px] text-amber-600 font-semibold flex items-center gap-1 mt-0.5" title="مندوب المبيعات المسؤول">
                                            <x-icon name="user" size="11" />
                                            <span>مندوب: {{ $customer->salesRep->name }}</span>
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    @if($customer->customer_type === 'wholesale')
                                        <span class="erp-badge erp-badge-success text-xs">تاجر جملة</span>
                                    @elseif($customer->customer_type === 'distributor')
                                        <span class="erp-badge erp-badge-warning text-xs">موزع</span>
                                    @else
                                        <span class="erp-badge erp-badge-info text-xs">قطاعي</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="tel:{{ $customer->phone }}" class="font-numeric text-xs text-[var(--text-main)] hover:text-[var(--primary)] direction-ltr inline-block">
                                        {{ $customer->phone }}
                                    </a>
                                    @if($customer->phone_alt)
                                        <div class="font-numeric text-[11px] text-[var(--text-muted)] direction-ltr">
                                            {{ $customer->phone_alt }}
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <div class="text-xs text-[var(--text-secondary)]">
                                        {{ $customer->city ?: '—' }}
                                    </div>
                                    @if($customer->address)
                                        <div class="text-[11px] text-[var(--text-muted)] truncate max-w-xs">
                                            {{ $customer->address }}
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    @if($customer->pricing_tier === 'wholesale')
                                        <span class="erp-badge erp-badge-primary text-xs">سعر الجملة</span>
                                    @elseif($customer->pricing_tier === 'distributor')
                                        <span class="erp-badge erp-badge-warning text-xs">سعر الموزع</span>
                                    @else
                                        <span class="erp-badge erp-badge-neutral text-xs">سعر القطاعي</span>
                                    @endif
                                </td>
                                <td class="text-end font-numeric font-bold">
                                    @if($customer->current_balance > 0)
                                        <span class="text-[var(--danger)]">
                                            {{ number_format($customer->current_balance, 2) }} ج.م
                                            <span class="text-[10px] font-normal block text-[var(--text-muted)]">(مدين عليه)</span>
                                        </span>
                                    @elseif($customer->current_balance < 0)
                                        <span class="text-[var(--success)]">
                                            {{ number_format(abs($customer->current_balance), 2) }} ج.م
                                            <span class="text-[10px] font-normal block text-[var(--text-muted)]">(دائن له)</span>
                                        </span>
                                    @else
                                        <span class="text-[var(--text-muted)]">0.00 ج.م</span>
                                    @endif
                                </td>
                                <td class="text-end font-numeric text-xs text-[var(--text-secondary)]">
                                    {{ $customer->credit_limit > 0 ? number_format($customer->credit_limit, 2) . ' ج.م' : '—' }}
                                </td>
                                <td class="text-center">
                                    <span class="erp-badge {{ $customer->is_active ? 'erp-badge-neutral' : 'erp-badge-danger' }} text-xs">
                                        {{ $customer->is_active ? 'نشط' : 'معطل' }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="flex items-center justify-center gap-1">
                                        <a href="{{ route('customers.show', $customer) }}" class="erp-btn erp-btn-sm erp-btn-secondary" title="ملف العميل">
                                            <x-icon name="user" size="14" />
                                        </a>
                                        <button 
                                            type="button" 
                                            data-action="open-edit-customer"
                                            data-customer="{{ json_encode($customer) }}"
                                            class="erp-btn erp-btn-sm erp-btn-secondary"
                                            title="تعديل">
                                            <x-icon name="edit" size="14" />
                                        </button>
                                        @if((float) $customer->current_balance == 0.0)
                                            <form action="{{ route('customers.destroy', $customer) }}" method="POST" data-confirm="هل أنت متأكد من حذف العميل {{ $customer->name }}؟" class="m-0">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="erp-btn erp-btn-sm erp-btn-danger" title="حذف">
                                                    <x-icon name="trash" size="14" />
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-10 text-[var(--text-muted)]">
                                    <x-icon name="users" size="40" class="mb-2 inline-block text-[var(--text-muted)]" />
                                    <div class="font-bold text-base mb-1">لا توجد حسابات عملاء مطابقة</div>
                                    <div class="text-xs mb-3">يمكنك تسجيل عميل جديد أو تغيير معايير البحث والتصفية.</div>
                                    <button type="button" data-action="open-add-customer" class="erp-btn erp-btn-sm erp-btn-primary">
                                        <x-icon name="plus" size="14" />
                                        <span>إضافة عميل جديد الآن</span>
                                    </button>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($customers->hasPages())
                <div class="p-4 border-t border-[var(--border-color)]">
                    {{ $customers->links() }}
                </div>
            @endif
        </div>

        <!-- Modal: Add Customer -->
        <div id="addCustomerModal" class="erp-modal-overlay">
            <div class="erp-modal-container max-w-xl">
                <div class="erp-modal-header">
                    <div class="erp-modal-title">
                        <x-icon name="user" size="18" />
                        <span>إضافة عميل جديد</span>
                    </div>
                    <button type="button" data-action="close-add-customer" class="erp-modal-close" title="إغلاق">✕</button>
                </div>

                <form action="{{ route('customers.store') }}" method="POST">
                    @csrf
                    <div class="erp-modal-body p-5">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5 mb-3.5">
                            <div>
                                <label class="erp-label block mb-1.5 text-xs font-semibold">اسم العميل *</label>
                                <input type="text" name="name" class="erp-input w-full" placeholder="الاسم ثلاثي أو رباعي" required>
                            </div>
                            <div>
                                <label class="erp-label block mb-1.5 text-xs font-semibold">اسم المحل / الشركة</label>
                                <input type="text" name="company_name" class="erp-input w-full" placeholder="اسم المعرض أو المحل التجاري">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5 mb-3.5">
                            <div>
                                <label class="erp-label block mb-1.5 text-xs font-semibold">رقم الهاتف الأساسي *</label>
                                <input type="text" name="phone" class="erp-input font-numeric w-full" placeholder="01xxxxxxxxx" required>
                            </div>
                            <div>
                                <label class="erp-label block mb-1.5 text-xs font-semibold">رقم هاتف إضافي</label>
                                <input type="text" name="phone_alt" class="erp-input font-numeric w-full" placeholder="هاتف بديل أو أرضي">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5 mb-3.5">
                            <div>
                                <label class="erp-label block mb-1.5 text-xs font-semibold">نوع العميل *</label>
                                <select name="customer_type" class="erp-input w-full" required>
                                    <option value="wholesale">تاجر جملة</option>
                                    <option value="retail">قطاعي</option>
                                    <option value="distributor">موزع معتمد</option>
                                </select>
                            </div>
                            <div>
                                <label class="erp-label block mb-1.5 text-xs font-semibold">فئة التسعير المطبقة *</label>
                                <select name="pricing_tier" class="erp-input w-full" required>
                                    <option value="wholesale">سعر الجملة</option>
                                    <option value="retail">سعر القطاعي</option>
                                    <option value="distributor">سعر موزع</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5 mb-3.5">
                            <div>
                                <label class="erp-label block mb-1.5 text-xs font-semibold">المحافظة / المدينة</label>
                                <input type="text" name="city" class="erp-input w-full" placeholder="مثال: القاهرة، طنطا، أسيوط">
                            </div>
                            <div>
                                <label class="erp-label block mb-1.5 text-xs font-semibold">العنوان التفصيلي</label>
                                <input type="text" name="address" class="erp-input w-full" placeholder="المنطقة، الشارع، علامة مميزة">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3.5 mb-3.5">
                            <div>
                                <label class="erp-label block mb-1.5 text-xs font-semibold">المندوب المسؤول</label>
                                <select name="sales_rep_id" class="erp-input w-full">
                                    <option value="">-- بدون مندوب --</option>
                                    @foreach($salesReps as $rep)
                                        <option value="{{ $rep->id }}">{{ $rep->name }} ({{ (float)$rep->commission_rate }}{{ $rep->commission_type === 'percentage' ? '%' : ' ج.م' }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="erp-label block mb-1.5 text-xs font-semibold">حد الائتمان الأقصى (ج.م)</label>
                                <input type="number" step="0.01" min="0" name="credit_limit" value="0.00" class="erp-input font-numeric w-full">
                            </div>
                            <div>
                                <label class="erp-label block mb-1.5 text-xs font-semibold">الرصيد الافتتاحي (ج.م)</label>
                                <input type="number" step="0.01" name="opening_balance" value="0.00" class="erp-input font-numeric w-full" placeholder="موجب: عليه، سالب: له">
                            </div>
                        </div>

                        <div class="mb-3.5">
                            <label class="erp-label block mb-1.5 text-xs font-semibold">ملاحظات إضافية</label>
                            <textarea name="notes" class="erp-input w-full" rows="2" placeholder="ملاحظات حول طريقة السداد، شروط التعامل، إلخ..."></textarea>
                        </div>

                        <div>
                            <label class="flex items-center gap-2 cursor-pointer text-sm text-[var(--text-main)]">
                                <input type="checkbox" name="is_active" value="1" checked>
                                <span>حساب العميل نشط</span>
                            </label>
                        </div>
                    </div>

                    <div class="erp-modal-footer p-4 flex justify-end gap-2.5 bg-[var(--bg-surface-alt)] border-t border-[var(--border-light)]">
                        <button type="button" data-action="close-add-customer" class="erp-btn erp-btn-secondary">إلغاء</button>
                        <button type="submit" class="erp-btn erp-btn-primary">حفظ العميل</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal: Edit Customer -->
        <div id="editCustomerModal" class="erp-modal-overlay">
            <div class="erp-modal-container max-w-xl">
                <div class="erp-modal-header">
                    <div class="erp-modal-title">
                        <x-icon name="edit" size="18" />
                        <span>تعديل بيانات العميل</span>
                    </div>
                    <button type="button" data-action="close-edit-customer" class="erp-modal-close" title="إغلاق">✕</button>
                </div>

                <form id="editCustomerForm" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="erp-modal-body p-5">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5 mb-3.5">
                            <div>
                                <label class="erp-label block mb-1.5 text-xs font-semibold">اسم العميل *</label>
                                <input type="text" id="edit_cust_name" name="name" class="erp-input w-full" required>
                            </div>
                            <div>
                                <label class="erp-label block mb-1.5 text-xs font-semibold">اسم المحل / الشركة</label>
                                <input type="text" id="edit_cust_company_name" name="company_name" class="erp-input w-full">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5 mb-3.5">
                            <div>
                                <label class="erp-label block mb-1.5 text-xs font-semibold">رقم الهاتف الأساسي *</label>
                                <input type="text" id="edit_cust_phone" name="phone" class="erp-input font-numeric w-full" required>
                            </div>
                            <div>
                                <label class="erp-label block mb-1.5 text-xs font-semibold">رقم هاتف إضافي</label>
                                <input type="text" id="edit_cust_phone_alt" name="phone_alt" class="erp-input font-numeric w-full">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5 mb-3.5">
                            <div>
                                <label class="erp-label block mb-1.5 text-xs font-semibold">نوع العميل *</label>
                                <select id="edit_cust_customer_type" name="customer_type" class="erp-input w-full" required>
                                    <option value="wholesale">تاجر جملة</option>
                                    <option value="retail">قطاعي</option>
                                    <option value="distributor">موزع معتمد</option>
                                </select>
                            </div>
                            <div>
                                <label class="erp-label block mb-1.5 text-xs font-semibold">فئة التسعير المطبقة *</label>
                                <select id="edit_cust_pricing_tier" name="pricing_tier" class="erp-input w-full" required>
                                    <option value="wholesale">سعر الجملة</option>
                                    <option value="retail">سعر القطاعي</option>
                                    <option value="distributor">سعر موزع</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5 mb-3.5">
                            <div>
                                <label class="erp-label block mb-1.5 text-xs font-semibold">المحافظة / المدينة</label>
                                <input type="text" id="edit_cust_city" name="city" class="erp-input w-full">
                            </div>
                            <div>
                                <label class="erp-label block mb-1.5 text-xs font-semibold">العنوان التفصيلي</label>
                                <input type="text" id="edit_cust_address" name="address" class="erp-input w-full">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5 mb-3.5">
                            <div>
                                <label class="erp-label block mb-1.5 text-xs font-semibold">مندوب المبيعات المسؤول</label>
                                <select id="edit_cust_sales_rep_id" name="sales_rep_id" class="erp-input w-full">
                                    <option value="">-- بدون مندوب --</option>
                                    @foreach($salesReps as $rep)
                                        <option value="{{ $rep->id }}">{{ $rep->name }} ({{ (float)$rep->commission_rate }}{{ $rep->commission_type === 'percentage' ? '%' : ' ج.م' }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="erp-label block mb-1.5 text-xs font-semibold">حد الائتمان الأقصى (ج.م)</label>
                                <input type="number" step="0.01" min="0" id="edit_cust_credit_limit" name="credit_limit" class="erp-input font-numeric w-full">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5 mb-3.5">
                            <div>
                                <label class="erp-label block mb-1.5 text-xs font-semibold">الرصيد الافتتاحي (ج.م)</label>
                                <input type="number" step="0.01" id="edit_cust_opening_balance" name="opening_balance" class="erp-input font-numeric w-full" placeholder="موجب: عليه، سالب: له">
                            </div>
                            <div>
                                <label class="erp-label block mb-1.5 text-xs font-semibold">الرصيد المالي الحالي (ج.م)</label>
                                <input type="number" step="0.01" id="edit_cust_current_balance" name="current_balance" class="erp-input font-numeric w-full">
                            </div>
                        </div>

                        <div class="mb-3.5">
                            <label class="erp-label block mb-1.5 text-xs font-semibold">ملاحظات إضافية</label>
                            <textarea id="edit_cust_notes" name="notes" class="erp-input w-full" rows="2"></textarea>
                        </div>

                        <div>
                            <label class="flex items-center gap-2 cursor-pointer text-sm text-[var(--text-main)]">
                                <input type="checkbox" id="edit_cust_is_active" name="is_active" value="1">
                                <span>حساب العميل نشط</span>
                            </label>
                        </div>
                    </div>

                    <div class="erp-modal-footer p-4 flex justify-end gap-2.5 bg-[var(--bg-surface-alt)] border-t border-[var(--border-light)]">
                        <button type="button" data-action="close-edit-customer" class="erp-btn erp-btn-secondary">إلغاء</button>
                        <button type="submit" class="erp-btn erp-btn-primary">حفظ التعديلات</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
