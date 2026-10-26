@extends('layouts.app')

@section('control_panel')
    <x-control-panel title="إعدادات الشركة والنظام" :breadcrumbs="['الإدارة' => '#', $activeTab === 'print_templates' ? 'قوالب الطباعة' : ($activeTab === 'barcode' ? 'إعدادات الباركود' : ($activeTab === 'backups' ? 'النسخ الاحتياطي' : ($activeTab === 'departments' ? 'الأقسام والتصنيفات' : 'بيانات الشركة'))) => '#']">
        <x-slot:actions>
            @if($activeTab === 'barcode')
                <button type="submit" form="barcodeSettingsForm" class="erp-btn erp-btn-primary">
                    <x-icon name="save" size="16" />
                    <span>حفظ إعدادات الباركود</span>
                </button>
            @elseif($activeTab === 'print_templates')
                <button type="submit" form="printTemplateForm" class="erp-btn erp-btn-primary">
                    <x-icon name="save" size="16" />
                    <span>حفظ إعدادات القالب</span>
                </button>
            @elseif($activeTab === 'departments' || $activeTab === 'backups')
                <!-- Actions handled inside tab -->
            @else
                <button type="submit" form="settingsForm" class="erp-btn erp-btn-primary">
                    <x-icon name="save" size="16" />
                    <span>حفظ الإعدادات</span>
                </button>
            @endif
            <a href="{{ url('/') }}" class="erp-btn erp-btn-secondary">
                <x-icon name="arrow-left" size="16" />
                <span>العودة للرئيسية</span>
            </a>
        </x-slot:actions>

        <x-slot:extra>
            @if($settings->isMaintenance())
                <span class="erp-badge erp-badge-warning px-2.5 py-1 text-xs">
                    <x-icon name="alert-triangle" size="14" />
                    <span>حالة البرنامج: صيانة</span>
                </span>
            @else
                <span class="erp-badge erp-badge-success px-2.5 py-1 text-xs">
                    <x-icon name="check" size="14" />
                    <span>حالة البرنامج: نشط</span>
                </span>
            @endif
        </x-slot:extra>
    </x-control-panel>
@endsection

@section('content')
    <div data-page="settings">
        <!-- Flash Messages -->
        @if(session('status') === 'success')
            <div class="erp-alert erp-alert-success mb-4" role="alert">
                <x-icon name="check" size="16" />
                <div>{{ session('message', 'تم حفظ الإعدادات بنجاح.') }}</div>
            </div>
        @endif

        @if($errors->any())
            <div class="erp-alert erp-alert-danger mb-4" role="alert">
                <x-icon name="alert-triangle" size="16" />
                <div>
                    <strong>يرجى تصحيح الأخطاء التالية قبل المتابعة:</strong>
                    <ul class="my-1.5 mx-5 text-xs">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <!-- Settings Navigation Tabs (Matching Desktop App Tabs Layout) -->
        <div class="erp-card erp-tab-nav-card">
            <nav class="erp-settings-tab-bar">
                <!-- Tab 1: Company Info -->
                <a href="{{ route('settings.index', ['tab' => 'company']) }}" 
                   class="erp-tab-link {{ $activeTab === 'company' ? 'active' : '' }}">
                    <x-icon name="building-2" size="16" />
                    <span>بيانات وهوية الشركة</span>
                </a>

                <!-- Tab 2: Barcode Settings -->
                <a href="{{ route('settings.index', ['tab' => 'barcode']) }}" 
                   class="erp-tab-link {{ $activeTab === 'barcode' ? 'active' : '' }}">
                    <x-icon name="barcode" size="16" />
                    <span>إعدادات الباركود</span>
                </a>

                <!-- Tab 3: Print Template Center -->
                <a href="{{ route('settings.index', ['tab' => 'print_templates']) }}"
                   class="erp-tab-link {{ $activeTab === 'print_templates' ? 'active' : '' }}">
                    <x-icon name="printer" size="16" />
                    <span>قوالب الطباعة والمستندات</span>
                </a>

                <!-- Tab 4: Departments & Categories Import -->
                <a href="{{ route('settings.index', ['tab' => 'departments']) }}"
                   class="erp-tab-link {{ $activeTab === 'departments' ? 'active' : '' }}">
                    <x-icon name="folder" size="16" />
                    <span>الأقسام والتصنيفات</span>
                </a>

                <!-- Tab 5: Backups Management -->
                <a href="{{ route('settings.index', ['tab' => 'backups']) }}"
                   class="erp-tab-link {{ $activeTab === 'backups' ? 'active' : '' }}">
                    <x-icon name="hard-drive" size="16" />
                    <span>النسخ الاحتياطي (Backups)</span>
                </a>

                <!-- Tab 6: Printers & POS Hardware Management -->
                <a href="{{ route('settings.index', ['tab' => 'printers']) }}"
                   class="erp-tab-link {{ $activeTab === 'printers' ? 'active' : '' }}">
                    <x-icon name="printer" size="16" />
                    <span>إدارة الطابعات وأجهزة الكاشير</span>
                </a>

                <span class="erp-tab-link disabled" title="مرحلة قادمة في المخطط">
                    <x-icon name="trending-up" size="16" />
                    <span>إعدادات التقارير</span>
                    <span class="erp-badge erp-badge-neutral text-[10px] py-0.5 px-1.5">قريباً</span>
                </span>

                <span class="erp-tab-link disabled" title="مرحلة قادمة في المخطط">
                    <x-icon name="shield" size="16" />
                    <span>صلاحيات الكاشير</span>
                    <span class="erp-badge erp-badge-neutral text-[10px] py-0.5 px-1.5">قريباً</span>
                </span>
            </nav>
        </div>

        @if($activeTab === 'print_templates')
            <!-- TAB 3: PRINT TEMPLATE VISUAL DESIGNER -->
            @include('settings.tabs.print-templates')
        @elseif($activeTab === 'departments')
            <!-- TAB 4: DEPARTMENTS & CATEGORIES IMPORT -->
            @include('settings.tabs.departments')
        @elseif($activeTab === 'backups')
            <!-- TAB 5: DATABASE & SYSTEM BACKUPS -->
            @include('settings.tabs.backups')
        @elseif($activeTab === 'printers')
            <!-- TAB 6: PRINTERS & POS HARDWARE SETUP -->
            @include('settings.tabs.printers')
        @elseif($activeTab === 'barcode')
            <!-- TAB 2: BARCODE PRINTER SETTINGS -->
            <div class="grid grid-cols-[minmax(400px,1fr)_340px] gap-5 items-start">
                <!-- Form Left / Main Column -->
                <form id="barcodeSettingsForm" action="{{ route('settings.barcode.update') }}" method="POST">
                    @csrf
                    @method('PUT')

                    <!-- Card 1: Printer Hardware & Dimension Specifications -->
                    <div class="erp-card mb-5">
                        <div class="erp-card-header">
                            <div class="erp-card-title">
                                <x-icon name="printer" size="18" />
                                <span>أبعاد وملفات طابعة الباركود الحرارية</span>
                            </div>
                            <small class="text-[var(--text-muted)]">تطابق إعدادات طابعة Xprinter XP-233B وبكرات الاستيكر</small>
                        </div>
                        <div class="erp-card-body">
                            <div class="grid grid-cols-2 gap-4">
                                <!-- 1. Barcode Prefix -->
                                <div class="erp-form-group">
                                    <label for="barcode_prefix" class="erp-form-label">
                                        <span>بادئة الباركود</span>
                                        <span class="required">*</span>
                                    </label>
                                    <input
                                        type="text"
                                        id="barcode_prefix"
                                        name="barcode_prefix"
                                        class="erp-input font-numeric @error('barcode_prefix') is-invalid @enderror"
                                        value="{{ old('barcode_prefix', $barcodeSettings->barcode_prefix) }}"
                                        placeholder="02"
                                        maxlength="10"
                                        required
                                    >
                                    <span class="erp-form-hint">الرقم التعريفي الذي تبدأ به الأكواد المولدة آلياً (مثل 02).</span>
                                    @error('barcode_prefix')
                                        <span class="erp-error-msg">{{ $message }}</span>
                                    @enderror
                                </div>

                                <!-- 2. Printer Name -->
                                <div class="erp-form-group">
                                    <label for="printer_name" class="erp-form-label">
                                        <span>اسم الطابعة</span>
                                        <span class="required">*</span>
                                    </label>
                                    <input
                                        type="text"
                                        id="printer_name"
                                        name="printer_name"
                                        class="erp-input @error('printer_name') is-invalid @enderror"
                                        value="{{ old('printer_name', $barcodeSettings->printer_name) }}"
                                        placeholder="Xprinter XP-233B"
                                        required
                                    >
                                    <span class="erp-form-hint">اسم الطابعة الحرارية المعرفة على الجهاز.</span>
                                    @error('printer_name')
                                        <span class="erp-error-msg">{{ $message }}</span>
                                    @enderror
                                </div>

                                <!-- 3. Sticker Width (mm) -->
                                <div class="erp-form-group">
                                    <label for="label_width_mm" class="erp-form-label">
                                        <span>عرض الاستيكر (مم)</span>
                                        <span class="required">*</span>
                                    </label>
                                    <input
                                        type="number"
                                        id="label_width_mm"
                                        name="label_width_mm"
                                        class="erp-input font-numeric @error('label_width_mm') is-invalid @enderror"
                                        value="{{ old('label_width_mm', $barcodeSettings->label_width_mm) }}"
                                        min="10"
                                        max="150"
                                        required
                                    >
                                    <span class="erp-form-hint">العرض الفعلي للملصق (المقاس الشائع: 38 مم).</span>
                                    @error('label_width_mm')
                                        <span class="erp-error-msg">{{ $message }}</span>
                                    @enderror
                                </div>

                                <!-- 4. Sticker Height (mm) -->
                                <div class="erp-form-group">
                                    <label for="label_height_mm" class="erp-form-label">
                                        <span>ارتفاع الاستيكر (مم)</span>
                                        <span class="required">*</span>
                                    </label>
                                    <input
                                        type="number"
                                        id="label_height_mm"
                                        name="label_height_mm"
                                        class="erp-input font-numeric @error('label_height_mm') is-invalid @enderror"
                                        value="{{ old('label_height_mm', $barcodeSettings->label_height_mm) }}"
                                        min="10"
                                        max="150"
                                        required
                                    >
                                    <span class="erp-form-hint">طول/ارتفاع الملصق باتجاه السحب (25 مم أو 15 مم).</span>
                                    @error('label_height_mm')
                                        <span class="erp-error-msg">{{ $message }}</span>
                                    @enderror
                                </div>

                                <!-- 5. Barcode Width (px) -->
                                <div class="erp-form-group">
                                    <label for="barcode_width_px" class="erp-form-label">
                                        <span>عرض صورة الباركود (بكسل)</span>
                                        <span class="required">*</span>
                                    </label>
                                    <input
                                        type="number"
                                        id="barcode_width_px"
                                        name="barcode_width_px"
                                        class="erp-input font-numeric @error('barcode_width_px') is-invalid @enderror"
                                        value="{{ old('barcode_width_px', $barcodeSettings->barcode_width_px) }}"
                                        min="50"
                                        max="1000"
                                        required
                                    >
                                    <span class="erp-form-hint">العرض الأقصى للباركود (المثالي: 260 بكسل).</span>
                                    @error('barcode_width_px')
                                        <span class="erp-error-msg">{{ $message }}</span>
                                    @enderror
                                </div>

                                <!-- 6. Barcode Height (px) -->
                                <div class="erp-form-group">
                                    <label for="barcode_height_px" class="erp-form-label">
                                        <span>ارتفاع صورة الباركود (بكسل)</span>
                                        <span class="required">*</span>
                                    </label>
                                    <input
                                        type="number"
                                        id="barcode_height_px"
                                        name="barcode_height_px"
                                        class="erp-input font-numeric @error('barcode_height_px') is-invalid @enderror"
                                        value="{{ old('barcode_height_px', $barcodeSettings->barcode_height_px) }}"
                                        min="15"
                                        max="300"
                                        required
                                    >
                                    <span class="erp-form-hint">ارتفاع خطوط الباركود (المثالي: 80 لـ 25مم، أو 30 لـ 15مم).</span>
                                    @error('barcode_height_px')
                                        <span class="erp-error-msg">{{ $message }}</span>
                                    @enderror
                                </div>

                                <!-- 7. Product Font Size -->
                                <div class="erp-form-group">
                                    <label for="product_font_size" class="erp-form-label">
                                        <span>حجم خط اسم المنتج</span>
                                        <span class="required">*</span>
                                    </label>
                                    <input
                                        type="number"
                                        id="product_font_size"
                                        name="product_font_size"
                                        class="erp-input font-numeric @error('product_font_size') is-invalid @enderror"
                                        value="{{ old('product_font_size', $barcodeSettings->product_font_size) }}"
                                        min="8"
                                        max="30"
                                        required
                                    >
                                    <span class="erp-form-hint">حجم خط اسم الصنف على الملصق (المثالي: 14 إلى 17).</span>
                                    @error('product_font_size')
                                        <span class="erp-error-msg">{{ $message }}</span>
                                    @enderror
                                </div>

                                <!-- 8. Details Font Size -->
                                <div class="erp-form-group">
                                    <label for="details_font_size" class="erp-form-label">
                                        <span>حجم خط تفاصيل الباركود</span>
                                        <span class="required">*</span>
                                    </label>
                                    <input
                                        type="number"
                                        id="details_font_size"
                                        name="details_font_size"
                                        class="erp-input font-numeric @error('details_font_size') is-invalid @enderror"
                                        value="{{ old('details_font_size', $barcodeSettings->details_font_size) }}"
                                        min="6"
                                        max="24"
                                        required
                                    >
                                    <span class="erp-form-hint">حجم خط كود الصنف والسعر واسم الشركة (المثالي: 8 إلى 11).</span>
                                    @error('details_font_size')
                                        <span class="erp-error-msg">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <!-- Presets Quick Bar -->
                            <div class="mt-4 p-3 bg-[var(--bg-surface-alt)] rounded-sm border border-dashed border-[var(--border-color)] flex items-center justify-between flex-wrap gap-2">
                                <span class="text-xs text-[var(--text-muted)] font-semibold inline-flex items-center gap-1">
                                    <x-icon name="zap" size="13" />
                                    <span>قوالب جاهزة سريعة للأبعاد:</span>
                                </span>
                                <div class="flex gap-2">
                                    <button type="button" data-action="apply-barcode-preset" data-width="38" data-height="25" data-bar-w="260" data-bar-h="80" data-prod-font="14" data-det-font="8" class="erp-btn erp-btn-secondary text-[11.5px] py-1 px-2.5">
                                        <span>بكرة 38×25 مم (الافتراضي)</span>
                                    </button>
                                    <button type="button" data-action="apply-barcode-preset" data-width="38" data-height="15" data-bar-w="260" data-bar-h="40" data-prod-font="12" data-det-font="7" class="erp-btn erp-btn-secondary text-[11.5px] py-1 px-2.5">
                                        <span>بكرة 38×15 مم (صغير)</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card 2: Checkbox Display Elements on the Sticker -->
                    <div class="erp-card mb-5">
                        <div class="erp-card-header">
                            <div class="erp-card-title">
                                <x-icon name="sliders" size="18" />
                                <span>خيارات عناصر الطباعة الظاهرة على الاستيكر</span>
                            </div>
                            <small class="text-[var(--text-muted)]">حدد ما ترغب في إظهاره أو إخفائه على ملصق الباركود</small>
                        </div>
                        <div class="erp-card-body">
                            <div class="flex flex-col gap-3.5">
                                <!-- Checkbox 1: Company Name -->
                                <div class="bg-[var(--bg-surface-alt)] py-3 px-4 rounded-sm border border-[var(--border-color)]">
                                    <label class="flex items-center gap-2.5 cursor-pointer font-bold text-[var(--text-main)]">
                                        <input
                                            type="checkbox"
                                            id="show_company_name"
                                            name="show_company_name"
                                            value="1"
                                            {{ old('show_company_name', $barcodeSettings->show_company_name) ? 'checked' : '' }}
                                            class="w-4.5 h-4.5 accent-[var(--primary)]"
                                        >
                                        <span>إظهار اسم الشركة أعلى الاستيكر</span>
                                    </label>
                                    <div class="mt-2 ms-7">
                                        <input
                                            type="text"
                                            id="company_name_override"
                                            name="company_name_override"
                                            class="erp-input text-xs"
                                            value="{{ old('company_name_override', $barcodeSettings->company_name_override ?? $settings->company_name) }}"
                                            placeholder="شركة كيان للتجارة والتوزيع"
                                        >
                                        <span class="erp-form-hint">الاسم المطبوع على الاستيكر (افتراضياً: اسم الشركة من الإعدادات العامة).</span>
                                    </div>
                                </div>

                                <!-- Checkbox 2: Product Name -->
                                <div class="bg-[var(--bg-surface-alt)] py-3 px-4 rounded-sm border border-[var(--border-color)]">
                                    <label class="flex items-center gap-2.5 cursor-pointer font-bold text-[var(--text-main)]">
                                        <input
                                            type="checkbox"
                                            id="show_product_name"
                                            name="show_product_name"
                                            value="1"
                                            {{ old('show_product_name', $barcodeSettings->show_product_name) ? 'checked' : '' }}
                                            class="w-4.5 h-4.5 accent-[var(--primary)]"
                                        >
                                        <span>إظهار اسم الصنف / المنتج على الاستيكر</span>
                                    </label>
                                    <small class="block ms-7 text-[var(--text-muted)] text-[11.5px] mt-1">
                                        يطبع اسم الصنف (مثل: فرشاة شعر / طقم لؤلؤ) بالخط العربي العريض.
                                    </small>
                                </div>

                                <!-- Checkbox 3: Barcode Bars -->
                                <div class="bg-[var(--bg-surface-alt)] py-3 px-4 rounded-sm border border-[var(--border-color)]">
                                    <label class="flex items-center gap-2.5 cursor-pointer font-bold text-[var(--text-main)]">
                                        <input
                                            type="checkbox"
                                            id="show_barcode_bars"
                                            name="show_barcode_bars"
                                            value="1"
                                            {{ old('show_barcode_bars', $barcodeSettings->show_barcode_bars) ? 'checked' : '' }}
                                            class="w-4.5 h-4.5 accent-[var(--primary)]"
                                        >
                                        <span>إظهار خطوط الباركود (Code 128)</span>
                                    </label>
                                    <small class="block ms-7 text-[var(--text-muted)] text-[11.5px] mt-1">
                                        الخطوط الرأسية القابلة للمسح الضوئي بأجهزة قراءة الباركود والكاميرا.
                                    </small>
                                </div>

                                <!-- Checkbox 4: Item SKU -->
                                <div class="bg-[var(--bg-surface-alt)] py-3 px-4 rounded-sm border border-[var(--border-color)]">
                                    <label class="flex items-center gap-2.5 cursor-pointer font-bold text-[var(--text-main)]">
                                        <input
                                            type="checkbox"
                                            id="show_item_sku"
                                            name="show_item_sku"
                                            value="1"
                                            {{ old('show_item_sku', $barcodeSettings->show_item_sku) ? 'checked' : '' }}
                                            class="w-4.5 h-4.5 accent-[var(--primary)]"
                                        >
                                        <span>إظهار كود ورقم الصنف (ITEM-NO / SKU)</span>
                                    </label>
                                    <small class="block ms-7 text-[var(--text-muted)] text-[11.5px] mt-1">
                                        يطبع كود الصنف أسفل الباركود (مثل: HB-262 أو KY-NCK-001).
                                    </small>
                                </div>

                                <!-- Price Option: Wholesale vs Retail -->
                                <div class="bg-[var(--bg-surface-alt)] py-3 px-4 rounded-sm border border-[var(--border-color)]">
                                    <strong class="flex items-center gap-1.5 text-[var(--text-main)] text-sm mb-2">
                                        <x-icon name="banknote" size="16" />
                                        <span>خيار طباعة سعر البيع (جملة ولا تجزئة):</span>
                                    </strong>
                                    <div class="grid grid-cols-[repeat(auto-fit,minmax(180px,1fr))] gap-2.5">
                                        <!-- None -->
                                        <label class="erp-radio-pill">
                                            <input type="radio" name="print_price_type" value="none" {{ old('print_price_type', $barcodeSettings->print_price_type) === 'none' ? 'checked' : '' }}>
                                            <span>بدون إظهار السعر</span>
                                        </label>

                                        <!-- Retail -->
                                        <label class="erp-radio-pill">
                                            <input type="radio" name="print_price_type" value="retail" {{ old('print_price_type', $barcodeSettings->print_price_type) === 'retail' ? 'checked' : '' }}>
                                            <span>سعر بيع التجزئة (قطاعي)</span>
                                        </label>

                                        <!-- Wholesale -->
                                        <label class="erp-radio-pill">
                                            <input type="radio" name="print_price_type" value="wholesale" {{ old('print_price_type', $barcodeSettings->print_price_type) === 'wholesale' ? 'checked' : '' }}>
                                            <span>سعر بيع الجملة</span>
                                        </label>

                                        <!-- Both -->
                                        <label class="erp-radio-pill">
                                            <input type="radio" name="print_price_type" value="both" {{ old('print_price_type', $barcodeSettings->print_price_type) === 'both' ? 'checked' : '' }}>
                                            <span>كلاهما (جملة وتجزئة)</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Bar -->
                    <div class="flex items-center gap-3 mt-2.5">
                        <button type="submit" class="erp-btn erp-btn-primary py-2.5 px-6 text-sm">
                            <x-icon name="save" size="16" />
                            <span>حفظ إعدادات الباركود</span>
                        </button>
                        <a href="{{ url('/') }}" class="erp-btn erp-btn-secondary py-2.5 px-5">
                            <span>إلغاء</span>
                        </a>
                    </div>
                </form>

                <!-- Column 2: Live Interactive Sticker Preview -->
                <div class="sticky top-[70px]">
                    <div class="erp-card p-4 text-center">
                        <div class="erp-card-header p-0 pb-3 border-b border-[var(--border-color)] mb-3.5">
                            <div class="erp-card-title text-sm">
                                <x-icon name="eye" size="16" />
                                <span>معاينة حية للملصق</span>
                            </div>
                            <span id="previewDimensionsBadge" class="erp-badge erp-badge-neutral font-numeric text-[11px]">
                                {{ $barcodeSettings->label_width_mm }}×{{ $barcodeSettings->label_height_mm }} مم
                            </span>
                        </div>

                        <!-- Thermal Sticker Live Display Box -->
                        <div class="erp-sticker-preview-box">
                            <div id="liveStickerFrame" class="erp-live-sticker-frame">
                                <!-- Top: Company Name -->
                                <div id="previewCompanyText" class="erp-preview-company-text">
                                    {{ $barcodeSettings->getEffectiveCompanyName() }}
                                </div>

                                <!-- Middle Top: Product Name -->
                                <div id="previewProductText" class="erp-preview-product-name">
                                    فرشاة شعر كوري راقية
                                </div>

                                <!-- Barcode Bars -->
                                <div id="previewBarcodeBars" class="erp-preview-barcode-container">
                                    <svg id="previewBarcodeSvg" viewBox="0 0 200 40" width="85%" height="35" preserveAspectRatio="none">
                                        <g fill="#000000">
                                            <rect x="0" y="0" width="3" height="40"/>
                                            <rect x="5" y="0" width="2" height="40"/>
                                            <rect x="10" y="0" width="4" height="40"/>
                                            <rect x="16" y="0" width="2" height="40"/>
                                            <rect x="20" y="0" width="6" height="40"/>
                                            <rect x="28" y="0" width="3" height="40"/>
                                            <rect x="33" y="0" width="2" height="40"/>
                                            <rect x="38" y="0" width="5" height="40"/>
                                            <rect x="45" y="0" width="3" height="40"/>
                                            <rect x="50" y="0" width="2" height="40"/>
                                            <rect x="55" y="0" width="4" height="40"/>
                                            <rect x="62" y="0" width="6" height="40"/>
                                            <rect x="70" y="0" width="2" height="40"/>
                                            <rect x="75" y="0" width="4" height="40"/>
                                            <rect x="82" y="0" width="3" height="40"/>
                                            <rect x="88" y="0" width="5" height="40"/>
                                            <rect x="95" y="0" width="2" height="40"/>
                                            <rect x="100" y="0" width="6" height="40"/>
                                            <rect x="108" y="0" width="3" height="40"/>
                                            <rect x="114" y="0" width="4" height="40"/>
                                            <rect x="120" y="0" width="2" height="40"/>
                                            <rect x="125" y="0" width="5" height="40"/>
                                            <rect x="133" y="0" width="3" height="40"/>
                                            <rect x="138" y="0" width="2" height="40"/>
                                            <rect x="143" y="0" width="6" height="40"/>
                                            <rect x="151" y="0" width="4" height="40"/>
                                            <rect x="158" y="0" width="2" height="40"/>
                                            <rect x="163" y="0" width="5" height="40"/>
                                            <rect x="171" y="0" width="3" height="40"/>
                                            <rect x="176" y="0" width="2" height="40"/>
                                            <rect x="181" y="0" width="6" height="40"/>
                                            <rect x="190" y="0" width="3" height="40"/>
                                            <rect x="196" y="0" width="4" height="40"/>
                                        </g>
                                    </svg>
                                </div>

                                <!-- Bottom: SKU & Price -->
                                <div id="previewBottomDetails" class="erp-preview-item-code">
                                    <span id="previewSkuSpan">ITEM-NO: HB-262</span>
                                    <span id="previewPriceSpan" class="ms-1.5"></span>
                                </div>
                            </div>
                        </div>

                        <div class="mt-3 text-[11px] text-[var(--text-muted)] flex items-center justify-center gap-1.5">
                            <x-icon name="info" size="13" />
                            <span>تتغير المعاينة أعلاه تلقائياً بمجرد تعديل أي مقاس أو خيار.</span>
                        </div>
                    </div>
                </div>
            </div>

        @else
            <!-- TAB 1: COMPANY INFORMATION & SYSTEM STATUS -->
            <form id="settingsForm" action="{{ route('settings.update') }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <!-- Section A: Company Information -->
                <div class="erp-card mb-5">
                    <div class="erp-card-header">
                        <div class="erp-card-title">
                            <x-icon name="building-2" size="18" />
                            <span>بيانات وهوية الشركة</span>
                        </div>
                        <small class="text-[var(--text-muted)]">المعلومات الرسمية التي تظهر في الترويسة والقوائم</small>
                    </div>
                    <div class="erp-card-body">
                        <div class="erp-form-grid">
                            <!-- Company Name -->
                            <div class="erp-form-group">
                                <label for="company_name" class="erp-form-label">
                                    <span>اسم الشركة</span>
                                    <span class="required" title="حقل إجباري">*</span>
                                </label>
                                <input
                                    type="text"
                                    id="company_name"
                                    name="company_name"
                                    class="erp-input @error('company_name') is-invalid @enderror"
                                    value="{{ old('company_name', $settings->company_name) }}"
                                    placeholder="مثال: Kayan Fashion Accessories"
                                    required
                                >
                                <span class="erp-form-hint">يظهر اسم الشركة في الشريط الجانبي وأعلى فواتير ومستندات النظام.</span>
                                @error('company_name')
                                    <span class="erp-error-msg">{{ $message }}</span>
                                @enderror
                            </div>

                            <!-- Company Phone Number -->
                            <div class="erp-form-group">
                                <label for="company_phone" class="erp-form-label">
                                    <span>رقم الهاتف / واتساب الشركة</span>
                                </label>
                                <input
                                    type="text"
                                    id="company_phone"
                                    name="company_phone"
                                    class="erp-input font-numeric text-right @error('company_phone') is-invalid @enderror"
                                    value="{{ old('company_phone', $settings->company_phone) }}"
                                    placeholder="مثال: +20 100 123 4567"
                                    dir="ltr"
                                >
                                <span class="erp-form-hint">يقبل علامة + الدولية والأرقام للتواصل والمراسلات.</span>
                                @error('company_phone')
                                    <span class="erp-error-msg">{{ $message }}</span>
                                @enderror
                            </div>

                            <!-- Company Logo Upload -->
                            <div class="erp-form-group erp-form-full">
                                <label class="erp-form-label">
                                    <span>شعار الشركة (Logo)</span>
                                </label>
                                <div class="erp-logo-upload-box">
                                    <!-- Preview Frame -->
                                    <div class="erp-logo-preview-frame" id="logoPreviewContainer">
                                        @if($settings->logo_url)
                                            <img src="{{ $settings->logo_url }}" id="logoPreviewImg" alt="شعار الشركة">
                                        @else
                                            <span id="logoPlaceholder" class="text-2xl font-bold text-[var(--primary)]">
                                                {{ mb_substr($settings->company_name, 0, 1) }}
                                            </span>
                                            <img src="" id="logoPreviewImg" alt="معاينة الشعار" class="hidden">
                                        @endif
                                    </div>

                                    <!-- Upload Inputs & Guidelines -->
                                    <div class="flex-1">
                                        <input
                                            type="file"
                                            id="companyLogoInput"
                                            name="company_logo"
                                            accept="image/png,image/jpeg,image/webp"
                                            class="erp-input py-1.5 px-3 h-auto @error('company_logo') is-invalid @enderror"
                                        >
                                        <div class="erp-form-hint mt-1.5">
                                            الصيغ المدعومة: <strong>PNG, JPG, JPEG, WebP</strong>. الحد الأقصى للحجم: <strong>2 ميجابايت</strong>.
                                            يتم تخزين الشعار بأمان في مسار التخزين العام للنظام.
                                        </div>
                                        @error('company_logo')
                                            <span class="erp-error-msg">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section B: Program Status -->
                <div class="erp-card mb-5">
                    <div class="erp-card-header">
                        <div class="erp-card-title">
                            <x-icon name="zap" size="18" />
                            <span>حالة وتشغيل البرنامج (Program Status)</span>
                        </div>
                        <small class="text-[var(--text-muted)]">التحكم في وسم وتنبيه وضع تشغيل النظام</small>
                    </div>
                    <div class="erp-card-body">
                        <div class="erp-form-group">
                            <label class="erp-form-label">
                                <span>اختر الحالة التشغيلية الحالية:</span>
                            </label>

                            <div class="erp-radio-cards-grid">
                                <!-- Option 1: Active -->
                                <label class="erp-radio-card {{ old('program_status', $settings->program_status) === 'active' ? 'selected' : '' }}" id="cardStatusActive">
                                    <input
                                        type="radio"
                                        name="program_status"
                                        value="active"
                                        {{ old('program_status', $settings->program_status) === 'active' ? 'checked' : '' }}
                                    >
                                    <div class="erp-radio-card-body">
                                        <div class="erp-radio-card-title">
                                            <span class="erp-badge erp-badge-success">نشط (Active)</span>
                                        </div>
                                        <div class="erp-radio-card-desc">
                                            البرنامج في وضع التشغيل الكامل اليومي، وتظهر علامة النظام النشط باللون الأخضر.
                                        </div>
                                    </div>
                                </label>

                                <!-- Option 2: Maintenance -->
                                <label class="erp-radio-card {{ old('program_status', $settings->program_status) === 'maintenance' ? 'selected' : '' }}" id="cardStatusMaintenance">
                                    <input
                                        type="radio"
                                        name="program_status"
                                        value="maintenance"
                                        {{ old('program_status', $settings->program_status) === 'maintenance' ? 'checked' : '' }}
                                    >
                                    <div class="erp-radio-card-body">
                                        <div class="erp-radio-card-title">
                                            <span class="erp-badge erp-badge-warning">صيانة (Maintenance)</span>
                                        </div>
                                        <div class="erp-radio-card-desc">
                                            تفعيل شارة الصيانة التوضيحية دون حظر دخول المستخدمين أو تعطيل أي من وظائف النظام.
                                        </div>
                                    </div>
                                </label>
                            </div>

                            @error('program_status')
                                <span class="erp-error-msg">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Form Submit Bar -->
                <div class="flex items-center justify-start gap-3 mt-2.5">
                    <button type="submit" class="erp-btn erp-btn-primary py-2.5 px-6 text-sm">
                        <x-icon name="save" size="16" />
                        <span>حفظ الإعدادات</span>
                    </button>
                    <a href="{{ url('/') }}" class="erp-btn erp-btn-secondary py-2.5 px-5">
                        <span>إلغاء</span>
                    </a>
                </div>
            </form>
        @endif
    </div>
@endsection
