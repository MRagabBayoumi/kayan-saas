@extends('layouts.app')

@section('control_panel')
    <x-control-panel title="مكاتب وشركات الشحن والتوصيل" :breadcrumbs="['العملاء والموردون' => route('shipping-offices.index'), 'مكاتب الشحن' => route('shipping-offices.index')]">
        <x-slot:actions>
            <button type="button" data-action="open-add-shipping" class="erp-btn erp-btn-primary">
                <x-icon name="plus" size="16" />
                <span>إضافة مكتب شحن جديد</span>
            </button>
            <a href="{{ route('customers.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="users" size="16" />
                <span>دليل العملاء</span>
            </a>
            <a href="{{ route('suppliers.index') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="truck" size="16" />
                <span>الموردون ومصانع الصين</span>
            </a>
        </x-slot:actions>

        <x-slot:extra>
            <span class="erp-badge erp-badge-neutral font-numeric px-2.5 py-1 text-xs">
                <span>إجمالي مكاتب الشحن:</span>
                <strong>{{ $offices->count() }}</strong>
            </span>
        </x-slot:extra>
    </x-control-panel>
@endsection

@section('content')
    <div data-page="shipping-offices">
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

        <!-- Shipping Offices Grid -->
        <div class="shipping-grid">
            @forelse($offices as $office)
                <div class="shipping-card">
                    <div class="shipping-card-body">
                        <div class="shipping-card-header">
                            <div>
                                <h3 class="shipping-name">{{ $office->name }}</h3>
                                <div class="shipping-contact">
                                    {{ $office->contact_person ? 'المسؤول: ' . $office->contact_person : 'شركة شحن معتمدة' }}
                                </div>
                            </div>
                            <span class="erp-badge {{ $office->is_active ? 'erp-badge-neutral' : 'erp-badge-danger' }} text-xs">
                                {{ $office->is_active ? 'نشط' : 'معطل' }}
                            </span>
                        </div>

                        <div class="mb-2">
                            @if($office->shipping_type === 'international_china')
                                <span class="erp-badge erp-badge-primary text-xs">
                                    <x-icon name="navigation" size="12" />
                                    <span>شحن دولي وحاويات (الصين)</span>
                                </span>
                            @else
                                <span class="erp-badge erp-badge-info text-xs">
                                    <x-icon name="truck" size="12" />
                                    <span>شحن داخلي وتوزيع محافظات (مصر)</span>
                                </span>
                            @endif
                        </div>

                        <div class="space-y-1.5 text-xs text-[var(--text-secondary)] mb-3">
                            <div class="flex items-center gap-1.5">
                                <x-icon name="phone" size="14" class="text-[var(--text-muted)]" />
                                <a href="tel:{{ $office->phone }}" class="font-numeric text-[var(--text-main)] hover:text-[var(--primary)] direction-ltr inline-block">
                                    {{ $office->phone }}
                                </a>
                                @if($office->phone_alt)
                                    <span class="text-[var(--text-muted)] font-numeric direction-ltr">/ {{ $office->phone_alt }}</span>
                                @endif
                            </div>

                            @if($office->address)
                                <div class="flex items-center gap-1.5">
                                    <x-icon name="map-pin" size="14" class="text-[var(--text-muted)]" />
                                    <span class="truncate">{{ $office->address }}</span>
                                </div>
                            @endif
                        </div>

                        @if($office->rate_per_cbm_or_kg)
                            <div class="shipping-rate-box">
                                <span class="text-xs text-[var(--text-muted)] font-semibold">سعر الشحن التقديري:</span>
                                <span class="font-numeric font-bold text-sm text-[var(--text-main)]">
                                    {{ number_format($office->rate_per_cbm_or_kg, 2) }}
                                    <span class="text-xs font-normal text-[var(--text-muted)]">{{ $office->shipping_type === 'international_china' ? 'USD / CBM' : 'ج.م / طرد' }}</span>
                                </span>
                            </div>
                        @endif

                        @if($office->notes)
                            <p class="text-xs text-[var(--text-muted)] bg-[var(--bg-surface-alt)] p-2.5 rounded border border-[var(--border-light)] m-0 mt-2">
                                {{ $office->notes }}
                            </p>
                        @endif
                    </div>

                    <div class="shipping-card-footer">
                        <button 
                            type="button" 
                            data-action="open-edit-shipping"
                            data-shipping="{{ json_encode($office) }}"
                            class="erp-btn erp-btn-sm erp-btn-secondary"
                            title="تعديل">
                            <x-icon name="edit" size="14" />
                            <span>تعديل</span>
                        </button>

                        <form action="{{ route('shipping-offices.destroy', $office) }}" method="POST" data-confirm="هل أنت متأكد من حذف مكتب الشحن {{ $office->name }}؟" class="m-0">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="erp-btn erp-btn-sm erp-btn-danger" title="حذف">
                                <x-icon name="trash" size="14" />
                            </button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="col-span-full erp-card p-10 text-center text-[var(--text-muted)]">
                    <x-icon name="navigation" size="48" class="mb-3 inline-block text-[var(--text-muted)]" />
                    <h3 class="font-bold text-base mb-1">لا توجد مكاتب شحن مسجلة</h3>
                    <p class="text-xs mb-4">قم بإضافة شركات الشحن الدولي وخطوط التوزيع الداخلي لتنظيم إرسال وتوصيل بضائع العملاء.</p>
                    <button type="button" data-action="open-add-shipping" class="erp-btn erp-btn-primary">
                        <x-icon name="plus" size="14" />
                        <span>إضافة أول مكتب شحن</span>
                    </button>
                </div>
            @endforelse
        </div>

        <!-- Modal: Add Shipping Office -->
        <div id="addShippingModal" class="erp-modal-overlay">
            <div class="erp-modal-container max-w-lg">
                <div class="erp-modal-header">
                    <div class="erp-modal-title">
                        <x-icon name="navigation" size="18" />
                        <span>إضافة مكتب أو شركة شحن</span>
                    </div>
                    <button type="button" data-action="close-add-shipping" class="erp-modal-close" title="إغلاق">✕</button>
                </div>

                <form action="{{ route('shipping-offices.store') }}" method="POST">
                    @csrf
                    <div class="erp-modal-body p-5">
                        <div class="mb-3.5">
                            <label class="erp-label block mb-1.5 text-xs font-semibold">اسم مكتب أو شركة الشحن *</label>
                            <input type="text" name="name" class="erp-input w-full" placeholder="مثال: شركة النجم للشحن الدولي / مكتب البراق" required>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5 mb-3.5">
                            <div>
                                <label class="erp-label block mb-1.5 text-xs font-semibold">نوع نشاط الشحن *</label>
                                <select name="shipping_type" class="erp-input w-full" required>
                                    <option value="domestic_egypt">شحن داخلي محافظات (مصر)</option>
                                    <option value="international_china">شحن دولي بحري/جوي (الصين)</option>
                                </select>
                            </div>
                            <div>
                                <label class="erp-label block mb-1.5 text-xs font-semibold">المسؤول أو المندوب</label>
                                <input type="text" name="contact_person" class="erp-input w-full" placeholder="اسم مندوب الشحن">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5 mb-3.5">
                            <div>
                                <label class="erp-label block mb-1.5 text-xs font-semibold">رقم الهاتف الأساسي *</label>
                                <input type="text" name="phone" class="erp-input font-numeric w-full" placeholder="01xxxxxxxxx" required>
                            </div>
                            <div>
                                <label class="erp-label block mb-1.5 text-xs font-semibold">رقم هاتف إضافي</label>
                                <input type="text" name="phone_alt" class="erp-input font-numeric w-full" placeholder="هاتف بديل">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5 mb-3.5">
                            <div>
                                <label class="erp-label block mb-1.5 text-xs font-semibold">سعر الشحن التقديري (CBM/طرد)</label>
                                <input type="number" step="0.01" min="0" name="rate_per_cbm_or_kg" class="erp-input font-numeric w-full" placeholder="0.00">
                            </div>
                            <div>
                                <label class="erp-label block mb-1.5 text-xs font-semibold">العنوان ومقر المكتب</label>
                                <input type="text" name="address" class="erp-input w-full" placeholder="مقر استلام الطرود">
                            </div>
                        </div>

                        <div class="mb-3.5">
                            <label class="erp-label block mb-1.5 text-xs font-semibold">ملاحظات ومناطق التغطية</label>
                            <textarea name="notes" class="erp-input w-full" rows="2" placeholder="المحافظات المغطاة، مواعيد التحرك، إلخ..."></textarea>
                        </div>

                        <div>
                            <label class="flex items-center gap-2 cursor-pointer text-sm text-[var(--text-main)]">
                                <input type="checkbox" name="is_active" value="1" checked>
                                <span>مكتب شحن نشط</span>
                            </label>
                        </div>
                    </div>

                    <div class="erp-modal-footer p-4 flex justify-end gap-2.5 bg-[var(--bg-surface-alt)] border-t border-[var(--border-light)]">
                        <button type="button" data-action="close-add-shipping" class="erp-btn erp-btn-secondary">إلغاء</button>
                        <button type="submit" class="erp-btn erp-btn-primary">حفظ مكتب الشحن</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal: Edit Shipping Office -->
        <div id="editShippingModal" class="erp-modal-overlay">
            <div class="erp-modal-container max-w-lg">
                <div class="erp-modal-header">
                    <div class="erp-modal-title">
                        <x-icon name="edit" size="18" />
                        <span>تعديل بيانات مكتب الشحن</span>
                    </div>
                    <button type="button" data-action="close-edit-shipping" class="erp-modal-close" title="إغلاق">✕</button>
                </div>

                <form id="editShippingForm" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="erp-modal-body p-5">
                        <div class="mb-3.5">
                            <label class="erp-label block mb-1.5 text-xs font-semibold">اسم مكتب أو شركة الشحن *</label>
                            <input type="text" id="edit_ship_name" name="name" class="erp-input w-full" required>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5 mb-3.5">
                            <div>
                                <label class="erp-label block mb-1.5 text-xs font-semibold">نوع نشاط الشحن *</label>
                                <select id="edit_ship_shipping_type" name="shipping_type" class="erp-input w-full" required>
                                    <option value="domestic_egypt">شحن داخلي محافظات (مصر)</option>
                                    <option value="international_china">شحن دولي بحري/جوي (الصين)</option>
                                </select>
                            </div>
                            <div>
                                <label class="erp-label block mb-1.5 text-xs font-semibold">المسؤول أو المندوب</label>
                                <input type="text" id="edit_ship_contact_person" name="contact_person" class="erp-input w-full">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5 mb-3.5">
                            <div>
                                <label class="erp-label block mb-1.5 text-xs font-semibold">رقم الهاتف الأساسي *</label>
                                <input type="text" id="edit_ship_phone" name="phone" class="erp-input font-numeric w-full" required>
                            </div>
                            <div>
                                <label class="erp-label block mb-1.5 text-xs font-semibold">رقم هاتف إضافي</label>
                                <input type="text" id="edit_ship_phone_alt" name="phone_alt" class="erp-input font-numeric w-full">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5 mb-3.5">
                            <div>
                                <label class="erp-label block mb-1.5 text-xs font-semibold">سعر الشحن التقديري (CBM/طرد)</label>
                                <input type="number" step="0.01" min="0" id="edit_ship_rate_per_cbm_or_kg" name="rate_per_cbm_or_kg" class="erp-input font-numeric w-full">
                            </div>
                            <div>
                                <label class="erp-label block mb-1.5 text-xs font-semibold">العنوان ومقر المكتب</label>
                                <input type="text" id="edit_ship_address" name="address" class="erp-input w-full">
                            </div>
                        </div>

                        <div class="mb-3.5">
                            <label class="erp-label block mb-1.5 text-xs font-semibold">ملاحظات ومناطق التغطية</label>
                            <textarea id="edit_ship_notes" name="notes" class="erp-input w-full" rows="2"></textarea>
                        </div>

                        <div>
                            <label class="flex items-center gap-2 cursor-pointer text-sm text-[var(--text-main)]">
                                <input type="checkbox" id="edit_ship_is_active" name="is_active" value="1">
                                <span>مكتب شحن نشط</span>
                            </label>
                        </div>
                    </div>

                    <div class="erp-modal-footer p-4 flex justify-end gap-2.5 bg-[var(--bg-surface-alt)] border-t border-[var(--border-light)]">
                        <button type="button" data-action="close-edit-shipping" class="erp-btn erp-btn-secondary">إلغاء</button>
                        <button type="submit" class="erp-btn erp-btn-primary">حفظ التعديلات</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
