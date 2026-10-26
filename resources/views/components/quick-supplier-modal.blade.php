<!-- Quick Add Supplier Modal Dialog -->
<div id="quickSupplierModal" class="erp-modal-overlay" role="dialog" aria-modal="true" aria-labelledby="quickSupplierModalTitle">
    <div class="erp-modal-container max-w-xl">
        <!-- Header -->
        <div class="erp-modal-header flex items-center justify-between border-b border-[var(--border-color)] pb-3">
            <div class="erp-modal-title flex items-center gap-2 text-base font-bold text-[var(--text-main)]" id="quickSupplierModalTitle">
                <div class="w-8 h-8 rounded-lg bg-[var(--primary-soft)] text-[var(--primary)] flex items-center justify-center">
                    <x-icon name="truck" size="18" />
                </div>
                <div>
                    <div class="text-sm font-bold text-[var(--text-main)]">إضافة مورد / مصنع جديد سريع</div>
                    <div class="text-[11px] text-[var(--text-muted)] font-normal">تسجيل المورد واختياره مباشرة في فاتورة الشراء</div>
                </div>
            </div>
            <button type="button" id="closeQuickSupplierModalBtn" class="erp-modal-close text-[var(--text-muted)] hover:text-[var(--text-main)] cursor-pointer text-base leading-none p-1" aria-label="إغلاق">
                <x-icon name="x" size="18" />
            </button>
        </div>

        <!-- Form -->
        <form id="quickSupplierForm" method="POST" action="{{ route('suppliers.store') }}">
            @csrf
            <div class="erp-modal-body p-4 sm:p-5 space-y-3.5 max-h-[75vh] overflow-y-auto">
                <!-- Alert Box -->
                <div id="quickSupplierAlert" class="erp-alert erp-alert-danger hidden flex items-center gap-2 text-xs">
                    <x-icon name="alert-triangle" size="16" class="shrink-0" />
                    <span id="quickSupplierAlertMsg"></span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div class="erp-form-group mb-0">
                        <label for="quick_sup_name" class="erp-form-label text-xs font-semibold">
                            اسم المورد / المصنع <span class="text-[var(--danger)]">*</span>
                        </label>
                        <input 
                            type="text" 
                            id="quick_sup_name" 
                            name="name" 
                            class="erp-input text-xs font-semibold" 
                            placeholder="مثال: شركة الأمل للاستيراد والتجارة" 
                            required
                        >
                    </div>

                    <div class="erp-form-group mb-0">
                        <label for="quick_sup_type" class="erp-form-label text-xs font-semibold">
                            نوع المورد <span class="text-[var(--danger)]">*</span>
                        </label>
                        <select id="quick_sup_type" name="supplier_type" class="erp-input text-xs font-semibold" required>
                            <option value="local_supplier" selected>مورد محلي (مصر)</option>
                            <option value="china_factory">مصنع في الصين (استيراد)</option>
                            <option value="shipping_agent">وكيل شحن وتخليص</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div class="erp-form-group mb-0">
                        <label for="quick_sup_phone" class="erp-form-label text-xs font-semibold">رقم الهاتف / الواتساب</label>
                        <input 
                            type="text" 
                            id="quick_sup_phone" 
                            name="phone" 
                            class="erp-input text-xs font-numeric" 
                            placeholder="مثال: 01012345678 / +86..."
                        >
                    </div>

                    <div class="erp-form-group mb-0">
                        <label for="quick_sup_contact" class="erp-form-label text-xs font-semibold">المسؤول / جهة الاتصال</label>
                        <input 
                            type="text" 
                            id="quick_sup_contact" 
                            name="contact_person" 
                            class="erp-input text-xs" 
                            placeholder="مثال: أ. أحمد / Mr. Liu"
                        >
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div class="erp-form-group mb-0">
                        <label for="quick_sup_country" class="erp-form-label text-xs font-semibold">الدولة</label>
                        <input 
                            type="text" 
                            id="quick_sup_country" 
                            name="country" 
                            value="مصر" 
                            class="erp-input text-xs" 
                            placeholder="مصر / China"
                        >
                    </div>

                    <div class="erp-form-group mb-0">
                        <label for="quick_sup_city" class="erp-form-label text-xs font-semibold">المدينة / المحافظة</label>
                        <input 
                            type="text" 
                            id="quick_sup_city" 
                            name="city" 
                            class="erp-input text-xs" 
                            placeholder="مثال: القاهرة / Guangzhou"
                        >
                    </div>
                </div>

                <!-- China Specific Fields (shown only when china_factory is selected) -->
                <div id="quick_sup_china_fields" class="p-3.5 rounded-lg border border-[var(--border-light)] bg-[var(--bg-surface-alt)]/60 hidden">
                    <div class="text-xs font-semibold text-[var(--primary)] mb-2 flex items-center gap-1.5">
                        <x-icon name="globe" size="14" />
                        <span>بيانات خاصة بمصانع الصين والاستيراد</span>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div class="erp-form-group mb-0">
                            <label for="quick_sup_wechat_id" class="erp-form-label text-xs font-semibold">معرّف وي شات (WeChat ID)</label>
                            <input 
                                type="text" 
                                id="quick_sup_wechat_id" 
                                name="wechat_id" 
                                class="erp-input text-xs font-numeric" 
                                placeholder="WeChat ID لمصانع الصين"
                            >
                        </div>
                        <div class="erp-form-group mb-0">
                            <label for="quick_sup_currency" class="erp-form-label text-xs font-semibold">عملة الصين / عملة الحساب *</label>
                            <select id="quick_sup_currency" name="currency" class="erp-input text-xs">
                                <option value="EGP">الجنيه المصري (EGP ج.م)</option>
                                <option value="CNY" selected>اليوان الصيني (CNY ¥)</option>
                                <option value="USD">الدولار الأمريكي (USD $)</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div class="erp-form-group mb-0">
                        <label for="quick_sup_opening_balance" class="erp-form-label text-xs font-semibold">الرصيد الافتتاحي (ج.م)</label>
                        <input 
                            type="number" 
                            step="0.01" 
                            id="quick_sup_opening_balance" 
                            name="current_balance" 
                            value="0.00" 
                            class="erp-input text-xs font-numeric"
                        >
                    </div>

                    <div class="erp-form-group mb-0">
                        <label for="quick_sup_address" class="erp-form-label text-xs font-semibold">العنوان أو موقع المصنع</label>
                        <input 
                            type="text" 
                            id="quick_sup_address" 
                            name="address" 
                            class="erp-input text-xs" 
                            placeholder="العنوان أو اسم السوق والمحل"
                        >
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="erp-modal-footer flex items-center justify-between border-t border-[var(--border-color)] p-3.5 bg-[var(--bg-hover)]">
                <button type="button" id="cancelQuickSupplierBtn" class="erp-btn erp-btn-secondary text-xs">إلغاء</button>
                <button type="submit" id="saveQuickSupplierBtn" class="erp-btn erp-btn-primary text-xs flex items-center gap-1.5">
                    <x-icon name="check" size="14" />
                    <span>حفظ وتحديد المورد</span>
                </button>
            </div>
        </form>
    </div>
</div>
