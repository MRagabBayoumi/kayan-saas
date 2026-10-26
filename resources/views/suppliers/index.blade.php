@extends('layouts.app')

@section('control_panel')
    <x-control-panel title="دليل الموردين ومصانع الصين" :breadcrumbs="['العملاء والموردون' => route('suppliers.index'), 'الموردون' => route('suppliers.index')]">
        <x-slot:actions>
            <button type="button" data-action="open-add-supplier" class="erp-btn erp-btn-primary">
                <x-icon name="plus" size="16" />
                <span>إضافة مورد جديد</span>
            </button>

            <!-- Supplier Balance Due Report & Print Menu -->
            <div class="erp-dropdown">
                <button type="button" class="erp-btn erp-btn-secondary" data-dropdown-toggle="supplierIndexBalanceMenu">
                    <x-icon name="briefcase" size="16" />
                    <span>تقرير المستحقات (Balance Due)</span>
                    <x-icon name="chevron-down" size="14" />
                </button>
                <div id="supplierIndexBalanceMenu" class="erp-dropdown-menu">
                    <a href="{{ route('suppliers.balance-due-report') }}" class="erp-dropdown-item">
                        <x-icon name="eye" size="16" />
                        <span>استعراض تقرير الأرصدة المستحقة</span>
                    </a>
                    <div class="erp-dropdown-divider"></div>
                    <a href="{{ route('suppliers.balance-due-report.print', ['format' => 'a4']) }}" target="_blank" class="erp-dropdown-item">
                        <x-icon name="file-text" size="16" />
                        <span>طباعة تقرير الأرصدة A4 قياسي</span>
                    </a>
                    <a href="{{ route('suppliers.balance-due-report.print', ['format' => 'a5']) }}" target="_blank" class="erp-dropdown-item">
                        <x-icon name="file" size="16" />
                        <span>طباعة تقرير الأرصدة A5 وسط</span>
                    </a>
                    <a href="{{ route('suppliers.balance-due-report.print', ['format' => 'thermal_80mm']) }}" target="_blank" class="erp-dropdown-item">
                        <x-icon name="printer" size="16" />
                        <span>طباعة تقرير الأرصدة 80 مم حراري</span>
                    </a>
                </div>
            </div>

            <a href="{{ route('supplier-payments.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="receipt" size="16" />
                <span>مدفوعات وسندات الصرف</span>
            </a>
            <a href="{{ route('supplier-installments.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="calendar" size="16" />
                <span>جدول الأقساط والمستحقات</span>
            </a>
            <a href="{{ route('customers.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="users" size="16" />
                <span>دليل العملاء</span>
            </a>
            <a href="{{ route('shipping-offices.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="navigation" size="16" />
                <span>مكاتب الشحن</span>
            </a>
        </x-slot:actions>

        <x-slot:extra>
            <span class="erp-badge erp-badge-neutral font-numeric px-2.5 py-1 text-xs">
                <span>إجمالي الموردين:</span>
                <strong>{{ $suppliers->total() }}</strong>
            </span>
        </x-slot:extra>
    </x-control-panel>
@endsection

@section('content')
    <div data-page="suppliers">
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
        <div class="supplier-kpi-grid">
            <div class="supplier-kpi-card">
                <div class="supplier-kpi-icon primary">
                    <x-icon name="truck" size="22" />
                </div>
                <div class="supplier-kpi-info">
                    <div class="supplier-kpi-label">إجمالي الموردين والجهات</div>
                    <div class="supplier-kpi-value font-numeric">{{ number_format($metrics['total_suppliers']) }}</div>
                </div>
            </div>

            <div class="supplier-kpi-card">
                <div class="supplier-kpi-icon danger">
                    <x-icon name="home" size="22" />
                </div>
                <div class="supplier-kpi-info">
                    <div class="supplier-kpi-label">مصانع وموردي الصين</div>
                    <div class="supplier-kpi-value font-numeric">{{ number_format($metrics['china_factories_count']) }}</div>
                </div>
            </div>

            <div class="supplier-kpi-card">
                <div class="supplier-kpi-icon info">
                    <x-icon name="package" size="22" />
                </div>
                <div class="supplier-kpi-info">
                    <div class="supplier-kpi-label">الموردون المحليون (مصر)</div>
                    <div class="supplier-kpi-value font-numeric">{{ number_format($metrics['local_suppliers_count']) }}</div>
                </div>
            </div>

            <div class="supplier-kpi-card">
                <div class="supplier-kpi-icon warning">
                    <x-icon name="navigation" size="22" />
                </div>
                <div class="supplier-kpi-info">
                    <div class="supplier-kpi-label">وكلاء الشحن والتخليص</div>
                    <div class="supplier-kpi-value font-numeric">{{ number_format($metrics['shipping_agents_count']) }}</div>
                </div>
            </div>

            <div class="supplier-kpi-card">
                <div class="supplier-kpi-icon success">
                    <x-icon name="dollar-sign" size="22" />
                </div>
                <div class="supplier-kpi-info">
                    <div class="supplier-kpi-label">مستحقات آجلة للموردين</div>
                    <div class="supplier-kpi-value font-numeric">{{ number_format($metrics['total_payables'] ?? 0, 2) }} <span class="text-xs font-normal text-[var(--text-muted)]">ج.م</span></div>
                </div>
            </div>
        </div>

        <!-- Filter & Search Bar -->
        <div class="supplier-filter-bar">
            <form action="{{ route('suppliers.index') }}" method="GET" class="supplier-filter-inputs">
                <input 
                    type="text" 
                    name="search" 
                    value="{{ request('search') }}" 
                    class="supplier-filter-input" 
                    placeholder="ابحث باسم المصنع، المسؤول، الهاتف، أو WeChat...">

                <select name="type" class="supplier-filter-input">
                    <option value="">جميع أنواع الموردين</option>
                    <option value="china_factory" @selected(request('type') === 'china_factory')>مصنع في الصين</option>
                    <option value="local_supplier" @selected(request('type') === 'local_supplier')>مورد محلي</option>
                    <option value="shipping_agent" @selected(request('type') === 'shipping_agent')>وكيل شحن / تخليص</option>
                </select>

                @if($countries->isNotEmpty())
                    <select name="country" class="supplier-filter-input">
                        <option value="">جميع الدول</option>
                        @foreach($countries as $cnt)
                            <option value="{{ $cnt }}" @selected(request('country') === $cnt)>{{ $cnt }}</option>
                        @endforeach
                    </select>
                @endif

                <button type="submit" class="erp-btn erp-btn-sm erp-btn-primary">
                    <x-icon name="search" size="14" />
                    <span>تصفية</span>
                </button>

                @if(request()->hasAny(['search', 'type', 'country']))
                    <a href="{{ route('suppliers.index') }}" class="erp-btn erp-btn-sm erp-btn-secondary" title="إلغاء التصفية">
                        <x-icon name="refresh" size="14" />
                        <span>إلغاء</span>
                    </a>
                @endif
            </form>
        </div>

        <!-- Suppliers Table -->
        <div class="erp-card overflow-hidden">
            <div class="erp-table-responsive">
                <table class="erp-table">
                    <thead>
                        <tr>
                            <th class="w-12 text-center">#</th>
                            <th>المورد / المصنع</th>
                            <th>التصنيف</th>
                            <th>الدولة / المدينة</th>
                            <th>المسؤول للتواصل</th>
                            <th>الهاتف / الاتصال</th>
                            <th>حساب WeChat</th>
                            <th class="text-center">عملة الحساب</th>
                            <th class="text-end">الرصيد المعلق</th>
                            <th class="text-center">الحالة</th>
                            <th class="text-center w-28">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($suppliers as $supplier)
                            <tr>
                                <td class="text-center font-numeric text-xs text-[var(--text-muted)]">
                                    {{ $supplier->id }}
                                </td>
                                <td>
                                    <a href="{{ route('suppliers.show', $supplier) }}" class="font-bold text-[var(--text-main)] hover:text-[var(--primary)] block">
                                        {{ $supplier->name }}
                                    </a>
                                    @if($supplier->address)
                                        <div class="text-[11px] text-[var(--text-muted)] truncate max-w-xs">
                                            {{ $supplier->address }}
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    @if($supplier->supplier_type === 'china_factory')
                                        <span class="erp-badge erp-badge-danger text-xs">مصنع بالصين</span>
                                    @elseif($supplier->supplier_type === 'shipping_agent')
                                        <span class="erp-badge erp-badge-warning text-xs">وكيل شحن</span>
                                    @else
                                        <span class="erp-badge erp-badge-info text-xs">مورد محلي</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="text-xs font-semibold text-[var(--text-main)]">
                                        {{ $supplier->country ?: 'China' }}
                                    </div>
                                    @if($supplier->city)
                                        <div class="text-[11px] text-[var(--text-muted)]">
                                            {{ $supplier->city }}
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <div class="text-xs text-[var(--text-main)]">
                                        {{ $supplier->contact_person ?: '—' }}
                                    </div>
                                    @if($supplier->email)
                                        <div class="text-[11px] text-[var(--text-muted)] direction-ltr">
                                            {{ $supplier->email }}
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    @if($supplier->phone)
                                        <a href="tel:{{ $supplier->phone }}" class="font-numeric text-xs text-[var(--text-main)] hover:text-[var(--primary)] direction-ltr inline-block">
                                            {{ $supplier->phone }}
                                        </a>
                                    @else
                                        <span class="text-xs text-[var(--text-muted)]">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($supplier->wechat_id)
                                        <span class="supplier-wechat-tag font-numeric">
                                            <x-icon name="smartphone" size="12" />
                                            <span>{{ $supplier->wechat_id }}</span>
                                        </span>
                                    @else
                                        <span class="text-xs text-[var(--text-muted)]">—</span>
                                    @endif
                                </td>
                                <td class="text-center font-numeric font-bold text-xs">
                                    <span class="erp-badge erp-badge-neutral">
                                        {{ $supplier->currency }}
                                    </span>
                                </td>
                                <td class="text-end font-numeric font-bold text-xs">
                                    @if($supplier->current_balance != 0)
                                        <span class="{{ $supplier->current_balance > 0 ? 'text-[var(--warning)]' : 'text-[var(--success)]' }}">
                                            {{ number_format($supplier->current_balance, 2) }} {{ $supplier->currency }}
                                        </span>
                                    @else
                                        <span class="text-[var(--text-muted)]">0.00 {{ $supplier->currency }}</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="erp-badge {{ $supplier->is_active ? 'erp-badge-neutral' : 'erp-badge-danger' }} text-xs">
                                        {{ $supplier->is_active ? 'نشط' : 'معطل' }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="flex items-center justify-center gap-1">
                                        <button 
                                            type="button" 
                                            data-action="quick-pay-supplier"
                                            data-supplier-id="{{ $supplier->id }}"
                                            data-supplier-name="{{ $supplier->name }}"
                                            data-supplier-balance="{{ (float)$supplier->current_balance }}"
                                            data-supplier-currency="{{ $supplier->currency }}"
                                            class="erp-btn erp-btn-sm text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200"
                                            title="صرف دفعة / سند صرف">
                                            <x-icon name="credit-card" size="14" />
                                        </button>
                                        <button 
                                            type="button" 
                                            data-action="add-installment-supplier"
                                            data-supplier-id="{{ $supplier->id }}"
                                            data-supplier-name="{{ $supplier->name }}"
                                            class="erp-btn erp-btn-sm text-sky-700 bg-sky-50 hover:bg-sky-100 border border-sky-200"
                                            title="جدولة قسط مستحق">
                                            <x-icon name="calendar" size="14" />
                                        </button>
                                        <a href="{{ route('suppliers.show', $supplier) }}" class="erp-btn erp-btn-sm erp-btn-secondary" title="ملف المورد">
                                            <x-icon name="truck" size="14" />
                                        </a>
                                        <button 
                                            type="button" 
                                            data-action="open-edit-supplier"
                                            data-supplier="{{ json_encode($supplier) }}"
                                            class="erp-btn erp-btn-sm erp-btn-secondary"
                                            title="تعديل">
                                            <x-icon name="edit" size="14" />
                                        </button>
                                        @if((float) $supplier->current_balance == 0.0)
                                            <form action="{{ route('suppliers.destroy', $supplier) }}" method="POST" data-confirm="هل أنت متأكد من حذف المورد {{ $supplier->name }}؟" class="m-0">
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
                                <td colspan="11" class="text-center py-10 text-[var(--text-muted)]">
                                    <x-icon name="truck" size="40" class="mb-2 inline-block text-[var(--text-muted)]" />
                                    <div class="font-bold text-base mb-1">لا يوجد موردون مسجلون مطابقون</div>
                                    <div class="text-xs mb-3">سجل مصانع الاستيراد أو الموردين المحليين لتسهيل إصدار أوامر الشراء.</div>
                                    <button type="button" data-action="open-add-supplier" class="erp-btn erp-btn-sm erp-btn-primary">
                                        <x-icon name="plus" size="14" />
                                        <span>إضافة مورد جديد</span>
                                    </button>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($suppliers->hasPages())
                <div class="p-4 border-t border-[var(--border-color)]">
                    {{ $suppliers->links() }}
                </div>
            @endif
        </div>

        <!-- Modal: Add Supplier -->
        <div id="addSupplierModal" class="erp-modal-overlay">
            <div class="erp-modal-container max-w-xl">
                <div class="erp-modal-header">
                    <div class="erp-modal-title">
                        <x-icon name="truck" size="18" />
                        <span>إضافة مورد / مصنع جديد</span>
                    </div>
                    <button type="button" data-action="close-add-supplier" class="erp-modal-close" title="إغلاق">✕</button>
                </div>

                <form id="addSupplierForm" action="{{ route('suppliers.store') }}" method="POST">
                    @csrf
                    <div class="erp-modal-body p-5">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5 mb-3.5">
                            <div>
                                <label class="erp-label block mb-1.5 text-xs font-semibold">اسم المورد / المصنع *</label>
                                <input type="text" name="name" class="erp-input w-full" placeholder="مثال: شركة الأمل للتجارة" required>
                            </div>
                            <div>
                                <label class="erp-label block mb-1.5 text-xs font-semibold">نوع المورد *</label>
                                <select id="add_sup_supplier_type" name="supplier_type" class="erp-input w-full" required>
                                    <option value="local_supplier" selected>مورد محلي (مصر)</option>
                                    <option value="china_factory">مصنع في الصين (استيراد)</option>
                                    <option value="shipping_agent">وكيل شحن وتخليص جمركي</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5 mb-3.5">
                            <div>
                                <label class="erp-label block mb-1.5 text-xs font-semibold">المسؤول أو جهة الاتصال</label>
                                <input type="text" name="contact_person" class="erp-input w-full" placeholder="مثال: أ. أحمد / Mr. Wang">
                            </div>
                            <div>
                                <label class="erp-label block mb-1.5 text-xs font-semibold">رقم الهاتف / الواتساب</label>
                                <input type="text" name="phone" class="erp-input font-numeric w-full" placeholder="01xxxxxxxxx / +86 xxxxxxxxx">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5 mb-3.5">
                            <div>
                                <label class="erp-label block mb-1.5 text-xs font-semibold">البريد الإلكتروني</label>
                                <input type="email" name="email" class="erp-input font-numeric w-full" placeholder="supplier@domain.com">
                            </div>
                            <div>
                                <label class="erp-label block mb-1.5 text-xs font-semibold">الرصيد المالي الافتتاحي</label>
                                <input type="number" step="0.01" name="current_balance" value="0.00" class="erp-input font-numeric w-full">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5 mb-3.5">
                            <div>
                                <label class="erp-label block mb-1.5 text-xs font-semibold">الدولة</label>
                                <input type="text" id="add_sup_country" name="country" value="مصر" class="erp-input w-full" placeholder="مصر / China">
                            </div>
                            <div>
                                <label class="erp-label block mb-1.5 text-xs font-semibold">المدينة أو المحافظة</label>
                                <input type="text" name="city" class="erp-input w-full" placeholder="القاهرة، الإسكندرية، Guangzhou...">
                            </div>
                        </div>

                        <!-- China Specific Fields (shown only when china_factory is selected) -->
                        <div id="add_china_fields" class="p-3.5 mb-3.5 rounded-lg border border-[var(--border-light)] bg-[var(--bg-surface-alt)]/50" style="display: none;">
                            <div class="text-xs font-semibold text-[var(--primary)] mb-2.5 flex items-center gap-1.5">
                                <x-icon name="globe" size="14" />
                                <span>بيانات خاصة بمصانع الصين والاستيراد</span>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                                <div>
                                    <label class="erp-label block mb-1.5 text-xs font-semibold">معرّف وي شات (WeChat ID)</label>
                                    <input type="text" id="add_sup_wechat_id" name="wechat_id" class="erp-input font-numeric w-full" placeholder="WeChat ID لمصانع الصين">
                                </div>
                                <div>
                                    <label class="erp-label block mb-1.5 text-xs font-semibold">عملة الصين / عملة الحساب *</label>
                                    <select id="add_sup_currency" name="currency" class="erp-input w-full">
                                        <option value="EGP" selected>الجنيه المصري (EGP ج.م)</option>
                                        <option value="CNY">اليوان الصيني (CNY ¥)</option>
                                        <option value="USD">الدولار الأمريكي (USD $)</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3.5">
                            <label class="erp-label block mb-1.5 text-xs font-semibold">العنوان التفصيلي وموقع المصنع / المحل</label>
                            <input type="text" name="address" class="erp-input w-full" placeholder="عنوان المصنع، رقم المستودع، أو المحل">
                        </div>

                        <div class="mb-3.5">
                            <label class="erp-label block mb-1.5 text-xs font-semibold">ملاحظات إضافية</label>
                            <textarea name="notes" class="erp-input w-full" rows="2" placeholder="ملاحظات حول جودة البضائع، شروط الدفع، موانئ الشحن المفضلة..."></textarea>
                        </div>

                        <div>
                            <label class="flex items-center gap-2 cursor-pointer text-sm text-[var(--text-main)]">
                                <input type="checkbox" name="is_active" value="1" checked>
                                <span>حساب المورد نشط</span>
                            </label>
                        </div>
                    </div>

                    <div class="erp-modal-footer p-4 flex justify-end gap-2.5 bg-[var(--bg-surface-alt)] border-t border-[var(--border-light)]">
                        <button type="button" data-action="close-add-supplier" class="erp-btn erp-btn-secondary">إلغاء</button>
                        <button type="submit" class="erp-btn erp-btn-primary">حفظ المورد</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal: Edit Supplier -->
        <div id="editSupplierModal" class="erp-modal-overlay">
            <div class="erp-modal-container max-w-xl">
                <div class="erp-modal-header">
                    <div class="erp-modal-title">
                        <x-icon name="edit" size="18" />
                        <span>تعديل بيانات المورد</span>
                    </div>
                    <button type="button" data-action="close-edit-supplier" class="erp-modal-close" title="إغلاق">✕</button>
                </div>

                <form id="editSupplierForm" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="erp-modal-body p-5">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5 mb-3.5">
                            <div>
                                <label class="erp-label block mb-1.5 text-xs font-semibold">اسم المورد / المصنع *</label>
                                <input type="text" id="edit_sup_name" name="name" class="erp-input w-full" required>
                            </div>
                            <div>
                                <label class="erp-label block mb-1.5 text-xs font-semibold">نوع المورد *</label>
                                <select id="edit_sup_supplier_type" name="supplier_type" class="erp-input w-full" required>
                                    <option value="local_supplier">مورد محلي (مصر)</option>
                                    <option value="china_factory">مصنع في الصين (استيراد)</option>
                                    <option value="shipping_agent">وكيل شحن وتخليص جمركي</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5 mb-3.5">
                            <div>
                                <label class="erp-label block mb-1.5 text-xs font-semibold">المسؤول أو جهة الاتصال</label>
                                <input type="text" id="edit_sup_contact_person" name="contact_person" class="erp-input w-full">
                            </div>
                            <div>
                                <label class="erp-label block mb-1.5 text-xs font-semibold">رقم الهاتف / الواتساب</label>
                                <input type="text" id="edit_sup_phone" name="phone" class="erp-input font-numeric w-full">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5 mb-3.5">
                            <div>
                                <label class="erp-label block mb-1.5 text-xs font-semibold">البريد الإلكتروني</label>
                                <input type="email" id="edit_sup_email" name="email" class="erp-input font-numeric w-full">
                            </div>
                            <div>
                                <label class="erp-label block mb-1.5 text-xs font-semibold">الرصيد المالي الحالي</label>
                                <input type="number" step="0.01" id="edit_sup_current_balance" name="current_balance" class="erp-input font-numeric w-full">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5 mb-3.5">
                            <div>
                                <label class="erp-label block mb-1.5 text-xs font-semibold">الدولة</label>
                                <input type="text" id="edit_sup_country" name="country" class="erp-input w-full">
                            </div>
                            <div>
                                <label class="erp-label block mb-1.5 text-xs font-semibold">المدينة أو المقاطعة</label>
                                <input type="text" id="edit_sup_city" name="city" class="erp-input w-full">
                            </div>
                        </div>

                        <!-- China Specific Fields (shown only when china_factory is selected) -->
                        <div id="edit_china_fields" class="p-3.5 mb-3.5 rounded-lg border border-[var(--border-light)] bg-[var(--bg-surface-alt)]/50" style="display: none;">
                            <div class="text-xs font-semibold text-[var(--primary)] mb-2.5 flex items-center gap-1.5">
                                <x-icon name="globe" size="14" />
                                <span>بيانات خاصة بمصانع الصين والاستيراد</span>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                                <div>
                                    <label class="erp-label block mb-1.5 text-xs font-semibold">معرّف وي شات (WeChat ID)</label>
                                    <input type="text" id="edit_sup_wechat_id" name="wechat_id" class="erp-input font-numeric w-full" placeholder="WeChat ID لمصانع الصين">
                                </div>
                                <div>
                                    <label class="erp-label block mb-1.5 text-xs font-semibold">عملة الصين / عملة الحساب *</label>
                                    <select id="edit_sup_currency" name="currency" class="erp-input w-full" required>
                                        <option value="CNY">اليوان الصيني (CNY ¥)</option>
                                        <option value="USD">الدولار الأمريكي (USD $)</option>
                                        <option value="EGP">الجنيه المصري (EGP ج.م)</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3.5">
                            <label class="erp-label block mb-1.5 text-xs font-semibold">العنوان التفصيلي وموقع المصنع</label>
                            <input type="text" id="edit_sup_address" name="address" class="erp-input w-full">
                        </div>

                        <div class="mb-3.5">
                            <label class="erp-label block mb-1.5 text-xs font-semibold">ملاحظات إضافية</label>
                            <textarea id="edit_sup_notes" name="notes" class="erp-input w-full" rows="2"></textarea>
                        </div>

                        <div>
                            <label class="flex items-center gap-2 cursor-pointer text-sm text-[var(--text-main)]">
                                <input type="checkbox" id="edit_sup_is_active" name="is_active" value="1">
                                <span>حساب المورد نشط</span>
                            </label>
                        </div>
                    </div>

                    <div class="erp-modal-footer p-4 flex justify-end gap-2.5 bg-[var(--bg-surface-alt)] border-t border-[var(--border-light)]">
                        <button type="button" data-action="close-edit-supplier" class="erp-btn erp-btn-secondary">إلغاء</button>
                        <button type="submit" class="erp-btn erp-btn-primary">حفظ التعديلات</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal: Quick Supplier Payment -->
        <div id="quickSupplierPaymentModal" class="erp-modal-overlay">
            <div class="erp-modal-container max-w-lg">
                <div class="erp-modal-header">
                    <div class="erp-modal-title">
                        <x-icon name="credit-card" size="18" />
                        <span>تسجيل سند صرف دفعة للمورد</span>
                    </div>
                    <button type="button" data-action="close-quick-pay" class="erp-modal-close">✕</button>
                </div>

                <form id="quickSupplierPaymentForm" method="POST">
                    @csrf
                    <div class="erp-modal-body p-5">
                        <div class="p-3 mb-3.5 rounded-lg bg-sky-50/70 border border-sky-200">
                            <div class="flex items-center justify-between text-xs">
                                <div>
                                    <span class="text-sky-800">المورد: </span>
                                    <strong id="qsp_supplier_name" class="text-sm font-bold text-[var(--text-main)]"></strong>
                                </div>
                                <div>
                                    <span class="text-sky-800">الرصيد المستحق: </span>
                                    <strong id="qsp_supplier_balance" class="text-sm font-numeric text-rose-600"></strong>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5 mb-3.5">
                            <div>
                                <label class="erp-label block mb-1.5 text-xs font-semibold">سداد على قسط محدد (اختياري)</label>
                                <select name="supplier_installment_id" id="qsp_installment_id" class="erp-input w-full" onchange="handleQspInstallmentChange(this)">
                                    <option value="">-- بدون قسط (سداد عام) --</option>
                                </select>
                            </div>
                            <div>
                                <label class="erp-label block mb-1.5 text-xs font-semibold">سداد على فاتورة محددة (اختياري)</label>
                                <select name="purchase_invoice_id" id="qsp_invoice_id" class="erp-input w-full" onchange="handleQspInvoiceChange(this)">
                                    <option value="">-- بدون فاتورة (سداد عام) --</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5 mb-3.5">
                            <div>
                                <label class="erp-label block mb-1.5 text-xs font-semibold">المبلغ المنصرف *</label>
                                <input type="number" step="0.01" min="0.01" name="amount" id="qsp_amount" class="erp-input font-numeric font-bold text-base text-[var(--danger)] w-full" required placeholder="0.00">
                            </div>
                            <div>
                                <label class="erp-label block mb-1.5 text-xs font-semibold">الخزينة / المصدر *</label>
                                <select name="treasury_id" class="erp-input w-full" required>
                                    @foreach($treasuries ?? [] as $tr)
                                        <option value="{{ $tr->id }}">
                                            {{ $tr->name }} (رصيد: {{ number_format((float)$tr->current_balance, 2) }} ج.م)
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5 mb-3.5">
                            <div>
                                <label class="erp-label block mb-1.5 text-xs font-semibold">تاريخ الصرف *</label>
                                <input type="date" name="payment_date" value="{{ date('Y-m-d') }}" class="erp-input font-numeric w-full" required>
                            </div>
                            <div>
                                <label class="erp-label block mb-1.5 text-xs font-semibold">طريقة الصرف *</label>
                                <select name="payment_method" class="erp-input w-full" required>
                                    <option value="cash" selected>نقداً من الخزينة / الدرج</option>
                                    <option value="bank_transfer">تحويل بنكي</option>
                                    <option value="cheque">شيك مصرفي</option>
                                    <option value="card">بطاقة دفع / فيزا</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3.5">
                            <label class="erp-label block mb-1.5 text-xs font-semibold">رقم المرجع / الشيك (اختياري)</label>
                            <input type="text" name="reference_number" class="erp-input font-numeric w-full" placeholder="رقم الشيك أو الحوالة البنكية...">
                        </div>

                        <div class="mb-3.5">
                            <label class="erp-label block mb-1.5 text-xs font-semibold">ملاحظات وبيان الصرف</label>
                            <textarea name="notes" class="erp-input w-full" rows="2" placeholder="بيان الصرف..."></textarea>
                        </div>
                    </div>

                    <div class="erp-modal-footer p-4 flex justify-end gap-2.5 bg-[var(--bg-surface-alt)] border-t border-[var(--border-light)]">
                        <button type="button" data-action="close-quick-pay" class="erp-btn erp-btn-secondary">إلغاء</button>
                        <button type="submit" class="erp-btn erp-btn-success">تأكيد وصرف السند</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal: Quick Add Installment for Supplier -->
        <div id="quickInstallmentModal" class="erp-modal-overlay">
            <div class="erp-modal-container max-w-lg">
                <div class="erp-modal-header">
                    <div class="erp-modal-title">
                        <x-icon name="calendar-plus" size="18" />
                        <span>جدولة قسط مستحق للمورد</span>
                    </div>
                    <button type="button" data-action="close-quick-installment" class="erp-modal-close">✕</button>
                </div>

                <form action="{{ route('supplier-installments.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="supplier_id" id="qinst_supplier_id">
                    <input type="hidden" name="plan_type" value="single">

                    <div class="erp-modal-body p-5">
                        <div class="p-3 mb-3.5 rounded-lg bg-sky-50/70 border border-sky-200">
                            <span class="text-xs text-sky-800">المورد: </span>
                            <strong id="qinst_supplier_name" class="text-sm font-bold text-[var(--text-main)]"></strong>
                        </div>

                        <div class="mb-3.5">
                            <label class="erp-label block mb-1.5 text-xs font-semibold">الفاتورة المرتبطة (اختياري)</label>
                            <select name="purchase_invoice_id" id="qinst_invoice_id" class="erp-input w-full">
                                <option value="">-- حساب عام للمورد --</option>
                            </select>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5 mb-3.5">
                            <div>
                                <label class="erp-label block mb-1.5 text-xs font-semibold">عنوان القسط *</label>
                                <input type="text" name="installment_title" class="erp-input w-full" placeholder="مثال: قسط مستحق شهر..." required>
                            </div>
                            <div>
                                <label class="erp-label block mb-1.5 text-xs font-semibold">مبلغ القسط *</label>
                                <input type="number" step="0.01" min="0.01" name="amount" class="erp-input font-numeric font-bold w-full" required placeholder="0.00">
                            </div>
                        </div>

                        <div class="mb-3.5">
                            <label class="erp-label block mb-1.5 text-xs font-semibold">تاريخ الاستحقاق *</label>
                            <input type="date" name="due_date" value="{{ date('Y-m-d', strtotime('+7 days')) }}" class="erp-input font-numeric w-full" required>
                        </div>

                        <div class="mb-3.5">
                            <label class="erp-label block mb-1.5 text-xs font-semibold">ملاحظات إضافية</label>
                            <textarea name="notes" class="erp-input w-full" rows="2" placeholder="ملاحظات وشروط الدفعة..."></textarea>
                        </div>
                    </div>

                    <div class="erp-modal-footer p-4 flex justify-end gap-2.5 bg-[var(--bg-surface-alt)] border-t border-[var(--border-light)]">
                        <button type="button" data-action="close-quick-installment" class="erp-btn erp-btn-secondary">إلغاء</button>
                        <button type="submit" class="erp-btn erp-btn-primary">حفظ القسط</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function handleQspInstallmentChange(select) {
            const opt = select.selectedOptions[0];
            const amountInput = document.getElementById('qsp_amount');
            const invSelect = document.getElementById('qsp_invoice_id');

            if (opt && opt.dataset.remaining) {
                if (amountInput) {
                    amountInput.value = opt.dataset.remaining;
                    amountInput.max = opt.dataset.remaining;
                }
                if (opt.dataset.invoiceId && invSelect) {
                    invSelect.value = opt.dataset.invoiceId;
                }
            } else {
                if (amountInput) amountInput.removeAttribute('max');
            }
        }

        function handleQspInvoiceChange(select) {
            const opt = select.selectedOptions[0];
            const amountInput = document.getElementById('qsp_amount');

            if (opt && opt.dataset.remaining) {
                if (amountInput && (!amountInput.value || Number(amountInput.value) <= 0)) {
                    amountInput.value = opt.dataset.remaining;
                }
                if (amountInput) {
                    amountInput.max = opt.dataset.remaining;
                }
            } else {
                if (amountInput) amountInput.removeAttribute('max');
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const payModal = document.getElementById('quickSupplierPaymentModal');
            const instModal = document.getElementById('quickInstallmentModal');

            // Handle Quick Pay Click on table row
            document.querySelectorAll('[data-action="quick-pay-supplier"]').forEach(btn => {
                btn.addEventListener('click', () => {
                    const supId = btn.dataset.supplierId;
                    const supName = btn.dataset.supplierName;
                    const supBalance = btn.dataset.supplierBalance;
                    const supCurrency = btn.dataset.supplierCurrency || 'EGP';

                    const form = document.getElementById('quickSupplierPaymentForm');
                    if (form) form.action = `/suppliers/${supId}/payments`;

                    document.getElementById('qsp_supplier_name').textContent = supName;
                    document.getElementById('qsp_supplier_balance').textContent = `${Number(supBalance).toLocaleString('ar-EG', {minimumFractionDigits: 2})} ${supCurrency}`;

                    const instSelect = document.getElementById('qsp_installment_id');
                    const invSelect = document.getElementById('qsp_invoice_id');
                    const amountInput = document.getElementById('qsp_amount');

                    instSelect.innerHTML = '<option value="">-- جاري التحميل... --</option>';
                    invSelect.innerHTML = '<option value="">-- جاري التحميل... --</option>';

                    if (Number(supBalance) > 0) {
                        amountInput.value = supBalance;
                    } else {
                        amountInput.value = '';
                    }

                    if (payModal) {
                        payModal.classList.add('open');
                        payModal.style.display = 'flex';
                    }

                    fetch(`/suppliers/${supId}/payables-data`)
                        .then(res => res.json())
                        .then(data => {
                            instSelect.innerHTML = '<option value="">-- بدون قسط (سداد عام) --</option>';
                            invSelect.innerHTML = '<option value="">-- بدون فاتورة (سداد عام) --</option>';

                            if (!data.success) return;

                            if (data.installments && data.installments.length > 0) {
                                data.installments.forEach(inst => {
                                    const opt = document.createElement('option');
                                    opt.value = inst.id;
                                    opt.dataset.remaining = inst.remaining_amount;
                                    opt.dataset.invoiceId = inst.purchase_invoice_id || '';
                                    opt.textContent = `${inst.installment_title} (متبقي ${inst.remaining_amount} ج.م - ${inst.status_label})`;
                                    instSelect.appendChild(opt);
                                });
                            }

                            if (data.invoices && data.invoices.length > 0) {
                                data.invoices.forEach(inv => {
                                    const opt = document.createElement('option');
                                    opt.value = inv.id;
                                    opt.dataset.remaining = inv.remaining_amount;
                                    opt.textContent = `فاتورة #${inv.invoice_number} (متبقي ${Number(inv.remaining_amount).toLocaleString('ar-EG', {minimumFractionDigits: 2})} ${inv.currency})`;
                                    invSelect.appendChild(opt);
                                });
                            }
                        })
                        .catch(e => {
                            console.error('Error fetching payables:', e);
                            instSelect.innerHTML = '<option value="">-- بدون قسط (سداد عام) --</option>';
                            invSelect.innerHTML = '<option value="">-- بدون فاتورة (سداد عام) --</option>';
                        });
                });
            });

            document.querySelectorAll('[data-action="close-quick-pay"]').forEach(btn => {
                btn.addEventListener('click', () => {
                    if (payModal) {
                        payModal.classList.remove('open');
                        payModal.style.display = 'none';
                    }
                });
            });

            // Handle Quick Add Installment Click on table row
            document.querySelectorAll('[data-action="add-installment-supplier"]').forEach(btn => {
                btn.addEventListener('click', () => {
                    const supId = btn.dataset.supplierId;
                    const supName = btn.dataset.supplierName;

                    document.getElementById('qinst_supplier_id').value = supId;
                    document.getElementById('qinst_supplier_name').textContent = supName;

                    const invSelect = document.getElementById('qinst_invoice_id');
                    invSelect.innerHTML = '<option value="">-- حساب عام للمورد --</option>';

                    if (instModal) {
                        instModal.classList.add('open');
                        instModal.style.display = 'flex';
                    }

                    fetch(`/suppliers/${supId}/payables-data`)
                        .then(res => res.json())
                        .then(data => {
                            if (data.success && data.invoices) {
                                data.invoices.forEach(inv => {
                                    const opt = document.createElement('option');
                                    opt.value = inv.id;
                                    opt.textContent = `فاتورة #${inv.invoice_number} (متبقي ${Number(inv.remaining_amount).toLocaleString('ar-EG', {minimumFractionDigits: 2})} ${inv.currency})`;
                                    invSelect.appendChild(opt);
                                });
                            }
                        })
                        .catch(e => console.error('Error fetching invoices:', e));
                });
            });

            document.querySelectorAll('[data-action="close-quick-installment"]').forEach(btn => {
                btn.addEventListener('click', () => {
                    if (instModal) {
                        instModal.classList.remove('open');
                        instModal.style.display = 'none';
                    }
                });
            });

            // Backdrop dismissal
            [payModal, instModal].forEach(m => {
                if (m) {
                    m.addEventListener('click', (e) => {
                        if (e.target === m) {
                            m.classList.remove('open');
                            m.style.display = 'none';
                        }
                    });
                }
            });
        });
    </script>
@endsection
