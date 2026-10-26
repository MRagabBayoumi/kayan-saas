<div data-page="settings-print-templates" 
     data-initial-template="{{ json_encode($currentTemplate) }}" 
     data-initial-sample="{{ json_encode($sampleData) }}">

    <script type="application/json" id="printTemplateInitialData">
        {!! json_encode(['template' => $currentTemplate, 'sample' => $sampleData], JSON_UNESCAPED_UNICODE) !!}
    </script>

    @php
        $activeLogo = $currentTemplate->getSetting('general.custom_logo') ?: ($sampleData['company']['logo_url'] ?? null);
    @endphp

    <!-- Split View: Controls on Right, Live Simulator on Left -->
    <div class="print-designer-layout">

        <!-- Controls Column -->
        <div class="designer-controls-card">
            
            <!-- Document Type Selector -->
            <div class="designer-selector-group">
                <label class="block text-xs font-semibold mb-2">نوع المستند</label>
                <div class="designer-nav-pills">
                    @foreach($docTypeLabels as $typeKey => $typeLabel)
                        <a href="{{ route('settings.index', ['tab' => 'print_templates', 'doc_type' => $typeKey, 'paper_format' => $currentPaperFormat]) }}" 
                           class="designer-pill-btn {{ $currentDocType === $typeKey ? 'active' : '' }}">
                            <span>{{ $typeLabel }}</span>
                        </a>
                    @endforeach
                </div>
            </div>

            <!-- Paper Format Selector (A4, A5, Thermal 80mm) -->
            <div class="designer-selector-group">
                <label class="block text-xs font-semibold mb-2">مقاس الورق ونوع الطابعة</label>
                <div class="designer-format-pills">
                    <a href="{{ route('settings.index', ['tab' => 'print_templates', 'doc_type' => $currentDocType, 'paper_format' => 'a4']) }}" 
                       class="designer-format-btn {{ $currentPaperFormat === 'a4' ? 'active' : '' }}">
                        <span>A4 قياسي</span>
                        <span class="format-subtext">210 × 297 مم</span>
                    </a>
                    <a href="{{ route('settings.index', ['tab' => 'print_templates', 'doc_type' => $currentDocType, 'paper_format' => 'a5']) }}" 
                       class="designer-format-btn {{ $currentPaperFormat === 'a5' ? 'active' : '' }}">
                        <span>A5 قياسي</span>
                        <span class="format-subtext">148 × 210 مم</span>
                    </a>
                    <a href="{{ route('settings.index', ['tab' => 'print_templates', 'doc_type' => $currentDocType, 'paper_format' => 'thermal_80mm']) }}" 
                       class="designer-format-btn {{ $currentPaperFormat === 'thermal_80mm' ? 'active' : '' }}">
                        <span>حراري 80 مم</span>
                        <span class="format-subtext">شريط إيصالات</span>
                    </a>
                </div>
            </div>

            <!-- Template Select / Switcher -->
            @if($templatesList->count() > 1)
                <div class="designer-selector-group">
                    <label for="templateSelector" class="block text-xs font-semibold mb-1.5">القالب النشط</label>
                    <div class="flex gap-2">
                        <select id="templateSelector" class="erp-input flex-1 text-xs">
                            @foreach($templatesList as $t)
                                <option value="{{ $t->id }}" {{ $currentTemplate->id === $t->id ? 'selected' : '' }}>
                                    {{ $t->name }} {{ $t->is_default ? '(الافتراضي)' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            @endif

            <!-- Template Settings Form -->
            <form id="printTemplateForm" action="{{ $currentTemplate->id ? route('settings.print-templates.update', $currentTemplate->id) : '#' }}" method="POST">
                @csrf
                @method('PUT')

                <!-- Template Metadata Header -->
                <div class="p-4 border-b border-[var(--border-color)]">
                    <div class="mb-3">
                        <label class="block text-xs font-semibold mb-1">اسم القالب</label>
                        <input type="text" name="template_name" value="{{ $currentTemplate->name }}" class="erp-input text-xs" required />
                    </div>
                    <label class="inline-flex items-center gap-2 cursor-pointer text-xs font-medium">
                        <input type="checkbox" name="template_is_default" value="1" {{ $currentTemplate->is_default ? 'checked' : '' }} class="rounded border-[var(--border-color)]" />
                        <span>تعيين كقالب افتراضي لهذا المقاس ونوع المستند</span>
                    </label>
                </div>

                <!-- Accordion 1: Branding & Identity -->
                <div class="designer-accordion-section">
                    <button type="button" class="designer-accordion-header">
                        <span class="flex items-center gap-2">
                            <x-icon name="building-2" size="16" />
                            <span>الهوية والشعار وبيانات المنشأة</span>
                        </span>
                        <x-icon name="chevron-down" size="16" class="accordion-chevron transition-transform" />
                    </button>
                    <div class="designer-accordion-body">
                        <div class="designer-toggle-row">
                            <label for="sw_show_logo">إظهار شعار الشركة</label>
                            <label class="designer-switch">
                                <input type="checkbox" id="sw_show_logo" name="settings[general][show_logo]" value="1" {{ $currentTemplate->getSetting('general.show_logo') ? 'checked' : '' }}>
                                <span class="designer-slider"></span>
                            </label>
                        </div>

                        <!-- Logo Upload and Realtime Preview -->
                        <div>
                            <label class="block text-xs font-semibold mb-1">تغيير أو اختيار شعار مخصص</label>
                            <div class="designer-logo-upload-wrap">
                                <input type="file" id="template_logo_upload" accept="image/png,image/jpeg,image/webp,image/svg+xml" class="erp-input text-xs flex-1" />
                                <input type="hidden" name="settings[general][custom_logo]" id="input_custom_logo" value="{{ $currentTemplate->getSetting('general.custom_logo', '') }}">
                                <button type="button" id="btn_remove_logo" class="erp-btn erp-btn-secondary text-xs text-rose-600 {{ $currentTemplate->getSetting('general.custom_logo') ? '' : 'hidden' }}" title="إزالة الشعار المخصص والعودة لشعار النظام">
                                    <x-icon name="trash-2" size="14" />
                                </button>
                            </div>
                            <span class="text-[11px] text-muted mt-1 block">يدعم صيغ PNG, JPG, WebP أو SVG للمعاينة المباشرة.</span>
                        </div>

                        <div class="grid grid-cols-2 gap-2 text-xs">
                            <div>
                                <label class="block mb-1 font-semibold">حجم الشعار</label>
                                <select name="settings[general][logo_size]" class="erp-input text-xs">
                                    <option value="sm" {{ $currentTemplate->getSetting('general.logo_size') === 'sm' ? 'selected' : '' }}>صغير (25-35 مم)</option>
                                    <option value="md" {{ $currentTemplate->getSetting('general.logo_size') === 'md' ? 'selected' : '' }}>متوسط (40-50 مم)</option>
                                    <option value="lg" {{ $currentTemplate->getSetting('general.logo_size') === 'lg' ? 'selected' : '' }}>كبير (60-70 مم)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block mb-1 font-semibold">حجم الخط العام</label>
                                <select name="settings[general][font_size]" class="erp-input text-xs">
                                    <option value="sm" {{ $currentTemplate->getSetting('general.font_size') === 'sm' ? 'selected' : '' }}>صغير (Compact)</option>
                                    <option value="md" {{ $currentTemplate->getSetting('general.font_size') === 'md' ? 'selected' : '' }}>متوسط (Normal)</option>
                                    <option value="lg" {{ $currentTemplate->getSetting('general.font_size') === 'lg' ? 'selected' : '' }}>كبير (Large)</option>
                                </select>
                            </div>
                        </div>
                        <div class="designer-toggle-row">
                            <label for="sw_show_company_name">إظهار اسم الشركة</label>
                            <label class="designer-switch">
                                <input type="checkbox" id="sw_show_company_name" name="settings[general][show_company_name]" value="1" {{ $currentTemplate->getSetting('general.show_company_name') ? 'checked' : '' }}>
                                <span class="designer-slider"></span>
                            </label>
                        </div>
                        <div class="designer-toggle-row">
                            <label for="sw_show_company_phone">إظهار رقم الهاتف</label>
                            <label class="designer-switch">
                                <input type="checkbox" id="sw_show_company_phone" name="settings[general][show_company_phone]" value="1" {{ $currentTemplate->getSetting('general.show_company_phone') ? 'checked' : '' }}>
                                <span class="designer-slider"></span>
                            </label>
                        </div>
                        <div class="designer-toggle-row">
                            <label for="sw_show_company_address">إظهار العنوان والمقر</label>
                            <label class="designer-switch">
                                <input type="checkbox" id="sw_show_company_address" name="settings[general][show_company_address]" value="1" {{ $currentTemplate->getSetting('general.show_company_address') ? 'checked' : '' }}>
                                <span class="designer-slider"></span>
                            </label>
                        </div>
                        <div>
                            <label class="block text-xs mb-1 font-semibold">عنوان المستند المخصص</label>
                            <input type="text" name="settings[general][custom_title]" value="{{ $currentTemplate->getSetting('general.custom_title') }}" class="erp-input text-xs" />
                        </div>
                        <div>
                            <label class="block text-xs mb-1 font-semibold">العنوان الفرعي</label>
                            <input type="text" name="settings[general][custom_subtitle]" value="{{ $currentTemplate->getSetting('general.custom_subtitle') }}" class="erp-input text-xs" />
                        </div>
                        <div>
                            <label class="block text-xs mb-1 font-semibold">اللون الرئيسي المميز للقالب</label>
                            <div class="designer-color-input-group">
                                <input type="color" id="template_color_picker" value="{{ $currentTemplate->getSetting('general.primary_color', '#1e293b') }}" class="designer-color-picker" />
                                <input type="text" name="settings[general][primary_color]" id="template_primary_color_input" value="{{ $currentTemplate->getSetting('general.primary_color', '#1e293b') }}" class="erp-input text-xs font-mono flex-1" placeholder="#1e293b" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Accordion 2: Document Meta & Barcode -->
                <div class="designer-accordion-section">
                    <button type="button" class="designer-accordion-header">
                        <span class="flex items-center gap-2">
                            <x-icon name="file-text" size="16" />
                            <span>بيانات المستند والباركود</span>
                        </span>
                        <x-icon name="chevron-down" size="16" class="accordion-chevron transition-transform" />
                    </button>
                    <div class="designer-accordion-body">
                        <div class="designer-toggle-row">
                            <label for="sw_doc_num">إظهار رقم المستند / الفاتورة</label>
                            <label class="designer-switch">
                                <input type="checkbox" id="sw_doc_num" name="settings[document_meta][show_doc_number]" value="1" {{ $currentTemplate->getSetting('document_meta.show_doc_number') ? 'checked' : '' }}>
                                <span class="designer-slider"></span>
                            </label>
                        </div>
                        <div class="designer-toggle-row">
                            <label for="sw_doc_date">إظهار تاريخ التحرير</label>
                            <label class="designer-switch">
                                <input type="checkbox" id="sw_doc_date" name="settings[document_meta][show_doc_date]" value="1" {{ $currentTemplate->getSetting('document_meta.show_doc_date') ? 'checked' : '' }}>
                                <span class="designer-slider"></span>
                            </label>
                        </div>
                        <div class="designer-toggle-row">
                            <label for="sw_doc_due">إظهار تاريخ الاستحقاق</label>
                            <label class="designer-switch">
                                <input type="checkbox" id="sw_doc_due" name="settings[document_meta][show_due_date]" value="1" {{ $currentTemplate->getSetting('document_meta.show_due_date') ? 'checked' : '' }}>
                                <span class="designer-slider"></span>
                            </label>
                        </div>
                        <div class="designer-toggle-row">
                            <label for="sw_doc_wh">إظهار المستودع / الفرع</label>
                            <label class="designer-switch">
                                <input type="checkbox" id="sw_doc_wh" name="settings[document_meta][show_warehouse]" value="1" {{ $currentTemplate->getSetting('document_meta.show_warehouse') ? 'checked' : '' }}>
                                <span class="designer-slider"></span>
                            </label>
                        </div>
                        <div class="designer-toggle-row">
                            <label for="sw_doc_badge">إظهار شارة الحالة</label>
                            <label class="designer-switch">
                                <input type="checkbox" id="sw_doc_badge" name="settings[document_meta][show_status_badge]" value="1" {{ $currentTemplate->getSetting('document_meta.show_status_badge') ? 'checked' : '' }}>
                                <span class="designer-slider"></span>
                            </label>
                        </div>
                        <div class="designer-toggle-row">
                            <label for="sw_doc_barcode">إظهار باركود المستند (Code 128)</label>
                            <label class="designer-switch">
                                <input type="checkbox" id="sw_doc_barcode" name="settings[document_meta][show_barcode]" value="1" {{ $currentTemplate->getSetting('document_meta.show_barcode') ? 'checked' : '' }}>
                                <span class="designer-slider"></span>
                            </label>
                        </div>
                        <div class="designer-toggle-row">
                            <label for="sw_doc_qr">إظهار رمز الاستجابة السريعة (QR Code)</label>
                            <label class="designer-switch">
                                <input type="checkbox" id="sw_doc_qr" name="settings[document_meta][show_qr_code]" value="1" {{ $currentTemplate->getSetting('document_meta.show_qr_code') ? 'checked' : '' }}>
                                <span class="designer-slider"></span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Accordion 3: Counterparty Info -->
                <div class="designer-accordion-section">
                    <button type="button" class="designer-accordion-header">
                        <span class="flex items-center gap-2">
                            <x-icon name="users" size="16" />
                            <span>بيانات الطرف المقابل (العميل / المورد)</span>
                        </span>
                        <x-icon name="chevron-down" size="16" class="accordion-chevron transition-transform" />
                    </button>
                    <div class="designer-accordion-body">
                        <div>
                            <label class="block text-xs mb-1 font-semibold">مسمى الطرف (العميل / المورد / الجهة)</label>
                            <input type="text" name="settings[party_meta][party_type_label]" value="{{ $currentTemplate->getSetting('party_meta.party_type_label') }}" class="erp-input text-xs" />
                        </div>
                        <div class="designer-toggle-row">
                            <label for="sw_party_name">إظهار اسم الطرف</label>
                            <label class="designer-switch">
                                <input type="checkbox" id="sw_party_name" name="settings[party_meta][show_party_name]" value="1" {{ $currentTemplate->getSetting('party_meta.show_party_name') ? 'checked' : '' }}>
                                <span class="designer-slider"></span>
                            </label>
                        </div>
                        <div class="designer-toggle-row">
                            <label for="sw_party_phone">إظهار الهاتف والتواصل</label>
                            <label class="designer-switch">
                                <input type="checkbox" id="sw_party_phone" name="settings[party_meta][show_party_phone]" value="1" {{ $currentTemplate->getSetting('party_meta.show_party_phone') ? 'checked' : '' }}>
                                <span class="designer-slider"></span>
                            </label>
                        </div>
                        <div class="designer-toggle-row">
                            <label for="sw_party_address">إظهار العنوان</label>
                            <label class="designer-switch">
                                <input type="checkbox" id="sw_party_address" name="settings[party_meta][show_party_address]" value="1" {{ $currentTemplate->getSetting('party_meta.show_party_address') ? 'checked' : '' }}>
                                <span class="designer-slider"></span>
                            </label>
                        </div>
                        <div class="designer-toggle-row">
                            <label for="sw_party_tax">إظهار الرقم الضريبي / السجل التجاري</label>
                            <label class="designer-switch">
                                <input type="checkbox" id="sw_party_tax" name="settings[party_meta][show_party_tax_no]" value="1" {{ $currentTemplate->getSetting('party_meta.show_party_tax_no') ? 'checked' : '' }}>
                                <span class="designer-slider"></span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Accordion 4: Table Columns -->
                <div class="designer-accordion-section">
                    <button type="button" class="designer-accordion-header">
                        <span class="flex items-center gap-2">
                            <x-icon name="table" size="16" />
                            <span>أعمدة جدول البنود والأسعار</span>
                        </span>
                        <x-icon name="chevron-down" size="16" class="accordion-chevron transition-transform" />
                    </button>
                    <div class="designer-accordion-body">
                        <div>
                            <label class="block text-xs mb-1 font-semibold">كثافة الجدول والمسافات</label>
                            <select name="settings[table_columns][density]" class="erp-input text-xs">
                                <option value="comfortable" {{ $currentTemplate->getSetting('table_columns.density') === 'comfortable' ? 'selected' : '' }}>مريح (Comfortable)</option>
                                <option value="compact" {{ $currentTemplate->getSetting('table_columns.density') === 'compact' ? 'selected' : '' }}>مضغوط (Compact - للأوراق الصغيرة والحرارية)</option>
                            </select>
                        </div>
                        <div class="designer-toggle-row">
                            <label for="sw_col_idx">مسلسل (#)</label>
                            <label class="designer-switch">
                                <input type="checkbox" id="sw_col_idx" name="settings[table_columns][show_index]" value="1" {{ $currentTemplate->getSetting('table_columns.show_index') ? 'checked' : '' }}>
                                <span class="designer-slider"></span>
                            </label>
                        </div>
                        <div class="designer-toggle-row">
                            <label for="sw_col_sku">كود الصنف (SKU)</label>
                            <label class="designer-switch">
                                <input type="checkbox" id="sw_col_sku" name="settings[table_columns][show_sku]" value="1" {{ $currentTemplate->getSetting('table_columns.show_sku') ? 'checked' : '' }}>
                                <span class="designer-slider"></span>
                            </label>
                        </div>
                        <div class="designer-toggle-row">
                            <label for="sw_col_barcode">باركود الصنف</label>
                            <label class="designer-switch">
                                <input type="checkbox" id="sw_col_barcode" name="settings[table_columns][show_barcode]" value="1" {{ $currentTemplate->getSetting('table_columns.show_barcode') ? 'checked' : '' }}>
                                <span class="designer-slider"></span>
                            </label>
                        </div>
                        <div class="designer-toggle-row">
                            <label for="sw_col_unit">الوحدة</label>
                            <label class="designer-switch">
                                <input type="checkbox" id="sw_col_unit" name="settings[table_columns][show_unit]" value="1" {{ $currentTemplate->getSetting('table_columns.show_unit') ? 'checked' : '' }}>
                                <span class="designer-slider"></span>
                            </label>
                        </div>
                        <div class="designer-toggle-row">
                            <label for="sw_col_wh">المخزن</label>
                            <label class="designer-switch">
                                <input type="checkbox" id="sw_col_wh" name="settings[table_columns][show_warehouse]" value="1" {{ $currentTemplate->getSetting('table_columns.show_warehouse', true) ? 'checked' : '' }}>
                                <span class="designer-slider"></span>
                            </label>
                        </div>
                        <div class="designer-toggle-row">
                            <label for="sw_col_price">سعر الوحدة</label>
                            <label class="designer-switch">
                                <input type="checkbox" id="sw_col_price" name="settings[table_columns][show_price]" value="1" {{ $currentTemplate->getSetting('table_columns.show_price') ? 'checked' : '' }}>
                                <span class="designer-slider"></span>
                            </label>
                        </div>
                        <div class="designer-toggle-row">
                            <label for="sw_col_discount">الخصم</label>
                            <label class="designer-switch">
                                <input type="checkbox" id="sw_col_discount" name="settings[table_columns][show_discount]" value="1" {{ $currentTemplate->getSetting('table_columns.show_discount') ? 'checked' : '' }}>
                                <span class="designer-slider"></span>
                            </label>
                        </div>
                        <div class="designer-toggle-row">
                            <label for="sw_col_tax">الضريبة</label>
                            <label class="designer-switch">
                                <input type="checkbox" id="sw_col_tax" name="settings[table_columns][show_tax]" value="1" {{ $currentTemplate->getSetting('table_columns.show_tax') ? 'checked' : '' }}>
                                <span class="designer-slider"></span>
                            </label>
                        </div>
                        <div class="designer-toggle-row">
                            <label for="sw_col_total">الإجمالي</label>
                            <label class="designer-switch">
                                <input type="checkbox" id="sw_col_total" name="settings[table_columns][show_total]" value="1" {{ $currentTemplate->getSetting('table_columns.show_total') ? 'checked' : '' }}>
                                <span class="designer-slider"></span>
                            </label>
                        </div>
                        <div class="designer-toggle-row">
                            <label for="sw_col_notes">ملاحظات البند</label>
                            <label class="designer-switch">
                                <input type="checkbox" id="sw_col_notes" name="settings[table_columns][show_notes]" value="1" {{ $currentTemplate->getSetting('table_columns.show_notes') ? 'checked' : '' }}>
                                <span class="designer-slider"></span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Accordion 5: Financial Summary -->
                <div class="designer-accordion-section">
                    <button type="button" class="designer-accordion-header">
                        <span class="flex items-center gap-2">
                            <x-icon name="dollar-sign" size="16" />
                            <span>الملخص المالي ومجاميع الفاتورة</span>
                        </span>
                        <x-icon name="chevron-down" size="16" class="accordion-chevron transition-transform" />
                    </button>
                    <div class="designer-accordion-body">
                        <div class="designer-toggle-row">
                            <label for="sw_fin_subtotal">المجموع الفرعي</label>
                            <label class="designer-switch">
                                <input type="checkbox" id="sw_fin_subtotal" name="settings[financial_summary][show_subtotal]" value="1" {{ $currentTemplate->getSetting('financial_summary.show_subtotal') ? 'checked' : '' }}>
                                <span class="designer-slider"></span>
                            </label>
                        </div>
                        <div class="designer-toggle-row">
                            <label for="sw_fin_discount">الخصم الإجمالي</label>
                            <label class="designer-switch">
                                <input type="checkbox" id="sw_fin_discount" name="settings[financial_summary][show_discount]" value="1" {{ $currentTemplate->getSetting('financial_summary.show_discount') ? 'checked' : '' }}>
                                <span class="designer-slider"></span>
                            </label>
                        </div>
                        <div class="designer-toggle-row">
                            <label for="sw_fin_shipping">تكلفة الشحن والنقل</label>
                            <label class="designer-switch">
                                <input type="checkbox" id="sw_fin_shipping" name="settings[financial_summary][show_shipping]" value="1" {{ $currentTemplate->getSetting('financial_summary.show_shipping') ? 'checked' : '' }}>
                                <span class="designer-slider"></span>
                            </label>
                        </div>
                        <div class="designer-toggle-row">
                            <label for="sw_fin_extra">مصاريف إضافية (خاص بالمشتريات)</label>
                            <label class="designer-switch">
                                <input type="checkbox" id="sw_fin_extra" name="settings[financial_summary][show_extra_expenses]" value="1" {{ $currentTemplate->getSetting('financial_summary.show_extra_expenses') ? 'checked' : '' }}>
                                <span class="designer-slider"></span>
                            </label>
                        </div>
                        <div class="designer-toggle-row">
                            <label for="sw_fin_tax">الضريبة المضافة</label>
                            <label class="designer-switch">
                                <input type="checkbox" id="sw_fin_tax" name="settings[financial_summary][show_tax]" value="1" {{ $currentTemplate->getSetting('financial_summary.show_tax') ? 'checked' : '' }}>
                                <span class="designer-slider"></span>
                            </label>
                        </div>
                        <div class="designer-toggle-row">
                            <label for="sw_fin_net">الصافي الإجمالي النهائي</label>
                            <label class="designer-switch">
                                <input type="checkbox" id="sw_fin_net" name="settings[financial_summary][show_net_total]" value="1" {{ $currentTemplate->getSetting('financial_summary.show_net_total') ? 'checked' : '' }}>
                                <span class="designer-slider"></span>
                            </label>
                        </div>
                        <div class="designer-toggle-row">
                            <label for="sw_fin_paid">المدفوع نقداً</label>
                            <label class="designer-switch">
                                <input type="checkbox" id="sw_fin_paid" name="settings[financial_summary][show_paid]" value="1" {{ $currentTemplate->getSetting('financial_summary.show_paid') ? 'checked' : '' }}>
                                <span class="designer-slider"></span>
                            </label>
                        </div>
                        <div class="designer-toggle-row">
                            <label for="sw_fin_remaining">المتبقي / الآجل</label>
                            <label class="designer-switch">
                                <input type="checkbox" id="sw_fin_remaining" name="settings[financial_summary][show_remaining]" value="1" {{ $currentTemplate->getSetting('financial_summary.show_remaining') ? 'checked' : '' }}>
                                <span class="designer-slider"></span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Accordion 6: Notes & Terms -->
                <div class="designer-accordion-section">
                    <button type="button" class="designer-accordion-header">
                        <span class="flex items-center gap-2">
                            <x-icon name="help-circle" size="16" />
                            <span>الملاحظات والشروط والأحكام</span>
                        </span>
                        <x-icon name="chevron-down" size="16" class="accordion-chevron transition-transform" />
                    </button>
                    <div class="designer-accordion-body">
                        <div class="designer-toggle-row">
                            <label for="sw_notes">إظهار صندوق الملاحظات</label>
                            <label class="designer-switch">
                                <input type="checkbox" id="sw_notes" name="settings[notes_and_terms][show_notes]" value="1" {{ $currentTemplate->getSetting('notes_and_terms.show_notes') ? 'checked' : '' }}>
                                <span class="designer-slider"></span>
                            </label>
                        </div>
                        <div>
                            <label class="block text-xs mb-1 font-semibold">نص الملاحظة الافتراضية</label>
                            <textarea name="settings[notes_and_terms][notes_text]" rows="2" class="erp-input text-xs">{{ $currentTemplate->getSetting('notes_and_terms.notes_text') }}</textarea>
                        </div>
                        <div class="designer-toggle-row">
                            <label for="sw_terms">إظهار الشروط والأحكام القانونية</label>
                            <label class="designer-switch">
                                <input type="checkbox" id="sw_terms" name="settings[notes_and_terms][show_terms]" value="1" {{ $currentTemplate->getSetting('notes_and_terms.show_terms') ? 'checked' : '' }}>
                                <span class="designer-slider"></span>
                            </label>
                        </div>
                        <div>
                            <label class="block text-xs mb-1 font-semibold">نص الشروط والأحكام</label>
                            <textarea name="settings[notes_and_terms][terms_text]" rows="3" class="erp-input text-xs">{{ $currentTemplate->getSetting('notes_and_terms.terms_text') }}</textarea>
                        </div>
                    </div>
                </div>

                <!-- Accordion 7: Signatures & Stamp -->
                <div class="designer-accordion-section">
                    <button type="button" class="designer-accordion-header">
                        <span class="flex items-center gap-2">
                            <x-icon name="check-circle-2" size="16" />
                            <span>التوقيعات والختم الإداري</span>
                        </span>
                        <x-icon name="chevron-down" size="16" class="accordion-chevron transition-transform" />
                    </button>
                    <div class="designer-accordion-body">
                        <div class="designer-toggle-row">
                            <label for="sw_sigs">إظهار خانات التوقيعات</label>
                            <label class="designer-switch">
                                <input type="checkbox" id="sw_sigs" name="settings[signatures][show_signatures]" value="1" {{ $currentTemplate->getSetting('signatures.show_signatures') ? 'checked' : '' }}>
                                <span class="designer-slider"></span>
                            </label>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-xs mb-1 font-semibold">مسمى الطرف الأول</label>
                                <input type="text" name="settings[signatures][first_party_title]" value="{{ $currentTemplate->getSetting('signatures.first_party_title') }}" class="erp-input text-xs" />
                            </div>
                            <div>
                                <label class="block text-xs mb-1 font-semibold">مسمى الطرف الثاني</label>
                                <input type="text" name="settings[signatures][second_party_title]" value="{{ $currentTemplate->getSetting('signatures.second_party_title') }}" class="erp-input text-xs" />
                            </div>
                        </div>
                        <div class="designer-toggle-row">
                            <label for="sw_stamp">إظهار موضع ختم المنشأة</label>
                            <label class="designer-switch">
                                <input type="checkbox" id="sw_stamp" name="settings[signatures][show_stamp_box]" value="1" {{ $currentTemplate->getSetting('signatures.show_stamp_box') ? 'checked' : '' }}>
                                <span class="designer-slider"></span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="p-4 bg-[var(--surface-muted)] border-t border-[var(--border-color)] flex items-center justify-between">
                    <button type="submit" class="erp-btn erp-btn-primary">
                        <x-icon name="save" size="16" />
                        <span>حفظ إعدادات القالب</span>
                    </button>

                    <button type="button" id="btnResetTemplate" data-reset-url="{{ route('settings.print-templates.reset', $currentTemplate->id) }}" class="erp-btn erp-btn-secondary text-xs">
                        <x-icon name="rotate-ccw" size="14" />
                        <span>استعادة الافتراضي</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Live Preview Simulator Column -->
        <div class="designer-preview-column">
            <div class="designer-preview-toolbar">
                <div class="flex items-center gap-2">
                    <span class="inline-block w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span class="text-xs font-semibold">معاينة تفاعلية حية (Live Preview)</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="erp-badge erp-badge-neutral text-xs">
                        {{ $currentPaperFormat === 'a4' ? 'ورق A4' : ($currentPaperFormat === 'a5' ? 'ورق A5' : 'حراري 80 مم') }}
                    </span>
                </div>
            </div>

            <!-- Paper Viewport Container -->
            <div class="preview-viewport-container">
                <div id="paperMockupSheet" class="paper-sheet" data-format="{{ $currentPaperFormat }}" data-font-size="{{ $currentTemplate->getSetting('general.font_size', 'md') }}">
                    <!-- Sample Watermark Ribbon -->
                    <div class="paper-watermark-ribbon">
                        {{ $sampleData['watermark'] ?? 'معاينة تجريبية' }}
                    </div>

                    @if($currentPaperFormat === 'thermal_80mm')
                        <!-- =================================================================
                             Thermal 80mm Cashier Mockup
                             ================================================================= -->
                        <div class="thermal-receipt-mockup">
                            <div class="doc-mockup-header">
                                <div class="preview-header-brand">
                                    <div class="preview-logo doc-mockup-logo-{{ $currentTemplate->getSetting('general.logo_size', 'sm') }} mb-1 {{ $currentTemplate->getSetting('general.show_logo') ? '' : 'template-hidden' }}">
                                        @if($activeLogo)
                                            <img src="{{ $activeLogo }}" alt="Logo" class="preview-logo-img" />
                                        @else
                                            <div class="preview-logo-placeholder">
                                                LOGO
                                            </div>
                                        @endif
                                    </div>
                                    <div class="preview-company-name font-bold text-sm text-slate-800 {{ $currentTemplate->getSetting('general.show_company_name') ? '' : 'template-hidden' }}">
                                        {{ $sampleData['company']['name'] ?? 'شركة كيان للتجارة والتوزيع' }}
                                    </div>
                                    <div class="preview-company-phone text-xs text-slate-600 {{ $currentTemplate->getSetting('general.show_company_phone') ? '' : 'template-hidden' }}">
                                        هاتف: {{ $sampleData['company']['phone'] ?? '01012345678' }}
                                    </div>
                                    <div class="preview-company-address text-[11px] text-slate-500 {{ $currentTemplate->getSetting('general.show_company_address') ? '' : 'template-hidden' }}">
                                        {{ $sampleData['company']['address'] ?? 'المنصورة، مصر' }}
                                    </div>
                                </div>

                                <div class="preview-header-doc text-center mt-2">
                                    <div class="preview-doc-title doc-mockup-title">
                                        {{ $currentTemplate->getSetting('general.custom_title', $sampleData['document']['type_label']) }}
                                    </div>
                                    <div class="preview-doc-subtitle text-xs text-slate-500 {{ $currentTemplate->getSetting('general.custom_subtitle') ? '' : 'template-hidden' }}">
                                        {{ $currentTemplate->getSetting('general.custom_subtitle', '') }}
                                    </div>
                                </div>
                            </div>

                            <div class="doc-mockup-meta-grid">
                                <div>
                                    <span class="preview-doc-number font-semibold text-slate-800 ml-2 {{ $currentTemplate->getSetting('document_meta.show_doc_number') ? '' : 'template-hidden' }}">
                                        رقم: {{ $sampleData['document']['number'] ?? 'INV-001' }}
                                    </span>
                                    <span class="preview-doc-date text-slate-600 ml-2 {{ $currentTemplate->getSetting('document_meta.show_doc_date') ? '' : 'template-hidden' }}">
                                        {{ $sampleData['document']['date'] ?? '2026-10-14' }}
                                    </span>
                                    <span class="preview-doc-due-date text-slate-600 ml-2 {{ $currentTemplate->getSetting('document_meta.show_due_date') ? '' : 'template-hidden' }}">
                                        استحقاق: {{ $sampleData['document']['due_date'] ?? '2026-10-21' }}
                                    </span>
                                </div>
                                <div>
                                    <span class="preview-party-label font-bold text-xs text-slate-700">
                                        {{ $currentTemplate->getSetting('party_meta.party_type_label', 'الطرف') }}:
                                    </span>
                                    <span class="preview-party-name font-semibold text-slate-800 {{ $currentTemplate->getSetting('party_meta.show_party_name') ? '' : 'template-hidden' }}">
                                        {{ $sampleData['party']['name'] ?? 'محل الأناقة' }}
                                    </span>
                                </div>
                                <div class="preview-party-phone-row {{ $currentTemplate->getSetting('party_meta.show_party_phone') ? '' : 'template-hidden' }}">
                                    <span class="text-xs text-slate-600">هاتف: </span>
                                    <span class="preview-party-phone text-xs font-semibold">{{ $sampleData['party']['phone'] ?? '01123456789' }}</span>
                                </div>
                                <div class="preview-party-tax-row {{ $currentTemplate->getSetting('party_meta.show_party_tax_no') ? '' : 'template-hidden' }}">
                                    <span class="text-xs text-slate-600">ضريبي: </span>
                                    <span class="preview-party-tax-no text-xs font-semibold">{{ $sampleData['party']['tax_no'] ?? '123-456-789' }}</span>
                                </div>
                                <div class="preview-doc-warehouse-row {{ $currentTemplate->getSetting('document_meta.show_warehouse') ? '' : 'template-hidden' }}">
                                    <span class="text-xs text-slate-600">المخزن: </span>
                                    <span class="preview-doc-warehouse text-xs font-semibold">{{ $sampleData['document']['warehouse'] ?? 'الرئيسي' }}</span>
                                </div>
                            </div>

                            <!-- Barcode & QR in Thermal -->
                            <div class="text-center my-2">
                                <div class="preview-doc-barcode {{ $currentTemplate->getSetting('document_meta.show_barcode') ? '' : 'template-hidden' }}">
                                    <div class="preview-barcode-bars">
                                        <span class="b-med"></span><span class="b-gap-1"></span><span class="b-wide"></span><span class="b-gap-2"></span><span class="b-thin"></span><span class="b-gap-2"></span><span class="b-xwide"></span><span class="b-gap-1"></span><span class="b-med"></span><span class="b-gap-1"></span><span class="b-wide"></span><span class="b-med"></span><span class="b-gap-1"></span><span class="b-med"></span>
                                    </div>
                                    <span class="text-[10px] font-mono mt-0.5">{{ $sampleData['document']['barcode'] ?? 'INV-001' }}</span>
                                </div>

                                <div class="preview-doc-qr {{ $currentTemplate->getSetting('document_meta.show_qr_code') ? '' : 'template-hidden' }}">
                                    <div class="preview-qr-box mx-auto">
                                        <div class="q-dot"></div><div class="q-dot"></div><div class="q-space"></div><div class="q-dot"></div>
                                        <div class="q-dot"></div><div class="q-space"></div><div class="q-dot"></div><div class="q-space"></div>
                                        <div class="q-space"></div><div class="q-dot"></div><div class="q-space"></div><div class="q-dot"></div>
                                        <div class="q-dot"></div><div class="q-dot"></div><div class="q-dot"></div><div class="q-dot"></div>
                                    </div>
                                </div>
                            </div>

                            @if(isset($sampleData['items']))
                                <table class="doc-mockup-table {{ $currentTemplate->getSetting('table_columns.density') === 'compact' ? 'density-compact' : '' }}">
                                    <thead>
                                        <tr>
                                            <th class="col-index {{ $currentTemplate->getSetting('table_columns.show_index') ? '' : 'template-hidden' }}">#</th>
                                            <th class="col-sku {{ $currentTemplate->getSetting('table_columns.show_sku') ? '' : 'template-hidden' }}">كود</th>
                                            <th class="col-name text-right">الصنف</th>
                                            <th class="col-qty text-center">الكمية</th>
                                            <th class="col-unit {{ $currentTemplate->getSetting('table_columns.show_unit') ? '' : 'template-hidden' }}">الوحدة</th>
                                            <th class="col-price text-left {{ $currentTemplate->getSetting('table_columns.show_price') ? '' : 'template-hidden' }}">السعر</th>
                                            <th class="col-discount text-left {{ $currentTemplate->getSetting('table_columns.show_discount') ? '' : 'template-hidden' }}">الخصم</th>
                                            <th class="col-total text-left {{ $currentTemplate->getSetting('table_columns.show_total') ? '' : 'template-hidden' }}">الإجمالي</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($sampleData['items'] as $item)
                                            <tr>
                                                <td class="col-index text-center {{ $currentTemplate->getSetting('table_columns.show_index') ? '' : 'template-hidden' }}">{{ $item['index'] }}</td>
                                                <td class="col-sku text-center font-mono {{ $currentTemplate->getSetting('table_columns.show_sku') ? '' : 'template-hidden' }}">{{ $item['sku'] }}</td>
                                                <td class="col-name font-medium">
                                                    {{ $item['name'] }}
                                                    @if(!empty($item['notes']))
                                                        <div class="col-notes text-[10px] text-slate-500 {{ $currentTemplate->getSetting('table_columns.show_notes') ? '' : 'template-hidden' }}">{{ $item['notes'] }}</div>
                                                    @endif
                                                </td>
                                                <td class="col-qty text-center font-semibold">{{ number_format($item['quantity'], 2) }}</td>
                                                <td class="col-unit text-center {{ $currentTemplate->getSetting('table_columns.show_unit') ? '' : 'template-hidden' }}">{{ $item['unit'] }}</td>
                                                <td class="col-price text-left font-mono {{ $currentTemplate->getSetting('table_columns.show_price') ? '' : 'template-hidden' }}">{{ number_format($item['unit_price'], 2) }}</td>
                                                <td class="col-discount text-left font-mono text-rose-600 {{ $currentTemplate->getSetting('table_columns.show_discount') ? '' : 'template-hidden' }}">{{ number_format($item['discount'], 2) }}</td>
                                                <td class="col-total text-left font-mono font-bold {{ $currentTemplate->getSetting('table_columns.show_total') ? '' : 'template-hidden' }}">{{ number_format($item['subtotal'], 2) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @endif

                            @if(isset($sampleData['financial']))
                                <div class="doc-mockup-totals-box">
                                    <table class="doc-mockup-totals-table">
                                        <tr class="row-subtotal {{ $currentTemplate->getSetting('financial_summary.show_subtotal') ? '' : 'template-hidden' }}">
                                            <td class="text-slate-600">المجموع:</td>
                                            <td class="text-left font-mono font-semibold">{{ number_format($sampleData['financial']['subtotal'], 2) }} ج.م</td>
                                        </tr>
                                        <tr class="row-discount {{ $currentTemplate->getSetting('financial_summary.show_discount') ? '' : 'template-hidden' }}">
                                            <td class="text-slate-600">الخصم:</td>
                                            <td class="text-left font-mono">- {{ number_format($sampleData['financial']['discount'], 2) }} ج.م</td>
                                        </tr>
                                        <tr class="row-shipping {{ $currentTemplate->getSetting('financial_summary.show_shipping') ? '' : 'template-hidden' }}">
                                            <td class="text-slate-600">الشحن:</td>
                                            <td class="text-left font-mono">+ {{ number_format($sampleData['financial']['shipping'], 2) }} ج.م</td>
                                        </tr>
                                        <tr class="row-net-total grand-total-row {{ $currentTemplate->getSetting('financial_summary.show_net_total') ? '' : 'template-hidden' }}">
                                            <td>الصافي:</td>
                                            <td class="text-left font-mono">{{ number_format($sampleData['financial']['net_total'], 2) }} ج.م</td>
                                        </tr>
                                        <tr class="row-paid {{ $currentTemplate->getSetting('financial_summary.show_paid') ? '' : 'template-hidden' }}">
                                            <td class="text-slate-600">المدفوع:</td>
                                            <td class="text-left font-mono">{{ number_format($sampleData['financial']['paid'], 2) }} ج.م</td>
                                        </tr>
                                        <tr class="row-remaining {{ $currentTemplate->getSetting('financial_summary.show_remaining') ? '' : 'template-hidden' }}">
                                            <td class="text-slate-600">المتبقي:</td>
                                            <td class="text-left font-mono">{{ number_format($sampleData['financial']['remaining'], 2) }} ج.م</td>
                                        </tr>
                                    </table>
                                </div>
                            @endif

                            <div class="preview-notes-box doc-mockup-notes {{ $currentTemplate->getSetting('notes_and_terms.show_notes') ? '' : 'template-hidden' }}">
                                <span class="notes-content">{{ $currentTemplate->getSetting('notes_and_terms.notes_text') }}</span>
                            </div>

                            <div class="preview-terms-box {{ $currentTemplate->getSetting('notes_and_terms.show_terms') ? '' : 'template-hidden' }}">
                                <span class="terms-content">{{ $currentTemplate->getSetting('notes_and_terms.terms_text') }}</span>
                            </div>

                            <div class="doc-mockup-signatures {{ $currentTemplate->getSetting('signatures.show_signatures') ? '' : 'template-hidden' }}">
                                <div class="sig-box">
                                    <span class="first-party-title">{{ $currentTemplate->getSetting('signatures.first_party_title', 'المستلم') }}</span>
                                    <div class="sig-line"></div>
                                </div>
                                <div class="sig-box">
                                    <span class="second-party-title">{{ $currentTemplate->getSetting('signatures.second_party_title', 'الكاشير') }}</span>
                                    <div class="sig-line"></div>
                                </div>
                            </div>
                        </div>
                    @else
                        <!-- =================================================================
                             A4 / A5 Enterprise Dispatch Layout (مطابق لصورة الفاتورة الفعلية)
                             ================================================================= -->
                        <div class="dispatch-mockup-container">
                            
                            <!-- 1. Top Branding Header (Company Identity, Logo & Status Badge) -->
                            <div class="dispatch-company-branding preview-header-brand">
                                <div class="flex items-center gap-3">
                                    <div class="preview-logo doc-mockup-logo-{{ $currentTemplate->getSetting('general.logo_size', 'md') }} {{ $currentTemplate->getSetting('general.show_logo') ? '' : 'template-hidden' }}">
                                        @if($activeLogo)
                                            <img src="{{ $activeLogo }}" alt="Logo" class="preview-logo-img" />
                                        @else
                                            <div class="preview-logo-placeholder">
                                                LOGO
                                            </div>
                                        @endif
                                    </div>
                                    <div class="preview-company-details">
                                        <div class="preview-company-name font-bold text-sm {{ $currentTemplate->getSetting('general.show_company_name') ? '' : 'template-hidden' }}">
                                            {{ $sampleData['company']['name'] ?? 'شركة كيان للتجارة والتوزيع' }}
                                        </div>
                                        <div class="preview-company-phone text-xs text-slate-600 {{ $currentTemplate->getSetting('general.show_company_phone') ? '' : 'template-hidden' }}">
                                            هاتف: {{ $sampleData['company']['phone'] ?? '01012345678' }}
                                        </div>
                                        <div class="preview-company-address text-xs text-slate-500 {{ $currentTemplate->getSetting('general.show_company_address') ? '' : 'template-hidden' }}">
                                            {{ $sampleData['company']['address'] ?? 'شارع الجمهورية، المنصورة' }}
                                        </div>
                                    </div>
                                </div>
                                <div class="preview-doc-badge dispatch-badge-original {{ $currentTemplate->getSetting('document_meta.show_status_badge') ? '' : 'template-hidden' }}">
                                    {{ $sampleData['document']['status'] ?? 'Original' }}
                                </div>
                            </div>

                            <!-- 2. Header: 3 Columns -->
                            <header class="dispatch-header">
                                <!-- Right Column: Document Number & Client Info -->
                                <div class="dispatch-col-right">
                                    <div class="dispatch-field preview-doc-number-row {{ $currentTemplate->getSetting('document_meta.show_doc_number') ? '' : 'template-hidden' }}">
                                        <span class="dispatch-field-label">رقم المستند :</span>
                                        <strong class="dispatch-field-val preview-doc-number font-numeric">{{ $sampleData['document']['number'] ?? 'INV-001' }}</strong>
                                    </div>
                                    <div class="dispatch-field preview-party-code-row">
                                        <span class="dispatch-field-label">كود الطرف :</span>
                                        <span class="dispatch-field-val font-numeric">{{ $sampleData['party']['code'] ?? '1' }}</span>
                                    </div>
                                    <div class="dispatch-field preview-party-name-row {{ $currentTemplate->getSetting('party_meta.show_party_name') ? '' : 'template-hidden' }}">
                                        <span class="dispatch-field-label preview-party-label">{{ $currentTemplate->getSetting('party_meta.party_type_label', 'اسم العميل') }} :</span>
                                        <strong class="dispatch-field-val preview-party-name">{{ $sampleData['party']['name'] ?? 'محل الأناقة' }}</strong>
                                    </div>
                                    <div class="dispatch-field preview-party-phone-row {{ $currentTemplate->getSetting('party_meta.show_party_phone') ? '' : 'template-hidden' }}">
                                        <span class="dispatch-field-label">رقم التليفون :</span>
                                        <span class="dispatch-field-val preview-party-phone font-numeric">{{ $sampleData['party']['phone'] ?? '01123456789' }}</span>
                                    </div>
                                    <div class="dispatch-field preview-party-tax-row {{ $currentTemplate->getSetting('party_meta.show_party_tax_no') ? '' : 'template-hidden' }}">
                                        <span class="dispatch-field-label">الرقم الضريبي :</span>
                                        <span class="dispatch-field-val preview-party-tax-no font-numeric">{{ $sampleData['party']['tax_no'] ?? '987-654-321' }}</span>
                                    </div>
                                </div>

                                <!-- Center Column: Official Stamp Box & QR -->
                                <div class="dispatch-col-center">
                                    <div class="dispatch-stamp-frame preview-stamp-box {{ $currentTemplate->getSetting('signatures.show_stamp_box') ? '' : 'template-hidden' }}">
                                        <span class="stamp-stars">★★★★★</span>
                                        <span class="stamp-title preview-doc-title">{{ $currentTemplate->getSetting('general.custom_title', $sampleData['document']['type_label']) }}</span>
                                        <span class="stamp-date font-numeric">{{ \Carbon\Carbon::parse($sampleData['document']['date'] ?? now())->format('d/m/Y') }}</span>
                                        <span class="stamp-stars">★★★★★</span>
                                    </div>
                                    <div class="preview-doc-subtitle text-[11px] text-slate-500 mt-1 {{ $currentTemplate->getSetting('general.custom_subtitle') ? '' : 'template-hidden' }}">
                                        {{ $currentTemplate->getSetting('general.custom_subtitle', '') }}
                                    </div>

                                    <!-- QR Code Center -->
                                    <div class="preview-doc-qr mt-2 {{ $currentTemplate->getSetting('document_meta.show_qr_code') ? '' : 'template-hidden' }}">
                                        <div class="preview-qr-box">
                                            <div class="q-dot"></div><div class="q-dot"></div><div class="q-space"></div><div class="q-dot"></div>
                                            <div class="q-dot"></div><div class="q-space"></div><div class="q-dot"></div><div class="q-space"></div>
                                            <div class="q-space"></div><div class="q-dot"></div><div class="q-space"></div><div class="q-dot"></div>
                                            <div class="q-dot"></div><div class="q-dot"></div><div class="q-dot"></div><div class="q-dot"></div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Left Column: Date & Time, Due Date, Warehouse, Shipping Company & Barcode -->
                                <div class="dispatch-col-left">
                                    <div class="dispatch-field preview-doc-date-row {{ $currentTemplate->getSetting('document_meta.show_doc_date') ? '' : 'template-hidden' }}">
                                        <span class="dispatch-field-label">التاريخ :</span>
                                        <span class="dispatch-field-val preview-doc-date font-numeric">{{ \Carbon\Carbon::parse($sampleData['document']['date'] ?? now())->format('d/m/Y 14:30:00') }}</span>
                                    </div>
                                    <div class="dispatch-field preview-doc-due-date-row {{ $currentTemplate->getSetting('document_meta.show_due_date') ? '' : 'template-hidden' }}">
                                        <span class="dispatch-field-label">تاريخ الاستحقاق :</span>
                                        <span class="dispatch-field-val preview-doc-due-date font-numeric">{{ \Carbon\Carbon::parse($sampleData['document']['due_date'] ?? now()->addDays(7))->format('d/m/Y') }}</span>
                                    </div>
                                    <div class="dispatch-field preview-doc-warehouse-row {{ $currentTemplate->getSetting('document_meta.show_warehouse') ? '' : 'template-hidden' }}">
                                        <span class="dispatch-field-label">المستودع :</span>
                                        <span class="dispatch-field-val preview-doc-warehouse">{{ $sampleData['document']['warehouse'] ?? 'المخزن الرئيسي' }}</span>
                                    </div>
                                    <div class="dispatch-field preview-party-address-row {{ $currentTemplate->getSetting('party_meta.show_party_address') ? '' : 'template-hidden' }}">
                                        <span class="dispatch-field-label">العنوان :</span>
                                        <span class="dispatch-field-val preview-party-address">{{ $sampleData['party']['address'] ?? 'شارع الجمهورية، المنصورة' }}</span>
                                    </div>
                                    <div class="dispatch-field preview-shipping-row">
                                        <span class="dispatch-field-label">شركة الشحن :</span>
                                        <span class="dispatch-field-val">{{ $sampleData['party']['shipping_company'] ?? 'عميل معرض / شحن داخلي' }}</span>
                                    </div>

                                    <!-- Barcode Left -->
                                    <div class="preview-doc-barcode mt-1 {{ $currentTemplate->getSetting('document_meta.show_barcode') ? '' : 'template-hidden' }}">
                                        <div class="preview-barcode-bars">
                                            <span class="b-med"></span><span class="b-gap-1"></span><span class="b-wide"></span><span class="b-gap-2"></span><span class="b-thin"></span><span class="b-gap-2"></span><span class="b-xwide"></span><span class="b-gap-1"></span><span class="b-med"></span><span class="b-gap-1"></span><span class="b-wide"></span><span class="b-med"></span><span class="b-gap-1"></span><span class="b-med"></span>
                                        </div>
                                        <span class="text-[10px] font-mono mt-0.5">{{ $sampleData['document']['barcode'] ?? 'INV20260042' }}</span>
                                    </div>
                                </div>
                            </header>

                            <!-- 3. High-Contrast Solid Grid Table -->
                            @if(isset($sampleData['items']))
                                <table class="dispatch-table doc-mockup-table {{ $currentTemplate->getSetting('table_columns.density') === 'compact' ? 'density-compact' : '' }}">
                                    <thead>
                                        <tr>
                                            <th class="col-index col-seq {{ $currentTemplate->getSetting('table_columns.show_index') ? '' : 'template-hidden' }}">م</th>
                                            <th class="col-sku {{ $currentTemplate->getSetting('table_columns.show_sku') ? '' : 'template-hidden' }}">كود الصنف</th>
                                            <th class="col-barcode {{ $currentTemplate->getSetting('table_columns.show_barcode') ? '' : 'template-hidden' }}">الباركود</th>
                                            <th class="col-name">اسم الصنف</th>
                                            <th class="col-qty">الكمية</th>
                                            <th class="col-unit {{ $currentTemplate->getSetting('table_columns.show_unit') ? '' : 'template-hidden' }}">وحده القياس</th>
                                            <th class="col-warehouse col-wh {{ $currentTemplate->getSetting('table_columns.show_warehouse', true) ? '' : 'template-hidden' }}">المخزن</th>
                                            <th class="col-price {{ $currentTemplate->getSetting('table_columns.show_price') ? '' : 'template-hidden' }}">السعر</th>
                                            <th class="col-discount {{ $currentTemplate->getSetting('table_columns.show_discount') ? '' : 'template-hidden' }}">الخصم</th>
                                            <th class="col-tax {{ $currentTemplate->getSetting('table_columns.show_tax') ? '' : 'template-hidden' }}">الضريبة</th>
                                            <th class="col-total {{ $currentTemplate->getSetting('table_columns.show_total') ? '' : 'template-hidden' }}">الاجمالي</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($sampleData['items'] as $item)
                                            <tr>
                                                <td class="col-index col-seq font-numeric {{ $currentTemplate->getSetting('table_columns.show_index') ? '' : 'template-hidden' }}">{{ $item['index'] }}</td>
                                                <td class="col-sku font-numeric {{ $currentTemplate->getSetting('table_columns.show_sku') ? '' : 'template-hidden' }}">{{ $item['sku'] }}</td>
                                                <td class="col-barcode font-numeric {{ $currentTemplate->getSetting('table_columns.show_barcode') ? '' : 'template-hidden' }}">{{ $item['barcode'] ?? '6221000' }}</td>
                                                <td class="col-name font-bold">
                                                    {{ $item['name'] }}
                                                    @if(!empty($item['notes']))
                                                        <div class="col-notes text-[10px] text-slate-500 font-normal {{ $currentTemplate->getSetting('table_columns.show_notes') ? '' : 'template-hidden' }}">{{ $item['notes'] }}</div>
                                                    @endif
                                                </td>
                                                <td class="col-qty font-numeric">{{ number_format($item['quantity'], 2) }}</td>
                                                <td class="col-unit {{ $currentTemplate->getSetting('table_columns.show_unit') ? '' : 'template-hidden' }}">{{ $item['unit'] }}</td>
                                                <td class="col-warehouse col-wh font-numeric {{ $currentTemplate->getSetting('table_columns.show_warehouse', true) ? '' : 'template-hidden' }}">{{ $item['warehouse'] ?? '20' }}</td>
                                                <td class="col-price font-numeric {{ $currentTemplate->getSetting('table_columns.show_price') ? '' : 'template-hidden' }}">{{ number_format($item['unit_price'], 2) }}</td>
                                                <td class="col-discount font-numeric text-rose-600 {{ $currentTemplate->getSetting('table_columns.show_discount') ? '' : 'template-hidden' }}">{{ number_format($item['discount'], 2) }}</td>
                                                <td class="col-tax font-numeric {{ $currentTemplate->getSetting('table_columns.show_tax') ? '' : 'template-hidden' }}">0.00</td>
                                                <td class="col-total font-numeric font-bold {{ $currentTemplate->getSetting('table_columns.show_total') ? '' : 'template-hidden' }}">{{ number_format($item['subtotal'], 2) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @elseif(isset($sampleData['transactions']))
                                <table class="dispatch-table doc-mockup-table {{ $currentTemplate->getSetting('table_columns.density') === 'compact' ? 'density-compact' : '' }}">
                                    <thead>
                                        <tr>
                                            <th class="text-center">التاريخ</th>
                                            <th class="text-center">رقم المرجع</th>
                                            <th class="text-right">البيان والحركة</th>
                                            <th class="text-left">مدين</th>
                                            <th class="text-left">دائن</th>
                                            <th class="text-left">الرصيد</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($sampleData['transactions'] as $tx)
                                            <tr>
                                                <td class="text-center text-xs font-numeric">{{ $tx['date'] }}</td>
                                                <td class="text-center text-xs font-numeric">{{ $tx['ref_number'] }}</td>
                                                <td class="text-xs">{{ $tx['description'] }}</td>
                                                <td class="text-left text-xs font-numeric">{{ $tx['debit'] > 0 ? number_format($tx['debit'], 2) : '-' }}</td>
                                                <td class="text-left text-xs font-numeric">{{ $tx['credit'] > 0 ? number_format($tx['credit'], 2) : '-' }}</td>
                                                <td class="text-left text-xs font-numeric font-bold">{{ number_format($tx['balance'], 2) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @endif

                            <!-- 4. Notes & Financial Totals Section -->
                            <div class="dispatch-bottom-section">
                                <div class="dispatch-notes-area">
                                    <div class="preview-notes-box {{ $currentTemplate->getSetting('notes_and_terms.show_notes') ? '' : 'template-hidden' }}">
                                        <div class="dispatch-notes-header">ملاحظات</div>
                                        <div class="dispatch-notes-body font-numeric notes-content">
                                            {{ $currentTemplate->getSetting('notes_and_terms.notes_text') }}
                                        </div>
                                    </div>
                                    <div class="preview-terms-box {{ $currentTemplate->getSetting('notes_and_terms.show_terms') ? '' : 'template-hidden' }}">
                                        <span class="terms-content">{{ $currentTemplate->getSetting('notes_and_terms.terms_text') }}</span>
                                    </div>
                                </div>

                                @if(isset($sampleData['financial']))
                                    <div class="dispatch-totals-area doc-mockup-totals-box">
                                        <table class="dispatch-totals-table font-numeric">
                                            <tr class="row-subtotal {{ $currentTemplate->getSetting('financial_summary.show_subtotal') ? '' : 'template-hidden' }}">
                                                <td class="tot-label">المجموع:</td>
                                                <td class="tot-val">{{ number_format($sampleData['financial']['subtotal'], 2) }} ج.م</td>
                                            </tr>
                                            <tr class="row-discount {{ $currentTemplate->getSetting('financial_summary.show_discount') ? '' : 'template-hidden' }}">
                                                <td class="tot-label">الخصم:</td>
                                                <td class="tot-val">- {{ number_format($sampleData['financial']['discount'], 2) }} ج.م</td>
                                            </tr>
                                            <tr class="row-shipping {{ $currentTemplate->getSetting('financial_summary.show_shipping') ? '' : 'template-hidden' }}">
                                                <td class="tot-label">الشحن:</td>
                                                <td class="tot-val">+ {{ number_format($sampleData['financial']['shipping'], 2) }} ج.م</td>
                                            </tr>
                                            <tr class="row-extra-expenses {{ $currentTemplate->getSetting('financial_summary.show_extra_expenses') ? '' : 'template-hidden' }}">
                                                <td class="tot-label">مصاريف إضافية:</td>
                                                <td class="tot-val">+ 0.00 ج.م</td>
                                            </tr>
                                            <tr class="row-tax {{ $currentTemplate->getSetting('financial_summary.show_tax') ? '' : 'template-hidden' }}">
                                                <td class="tot-label">ضريبة القيمة المضافة:</td>
                                                <td class="tot-val">+ 0.00 ج.م</td>
                                            </tr>
                                            <tr class="row-net-total tot-grand {{ $currentTemplate->getSetting('financial_summary.show_net_total') ? '' : 'template-hidden' }}">
                                                <td class="tot-label">الصافي:</td>
                                                <td class="tot-val">{{ number_format($sampleData['financial']['net_total'], 2) }} ج.م</td>
                                            </tr>
                                            <tr class="row-paid {{ $currentTemplate->getSetting('financial_summary.show_paid') ? '' : 'template-hidden' }}">
                                                <td class="tot-label">المدفوع:</td>
                                                <td class="tot-val">{{ number_format($sampleData['financial']['paid'], 2) }} ج.م</td>
                                            </tr>
                                            <tr class="row-remaining {{ $currentTemplate->getSetting('financial_summary.show_remaining') ? '' : 'template-hidden' }}">
                                                <td class="tot-label">المتبقي:</td>
                                                <td class="tot-val">{{ number_format($sampleData['financial']['remaining'], 2) }} ج.م</td>
                                            </tr>
                                        </table>
                                    </div>
                                @endif
                            </div>

                            <!-- 5. Signatures -->
                            <div class="doc-mockup-signatures {{ $currentTemplate->getSetting('signatures.show_signatures') ? '' : 'template-hidden' }}">
                                <div class="sig-box">
                                    <div class="first-party-title font-semibold">{{ $currentTemplate->getSetting('signatures.first_party_title', 'المستلم') }}</div>
                                    <div class="sig-line"></div>
                                </div>
                                <div class="sig-box">
                                    <div class="second-party-title font-semibold">{{ $currentTemplate->getSetting('signatures.second_party_title', 'المسؤول') }}</div>
                                    <div class="sig-line"></div>
                                </div>
                            </div>

                            <!-- 6. Pinned Bottom Info Bar -->
                            <div class="dispatch-footer-bar">
                                <div class="footer-box box-user">
                                    <span class="box-label">اسم المستخدم :</span>
                                    <strong class="box-val">{{ auth()->user()?->name ?? 'مدير النظام' }}</strong>
                                </div>
                                <div class="footer-box box-pages">
                                    <span>الصفحات من 1 إلي 1</span>
                                </div>
                                <div class="footer-box box-time">
                                    <span class="box-label">تاريخ ووقت الطباعة :</span>
                                    <span class="box-val font-numeric">{{ now()->format('d/m/Y h:i a') }}</span>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

    </div>
</div>
